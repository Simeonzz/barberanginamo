<?php
// get_unavailable_staff.php
include 'config.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';
if (!$date || !$time) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT staff_id FROM bookings WHERE booking_date = ? AND booking_time = ? AND status IN ('pending', 'confirmed', 'approved')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $date, $time);
$stmt->execute();
$res = $stmt->get_result();
$unavailable = [];
while ($row = $res->fetch_assoc()) {
    $unavailable[] = (int)$row['staff_id'];
}
echo json_encode($unavailable);
