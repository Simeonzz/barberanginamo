<?php
// config.php - Updated version with proper session and database handling
$host = '127.0.0.1';
$user = 'root';
$pass = '';          // XAMPP default
$db   = 'barberanginamodb';
$port = 3306;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global database connection variable
$conn = null;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    mysqli_real_connect($conn, $host, $user, $pass, $db, $port);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "<h2>Database connection failed</h2>";
    echo "<p>Could not connect to MySQL using the settings in <code>config.php</code>.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Authentication check function for logged in users
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: auth.php');
        exit();
    }
}

// Admin role check function
function requireAdmin() {
    requireAuth(); // First check if user is logged in
    
    // Check if user has admin role
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Method 1: Check users table for role column
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['role'] !== 'admin') {
                // Not admin, redirect to user dashboard
                header('Location: index.php');
                exit();
            }
        } else {
            // User not found or no role column
            header('Location: auth.php');
            exit();
        }
        $stmt->close();
    }
}
?>