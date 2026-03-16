<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_display'] ?? 'Guest';
$current_page = basename($_SERVER['PHP_SELF']);
$is_home_page = ($current_page == 'index.php');
?>

<style>
    /* Navigation Bar - Exactly like dashboard */
    .navbar {
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

    .navbar-container {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    /* Brand on the left */
    .navbar-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
        gap: 12px;
    }

    .logo {
        width: 50px;
        height: 50px;
        object-fit: contain;
        background: none;
        border-radius: 50%;
        box-shadow: none;
    }

    .brand-text {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: #d4af37;
        letter-spacing: 1px;
        white-space: nowrap;
        font-weight: 600;
    }

    /* Navigation menu in center */
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
        display: block;
    }

    .nav-link:hover {
        color: #d4af37;
    }

    .nav-link.active {
        color: #d4af37;
        border-bottom: 2px solid #d4af37;
        padding-bottom: 8px;
    }

    /* Auth buttons on the right */
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .auth-btn {
        padding: 10px 25px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .btn-login {
        background: #d4af37;
        color: #1a1a1a;
        font-weight: 600;
    }

    .btn-login:hover {
        background: #b8860b;
        transform: translateY(-2px);
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

    /* Mobile menu button */
    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #c4c4c4;
        padding: 5px;
    }

    /* Responsive design */
    @media (max-width: 992px) {
        .nav-menu {
            gap: 20px;
        }
        
        .brand-text {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 768px) {
        .navbar {
            height: 70px;
            padding: 0 15px;
        }
        
        .navbar-container {
            padding: 0;
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
        
        .brand-text {
            font-size: 1.1rem;
        }
    }

    body {
        padding-top: 80px;
    }

    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }
    }
</style>

<nav class="navbar">
    <div class="navbar-container">
        <!-- Brand on the left -->
        <a href="<?php echo $is_home_page ? '#home' : 'index.php#home'; ?>" class="navbar-brand">
            <img src="assets/images/logos/logo.png" alt="Barberang Ina Mo Logo" class="logo" />
            <div class="brand-text">Barberang Ina Mo</div>
        </a>
        
        <!-- Mobile menu button -->
        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>

        <!-- Navigation Menu in the center -->
        <ul class="nav-menu" id="navMenu">
            <li class="nav-item">
                <a href="<?php echo $is_home_page ? '#home' : 'index.php#home'; ?>" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    HOME
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $is_home_page ? '#about' : 'index.php#about'; ?>" class="nav-link">
                    ABOUT US
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $is_home_page ? '#services' : 'index.php#services'; ?>" class="nav-link">
                    SERVICES
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $is_home_page ? '#products' : 'index.php#products'; ?>" class="nav-link">
                    PRODUCTS
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $is_home_page ? '#appointment' : 'index.php#appointment'; ?>" class="nav-link">
                    APPOINTMENT
                </a>
            </li>
            <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        MY ACCOUNT
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Auth buttons on the right -->
        <div class="navbar-actions">
            <?php if ($isLoggedIn): ?>
                <a href="logout.php" class="auth-btn btn-logout">LOGOUT</a>
            <?php else: ?>
                <a href="auth.php" class="auth-btn btn-login">LOGIN</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            mobileMenuBtn.innerHTML = navMenu.classList.contains('active') ? '✕' : '☰';
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                navMenu.classList.remove('active');
                mobileMenuBtn.innerHTML = '☰';
            }
        });

        // Close mobile menu when clicking a link
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                mobileMenuBtn.innerHTML = '☰';
            });
        });
    }

    // Update active navigation on scroll (only on home page)
    <?php if ($is_home_page): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        
        function updateActiveNav() {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.scrollY >= (sectionTop - 100)) {
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
        
        window.addEventListener('scroll', updateActiveNav);
        updateActiveNav();
    });
    <?php endif; ?>
</script>