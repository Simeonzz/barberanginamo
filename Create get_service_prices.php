<?php
// get_service_prices.php
include 'config.php';

header('Content-Type: application/json');

$prices = [];
$result = $conn->query("SELECT id, price FROM services WHERE is_active = 1");

while ($row = $result->fetch_assoc()) {
    $prices[$row['id']] = (float)$row['price'];
}

echo json_encode(['prices' => $prices]);
?>