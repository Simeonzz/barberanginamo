<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$new_date = $_POST['new_date'] ?? '';
$new_time = $_POST['new_time'] ?? '';

if (!$booking_id || !$new_date || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

// Check if booking belongs to user
$stmt = $conn->prepare("SELECT staff_id FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$stmt->bind_result($staff_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Check for staff double booking
if ($staff_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND booking_date = ? AND booking_time = ? AND status IN ('pending', 'confirmed', 'approved') AND id != ?");
    $stmt->bind_param('issi', $staff_id, $new_date, $new_time, $booking_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Staff is already booked at this time.']);
        exit;
    }
}

// Update booking
$stmt = $conn->prepare("UPDATE bookings SET booking_date = ?, booking_time = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssii', $new_date, $new_time, $booking_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking.']);
}
$stmt->close();
?>
