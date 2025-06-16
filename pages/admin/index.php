<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

$monthly_sales = getMonthlySales();
$annual_sales = getAnnualSales();

$count_users = getCountUsers();
$count_products = getCountProducts();
$count_orders = getCountOrders();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>

<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Dashboard Administration</h1>
        <div class="row">
          <h5>Monthly sales: <?php echo $monthly_sales; ?></h5>
          <h5>Annual sales: <?php echo $annual_sales; ?></h5>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Users: <?php echo $count_users?></h5>
                        <p class="card-text">Manage registered users.</p>
                        <a href="<?= $base_url; ?>/pages/admin/users.php" class="btn btn-primary">View Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Products: <?php echo $count_products?></h5>
                        <p class="card-text">Manage product listings.</p>
                        <a href="<?= $base_url; ?>/pages/admin/products.php" class="btn btn-primary">View Products</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Orders: <?php echo $count_orders?></h5>
                        <p class="card-text">View and process orders.</p>
                        <a href="<?= $base_url; ?>/pages/admin/orders.php" class="btn btn-primary">View Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
