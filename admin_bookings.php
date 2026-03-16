<?php
// admin_bookings.php - Fixed version with preserved filters
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// ---------- CONFIG ----------
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'barberanginamodb';

// ---------- CONNECT ----------
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Check if tables exist, if not create them
function setupDatabase($conn) {
    // Check if bookings table exists
    $result = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($result->num_rows == 0) {
        // Create bookings table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            service_id INT,
            staff_id INT,
            booking_date DATE,
            booking_time TIME,
            total_amount DECIMAL(10,2),
            down_payment DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'pending',
            payment_proof_url VARCHAR(500),
            customer_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add some sample data for testing
        $conn->query("INSERT INTO bookings (user_id, service_id, staff_id, booking_date, booking_time, total_amount, status, customer_notes) VALUES 
            (1, 1, 1, CURDATE(), '14:00:00', 350.00, 'pending', 'First time customer'),
            (2, 2, 2, CURDATE(), '15:30:00', 200.00, 'confirmed', 'Regular customer'),
            (3, 3, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 450.00, 'completed', 'Premium service requested')
        ");
    }
}

setupDatabase($conn);

// Get current filter and search parameters
$current_filter = $_GET['filter'] ?? 'pending'; // Default to pending
$current_search = $_GET['search'] ?? '';
$allowedFilters = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($current_filter, $allowedFilters)) {
    $current_filter = 'pending';
}

// ---------- Handle POST actions ----------
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $bookingId = intval($_POST['booking_id'] ?? 0);

    if ($bookingId <= 0) {
        $flash = ['type' => 'danger', 'msg' => 'Invalid booking ID.'];
    } else {
        if ($action === 'update_status' && isset($_POST['status'])) {
            $status = htmlspecialchars($_POST['status']);
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $bookingId);
            if ($stmt->execute()) {
                $flash = ['type' => 'success', 'msg' => "Booking status updated to '{$status}'."];
            } else {
                $flash = ['type' => 'danger', 'msg' => "Failed updating booking status."];
            }
            $stmt->close();
        } elseif ($action === 'update_payment' && isset($_POST['payment_status'])) {
            $payment_status = htmlspecialchars($_POST['payment_status']);
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $payment_status, $bookingId);
            if ($stmt->execute()) {
                $flash = ['type' => 'success', 'msg' => "Payment status updated to '{$payment_status}'."];
            } else {
                $flash = ['type' => 'danger', 'msg' => "Failed updating payment status."];
            }
            $stmt->close();
        } else {
            $flash = ['type' => 'danger', 'msg' => 'Unknown action.'];
        }
    }

    if ($flash) {
        // Preserve filter and search parameters after redirect
        $redirect_url = 'admin_bookings.php?filter=' . urlencode($current_filter) . '&search=' . urlencode($current_search) . '&flash=' . urlencode(json_encode($flash));
        header('Location: ' . $redirect_url);
        exit();
    }
}

// If there is flash in GET, show it
if (!empty($_GET['flash'])) {
    $flash = json_decode(urldecode($_GET['flash']), true);
}

// ---------- Handle search and filters ----------
$filter = $current_filter;
$search = $current_search;

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = "";

if ($filter !== 'all') {
    $whereConditions[] = "b.status = ?";
    $params[] = $filter;
    $types .= "s";
}

if (!empty($search)) {
    $whereConditions[] = "(u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR s.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

$where = "";
if (!empty($whereConditions)) {
    $where = "WHERE " . implode(" AND ", $whereConditions);
}

// ---------- Fetch bookings ----------
$sql = "
    SELECT 
        b.id,
        b.total_amount,
        b.down_payment,
        b.booking_date,
        b.booking_time,
        b.status,
        b.payment_status,
        b.payment_proof_url,
        b.customer_notes,
        b.created_at,
        GROUP_CONCAT(s.name SEPARATOR ', ') AS service_name,
        st.pseudonym AS stylist_name,
        u.fullname AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    LEFT JOIN services s ON bi.service_id = s.id
    LEFT JOIN staff st ON b.staff_id = st.id
    LEFT JOIN users u ON b.user_id = u.id
    $where
    GROUP BY b.id
    ORDER BY b.created_at DESC, b.booking_date DESC, b.booking_time DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Convert time to 12-hour format
        if (!empty($row['booking_time'])) {
            $time = DateTime::createFromFormat('H:i:s', $row['booking_time']);
            if ($time) {
                $row['booking_time_formatted'] = $time->format('h:i A');
            } else {
                $row['booking_time_formatted'] = $row['booking_time'];
            }
        } else {
            $row['booking_time_formatted'] = 'N/A';
        }
        $bookings[] = $row;
    }
}

// Function to convert time to 12-hour format for display
function formatTimeTo12Hour($time) {
    if (empty($time)) return 'N/A';
    $timeObj = DateTime::createFromFormat('H:i:s', $time);
    return $timeObj ? $timeObj->format('h:i A') : $time;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Bookings</title>
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
        
        /* Booking Card */
        .booking-card {
            border-left: 4px solid #4361ee;
        }
        
        .booking-card.pending {
            border-left-color: #f59e0b;
        }
        
        .booking-card.confirmed {
            border-left-color: #10b981;
        }
        
        .booking-card.completed {
            border-left-color: #3b82f6;
        }
        
        .booking-card.cancelled {
            border-left-color: #ef4444;
        }
        
        .time-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        /* Search Bar */
        .search-bar {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Active filter highlight */
        .filter-active {
            font-weight: 600;
            color: var(--primary-color);
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
                <h1>Manage Bookings</h1>
                <p>Approve, reschedule, or cancel customer bookings</p>
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
            <!-- Quick Stats -->
            <div class="row g-3 mb-4">
                <?php
                // Calculate stats
                $totalBookings = 0;
                $pendingCount = 0;
                $confirmedCount = 0;
                $completedCount = 0;
                $totalRevenue = 0;
                
                // Get counts from database for stats (without filters)
                $statsQuery = "SELECT status, COUNT(*) as count, SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as revenue FROM bookings GROUP BY status";
                $statsResult = $conn->query($statsQuery);
                while ($stat = $statsResult->fetch_assoc()) {
                    $totalBookings += $stat['count'];
                    if ($stat['status'] == 'pending') $pendingCount = $stat['count'];
                    if ($stat['status'] == 'confirmed') $confirmedCount = $stat['count'];
                    if ($stat['status'] == 'completed') {
                        $completedCount = $stat['count'];
                        $totalRevenue = $stat['revenue'];
                    }
                }
                ?>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $totalBookings ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card <?= $filter == 'pending' ? 'border border-warning' : '' ?>">
                        <div class="stat-number"><?= $pendingCount ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card <?= $filter == 'confirmed' ? 'border border-success' : '' ?>">
                        <div class="stat-number"><?= $confirmedCount ?></div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">₱<?= number_format($totalRevenue, 0) ?></div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>
            </div>

            <!-- Quick Filter Tabs -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <a href="?filter=all<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $filter == 'all' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm">
                    All Bookings
                </a>
                <a href="?filter=pending<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $filter == 'pending' ? 'btn-warning' : 'btn-outline-warning' ?> btn-sm">
                    Pending
                </a>
                <a href="?filter=confirmed<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $filter == 'confirmed' ? 'btn-success' : 'btn-outline-success' ?> btn-sm">
                    Confirmed
                </a>
                <a href="?filter=completed<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $filter == 'completed' ? 'btn-info' : 'btn-outline-info' ?> btn-sm">
                    Completed
                </a>
                <a href="?filter=cancelled<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $filter == 'cancelled' ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm">
                    Cancelled
                </a>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-bar">
                <form method="get" class="row g-3">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by customer, email, phone, or service..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="admin_bookings.php?filter=<?= urlencode($filter) ?>" class="btn btn-outline-secondary w-100">Clear Search</a>
                    </div>
                </form>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type'] ?: 'info') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flash['msg'] ?: '') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current filter indicator -->
            <div class="mb-3">
                <span class="text-muted">
                    Showing: <strong><?= $filter == 'all' ? 'All Bookings' : ucfirst($filter) . ' Bookings' ?></strong>
                    <?php if (!empty($search)): ?>
                        with search "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php endif; ?>
                    (<?= count($bookings) ?> results)
                </span>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="card p-5 text-center">
                    <div class="mb-4">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                    </div>
                    <h5 class="mb-3">No bookings found</h5>
                    <p class="text-muted mb-4">
                        <?= !empty($search) ? 'No bookings match your search criteria.' : 'No ' . ($filter != 'all' ? $filter . ' ' : '') . 'bookings available.' ?>
                    </p>
                    <?php if ($filter != 'pending'): ?>
                        <a href="?filter=pending" class="btn btn-primary">View Pending Bookings</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($bookings as $b): 
                        // Determine badge classes
                        $status = $b['status'] ?? 'pending';
                        $payment_status = $b['payment_status'] ?? 'pending';
                        $statusBadgeClass = $status === 'confirmed' ? 'badge bg-success' : 
                                          ($status === 'pending' ? 'badge bg-warning' : 
                                          ($status === 'cancelled' ? 'badge bg-danger' : 'badge bg-info'));
                        $paymentBadgeClass = $payment_status === 'verified' ? 'badge bg-success' : 'badge bg-warning';
                        $bookingCardClass = 'booking-card ' . $status;
                        
                        // Format time to 12-hour
                        $formattedTime = formatTimeTo12Hour($b['booking_time'] ?? '');
                    ?>
                        <div class="col-12">
                            <div class="card <?= $bookingCardClass ?> shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <!-- Customer Info -->
                                        <div class="col-lg-2 mb-3 mb-lg-0">
                                            <p class="text-sm text-muted mb-1">Customer</p>
                                            <div class="fw-semibold"><?= htmlspecialchars($b['customer_name'] ?: 'Unknown') ?></div>
                                            <div class="muted small"><?= htmlspecialchars($b['customer_email'] ?? '') ?></div>
                                            <div class="muted small"><?= htmlspecialchars($b['customer_phone'] ?? '') ?></div>
                                        </div>

                                        <!-- Service Info -->
                                        <div class="col-lg-2 mb-3 mb-lg-0">
                                            <p class="text-sm text-muted mb-1">Service</p>
                                            <div class="fw-semibold"><?= htmlspecialchars($b['service_name'] ?? '—') ?></div>
                                            <div class="muted small"><?= htmlspecialchars($b['stylist_name'] ?? '—') ?></div>
                                        </div>

                                        <!-- Date & Time -->
                                        <div class="col-lg-2 mb-3 mb-lg-0">
                                            <p class="text-sm text-muted mb-1">Date & Time</p>
                                            <div class="fw-semibold"><?= date('M d, Y', strtotime($b['booking_date'])) ?></div>
                                            <div class="time-badge"><?= $formattedTime ?></div>
                                            <small class="text-muted">Booked: <?= date('M d', strtotime($b['created_at'] ?? 'now')) ?></small>
                                        </div>

                                        <!-- Payment Info -->
                                        <div class="col-lg-2 mb-3 mb-lg-0">
                                            <p class="text-sm text-muted mb-1">Payment</p>
                                            <div class="fw-semibold">₱<?= number_format((float)$b['total_amount'], 2) ?></div>
                                            <?php if (!empty($b['down_payment']) && $b['down_payment'] > 0): ?>
                                                <small>Down: ₱<?= number_format((float)$b['down_payment'], 2) ?></small>
                                            <?php endif; ?>
                                            <div class="mt-1 <?= $paymentBadgeClass ?> small"><?= ucfirst($payment_status) ?></div>
                                            <?php if (!empty($b['payment_proof_url'])): ?>
                                                <div class="mt-2">
                                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#proofModal" 
                                                            data-img="<?= htmlspecialchars($b['payment_proof_url']) ?>" 
                                                            data-customer="<?= htmlspecialchars($b['customer_name']) ?>">
                                                        <i class="bi bi-image"></i> View Proof
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Status & Actions -->
                                        <div class="col-lg-4">
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                                <span class="<?= $statusBadgeClass ?>"><?= ucfirst($status) ?></span>
                                                
                                                <?php if ($status === 'pending'): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Approve this booking?')">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= intval($b['id']) ?>">
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Cancel this booking?')">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= intval($b['id']) ?>">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                    </form>
                                                <?php elseif ($status === 'confirmed'): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Mark this booking as completed?')">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= intval($b['id']) ?>">
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="btn btn-sm btn-primary">Complete</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($payment_status === 'pending' && !empty($b['payment_proof_url'])): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_payment">
                                                        <input type="hidden" name="booking_id" value="<?= intval($b['id']) ?>">
                                                        <input type="hidden" name="payment_status" value="verified">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Verify Payment</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($b['customer_notes'])): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Notes: <?= nl2br(htmlspecialchars($b['customer_notes'])) ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="proofMeta" class="mb-3 text-muted"></div>
                    <img id="proofImage" src="" alt="Payment Proof" class="img-fluid rounded" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        
        // Payment proof modal
        var proofModal = document.getElementById('proofModal');
        if (proofModal) {
            proofModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var img = button.getAttribute('data-img');
                var customer = button.getAttribute('data-customer') || '';
                document.getElementById('proofImage').src = img;
                document.getElementById('proofMeta').textContent = customer ? 'Customer: ' + customer : '';
            });
        }
    </script>
</body>
</html>