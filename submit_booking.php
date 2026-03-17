<?php
// submit_booking.php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: auth.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$service_ids = isset($_POST['service_ids']) && is_array($_POST['service_ids']) ? array_map('intval', $_POST['service_ids']) : [];
$staff_id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$payment_proof_url = null;

// Basic validation
$errors = [];
if (empty($service_ids)) $errors[] = 'Please choose at least one service.';
if (!$booking_date) $errors[] = 'Please choose a booking date.';
if (!$booking_time) $errors[] = 'Please choose a booking time.';

// Validate date/time not in the past
$datetime = DateTime::createFromFormat('Y-m-d H:i', $booking_date . ' ' . $booking_time);
if (!$datetime) {
    $errors[] = 'Invalid date or time.';
} else {
    $now = new DateTime();
    if ($datetime < $now) $errors[] = 'Booking must be in the future.';
}

// Handle file upload (required)
if (empty($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Payment proof is required.';
} else {
    $file = $_FILES['payment_proof'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error uploading file.';
    } else {
        $maxSize = 4 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large. Max 4MB.';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $errors[] = 'Invalid file type. Use JPG or PNG.';
        } else {
            $ext = $allowed[$mime];
            $uploadsDir = __DIR__ . '/uploads/booking_proofs';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $filename = 'proof_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadsDir . '/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Failed to save upload.';
            } else {
                $payment_proof_url = 'uploads/booking_proofs/' . $filename;
            }
        }
    }
}

// If errors, save to session and redirect back
if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    header('Location: booking.php');
    exit;
}

// Get service prices for total_amounts
$service_prices = [];
if (!empty($service_ids)) {
    $ids_str = implode(',', $service_ids);
    $serviceRes = $conn->query("SELECT id, price FROM services WHERE id IN ($ids_str)");
    while ($row = $serviceRes->fetch_assoc()) {
        $service_prices[$row['id']] = floatval($row['price']);
    }
    // Check if all selected services exist
    foreach ($service_ids as $sid) {
        if (!isset($service_prices[$sid])) {
            $_SESSION['booking_errors'] = ['Invalid service selected.'];
            header('Location: booking.php');
            exit;
        }
    }
}

// Prevent double booking if staff is selected (for each service)
if ($staff_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND booking_date = ? AND booking_time = ? AND status IN ('pending', 'confirmed', 'approved')");
    $stmt->bind_param('iss', $staff_id, $booking_date, $booking_time);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
        $_SESSION['booking_errors'] = ['This staff member is already booked at this time. Please choose another staff or time.'];
        header('Location: booking.php');
        exit;
    }
}

// Combine all selected services into one booking row
$success = true;
$service_ids_str = implode(',', $service_ids);
$total_amount = 0;
foreach ($service_ids as $sid) {
    $total_amount += $service_prices[$sid];
}
$down_payment = $total_amount * 0.5;
$stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, staff_id, booking_date, booking_time, total_amount, down_payment, payment_proof_url, customer_notes, payment_status, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'unverified', 'pending', NOW())");
if ($stmt) {
    $stmt->bind_param("isissddss", $user_id, $service_ids_str, $staff_id, $booking_date, $booking_time, $total_amount, $down_payment, $payment_proof_url, $notes);
    if (!$stmt->execute()) {
        $success = false;
    }
    $stmt->close();
} else {
    $success = false;
}
if ($success) {
    if (!empty($staff_id)) {
        $staffStmt = $conn->prepare("UPDATE staff SET is_available = 0 WHERE id = ?");
        if ($staffStmt) {
            $staffStmt->bind_param("i", $staff_id);
            $staffStmt->execute();
            $staffStmt->close();
        }
    }
    $_SESSION['booking_success'] = 'Booking submitted successfully! We will contact you to confirm your appointment.';
    header('Location: index.php');
    exit;
} else {
    $_SESSION['booking_errors'] = ['Failed to save booking. Please try again.'];
    header('Location: booking.php');
    exit;
}
?>
