<?php
// includes/require_admin.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized. Please log in.');
}

// quick session-based check
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    return;
}

// fallback: verify from DB
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!isset($row['role']) || $row['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden. Admins only.');
}
