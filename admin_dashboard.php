<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barberanginamodb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch statistics
// Total Bookings
$totalBookingsResult = $conn->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $totalBookingsResult ? $totalBookingsResult->fetch_assoc()['total'] : 0;

// Pending Bookings
$pendingBookingsResult = $conn->query("SELECT COUNT(*) as pending FROM bookings WHERE status = 'pending'");
$pendingBookings = $pendingBookingsResult ? $pendingBookingsResult->fetch_assoc()['pending'] : 0;

// Total Revenue (completed bookings only)
$totalRevenueResult = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'completed'");
$totalRevenue = $totalRevenueResult ? $totalRevenueResult->fetch_assoc()['revenue'] : 0;

// Monthly Revenue (current month)
$currentMonth = date('Y-m');
$monthlyRevenueResult = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as monthly FROM bookings WHERE status = 'completed' AND DATE_FORMAT(booking_date, '%Y-%m') = '$currentMonth'");
$monthlyRevenue = $monthlyRevenueResult ? $monthlyRevenueResult->fetch_assoc()['monthly'] : 0;

// Total Customers
$totalCustomersResult = $conn->query("SELECT COUNT(*) as customers FROM users WHERE role = 'customer'");
$totalCustomers = $totalCustomersResult ? $totalCustomersResult->fetch_assoc()['customers'] : 0;

// Available Staff
$availableStaffResult = $conn->query("SELECT COUNT(*) as staff FROM staff WHERE is_available = 1");
$availableStaff = $availableStaffResult ? $availableStaffResult->fetch_assoc()['staff'] : 0;

// Weekly Bookings (last 7 days)
$weeklyBookingsResult = $conn->query("SELECT COUNT(*) as weekly FROM bookings WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weeklyBookings = $weeklyBookingsResult ? $weeklyBookingsResult->fetch_assoc()['weekly'] : 0;

// Recent Bookings
$recentBookings = [];
$recentBookingsResult = $conn->query("
    SELECT b.*, u.fullname as customer_name, s.name as service_name 
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    LEFT JOIN services s ON b.service_id = s.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
if ($recentBookingsResult) {
    $recentBookings = $recentBookingsResult->fetch_all(MYSQLI_ASSOC);
}

// Pending bookings for display
$pendingList = [];
$pendingQuery = $conn->query("
    SELECT b.*, u.fullname, s.name as service 
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    LEFT JOIN services s ON b.service_id = s.id 
    WHERE b.status = 'pending' 
    ORDER BY b.booking_date DESC 
    LIMIT 5
");
if ($pendingQuery) {
    $pendingList = $pendingQuery->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #4361ee;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #3b82f6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-header p {
            color: #94a3b8;
            font-size: 0.85rem;
            margin: 5px 0 0 0;
        }
        
        .menu {
            padding: 15px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left-color: var(--sidebar-active);
        }
        
        .menu-item.active {
            background: rgba(59, 130, 246, 0.1);
            color: white;
            border-left-color: var(--sidebar-active);
            font-weight: 500;
        }
        
        .menu-icon {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .menu-text {
            flex: 1;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .page-title p {
            color: #64748b;
            font-size: 0.875rem;
            margin: 5px 0 0 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Content Area */
        .content-area {
            padding: 25px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #64748b;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Cards */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }
        
        /* Stats Cards */
        .stat-card {
            height: 100%;
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php
    $menuItems = [
        ["title" => "Dashboard", "url" => "admin_dashboard.php", "icon" => "bi-house"],
        ["title" => "Bookings", "url" => "admin_bookings.php", "icon" => "bi-calendar-check"],
        ["title" => "Services", "url" => "admin_services.php", "icon" => "bi-scissors"],
        ["title" => "Products", "url" => "admin_products.php", "icon" => "bi-bag"],
        ["title" => "Staff", "url" => "admin_staff.php", "icon" => "bi-people"],
        ["title" => "Customers", "url" => "admin_customers.php", "icon" => "bi-person-lines-fill"],
        ["title" => "Reports", "url" => "admin_reports.php", "icon" => "bi-bar-chart"],
        ["title" => "Settings", "url" => "admin_settings.php", "icon" => "bi-gear"],
    ];
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Barberang Ina Mo</h2>
            <p>Admin Dashboard</p>
        </div>
        
        <div class="menu">
            <?php foreach ($menuItems as $item): ?>
                <?php $isActive = ($currentPage == $item['url']) ? 'active' : ''; ?>
                <a href="<?= $item['url'] ?>" class="menu-item <?= $isActive ?>">
                    <i class="menu-icon bi <?= $item['icon'] ?>"></i>
                    <span class="menu-text"><?= $item['title'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div style="padding: 20px; margin-top: auto;">
            <a href="logout.php" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Welcome to Barberang Ina Mo Admin Panel</p>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div style="font-weight: 500;">Admin User</div>
                    <small class="text-muted">Administrator</small>
                </div>
            </div>
        </nav>
        
        <!-- Content Area -->
        <div class="content-area">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h2 class="mb-2">Welcome back, Admin!</h2>
                                    <p class="opacity-75">Manage your barbershop efficiently with our admin tools.</p>
                                    <a href="admin_bookings.php" class="btn btn-light">View Bookings</a>
                                </div>
                                <div class="col-md-4 text-end">
                                    <i class="bi bi-bar-chart-fill display-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Quick Stats -->
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Bookings</h6>
                                    <h3 class="fw-bold"><?= $totalBookings ?></h3>
                                    <small class="text-success"><?= $weeklyBookings ?> this week</small>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-calendar text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Revenue</h6>
                                    <h3 class="fw-bold">₱<?= number_format($totalRevenue, 2) ?></h3>
                                    <small class="text-success">₱<?= number_format($monthlyRevenue, 2) ?> this month</small>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-currency-dollar text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Customers</h6>
                                    <h3 class="fw-bold"><?= $totalCustomers ?></h3>
                                    <small class="text-muted">All customers</small>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-people text-info fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Available Staff</h6>
                                    <h3 class="fw-bold"><?= $availableStaff ?></h3>
                                    <small class="text-success">Ready for booking</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-person-badge text-warning fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-4">
                <!-- Pending Bookings -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Bookings (<?= $pendingBookings ?>)</h5>
                            <a href="admin_bookings.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingList)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">No pending bookings</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($pendingList as $row): ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($row['fullname'] ?? 'Unknown') ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($row['service'] ?? 'Unknown Service') ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted"><?= date('M d', strtotime($row['booking_date'] ?? 'now')) ?></small><br>
                                                    <span class="badge bg-warning">Pending</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Recent Bookings</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentBookings)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-check text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">No bookings yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
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
                                            <?php foreach($recentBookings as $booking): 
                                                $statusClass = $booking['status'] == 'completed' ? 'bg-success' : 
                                                            ($booking['status'] == 'confirmed' ? 'bg-primary' : 
                                                            ($booking['status'] == 'pending' ? 'bg-warning' : 'bg-danger'));
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($booking['service_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M d', strtotime($booking['booking_date'] ?? 'now')) ?></td>
                                                <td>₱<?= number_format($booking['total_amount'] ?? 0, 2) ?></td>
                                                <td><span class="badge <?= $statusClass ?>"><?= ucfirst($booking['status'] ?? 'unknown') ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-4">
                <!-- Quick Actions -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="admin_bookings.php?action=add" class="btn btn-outline-primary w-100 text-start">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Booking
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin_services.php" class="btn btn-outline-success w-100 text-start">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Service
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin_products.php" class="btn btn-outline-info w-100 text-start">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Product
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin_staff.php" class="btn btn-outline-warning w-100 text-start">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Staff
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>