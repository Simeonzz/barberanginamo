<?php
// admin_staff.php - Complete working version
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// Database Configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'barberanginamodb';

// DB Connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('DB Connection failed: ' . $conn->connect_error);
}

// Setup staff table if needed
function setupStaffTable($conn) {
    // Check if staff table exists
    $result = $conn->query("SHOW TABLES LIKE 'staff'");
    if ($result->num_rows == 0) {
        // Create staff table
        $conn->query("CREATE TABLE IF NOT EXISTS staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pseudonym VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL,
            specialty TEXT,
            experience TEXT,
            image_url VARCHAR(500),
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample staff
        $sampleStaff = [
            ['pseudonym' => 'Master Barber', 'role' => 'Senior Barber', 'specialty' => 'Classic Cuts, Fades', 'experience' => '10+ years experience'],
            ['pseudonym' => 'Style King', 'role' => 'Stylist', 'specialty' => 'Modern Styles, Coloring', 'experience' => '8 years experience'],
            ['pseudonym' => 'Beard Guru', 'role' => 'Beard Specialist', 'specialty' => 'Beard Trims, Shaping', 'experience' => '6 years experience']
        ];
        
        foreach ($sampleStaff as $staff) {
            $stmt = $conn->prepare("INSERT INTO staff (pseudonym, role, specialty, experience, is_available) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $staff['pseudonym'], $staff['role'], $staff['specialty'], $staff['experience']);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Check for missing columns and add them
        $columns = $conn->query("SHOW COLUMNS FROM staff");
        $columnNames = [];
        while ($col = $columns->fetch_assoc()) {
            $columnNames[] = $col['Field'];
        }
        
        // Add missing columns
        if (!in_array('experience', $columnNames)) {
            $conn->query("ALTER TABLE staff ADD COLUMN experience TEXT AFTER specialty");
        }
        if (!in_array('image_url', $columnNames)) {
            $conn->query("ALTER TABLE staff ADD COLUMN image_url VARCHAR(500) AFTER experience");
        }
        if (!in_array('is_available', $columnNames)) {
            $conn->query("ALTER TABLE staff ADD COLUMN is_available BOOLEAN DEFAULT 1 AFTER image_url");
        }
        if (!in_array('created_at', $columnNames)) {
            $conn->query("ALTER TABLE staff ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_available");
        }
    }
}

setupStaffTable($conn);

// Handle Add/Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    $id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
    $pseudonym = trim($_POST['pseudonym'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    $image_url = $_POST['existing_image'] ?? null;
    // Handle file upload if present
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $upload_dir = 'uploads/staff/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file = $_FILES['image'];
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'staff_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $image_url = $target;
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Invalid image file. Only JPG, PNG, WEBP up to 2MB allowed.';
        }
    }
    // If no image uploaded and no existing image, use placeholder
    if (empty($image_url)) {
        $image_url = 'https://via.placeholder.com/400x300/4361ee/ffffff?text=' . urlencode($pseudonym);
    }
    
    // Basic validation
    if ($pseudonym === '' || $role === '' || $specialty === '') {
        $error = 'Please fill in all required fields.';
    } else {
        if ($id) {
            // Edit existing staff
            if ($image_url) {
                $stmt = $conn->prepare("UPDATE staff SET pseudonym=?, role=?, specialty=?, experience=?, image_url=? WHERE id=?");
                $stmt->bind_param("sssssi", $pseudonym, $role, $specialty, $experience, $image_url, $id);
            } else {
                $stmt = $conn->prepare("UPDATE staff SET pseudonym=?, role=?, specialty=?, experience=? WHERE id=?");
                $stmt->bind_param("ssssi", $pseudonym, $role, $specialty, $experience, $id);
            }
            
            if ($stmt->execute()) {
                $success = 'Staff updated successfully.';
            } else {
                $error = 'Failed to update staff.';
            }
            $stmt->close();
        } else {
            // Insert new staff
            $stmt = $conn->prepare("INSERT INTO staff (pseudonym, role, specialty, experience, image_url, is_available) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $pseudonym, $role, $specialty, $experience, $image_url);
            
            if ($stmt->execute()) {
                $success = 'Staff added successfully.';
            } else {
                $error = 'Failed to add staff.';
            }
            $stmt->close();
        }
    }
    
    // Redirect
    if (isset($success)) {
        header('Location: admin_staff.php?success=' . urlencode($success));
        exit();
    } elseif (isset($error)) {
        header('Location: admin_staff.php?error=' . urlencode($error));
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: admin_staff.php?success=' . urlencode('Staff deleted.'));
    exit();
}

// Handle Toggle Availability (AJAX or normal)
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE staff SET is_available = 1 - is_available WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Get new status
    $res = $conn->query("SELECT is_available FROM staff WHERE id = $id");
    $row = $res ? $res->fetch_assoc() : null;
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_available' => $row ? intval($row['is_available']) : 0]);
        exit();
    } else {
        header('Location: admin_staff.php?success=' . urlencode('Staff availability updated.'));
        exit();
    }
}

// Fetch all staff
$rows = [];
$res = $conn->query("SELECT * FROM staff ORDER BY id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Staff Management</title>
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
        
        /* Staff Card */
        .staff-img {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .staff-placeholder {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .badge-available {
            background: #10b981;
            color: white;
        }
        
        .badge-unavailable {
            background: #ef4444;
            color: white;
        }
        
        .badge-busy {
            background: #ef4444 !important;
            color: #fff !important;
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
                <h1>Staff Management</h1>
                <p>Manage stylists and their availability</p>
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
            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h2 class="fw-bold">Staff Management</h2>
                    <p class="text-muted">Manage stylists and their availability</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" id="busyDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="max-width: 140px;">
                    <input type="time" id="busyTime" class="form-control form-control-sm" value="<?= date('H:i') ?>" style="max-width: 110px;">
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staffModal" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-2"></i> Add Staff
                </button>
            </div>

            <?php if (empty($rows)): ?>
                <div class="card p-5 text-center">
                    <div class="mb-3">
                        <i class="bi bi-people display-1 text-muted"></i>
                    </div>
                    <h5 class="mb-1">No staff members yet</h5>
                    <p class="text-muted mb-0">Add your first staff member using the button above.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($rows as $member): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100">
                                <?php if (!empty($member['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($member['image_url']) ?>" alt="<?= htmlspecialchars($member['pseudonym']) ?>" class="staff-img">
                                <?php else: ?>
                                    <div class="staff-placeholder">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($member['pseudonym']) ?></h5>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($member['role']) ?></p>
                                        </div>
                                        <span class="badge staff-busy-badge <?php if ($member['is_available']) { echo 'badge-available'; } else { echo 'badge-busy'; } ?>" data-staff-id="<?= $member['id'] ?>">
                                            <?php if ($member['is_available']) { echo 'Available'; } else { echo 'Busy'; } ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text small text-muted mb-3">
                                        <strong>Specialty:</strong> <?= htmlspecialchars($member['specialty']) ?>
                                    </p>
                                    
                                    <?php if (!empty($member['experience'])): ?>
                                        <p class="card-text small text-muted mb-3">
                                            <strong>Experience:</strong> <?= htmlspecialchars($member['experience']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm flex-fill" onclick='openEditModal(<?= htmlspecialchars(json_encode($member), ENT_QUOTES, 'UTF-8') ?>)'>

                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        
                                        <?php if ($member['is_available']): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill toggle-availability-btn" data-staff-id="<?= $member['id'] ?>" data-available="1">
                                                <i class="bi bi-x-circle me-1"></i> Set Busy
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-success btn-sm flex-fill toggle-availability-btn" data-staff-id="<?= $member['id'] ?>" data-available="0">
                                                <i class="bi bi-check-circle me-1"></i> Set Available
                                            </button>
                                        <?php endif; ?>
                                        <a href="?delete=<?= $member['id'] ?>" class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this staff member?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff Modal -->
    <div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data" id="staffForm">
                    <input type="hidden" name="staff_id" id="staff_id">
                    <input type="hidden" name="existing_image" id="existing_image">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staffModalTitle">Add Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name (Pseudonym) *</label>
                                <input type="text" name="pseudonym" id="pseudonym" class="form-control" required 
                                       placeholder="e.g., Master Barber">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Role *</label>
                                <input type="text" name="role" id="role" class="form-control" required 
                                       placeholder="e.g., Senior Barber">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Specialty *</label>
                                <textarea name="specialty" id="specialty" class="form-control" rows="2" required 
                                          placeholder="Describe their specialty skills"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Experience (Optional)</label>
                                <textarea name="experience" id="experience" class="form-control" rows="2" 
                                          placeholder="Years of experience, certifications, etc."></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Profile Image (Optional)</label>
                                <input type="file" name="image" id="image" accept="image/*" class="form-control">
                                <div class="form-text">Max 2MB. JPG, PNG, WEBP only.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Image Preview</label>
                                <div id="imagePreview" class="border rounded p-2 text-center" 
                                     style="height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <div id="noImage" class="text-muted">
                                        <i class="bi bi-person-badge display-6"></i>
                                        <p class="mt-2 mb-0">No image selected</p>
                                    </div>
                                    <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="display: none; max-height: 140px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_staff" class="btn btn-primary">Save Staff</button>
                    </div>
                </form>
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
        
        // Initialize Bootstrap modal
        const staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
        
        // Open Add modal
        function openAddModal() {
            document.getElementById('staffForm').reset();
            document.getElementById('staff_id').value = '';
            document.getElementById('existing_image').value = '';
            document.getElementById('staffModalTitle').textContent = 'Add Staff';
            // Reset image preview
            const previewImg = document.getElementById('previewImg');
            const noImage = document.getElementById('noImage');
            previewImg.src = '';
            previewImg.style.display = 'none';
            noImage.style.display = 'block';
            staffModal.show();
        }
        
        // Open Edit modal
        function openEditModal(member) {
            console.log('Editing member:', member);
            
            document.getElementById('staffForm').reset();
            document.getElementById('staff_id').value = member.id || '';
            document.getElementById('existing_image').value = member.image_url || '';
            document.getElementById('pseudonym').value = member.pseudonym || '';
            document.getElementById('role').value = member.role || '';
            document.getElementById('specialty').value = member.specialty || '';
            document.getElementById('experience').value = member.experience || '';
            document.getElementById('staffModalTitle').textContent = 'Edit Staff';
            
            // Show existing image if available
            if (member.image_url && member.image_url.trim() !== '') {
                document.getElementById('previewImg').src = member.image_url;
                document.getElementById('previewImg').style.display = 'block';
                document.getElementById('noImage').style.display = 'none';
            } else {
                document.getElementById('previewImg').style.display = 'none';
                document.getElementById('noImage').style.display = 'block';
            }
            
            staffModal.show();
        }
        
        // Image preview for new uploads
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewImg = document.getElementById('previewImg');
            const noImage = document.getElementById('noImage');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    previewImg.src = ev.target.result;
                    previewImg.style.display = 'block';
                    noImage.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '';
                previewImg.style.display = 'none';
                noImage.style.display = 'block';
            }
        });
        
        // Form validation
        document.getElementById('staffForm').addEventListener('submit', function(e) {
            const pseudonym = document.getElementById('pseudonym').value.trim();
            const role = document.getElementById('role').value.trim();
            const specialty = document.getElementById('specialty').value.trim();
            
            if (!pseudonym || !role || !specialty) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            return true;
        });
        
        // Highlight staff as busy if they have bookings for the selected date and time slot
        function updateStaffBusyBadges() {
            var date = document.getElementById('busyDate').value;
            var time = document.getElementById('busyTime').value;
            fetch('./admin/get_staff_busy_status.php?date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time))
                .then(r => r.json())
                .then(busy => {
                    document.querySelectorAll('.staff-busy-badge').forEach(function(badge) {
                        var staffId = badge.getAttribute('data-staff-id');
                        if (busy[staffId] && busy[staffId] > 0) {
                            badge.classList.remove('badge-available');
                            badge.classList.add('badge-busy');
                            badge.textContent = 'Busy';
                        } else {
                            badge.classList.remove('badge-busy');
                            badge.classList.add('badge-available');
                            badge.textContent = 'Available';
                        }
                    });
                })
                .catch(function(e) {
                    // Optionally log error
                });
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Manual Set Busy/Set Available (AJAX)
            document.querySelectorAll('.toggle-availability-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var staffId = this.getAttribute('data-staff-id');
                    var isAvailable = this.getAttribute('data-available');
                    var button = this;
                    fetch('?toggle=' + encodeURIComponent(staffId) + '&ajax=1')
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                            // Update badge
                            var badge = document.querySelector('.staff-busy-badge[data-staff-id="' + staffId + '"]');
                            if (badge) {
                                if (data.is_available == 1) {
                                    badge.classList.remove('badge-busy');
                                    badge.classList.add('badge-available');
                                    badge.textContent = 'Available';
                                } else {
                                    badge.classList.remove('badge-available');
                                    badge.classList.add('badge-busy');
                                    badge.textContent = 'Busy';
                                }
                            }
                            // Update button
                            if (data.is_available == 1) {
                                button.classList.remove('btn-outline-success');
                                button.classList.add('btn-outline-secondary');
                                button.innerHTML = '<i class="bi bi-x-circle me-1"></i> Set Busy';
                                button.setAttribute('data-available', '1');
                            } else {
                                button.classList.remove('btn-outline-secondary');
                                button.classList.add('btn-outline-success');
                                button.innerHTML = '<i class="bi bi-check-circle me-1"></i> Set Available';
                                button.setAttribute('data-available', '0');
                            }
                        });
                });
            });
        });
        document.getElementById('busyDate').addEventListener('change', updateStaffBusyBadges);
        document.getElementById('busyTime').addEventListener('change', updateStaffBusyBadges);
    </script>
</body>
</html>