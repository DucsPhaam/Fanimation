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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.2.0/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztZQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
    <style>
        /* CSS cho hiệu ứng highlight mượt mà và gạch chân bằng chiều dài chữ */
        .navbar-nav .nav-link {
            position: relative;
            transition: all 0.3s ease;
            color: #fff;
            font-weight: bold;
            display: inline-block; /* Chỉ bao quanh nội dung văn bản */
            z-index: 1; /* Đảm bảo liên kết nằm trên các phần tử khác */
        }
        
        .navbar-nav .nav-link.active::after {
            content: "";
            position: absolute;
            width: 100%; /* Gạch chân bằng chiều rộng văn bản */
            height: 1.8px;
            background-color: #fff; /* Màu gạch chân trắng khi active */
            left: 0;
            bottom: 3px;
            transition: width 0.3s ease-in-out;
        }
        .navbar-nav .nav-link:hover::after {
            width: 100%; /* Gạch chân bằng chiều rộng văn bản khi hover */
        }
        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            width: 0;
            height: 1.8px;
            background-color: white;
            left: 0;
            bottom: 3px;
            transition: width 0.3s ease-in-out;
        }
        /* Sửa dropdown menu để không che các liên kết */
        .dropdown-menu {
            position: absolute;
            top: 100%; /* Đảm bảo menu xuất hiện bên dưới */
            margin-top: 5px; /* Khoảng cách nhỏ từ liên kết cha */
            z-index: 1000; /* Đảm bảo menu nằm trên các phần tử khác */
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .dropdown-toggle::after {
            display: none !important; /* Ẩn mũi tên dropdown mặc định */
        }
        /* Thêm khoảng cách đều giữa các mục Home, Help Center, Products */
        .navbar-nav {
            display: flex;
            justify-content: space-around; /* Cách đều các mục */
            width: 100%; /* Đảm bảo navbar-nav chiếm toàn bộ chiều rộng */
            max-width: 600px; /* Giới hạn chiều rộng tối đa để kiểm soát khoảng cách */
        }
        .nav-item {
            flex: 1; /* Các mục chia đều không gian */
            text-align: center; /* Căn giữa nội dung */
            display: flex; /* Đảm bảo nav-link căn giữa trong nav-item */
            justify-content: center; /* Căn giữa nội dung trong nav-item */
            position: relative; /* Đảm bảo dropdown menu căn chỉnh đúng */
        }
        /* Đảm bảo các phần tử khác (logo, search, cart, user) không bị ảnh hưởng */
        .container-fluid {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-search, .search-container, .user-dropdown {
            flex-shrink: 0; /* Ngăn các phần tử này bị co lại */
        }
        /* Tinh chỉnh user-dropdown để nhất quán */
        .user-dropdown .dropdown-menu {
            min-width: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        }
        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark header-includes">
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
                    <li class="nav-item fs-4"><a class="nav-link" href="<?php echo htmlspecialchars($index_url); ?>">Home</a></li>
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
                    <li class="nav-item fs-4"><a class="nav-link" href="<?php echo htmlspecialchars($index_url); ?>">Home</a></li>
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
                <form method="GET" action="<?php echo $search_result_url; ?>" class="d-flex align-items-center mb-1">
                    <input class="form-control form-control-sm w-24 me-1 mt-2" name="search" type="text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search for products" aria-label="Tìm kiếm">
                    <button type="submit" class="btn btn-outline-secondary bg-white btn-sm mt-2">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="search-container position-relative d-inline-block me-3">
                <a href="<?php echo $cart_url; ?>" class="position-relative">
                    <i class="bi bi-cart3"></i>
                </a>
            </div>
            <div class="user-dropdown">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="../" data-bs-toggle="dropdown" aria-label="User menu">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <?php 
                        if(!isset($_SESSION["user_id"])){
                        ?>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $login_url; ?>">Log In</a></li>
                            <li><a class="dropdown-item" href="<?php echo $register_url; ?>">Register</a></li>
                            <li><a class="dropdown-item" href="<?php echo $my_order_url; ?>">My Order</a></li>
                        </ul>
                        <?php }else{?>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $logout_url; ?>">Log Out</a></li>
                            <li><a class="dropdown-item" href="<?php echo $profile_url; ?>">Profile Information</a></li>
                            <li><a class="dropdown-item" href="<?php echo $my_order_url; ?>">My Order</a></li>
                        </ul>
                        <?php }?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Khởi tạo dropdown của Bootstrap
        var dropdownElements = document.querySelectorAll('.dropdown-toggle');
        dropdownElements.forEach(function (dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Logic để highlight liên kết điều hướng
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
        const currentPath = window.location.pathname;

        navLinks.forEach(link => {
            // So sánh href của liên kết với đường dẫn hiện tại
            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath || (linkPath === '<?php echo $index_url; ?>' && currentPath === '/')) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>
