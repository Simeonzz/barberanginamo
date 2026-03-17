<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// Database Connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "barberanginamodb"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Setup necessary tables
function setupTables($conn) {
    // Check and create users table if needed
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample users
        $sampleUsers = [
            ['fullname' => 'John Doe', 'email' => 'john@example.com', 'phone' => '09171234567', 'role' => 'customer'],
            ['fullname' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '09172345678', 'role' => 'customer'],
            ['fullname' => 'Admin User', 'email' => 'admin@example.com', 'phone' => '09170000000', 'role' => 'admin']
        ];
        
        foreach ($sampleUsers as $user) {
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user['fullname'], $user['email'], $user['phone'], $user['role']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Check and create bookings table if needed
    $result = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            service_id INT,
            staff_id INT,
            booking_date DATE,
            booking_time TIME,
            total_amount DECIMAL(10,2),
            status VARCHAR(20) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample bookings
        $sampleBookings = [
            ['user_id' => 1, 'service_id' => 1, 'staff_id' => 1, 'booking_date' => date('Y-m-d', strtotime('-7 days')), 'booking_time' => '14:00:00', 'total_amount' => 350.00, 'status' => 'completed', 'payment_status' => 'verified'],
            ['user_id' => 2, 'service_id' => 2, 'staff_id' => 2, 'booking_date' => date('Y-m-d', strtotime('-3 days')), 'booking_time' => '10:00:00', 'total_amount' => 200.00, 'status' => 'confirmed', 'payment_status' => 'verified'],
            ['user_id' => 1, 'service_id' => 3, 'staff_id' => 3, 'booking_date' => date('Y-m-d'), 'booking_time' => '15:30:00', 'total_amount' => 450.00, 'status' => 'pending', 'payment_status' => 'pending']
        ];
        
        foreach ($sampleBookings as $booking) {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, staff_id, booking_date, booking_time, total_amount, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissdss", $booking['user_id'], $booking['service_id'], $booking['staff_id'], $booking['booking_date'], $booking['booking_time'], $booking['total_amount'], $booking['status'], $booking['payment_status']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Check and create services table if needed
    $result = $conn->query("SHOW TABLES LIKE 'services'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Check and create staff table if needed
    $result = $conn->query("SHOW TABLES LIKE 'staff'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pseudonym VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL,
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
}

setupTables($conn);

// Fetch All Bookings with Join
$sql = "
    SELECT 
        b.id,
        b.total_amount,
        b.booking_date,
        b.status,
        b.payment_status,
        s.name AS service_name,
        st.pseudonym AS stylist_name,
        u.fullname AS customer_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN staff st ON b.staff_id = st.id
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.booking_date DESC
";

$result = $conn->query($sql);

// Initialize variables
$totalRevenue = 0;
$monthlyRevenue = 0;
$weeklyRevenue = 0;
$totalBookings = 0;
$pendingBookings = 0;
$completedBookings = 0;
$confirmedBookings = 0;
$cancelledBookings = 0;

$serviceCounts = [];
$stylistCounts = [];
$customerCounts = [];

$currentMonth = date('Y-m');
$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$currentWeekEnd = date('Y-m-d', strtotime('sunday this week'));

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalBookings++;
        $amount = (float)$row['total_amount'];
        
        // Total revenue
        $totalRevenue += $amount;
        
        // Monthly revenue
        $bookingMonth = date('Y-m', strtotime($row['booking_date']));
        if ($bookingMonth === $currentMonth) {
            $monthlyRevenue += $amount;
        }
        
        // Weekly revenue
        $bookingDate = $row['booking_date'];
        if ($bookingDate >= $currentWeekStart && $bookingDate <= $currentWeekEnd) {
            $weeklyRevenue += $amount;
        }
        
        // Count by status
        switch ($row['status']) {
            case 'completed':
                $completedBookings++;
                break;
            case 'confirmed':
                $confirmedBookings++;
                break;
            case 'pending':
                $pendingBookings++;
                break;
            case 'cancelled':
                $cancelledBookings++;
                break;
        }
        
        // Count top service
        $serviceName = $row['service_name'] ?: 'Unknown';
        $serviceCounts[$serviceName] = ($serviceCounts[$serviceName] ?? 0) + 1;
        
        // Count top stylist
        $stylistName = $row['stylist_name'] ?: 'Unknown';
        $stylistCounts[$stylistName] = ($stylistCounts[$stylistName] ?? 0) + 1;
        
        // Count top customer
        $customerName = $row['customer_name'] ?: 'Unknown';
        $customerCounts[$customerName] = ($customerCounts[$customerName] ?? 0) + 1;
    }
}

// Identify top performers
arsort($serviceCounts);
arsort($stylistCounts);
arsort($customerCounts);

$topServiceName = key($serviceCounts) ?: 'N/A';
$topServiceCount = reset($serviceCounts) ?: 0;

$topStylistName = key($stylistCounts) ?: 'N/A';
$topStylistCount = reset($stylistCounts) ?: 0;

$topCustomerName = key($customerCounts) ?: 'N/A';
$topCustomerCount = reset($customerCounts) ?: 0;

// Get customer count
$customerResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$totalCustomers = $customerResult ? $customerResult->fetch_assoc()['count'] : 0;

// Get staff count
$staffResult = $conn->query("SELECT COUNT(*) as count FROM staff");
$totalStaff = $staffResult ? $staffResult->fetch_assoc()['count'] : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Reports & Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-theme.css">
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
            border: none;
        }
        
        .bg-revenue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .bg-bookings {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .bg-customers {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .bg-completion {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
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
                <h1>Reports & Analytics</h1>
                <p>Detailed insights and performance metrics</p>
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
            <h1 class="mb-3 fw-bold">Reports & Analytics</h1>
            <p class="text-muted mb-4">Detailed insights and performance metrics</p>

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-revenue">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white-50">Total Revenue</h6>
                                    <h3 class="fw-bold">₱<?= number_format($totalRevenue, 2) ?></h3>
                                    <p class="mb-0">All-time earnings</p>
                                </div>
                                <i class="bi bi-currency-dollar display-5 opacity-50"></i>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <i class="bi bi-calendar-week me-1"></i>
                                    This week: ₱<?= number_format($weeklyRevenue, 2) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-bookings">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white-50">Total Bookings</h6>
                                    <h3 class="fw-bold"><?= $totalBookings ?></h3>
                                    <p class="mb-0">All bookings</p>
                                </div>
                                <i class="bi bi-calendar-check display-5 opacity-50"></i>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <span class="badge bg-success"><?= $completedBookings ?> completed</span>
                                    <span class="badge bg-warning ms-1"><?= $pendingBookings ?> pending</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-customers">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white-50">Total Customers</h6>
                                    <h3 class="fw-bold"><?= $totalCustomers ?></h3>
                                    <p class="mb-0">Registered customers</p>
                                </div>
                                <i class="bi bi-people display-5 opacity-50"></i>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <i class="bi bi-star-fill me-1"></i>
                                    Top: <?= htmlspecialchars($topCustomerName) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-completion">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-white-50">Completion Rate</h6>
                                    <h3 class="fw-bold">
                                        <?= $totalBookings > 0 ? number_format(($completedBookings / $totalBookings) * 100, 1) : 0 ?>%
                                    </h3>
                                    <p class="mb-0">Successful bookings</p>
                                </div>
                                <i class="bi bi-graph-up display-5 opacity-50"></i>
                            </div>
                            <div class="mt-3">
                                <small class="opacity-75">
                                    <?= $completedBookings ?> of <?= $totalBookings ?> bookings
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Top Performing Service</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="bi bi-scissors display-1 text-primary mb-3"></i>
                                <h3><?= htmlspecialchars($topServiceName) ?></h3>
                                <p class="text-muted"><?= $topServiceCount ?> bookings</p>
                            </div>
                            <?php if (!empty($serviceCounts)): ?>
                            <div class="mt-3">
                                <h6>Top Services:</h6>
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    $counter = 0;
                                    foreach ($serviceCounts as $service => $count): 
                                        if ($counter++ >= 5) break;
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($service) ?>
                                            <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Top Performing Stylist</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="bi bi-person-badge display-1 text-success mb-3"></i>
                                <h3><?= htmlspecialchars($topStylistName) ?></h3>
                                <p class="text-muted"><?= $topStylistCount ?> bookings served</p>
                            </div>
                            <?php if (!empty($stylistCounts)): ?>
                            <div class="mt-3">
                                <h6>Top Stylists:</h6>
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    $counter = 0;
                                    foreach ($stylistCounts as $stylist => $count): 
                                        if ($counter++ >= 5) break;
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($stylist) ?>
                                            <span class="badge bg-success rounded-pill"><?= $count ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Booking Status Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                <div style="width: 200px; height: 200px; position: relative;">
                                    <!-- Simple status visualization -->
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-success me-2" style="width: 20px; height: 20px;"></div>
                                                <span>Completed: <?= $completedBookings ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary me-2" style="width: 20px; height: 20px;"></div>
                                                <span>Confirmed: <?= $confirmedBookings ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-warning me-2" style="width: 20px; height: 20px;"></div>
                                                <span>Pending: <?= $pendingBookings ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-danger me-2" style="width: 20px; height: 20px;"></div>
                                                <span>Cancelled: <?= $cancelledBookings ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Completed</span>
                                    <span class="fw-bold"><?= $completedBookings ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Confirmed</span>
                                    <span class="fw-bold"><?= $confirmedBookings ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Pending</span>
                                    <span class="fw-bold"><?= $pendingBookings ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Cancelled</span>
                                    <span class="fw-bold"><?= $cancelledBookings ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detailed Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Monthly Revenue</h6>
                                <h4 class="fw-bold text-primary">₱<?= number_format($monthlyRevenue, 2) ?></h4>
                                <small class="text-muted">Current month</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Avg Booking Value</h6>
                                <h4 class="fw-bold text-success">
                                    ₱<?= $totalBookings > 0 ? number_format($totalRevenue / $totalBookings, 2) : "0.00" ?>
                                </h4>
                                <small class="text-muted">Per booking</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Total Staff</h6>
                                <h4 class="fw-bold text-info"><?= $totalStaff ?></h4>
                                <small class="text-muted">Barbers & stylists</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Cancellation Rate</h6>
                                <h4 class="fw-bold text-danger">
                                    <?= $totalBookings > 0 ? number_format(($cancelledBookings / $totalBookings) * 100, 1) : 0 ?>%
                                </h4>
                                <small class="text-muted">Of total bookings</small>
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
