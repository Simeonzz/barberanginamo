<?php


// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barberanginamodb"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is admin (simplified - you should add proper authentication)
// For now, we'll just check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If session isn't started or user not logged in, redirect
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth_login.php");
        exit();
    }
}
// Define sidebar menu
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo ucfirst(str_replace(['admin_', '.php'], '', $currentPage)); ?></title>
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
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            <a href="?logout=true" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center">
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
                <h1><?php echo ucfirst(str_replace(['admin_', '.php', '_'], ['', '', ' '], $currentPage)); ?></h1>
                <p>Manage <?php echo str_replace(['admin_', '.php', '_'], ['', '', ' '], $currentPage); ?> efficiently</p>
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
            <!-- Content will be loaded here -->
            <?php
            // This will be filled by individual admin pages
            // Each admin page should include this layout and provide its content
            ?>