<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
session_start();
require_once $db_connect_url;
include $function_url;
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation</title>
    <meta name="description" content="Shop premium luggage, backpacks, handbags, and accessories at Brown Luggage. Enjoy exclusive deals and quality products.">
    <meta name="keywords" content="luggage, backpacks, handbags, accessories, Brown Luggage">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztZQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <div class="logo-container">
                <a href="<?php echo $index_url; ?>">
                    <i class="bi bi-fan fan-icon"></i>
                    <div class="fanimation-text">Fanimation</div>
                    <div class="ceiling-fans-text">Ceiling Fans</div>
                </a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === "admin") { ?>
            <div class="collapse navbar-collapse" id="main_nav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link fs-5" href="<?php echo $admin_index_url; ?>">Dashboard Administration</a>
                    </li>
                </ul>
            </div>
            <?php } else { header("Location: $index_url"); exit; } ?>
            <!-- profile -->
            <a class="nav-link ms-3" href="<?php echo $index_url; ?>">Home</a>
            <div class="user-dropdown ms-2">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-label="User menu">
                    <i class="bi bi-person-circle"></i>
                </a>
                <?php if (!isset($_SESSION["user_id"])) { ?>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo $login_url; ?>">Đăng nhập</a></li>
                    <li><a class="dropdown-item" href="<?php echo $register_url; ?>">Đăng Ký</a></li>
                </ul>
                <?php } else { ?>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo $logout_url; ?>">Đăng xuất</a></li>
                    <li><a class="dropdown-item" href="<?php echo $account_url; ?>">Thông tin tài khoản</a></li>
                </ul>
                <?php } ?>
            </div>
        </div>
    </nav>
</body>
</html>