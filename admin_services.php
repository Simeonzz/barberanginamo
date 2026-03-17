<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

// --- Database Connection ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barberanginamodb"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Setup services table if it doesn't exist
function setupServicesTable($conn) {
    // Check if services table exists
    $result = $conn->query("SHOW TABLES LIKE 'services'");
    if ($result->num_rows == 0) {
        // Create services table with correct column names
        $conn->query("CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(80) DEFAULT 'Uncategorized',
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            duration INT NOT NULL,
            image_url VARCHAR(500),
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add sample services (only if table is empty)
        $countResult = $conn->query("SELECT COUNT(*) as count FROM services");
        $count = $countResult->fetch_assoc()['count'];
        
        if ($count == 0) {
            $sampleServices = [
                ['name' => 'Classic Haircut', 'description' => 'Traditional haircut with scissor work', 'price' => 250.00, 'duration' => 30],
                ['name' => 'Premium Fade', 'description' => 'Modern fade haircut with detailed styling', 'price' => 350.00, 'duration' => 45],
                ['name' => 'Beard Trim & Shape', 'description' => 'Professional beard grooming and shaping', 'price' => 150.00, 'duration' => 20],
                ['name' => 'Haircut & Beard Combo', 'description' => 'Complete grooming package', 'price' => 400.00, 'duration' => 60],
                ['name' => 'Kids Haircut', 'description' => 'Special haircut for children', 'price' => 200.00, 'duration' => 25],
                ['name' => 'Senior Citizen Discount', 'description' => 'Special rate for senior citizens', 'price' => 180.00, 'duration' => 30]
            ];
            
            foreach ($sampleServices as $service) {
                $stmt = $conn->prepare("INSERT INTO services (name, category, description, price, duration, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $cat = 'Haircut';
                $stmt->bind_param("sssdi", $service['name'], $cat, $service['description'], $service['price'], $service['duration']);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        // Table exists, check for missing columns and add them
        $columns = $conn->query("SHOW COLUMNS FROM services");
        $columnNames = [];
        while ($col = $columns->fetch_assoc()) {
            $columnNames[] = $col['Field'];
        }
        
        // Add missing columns if they don't exist
        if (!in_array('description', $columnNames)) {
            $conn->query("ALTER TABLE services ADD COLUMN description TEXT AFTER name");
        }
        if (!in_array('category', $columnNames)) {
            $conn->query("ALTER TABLE services ADD COLUMN category VARCHAR(80) DEFAULT 'Uncategorized' AFTER name");
        }
        if (!in_array('duration', $columnNames)) {
            $conn->query("ALTER TABLE services ADD COLUMN duration INT AFTER price");
        }
        if (!in_array('image_url', $columnNames)) {
            $conn->query("ALTER TABLE services ADD COLUMN image_url VARCHAR(500) AFTER duration");
        }
        if (!in_array('is_active', $columnNames)) {
            $conn->query("ALTER TABLE services ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER image_url");
        }
    }
}

setupServicesTable($conn);

// Handle file upload
function uploadImage($file) {
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    $targetDir = "uploads/services/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate unique filename
    $fileName = time() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['error' => 'File is not an image.'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['error' => 'File is too large. Max 5MB.'];
    }
    
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['error' => 'Only JPG, JPEG, PNG, GIF & WEBP files are allowed.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => $targetFile];
    } else {
        return ['error' => 'Failed to upload image.'];
    }
}

// --- Handle Add or Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $id = $_POST['service_id'] ?? null;
    $name = trim($_POST['name']);
    $category = trim($_POST['category'] ?? 'Uncategorized');
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    
    $image_url = null;
    $error = null;
    
    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']);
        if (isset($uploadResult['success'])) {
            $image_url = $uploadResult['success'];
        } elseif (isset($uploadResult['error'])) {
            $error = $uploadResult['error'];
        }
    } elseif (!empty($_POST['existing_image'])) {
        // Keep existing image if no new image uploaded
        $image_url = $_POST['existing_image'];
    }
    
    if (!$error) {
        if ($id) {
            // Update service
            if ($image_url) {
                $stmt = $conn->prepare("UPDATE services SET name=?, category=?, description=?, price=?, duration=?, image_url=? WHERE id=?");
                $stmt->bind_param("sssdisi", $name, $category, $description, $price, $duration, $image_url, $id);
            } else {
                $stmt = $conn->prepare("UPDATE services SET name=?, category=?, description=?, price=?, duration=? WHERE id=?");
                $stmt->bind_param("sssiii", $name, $category, $description, $price, $duration, $id);
            }
            $message = "Service updated successfully.";
        } else {
            // Insert new service
            if ($image_url) {
                $stmt = $conn->prepare("INSERT INTO services (name, category, description, price, duration, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssdis", $name, $category, $description, $price, $duration, $image_url);
            } else {
                $stmt = $conn->prepare("INSERT INTO services (name, category, description, price, duration, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssdi", $name, $category, $description, $price, $duration);
            }
            $message = "Service added successfully.";
        }
        
        if ($stmt->execute()) {
            header("Location: admin_services.php?msg=" . urlencode($message));
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
    
    if ($error) {
        $_SESSION['error'] = $error;
        header("Location: admin_services.php");
        exit();
    }
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM services WHERE id=$id");
    header("Location: admin_services.php?msg=" . urlencode("Service deleted successfully."));
    exit();
}

// --- Handle Toggle Active/Inactive ---
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE services SET is_active = NOT is_active WHERE id=$id");
    header("Location: admin_services.php?msg=" . urlencode("Service status updated."));
    exit();
}

// --- Fetch All Services ---
$result = $conn->query("SELECT * FROM services ORDER BY name ASC");
$services = [];
if ($result) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/admin-theme.css">
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
        
        /* Service Card */
        .service-img {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .service-placeholder {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .service-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        .btn-edit {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit:hover {
            background: white;
            transform: scale(1.1);
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
                <h1>Manage Services</h1>
                <p>Add, edit, or remove salon services</p>
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
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Manage Services</h2>
                    <p class="text-muted">Add, edit, or remove salon services</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="clearForm()">
                    <i class="bi bi-plus-circle me-2"></i> Add Service
                </button>
            </div>

            <?php if (empty($services)): ?>
                <div class="card p-5 text-center">
                    <div class="mb-4">
                        <i class="bi bi-scissors display-1 text-muted"></i>
                    </div>
                    <h5 class="mb-3">No services yet</h5>
                    <p class="text-muted mb-4">Add your first service to get started</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
                        <i class="bi bi-plus-circle me-2"></i> Add First Service
                    </button>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $service): 
                        // Safely get values with defaults
                        $image_url = isset($service['image_url']) ? $service['image_url'] : null;
                        $is_active = isset($service['is_active']) ? $service['is_active'] : 1;
                        $id = isset($service['id']) ? $service['id'] : 0;
                        $name = isset($service['name']) ? $service['name'] : 'Unknown';
                        $description = isset($service['description']) ? $service['description'] : 'No description available';
                        $price = isset($service['price']) ? $service['price'] : 0;
                        $duration = isset($service['duration']) ? $service['duration'] : 0;
                    ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100 position-relative">
                                <div class="service-actions">
                                    <button class="btn-edit" onclick="editService(<?= htmlspecialchars(json_encode($service), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </button>
                                </div>
                                
                                <?php if (!empty($image_url) && file_exists($image_url)): ?>
                                    <img src="<?= htmlspecialchars($image_url) ?>" class="service-img">
                                <?php else: ?>
                                    <div class="service-placeholder">
                                        <i class="bi bi-scissors display-3"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($name) ?>
                                        <span class="badge <?= $is_active ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </h5>
                                    <p class="card-text text-muted small mb-2"><?= nl2br(htmlspecialchars($description)) ?></p>
                                    <div class="small text-muted mb-3"><strong>Category:</strong> <?= htmlspecialchars($service['category'] ?? 'Uncategorized') ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-1"><strong>Price:</strong> ₱<?= number_format($price, 2) ?></p>
                                            <p class="mb-0"><strong>Duration:</strong> <?= $duration ?> minutes</p>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editService(<?= htmlspecialchars(json_encode($service), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <a href="?toggle=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                                                <?= $is_active ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <a href="?delete=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="serviceForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="service_id">
                        <input type="hidden" name="existing_image" id="existing_image">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Service Name *</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <input type="text" name="category" id="category" class="form-control" placeholder="e.g. Haircut, Hair Color, Nails" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Price (₱) *</label>
                                        <input type="number" name="price" id="price" step="0.01" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Duration (minutes) *</label>
                                        <input type="number" name="duration" id="duration" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Service Image (Optional)</label>
                                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                                    <div class="form-text">Max 5MB. JPG, PNG, GIF, WEBP</div>
                                </div>
                                
                                <div class="image-preview mt-3">
                                    <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                    <div id="noImage" class="text-center text-muted p-4 border rounded">
                                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                                        <p class="mt-2">No image selected</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_service" class="btn btn-primary">Save Service</button>
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
        const serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
        
        // Service modal functions
        function clearForm() {
            document.getElementById('modalTitle').textContent = 'Add Service';
            document.getElementById('service_id').value = '';
            document.getElementById('existing_image').value = '';
            document.getElementById('name').value = '';
            document.getElementById('category').value = '';
            document.getElementById('description').value = '';
            document.getElementById('price').value = '';
            document.getElementById('duration').value = '';
            document.getElementById('image').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('noImage').style.display = 'block';
        }
        
        function editService(service) {
            console.log('Editing service:', service); // Debug log
            
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('service_id').value = service.id || '';
            document.getElementById('existing_image').value = service.image_url || '';
            document.getElementById('name').value = service.name || '';
            document.getElementById('category').value = service.category || '';
            document.getElementById('description').value = service.description || '';
            document.getElementById('price').value = service.price || '';
            document.getElementById('duration').value = service.duration || '';
            
            if (service.image_url && service.image_url.trim() !== '') {
                document.getElementById('imagePreview').src = service.image_url;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('noImage').style.display = 'none';
                console.log('Image URL:', service.image_url); // Debug log
            } else {
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('noImage').style.display = 'block';
            }
            
            // Show the modal
            serviceModal.show();
        }
        
        // Image preview
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('noImage').style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('noImage').style.display = 'block';
            }
        });
        
        // Form validation
        document.getElementById('serviceForm').addEventListener('submit', function(event) {
            const name = document.getElementById('name').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = document.getElementById('price').value;
            const duration = document.getElementById('duration').value;
            
            if (!name || !description || !price || !duration) {
                event.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (parseFloat(price) <= 0) {
                event.preventDefault();
                alert('Price must be greater than 0.');
                return false;
            }
            
            if (parseInt(duration) <= 0) {
                event.preventDefault();
                alert('Duration must be greater than 0 minutes.');
                return false;
            }
            
            return true;
        });
        
        // Make edit buttons clickable
        document.addEventListener('DOMContentLoaded', function() {
            // Attach click event to all edit buttons
            const editButtons = document.querySelectorAll('.btn-outline-primary');
            editButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    // The onclick attribute should handle this
                });
            });
        });
    </script>
</body>
</html>
