<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php'; // provides $conn (mysqli)
require_once 'send_gmail_register.php'; // <-- connect email sender
require_once 'send_otp.php'; // <-- Add OTP sender

function e($v){ return htmlspecialchars($v, ENT_QUOTES); }

$errors = [];
$success = '';
$tab = 'login'; // which tab to show by default

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'login';

  if ($action === 'login') {
    $tab = 'login';
    $ident = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($ident === '' || $password === '') {
      $errors[] = 'Please provide both identifier and password.';
    } else {
      // Try each possible identifier column in sequence
      $candidates = ['email', 'phone', 'username'];
      $user = null;
      
      foreach ($candidates as $col) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE `$col` = ? LIMIT 1");
        if (!$stmt) continue;
        
        $stmt->bind_param("s", $ident);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows) {
          $user = $res->fetch_assoc();
          $stmt->close();
          break;
        }
        $stmt->close();
      }
      
      if ($user) {
        if (!isset($user['password'])) {
          $errors[] = 'Password column missing in users table.';
        } elseif (password_verify($password, $user['password'])) {
          // Login successful
          $_SESSION['user_id'] = (int)$user['id'];
          $_SESSION['user_display'] = $user['fullname'] ?? $user['name'] ?? 'User';
          $_SESSION['user_role'] = $user['role'] ?? 'customer';
          session_regenerate_id(true);

          // Redirect based on role
          if (isset($user['role']) && $user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
          } else {
            header('Location: index.php');
          }
          exit;
        } else {
          $errors[] = 'Invalid password.';
        }
      } else {
        $errors[] = 'Account not found.';
      }
    }

  } elseif ($action === 'register') {
    $tab = 'register';
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || $phone === '' || $email === '' || $password === '' || $confirm_password === '') {
      $errors[] = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
      $errors[] = 'Passwords do not match.';
    } else {
      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
      }
      // Validate password strength
      elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
      }
      elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain both letters and numbers.';
      }
      else {
        // determine columns mapping
        $colsRes = $conn->query("SHOW COLUMNS FROM users");
        $cols = [];
        while ($r = $colsRes->fetch_assoc()) $cols[] = $r['Field'];

        // map to existing columns
        $map = [];
        $map['fullname'] = in_array('fullname', $cols, true) ? 'fullname' : (in_array('name', $cols, true) ? 'name' : null);
        $map['phone'] = in_array('phone', $cols, true) ? 'phone' : (in_array('contact', $cols, true) ? 'contact' : null);
        $map['email'] = in_array('email', $cols, true) ? 'email' : (in_array('user_email', $cols, true) ? 'user_email' : null);
        $map['password'] = in_array('password', $cols, true) ? 'password' : null;

        $missing = [];
        foreach (['fullname','phone','email','password'] as $k) if (empty($map[$k])) $missing[] = $k;
        if ($missing) {
          $errors[] = 'Users table missing columns: ' . implode(', ', $missing) . '. Ask admin to add them or adjust schema.';
        } else {
          // check duplicate
          $stmt = $conn->prepare("SELECT id FROM users WHERE `{$map['email']}` = ? LIMIT 1");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $dupe = $stmt->get_result();
          if ($dupe && $dupe->num_rows) {
            $errors[] = 'Email already registered.';
          } else {
            // Check if email is already pending verification
            $check_pending = $conn->prepare("SELECT id FROM email_verification WHERE email = ? AND verified = 0 AND expires_at > NOW()");
            $check_pending->bind_param("s", $email);
            $check_pending->execute();
            $pending_result = $check_pending->get_result();
            
            if ($pending_result->num_rows > 0) {
              // Update existing pending verification
              $hashed = password_hash($password, PASSWORD_DEFAULT);
              $username = strtolower(explode('@', $email)[0]);
              $otp = generateOTP();
              $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
              
              $update = $conn->prepare("UPDATE email_verification SET fullname = ?, phone = ?, password = ?, username = ?, otp_code = ?, expires_at = ? WHERE email = ?");
              $update->bind_param("sssssss", $fullname, $phone, $hashed, $username, $otp, $expires_at, $email);
              
              if ($update->execute()) {
                // Send OTP email
                $emailResult = sendOTPEmail($email, $fullname, $otp);
                
                if ($emailResult['success']) {
                  $_SESSION['pending_verification'] = ['email' => $email, 'fullname' => $fullname];
                  header('Location: verify_otp.php');
                  exit;
                } else {
                  $errors[] = 'Failed to send verification email. ' . $emailResult['message'];
                }
              } else {
                $errors[] = 'Failed to update verification. Please try again.';
              }
            } else {
              // New registration - store in verification table first
              $hashed = password_hash($password, PASSWORD_DEFAULT);
              
              // Generate unique username based on email
              $username = strtolower(explode('@', $email)[0]);
              $base_username = $username;
              $counter = 1;
              while (true) {
                $check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $check->bind_param("s", $username);
                $check->execute();
                if ($check->get_result()->num_rows === 0) break;
                $username = $base_username . $counter;
                $counter++;
              }
              
              // Generate OTP
              $otp = generateOTP();
              $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
              
              // Insert into email_verification table
              $insert_verify = $conn->prepare("INSERT INTO email_verification (email, fullname, phone, password, username, otp_code, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
              $insert_verify->bind_param("sssssss", $email, $fullname, $phone, $hashed, $username, $otp, $expires_at);
              
              if ($insert_verify->execute()) {
                // Send OTP email
                $emailResult = sendOTPEmail($email, $fullname, $otp);
                
                if ($emailResult['success']) {
                  $_SESSION['pending_verification'] = ['email' => $email, 'fullname' => $fullname];
                  header('Location: verify_otp.php');
                  exit;
                } else {
                  $errors[] = 'Failed to send verification email. ' . $emailResult['message'];
                }
              } else {
                $errors[] = 'Failed to initiate verification. Please try again.';
              }
            }
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login & Register | Barberang Ina Mo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1a1a1a;
      --gold: #FFD700;
      --light-gold: #fff9e6;
      --dark-gold: #cca300;
      --gray: #f8f9fa;
    }
    
    body {
      background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .auth-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .auth-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      width: 100%;
      max-width: 450px;
      transition: transform 0.3s ease;
    }
    
    .auth-card:hover {
      transform: translateY(-5px);
    }
    
    .auth-header {
      background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
      padding: 30px;
      text-align: center;
      position: relative;
    }
    
    .logo-container {
      width: 100px;
      height: 100px;
      margin: 0 auto 20px;
      border-radius: 50%;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border: 3px solid var(--gold);
    }
    
    .logo-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .auth-title {
      color: var(--gold);
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    .auth-subtitle {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.9rem;
    }
    
    .auth-body {
      padding: 30px;
    }
    
    /* Tabs */
    .auth-tabs {
      display: flex;
      margin-bottom: 25px;
      border-bottom: 2px solid #eee;
    }
    
    .auth-tab {
      flex: 1;
      text-align: center;
      padding: 12px;
      background: none;
      border: none;
      font-size: 1rem;
      font-weight: 600;
      color: #999;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
    }
    
    .auth-tab.active {
      color: var(--primary);
    }
    
    .auth-tab.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--gold);
      border-radius: 3px 3px 0 0;
    }
    
    /* Form */
    .auth-form {
      display: none;
    }
    
    .auth-form.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .form-label {
      color: #333;
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
    }
    
    .form-control {
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      padding: 12px 15px;
      font-size: 0.95rem;
      transition: all 0.3s;
      background: #fff;
    }
    
    .form-control:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
    }
    
    .btn-auth {
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
      margin-top: 10px;
    }
    
    .btn-auth:hover {
      background: linear-gradient(135deg, #ffed4e 0%, #e6c200 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
    }
    
    /* Messages */
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
    
    /* Links */
    .auth-link {
      text-align: center;
      margin-top: 20px;
      color: #666;
      font-size: 0.9rem;
    }
    
    .auth-link a {
      color: var(--gold);
      text-decoration: none;
      font-weight: 600;
    }
    
    .auth-link a:hover {
      text-decoration: underline;
    }
    
    /* Responsive */
    @media (max-width: 576px) {
      .auth-card {
        border-radius: 15px;
      }
      
      .auth-header {
        padding: 20px;
      }
      
      .auth-body {
        padding: 20px;
      }
      
      .logo-container {
        width: 80px;
        height: 80px;
      }
    }
    
    /* Forgot password */
    .forgot-password {
      text-align: right;
      margin-bottom: 15px;
    }
    
    .forgot-password a {
      color: #666;
      text-decoration: none;
      font-size: 0.9rem;
    }
    
    .forgot-password a:hover {
      color: var(--gold);
      text-decoration: underline;
    }

    /* Password strength indicator */
    .password-strength {
      height: 5px;
      margin-top: 8px;
      border-radius: 5px;
      transition: all 0.3s;
    }

    .strength-weak {
      background: #ff4444;
      width: 33.33%;
    }

    .strength-medium {
      background: #ffbb33;
      width: 66.66%;
    }

    .strength-strong {
      background: #00C851;
      width: 100%;
    }

    /* Password match indicator */
    .password-match {
      font-size: 0.85rem;
      margin-top: 5px;
    }

    .match-success {
      color: #00C851;
    }

    .match-error {
      color: #ff4444;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="auth-container">
    <div class="auth-card">
      <!-- Header -->
      <div class="auth-header">
        <div class="logo-container">
          <?php if(file_exists('uploads/picture/LOGO.jpg')): ?>
            <img src="uploads/picture/LOGO.jpg" alt="Barberang Ina Mo Logo">
          <?php else: ?>
            <i class="bi bi-scissors" style="font-size: 3rem; color: var(--gold);"></i>
          <?php endif; ?>
        </div>
        <h1 class="auth-title">BARBERANG INA MO</h1>
        <p class="auth-subtitle">Your beauty, our passion</p>
      </div>
      
      <!-- Body -->
      <div class="auth-body">
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
          <div class="alert-message alert-error">
            <?php echo e(implode('<br>', $errors)); ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert-message alert-success">
            <?php echo e($success); ?>
          </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="auth-tabs">
          <button class="auth-tab <?php echo ($tab === 'login') ? 'active' : ''; ?>" data-tab="login">
            Login
          </button>
          <button class="auth-tab <?php echo ($tab === 'register') ? 'active' : ''; ?>" data-tab="register">
            Register
          </button>
        </div>
        
        <!-- Login Form -->
        <form method="POST" class="auth-form <?php echo ($tab === 'login') ? 'active' : ''; ?>" id="login-form">
          <input type="hidden" name="action" value="login">
          
          <div class="mb-3">
            <label class="form-label" for="identifier">Email, Phone, or Username</label>
            <input type="text" class="form-control" id="identifier" name="identifier" 
                   placeholder="Enter email, phone, or username" required 
                   value="<?php echo e($_POST['identifier'] ?? ''); ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          
          <div class="forgot-password">
            <a href="forgot.php">Forgot password?</a>
          </div>
          
          <button type="submit" class="btn btn-auth">Login</button>
        </form>
        
        <!-- Register Form -->
        <form method="POST" class="auth-form <?php echo ($tab === 'register') ? 'active' : ''; ?>" id="register-form" onsubmit="return validateForm()">
          <input type="hidden" name="action" value="register">
          
          <div class="mb-3">
            <label class="form-label" for="fullname">Full Name</label>
            <input type="text" class="form-control" id="fullname" name="fullname" 
                   placeholder="Enter your full name" required 
                   value="<?php echo e($_POST['fullname'] ?? ''); ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" 
                   placeholder="Enter your phone number" required 
                   value="<?php echo e($_POST['phone'] ?? ''); ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" 
                   placeholder="Enter your email" required 
                   value="<?php echo e($_POST['email'] ?? ''); ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="reg_password">Password</label>
            <input type="password" class="form-control" id="reg_password" name="password" 
                   placeholder="Create a password" required onkeyup="checkPasswordStrength(this.value); checkPasswordMatch();">
            <div class="password-strength" id="password-strength"></div>
            <small class="text-muted">At least 8 characters with letters and numbers</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                   placeholder="Re-enter your password" required onkeyup="checkPasswordMatch()">
            <div id="password-match-message" class="password-match"></div>
          </div>
          
          <button type="submit" class="btn btn-auth">Create Account</button>
        </form>
        
        <div class="auth-link">
          <?php if($tab === 'login'): ?>
            Don't have an account? <a href="#" onclick="switchTab('register'); return false;" data-tab="register">Sign up here</a>
          <?php else: ?>
            Already have an account? <a href="#" onclick="switchTab('login'); return false;" data-tab="login">Login here</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching function
    function switchTab(tabName) {
      // Update tabs
      document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.classList.remove('active');
        if(tab.getAttribute('data-tab') === tabName) {
          tab.classList.add('active');
        }
      });
      
      // Update forms
      document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
      });
      document.getElementById(tabName + '-form').classList.add('active');
      
      // Update URL
      history.replaceState(null, '', '?tab=' + tabName);
    }

    // Tab switching event listeners
    document.querySelectorAll('.auth-tab').forEach(element => {
      element.addEventListener('click', function(e) {
        e.preventDefault();
        const targetTab = this.getAttribute('data-tab');
        switchTab(targetTab);
      });
    });
    
    // Auto-focus on first input
    document.addEventListener('DOMContentLoaded', function() {
      const activeForm = document.querySelector('.auth-form.active');
      if (activeForm) {
        const firstInput = activeForm.querySelector('input');
        if (firstInput) {
          setTimeout(() => firstInput.focus(), 100);
        }
      }
    });

    // Password strength checker
    function checkPasswordStrength(password) {
      const strengthIndicator = document.getElementById('password-strength');
      strengthIndicator.className = 'password-strength';
      
      if (password.length === 0) {
        strengthIndicator.style.width = '0';
        return;
      }
      
      let strength = 0;
      
      // Check length
      if (password.length >= 8) strength++;
      if (password.length >= 12) strength++;
      
      // Check for letters and numbers
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;
      
      // Determine strength class
      if (strength <= 2) {
        strengthIndicator.classList.add('strength-weak');
      } else if (strength <= 4) {
        strengthIndicator.classList.add('strength-medium');
      } else {
        strengthIndicator.classList.add('strength-strong');
      }
    }

    // Check if passwords match
    function checkPasswordMatch() {
      const password = document.getElementById('reg_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchMessage = document.getElementById('password-match-message');
      
      if (confirmPassword.length === 0) {
        matchMessage.innerHTML = '';
        return;
      }
      
      if (password === confirmPassword) {
        matchMessage.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords match';
        matchMessage.className = 'password-match match-success';
      } else {
        matchMessage.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Passwords do not match';
        matchMessage.className = 'password-match match-error';
      }
    }

    // Validate form before submission
    function validateForm() {
      const password = document.getElementById('reg_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
      }
      
      if (password.length < 8) {
        alert('Password must be at least 8 characters long!');
        return false;
      }
      
      if (!password.match(/[A-Za-z]/) || !password.match(/[0-9]/)) {
        alert('Password must contain both letters and numbers!');
        return false;
      }
      
      return true;
    }
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>