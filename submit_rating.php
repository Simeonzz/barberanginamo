<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];
$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : null;

// If booking_id is 0, set it to NULL
if ($booking_id === 0) {
    $booking_id = null;
}

// Validate input
if (!$staff_id) {
    echo json_encode(['success' => false, 'message' => 'Please select a staff member']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid rating (1-5 stars)']);
    exit();
}

// Check if staff exists
$staff_check = $conn->prepare("SELECT id FROM staff WHERE id = ?");
$staff_check->bind_param("i", $staff_id);
$staff_check->execute();
$staff_result = $staff_check->get_result();

if ($staff_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Staff member not found']);
    exit();
}

// Check if user has already rated this staff recently (optional - prevent spam)
$check_existing = $conn->prepare("SELECT id FROM staff_ratings WHERE staff_id = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
$check_existing->bind_param("ii", $staff_id, $user_id);
$check_existing->execute();
$existing_result = $check_existing->get_result();

if ($existing_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already rated this staff member recently']);
    exit();
}

// Insert rating into database
$conn->begin_transaction();

try {
    // Insert the rating - booking_id can be NULL
    $insert = $conn->prepare("INSERT INTO staff_ratings (booking_id, staff_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("iiiis", $booking_id, $staff_id, $user_id, $rating, $comment);
    $insert->execute();
    
    // Update or insert staff rating stats
    $check_stats = $conn->prepare("SELECT * FROM staff_rating_stats WHERE staff_id = ?");
    $check_stats->bind_param("i", $staff_id);
    $check_stats->execute();
    $stats_result = $check_stats->get_result();
    
    if ($stats_result->num_rows > 0) {
        // Update existing stats
        $stats = $stats_result->fetch_assoc();
        $new_total = $stats['total_ratings'] + 1;
        $new_average = (($stats['average_rating'] * $stats['total_ratings']) + $rating) / $new_total;
        
        $update = $conn->prepare("UPDATE staff_rating_stats SET average_rating = ?, total_ratings = ? WHERE staff_id = ?");
        $update->bind_param("dii", $new_average, $new_total, $staff_id);
        $update->execute();
    } else {
        // Insert new stats
        $insert_stats = $conn->prepare("INSERT INTO staff_rating_stats (staff_id, average_rating, total_ratings) VALUES (?, ?, 1)");
        $insert_stats->bind_param("id", $staff_id, $rating);
        $insert_stats->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Thank you for your rating!']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>