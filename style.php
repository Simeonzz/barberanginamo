<?php
header("Content-Type: text/css; charset=UTF-8");
header("Cache-Control: public, max-age=3600");

// Color palette from screenshots
$primary = '#ffffff';
$secondary = '#000000';
$accent = '#d4af37'; // Gold color from screenshots
$lightBg = '#f8f8f8';
$darkBg = '#1a1a1a';
$textLight = '#ffffff';
$textDark = '#333333';
$borderColor = '#e0e0e0';

echo <<<CSS
/* ========== GLOBAL STYLES ========== */
:root {
  --primary: #{$primary};
  --secondary: #{$secondary};
  --accent: #{$accent};
  --light-bg: #{$lightBg};
  --dark-bg: #{$darkBg};
  --text-light: #{$textLight};
  --text-dark: #{$textDark};
  --border: #{$borderColor};
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: var(--text-dark);
  background: var(--light-bg);
  overflow-x: hidden;
}

/* ========== TYPOGRAPHY ========== */
h1, h2, h3, h4, h5, h6 {
  font-family: 'Georgia', 'Times New Roman', serif;
  font-weight: 400;
  margin-bottom: 1rem;
}

h1 {
  font-size: 3.5rem;
  letter-spacing: 2px;
}

h2 {
  font-size: 2.5rem;
  margin-bottom: 2rem;
  position: relative;
  padding-bottom: 1rem;
}

h2::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 2px;
  background: var(--accent);
}

.section-title {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title h2::after {
  left: 50%;
  transform: translateX(-50%);
}

/* ========== LAYOUT ========== */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.section {
  padding: 80px 0;
}

.section-white {
  background: var(--primary);
}

.section-light {
  background: var(--light-bg);
}

.section-dark {
  background: var(--dark-bg);
  color: var(--text-light);
}

/* ========== BUTTONS ========== */
.btn {
  display: inline-block;
  padding: 14px 32px;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: var(--secondary);
  color: var(--primary);
}

.btn-primary:hover {
  background: var(--accent);
  transform: translateY(-2px);
}

.btn-secondary {
  background: var(--accent);
  color: var(--secondary);
}

.btn-secondary:hover {
  background: var(--secondary);
  color: var(--primary);
  transform: translateY(-2px);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--secondary);
  color: var(--secondary);
}

.btn-outline:hover {
  background: var(--secondary);
  color: var(--primary);
}

/* ========== CARDS ========== */
.service-card {
  background: var(--primary);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  transition: transform 0.3s ease;
}

.service-card:hover {
  transform: translateY(-10px);
}

.service-image {
  width: 100%;
  height: 250px;
  object-fit: cover;
}

.service-content {
  padding: 25px;
}

.service-title {
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: var(--secondary);
}

.service-description {
  color: #666;
  margin-bottom: 15px;
  font-size: 0.95rem;
}

.service-price {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--accent);
}

/* ========== FORM ELEMENTS ========== */
.form-group {
  margin-bottom: 25px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--text-dark);
}

.form-control {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid var(--border);
  border-radius: 4px;
  font-size: 1rem;
  font-family: inherit;
  transition: border-color 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: var(--accent);
}

.form-select {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid var(--border);
  border-radius: 4px;
  background: var(--primary);
  font-size: 1rem;
  font-family: inherit;
}

/* ========== GRID SYSTEM ========== */
.grid {
  display: grid;
  gap: 30px;
}

.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
.grid-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 992px) {
  .grid-3, .grid-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .grid-2, .grid-3, .grid-4 {
    grid-template-columns: 1fr;
  }
  
  h1 { font-size: 2.5rem; }
  h2 { font-size: 2rem; }
  .section { padding: 60px 0; }
}

/* ========== UTILITY CLASSES ========== */
.text-center { text-align: center; }
.mt-1 { margin-top: 1rem; }
.mt-2 { margin-top: 2rem; }
.mt-3 { margin-top: 3rem; }
.mb-1 { margin-bottom: 1rem; }
.mb-2 { margin-bottom: 2rem; }
.mb-3 { margin-bottom: 3rem; }
.py-1 { padding-top: 1rem; padding-bottom: 1rem; }
.py-2 { padding-top: 2rem; padding-bottom: 2rem; }
.py-3 { padding-top: 3rem; padding-bottom: 3rem; }

/* ========== ALERTS ========== */
.alert {
  padding: 15px 20px;
  border-radius: 4px;
  margin-bottom: 20px;
  border-left: 4px solid;
}

.alert-success {
  background: #f0fff4;
  border-color: #38a169;
  color: #276749;
}

.alert-error {
  background: #fff5f5;
  border-color: #fc8181;
  color: #c53030;
}

/* ========== BADGES ========== */
.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-popular {
  background: var(--accent);
  color: var(--secondary);
}

.badge-new {
  background: #4299e1;
  color: white;
}
CSS;
?>