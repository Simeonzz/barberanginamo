<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit();
}

// Get booking details
$query = "SELECT b.*, s.id as service_id 
          FROM bookings b 
          LEFT JOIN services s ON b.service_id = s.id 
          WHERE b.id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Return service_id as an array for compatibility with checkbox selection
    $service_ids = [];
    if ($row['service_id']) {
        $service_ids[] = intval($row['service_id']);
    }
    
    echo json_encode([
        'success' => true,
        'services' => $service_ids,
        'staff_id' => $row['staff_id'] ? intval($row['staff_id']) : null
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>s