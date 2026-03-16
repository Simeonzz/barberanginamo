<?php
// admin_navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'staff']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Admin sidebar + header -->
<style>
  :root{--sidebar-width:260px;--accent:#6d28d9;--muted:#6b7280}
  .admin-topbar{position:fixed;top:0;left:0;right:0;height:68px;background:#fff;box-shadow:0 2px 12px rgba(15,23,42,0.08);display:flex;align-items:center;padding:0 12px;z-index:1200}
  .hamburger{width:48px;height:48px;display:grid;place-items:center;border-radius:8px;cursor:pointer;margin-left:8px}
  .hamburger svg{width:22px;height:22px;color:var(--muted)}
  .brand{display:flex;align-items:center;gap:12px;margin-left:8px;font-weight:700;font-size:16px}
  .sidebar{position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-width);background:#fff;border-right:1px solid rgba(0,0,0,0.04);box-shadow:2px 0 24px rgba(15,23,42,0.06);transform:translateX(-100%);transition:transform .28s ease;z-index:1199;padding-top:80px}
  .sidebar.open{transform:translateX(0)}
  .sidebar .menu{display:flex;flex-direction:column;padding:12px}
  .sidebar a{display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;color:var(--muted);text-decoration:none;font-weight:700;margin-bottom:6px}
  .sidebar a svg{width:18px;height:18px;opacity:.9}
  .sidebar a.active{background:rgba(109,40,217,0.06);color:var(--accent)}
  /* when sidebar open, push content */
  body.sidebar-open .main-content{margin-left:var(--sidebar-width);transition:margin .28s ease}
  /* small screens: overlay */
  @media (max-width:767px){
    .sidebar{width:260px}
    body.sidebar-open .main-content{margin-left:0}
  }
</style>

<div class="admin-topbar">
  <div class="hamburger" id="hamburger" role="button" tabindex="0" aria-label="Toggle menu" aria-expanded="false" title="Toggle menu">
    <!-- hamburger icon -->
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </div>
  <div class="brand">
    <?php if (file_exists('uploads/picture/LOGO.jpg')): ?>
    <img src="uploads/picture/LOGO.jpg" alt="Logo" width="36" height="36" style="border-radius:8px; object-fit: cover;">
<?php else: ?>
    <i class="bi bi-scissors" style="font-size: 24px; color: #6d28d9;"></i>
<?php endif; ?>
    <span>Barberang Ina Mo - Admin</span>
  </div>
  <div style="margin-left:auto;margin-right:12px;display:flex;align-items:center;gap:12px">
    <span style="color:var(--muted);font-size:14px">Welcome, <?php echo htmlspecialchars($_SESSION['user_display'] ?? 'Admin'); ?></span>
    <a href="logout.php" style="background:#fff;border:1px solid rgba(0,0,0,0.06);color:var(--text);font-weight:700;padding:8px 12px;border-radius:8px;text-decoration:none;">Logout</a>
  </div>
</div>

<aside class="sidebar" id="adminSidebar" aria-hidden="true">
  <div class="menu">
    <a href="admin_dashboard.php" class="<?php if ($current_page == 'admin_dashboard.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M3 13h8V3H3v10zM3 21h8v-6H3v6zM13 21h8V11h-8v10zM13 3v6h8V3h-8z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Dashboard</a>
    <a href="admin_bookings.php" class="<?php if ($current_page == 'admin_bookings.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M8 3v4M16 3v4M21 11v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Manage Bookings</a>
    <a href="admin_services.php" class="<?php if ($current_page == 'admin_services.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 2l2 4 4 .5-3 2 1 4-3-2-3 2 1-4-3-2 4-.5L12 2zM3 20h18" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Manage Services</a>
    <a href="admin_products.php" class="<?php if ($current_page == 'admin_products.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73L13 2 4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73L11 22l9-4.27A2 2 0 0 0 21 16z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Manage Products</a>
    <a href="admin_customers.php" class="<?php if ($current_page == 'admin_customers.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-3-3.87M4 21v-2a4 4 0 0 1 3-3.87M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Customer Records</a>
    <a href="admin_reports.php" class="<?php if ($current_page == 'admin_reports.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M3 3h18v18H3V3zM7 14l3-3 2 2 5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Reports & Analytics</a>
    <a href="admin_settings.php" class="<?php if ($current_page == 'admin_settings.php') echo 'active'; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06A2 2 0 1 1 3.28 16.88l.06-.06A1.65 1.65 0 0 0 3.67 15c-.05-.33-.08-.66-.08-1s.03-.67.08-1a1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 5.6 7.28l.06.06c.5.5 1.16.65 1.82.33.33-.16.66-.29 1-.33H9a1.65 1.65 0 0 0 1-1.51V5a2 2 0 1 1 4 0v.09c.14.81.7 1.46 1.51 1.6.36.07.71.2 1 .33.66.32 1.32.17 1.82-.33l.06-.06A2 2 0 1 1 20.72 8.12l-.06.06c-.2.2-.35.45-.44.72-.11.32-.17.66-.17 1 0 .34.06.68.17 1 .09.27.24.52.44.72l.06.06A2 2 0 0 1 19.4 15z" stroke="currentColor" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Settings</a>
    <a href="logout.php" style="margin-top:8px;color:#b91c1c"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M16 17l5-5-5-5M21 12H9M13 5v-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Logout</a>
  </div>
</aside>

<script>
  (function(){
    var hamburger = document.getElementById('hamburger');
    var sidebar = document.getElementById('adminSidebar');
    var storageKey = 'adminSidebarOpen';
    function openSidebar(){
      sidebar.classList.add('open'); document.body.classList.add('sidebar-open'); sidebar.setAttribute('aria-hidden','false');
      try{ localStorage.setItem(storageKey, '1'); }catch(e){}
      // update accessible state
      if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
      // focus first focusable element inside sidebar for keyboard users
      var first = sidebar.querySelector('a, button, [href], [tabindex]:not([tabindex="-1"])'); if (first) first.focus();
    }
    function closeSidebar(){
      sidebar.classList.remove('open'); document.body.classList.remove('sidebar-open'); sidebar.setAttribute('aria-hidden','true');
        try{ localStorage.setItem(storageKey, '0'); }catch(e){}
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    }

    // initialize from localStorage
    try{
      var saved = localStorage.getItem(storageKey);
      if (saved === '1') openSidebar(); else closeSidebar();
    }catch(e){ /* ignore */ }

    if (hamburger) {
      hamburger.addEventListener('click', function(e){
        if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
      });
      // support Enter/Space activation when focused
      hamburger.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
        }
      });
    }
    // close when clicking outside on small screens
    document.addEventListener('click', function(e){
      if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('open')){
        closeSidebar();
      }
    });
    // close on escape
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeSidebar(); });
  })();
</script>