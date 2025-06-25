<?php
    include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
    require_once $db_connect_url; // Assumes this initializes $conn
    include $admin_header_url;

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Check if order ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: $base_url/pages/admin/manage_orders.php");
        exit;
    }

    $order_id = (int)$_GET['id'];

    // Fetch order details
    $order_sql = "SELECT id, user_id, status, created_at, fullname, email, phone_number, address, note, total_money, payment_status 
                  FROM orders 
                  WHERE id = ?";
    $order_stmt = mysqli_prepare($conn, $order_sql);

    // Check if query preparation failed
    if ($order_stmt === false) {
        error_log("mysqli_prepare failed for order query: " . mysqli_error($conn));
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Error preparing query: " . addslashes(mysqli_error($conn)) . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location='$base_url/pages/admin/manage_orders.php';
                });
              </script>";
        exit;
    }

    mysqli_stmt_bind_param($order_stmt, 'i', $order_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    $order = mysqli_fetch_assoc($order_result);

    if (!$order) {
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Order not found!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location='$base_url/pages/admin/manage_orders.php';
                });
              </script>";
        exit;
    }

    // Fetch order items with product name from products table
    $items_sql = "SELECT p.name AS product_name, oi.quantity, oi.price, oi.total_money AS total, oi.payment_method 
                  FROM order_items oi 
                  JOIN product_variants pv ON oi.product_variant_id = pv.id 
                  JOIN products p ON pv.product_id = p.id 
                  WHERE oi.order_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_sql);

    // Check if query preparation failed for items
    if ($items_stmt === false) {
        error_log("mysqli_prepare failed for items query: " . mysqli_error($conn));
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Error preparing items query: " . addslashes(mysqli_error($conn)) . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location='$base_url/pages/admin/manage_orders.php';
                });
              </script>";
        exit;
    }

    mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    $order_items = [];
    while ($row = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Order Details #<?= htmlspecialchars($order_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <!-- SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>
<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Order Details #<?= htmlspecialchars($order_id) ?></h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']) ?></p>
                        <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                        <p><strong>Phone Number:</strong> <?= htmlspecialchars($order['phone_number']) ?: 'N/A' ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                        <p><strong>Note:</strong> <?= htmlspecialchars($order['note']) ?: 'N/A' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Order Date:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        <p><strong>Total Amount:</strong> $<?= number_format($order['total_money'], 2) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge 
                                <?php 
                                switch ($order['status']) {
                                    case 'pending':
                                        echo 'bg-warning';
                                        break;
                                    case 'processing':
                                        echo 'bg-info';
                                        break;
                                    case 'shipped':
                                        echo 'bg-primary';
                                        break;
                                    case 'completed':
                                        echo 'bg-success';
                                        break;
                                    case 'cancelled':
                                        echo 'bg-danger';
                                        break;
                                    default:
                                        echo 'bg-secondary';
                                }
                                ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </p>
                        <p><strong>Payment Status:</strong> 
                            <span class="badge <?= $order['payment_status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                <?= htmlspecialchars($order['payment_status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <h5 class="card-title mt-4">Order Items</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($order_items)): ?>
                                <tr><td colspan="5" class="text-center">No items found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td>$<?= number_format($item['total'], 2) ?></td>
                                        <td><?= htmlspecialchars($item['payment_method']) ?: 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Actions -->
                <div class="mt-4">
                    <button class="btn btn-warning" onclick="updateOrderStatus(<?= $order['id'] ?>, 'processing')">
                        <i class="bi bi-gear"></i> Process
                    </button>
                    <button class="btn btn-success" onclick="updateOrderStatus(<?= $order['id'] ?>, 'completed')">
                        <i class="bi bi-check-circle"></i> Deliver
                    </button>
                    <button class="btn btn-danger" onclick="updateOrderStatus(<?= $order['id'] ?>, 'cancelled')">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <a href="<?= $base_url ?>/pages/admin/orders.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateOrderStatus(orderId, status) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to update order #${orderId} to ${status}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('update_order_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `order_id=${orderId}&status=${status}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response:', data);
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Order status updated successfully!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update order status: ' + (data.error || 'Unknown error'),
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating the order status: ' + error.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>
