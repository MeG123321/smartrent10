<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, 
           p.title, p.city, p.price, p.image,
           u.name AS owner_name, u.id AS owner_id
    FROM assignments a
    LEFT JOIN properties p ON a.property_id = p.id
    LEFT JOIN users u ON p.owner_id = u.id
    WHERE a.tenant_id = :uid AND a.status = 'confirmed'
    ORDER BY a.created_at DESC
");
$stmt->execute(['uid' => $user_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('format_price')) {
    function format_price($amount): string {
        if ($amount === null || $amount === '' || !is_numeric($amount)) return '-';
        $val = (float)$amount;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', ' ') . ' zł';
        }
        return number_format($val, 2, ',', ' ') . ' zł';
    }
}

if (!function_exists('calculate_days_until_payment')) {
    function calculate_days_until_payment($created_at): array {
        $createdDate = new DateTime($created_at);
        $paymentDate = clone $createdDate;
        $paymentDate->modify('+30 days');
        
        $now = new DateTime();
        $diff = $now->diff($paymentDate);
        
        $daysRemaining = (int)$diff->format('%r%a');
        
        return [
            'days' => abs($daysRemaining),
            'isPast' => $daysRemaining < 0,
            'paymentDate' => $paymentDate->format('Y-m-d')
        ];
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Wynajęte mieszkania — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .payment-info {
      padding: 12px;
      border-radius: 4px;
      margin-top: 8px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .payment-info.pending {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #ffffff;
      border: 1px solid #764ba2;
      box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    .payment-info.overdue {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: #ffffff;
      border: 1px solid #f5576c;
      box-shadow: 0 2px 4px rgba(245, 87, 108, 0.3);
    }
    .payment-info.soon {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
      color: #333333;
      border: 1px solid #fee140;
      box-shadow: 0 2px 4px rgba(250, 112, 154, 0.3);
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Wynajęte mieszkania</h2>
  
  <div style="margin-bottom:12px">
    <a class="btn btn-ghost" href="user_panel.php">Powrót do panelu</a>
    <a class="btn" href="property_list.php">Przeglądaj oferty</a>
  </div>

  <?php if (empty($rentals)): ?>
    <div class="panel">
      <p>Nie wynajmujesz obecnie żadnych mieszkań.</p>
      <p><a class="btn" href="property_list.php">Przeglądaj dostępne oferty</a></p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($rentals as $r): ?>
        <?php
          $imgSrc = !empty($r['image']) 
              ? 'uploads/properties/' . rawurlencode($r['image'])
              : 'assets/img/placeholder.png';
          $statusText = '';
          $statusClass = '';
          
          switch($r['status']) {
              case 'confirmed':
                  $statusText = 'Wynajęte';
                  $statusClass = 'status-active';
                  break;
              case 'completed':
                  $statusText = 'Zakończone';
                  $statusClass = 'status-completed';
                  break;
              case 'cancelled':
                  $statusText = 'Anulowane';
                  $statusClass = 'status-cancelled';
                  break;
              default:
                  $statusText = 'Nieznany';
                  $statusClass = 'status-unknown';
          }
          
          $paymentInfo = calculate_days_until_payment($r['created_at']);
          $paymentClass = 'pending';
          $paymentMessage = '';
          
          if ($paymentInfo['isPast']) {
              $paymentClass = 'overdue';
              $paymentMessage = "⚠️ ZALEGŁA! Płatność należy była " . $paymentInfo['days'] . " dni temu (" . $paymentInfo['paymentDate'] . ")";
          } elseif ($paymentInfo['days'] <= 7) {
              $paymentClass = 'soon';
              $paymentMessage = "⏰ Płatność za " . $paymentInfo['days'] . " dni (" . $paymentInfo['paymentDate'] . ")";
          } else {
              $paymentMessage = "Płatność za " . $paymentInfo['days'] . " dni (" . $paymentInfo['paymentDate'] . ")";
          }
        ?>
        <article class="card">
          <div class="card-img" style="background-image:url('<?=htmlspecialchars($imgSrc, ENT_QUOTES)?>')">
            <span class="badge <?=$statusClass?>"><?=htmlspecialchars($statusText)?></span>
          </div>
          <div class="card-body">
            <h3><?=htmlspecialchars($r['title'] ?? 'Bez tytułu')?></h3>
            <p class="muted"><?=htmlspecialchars($r['city'] ?? '-')?></p>
            <p class="muted" style="font-size:0.9rem">
              Właściciel: <?=htmlspecialchars($r['owner_name'] ?? 'Nieznany')?>
            </p>
            <div class="price"><?=format_price($r['price'])?> / miesiąc</div>
            
            <!-- Informacja o płatności z kolorem jak przycisk -->
            <a class="btn btn-sm" href="javascript:void(0);">
              <?=htmlspecialchars($paymentMessage)?>
            </a>
            
            <?php if ($r['start_date'] || $r['end_date']): ?>
              <p style="font-size:0.9rem; margin-top:8px">
                <strong>Okres wynajmu:</strong><br>
                <?=htmlspecialchars($r['start_date'] ?? 'brak')?> → <?=htmlspecialchars($r['end_date'] ?? 'brak')?>
              </p>
            <?php endif; ?>
            <p class="muted" style="font-size:0.85rem">
              Przypisane: <?=htmlspecialchars($r['created_at'])?>
            </p>
            <div style="margin-top:8px">
              <a class="btn btn-sm" href="property_details.php?id=<?=intval($r['property_id'])?>">Szczegóły</a>
              <?php if ($r['owner_id']): ?>
                <a class="btn btn-sm" href="messages.php?property_id=<?=intval($r['property_id'])?>&partner_id=<?=intval($r['owner_id'])?>">Napisz do właściciela</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>