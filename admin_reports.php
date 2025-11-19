<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$report = ['summary'=>['total_assignments'=>0,'active_rentals'=>0,'completed_rentals'=>0,'pending_payments'=>0],'by_property'=>[]];

if ($from || $to) {
    $from_date = $from ? $from . ' 00:00:00' : '2020-01-01 00:00:00';
    $to_date = $to ? $to . ' 23:59:59' : date('Y-m-d 23:59:59');
    
    try {
        // Główny raport
        $sql = "SELECT 
                    a.id,
                    a.property_id,
                    p.title,
                    p.city,
                    p.price,
                    a.status,
                    a.start_date,
                    a.end_date,
                    a.created_at,
                    COUNT(a.id) as total_assignments,
                    SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_count
                FROM assignments a
                JOIN properties p ON a.property_id = p.id
                WHERE a.created_at >= :from_date
                AND a.created_at <= :to_date
                GROUP BY a.property_id
                ORDER BY total_assignments DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':from_date' => $from_date,
            ':to_date' => $to_date
        ]);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Podsumowanie całości
        $summary_sql = "SELECT 
                            COUNT(*) as total_assignments,
                            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as active_rentals,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_rentals,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments
                        FROM assignments
                        WHERE created_at >= :from_date
                        AND created_at <= :to_date";
        
        $stmt_summary = $pdo->prepare($summary_sql);
        $stmt_summary->execute([
            ':from_date' => $from_date,
            ':to_date' => $to_date
        ]);
        $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);
        
        // Popularność miast
        $cities_sql = "SELECT 
                            p.city,
                            COUNT(a.id) as count
                        FROM assignments a
                        JOIN properties p ON a.property_id = p.id
                        WHERE a.created_at >= :from_date
                        AND a.created_at <= :to_date
                        GROUP BY p.city
                        ORDER BY count DESC
                        LIMIT 5";
        
        $stmt_cities = $pdo->prepare($cities_sql);
        $stmt_cities->execute([
            ':from_date' => $from_date,
            ':to_date' => $to_date
        ]);
        $top_cities = $stmt_cities->fetchAll(PDO::FETCH_ASSOC);
        
        $report = [
            'summary' => [
                'total_assignments' => (int)($summary['total_assignments'] ?? 0),
                'active_rentals' => (int)($summary['active_rentals'] ?? 0),
                'completed_rentals' => (int)($summary['completed_rentals'] ?? 0),
                'pending_payments' => (int)($summary['pending_payments'] ?? 0)
            ],
            'by_property' => $properties,
            'top_cities' => $top_cities
        ];
        
        // Eksport CSV
        if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
            admin_export_report_csv_assignments($report['by_property'], $report['summary'], 'raport_przypisania_'.$from.'_'.$to.'.csv');
        }
    } catch (Exception $e) {
        $error = "Błąd przy generowaniu raportu: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Raporty — Panel admina</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Raporty przypisań</h2>

  <form method="get" class="panel" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <label style="margin:0">Od <input type="date" name="from" value="<?=htmlspecialchars($from ?? '')?>"></label>
    <label style="margin:0">Do <input type="date" name="to" value="<?=htmlspecialchars($to ?? '')?>"></label>
    <button class="btn btn-primary" type="submit">Generuj</button>
    <?php if ($from || $to): ?>
      <a class="btn" href="?from=<?=urlencode($from ?? '')?>&to=<?=urlencode($to ?? '')?>&export=csv">Eksportuj CSV</a>
    <?php endif; ?>
  </form>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <?php if ($report['summary']['total_assignments'] > 0): ?>
    <!-- Metryki główne -->
    <div class="panel" style="margin-top:12px">
      <h3>Podsumowanie okresu</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        <div style="padding:12px;background:rgba(110,231,183,0.1);border-radius:8px;border-left:3px solid var(--accent)">
          <p style="color:var(--muted);margin:0 0 4px 0;font-size:0.9rem">Łącznie przypisań</p>
          <p style="font-size:1.8rem;font-weight:700;margin:0"><?=intval($report['summary']['total_assignments'])?></p>
        </div>
        <div style="padding:12px;background:rgba(96,165,250,0.1);border-radius:8px;border-left:3px solid var(--accent-2)">
          <p style="color:var(--muted);margin:0 0 4px 0;font-size:0.9rem">Aktywne wynajmy</p>
          <p style="font-size:1.8rem;font-weight:700;margin:0;color:var(--accent-2)"><?=intval($report['summary']['active_rentals'])?></p>
        </div>
        <div style="padding:12px;background:rgba(34,197,94,0.1);border-radius:8px;border-left:3px solid #22c55e">
          <p style="color:var(--muted);margin:0 0 4px 0;font-size:0.9rem">Zakończone wynajmy</p>
          <p style="font-size:1.8rem;font-weight:700;margin:0;color:#22c55e"><?=intval($report['summary']['completed_rentals'])?></p>
        </div>
        <div style="padding:12px;background:rgba(255,193,7,0.1);border-radius:8px;border-left:3px solid #ffc107">
          <p style="color:var(--muted);margin:0 0 4px 0;font-size:0.9rem">Oczekujące na płatność</p>
          <p style="font-size:1.8rem;font-weight:700;margin:0;color:#ffc107"><?=intval($report['summary']['pending_payments'])?></p>
        </div>
      </div>
    </div>

    <!-- Top miasta -->
    <?php if (!empty($report['top_cities'])): ?>
    <div class="panel" style="margin-top:12px">
      <h3>Top 5 miast</h3>
      <table class="table">
        <thead><tr><th>Miasto</th><th>Liczba przypisań</th></tr></thead>
        <tbody>
        <?php foreach ($report['top_cities'] as $city): ?>
          <tr>
            <td><?=htmlspecialchars($city['city'])?></td>
            <td style="font-weight:600"><?=intval($city['count'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Top mieszkania -->
    <div class="panel" style="margin-top:12px">
      <h3>Top mieszkania</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Tytuł</th><th>Miasto</th><th>Cena/m-c</th><th>Przypisań</th><th>Aktywne</th><th>Zakończone</th></tr></thead>
        <tbody>
        <?php foreach ($report['by_property'] as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['id'])?></td>
            <td><?=htmlspecialchars($p['title'])?></td>
            <td><?=htmlspecialchars($p['city'])?></td>
            <td><?=number_format((float)$p['price'],2,',',' ')?> zł</td>
            <td style="font-weight:600"><?=htmlspecialchars($p['total_assignments'])?></td>
            <td style="color:var(--accent-2)"><?=htmlspecialchars($p['active_count'])?></td>
            <td style="color:#22c55e"><?=htmlspecialchars($p['completed_count'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif ($from || $to): ?>
    <div class="panel alert alert-info" style="margin-top:12px">
      <p>Brak danych przypisań dla wybranego okresu.</p>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>