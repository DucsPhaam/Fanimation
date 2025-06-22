<?php
// Khởi tạo session nếu chưa bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $function_url;

$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation</title>
    <meta name="description" content="Mua sắm quạt trần, quạt đứng, quạt treo tường, quạt thông gió và phụ kiện cao cấp tại Fanimation. Khám phá các sản phẩm chất lượng và ưu đãi độc quyền.">
    <meta name="keywords" content="quạt trần, quạt đứng, quạt treo tường, quạt thông gió, phụ kiện, Fanimation">
    <link rel="icon" href="<?php echo htmlspecialchars($logo_url); ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztZQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <div class="logo-container">
                <a href="<?php echo htmlspecialchars($index_url); ?>" class="logo-container">
                    <i class="bi bi-fan fan-icon"></i>
                    <div class="fanimation-text">Fanimation</div>
                    <div class="ceiling-fans-text">Ceiling Fans</div>
                </a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav" aria-controls="main_nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === "admin") { ?>
            <div class="collapse navbar-collapse" id="main_nav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item active fs-4"><a class="nav-link" href="<?php echo htmlspecialchars($index_url); ?>">Home</a></li>
                    <li class="nav-item fs-4"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo htmlspecialchars($help_center_url); ?>">Help Center</a>
                        <a class="nav-link dropdown-toggle fs-4 d-inline-block" href="#" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($help_center_url); ?>#contact-tech">Contact Tech Support</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($help_center_url); ?>#about-us">About Us</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo htmlspecialchars($products_url); ?>">Products</a>
                        <a class="nav-link dropdown-toggle fs-4" href="<?php echo htmlspecialchars($products_url); ?>" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=1">Ceiling fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=2">Pedestal fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=3">Wall fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=4">Exhaust fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=5">Accessories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item fs-4"><a class="nav-link" href="<?php echo htmlspecialchars($admin_index_url); ?>">Dashboard</a></li>
                </ul>
            </div>
            <?php } else { ?>
            <div class="collapse navbar-collapse" id="main_nav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item active fs-4"><a class="nav-link" href="<?php echo htmlspecialchars($index_url); ?>">Home</a></li>
                    <li class="nav-item fs-4"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo htmlspecialchars($help_center_url); ?>">Help Center</a>
                        <a class="nav-link dropdown-toggle fs-4 d-inline-block" href="#" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($help_center_url); ?>#contact-tech">Contact Tech Support</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($help_center_url); ?>#about-us">About Us</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo htmlspecialchars($products_url); ?>">Products</a>
                        <a class="nav-link dropdown-toggle fs-4" href="<?php echo htmlspecialchars($products_url); ?>" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=1">Ceiling fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=2">Pedestal fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=3">Wall fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=4">Exhaust fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($products_url); ?>?category=5">Accessories</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php } ?>
            <div class="nav-search">
                <form method="GET" action="<?php echo htmlspecialchars($search_result_url); ?>" class="d-flex align-items-center mb-1">
                    <input class="form-control form-control-sm w-24 me-1 mt-2" name="search" type="text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm sản phẩm" aria-label="Tìm kiếm">
                    <button type="submit" class="btn btn-outline-secondary bg-white btn-sm mt-2">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="search-container position-relative d-inline-block me-3">
                <a href="<?php echo htmlspecialchars($cart_url); ?>" class="position-relative">
                    <i class="bi bi-cart3"></i>
                </a>
            </div>
            <div class="user-dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-label="Menu người dùng">
                    <i class="bi bi-person-circle"></i>
                </a>
                <?php if (!isset($_SESSION["user_id"])) { ?>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($login_url); ?>">Đăng nhập</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($register_url); ?>">Đăng ký</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($my_order_url); ?>">Đơn hàng của tôi</a></li>
                </ul>
                <?php } else { ?>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($logout_url); ?>">Đăng xuất</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($profile_url); ?>">Thông tin cá nhân</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($my_order_url); ?>">Đơn hàng của tôi</a></li>
                </ul>
                <?php } ?>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Khởi tạo thủ công các dropdown của Bootstrap để tránh xung đột
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.forEach(function(dropdownToggleEl) {
                new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
