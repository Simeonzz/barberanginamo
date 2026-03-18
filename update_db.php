<?php
$conn = new mysqli('localhost', 'root', '', 'barberanginamodb');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if duration_display column exists
$result = $conn->query('SHOW COLUMNS FROM services LIKE "duration_display"');
if ($result->num_rows == 0) {
    echo 'Adding duration_display column...';
    if ($conn->query('ALTER TABLE services ADD COLUMN duration_display VARCHAR(50) AFTER duration')) {
        echo 'Column added successfully!';
    } else {
        echo 'Error adding column: ' . $conn->error;
    }
} else {
    echo 'Column already exists.';
}
$conn->close();
?>