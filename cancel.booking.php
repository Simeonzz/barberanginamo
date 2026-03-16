<?php
// cancel_booking.php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

// Check if booking belongs to user
$check_stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $booking_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied']);
    exit();
}

// Update booking status to cancelled
$update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
}

$update_stmt->close();
$conn->close();
?>