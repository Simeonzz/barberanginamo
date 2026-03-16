<?php
require_once 'config.php';

$username = 'Admin@Barberanginamo';
$email = 'Admin@Barberanginamo';
$fullname = 'Administrator';
$password = password_hash('admin', PASSWORD_DEFAULT);
$role = 'admin';

$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param('s', $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo "Admin already exists!";
} else {
  $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param('sssss', $username, $password, $fullname, $email, $role);
  if ($stmt->execute()) echo "Admin account created successfully!";
  else echo "Error: " . $stmt->error;
}
?>
