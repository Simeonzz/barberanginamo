<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user name
$userQuery = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$userName = $userData['fullname'] ?? 'User';

// Fetch user bookings with related data
$query = "
    SELECT 
        b.id, 
        b.booking_date, 
        b.booking_time, 
        b.status, 
        b.payment_status, 
        s.price, 
        s.name AS service_name, 
        st.pseudonym AS staff_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN staff st ON b.staff_id = st.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all services for edit modal
$servicesQuery = "SELECT id, name FROM services WHERE is_active=1 ORDER BY name";
$servicesResult = $conn->query($servicesQuery);
$allServices = [];
while ($row = $servicesResult->fetch_assoc()) {
    $allServices[] = $row;
}

// Fetch all staff for edit modal
$staffQuery = "SELECT id, pseudonym FROM staff WHERE is_available=1 ORDER BY pseudonym";
$staffResult = $conn->query($staffQuery);
$allStaff = [];
while ($row = $staffResult->fetch_assoc()) {
    $allStaff[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Barberang Ina Mo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --gold: #d4af37;
            --light-gold: rgba(212, 175, 55, 0.1);
            --dark-gold: #b8860b;
            --light-bg: #f9f9f9;
            --text-dark: #333;
            --text-light: #fff;
            --muted: #666;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }
        
        /* NAVBAR STYLES - Same as navbar.php */
        .dashboard-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(26, 26, 26, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            height: 80px;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            gap: 12px;
        }
        
        .nav-logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: none;
            border-radius: 50%;
            box-shadow: none;
        }
        
        .nav-brand-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #d4af37;
            letter-spacing: 1px;
            white-space: nowrap;
            font-weight: 600;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 40px;
            margin: 0;
            padding: 0;
            justify-content: center;
            flex: 1;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            text-decoration: none;
            color: #c4c4c4;
            font-weight: 500;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 0;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: #d4af37;
        }
        
        .nav-link.active {
            color: #d4af37;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 8px;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
        }
        
        .nav-auth-btn {
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-logout {
            background: transparent;
            color: #c4c4c4;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        .btn-logout:hover {
            border-color: #d4af37;
            color: #d4af37;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #c4c4c4;
            padding: 5px;
        }
        
        @media (max-width: 992px) {
            .nav-menu {
                gap: 20px;
            }
            
            .nav-brand-text {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-navbar {
                height: 70px;
                padding: 0 15px;
            }
            
            .nav-left {
                gap: 15px;
            }
            
            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                background: rgba(26, 26, 26, 0.98);
                backdrop-filter: blur(10px);
                flex-direction: column;
                align-items: center;
                padding: 40px 0;
                gap: 30px;
                transition: left 0.3s ease;
                z-index: 999;
            }
            
            .nav-menu.active {
                left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-brand-text {
                font-size: 1.1rem;
            }
            
            .nav-right {
                display: none;
            }
        }
        
        /* DASHBOARD CONTENT STYLES */
        body {
            padding-top: 80px;
        }
        
        .account-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .page-title {
            font-size: 2.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        
        .page-subtitle {
            color: var(--muted);
            font-size: 1.1rem;
        }
        
        .welcome-message {
            font-size: 1.2rem;
            color: var(--gold);
            margin-bottom: 20px;
        }
        
        .bookings-section {
            margin-bottom: 60px;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gold);
        }
        
        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--gold);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .booking-service {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .booking-stylist {
            color: var(--muted);
            font-size: 0.95rem;
        }
        
        .booking-date {
            text-align: right;
        }
        
        .date-day {
            font-size: 1.8rem;
            color: var(--gold);
            font-weight: 700;
            line-height: 1;
        }
        
        .date-month {
            color: var(--muted);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item h4 {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-item p {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin: 0;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            background: var(--light-gold);
            color: var(--dark-gold);
        }
        
        .status-confirmed {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .payment-badge {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }
        
        .payment-paid {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .payment-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .no-bookings {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #eee;
        }
        
        .no-bookings-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-bookings h3 {
            color: var(--muted);
            margin-bottom: 10px;
        }
        
        .no-bookings p {
            color: #999;
            margin-bottom: 30px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-edit, .btn-cancel, .btn-service-staff {
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-edit {
            background: var(--gold);
            color: white;
        }
        
        .btn-edit:hover {
            background: var(--dark-gold);
        }
        
        .btn-cancel {
            background: transparent;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-cancel:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        .btn-service-staff {
            background: var(--gold);
            color: white;
            opacity: 0.9;
        }
        
        .btn-service-staff:hover {
            opacity: 1;
            background: var(--dark-gold);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #888;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal h4 {
            margin-bottom: 20px;
            color: var(--gold);
            font-size: 1.5rem;
        }
        
        .modal .form-group {
            margin-bottom: 20px;
        }
        
        .modal .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .modal .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .modal .form-control:focus {
            outline: none;
            border-color: var(--gold);
        }
        
        .modal select[multiple] {
            height: 150px;
        }
        
        .modal-error {
            color: #dc2626;
            margin-bottom: 15px;
            display: none;
            font-size: 0.9rem;
        }
        
        .modal .btn {
            width: 100%;
            padding: 12px;
            background: var(--gold);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .modal .btn:hover {
            background: var(--dark-gold);
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .account-container {
                padding: 20px;
            }
            
            .page-title {
                font-size: 2.2rem;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .booking-date {
                text-align: left;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                margin: 20px auto;
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR WITHOUT GUEST WELCOME -->
    <nav class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php#home" class="nav-brand">
                    <img src="assets/images/logos/logo.png" alt="Barberang Ina Mo Logo" class="nav-logo-img" />
                    <div class="nav-brand-text">Barberang Ina Mo</div>
                </a>
            </div>
            
            <ul class="nav-menu" id="navMenu">
                <li class="nav-item"><a href="index.php#home" class="nav-link">HOME</a></li>
                <li class="nav-item"><a href="index.php#about" class="nav-link">ABOUT US</a></li>
                <li class="nav-item"><a href="index.php#services" class="nav-link">SERVICES</a></li>
                <li class="nav-item"><a href="index.php#products" class="nav-link">PRODUCTS</a></li>
                <li class="nav-item"><a href="index.php#appointment" class="nav-link">APPOINTMENT</a></li>
                <li class="nav-item"><a href="dashboard.php" class="nav-link active">MY ACCOUNT</a></li>
            </ul>
            
            <div class="nav-right">
                <a href="logout.php" class="nav-auth-btn btn-logout">LOGOUT</a>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
        </div>
    </nav>
    
    <!-- DASHBOARD CONTENT -->
    <div class="account-container">
        <!-- Page Header with Welcome -->
        <div class="page-header">
            <h1 class="page-title">My Account</h1>
            <div class="welcome-message">Welcome back, <?php echo htmlspecialchars($userName); ?>!</div>
            <p class="page-subtitle">Manage your appointments and profile</p>
        </div>
        
        <!-- Bookings Section -->
        <div class="bookings-section">
            <h2 class="section-title">My Appointments</h2>
            
            <?php if ($result->num_rows === 0): ?>
                <div class="no-bookings">
                    <div class="no-bookings-icon">📅</div>
                    <h3>No Appointments Yet</h3>
                    <p>You haven't made any bookings yet. Schedule your first appointment to get started!</p>
                    <a href="booking.php" class="btn-edit" style="text-decoration: none; display: inline-block;">Book Now</a>
                </div>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="booking-card" id="booking-<?php echo $row['id']; ?>">
                        <div class="booking-header">
                            <div>
                                <h3 class="booking-service"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                                <p class="booking-stylist">
                                    <?php 
                                    if (!empty($row['staff_name'])) {
                                        echo 'With: ' . htmlspecialchars($row['staff_name']);
                                    } else {
                                        echo 'Staff: Any available';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="booking-date">
                                <div class="date-day"><?php echo date('d', strtotime($row['booking_date'])); ?></div>
                                <div class="date-month"><?php echo date('M', strtotime($row['booking_date'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <h4>Date & Time</h4>
                                <p>
                                    <?php echo date('F j, Y', strtotime($row['booking_date'])); ?><br>
                                    <?php echo date('g:i A', strtotime($row['booking_time'])); ?>
                                </p>
                            </div>
                            
                            <div class="detail-item">
                                <h4>Status</h4>
                                <span class="badge status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <h4>Payment</h4>
                                <span class="badge payment-badge payment-<?php echo strtolower($row['payment_status']); ?>">
                                    <?php echo ucfirst($row['payment_status']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <h4>Amount</h4>
                                <p>₱<?php echo number_format($row['price'], 2); ?></p>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="openRescheduleModal(<?php echo $row['id']; ?>, '<?php echo $row['booking_date']; ?>', '<?php echo $row['booking_time']; ?>')">
                                Reschedule
                            </button>
                            <button class="btn-cancel" onclick="cancelBooking(<?php echo $row['id']; ?>)">
                                Cancel Booking
                            </button>
                            <button class="btn-service-staff" onclick="openEditServiceStaffModal(<?php echo $row['id']; ?>)">
                                Edit Services/Staff
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <!-- Additional Sections -->
        <div class="row" style="margin-top: 40px;">
            <div class="col-md-6 mb-4">
                <div class="booking-card">
                    <h4 style="margin-bottom: 15px; color: var(--primary);">Account Settings</h4>
                    <p style="color: var(--muted); margin-bottom: 20px;">Update your profile information and preferences</p>
                    <button class="btn-edit" onclick="editProfile()">Edit Profile</button>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="booking-card">
                    <h4 style="margin-bottom: 15px; color: var(--primary);">Need Help?</h4>
                    <p style="color: var(--muted); margin-bottom: 20px;">Contact us for any questions about your bookings</p>
                    <a href="index.php#contact" class="btn-edit" style="text-decoration: none; display: inline-block;">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- RESCHEDULE MODAL -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeRescheduleModal()">&times;</button>
            <h4>Reschedule Booking</h4>
            <form id="rescheduleForm">
                <input type="hidden" id="rescheduleBookingId" name="booking_id">
                
                <div class="form-group">
                    <label for="new_date" class="form-label">New Date</label>
                    <input type="date" id="new_date" name="new_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="new_time" class="form-label">New Time</label>
                    <input type="time" id="new_time" name="new_time" class="form-control" required>
                </div>
                
                <div id="rescheduleError" class="modal-error"></div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
    
    <!-- EDIT SERVICES/STAFF MODAL -->
    <div id="editServiceStaffModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditServiceStaffModal()">&times;</button>
            <h4>Edit Services & Staff</h4>
            <form id="editServiceStaffForm">
                <input type="hidden" id="editBookingId" name="booking_id">
                
             <div class="form-group">
    <label class="form-label">Select Services (Check all that apply)</label>
    <div id="servicesCheckboxContainer" style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f9f9f9;">
        <?php foreach ($allServices as $service): ?>
            <label style="display: flex; align-items: center; gap: 10px; padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">
                <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" class="service-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                <span style="color: #333; font-size: 0.95rem;"><?php echo htmlspecialchars($service['name']); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <small style="color: #666; display: block; margin-top: 8px;">✓ Check all services you want for this appointment</small>
</div>
                <div class="form-group">
                    <label for="edit_staff" class="form-label">Select Staff</label>
                    <select id="edit_staff" name="staff_id" class="form-control" required>
                        <option value="">Any Available Staff</option>
                        <?php foreach ($allStaff as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['pseudonym']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="editServiceStaffError" class="modal-error"></div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // EDIT SERVICES/STAFF MODAL FUNCTIONS - REPLACE YOUR EXISTING FUNCTION WITH THIS
function openEditServiceStaffModal(bookingId) {
    document.getElementById('editBookingId').value = bookingId;
    document.getElementById('editServiceStaffError').style.display = 'none';
    
    // First, uncheck all checkboxes
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = false;
    });
    
    // Remove existing price display if any
    const oldPriceDisplay = document.getElementById('selectedServicesPrice');
    if (oldPriceDisplay) oldPriceDisplay.remove();
    
    // Fetch current booking details
    fetch('get_booking_services.php?booking_id=' + bookingId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store price map globally for this modal session
                window.servicePriceMap = data.price_map || {};
                
                // Check the checkboxes for selected services
                if (data.booking_services && data.booking_services.length > 0) {
                    data.booking_services.forEach(serviceId => {
                        const checkbox = document.querySelector(`.service-checkbox[value="${serviceId}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                
                // Set selected staff
                const staffSelect = document.getElementById('edit_staff');
                if (data.booking_staff) {
                    staffSelect.value = data.booking_staff;
                } else {
                    staffSelect.value = '';
                }
                
                // Update total price display
                updateTotalPrice();
                
                document.getElementById('editServiceStaffModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                alert('Failed to load booking details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading booking details. Check console for details.');
        });
}

// Function to update total price based on selected checkboxes
function updateTotalPrice() {
    const checkboxes = document.querySelectorAll('.service-checkbox:checked');
    let total = 0;
    
    checkboxes.forEach(cb => {
        const serviceId = cb.value;
        if (window.servicePriceMap && window.servicePriceMap[serviceId]) {
            total += parseFloat(window.servicePriceMap[serviceId]);
        }
    });
    
    // Create or update price display
    let priceDisplay = document.getElementById('selectedServicesPrice');
    if (!priceDisplay) {
        const container = document.querySelector('#editServiceStaffModal .modal-content');
        if (container) {
            priceDisplay = document.createElement('div');
            priceDisplay.id = 'selectedServicesPrice';
            priceDisplay.style.marginTop = '20px';
            priceDisplay.style.marginBottom = '20px';
            priceDisplay.style.padding = '15px';
            priceDisplay.style.backgroundColor = '#f9f9f9';
            priceDisplay.style.borderRadius = '8px';
            priceDisplay.style.border = '1px solid #d4af37';
            
            // Insert before the staff selection
            const staffGroup = document.querySelector('#editServiceStaffModal .form-group:last-of-type');
            if (staffGroup) {
                staffGroup.parentNode.insertBefore(priceDisplay, staffGroup);
            }
        }
    }
    
    if (priceDisplay) {
        priceDisplay.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 600; color: #333;">Total Amount:</span>
                <span style="font-size: 1.3rem; font-weight: 700; color: #d4af37;">₱${total.toFixed(2)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: #333;">Down Payment (50%):</span>
                <span style="font-size: 1.2rem; font-weight: 600; color: #d4af37;">₱${(total * 0.5).toFixed(2)}</span>
            </div>
        `;
    }
}

// Add event listeners to checkboxes when modal opens
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('service-checkbox')) {
        updateTotalPrice();
    }
});

// Update the form submission to show selected services count
const originalSubmit = document.getElementById('editServiceStaffForm').onsubmit;
document.getElementById('editServiceStaffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const checkedCount = document.querySelectorAll('.service-checkbox:checked').length;
    if (checkedCount === 0) {
        document.getElementById('editServiceStaffError').textContent = 'Please select at least one service.';
        document.getElementById('editServiceStaffError').style.display = 'block';
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('update_booking_services.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking updated successfully with ' + checkedCount + ' service(s)!');
            closeEditServiceStaffModal();
            location.reload();
        } else {
            const errorDiv = document.getElementById('editServiceStaffError');
            errorDiv.textContent = data.message || 'Failed to update booking.';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        const errorDiv = document.getElementById('editServiceStaffError');
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
        console.error('Error:', error);
    });
});
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');
        
        if (mobileMenuBtn && navMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                mobileMenuBtn.innerHTML = navMenu.classList.contains('active') ? '✕' : '☰';
            });

            document.addEventListener('click', function(event) {
                if (!navMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                    navMenu.classList.remove('active');
                    mobileMenuBtn.innerHTML = '☰';
                }
            });

            navMenu.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    navMenu.classList.remove('active');
                    mobileMenuBtn.innerHTML = '☰';
                });
            });
        }
        
        // RESCHEDULE MODAL FUNCTIONS
        function openRescheduleModal(bookingId, date, time) {
            document.getElementById('rescheduleBookingId').value = bookingId;
            document.getElementById('new_date').value = date;
            document.getElementById('new_time').value = time;
            document.getElementById('rescheduleError').style.display = 'none';
            document.getElementById('rescheduleModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Reschedule form submission
        document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('reschedule_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking rescheduled successfully!');
                    closeRescheduleModal();
                    location.reload();
                } else {
                    const errorDiv = document.getElementById('rescheduleError');
                    errorDiv.textContent = data.message || 'Failed to reschedule booking.';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                const errorDiv = document.getElementById('rescheduleError');
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
                console.error('Error:', error);
            });
        });
        
     // EDIT SERVICES/STAFF MODAL FUNCTIONS
function openEditServiceStaffModal(bookingId) {
    document.getElementById('editBookingId').value = bookingId;
    document.getElementById('editServiceStaffError').style.display = 'none';
    
    // First, uncheck all checkboxes
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = false;
    });
    
    // Fetch current booking details - CHANGED TO get_booking_services.php
    fetch('get_booking_services.php?booking_id=' + bookingId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check the checkboxes for selected services
                if (data.booking_services && data.booking_services.length > 0) {
                    data.booking_services.forEach(serviceId => {
                        const checkbox = document.querySelector(`.service-checkbox[value="${serviceId}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                
                // Set selected staff
                const staffSelect = document.getElementById('edit_staff');
                if (data.booking_staff) {
                    staffSelect.value = data.booking_staff;
                } else {
                    staffSelect.value = '';
                }
                
                document.getElementById('editServiceStaffModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                alert('Failed to load booking details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading booking details. Check console for details.');
        });
}
        function closeEditServiceStaffModal() {
            document.getElementById('editServiceStaffModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Edit services/staff form submission
        document.getElementById('editServiceStaffForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_booking_services.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking updated successfully!');
                    closeEditServiceStaffModal();
                    location.reload();
                } else {
                    const errorDiv = document.getElementById('editServiceStaffError');
                    errorDiv.textContent = data.message || 'Failed to update booking.';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                const errorDiv = document.getElementById('editServiceStaffError');
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
                console.error('Error:', error);
            });
        });
        
function calculateTotalPrice() {
    const checkboxes = document.querySelectorAll('.service-checkbox:checked');
    let total = 0;
    
    // You'll need to pass the prices from PHP to JavaScript
    // For now, we'll fetch prices via AJAX or store them in data attributes
    
    fetch('get_service_prices.php')
        .then(response => response.json())
        .then(data => {
            checkboxes.forEach(cb => {
                const serviceId = cb.value;
                if (data.prices[serviceId]) {
                    total += parseFloat(data.prices[serviceId]);
                }
            });
            console.log('Total price: ₱' + total.toFixed(2));
            // You can display this somewhere in the UI
        });
}

// Add event listeners to checkboxes
document.querySelectorAll('.service-checkbox').forEach(cb => {
    cb.addEventListener('change', calculateTotalPrice);
});s
        // Cancel booking function
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                
                fetch('cancel_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
            }
        }
        
        function editProfile() {
            alert('Profile editing feature coming soon!');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const rescheduleModal = document.getElementById('rescheduleModal');
            const editModal = document.getElementById('editServiceStaffModal');
            
            if (event.target === rescheduleModal) {
                closeRescheduleModal();
            }
            if (event.target === editModal) {
                closeEditServiceStaffModal();
            }
        }
        
        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRescheduleModal();
                closeEditServiceStaffModal();
            }
        });
        
        // Update booking status colors on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-badge').forEach(badge => {
                const status = badge.textContent.toLowerCase().trim();
                badge.classList.add('status-' + status);
            });
            
            document.querySelectorAll('.payment-badge').forEach(badge => {
                const status = badge.textContent.toLowerCase().trim();
                badge.classList.add('payment-' + status);
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Rating Modal -->
<div id="ratingModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:white; border-radius:15px; max-width:450px; margin:100px auto; padding:30px; position:relative;">
        <span class="close" onclick="closeRatingModal()" style="position:absolute; top:15px; right:20px; font-size:28px; cursor:pointer;">&times;</span>
        <h3 style="text-align:center; color:#d4af37; margin-bottom:20px;">Rate Your Experience</h3>
        
        <div id="ratingBookingInfo" style="text-align:center; margin-bottom:20px; padding:10px; background:#f5f5f5; border-radius:8px;">
            <p style="margin:0; color:#666;">Booking #<span id="ratingBookingId"></span></p>
            <p style="margin:5px 0 0; font-weight:600; color:#333;" id="ratingStaffName"></p>
        </div>
        
        <form id="ratingForm" onsubmit="submitRating(event)">
            <input type="hidden" name="booking_id" id="ratingBookingIdInput">
            <input type="hidden" name="staff_id" id="ratingStaffIdInput">
            
            <div style="text-align:center; margin-bottom:20px;">
                <p style="margin-bottom:10px; color:#333; font-weight:600;">Your Rating</p>
                <div class="star-rating" style="font-size: 40px; cursor: pointer;">
                    <span class="star" data-rating="1" style="color: #ddd;" onmouseover="highlightStars(1)" onmouseout="resetStars()" onclick="setRating(1)">★</span>
                    <span class="star" data-rating="2" style="color: #ddd;" onmouseover="highlightStars(2)" onmouseout="resetStars()" onclick="setRating(2)">★</span>
                    <span class="star" data-rating="3" style="color: #ddd;" onmouseover="highlightStars(3)" onmouseout="resetStars()" onclick="setRating(3)">★</span>
                    <span class="star" data-rating="4" style="color: #ddd;" onmouseover="highlightStars(4)" onmouseout="resetStars()" onclick="setRating(4)">★</span>
                    <span class="star" data-rating="5" style="color: #ddd;" onmouseover="highlightStars(5)" onmouseout="resetStars()" onclick="setRating(5)">★</span>
                </div>
                <input type="hidden" name="rating" id="selectedRating" value="0">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; color:#333; font-weight:600;">Comment (Optional)</label>
                <textarea name="comment" id="ratingComment" rows="4" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-family:inherit;"></textarea>
            </div>
            
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeRatingModal()" style="flex:1; padding:12px; background:#f0f0f0; border:none; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="flex:1; padding:12px; background:#d4af37; color:white; border:none; border-radius:8px; cursor:pointer;">Submit Rating</button>
            </div>
        </form>
        
        <div id="ratingMessage" style="margin-top:15px; text-align:center; display:none;"></div>
    </div>
</div>

<script>
// Rating Modal Functions
let currentRating = 0;
let selectedStaffId = 0;
let selectedStaffName = '';

function openRatingModal(bookingId, staffId, staffName) {
    document.getElementById('ratingBookingId').textContent = bookingId;
    document.getElementById('ratingBookingIdInput').value = bookingId;
    document.getElementById('ratingStaffIdInput').value = staffId;
    document.getElementById('ratingStaffName').textContent = 'Staff: ' + staffName;
    
    selectedStaffId = staffId;
    selectedStaffName = staffName;
    
    // Reset stars
    currentRating = 0;
    document.getElementById('selectedRating').value = 0;
    resetStars();
    document.getElementById('ratingComment').value = '';
    document.getElementById('ratingMessage').style.display = 'none';
    
    document.getElementById('ratingModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
    document.body.style.overflow = '';
}

function highlightStars(rating) {
    const stars = document.querySelectorAll('.star');
    for (let i = 0; i < stars.length; i++) {
        if (i < rating) {
            stars[i].style.color = '#d4af37';
        } else {
            stars[i].style.color = '#ddd';
        }
    }
}

function resetStars() {
    const stars = document.querySelectorAll('.star');
    for (let i = 0; i < stars.length; i++) {
        if (i < currentRating) {
            stars[i].style.color = '#d4af37';
        } else {
            stars[i].style.color = '#ddd';
        }
    }
}

function setRating(rating) {
    currentRating = rating;
    document.getElementById('selectedRating').value = rating;
    highlightStars(rating);
}

function submitRating(event) {
    event.preventDefault();
    
    if (currentRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const formData = new FormData(document.getElementById('ratingForm'));
    
    fetch('submit_rating.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('ratingMessage');
        messageDiv.style.display = 'block';
        
        if (data.success) {
            messageDiv.style.color = '#28a745';
            messageDiv.innerHTML = '✓ ' + data.message;
            setTimeout(() => {
                closeRatingModal();
                location.reload(); // Refresh to update UI
            }, 2000);
        } else {
            messageDiv.style.color = '#dc3545';
            messageDiv.innerHTML = '✗ ' + data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Add this to your existing booking cards in dashboard.php
// Find where you display completed bookings and add this button
function checkForCompletedBookings() {
    // This will be called when page loads to check if any completed bookings need rating
    const completedBookings = document.querySelectorAll('.booking-card.completed');
    completedBookings.forEach(booking => {
        const bookingId = booking.dataset.bookingId;
        const staffId = booking.dataset.staffId;
        const staffName = booking.dataset.staffName;
        const isRated = booking.dataset.rated === 'true';
        
        if (!isRated && staffId) {
            // Show rating prompt after 2 seconds
            setTimeout(() => {
                if (confirm('Would you like to rate your experience with ' + staffName + '?')) {
                    openRatingModal(bookingId, staffId, staffName);
                }
            }, 2000);
        }
    });
}

// Call this when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    checkForCompletedBookings();
});
</script>

<style>
/* Rating Stars */
.star-rating {
    display: inline-block;
}

.star {
    transition: color 0.2s;
}

.star:hover {
    transform: scale(1.1);
}

/* Make sure modal appears above everything */
#ratingModal {
    z-index: 10000;
}
</style>
</body>
</html>