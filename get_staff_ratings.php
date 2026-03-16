<?php
include 'config.php';

header('Content-Type: application/json');

$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

if (!$staff_id) {
    echo json_encode(['success' => false, 'message' => 'Staff ID required']);
    exit();
}

// Get staff rating stats
$stats = $conn->prepare("SELECT average_rating, total_ratings FROM staff_rating_stats WHERE staff_id = ?");
$stats->bind_param("i", $staff_id);
$stats->execute();
$stats_result = $stats->get_result();
$stats_data = $stats_result->fetch_assoc();

// Get recent ratings with comments
$ratings = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.fullname as user_name
    FROM staff_ratings r
    JOIN users u ON r.user_id = u.id
    WHERE r.staff_id = ? AND r.comment IS NOT NULL AND r.comment != ''
    ORDER BY r.created_at DESC
    LIMIT 10
");
$ratings->bind_param("i", $staff_id);
$ratings->execute();
$ratings_result = $ratings->get_result();

$comments = [];
while ($row = $ratings_result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode([
    'success' => true,
    'average' => $stats_data ? floatval($stats_data['average_rating']) : 0,
    'total' => $stats_data ? intval($stats_data['total_ratings']) : 0,
    'comments' => $comments
]);
?>