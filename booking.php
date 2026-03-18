<?php
session_start();
include 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Fetch services
$services = [];
// Ensure category column exists (for older databases)
$colRes = $conn->query("SHOW COLUMNS FROM services LIKE 'category'");
if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE services ADD COLUMN category VARCHAR(80) DEFAULT 'Uncategorized' AFTER name");
}

// If duration_display column exists, fetch it too (for showing both text and minutes)
$durationDisplayCol = false;
$colRes2 = $conn->query("SHOW COLUMNS FROM services LIKE 'duration_display'");
if ($colRes2 && $colRes2->num_rows > 0) {
    $durationDisplayCol = true;
}

if ($durationDisplayCol) {
    $res = $conn->query("SELECT id, name, category, price, duration, duration_display FROM services WHERE is_active=1 ORDER BY category, name");
} else {
    $res = $conn->query("SELECT id, name, category, price, duration FROM services WHERE is_active=1 ORDER BY category, name");
}
while ($row = $res->fetch_assoc()) $services[] = $row;

// Fetch staff (include photo field if exists)
$staff = [];
$photoExists = false;
$colRes = $conn->query("SHOW COLUMNS FROM staff LIKE 'photo'");
if ($colRes && $colRes->num_rows > 0) {
    $photoExists = true;
}
if ($photoExists) {
    $res2 = $conn->query("SELECT id, pseudonym, role, specialty, photo, is_available FROM staff WHERE is_available=1 ORDER BY pseudonym");
} else {
    $res2 = $conn->query("SELECT id, pseudonym, role, specialty, is_available FROM staff WHERE is_available=1 ORDER BY pseudonym");
}
while ($row = $res2->fetch_assoc()) $staff[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking | Barberang Ina Mo</title>
    <link rel="stylesheet" href="style.php">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #000000;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }
        
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: clamp(16px, 4vw, 40px) 20px;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: clamp(24px, 5vw, 40px);
        }
        
        .booking-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            color: #FFD700;
            margin-bottom: 10px;
        }
        
        .booking-subtitle {
            color: #c4c4c4;
            font-size: clamp(0.95rem, 2.2vw, 1.1rem);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .booking-card {
            background: #1a1a1a;
            border-radius: 15px;
            padding: clamp(20px, 4vw, 40px);
            border: 1px solid rgba(255, 215, 0, 0.25);
            margin-bottom: 30px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.35);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: clamp(16px, 3vw, 30px);
            margin-bottom: clamp(18px, 3vw, 30px);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #FFD700;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .form-input, .form-select, .form-textarea, .form-file {
            width: 100%;
            padding: 12px 15px;
            background: #2a2a2a;
            border: 1px solid #FFD700;
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .form-input::placeholder, .form-textarea::placeholder {
            color: #9a9a9a;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus, .form-file:focus {
            outline: none;
            border-color: #ffd84d;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.12);
            background: #262626;
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .qr-section {
            background: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .qr-placeholder {
            width: 180px;
            height: 180px;
            background: #1a1a1a;
            margin: 0 auto 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD700;
            font-size: 0.9rem;
            border: 1px dashed #FFD700;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }
        
        .login-prompt {
            text-align: center;
            padding: 60px 20px;
        }

        .dropdown-multicheckbox label:hover {
            background: rgba(255, 215, 0, 0.08);
        }

        /* Category Pills */
        .category-strip {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .category-pill {
            padding: 10px 20px;
            background: #2a2a2a;
            color: #c4c4c4;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .category-pill:hover {
            background: #333;
            border-color: #FFD700;
            color: #FFD700;
        }

        .category-pill.active {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
            font-weight: 600;
        }

        /* Service List Container */
        .service-list-container {
            background: #2a2a2a;
            border: 1px solid #FFD700;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .service-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            cursor: pointer;
            background: #1a1a1a;
            border-radius: 6px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .service-option:last-child {
            margin-bottom: 0;
        }

        .service-option:hover {
            background: #262626;
            border-left: 3px solid #FFD700;
            padding-left: 10px;
        }

        .service-option input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .service-option span {
            color: #c4c4c4;
            font-size: 0.9rem;
            flex: 1;
        }

        .see-all-btn {
            background: #FFD700;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .see-all-btn:hover {
            background: #ffd84d;
            transform: translateY(-2px);
        }

        .hidden-services {
            display: none;
        }

        .hidden-services.show {
            display: block;
        }
        
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .booking-card {
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .booking-container {
                padding: 20px;
            }
            
            .booking-title {
                font-size: 2rem;
            }
            
            .booking-card {
                padding: 20px;
            }

            .booking-card .btn {
                width: 100%;
                text-align: center;
            }

            .category-strip {
                gap: 8px;
            }

            .category-pill {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="booking-container">
        <?php if (!empty($_SESSION['booking_errors'])): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach($_SESSION['booking_errors'] as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                </ul>
            </div>
            <?php unset($_SESSION['booking_errors']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['booking_success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['booking_success']); ?>
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>
        
        <?php if (!$isLoggedIn): ?>
            <div class="booking-card login-prompt">
                <h2 style="color: #FFD700; margin-bottom: 20px;">Login Required</h2>
                <p style="color: #c4c4c4; margin-bottom: 30px; font-size: 1.1rem;">
                    Please login to access our booking system and schedule your appointment.
                </p>
                <a href="auth.php" class="btn" style="padding: 15px 40px; font-size: 1.1rem;">Login Now</a>
            </div>
        <?php else: ?>
            <div class="booking-header">
                <h1 class="booking-title">Make a Booking</h1>
                <p class="booking-subtitle">Fill in your details and upload proof of 50% down payment (required).</p>
            </div>
            
            <div class="booking-card">
                <form action="submit_booking.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Service(s) *</label>
                            <?php
                                // Group services by category
                                $servicesByCategory = [];
                                foreach ($services as $s) {
                                    $cat = trim($s['category'] ?? 'Uncategorized');
                                    if ($cat === '') $cat = 'Uncategorized';
                                    if (!isset($servicesByCategory[$cat])) {
                                        $servicesByCategory[$cat] = [];
                                    }
                                    $servicesByCategory[$cat][] = $s;
                                }
                                
                                // Get non-uncategorized categories
                                $categories = [];
                                foreach ($servicesByCategory as $cat => $svcs) {
                                    if ($cat !== 'Uncategorized') {
                                        $categories[] = $cat;
                                    }
                                }
                                // Add Uncategorized at the end if it exists
                                if (isset($servicesByCategory['Uncategorized'])) {
                                    $categories[] = 'Uncategorized';
                                }
                            ?>
                            <div class="category-strip" id="serviceCategoryStrip">
                                <?php foreach ($categories as $i => $cat): ?>
                                    <button type="button" class="category-pill<?php echo $i === 0 ? ' active' : ''; ?>" data-category="<?php echo htmlspecialchars($cat); ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="service-list-container" id="serviceListContainer">
                                <?php 
                                    // Display first category services (first 3) + see all button
                                    $firstCategory = $categories[0] ?? 'Uncategorized';
                                    $firstCategoryServices = $servicesByCategory[$firstCategory] ?? [];
                                    $displayCount = 3;
                                    
                                    for ($j = 0; $j < count($firstCategoryServices); $j++):
                                        $s = $firstCategoryServices[$j];
                                        $cat = trim($s['category'] ?? 'Uncategorized');
                                        if ($cat === '') $cat = 'Uncategorized';
                                        $isHidden = $j >= $displayCount ? 'hidden-services' : '';
                                ?>
                                    <label class="service-option <?php echo $isHidden; ?>" data-category="<?php echo htmlspecialchars($cat); ?>" style="display:flex; align-items:center; gap:8px; padding:8px 12px; cursor:pointer;">
                                        <input type="checkbox" name="service_ids[]" value="<?php echo $s['id']; ?>">
                                        <?php
                                            $durationLabel = isset($s['duration_display']) && trim($s['duration_display']) !== ''
                                                ? $s['duration_display'] . ' (' . $s['duration'] . ' minutes)'
                                                : $s['duration'] . ' minutes';
                                        ?>
                                        <span><?php echo htmlspecialchars($s['name'] . ' — ₱' . number_format($s['price'],2) . ' — ' . $durationLabel); ?></span>
                                    </label>
                                <?php endfor; ?>
                                
                                <!-- See All Button -->
                                <?php if (count($firstCategoryServices) > $displayCount): ?>
                                    <button type="button" class="see-all-btn" id="seeAllBtn" onclick="toggleSeeAll(event)">
                                        See All (<?php echo count($firstCategoryServices); ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="staff_id" class="form-label">Choose Staff (optional)</label>
                            <select name="staff_id" id="staff_id" class="form-select">
                                <option value="">Any available staff</option>
                                <?php foreach($staff as $sf): ?>
                                    <option value="<?php echo $sf['id']; ?>"><?php echo htmlspecialchars($sf['pseudonym']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking_date" class="form-label">Booking Date *</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-input" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="booking_time" class="form-label">Booking Time *</label>
                            <input type="time" name="booking_time" id="booking_time" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">Special Requests</label>
                        <textarea name="notes" id="notes" class="form-textarea" placeholder="Any special requests or notes..."></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="payment_proof" class="form-label">Payment Proof (JPG, PNG, max 4MB) *</label>
                            <input type="file" name="payment_proof" id="payment_proof" class="form-file" 
                                   accept="image/png,image/jpeg" required>
                            <small style="color: #c4c4c4; font-size: 0.9rem; display: block; margin-top: 5px;">
                                Upload screenshot of your 50% down payment and send it to our GCASH number: 09123456789. Make sure your name and booking details are visible in the screenshot.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <div class="qr-section">
                                <div style="color: #FFD700; font-weight: 600; margin-bottom: 10px;">GCASH QR Code</div>
                                <div class="qr-placeholder">
                                    SCAN TO PAY<br>50% Down Payment
                                </div>
                                <div style="color: #c4c4c4; font-size: 0.9rem; margin-top: 10px;">
                                    Scan QR code and upload payment proof above
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid rgba(255, 215, 0, 0.2);">
                        <a href="index.php" style="color: #c4c4c4; text-decoration: none;">Cancel</a>
                        <button type="submit" class="btn" style="padding: 15px 40px; font-size: 1.1rem;">
                            Submit Booking
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Set minimum time to current time if booking date is today
        document.getElementById('booking_date').addEventListener('change', function() {
            const today = new Date().toISOString().split('T')[0];
            const timeInput = document.getElementById('booking_time');
            if (this.value === today) {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                timeInput.min = `${hours}:${minutes}`;
            } else {
                timeInput.min = '00:00';
            }
        });

        // Services by category data
        const servicesData = <?php echo json_encode($servicesByCategory); ?>;
        const displayCount = 3;
        let currentCategory = '<?php echo $categories[0] ?? 'Uncategorized'; ?>';
        let showAllExpanded = false;

        // Category pill filtering
        document.querySelectorAll('.category-pill').forEach(pill => {
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state
                document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                
                // Get selected category
                const selectedCategory = this.getAttribute('data-category');
                currentCategory = selectedCategory;
                showAllExpanded = false;
                
                // Update service list
                updateServiceList(selectedCategory);
            });
        });

        function updateServiceList(category) {
            const container = document.getElementById('serviceListContainer');
            const servicesInCategory = servicesData[category] || [];
            
            let html = '';
            
            // Show first 3 services
            for (let i = 0; i < servicesInCategory.length; i++) {
                const service = servicesInCategory[i];
                const isHidden = i >= displayCount && !showAllExpanded ? 'hidden-services' : '';
                const durationLabel = service.duration_display && service.duration_display.trim() !== ''
                    ? service.duration_display + ' (' + service.duration + ' minutes)'
                    : service.duration + ' minutes';
                
                html += `
                    <label class="service-option ${isHidden}" data-category="${service.category || 'Uncategorized'}">
                        <input type="checkbox" name="service_ids[]" value="${service.id}">
                        <span>${service.name} — ₱${parseFloat(service.price).toFixed(2)} — ${durationLabel}</span>
                    </label>
                `;
            }
            
            // Add See All button if needed
            if (servicesInCategory.length > displayCount) {
                const btnText = showAllExpanded ? `Show Less (${displayCount})` : `See All (${servicesInCategory.length})`;
                html += `<button type="button" class="see-all-btn" onclick="toggleSeeAll(event)">${btnText}</button>`;
            }
            
            container.innerHTML = html;
            
            // Re-attach checkbox event listeners
            document.querySelectorAll('input[name="service_ids[]"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', updateServiceDropdownBtn);
            });
            
            // Update dropdown button text
            updateServiceDropdownBtn();
        }

        function toggleSeeAll(event) {
            event.preventDefault();
            showAllExpanded = !showAllExpanded;
            updateServiceList(currentCategory);
        }

        // Update dropdown button text with selected services (visual feedback)
        function updateServiceDropdownBtn() {
            var checked = Array.from(document.querySelectorAll('input[name="service_ids[]"]:checked'));
            if (checked.length > 0) {
                // Visual feedback that services are selected
                document.getElementById('serviceListContainer').style.borderColor = '#4ade80';
            } else {
                document.getElementById('serviceListContainer').style.borderColor = '#FFD700';
            }
        }

        // Required validation for at least one service
        document.querySelector('form').addEventListener('submit', function(e) {
            var checked = document.querySelectorAll('input[name="service_ids[]"]:checked');
            if (checked.length === 0) {
                alert('Please select at least one service.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
