<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

// Database connection
$host = "localhost";
$dbname = "barberanginamodb";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed']));
}

$customer_id = $_GET['customer_id'] ?? 0;

if ($customer_id > 0) {
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as service_name 
        FROM bookings b 
        LEFT JOIN services s ON b.service_id = s.id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    $stmt->execute([$customer_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($bookings);
} else {
    echo json_encode([]);
}
?>