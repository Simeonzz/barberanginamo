<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barberang Ina Mo - Premium Beauty Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.php">
    <style>
        /* Smooth scrolling for the entire page */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }   

        /* Section Styles */
        .page-section {
            min-height: 100vh;
            padding: 100px 0;
            display: flex;
            align-items: center;
        }

        /* Hero Section */
        #home {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding-top: 80px;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-title {
            font-size: 4.5rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
            letter-spacing: 3px;
        }

        .hero-subtitle {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 30px;
            letter-spacing: 2px;
            color: #d4af37;
        }

        .hero-description {
            max-width: 700px;
            margin: 0 auto 40px;
            font-size: 1.1rem;
            line-height: 1.8;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* About Section */
        #about {
            background: #f9f9f9;
        }

        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-image {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .section-title {
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 3rem;
            color: #333;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
            position: relative;
            padding-bottom: 20px;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background: #d4af37;
        }

        .about-text {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }

        /* Services Section */
        #services {
            background: white;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .service-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
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

        /* Products Section */
        #products {
            background: #f9f9f9;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4af37;
            font-size: 3rem;
        }

        .product-content {
            padding: 20px;
            text-align: center;
        }

        .product-name {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.4rem;
            color: #d4af37;
            font-weight: 700;
            margin-bottom: 15px;
        }

        /* Appointment Section */
        #appointment {
            background: #1E162B;
            color: Hex #d4af37;
        }

        .appointment-form {
            max-width: 800px;
            margin: 0 auto;
            background: #2a2a2a;
            padding: 50px;
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #d4af37;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: #3a3a3a;
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #d4af37;
        }

        /* Active Navigation Indicator */
        .nav-link.active {
            color: #d4af37;
            position: relative;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #d4af37;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .about-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .hero-title {
                font-size: 3.5rem;
            }
            
            .section-title h2 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .page-section {
                padding: 80px 0;
                min-height: auto;
            }
            
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.4rem;
            }
            
            .appointment-form {
                padding: 30px 20px;
            }
            
            html {
                scroll-padding-top: 70px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .services-grid,
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <!-- beben -->
</head>
<body>
    <?php 
    // We'll modify the navbar to use hash links
    include 'navbar.php'; 
    ?>

    <!-- Home Section -->
    <section id="home" class="page-section">
        <div class="hero-content">
            <h1 class="hero-title">BARBERANG INA MO</h1>
            <h2 class="hero-subtitle">IF IT MAKES YOU FEEL BEAUTIFUL, THEN DO IT! </h2>
            <p class="hero-description">
                Experience luxury hair care and wellness in a premium grooming environment. 
                Our skilled professionals provide exceptional services that enhance your natural beauty.
            </p>
            <div class="hero-buttons">
                <a href="#appointment" class="btn btn-primary" style="color: white;">Book Appointment</a>
                <a href="#services" class="btn btn-outline" style="color: white; border-color: white;">View Services</a>
            </div>
        </div>
    </section>

    <!-- Team Section Styles -->
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #0b0b0b;
        color: #fff;
    }

    .team-section {
        text-align: center;
        padding: 70px 20px;
    }

    .team-section h1 {
        font-size: 36px;
        margin-bottom: 10px;
    }

    .team-section p {
        color: #ccc;
        margin-bottom: 60px;
    }

    .team-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
        max-width: 1200px;
        margin: auto;
    }

    .team-card {
        position: relative;
        padding: 20px;
        transition: 0.3s ease;
        wdith: 100%;
    }

    .team-card:hover {
        transform: translateY(-10px);
    }

    .team-img {
        width: 320px;
        height: 320px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        background: #222;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .team-name {
        font-size: 22px;
        margin-top: 20px;
        letter-spacing: 1px;
    }

    .team-role {
        font-size: 18px;
        margin-top: 6px;
        color: #ddd;
    }

    .highlight .team-name,
    .highlight .team-role {
        color: #ff2d75;
    }

    .social-icons {
        position: absolute;
        left: -45px;
        top: 40%;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .social-icons i {
        color: #fff;
        cursor: pointer;
        transition: 0.3s;
        font-size: 18px;
    }

    .social-icons i:hover {
        color: #ff2d75;
    }

    @media (max-width: 900px) {
        .team-container {
            grid-template-columns: repeat(2, 1fr)!important;
        }
    }

    @media (max-width: 600px) {
        .team-container {
            grid-template-columns: 1fr!important;
        }

        .team-img {
            height: 280px;
        }

        .social-icons {
            position: static!important;
            flex-direction: row!important;
            justify-content: center!important;
            margin-top: 15px!important;
        }
    }
    </style>

    <!-- About Section -->
    <section id="about" class="page-section">
        <div class="container">
            <div class="about-content">
                <div class="about-image">
                    <img src="uploads/picture/ourstory.jpg" alt="Our Story">
                </div>
                <div>
                    <div class="section-title">
                        <h2>OUR STORY</h2>
                    </div>
                    <p class="about-text">
                        At <strong style="color: #d4af37;">Barberang Ina Mo</strong>, we believe beauty is an expression of your inner self. 
                        Founded with the vision to create a luxurious space where clients can relax and rejuvenate, 
                        our salon has grown into a sanctuary of creativity, passion, and care.
                    </p>
                    <p class="about-text">
                        Every member of our team is a mother - just like me. "Barberang Ina Mo" literally translates to 
                        "All of us are mothers working in this salon." It's witty yet deeply meaningful, representing 
                        our shared experience and commitment.
                    </p>
                    <p class="about-text">
                        I personally trained every member of our team, each starting with no prior salon experience. 
                        I'm confident that they give their absolute best to provide the quality service that our valued clients deserve.
                    </p>
                </div>
            </div>
        </div>
    </section>
<!-- Team Section -->
<?php
$team = [
    [
        "id" => 11,
        "name" => "Ms.Nica",
        "role" => "Senior Stylist/CEO",
        "image" => "assets/images/staff5.jpg"
    ],
    [
        "id" => 13,
        "name" => "Ms.Shan",
        "role" => "Junior Stylist",
        "image" => "assets/images/staff3.jpg"
    ],
    [
        "id" => 12,
        "name" => "Ms.Mau",
        "role" => "Junior Stylist",
        
        "image" => "assets/images/staff6.jpg"
    ],
    [
        "id" => 14,
        "name" => "Ms.Aan",
        "role" => "Junior Stylist",
        "image" => "assets/images/staff4.jpg"
    ],
    [
        "id" => 15,
        "name" => "Ms.Jing",
        "role" => "Head Assistant",
        "image" => "assets/images/staff8.jpg"
    ],
    [
        "id" => 16,
        "name" => "Ms.Joy",
        "role" => "Nail & Eyelash Matera",
        "image" => "assets/images/staff7.jpg"
    ],
    [
        "id" => 17,
        "name" => "Ms.Leigh",
        "role" => "Nail Teachnician",
        "image" => "assets/images/staff1.jpg"
    ],
];
?>

<!-- Team Carousel Section -->
<link rel="stylesheet" href="assets/team-carousel.css">
<section class="page-section" id="team" style="background: #0b0b0b; color: #fff; padding: 60px 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="text-align: center; font-family: 'Playfair Display', serif; font-size: 3rem; margin-bottom: 15px; color: #fff;">OUR SPECIAL TEAM</h2>
        <p style="text-align: center; color: #ccc; max-width: 1000px; margin: 0 auto 40px; font-size: 1.1rem;">
            Meet our talented team of stylists and beauty experts. Dedicated to making you look and feel your best.
        </p>
        <div class="team-carousel-container">
            <div class="team-carousel-track" id="teamCarouselTrack">
                <?php foreach($team as $index => $member): ?>
                <div class="team-carousel-card">
                    <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>" class="team-carousel-img">
                    <div class="team-carousel-name"><?php echo $member['name']; ?></div>
                    <div class="team-carousel-role"><?php echo $member['role']; ?></div>
                    <div class="team-carousel-rating">
                        <div class="staff-rating-badge" onclick="showStaffRatings(<?php echo $member['id']; ?>, '<?php echo $member['name']; ?>')">
                            <span class="stars" id="stars-<?php echo $member['id']; ?>">☆☆☆☆☆</span>
                            <span class="count" id="rating-<?php echo $member['id']; ?>">(0.0)</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="team-carousel-dots" id="teamCarouselDots">
                <?php foreach($team as $index => $member): ?>
                <span class="team-carousel-dot<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Staff Ratings Modal -->
<div class="modal-overlay" id="modalOverlay"></div>
<div class="staff-ratings-modal" id="staffRatingsModal">
    <span class="modal-close" onclick="closeStaffRatings()">&times;</span>
    <h3 id="ratingsStaffName" style="color: #d4af37; margin-bottom: 15px;"></h3>
    
    <div class="ratings-stats">
        <div class="average-rating">
            <span class="average-number" id="averageRating">0.0</span>
            <span style="color: #666;">out of 5</span>
        </div>
        <div class="rating-stars" id="ratingStars">
            <span>☆</span><span>☆</span><span>☆</span><span>☆</span><span>☆</span>
        </div>
        <p style="color: #666; margin-top: 5px;" id="totalRatings">0 ratings</p>
    </div>
    
    <h4 style="color: #333; margin-bottom: 15px;">Customer Reviews</h4>
    <div id="ratingsComments" style="max-height: 300px; overflow-y: auto;"></div>
</div>
</section>
<!-- Staff Ratings Display -->
<div class="staff-ratings-container" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 15px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; z-index: 10001; box-shadow: 0 20px 60px rgba(0,0,0,0.3);" id="staffRatingsModal">
    <span onclick="closeStaffRatings()" style="position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer;">&times;</span>
    <h3 id="ratingsStaffName" style="color: #d4af37; margin-bottom: 10px;"></h3>
    <div id="ratingsStats" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
            <span style="font-size: 1.5rem; color: #d4af37;" id="averageRating">0.0</span>
            <span style="color: #666;">out of 5</span>
        </div>
        <div style="display: flex; gap: 5px; margin-bottom: 5px;" id="ratingStars"></div>
        <p style="color: #666;" id="totalRatings">0 ratings</p>
    </div>
    <h4 style="color: #333; margin-bottom: 15px;">Customer Reviews</h4>
    <div id="ratingsComments" style="max-height: 300px; overflow-y: auto;"></div>
</div>

<style>
.staff-rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(212, 175, 55, 0.1);
    padding: 5px 10px;
    border-radius: 20px;
    margin-top: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.staff-rating-badge:hover {
    background: rgba(212, 175, 55, 0.2);
    transform: translateY(-2px);
}

.staff-rating-badge .stars {
    color: #d4af37;
    letter-spacing: 2px;
}

.staff-rating-badge .count {
    color: #fff;
    font-size: 0.85rem;
}

.comment-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.comment-item:last-child {
    border-bottom: none;
}

.comment-user {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.comment-stars {
    color: #d4af37;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.comment-text {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
}

.comment-date {
    color: #999;
    font-size: 0.8rem;
    margin-top: 5px;
}
</style>

<script>
function showStaffRatings(staffId, staffName) {
    fetch('get_staff_ratings.php?staff_id=' + staffId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ratingsStaffName').textContent = staffName + ' - Reviews';
                document.getElementById('averageRating').textContent = data.average.toFixed(1);
                document.getElementById('totalRatings').textContent = data.total + ' ratings';
                
                // Show stars
                const starsContainer = document.getElementById('ratingStars');
                starsContainer.innerHTML = '';
                const fullStars = Math.floor(data.average);
                const hasHalfStar = data.average - fullStars >= 0.5;
                
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('span');
                    star.style.fontSize = '1.2rem';
                    if (i <= fullStars) {
                        star.innerHTML = '★';
                        star.style.color = '#d4af37';
                    } else if (hasHalfStar && i === fullStars + 1) {
                        star.innerHTML = '½';
                        star.style.color = '#d4af37';
                    } else {
                        star.innerHTML = '☆';
                        star.style.color = '#ddd';
                    }
                    starsContainer.appendChild(star);
                }
                
                // Show comments
                const commentsDiv = document.getElementById('ratingsComments');
                commentsDiv.innerHTML = '';
                
                if (data.comments.length === 0) {
                    commentsDiv.innerHTML = '<p style="text-align:center; color:#999; padding:20px;">No reviews yet</p>';
                } else {
                    data.comments.forEach(comment => {
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'comment-item';
                        
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += i <= comment.rating ? '★' : '☆';
                        }
                        
                        commentDiv.innerHTML = `
                            <div class="comment-user">${comment.user_name}</div>
                            <div class="comment-stars">${stars}</div>
                            <div class="comment-text">${comment.comment || '<em>No comment</em>'}</div>
                            <div class="comment-date">${new Date(comment.created_at).toLocaleDateString()}</div>
                        `;
                        commentsDiv.appendChild(commentDiv);
                    });
                }
                
                document.getElementById('staffRatingsModal').style.display = 'block';
            }
        });
}

function closeStaffRatings() {
    document.getElementById('staffRatingsModal').style.display = 'none';
}
</script>
<style>
/* Hide scrollbar for Chrome/Safari */
.team-swipe-wrapper::-webkit-scrollbar {
    display: none;
}

/* Desktop styles - show multiple cards */
@media screen and (min-width: 769px) {
    .team-swipe-wrapper {
        overflow-x: hidden !important;
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 30px !important;
        scroll-snap-type: none !important;
        padding: 20px 0 !important;
    }
    
    .team-card {
        flex: 1 1 auto !important;
        scroll-snap-align: none !important;
        padding: 20px !important;
    }
    
    .team-card > div:first-child {
        width: 100% !important;
        height: 320px !important;
    }
    
    .team-card h3 {
        font-size: 1.5rem !important;
    }
    
    .team-card p {
        font-size: 1rem !important;
    }
    
    .team-dots {
        display: none !important;
    }
}

/* Mobile styles - single card with swipe */
@media screen and (max-width: 768px) {
    #team {
        padding: 40px 0 !important;
    }
    
    #team h2 {
        font-size: 2rem !important;
        padding: 0 10px !important;
    }
    
    #team p {
        font-size: 1rem !important;
        padding: 0 15px !important;
        margin-bottom: 20px !important;
    }
    
    .team-swipe-container {
        max-width: 340px !important;
    }
    
    .team-card > div:first-child {
        width: 240px !important;
        height: 300px !important;
    }
    
    .team-card h3 {
        font-size: 1.5rem !important;
        margin-top: 15px !important;
    }
    
    .team-card p {
        font-size: 1.1rem !important;
    }
}

/* Small mobile styles */
@media screen and (max-width: 380px) {
    .team-swipe-container {
        max-width: 280px !important;
    }
    
    .team-card > div:first-child {
        width: 200px !important;
        height: 260px !important;
    }
    
    .team-card h3 {
        font-size: 1.3rem !important;
    }
    
    .team-card p {
        font-size: 1rem !important;
    }
}
@media screen and (max-width: 768px) {
    footer, 
    .footer {
        position: relative !important;
        clear: both !important;
        width: 100% !important;
        padding: 30px 0 15px !important;
        margin-top: 30px !important;
        background: #0b0b0b !important;
        display: block !important;
        float: none !important;
    }
    
    .footer-content {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 25px !important;
        text-align: center !important;
        padding: 0 20px !important;
        max-width: 400px !important;
        margin: 0 auto !important;
    }
    
    .footer-column {
        margin-bottom: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    .footer-column h3 {
        font-size: 1.1rem !important;
        margin-bottom: 10px !important;
        color: #fff !important;
    }
    
    .footer-column p,
    .footer-column a {
        font-size: 0.9rem !important;
        margin-bottom: 5px !important;
        color: #999 !important;
        line-height: 1.5 !important;
        display: block !important;
    }
    
    .footer-bottom {
        padding: 20px 20px 0 !important;
        text-align: center !important;
        border-top: 1px solid #333 !important;
        margin-top: 20px !important;
        font-size: 0.8rem !important;
        color: #777 !important;
        clear: both !important;
    }
    
    /* Force clear any floating elements */
    #appointment {
        margin-bottom: 0 !important;
        padding-bottom: 30px !important;
        clear: both !important;
    }
    
    /* Ensure body and html allow proper flow */
    body, html {
        overflow-x: hidden !important;
        width: 100% !important;
    }
}
</style>

</script>
<script>
// Carousel movement and dot sync
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('teamCarouselTrack');
    const dotsWrap = document.getElementById('teamCarouselDots');
    if (!track) return;

    const cards = Array.from(track.children);
    if (cards.length === 0) return;

    // Duplicate cards to create a seamless marquee loop
    const originalWidth = track.scrollWidth;
    cards.forEach((card) => {
        track.appendChild(card.cloneNode(true));
    });

    const duration = Math.max(25, Math.round(originalWidth / 60));
    track.classList.add('team-carousel-auto');
    track.style.setProperty('--team-scroll-distance', `${originalWidth}px`);
    track.style.setProperty('--team-scroll-duration', `${duration}s`);

    if (dotsWrap) dotsWrap.style.display = 'none';

    // Scroll to card on dot click
    track.addEventListener('touchstart', () => {
        track.style.animationPlayState = 'paused';
    }, {passive: true});
    track.addEventListener('touchend', () => {
        track.style.animationPlayState = 'running';
    }, {passive: true});
});
</script>
</script>
    
    <!-- Services Section -->
    <section id="services" class="page-section">
        <div class="container">
            <div class="section-title">
                <h2>OUR SERVICES</h2>
                <p style="color: #666; max-width: 600px; margin: 0 auto; margin-top: 15px;">
                    Premium beauty services tailored to your needs
                </p>
            </div>
            
            <div class="services-grid">
                <?php
                // Fetch services from database
                include 'config.php';
                $result = $conn->query("SELECT * FROM services WHERE is_active=1");
                while($service = $result->fetch_assoc()):
                ?>
                <div class="service-card" tabindex="0" style="cursor:pointer"
                    data-id="<?php echo (int)$service['id']; ?>"
                    data-name="<?php echo htmlspecialchars($service['name']); ?>"
                    data-description="<?php echo htmlspecialchars($service['description'] ?? ''); ?>"
                    data-image="<?php echo htmlspecialchars($service['image_url'] ?? ''); ?>"
                    data-price="<?php echo number_format($service['price'], 2); ?>"
                    data-duration="<?php echo htmlspecialchars($service['duration']); ?>"
                    onclick="openServiceModal(this)"
                    onkeypress="if(event.key==='Enter'){openServiceModal(this);}"
                >
                    <div class="card-image">
                        <?php if(!empty($service['image_url'])): ?>
                            <img src="<?php echo $service['image_url']; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                        <?php else: ?>
                            <div style="height: 100%; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 3rem; color: #d4af37;">✂️</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="card-description"><?php echo htmlspecialchars(mb_strimwidth($service['description'] ?? '', 0, 120, "...")); ?></p>
                        <div class="card-footer">
                            <div class="card-price">₱<?php echo number_format($service['price'], 2); ?></div>
                            <div style="color: #999; font-size: 0.9rem;">
                                <span>⏱️</span>
                                <span><?php echo htmlspecialchars($service['duration']); ?> min</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="text-center mt-3">
                <a href="#appointment" class="btn btn-primary">Book a Service</a>
            </div>
                <div class="team-container" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:40px; max-width:1200px; margin:auto;">
            <div id="serviceModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5);">
                <div class="modal-content" style="background:white; border-radius:15px; max-width:800px; margin:60px auto; padding:0; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.2); display:flex; flex-direction:row; overflow:hidden;">
                    <button onclick="closeServiceModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:2rem; color:#999; cursor:pointer; z-index:2;">&times;</button>
                    <div id="modalImage" style="width:320px; min-width:220px; height:320px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; border-radius:10px; overflow:hidden;"></div>
                    <div style="flex:1; padding:40px 32px 40px 32px; display:flex; flex-direction:column; justify-content:center;">
                        <h2 id="modalName" style="font-family:'Playfair Display',serif; font-size:2rem; color:#333; margin-bottom:15px;"></h2>
                        <div style="margin-bottom:20px; color:#666; font-size:1.1rem; max-height:180px; overflow-y:auto; padding-right:8px;" id="modalDescription"></div>
                        <div style="display:flex; gap:16px; margin-bottom:18px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="background:#d4af37; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">⏱️</span>
                                <span id="modalDuration" style="font-weight:600; color:#333; font-size:1rem;"></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="background:#d4af37; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">💰</span>
                                <span id="modalPrice" style="font-weight:700; color:#d4af37; font-size:1rem;"></span>
                            </div>
                        </div>
                        <form action="booking.php" method="GET" style="margin-top:auto;">
                            <input type="hidden" name="service_id" id="modalServiceId">
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:15px; font-size:1.1rem;">Book This Service</button>
                        </form>
                    </div>
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
                // Service Image only
                var img = el.dataset.image;
                var imgDiv = document.getElementById('modalImage');
                if (img && img.trim() !== '') {
                    imgDiv.innerHTML = '<img src="'+img+'" alt="'+el.dataset.name+'" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:10px;">';
                } else {
                    imgDiv.innerHTML = '<span style="font-size:4rem;color:#d4af37;">✂️</span>';
                }
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
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="page-section">
        <div class="container">
            <div class="section-title">
                <h2>OUR PRODUCTS</h2>
                <p style="color: #666; max-width: 600px; margin: 0 auto; margin-top: 15px;">
                    Premium grooming products for professional home care
                </p>
            </div>
            
            <div class="products-grid">
                <?php
                // Fetch products from database
                $result = $conn->query("SELECT * FROM products WHERE is_active=1");
                while($product = $result->fetch_assoc()):
                ?>
                <div class="product-card" tabindex="0" style="cursor:pointer"
                    data-id="<?php echo (int)$product['id']; ?>"
                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                    data-description="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                    data-image="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                    data-price="<?php echo number_format($product['price'], 2); ?>"
                    data-stock="<?php echo (int)$product['stock_quantity']; ?>"
                    onclick="openProductModal(this)"
                    onkeypress="if(event.key==='Enter'){openProductModal(this);}"
                >
                    <div class="product-image">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span>🛍️</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                            <?php echo htmlspecialchars(mb_strimwidth($product['description'] ?? '', 0, 100, "...")); ?>
                        </p>
                        <button class="btn" style="width: 100%;">View Details</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <!-- Product Modal -->
            <div id="productModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5);">
                <div class="modal-content" style="background:white; border-radius:15px; max-width:800px; margin:60px auto; padding:0; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.2); display:flex; flex-direction:row; overflow:hidden;">
                    <button onclick="closeProductModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:2rem; color:#999; cursor:pointer; z-index:2;">&times;</button>
                    <div id="productModalImage" style="width:320px; min-width:220px; height:100%; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"></div>
                    <div style="flex:1; padding:40px 32px 40px 32px; display:flex; flex-direction:column; justify-content:center;">
                        <h2 id="productModalName" style="font-family:'Playfair Display',serif; font-size:2rem; color:#333; margin-bottom:15px;"></h2>
                        <div style="margin-bottom:20px; color:#666; font-size:1.1rem; max-height:180px; overflow-y:auto; padding-right:8px;" id="productModalDescription"></div>
                        <div style="display:flex; gap:16px; margin-bottom:18px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="background:#d4af37; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">💰</span>
                                <span id="productModalPrice" style="font-weight:700; color:#d4af37; font-size:1rem;"></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="background:#d4af37; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">📦</span>
                                <span id="productModalStock" style="font-weight:600; color:#333; font-size:1rem;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            function openProductModal(el) {
                document.getElementById('productModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
                // Fill modal content
                document.getElementById('productModalName').textContent = el.dataset.name;
                document.getElementById('productModalDescription').innerHTML = el.dataset.description.replace(/\n/g, '<br>');
                document.getElementById('productModalPrice').textContent = '₱' + el.dataset.price;
                document.getElementById('productModalStock').textContent = el.dataset.stock + ' in stock';
                // Image
                var img = el.dataset.image;
                var imgDiv = document.getElementById('productModalImage');
                imgDiv.innerHTML = img ? '<img src="'+img+'" alt="'+el.dataset.name+'" style="width:100%;height:100%;object-fit:cover;">' : '<span style="font-size:4rem;color:#d4af37;">🛍️</span>';
            }
            function closeProductModal() {
                document.getElementById('productModal').style.display = 'none';
                document.body.style.overflow = '';
            }
            // Close modal on outside click
            window.onclick = function(event) {
                var modal = document.getElementById('productModal');
                if (event.target === modal) closeProductModal();
            }
            // Close modal on Escape key
            document.addEventListener('keydown', function(e){
                if(e.key==='Escape') closeProductModal();
            });
            </script>
        </div>
    </section>

    <!-- Appointment Section -->
    <section id="appointment" class="page-section">
        <div class="container">
            <div class="section-title" style="text-align: center;">
                <h2 style="color: white;">BOOK APPOINTMENT</h2>
                <p style="color: #ccc; max-width: 600px; margin: 0 auto; margin-top: 15px;">
                    Schedule your visit with our expert team
                </p>
            </div>
            
            <div class="appointment-form">
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <p style="color: #ccc; margin-bottom: 20px;">Please login to book an appointment</p>
                        <a href="auth.php" class="btn btn-primary">Login Now</a>
                    </div>
                <?php else: ?>
                    <form action="booking.php" method="GET">
                        <div style="text-align: center; margin-top: 40px;">
                           <button type="submit" class="btn-submit" style="
    background: linear-gradient(135deg, #d4af37, #b8860b);
    color: #1a1a1a;
    border: none;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
    text-decoration: none;
    text-align: center;
    font-family: 'Montserrat', sans-serif;
">
    BOOK NOW
</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background: var(--primary); color: var(--primary-light); padding: 60px 0 30px;">
        <div class="container">
            <div class="footer-content" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; margin-bottom: 40px;">
                <div class="footer-column">
                    <h3 style="color: white; font-size: 1.2rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Barberang Ina Mo
                    </h3>
                    <p style="color: #999; line-height: 1.6;">
                        Your trusted partner in premium grooming services. Experience luxury and excellence in every visit.
                    </p>
                </div>
                
                <div class="footer-column">
                    <h3 style="color: white; font-size: 1.2rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Quick Links
                    </h3>
                    <a href="#home" style="color: #999; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s;">Home</a>
                    <a href="#about" style="color: #999; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s;">About Us</a>
                    <a href="#services" style="color: #999; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s;">Services</a>
                    <a href="#products" style="color: #999; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s;">Products</a>
                    <a href="#appointment" style="color: #999; text-decoration: none; display: block; margin-bottom: 10px; transition: color 0.3s;">Appointment</a>
                </div>
                
                <div class="footer-column">
                    <h3 style="color: white; font-size: 1.2rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Contact
                    </h3>
                    <p style="color: #999; margin-bottom: 10px;">📍 606 Tioco, Tondo, Manila</p>
                    <p style="color: #999; margin-bottom: 10px;">📞 +63 917 123 4567</p>
                    <p style="color: #999; margin-bottom: 10px;">✉️ Barberanginamo@gmail.com</p>
                </div>
                
                <div class="footer-column">
                    <h3 style="color: white; font-size: 1.2rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Hours
                    </h3>
                    <p style="color: #999; margin-bottom: 10px;">Mon - Sat: 9:00 AM - 9:00 PM</p>
                    <p style="color: #999; margin-bottom: 10px;">Sunday: 10:00 AM - 6:00 PM</p>
                </div>
            </div>
            
            <div class="footer-bottom" style="text-align: center; padding-top: 30px; border-top: 1px solid #444; font-size: 0.9rem;">
                © <?php echo date('Y'); ?> Barberang Ina Mo. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for active navigation highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.page-section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            // Function to update active navigation
            function updateActiveNav() {
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (pageYOffset >= (sectionTop - 150)) {
                        current = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href').substring(1) === current) {
                        link.classList.add('active');
                    }
                });
            }
            
            // Update on scroll
            window.addEventListener('scroll', updateActiveNav);
            
            // Update on page load
            updateActiveNav();
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        e.preventDefault();
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                        
                        // Close mobile menu if open
                        const mobileMenu = document.getElementById('navMenu');
                        if (mobileMenu && mobileMenu.classList.contains('active')) {
                            mobileMenu.classList.remove('active');
                            document.getElementById('mobileMenuBtn').innerHTML = '☰';
                        }
                    }
                });
            });
        });
    </script>
    <!-- Rating System JavaScript -->
<link rel="stylesheet" href="rating.css">
<script src="rating.js"></script>
<script>
// Load ratings for all staff
function loadAllStaffRatings() {
    <?php foreach($team as $member): ?>
    fetch('get_staff_ratings.php?staff_id=<?php echo $member['id']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const starsSpan = document.getElementById('stars-<?php echo $member['id']; ?>');
                const ratingSpan = document.getElementById('rating-<?php echo $member['id']; ?>');
                
                if (starsSpan && ratingSpan) {
                    const average = data.average;
                    let stars = '';
                    for (let i = 1; i <= 5; i++) {
                        if (i <= Math.floor(average)) {
                            stars += '★';
                        } else if (i === Math.floor(average) + 1 && average % 1 >= 0.5) {
                            stars += '½';
                        } else {
                            stars += '☆';
                        }
                    }
                    starsSpan.innerHTML = stars;
                    ratingSpan.innerHTML = `(${average.toFixed(1)})`;
                }
            }
        });
    <?php endforeach; ?>
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAllStaffRatings();
});
</script>
<!-- Rating Modal -->
<div id="ratingModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:white; border-radius:15px; max-width:450px; margin:100px auto; padding:30px; position:relative;">
        <span class="close" onclick="closeRatingModal()" style="position:absolute; top:15px; right:20px; font-size:28px; cursor:pointer;">&times;</span>
        <h3 style="text-align:center; color:#d4af37; margin-bottom:20px;">Rate Our Staff</h3>
        
        <div style="text-align:center; margin-bottom:20px;">
            <p style="color:#666;">Help us improve by rating our staff members</p>
        </div>
        
        <form id="ratingForm" onsubmit="submitRating(event)">
            <input type="hidden" name="booking_id" id="ratingBookingId" value="0">
            <input type="hidden" name="staff_id" id="ratingStaffId">
            
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; color:#333; font-weight:600;">Select Staff</label>
                <select id="ratingStaffSelect" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" required>
                    <option value="">Choose a staff member</option>
                    <?php
                    // Fetch all staff for the dropdown
                    $staff_query = "SELECT id, pseudonym, role FROM staff WHERE is_available = 1 ORDER BY pseudonym";
                    $staff_result = $conn->query($staff_query);
                    while ($staff_member = $staff_result->fetch_assoc()):
                    ?>
                    <option value="<?php echo $staff_member['id']; ?>">
                        <?php echo htmlspecialchars($staff_member['pseudonym']); ?> - <?php echo htmlspecialchars($staff_member['role']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="text-align:center; margin-bottom:20px;">
                <p style="margin-bottom:10px; color:#333; font-weight:600;">Your Rating</p>
                <div class="star-rating" style="font-size: 40px; cursor: pointer;">
                    <span class="star" data-rating="1" onclick="setRating(1)">☆</span>
                    <span class="star" data-rating="2" onclick="setRating(2)">☆</span>
                    <span class="star" data-rating="3" onclick="setRating(3)">☆</span>
                    <span class="star" data-rating="4" onclick="setRating(4)">☆</span>
                    <span class="star" data-rating="5" onclick="setRating(5)">☆</span>
                </div>
                <input type="hidden" name="rating" id="selectedRating" value="0">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; color:#333; font-weight:600;">Comment (Optional)</label>
                <textarea name="comment" id="ratingComment" rows="3" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" placeholder="Share your experience..."></textarea>
            </div>
            
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeRatingModal()" style="flex:1; padding:10px; background:#f0f0f0; border:none; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="flex:1; padding:10px; background:#d4af37; color:white; border:none; border-radius:8px; cursor:pointer;">Submit Rating</button>
            </div>
        </form>
        
        <div id="ratingMessage" style="margin-top:15px; text-align:center; display:none;"></div>
    </div>
</div>

<!-- Rate Us Button -->
<div style="position:fixed; bottom:30px; right:30px; z-index:9998;">
    <button onclick="openRatingModal()" style="background:#d4af37; color:#1a1a1a; border:none; border-radius:50px; padding:15px 30px; font-weight:600; cursor:pointer; box-shadow:0 5px 20px rgba(212,175,55,0.3);">
        <i class="bi bi-star-fill" style="margin-right:5px;"></i> Rate Us
    </button>
</div>

<style>
.star-rating .star {
    color: #ddd;
    transition: color 0.2s;
    cursor: pointer;
    font-size: 40px;
    display: inline-block;
    margin: 0 2px;
}
.star-rating .star:hover,
.star-rating .star.active {
    color: #d4af37;
}
.star-rating .star:hover ~ .star {
    color: #ddd;
}
</style>


<script>
let currentRating = 0;

// Rate Us button function - SIMPLE VERSION
function openRatingModal() {
    console.log("openRatingModal clicked"); // Debug line
    
    <?php if(!isset($_SESSION['user_id'])): ?>
    if (confirm('Please login to submit a rating. Would you like to login now?')) {
        window.location.href = 'auth.php';
    }
    return;
    <?php endif; ?>
    
    // Show the modal
    document.getElementById('ratingModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Reset form
    document.getElementById('selectedRating').value = 0;
    document.getElementById('ratingStaffSelect').value = '';
    document.getElementById('ratingComment').value = '';
    document.getElementById('ratingMessage').style.display = 'none';
    
    // Reset stars
    currentRating = 0;
    document.querySelectorAll('.star').forEach(star => {
        star.innerHTML = '☆';
        star.style.color = '#ddd';
    });
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
    document.body.style.overflow = '';
}

function setRating(rating) {
    currentRating = rating;
    document.getElementById('selectedRating').value = rating;
    
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.innerHTML = '★';
            star.style.color = '#d4af37';
        } else {
            star.innerHTML = '☆';
            star.style.color = '#ddd';
        }
    });
}

function submitRating(event) {
    event.preventDefault();
    
    const staffId = document.getElementById('ratingStaffSelect').value;
    
    if (!staffId) {
        alert('Please select a staff member');
        return;
    }
    
    if (currentRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const messageDiv = document.getElementById('ratingMessage');
    messageDiv.style.display = 'block';
    messageDiv.style.color = '#666';
    messageDiv.innerHTML = '⏳ Submitting...';
    
    const formData = new FormData();
    formData.append('staff_id', staffId);
    formData.append('rating', currentRating);
    formData.append('comment', document.getElementById('ratingComment').value);
    formData.append('booking_id', '0');
    
    fetch('submit_rating.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.style.color = '#28a745';
            messageDiv.innerHTML = '✓ ' + data.message;
            setTimeout(() => {
                closeRatingModal();
                location.reload();
            }, 2000);
        } else {
            messageDiv.style.color = '#dc3545';
            messageDiv.innerHTML = '✗ ' + data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.style.color = '#dc3545';
        messageDiv.innerHTML = '✗ Error submitting';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('ratingModal');
    if (event.target === modal) {
        closeRatingModal();
    }
}

// Add hover effect for stars
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('mouseover', function() {
        const rating = parseInt(this.getAttribute('data-rating'));
        document.querySelectorAll('.star').forEach((s, index) => {
            s.style.color = index < rating ? '#d4af37' : '#ddd';
        });
    });
    
    star.addEventListener('mouseout', function() {
        document.querySelectorAll('.star').forEach((s, index) => {
            s.style.color = index < currentRating ? '#d4af37' : '#ddd';
        });
    });
});

// Log to confirm script is loaded
console.log("Rating script loaded");
</script>
</body>
</html>
