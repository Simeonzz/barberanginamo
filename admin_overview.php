<?php
// admin_overview.php
session_start();
// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}
ob_start();


// --- Database Connection ---
$host = "localhost";
$dbname = "barberanginamodb";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database Connection Failed: " . $e->getMessage());
}

// --- Fetch Stats ---
try {
    // Total Bookings
    $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

    // Pending Bookings
    $pendingBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

    // Total Revenue
    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings")->fetchColumn();

    // Total Customers
    $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

    // Completed Bookings (with JOIN)
    $stmt = $pdo->query("
        SELECT b.id, b.booking_date, b.total_amount, b.status,
               s.name AS service_name,
               u.fullname AS customer_name
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.status = 'completed'
        ORDER BY b.booking_date DESC
    ");
    $completedBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . htmlspecialchars($e->getMessage()));
}
$content = ob_get_clean();
include 'admin_layout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Overview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .card { border: 1px solid rgba(0,0,0,0.1); border-radius: 12px; }
        .stat-number { font-size: 1.8rem; font-weight: bold; }
        .muted { color: #6c757d; font-size: 0.85rem; }
        .completed-box { border: 1px solid #dee2e6; border-radius: 10px; padding: 12px; }
        .badge-completed { background-color: #0d6efd; }
    </style>
</head>
<body>
<div class="container py-5">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="fw-bold mb-1">Dashboard Overview</h1>
        <p class="text-muted">Welcome to Barberang Ina Mo Admin Panel</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6 class="text-muted mb-2">Total Bookings</h6>
                <div class="stat-number mt-2"><?= $totalBookings ?></div>
                <div class="muted"><?= $pendingBookings ?> pending approval</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6 class="text-muted mb-2">Total Revenue</h6>
                <div class="stat-number mt-2">₱<?= number_format($totalRevenue, 2) ?></div>
                <div class="muted">All time earnings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6 class="text-muted mb-2">Total Customers</h6>
                <div class="stat-number mt-2"><?= $totalCustomers ?></div>
                <div class="muted">Registered users</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6 class="text-muted mb-2">Growth</h6>
                <div class="stat-number mt-2">+12%</div>
                <div class="muted">vs last month</div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Recent Bookings</h5>
        </div>
        <div class="card-body">
            <?php if (empty($completedBookings)): ?>
                <p class="text-center text-muted py-4">No completed bookings yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedBookings as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['customer_name']) ?></td>
                                <td><?= htmlspecialchars($b['service_name']) ?></td>
                                <td><?= date("M d, Y", strtotime($b['booking_date'])) ?></td>
                                <td>₱<?= number_format($b['total_amount'], 2) ?></td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>