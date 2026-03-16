<?php
session_start();
include 'config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function e($s){ return htmlspecialchars($s, ENT_QUOTES); }

// Fetch services
$services = [];
$sql = "SELECT * FROM services WHERE is_active=1 ORDER BY price ASC";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    if (!isset($row['description'])) {
        if (isset($row['service_description'])) $row['description'] = $row['service_description'];
        elseif (isset($row['details'])) $row['description'] = $row['details'];
        else $row['description'] = '';
    }
    $services[] = $row;
}

// Detail view
$selected = null;
if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM services WHERE id=? AND is_active=1 LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected ? e($selected['name']) : 'Our Services - Barberang Ina Mo'; ?></title>
    <link rel="stylesheet" href="style.php">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Hero Section */
        .services-hero {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1562322140-8baeececf3df?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 140px 0 80px;
            text-align: center;
        }

        .services-hero-title {
            font-size: 4rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }

        .services-hero-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
            opacity: 0.9;
        }

        /* Service Detail View */
        .service-detail {
            padding: 80px 0;
            background: #f9f9f9;
        }

        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .detail-image {
            height: 100%;
            min-height: 500px;
            background: #f0f0f0;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-content {
            padding: 50px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .service-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }

        .service-description {
            color: #666;
            line-height: 1.8;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .service-meta {
            display: flex;
            gap: 40px;
            margin: 30px 0;
            padding: 25px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meta-icon {
            width: 40px;
            height: 40px;
            background: #d4af37;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .meta-text {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.9rem;
            color: #999;
        }

        .meta-value {
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
        }

        .price {
            font-size: 2rem;
            color: #d4af37;
            font-weight: 700;
        }

        /* Service List View */
        .services-list {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.8rem;
            color: #333;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .section-title p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .service-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-image {
            height: 250px;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-content {
            padding: 25px;
        }

        .card-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .card-price {
            font-size: 1.5rem;
            color: #d4af37;
            font-weight: 700;
        }

        .card-duration {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #999;
            font-size: 0.9rem;
        }

        .card-button {
            background: #333;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .card-button:hover {
            background: #d4af37;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 10px;
            margin-top: 40px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-image {
                min-height: 300px;
            }
            
            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
            
            .services-hero-title {
                font-size: 3rem;
            }
        }

        @media (max-width: 768px) {
            .services-hero {
                padding: 120px 0 60px;
            }
            
            .services-list,
            .service-detail {
                padding: 60px 0;
            }
            
            .detail-content {
                padding: 30px;
            }
            
            .service-title {
                font-size: 2rem;
            }
            
            .service-meta {
                flex-direction: column;
                gap: 20px;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title h2 {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <?php if ($selected): ?>
        <!-- Service Detail View -->
        <section class="service-detail">
            <div class="container">
                <a href="services.php" class="back-link">
                    <span>←</span> Back to Services
                </a>
                
                <div class="detail-container">
                    <div class="detail-grid">
                        <div class="detail-image">
                            <?php if (!empty($selected['image_url'])): ?>
                                <img src="<?php echo e($selected['image_url']); ?>" alt="<?php echo e($selected['name']); ?>">
                            <?php else: ?>
                                <div style="height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <span style="font-size: 4rem;">✂️</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="detail-content">
                            <h1 class="service-title"><?php echo e($selected['name']); ?></h1>
                            <p class="service-description"><?php echo nl2br(e($selected['description'])); ?></p>
                            
                            <div class="service-meta">
                                <div class="meta-item">
                                    <div class="meta-icon">⏱️</div>
                                    <div class="meta-text">
                                        <span class="meta-label">Duration</span>
                                        <span class="meta-value"><?php echo e($selected['duration']); ?> minutes</span>
                                    </div>
                                </div>
                                
                                <div class="meta-item">
                                    <div class="meta-icon">💰</div>
                                    <div class="meta-text">
                                        <span class="meta-label">Price</span>
                                        <span class="price">₱<?php echo number_format($selected['price'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <form action="booking.php" method="GET" style="margin-top: 30px;">
                                <input type="hidden" name="service_id" value="<?php echo (int)$selected['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                                    Book This Service
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Services List View -->
        <section class="services-hero">
            <div class="container">
                <h1 class="services-hero-title">OUR SERVICES</h1>
                <p class="services-hero-subtitle">
                    Experience luxury hair care and wellness with our premium services
                </p>
            </div>
        </section>
        
        <section class="services-list">
            <div class="container">
                <div class="section-title">
                    <h2>Premium Beauty Services</h2>
                    <p>Choose from our wide range of professional services</p>
                </div>
                
                <?php if (!empty($services)): ?>
                    <div class="services-grid">
                        <?php foreach ($services as $s): ?>
                            <div class="service-card" tabindex="0" style="cursor:pointer" 
                                data-id="<?php echo (int)$s['id']; ?>"
                                data-name="<?php echo e($s['name']); ?>"
                                data-description="<?php echo e($s['description']); ?>"
                                data-image="<?php echo e($s['image_url']); ?>"
                                data-price="<?php echo number_format($s['price'], 2); ?>"
                                data-duration="<?php echo e($s['duration']); ?>"
                                onclick="openServiceModal(this)"
                                onkeypress="if(event.key==='Enter'){openServiceModal(this);}"
                            >
                                <div class="card-image">
                                    <?php if (!empty($s['image_url'])): ?>
                                        <img src="<?php echo e($s['image_url']); ?>" alt="<?php echo e($s['name']); ?>">
                                    <?php else: ?>
                                        <div style="height: 100%; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 3rem; color: #d4af37;">✂️</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-content">
                                    <h3 class="card-title"><?php echo e($s['name']); ?></h3>
                                    <p class="card-description"><?php echo e(mb_strimwidth($s['description'], 0, 120, "...")); ?></p>
                                    <div class="card-footer">
                                        <div>
                                            <div class="card-price">₱<?php echo number_format($s['price'], 2); ?></div>
                                            <div class="card-duration">
                                                <span>⏱️</span>
                                                <span><?php echo e($s['duration']); ?> min</span>
                                            </div>
                                        </div>
                                        <span class="card-button">View Details</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No services available at the moment.</h3>
                        <p>Please check back later for our service offerings.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Service Modal -->
    <div id="serviceModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5);">
        <div class="modal-content" style="background:white; border-radius:15px; max-width:600px; margin:60px auto; padding:40px; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <button onclick="closeServiceModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:2rem; color:#999; cursor:pointer;">&times;</button>
            <div id="modalImage" style="height:250px; margin-bottom:30px; border-radius:10px; overflow:hidden; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"></div>
            <h2 id="modalName" style="font-family:'Playfair Display',serif; font-size:2rem; color:#333; margin-bottom:15px;"></h2>
            <div style="margin-bottom:20px; color:#666; font-size:1.1rem;" id="modalDescription"></div>
            <div style="display:flex; gap:30px; margin-bottom:30px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="background:#d4af37; color:white; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">⏱️</span>
                    <span id="modalDuration" style="font-weight:600; color:#333;"></span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="background:#d4af37; color:white; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">💰</span>
                    <span id="modalPrice" style="font-weight:700; color:#d4af37; font-size:1.2rem;"></span>
                </div>
            </div>
            <form action="booking.php" method="GET">
                <input type="hidden" name="service_id" id="modalServiceId">
                <button type="submit" class="btn btn-primary" style="width:100%; padding:15px; font-size:1.1rem;">Book This Service</button>
            </form>
        </div>
    </div>
    <script>
    function openServiceModal(el) {
        document.getElementById('serviceModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        // Fill modal content
        document.getElementById('modalName').textContent = el.dataset.name;
        document.getElementById('modalDescription').innerHTML = el.dataset.description.replace(/\n/g, '<br>');
        document.getElementById('modalDuration').textContent = el.dataset.duration + ' min';
        document.getElementById('modalPrice').textContent = '₱' + el.dataset.price;
        document.getElementById('modalServiceId').value = el.dataset.id;
        // Image
        var img = el.dataset.image;
        var imgDiv = document.getElementById('modalImage');
        imgDiv.innerHTML = img ? '<img src="'+img+'" alt="'+el.dataset.name+'" style="width:100%;height:100%;object-fit:cover;">' : '<span style="font-size:4rem;color:#d4af37;">✂️</span>';
    }
    function closeServiceModal() {
        document.getElementById('serviceModal').style.display = 'none';
        document.body.style.overflow = '';
    }
    // Close modal on outside click
    window.onclick = function(event) {
        var modal = document.getElementById('serviceModal');
        if (event.target === modal) closeServiceModal();
    }
    // Close modal on Escape key
    document.addEventListener('keydown', function(e){
        if(e.key==='Escape') closeServiceModal();
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>