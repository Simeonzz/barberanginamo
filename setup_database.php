<?php
// First connect without database selected
$host = "localhost";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS barberanginamodb";
if ($conn->query($sql) === TRUE) {
    echo "Database 'barberanginamodb' created successfully or already exists!<br>";
} else {
    die("Error creating database: " . $conn->error . "<br>");
}

// Select the database
$conn->select_db("barberanginamodb");

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully or already exists!<br>";
} else {
    die("Error creating table: " . $conn->error . "<br>");
}


// Create bookings table with unique constraint to prevent double booking
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    service_id INT,
    staff_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'verified', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_staff_time (staff_id, booking_date, booking_time)
)";

if ($conn->query($sql) === TRUE) {
    echo "Bookings table created successfully or already exists!<br>";
} else {
    die("Error creating bookings table: " . $conn->error . "<br>");
}

// Create services table
$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT DEFAULT 30,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Services table created successfully or already exists!<br>";
} else {
    die("Error creating services table: " . $conn->error . "<br>");
}

$conn->close();
echo "<br>Database setup completed!<br>";
echo "<a href='create_admin.php?token=run_once_token_2025_!ChangeMe!'>Now click here to create admin user</a>";
?>