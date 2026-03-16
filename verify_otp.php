<?php
// verify_otp.php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
require_once 'send_otp.php';

$errors = [];
$success = '';
$email = $_SESSION['pending_verification']['email'] ?? '';
$fullname = $_SESSION['pending_verification']['fullname'] ?? '';

// If no pending verification, redirect to register
if (empty($email)) {
    header('Location: auth.php?tab=register');
    exit;
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            $errors[] = 'Please enter the OTP code.';
        } else {
            // First, let's check what's in the database for debugging
            $debug_stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = ? ORDER BY id DESC LIMIT 1");
            $debug_stmt->bind_param("s", $email);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            
            if ($debug_row = $debug_result->fetch_assoc()) {
                // Debug info - you can remove this after fixing
                error_log("Stored OTP: " . $debug_row['otp_code'] . ", Entered OTP: " . $otp);
                error_log("Expires at: " . $debug_row['expires_at'] . ", Current time: " . date('Y-m-d H:i:s'));
            }
            
            // Check OTP in database - without expiration first to debug
            $stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = ? AND otp_code = ? AND verified = 0 ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("ss", $email, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Now check if expired
                $current_time = date('Y-m-d H:i:s');
                if ($row['expires_at'] < $current_time) {
                    $errors[] = 'OTP has expired. Please request a new one.';
                } else {
                    // OTP is valid - move user to main users table
                    $fullname = $row['fullname'];
                    $phone = $row['phone'];
                    $username = $row['username'];
                    $hashed_password = $row['password'];
                    $role = 'customer';
                    
                    // Check what columns exist in users table
                    $colsRes = $conn->query("SHOW COLUMNS FROM users");
                    $cols = [];
                    while ($r = $colsRes->fetch_assoc()) $cols[] = $r['Field'];
                    
                    // Prepare insert based on available columns
                    $insert_success = false;
                    
                    if (in_array('fullname', $cols) && in_array('phone', $cols) && in_array('email', $cols) && in_array('username', $cols) && in_array('password', $cols) && in_array('role', $cols)) {
                        $insert = $conn->prepare("INSERT INTO users (fullname, phone, email, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                        $insert->bind_param("ssssss", $fullname, $phone, $email, $username, $hashed_password, $role);
                        $insert_success = $insert->execute();
                    } elseif (in_array('name', $cols) && in_array('contact', $cols) && in_array('user_email', $cols) && in_array('username', $cols) && in_array('password', $cols) && in_array('role', $cols)) {
                        $insert = $conn->prepare("INSERT INTO users (name, contact, user_email, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                        $insert->bind_param("ssssss", $fullname, $phone, $email, $username, $hashed_password, $role);
                        $insert_success = $insert->execute();
                    } else {
                        // Try dynamic column mapping
                        $user_cols = [];
                        $user_vals = [];
                        $types = "";
                        $params = [];
                        
                        if (in_array('fullname', $cols) || in_array('name', $cols)) {
                            $col_name = in_array('fullname', $cols) ? 'fullname' : 'name';
                            $user_cols[] = "`$col_name`";
                            $user_vals[] = $fullname;
                            $types .= "s";
                            $params[] = $fullname;
                        }
                        
                        if (in_array('phone', $cols) || in_array('contact', $cols)) {
                            $col_name = in_array('phone', $cols) ? 'phone' : 'contact';
                            $user_cols[] = "`$col_name`";
                            $user_vals[] = $phone;
                            $types .= "s";
                            $params[] = $phone;
                        }
                        
                        if (in_array('email', $cols) || in_array('user_email', $cols)) {
                            $col_name = in_array('email', $cols) ? 'email' : 'user_email';
                            $user_cols[] = "`$col_name`";
                            $user_vals[] = $email;
                            $types .= "s";
                            $params[] = $email;
                        }
                        
                        if (in_array('username', $cols)) {
                            $user_cols[] = "`username`";
                            $user_vals[] = $username;
                            $types .= "s";
                            $params[] = $username;
                        }
                        
                        if (in_array('password', $cols)) {
                            $user_cols[] = "`password`";
                            $user_vals[] = $hashed_password;
                            $types .= "s";
                            $params[] = $hashed_password;
                        }
                        
                        if (in_array('role', $cols)) {
                            $user_cols[] = "`role`";
                            $user_vals[] = $role;
                            $types .= "s";
                            $params[] = $role;
                        }
                        
                        if (!empty($user_cols)) {
                            $col_list = implode(', ', $user_cols);
                            $placeholders = implode(', ', array_fill(0, count($user_cols), '?'));
                            $insert_sql = "INSERT INTO users ($col_list) VALUES ($placeholders)";
                            $insert = $conn->prepare($insert_sql);
                            
                            if ($insert) {
                                $insert->bind_param($types, ...$params);
                                $insert_success = $insert->execute();
                            }
                        }
                    }
                    
                    if ($insert_success) {
                        // Mark as verified
                        $update = $conn->prepare("UPDATE email_verification SET verified = 1 WHERE id = ?");
                        $update->bind_param("i", $row['id']);
                        $update->execute();
                        
                        // Clear session
                        unset($_SESSION['pending_verification']);
                        
                        $success = 'Email verified successfully! You can now login.';
                        
                        // Redirect to login after 3 seconds
                        header("refresh:3;url=auth.php?tab=login");
                    } else {
                        $errors[] = 'Failed to create account. Please try again. Error: ' . ($conn->error ?? 'Unknown error');
                    }
                }
            } else {
                $errors[] = 'Invalid OTP code. Please try again.';
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Resend OTP
        $stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = ? AND verified = 0 ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Generate new OTP
            $new_otp = generateOTP();
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $update = $conn->prepare("UPDATE email_verification SET otp_code = ?, expires_at = ? WHERE id = ?");
            $update->bind_param("ssi", $new_otp, $expires_at, $row['id']);
            
            if ($update->execute()) {
                // Send new OTP
                $emailResult = sendOTPEmail($email, $row['fullname'], $new_otp);
                if ($emailResult['success']) {
                    $success = 'New OTP has been sent to your email.';
                } else {
                    $errors[] = 'Failed to send OTP. ' . $emailResult['message'];
                }
            } else {
                $errors[] = 'Failed to generate new OTP. Please try again.';
            }
        } else {
            $errors[] = 'No pending verification found. Please register again.';
        }
    }
}

// Add this debugging function at the bottom of the file (you can remove after fixing)
function checkOTPTable($conn, $email) {
    echo "<!-- Debug Info: \n";
    $result = $conn->query("SHOW CREATE TABLE email_verification");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Table structure: " . $row['Create Table'] . "\n";
    }
    
    $stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Current record: " . print_r($row, true) . "\n";
        echo "Current time: " . date('Y-m-d H:i:s') . "\n";
    } else {
        echo "No record found for email: $email\n";
    }
    echo " -->";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Barberang Ina Mo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --gold: #FFD700;
            --light-gold: #fff9e6;
            --dark-gold: #cca300;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .verify-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verify-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .verify-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 30px;
            text-align: center;
        }
        
        .verify-header h1 {
            color: var(--gold);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .verify-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .verify-body {
            padding: 30px;
        }
        
        .otp-input {
            text-align: center;
            font-size: 2rem;
            letter-spacing: 8px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .otp-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            outline: none;
        }
        
        .btn-verify {
            background: linear-gradient(135deg, var(--gold) 0%, var(--dark-gold) 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-verify:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #e6c200 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
        }
        
        .btn-resend {
            background: transparent;
            border: 2px solid var(--gold);
            color: var(--primary);
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-resend:hover {
            background: var(--light-gold);
        }
        
        .timer {
            text-align: center;
            font-size: 1.2rem;
            margin: 20px 0;
            color: #666;
        }
        
        .timer span {
            color: var(--gold);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .alert-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #ffe6e6;
            color: #cc0000;
            border-left-color: #cc0000;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #009900;
            border-left-color: #009900;
        }
        
        .email-display {
            background: var(--light-gold);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid var(--gold);
        }
        
        .email-display i {
            color: var(--gold);
            margin-right: 10px;
        }

        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            border: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <i class="bi bi-envelope-check" style="font-size: 3rem; color: var(--gold);"></i>
                <h1>VERIFY YOUR EMAIL</h1>
                <p>Please enter the OTP sent to your email</p>
            </div>
            
            <div class="verify-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert-message alert-error">
                        <?php echo implode('<br>', $errors); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-message alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <div class="email-display">
                    <i class="bi bi-envelope-fill"></i>
                    <?php echo htmlspecialchars($email); ?>
                </div>
                
                <form method="POST" id="verifyForm">
                    <div class="mb-3">
                        <label class="form-label">Enter OTP Code</label>
                        <input type="text" class="otp-input" name="otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" required>
                        <small class="text-muted d-block text-center mt-2">Enter the 6-digit code sent to your email</small>
                    </div>
                    
                    <button type="submit" name="verify_otp" class="btn btn-verify">
                        <i class="bi bi-check-circle"></i> Verify Email
                    </button>
                </form>
                
                <div class="timer" id="timer">
                    OTP expires in <span id="minutes">10</span>:<span id="seconds">00</span>
                </div>
                
                <form method="POST" id="resendForm">
                    <button type="submit" name="resend_otp" class="btn btn-resend" id="resendBtn">
                        <i class="bi bi-arrow-repeat"></i> Resend OTP
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="auth.php?tab=register" class="text-decoration-none" style="color: var(--gold);">
                        <i class="bi bi-arrow-left"></i> Back to Registration
                    </a>
                </div>

                <!-- Debug info - remove in production -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <?php checkOTPTable($conn, $email); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Timer functionality
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.minutes.textContent = minutes;
                display.seconds.textContent = seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    document.getElementById('resendBtn').disabled = false;
                    display.minutes.textContent = "00";
                    display.seconds.textContent = "00";
                }
            }, 1000);
        }

        window.onload = function () {
            // Disable resend button initially
            document.getElementById('resendBtn').disabled = true;
            
            // Set timer for 10 minutes
            var tenMinutes = 60 * 10,
                display = {
                    minutes: document.getElementById('minutes'),
                    seconds: document.getElementById('seconds')
                };
            startTimer(tenMinutes, display);
        };
        
        // Prevent form submission on Enter key in OTP field
        document.querySelector('.otp-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('verifyForm').submit();
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>