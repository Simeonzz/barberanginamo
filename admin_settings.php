<?php
session_start(); 
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        
        /* Settings Cards */
        .settings-card {
            height: 100%;
        }
        
        .settings-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .bg-business-hours {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .bg-notifications {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .bg-security {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .bg-data {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
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
                <h1>Settings</h1>
                <p>Configure system preferences and options</p>
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
            <div class="mb-5">
                <h1 class="fw-bold">Settings</h1>
                <p class="text-muted">Configure system preferences and options</p>
            </div>

            <div class="row g-4">
                <!-- Business Hours -->
                <div class="col-lg-8">
                    <div class="card settings-card">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <div class="settings-icon bg-business-hours">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Business Hours</h5>
                                <p class="text-muted mb-0 small">Configure salon operating hours</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="businessHoursForm">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Opening Time</th>
                                            <th>Closing Time</th>
                                            <th>Closed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $days = [
                                            ['name' => 'Monday', 'id' => 'monday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Tuesday', 'id' => 'tuesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Wednesday', 'id' => 'wednesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Thursday', 'id' => 'thursday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Friday', 'id' => 'friday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Saturday', 'id' => 'saturday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                                            ['name' => 'Sunday', 'id' => 'sunday', 'open' => '09:00', 'close' => '18:00', 'closed' => true]
                                        ];
                                        
                                        foreach ($days as $day): 
                                        ?>
                                        <tr>
                                            <td><?= $day['name'] ?></td>
                                            <td>
                                                <input type="time" 
                                                       id="<?= $day['id'] ?>_open" 
                                                       value="<?= $day['open'] ?>" 
                                                       class="form-control time-input"
                                                       <?= $day['closed'] ? 'disabled' : '' ?>>
                                            </td>
                                            <td>
                                                <input type="time" 
                                                       id="<?= $day['id'] ?>_close" 
                                                       value="<?= $day['close'] ?>" 
                                                       class="form-control time-input"
                                                       <?= $day['closed'] ? 'disabled' : '' ?>>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input day-closed" 
                                                           type="checkbox" 
                                                           id="<?= $day['id'] ?>_closed" 
                                                           <?= $day['closed'] ? 'checked' : '' ?>
                                                           data-day="<?= $day['id'] ?>">
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetBusinessHours()">Reset</button>
                                    <button type="button" class="btn btn-primary" onclick="saveBusinessHours()">Save Hours</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick Settings -->
                <div class="col-lg-4">
                    <!-- Password Change -->
                    <div class="card settings-card mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <div class="settings-icon bg-security">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Change Password</h5>
                                <p class="text-muted mb-0 small">Update your account password</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" id="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" id="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" id="confirm_password" class="form-control" required>
                                </div>
                                <button type="button" class="btn btn-primary w-100" onclick="changePassword()">
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card settings-card">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <div class="settings-icon bg-data">
                                <i class="bi bi-info-circle-fill"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">System Information</h5>
                                <p class="text-muted mb-0 small">System status and version</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">PHP Version</small>
                                <strong><?= phpversion() ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Server Time</small>
                                <strong><?= date('Y-m-d H:i:s') ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Memory Usage</small>
                                <strong><?= round(memory_get_usage() / 1024 / 1024, 2) ?> MB</strong>
                            </div>
                            <div class="text-center mt-4">
                                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh System
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Settings -->
            <div class="row g-4 mt-3">
                <!-- Booking Settings -->
                <div class="col-md-6">
                    <div class="card settings-card h-100">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <div class="settings-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                <i class="bi bi-calendar-check-fill text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Booking Settings</h5>
                                <p class="text-muted mb-0 small">Configure booking rules</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Advance Booking Limit (Days)</label>
                                <input type="number" id="advanceLimit" class="form-control" value="30" min="1" max="365">
                                <div class="form-text">Maximum days in advance customers can book</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Slot Duration (Minutes)</label>
                                <input type="number" id="slotDuration" class="form-control" value="30" min="15" max="120">
                                <div class="form-text">Duration of each booking time slot</div>
                            </div>
                            <button class="btn btn-outline-primary w-100" onclick="saveBookingSettings()">Save Booking Settings</button>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="col-md-6">
                    <div class="card settings-card h-100">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <div class="settings-icon" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                                <i class="bi bi-bell-fill text-warning"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Notification Settings</h5>
                                <p class="text-muted mb-0 small">Configure system notifications</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                <label class="form-check-label" for="emailNotifications">
                                    Email Notifications
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="smsNotifications">
                                <label class="form-check-label" for="smsNotifications">
                                    SMS Notifications
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="reminder24h" checked>
                                <label class="form-check-label" for="reminder24h">
                                    24-Hour Reminder
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reminder1h" checked>
                                <label class="form-check-label" for="reminder1h">
                                    1-Hour Reminder
                                </label>
                            </div>
                            <button class="btn btn-outline-warning w-100 mt-3" onclick="saveNotificationSettings()">Save Notification Settings</button>
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
        
        // Enable/disable time inputs based on closed checkbox
        document.querySelectorAll('.day-closed').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.dataset.day;
                const openInput = document.getElementById(`${day}_open`);
                const closeInput = document.getElementById(`${day}_close`);
                
                if (this.checked) {
                    openInput.disabled = true;
                    closeInput.disabled = true;
                } else {
                    openInput.disabled = false;
                    closeInput.disabled = false;
                }
            });
        });
        
        // Initialize all time inputs based on current state
        document.querySelectorAll('.day-closed').forEach(checkbox => {
            checkbox.dispatchEvent(new Event('change'));
        });
        
        // Reset business hours to default
        function resetBusinessHours() {
            const defaultHours = {
                monday: { open: '09:00', close: '18:00', closed: false },
                tuesday: { open: '09:00', close: '18:00', closed: false },
                wednesday: { open: '09:00', close: '18:00', closed: false },
                thursday: { open: '09:00', close: '18:00', closed: false },
                friday: { open: '09:00', close: '18:00', closed: false },
                saturday: { open: '09:00', close: '18:00', closed: false },
                sunday: { open: '09:00', close: '18:00', closed: true }
            };
            
            for (const [day, settings] of Object.entries(defaultHours)) {
                document.getElementById(`${day}_open`).value = settings.open;
                document.getElementById(`${day}_close`).value = settings.close;
                document.getElementById(`${day}_closed`).checked = settings.closed;
                
                // Trigger change event to update disabled state
                document.getElementById(`${day}_closed`).dispatchEvent(new Event('change'));
            }
            
            showAlert('Business hours reset to default.', 'info');
        }
        
        // Save business hours
        function saveBusinessHours() {
            const hours = {};
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            days.forEach(day => {
                hours[day] = {
                    open: document.getElementById(`${day}_open`).value,
                    close: document.getElementById(`${day}_close`).value,
                    closed: document.getElementById(`${day}_closed`).checked
                };
            });
            
            // In a real app, you would save this to the database
            console.log('Saving business hours:', hours);
            showAlert('Business hours saved successfully!', 'success');
        }
        
        // Change password
        function changePassword() {
            const current = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (!current || !newPass || !confirmPass) {
                showAlert('Please fill in all password fields.', 'danger');
                return;
            }
            
            if (newPass !== confirmPass) {
                showAlert('New passwords do not match.', 'danger');
                return;
            }
            
            if (newPass.length < 6) {
                showAlert('Password must be at least 6 characters long.', 'danger');
                return;
            }
            
            // In a real app, you would make an AJAX request to change the password
            console.log('Changing password...');
            showAlert('Password changed successfully!', 'success');
            
            // Clear form
            document.getElementById('passwordForm').reset();
        }
        
        // Save booking settings
        function saveBookingSettings() {
            const advanceLimit = document.getElementById('advanceLimit').value;
            const slotDuration = document.getElementById('slotDuration').value;
            
            if (!advanceLimit || !slotDuration) {
                showAlert('Please fill in all booking settings.', 'danger');
                return;
            }
            
            // In a real app, you would save this to the database
            console.log('Saving booking settings:', { advanceLimit, slotDuration });
            showAlert('Booking settings saved successfully!', 'success');
        }
        
        // Save notification settings
        function saveNotificationSettings() {
            const emailNotifications = document.getElementById('emailNotifications').checked;
            const smsNotifications = document.getElementById('smsNotifications').checked;
            const reminder24h = document.getElementById('reminder24h').checked;
            const reminder1h = document.getElementById('reminder1h').checked;
            
            // In a real app, you would save this to the database
            console.log('Saving notification settings:', {
                emailNotifications,
                smsNotifications,
                reminder24h,
                reminder1h
            });
            
            showAlert('Notification settings saved successfully!', 'success');
        }
        
        // Show alert message
        function showAlert(message, type) {
            // Remove any existing alerts
            const existingAlert = document.querySelector('.alert-dismissible:not(.alert-fixed)');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }
            }, 3000);
        }
    </script>
</body>
</html>