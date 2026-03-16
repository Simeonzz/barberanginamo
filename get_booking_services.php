<?php
// get_booking_services.php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id']);
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Get all services with prices
$services = [];
$res = $conn->query("SELECT id, name, price FROM services WHERE is_active = 1 ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $services[] = [
        'id' => (int)$row['id'], 
        'name' => $row['name'],
        'price' => (float)$row['price']
    ];
}

// Get all staff
$staff = [];
$res = $conn->query("SELECT id, pseudonym FROM staff WHERE is_available = 1 ORDER BY pseudonym");
while ($row = $res->fetch_assoc()) {
    $staff[] = ['id' => (int)$row['id'], 'pseudonym' => $row['pseudonym']];
}

// Get booking items (multiple services)
$booking_services = [];
$booking_staff = null;

// Check if booking_items table exists and has data
$tableCheck = $conn->query("SHOW TABLES LIKE 'booking_items'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    // Get services from booking_items
    $query = "SELECT service_id FROM booking_items WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $booking_services[] = (int)$row['service_id'];
    }
}

// If no items found, try the old way (single service)
if (empty($booking_services)) {
    $query = "SELECT service_id, staff_id FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['service_id']) {
            $booking_services[] = (int)$row['service_id'];
        }
        $booking_staff = $row['staff_id'] ? (int)$row['staff_id'] : null;
    }
} else {
    // Get staff from bookings table
    $query = "SELECT staff_id FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $booking_staff = $row['staff_id'] ? (int)$row['staff_id'] : null;
    }
}

// Create a price map for JavaScript
$priceMap = [];
foreach ($services as $s) {
    $priceMap[$s['id']] = $s['price'];
}

// Respond
echo json_encode([
    'success' => true,
    'all_services' => $services,
    'all_staff' => $staff,
    'booking_services' => $booking_services,
    'booking_staff' => $booking_staff,
    'price_map' => $priceMap
]);
?>