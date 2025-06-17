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
    <link rel="icon" href="<?php echo $logo_url; ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztZQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark ">
        <div class="container-fluid">
            <div class="logo-container">
                <a href="<?php echo $index_url; ?>" class="logo-container">
                    <i class="bi bi-fan fan-icon"></i>
                    <div class="fanimation-text">Fanimation</div>
                    <div class="ceiling-fans-text">Ceiling Fans</div>
                </a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === "admin"){ ?>
            <div class="collapse navbar-collapse" id="main_nav">
                <ul class="navbar-nav mx-auto"> <!-- Thêm mx-auto để căn giữa -->
                    <li class="nav-item active fs-4"> <a class="nav-link" href="<?php echo $index_url; ?>">Home</a> </li>
                    <li class="nav-item fs-4"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item dropdown">
                        <!-- Liên kết chính đến help_center.php -->
                        <a class="nav-link fs-4" href="help_center.php">Help Center</a>
                        <!-- Nút để mở dropdown -->
                        <a class="nav-link dropdown-toggle fs-4 d-inline-block" href="#" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="help_center.php#contact-tech">Contact Tech Support</a></li>
                            <li><a class="dropdown-item" href="help_center.php#about-us">About Us</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo $products_url?>">Products</a>
                        <a class="nav-link dropdown-toggle fs-4" href="<?php echo $products_url?>" data-bs-toggle="dropdown"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=1">Ceiling fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=2">Pedestal fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=3">Wall fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=4">Exhaust fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=5">Accessories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item fs-4"><a class="nav-link" href="<?php echo $admin_index_url ?>">Dashboard</a></li> <!-- Dành cho quản trị -->
                </ul>

            </div> <!-- navbar-collapse.// -->
            <?php }else{ ?>
            <div class="collapse navbar-collapse" id="main_nav">
                <ul class="navbar-nav mx-auto"> <!-- Thêm mx-auto để căn giữa -->
                    <li class="nav-item active fs-4"> <a class="nav-link" href="<?php echo $index_url; ?>">Home</a> </li>
                    <li class="nav-item fs-4"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item dropdown">
                        <!-- Liên kết chính đến help_center.php -->
                        <a class="nav-link fs-4" href="<?php echo $help_center_url; ?>">Help Center</a>
                        <!-- Nút để mở dropdown -->
                        <a class="nav-link dropdown-toggle fs-4 d-inline-block" href="#" data-bs-toggle="dropdown" aria-expanded="false"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="help_center.php#contact-tech">Contact Tech Support</a></li>
                            <li><a class="dropdown-item" href="help_center.php#about-us">About Us</a></li>
                        </ul>
                    </li>
                   <li class="nav-item dropdown">
                        <a class="nav-link fs-4" href="<?php echo $products_url?>">Products</a>
                        <a class="nav-link dropdown-toggle fs-4" href="<?php echo $products_url?>" data-bs-toggle="dropdown"></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=1">Ceiling fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=2">Pedestal fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=3">Wall fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=4">Exhaust fans</a></li>
                            <li><a class="dropdown-item" href="<?php echo $products_url?>?category=5">Accessories</a></li>
                        </ul>
                    </li>
                </ul>

            </div>
            <?php } ?>
            <!-- container-fluid.// -->
           <div class="nav-search">
                <form method="GET" action="<?php echo $search_result_url; ?>" class="d-flex align-items-center mb-1">
                    <input class="form-control form-control-sm w-24 me-1 mt-2" name="search" type="text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search for products" aria-label="Tìm kiếm">
                    <button type="submit" class="btn btn-outline-secondary bg-white btn-sm mt-2">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <!-- giỏ hàng -->
            <div class="search-container position-relative d-inline-block me-3">
                <a href="<?php echo $cart_url; ?>" class="position-relative">
                    <i class="bi bi-cart3"></i>
                </a>
            </div>

            <!-- profile -->
            <div class="user-dropdown">
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
            </div>
        </div>
    </nav>
