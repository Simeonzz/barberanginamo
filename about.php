<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Barberang Ina Mo</title>
    <link rel="stylesheet" href="style.php">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Hero Section */
        .about-hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('uploads/picture/ourstory.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 160px 0 100px;
            text-align: center;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-hero-content {
            max-width: 900px;
            padding: 0 20px;
        }

        .about-hero-title {
            font-size: 4.5rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .about-hero-subtitle {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 30px;
            letter-spacing: 1px;
            color: #d4af37;
            font-style: italic;
        }

        .hero-divider {
            width: 100px;
            height: 3px;
            background: #d4af37;
            margin: 30px auto;
        }

        /* Story Section */
        .story-section {
            padding: 100px 0;
            background: #f9f9f9;
        }

        .story-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .story-image {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .story-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .story-image:hover img {
            transform: scale(1.02);
        }

        .story-content {
            padding: 20px;
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

        .story-text {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }

        .story-highlight {
            background: rgba(212, 175, 55, 0.1);
            border-left: 4px solid #d4af37;
            padding: 25px;
            margin: 40px 0;
            border-radius: 0 8px 8px 0;
        }

        .story-highlight p {
            font-size: 1.2rem;
            font-style: italic;
            color: #333;
            line-height: 1.6;
            margin: 0;
        }

        .story-quote {
            color: #d4af37;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 30px;
            font-family: 'Playfair Display', serif;
        }

        /* Mission Section */
        .mission-section {
            background: #1a1a1a;
            color: white;
            padding: 100px 0;
        }

        .mission-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .mission-title {
            font-size: 2.8rem;
            color: #d4af37;
            margin-bottom: 40px;
            font-family: 'Playfair Display', serif;
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .mission-card {
            background: #2a2a2a;
            padding: 40px 30px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .mission-card:hover {
            transform: translateY(-10px);
            border-color: #d4af37;
        }

        .mission-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #d4af37;
        }

        .mission-card h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .mission-card p {
            color: #ccc;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Founder Section */
        .founder-section {
            padding: 100px 0;
            background: linear-gradient(rgba(249, 249, 249, 0.9), rgba(249, 249, 249, 0.9)), url('https://images.unsplash.com/photo-1580618672591-eb180b1a973f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
        }

        .founder-content {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .founder-quote {
            font-size: 1.8rem;
            color: #333;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 40px;
            position: relative;
            padding: 0 40px;
        }

        .founder-quote::before,
        .founder-quote::after {
            content: '"';
            font-size: 4rem;
            color: #d4af37;
            position: absolute;
            font-family: serif;
        }

        .founder-quote::before {
            top: -20px;
            left: 0;
        }

        .founder-quote::after {
            bottom: -40px;
            right: 0;
        }

        .founder-info {
            margin-top: 60px;
        }

        .founder-name {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 5px;
            font-family: 'Playfair Display', serif;
        }

        .founder-role {
            color: #d4af37;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .story-container {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .mission-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .about-hero-title {
                font-size: 3.5rem;
            }
            
            .section-title h2 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .about-hero {
                padding: 120px 0 80px;
                min-height: 70vh;
            }
            
            .about-hero-title {
                font-size: 2.8rem;
            }
            
            .about-hero-subtitle {
                font-size: 1.4rem;
            }
            
            .story-section,
            .mission-section,
            .founder-section {
                padding: 60px 0;
            }
            
            .mission-grid {
                grid-template-columns: 1fr;
            }
            
            .founder-quote {
                font-size: 1.4rem;
                padding: 0 20px;
            }
            
            .story-highlight {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .about-hero-title {
                font-size: 2.2rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .story-text {
                font-size: 1.05rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section with Story Cover -->
    <section class="about-hero">
        <div class="about-hero-content">
            <h1 class="about-hero-title">OUR STORY</h1>
            <div class="hero-divider"></div>
            <p class="about-hero-subtitle">Where Beauty Meets Purpose</p>
            <p style="max-width: 700px; margin: 20px auto 0; font-size: 1.1rem; opacity: 0.9;">
                A journey of empowerment, professional growth, and exceptional beauty services
            </p>
        </div>
    </section>

    <!-- Story Content Section -->
    <section class="story-section">
        <div class="container">
            <div class="story-container">
                <div class="story-image">
                    <img src="uploads/picture/ourstory.jpg" alt="Barberang Ina Mo Team">
                </div>
                
                <div class="story-content">
                    <div class="section-title">
                        <h2>The Meaning Behind Our Name</h2>
                    </div>
                    
                    <div class="story-quote">
                        "Barberang Ina Mo" - More than just a name, it's our identity
                    </div>
                    
                    <p class="story-text">
                        The phrase "Barberang Ina Mo" carries a dual meaning that reflects both our wit and our mission. 
                        On the surface, it's a playful expression, but at its core, it speaks to the heart of who we are: 
                        a team of dedicated mothers working together to provide exceptional beauty services.
                    </p>
                    
                    <div class="story-highlight">
                        <p>
                            Every member of our team is a mother - just like me. "Barberang Ina Mo" literally translates to 
                            "All of us are mothers working in this salon." It's witty yet deeply meaningful, representing 
                            our shared experience and commitment.
                        </p>
                    </div>
                    
                    <p class="story-text">
                        This name embodies our journey from personal challenges to professional empowerment, 
                        creating a space where beauty services meet meaningful employment opportunities for mothers 
                        seeking to provide for their families.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="mission-content">
                <h2 class="mission-title">Our Empowering Mission</h2>
                
                <p style="font-size: 1.2rem; line-height: 1.8; color: #ccc; margin-bottom: 40px;">
                    As the owner, my mission extends beyond providing beauty services. I created Barberang Ina Mo 
                    to offer a platform for mothers who face barriers to traditional employment due to age, 
                    educational background, or family responsibilities.
                </p>
                
                <div class="mission-grid">
                    <div class="mission-card">
                        <div class="mission-icon">👩‍👧‍👦</div>
                        <h3>For Mothers, By Mothers</h3>
                        <p>Creating opportunities for mothers who couldn't pursue traditional careers due to family commitments or educational barriers.</p>
                    </div>
                    
                    <div class="mission-card">
                        <div class="mission-icon">🎓</div>
                        <h3>Professional Training</h3>
                        <p>Providing comprehensive training to team members, many of whom started with zero salon experience, ensuring top-quality service.</p>
                    </div>
                    
                    <div class="mission-card">
                        <div class="mission-icon">✨</div>
                        <h3>Quality Service</h3>
                        <p>Confident that every team member gives their 100% best to provide the exceptional service our clients deserve.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Founder's Vision Section -->
    <section class="founder-section">
        <div class="container">
            <div class="founder-content">
                <div class="section-title">
                    <h2 style="color: #333;">From the Founder</h2>
                </div>
                
                <blockquote class="founder-quote">
                    I personally trained every member of our team, each starting with no prior salon experience. 
                    I'm confident that they give their absolute best to provide the quality service that our valued clients deserve.
                </blockquote>
                
                <div class="founder-info">
                    <h3 class="founder-name">Barberang Ina Mo Team</h3>
                    <p class="founder-role">Founder & Lead Trainer</p>
                    <p style="color: #666; max-width: 600px; margin: 0 auto; line-height: 1.6;">
                        Our journey began with a simple belief: every mother deserves the opportunity to build 
                        a professional career while balancing family life. Today, we're proud to offer both 
                        exceptional beauty services and meaningful employment.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section (Optional - You can keep your existing team section here) -->
    <section class="team-section" style="padding: 80px 0; background: #f9f9f9;">
        <div class="container">
            <div class="section-title" style="text-align: center;">
                <h2>Meet Our Dedicated Team</h2>
                <p style="color: #666; max-width: 600px; margin: 0 auto; margin-top: 15px;">
                    Trained professionals who are also devoted mothers, bringing care and excellence to every service
                </p>
            </div>
            
            <!-- Your existing team grid can go here -->
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>