<?php
session_start(); 
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// Database connection
$host = "localhost";
$dbname = "barberanginamodb";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to generate a unique username
function generateUniqueUsername($pdo, $fullname) {
    // Convert to lowercase, remove special chars, replace spaces with underscores
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $fullname));
    $base_username = trim($base_username, '_');
    
    if (empty($base_username)) {
        $base_username = 'guest';
    }
    
    $username = $base_username;
    $counter = 1;
    
    while (true) {
        // Check if username exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->rowCount() === 0) {
            break;
        }
        $username = $base_username . $counter;
        $counter++;
    }
    
    return $username;
}

// Setup users table if needed
function setupUsersTable($pdo) {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Create users table with username column
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            password VARCHAR(255),
            role VARCHAR(20) DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample customers with usernames
        $sampleCustomers = [
            ['username' => 'john_doe', 'fullname' => 'John Doe', 'email' => 'john@example.com', 'phone' => '09171234567', 'role' => 'customer'],
            ['username' => 'jane_smith', 'fullname' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '09172345678', 'role' => 'customer'],
            ['username' => 'mike_johnson', 'fullname' => 'Mike Johnson', 'email' => 'mike@example.com', 'phone' => '09173456789', 'role' => 'customer'],
            ['username' => 'sarah_williams', 'fullname' => 'Sarah Williams', 'email' => 'sarah@example.com', 'phone' => '09174567890', 'role' => 'customer'],
            ['username' => 'robert_brown', 'fullname' => 'Robert Brown', 'email' => 'robert@example.com', 'phone' => '09175678901', 'role' => 'customer']
        ];
        
        foreach ($sampleCustomers as $customer) {
            $stmt = $pdo->prepare("INSERT INTO users (username, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$customer['username'], $customer['fullname'], $customer['email'], $customer['phone'], $customer['role']]);
        }
    } else {
        // Check if username column exists, if not add it
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER id");
        }
    }
}

setupUsersTable($pdo);

// Function to setup bookings table with proper columns
function setupBookingsTable($pdo) {
    // Check if bookings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    if ($stmt->rowCount() == 0) {
        // Create bookings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            staff_id INT,
            booking_date DATE,
            booking_time TIME,
            total_amount DECIMAL(10,2),
            down_payment DECIMAL(10,2) DEFAULT 0,
            customer_notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        // Check if down_payment column exists, if not add it
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'down_payment'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN down_payment DECIMAL(10,2) DEFAULT 0 AFTER total_amount");
        }
    }
    
    // Create booking_items table for multiple services
    $stmt = $pdo->query("SHOW TABLES LIKE 'booking_items'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS booking_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT,
            service_id INT,
            price DECIMAL(10,2),
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        )");
    }
}

setupBookingsTable($pdo);

// Search customers
$searchTerm = $_GET["search"] ?? "";
$query = "SELECT * FROM users WHERE role = 'customer'";
$params = [];
if (!empty($searchTerm)) {
    $query .= " AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ? OR username LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all customers for dropdown (existing ones)
$allCustomers = $pdo->query("SELECT id, username, fullname, phone FROM users WHERE role = 'customer' ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

// Handle Add Booking
if (isset($_POST['add_booking'])) {
    $customer_type = $_POST['customer_type']; // 'existing' or 'new'
    $customer_id = null;
    $new_customer_name = '';
    
    if ($customer_type === 'existing') {
        $customer_id = $_POST['customer_id'];
        if (empty($customer_id)) {
            $error = "Please select a customer.";
        }
    } else {
        $new_customer_name = trim($_POST['new_customer_name']);
        $new_customer_email = trim($_POST['new_customer_email'] ?? '');
        $new_customer_phone = trim($_POST['new_customer_phone'] ?? '');
        
        if (empty($new_customer_name)) {
            $error = "Customer name is required for new customer.";
        } else {
            // Generate unique username
            $username = generateUniqueUsername($pdo, $new_customer_name);
            
            // MORE FLEXIBLE DUPLICATE CHECK
            $duplicate_found = false;
            $duplicate_message = "";
            
            // Check for exact email match only if email is provided
            if (!empty($new_customer_email)) {
                $checkEmailStmt = $pdo->prepare("SELECT id, fullname, email FROM users WHERE email = ? AND email IS NOT NULL AND email != ''");
                $checkEmailStmt->execute([$new_customer_email]);
                if ($checkEmailStmt->rowCount() > 0) {
                    $duplicate = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);
                    $duplicate_found = true;
                    $duplicate_message = "Email '{$new_customer_email}' is already used by {$duplicate['fullname']}. Please use a different email or select existing customer.";
                }
            }
            
            // Check for exact phone match only if phone is provided and no email duplicate found
            if (!$duplicate_found && !empty($new_customer_phone)) {
                $checkPhoneStmt = $pdo->prepare("SELECT id, fullname, phone FROM users WHERE phone = ? AND phone IS NOT NULL AND phone != ''");
                $checkPhoneStmt->execute([$new_customer_phone]);
                if ($checkPhoneStmt->rowCount() > 0) {
                    $duplicate = $checkPhoneStmt->fetch(PDO::FETCH_ASSOC);
                    $duplicate_found = true;
                    $duplicate_message = "Phone number '{$new_customer_phone}' is already used by {$duplicate['fullname']}. Please use a different phone or select existing customer.";
                }
            }
            
            if ($duplicate_found) {
                $error = $duplicate_message;
            } else {
                // Insert new customer with username
                $insertStmt = $pdo->prepare("INSERT INTO users (username, fullname, email, phone, role) VALUES (?, ?, ?, ?, 'customer')");
                if ($insertStmt->execute([$username, $new_customer_name, $new_customer_email, $new_customer_phone])) {
                    $customer_id = $pdo->lastInsertId();
                } else {
                    $errorInfo = $insertStmt->errorInfo();
                    $error = "Failed to create new customer: " . ($errorInfo[2] ?? 'Unknown error');
                }
            }
        }
    }
    
    if (empty($error) && $customer_id) {
        $selected_services = $_POST['services'] ?? [];
        $staff_id = $_POST['staff_id'];
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $total_amount = $_POST['total_amount'];
        $down_payment = $_POST['down_payment'];
        $customer_notes = $_POST['customer_notes'] ?? '';
        
        if (empty($selected_services)) {
            $error = "Please select at least one service.";
        } elseif (empty($staff_id)) {
            $error = "Please select a stylist.";
        } elseif (empty($booking_date) || empty($booking_time)) {
            $error = "Please select date and time.";
        } elseif (empty($total_amount) || $total_amount <= 0) {
            $error = "Please enter a valid total amount.";
        } elseif ($down_payment > $total_amount) {
            $error = "Down payment cannot be greater than total amount.";
        } else {
            try {
                // Insert main booking - status is 'confirmed' for admin bookings
                $sql = "INSERT INTO bookings (user_id, staff_id, booking_date, booking_time, total_amount, down_payment, customer_notes, status, payment_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', 'pending')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customer_id, $staff_id, $booking_date, $booking_time, $total_amount, $down_payment, $customer_notes]);
                
                $booking_id = $pdo->lastInsertId();
                
                // Insert booking items
                foreach ($selected_services as $service_id) {
                    // Get service price
                    $priceStmt = $pdo->prepare("SELECT price FROM services WHERE id = ?");
                    $priceStmt->execute([$service_id]);
                    $servicePrice = $priceStmt->fetchColumn();
                    
                    $itemStmt = $pdo->prepare("INSERT INTO booking_items (booking_id, service_id, price) VALUES (?, ?, ?)");
                    $itemStmt->execute([$booking_id, $service_id, $servicePrice]);
                }
                
                $success = "Booking added successfully for " . ($customer_type === 'new' ? $new_customer_name : "customer") . "!";
            } catch (PDOException $e) {
                $error = "Error adding booking: " . $e->getMessage();
            }
        }
    }
}

// Fetch services (for multiple selection)
$services = [];
try {
    // Setup services table if needed
    $stmt = $pdo->query("SHOW TABLES LIKE 'services'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            duration INT NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample services
        $sampleServices = [
            ['name' => 'Classic Haircut', 'description' => 'Traditional haircut', 'price' => 250.00, 'duration' => 30],
            ['name' => 'Premium Fade', 'description' => 'Modern fade with details', 'price' => 350.00, 'duration' => 45],
            ['name' => 'Beard Trim', 'description' => 'Professional beard grooming', 'price' => 150.00, 'duration' => 20],
            ['name' => 'Hair Wash & Style', 'description' => 'Wash, blowdry and style', 'price' => 200.00, 'duration' => 30],
            ['name' => 'Complete Package', 'description' => 'Haircut + Beard Trim + Wash', 'price' => 500.00, 'duration' => 60]
        ];
        
        foreach ($sampleServices as $service) {
            $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$service['name'], $service['description'], $service['price'], $service['duration']]);
        }
    }
    
    $services = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Continue even if services tables don't exist
}

// Fetch staff
$staff = [];
try {
    // Setup staff table if needed
    $stmt = $pdo->query("SHOW TABLES LIKE 'staff'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pseudonym VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL,
            specialty TEXT,
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample staff
        $sampleStaff = [
            ['pseudonym' => 'Master Barber', 'role' => 'Senior Barber', 'specialty' => 'Classic Cuts & Fades'],
            ['pseudonym' => 'Style King', 'role' => 'Stylist', 'specialty' => 'Modern Styles & Designs'],
            ['pseudonym' => 'Beard Guru', 'role' => 'Beard Specialist', 'specialty' => 'Beard Trims & Shaves'],
            ['pseudonym' => 'The Artist', 'role' => 'Hair Artist', 'specialty' => 'Creative Coloring & Designs']
        ];
        
        foreach ($sampleStaff as $staffMember) {
            $stmt = $pdo->prepare("INSERT INTO staff (pseudonym, role, specialty, is_available) VALUES (?, ?, ?, 1)");
            $stmt->execute([$staffMember['pseudonym'], $staffMember['role'], $staffMember['specialty']]);
        }
    }
    
    $staff = $pdo->query("SELECT * FROM staff WHERE is_available = 1 ORDER BY pseudonym")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Continue even if staff table doesn't exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Customer Records</title>
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
        
        /* Customer Card */
        .customer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Service Checkbox Styles */
        .service-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        
        .service-item:hover {
            background-color: #f8fafc;
            border-color: var(--primary-color);
        }
        
        .service-item input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .service-item label {
            width: 100%;
            cursor: pointer;
        }
        
        .service-price {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .selected-services-summary {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px;
            font-size: 0.9rem;
        }
        
        /* Customer Type Toggle */
        .customer-type-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .type-btn {
            flex: 1;
            padding: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .type-btn.active {
            border-color: var(--primary-color);
            background: #e8f0fe;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .type-btn i {
            font-size: 1.2rem;
            margin-right: 5px;
        }
        
        .customer-section {
            display: none;
        }
        
        .customer-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* New Customer Form */
        .new-customer-form {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Help text */
        .form-text {
            color: #6c757d;
            font-size: 0.75rem;
            margin-top: 4px;
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
                <h1>Customer Records</h1>
                <p>View customer profiles and add booking history</p>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Customer Records</h2>
                    <p class="text-muted">View customer profiles and add booking history</p>
                </div>
                <div>
                    <span class="badge bg-primary"><?= count($customers) ?> Customers</span>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <form method="get" class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search customers by name, username, email, or phone..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="admin_customers.php" class="btn btn-outline-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Customer List -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Customer List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($customers)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-people display-1 text-muted"></i>
                                    <h5 class="mt-3">No customers found</h5>
                                    <p class="text-muted"><?= !empty($searchTerm) ? 'Try a different search term' : 'No customers registered yet' ?></p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($customers as $c): 
                                        // Get customer statistics
                                        $bookingStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_spent, COUNT(*) AS total_bookings FROM bookings WHERE user_id = ?");
                                        $bookingStmt->execute([$c['id']]);
                                        $bookingData = $bookingStmt->fetch(PDO::FETCH_ASSOC);
                                        $totalSpent = $bookingData['total_spent'] ?? 0;
                                        $totalBookings = $bookingData['total_bookings'] ?? 0;
                                    ?>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start mb-3">
                                                        <div class="customer-avatar me-3">
                                                            <?= strtoupper(substr($c['fullname'], 0, 1)) ?>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h5 class="card-title mb-1"><?= htmlspecialchars($c['fullname']) ?></h5>
                                                            <p class="card-text text-muted small mb-2">
                                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($c['username'] ?? 'No username') ?><br>
                                                                <?php if (!empty($c['email'])): ?>
                                                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($c['email']) ?><br>
                                                                <?php endif; ?>
                                                                <?php if (!empty($c['phone'])): ?>
                                                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['phone']) ?>
                                                                <?php endif; ?>
                                                                <?php if (empty($c['email']) && empty($c['phone'])): ?>
                                                                    <span class="text-muted fst-italic">No contact info</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <span class="badge bg-info"><?= htmlspecialchars($c['role']) ?></span>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <div>
                                                            <small class="text-muted">Bookings</small>
                                                            <div class="fw-semibold"><?= $totalBookings ?></div>
                                                        </div>
                                                        <div>
                                                            <small class="text-muted">Total Spent</small>
                                                            <div class="fw-semibold">₱<?= number_format($totalSpent, 2) ?></div>
                                                        </div>
                                                        <div>
                                                            <small class="text-muted">Member Since</small>
                                                            <div class="fw-semibold"><?= date('M Y', strtotime($c['created_at'] ?? 'now')) ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline-primary flex-fill" 
                                                                onclick="viewCustomerDetails(<?= $c['id'] ?>, '<?= htmlspecialchars($c['fullname']) ?>')">
                                                            <i class="bi bi-eye me-1"></i> View Details
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" 
                                                                onclick="selectCustomerForBooking(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['fullname'])) ?>')">
                                                            <i class="bi bi-plus-circle"></i> Add Booking
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Add Booking Form -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Add Booking History</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="bookingForm" onsubmit="return validateForm()">
                                <!-- Customer Type Toggle -->
                                <div class="mb-3">
                                    <label class="form-label">Customer Type *</label>
                                    <div class="customer-type-toggle">
                                        <div class="type-btn active" onclick="toggleCustomerType('existing')" id="existingTypeBtn">
                                            <i class="bi bi-person-badge"></i> Existing Customer
                                        </div>
                                        <div class="type-btn" onclick="toggleCustomerType('new')" id="newTypeBtn">
                                            <i class="bi bi-person-plus"></i> New Guest
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="customer_type" id="customerType" value="existing">
                                
                                <!-- Existing Customer Section -->
                                <div class="customer-section active" id="existingCustomerSection">
                                    <div class="mb-3">
                                        <label class="form-label">Select Customer *</label>
                                        <select name="customer_id" id="customerSelect" class="form-control">
                                            <option value="">Select customer</option>
                                            <?php foreach ($allCustomers as $c): ?>
                                                <option value="<?= $c['id'] ?>">
                                                    <?= htmlspecialchars($c['fullname']) ?> (<?= htmlspecialchars($c['username'] ?? 'No username') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- New Customer Section -->
                                <div class="customer-section" id="newCustomerSection">
                                    <div class="new-customer-form">
                                        <div class="mb-3">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" name="new_customer_name" id="newCustomerName" class="form-control" placeholder="Enter guest name">
                                            <small class="form-text">Required for guest customers</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email (Optional)</label>
                                            <input type="email" name="new_customer_email" class="form-control" placeholder="Enter email (optional)">
                                            <small class="form-text">Leave empty for guest customers</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone (Optional)</label>
                                            <input type="text" name="new_customer_phone" class="form-control" placeholder="Enter phone (optional)">
                                            <small class="form-text">Leave empty for guest customers</small>
                                        </div>
                                        <div class="alert alert-info mt-2 mb-0 py-2">
                                            <small><i class="bi bi-info-circle"></i> Username will be auto-generated from the full name</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Services (Multiple Selection with Checkboxes) -->
                                <div class="mb-3">
                                    <label class="form-label">Services *</label>
                                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                        <?php if (empty($services)): ?>
                                            <p class="text-muted mb-0">No services available. Please add services first.</p>
                                        <?php else: ?>
                                            <?php foreach ($services as $s): ?>
                                                <div class="service-item">
                                                    <label class="d-flex align-items-center">
                                                        <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" 
                                                               data-price="<?= $s['price'] ?>" onchange="updateTotalAmount()">
                                                        <div class="flex-grow-1 ms-2">
                                                            <div class="d-flex justify-content-between">
                                                                <span class="fw-semibold"><?= htmlspecialchars($s['name']) ?></span>
                                                                <span class="service-price">₱<?= number_format($s['price'], 2) ?></span>
                                                            </div>
                                                            <small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="selected-services-summary mt-2" id="selectedServicesSummary">
                                        No services selected
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Stylist *</label>
                                    <select name="staff_id" class="form-control" required>
                                        <option value="">Select stylist</option>
                                        <?php foreach ($staff as $st): ?>
                                            <option value="<?= $st['id'] ?>">
                                                <?= htmlspecialchars($st['pseudonym']) ?> - <?= htmlspecialchars($st['specialty']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date *</label>
                                        <input type="date" name="booking_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Time *</label>
                                        <input type="time" name="booking_time" class="form-control" required value="14:00">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Total Amount *</label>
                                        <input type="number" step="0.01" name="total_amount" id="totalAmount" class="form-control" required readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Down Payment *</label>
                                        <input type="number" step="0.01" name="down_payment" id="downPayment" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea name="customer_notes" class="form-control" placeholder="Special requests or notes..." rows="3"></textarea>
                                </div>

                                <button type="submit" name="add_booking" class="btn btn-primary w-100">
                                    <i class="bi bi-calendar-plus me-2"></i> Add Booking Record
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="customerDetails">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading customer details...</p>
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
        
        // Toggle between existing and new customer
        function toggleCustomerType(type) {
            document.getElementById('customerType').value = type;
            
            // Update button styles
            document.getElementById('existingTypeBtn').classList.remove('active');
            document.getElementById('newTypeBtn').classList.remove('active');
            
            // Update sections
            document.getElementById('existingCustomerSection').classList.remove('active');
            document.getElementById('newCustomerSection').classList.remove('active');
            
            if (type === 'existing') {
                document.getElementById('existingTypeBtn').classList.add('active');
                document.getElementById('existingCustomerSection').classList.add('active');
                document.getElementById('customerSelect').required = true;
                document.getElementById('newCustomerName').required = false;
            } else {
                document.getElementById('newTypeBtn').classList.add('active');
                document.getElementById('newCustomerSection').classList.add('active');
                document.getElementById('customerSelect').required = false;
                document.getElementById('newCustomerName').required = true;
            }
        }
        
        // View customer details
        function viewCustomerDetails(customerId, customerName) {
            const customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
            const customerDetails = document.getElementById('customerDetails');
            
            // Show loading
            customerDetails.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading customer details...</p>
                </div>
            `;
            
            // Fetch customer details (simplified for now)
            setTimeout(() => {
                customerDetails.innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="customer-avatar d-inline-flex align-items-center justify-content-center" 
                                 style="width: 120px; height: 120px; font-size: 3rem;">
                                ${customerName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h4>${customerName}</h4>
                            <div class="mb-3">
                                <strong><i class="bi bi-person-badge me-2"></i>Customer ID:</strong> ${customerId}
                            </div>
                            <div class="mb-3">
                                <strong><i class="bi bi-calendar me-2"></i>Action:</strong> 
                                <button class="btn btn-sm btn-primary" onclick="selectCustomerForBooking(${customerId}, '${customerName.replace(/'/g, "\\'")}')">
                                    <i class="bi bi-plus-circle me-1"></i> Add Booking for this Customer
                                </button>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Detailed booking history will be displayed here when available.
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
            
            customerModal.show();
        }
        
        // Auto-select customer in booking form
        function selectCustomerForBooking(customerId, customerName) {
            // Switch to existing customer type
            toggleCustomerType('existing');
            document.getElementById('customerSelect').value = customerId;
            
            // Close modal if open
            const customerModal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
            if (customerModal) {
                customerModal.hide();
            }
            
            // Show notification
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                <strong>${customerName}</strong> selected for booking.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.getElementById('bookingForm');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }
            }, 3000);
        }
        
        // Calculate total amount based on selected services
        function updateTotalAmount() {
            const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
            let total = 0;
            let selectedServices = [];
            
            checkboxes.forEach(checkbox => {
                const price = parseFloat(checkbox.getAttribute('data-price'));
                total += price;
                
                // Get service name from parent label
                const serviceName = checkbox.closest('.service-item').querySelector('.fw-semibold').textContent;
                selectedServices.push(serviceName);
            });
            
            document.getElementById('totalAmount').value = total.toFixed(2);
            
            // Update summary
            const summary = document.getElementById('selectedServicesSummary');
            if (selectedServices.length > 0) {
                summary.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Selected: ${selectedServices.join(', ')}`;
                summary.classList.add('text-success');
            } else {
                summary.innerHTML = 'No services selected';
                summary.classList.remove('text-success');
            }
            
            // Validate down payment
            validateDownPayment();
        }
        
        // Validate down payment
        function validateDownPayment() {
            const total = parseFloat(document.getElementById('totalAmount').value) || 0;
            const downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
            
            if (downPayment > total) {
                document.getElementById('downPayment').classList.add('is-invalid');
                return false;
            } else {
                document.getElementById('downPayment').classList.remove('is-invalid');
                return true;
            }
        }
        
        // Form validation
        function validateForm() {
            const customerType = document.getElementById('customerType').value;
            
            // Validate customer selection
            if (customerType === 'existing') {
                const customerSelect = document.getElementById('customerSelect');
                if (!customerSelect.value) {
                    alert('Please select a customer.');
                    customerSelect.focus();
                    return false;
                }
            } else {
                const newCustomerName = document.getElementById('newCustomerName');
                if (!newCustomerName.value.trim()) {
                    alert('Please enter customer name.');
                    newCustomerName.focus();
                    return false;
                }
            }
            
            // Validate services
            const services = document.querySelectorAll('input[name="services[]"]:checked');
            if (services.length === 0) {
                alert('Please select at least one service.');
                return false;
            }
            
            // Validate staff
            const staff = document.querySelector('select[name="staff_id"]').value;
            if (!staff) {
                alert('Please select a stylist.');
                return false;
            }
            
            // Validate date and time
            const date = document.querySelector('input[name="booking_date"]').value;
            const time = document.querySelector('input[name="booking_time"]').value;
            if (!date || !time) {
                alert('Please select date and time.');
                return false;
            }
            
            // Validate down payment
            const total = parseFloat(document.getElementById('totalAmount').value);
            const downPayment = parseFloat(document.getElementById('downPayment').value);
            
            if (downPayment > total) {
                alert('Down payment cannot be greater than total amount.');
                return false;
            }
            
            return true;
        }
        
        // Add event listener for down payment validation
        document.getElementById('downPayment')?.addEventListener('input', validateDownPayment);
        
        // Auto-fill total when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateTotalAmount();
        });
    </script>
</body>
</html>
