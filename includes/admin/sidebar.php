<?php
// Get the current page URL or script name for comparison
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="text-white d-flex flex-column" style="background-color: rgb(133, 55, 167);">
    <div class="sidebar-header p-2 text-center">
        <a href="<?php echo $index_url; ?>" class="logo-container text-decoration-none">
            <i class="bi bi-fan fan-icon me-2"></i>
            <div class="fanimation-text">Fanimation</div>
            <div class="ceiling-fans-text">Ceiling Fans</div>
        </a>
    </div>
    <nav class="nav flex-column p-3">
        <a href="<?php echo $admin_index_url; ?>" class="nav-link text-white <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <i class="bi bi-house-door me-2"></i> Dashboard
        </a>
        <a href="<?php echo $base_url; ?>/pages/admin/users.php" class="nav-link text-white <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="<?php echo $base_url; ?>/pages/admin/products.php" class="nav-link text-white <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
            <i class="bi bi-box me-2"></i> Products
        </a>
        <a href="<?php echo $base_url; ?>/pages/admin/orders.php" class="nav-link text-white <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-cart me-2"></i> Orders
        </a>
        <a href="<?php echo $logout_url; ?>" class="nav-link text-white <?php echo $current_page === basename(parse_url($logout_url, PHP_URL_PATH)) ? 'active' : ''; ?>">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </nav>
</aside>

<style>
/* Custom sidebar styles */
#sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.sidebar-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-link {
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 5px;
    font-size: 1.1rem;
}

.nav-link:hover, .nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
}

@media (max-width: 991px) {
    #sidebar {
        transform: translateX(-100%);
    }
    #sidebar.active {
        transform: translateX(0);
    }
}

/* Main content padding to avoid overlap with sidebar */
main {
    margin-left: 250px;
}

@media (max-width: 991px) {
    main {
        margin-left: 0;
    }
}
</style>

<script>
// Toggle sidebar on mobile
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }
});
</script>
