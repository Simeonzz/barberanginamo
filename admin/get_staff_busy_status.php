<?php
// Returns staff busy status for today (or a given date)
include '../config.php';
header('Content-Type: application/json');
$date = $_GET['date'] ?? date('Y-m-d');


// Optionally, you can pass ?date=YYYY-MM-DD&time=HH:MM to check for a specific slot
$time = $_GET['time'] ?? null;
if ($time) {
    $sql = "SELECT staff_id FROM bookings WHERE booking_date = ? AND booking_time = ? AND status IN ('pending','confirmed','approved')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $date, $time);
    $stmt->execute();
    $res = $stmt->get_result();
    $busy = [];
    while ($row = $res->fetch_assoc()) {
        $busy[$row['staff_id']] = 1;
    }
    echo json_encode($busy);
} else {
    $sql = "SELECT staff_id FROM bookings WHERE booking_date = ? AND status IN ('pending','confirmed','approved')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $res = $stmt->get_result();
    $busy = [];
    while ($row = $res->fetch_assoc()) {
        $busy[$row['staff_id']] = 1;
    }
    echo json_encode($busy);
}
