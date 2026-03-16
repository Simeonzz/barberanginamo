<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$services = isset($_POST['services']) ? $_POST['services'] : [];
$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit();
}

if (empty($services)) {
    echo json_encode(['success' => false, 'message' => 'At least one service must be selected']);
    exit();
}

// Verify booking belongs to user
$check = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// Calculate total amount from all selected services
$total_amount = 0;
$service_ids = [];
$service_prices = [];

foreach ($services as $service_id) {
    $service_id = intval($service_id);
    $service_ids[] = $service_id;
    
    $serviceQuery = $conn->prepare("SELECT price FROM services WHERE id = ?");
    $serviceQuery->bind_param("i", $service_id);
    $serviceQuery->execute();
    $serviceResult = $serviceQuery->get_result();
    $serviceData = $serviceResult->fetch_assoc();
    
    if ($serviceData) {
        $price = $serviceData['price'];
        $total_amount += $price;
        $service_prices[$service_id] = $price;
    }
}

$down_payment = $total_amount * 0.5;

// Start transaction
$conn->begin_transaction();

try {
    // Update booking with first service (for backward compatibility) and new total
    $first_service = $service_ids[0];
    $update = $conn->prepare("UPDATE bookings SET service_id = ?, staff_id = ?, total_amount = ?, down_payment = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("iididi", $first_service, $staff_id, $total_amount, $down_payment, $booking_id, $user_id);
    $update->execute();
    
    // Check if booking_items table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'booking_items'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Delete existing booking items
        $delete = $conn->prepare("DELETE FROM booking_items WHERE booking_id = ?");
        $delete->bind_param("i", $booking_id);
        $delete->execute();
        
        // Insert new booking items
        $insert = $conn->prepare("INSERT INTO booking_items (booking_id, service_id, price) VALUES (?, ?, ?)");
        
        foreach ($service_ids as $sid) {
            $price = $service_prices[$sid];
            $insert->bind_param("iid", $booking_id, $sid, $price);
            $insert->execute();
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>