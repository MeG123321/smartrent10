<?php
session_start();

$host = 'localhost';
$db = 'smartrent';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT a.*, p.title, p.city, p.price, u.name as owner_name 
        FROM assignments a
        JOIN properties p ON a.property_id = p.id
        JOIN users u ON p.owner_id = u.id
        WHERE a.tenant_id = ?
        AND a.status = 'confirmed'
        ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="moje_wynajecia_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, array(
    'ID Przypisania',
    'Mieszkanie',
    'Miasto',
    'Właściciel',
    'Cena za miesiąc',
    'Data rozpoczęcia',
    'Data zakończenia',
    'Status',
    'Data przypisania'
), ';');

foreach ($rentals as $rental) {
    $start_date = $rental['start_date'] ? date('Y-m-d', strtotime($rental['start_date'])) : 'brak';
    $end_date = $rental['end_date'] ? date('Y-m-d', strtotime($rental['end_date'])) : 'brak';
    
    $days = '-';
    if ($rental['start_date'] && $rental['end_date']) {
        $start = new DateTime($rental['start_date']);
        $end = new DateTime($rental['end_date']);
        $days = $end->diff($start)->days + 1;
    }
    
    $status_text = $rental['status'] == 'confirmed' ? 'Wynajęte' : ucfirst($rental['status']);
    
    fputcsv($output, array(
        $rental['id'],
        $rental['title'],
        $rental['city'],
        $rental['owner_name'],
        $rental['price'] . ' PLN',
        $start_date,
        $end_date,
        $days,
        $status_text,
        date('Y-m-d H:i', strtotime($rental['created_at']))
    ), ';');
}

fclose($output);
exit;
?>