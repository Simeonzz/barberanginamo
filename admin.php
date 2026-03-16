<?php
// admin.php - Redirect to login for admin access
session_start();

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Otherwise redirect to login
header("Location: auth.php");
exit();
?>