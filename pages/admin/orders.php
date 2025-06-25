<?php
    include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
    require_once $db_connect_url; // Assumes this initializes $conn
    include $admin_header_url;

    // Get pagination and filter parameters from URL
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $records_per_page = 10; // Number of orders per page
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    // Function to fetch orders
    function getAllOrders($conn, $records_per_page, $page, $search, $status) {
        $offset = ($page - 1) * $records_per_page;
        $conditions = [];
        $params = [];
        $types = '';

        // Build search condition
        if (!empty($search)) {
            $conditions[] = "(fullname LIKE ? OR id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= 'ss';
        }

        // Build status condition
        if (!empty($status)) {
            $conditions[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }

        // Construct WHERE clause
        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Query to count total records
        $count_sql = "SELECT COUNT(*) as total FROM orders $where";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        }
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_records = mysqli_fetch_assoc($count_result)['total'];
        $total_pages = ceil($total_records / $records_per_page);

        // Query to fetch orders
        $sql = "SELECT id, fullname, created_at, total_money, status, payment_status 
                FROM orders 
                $where 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        $params[] = $records_per_page;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }

        return [
            'orders' => $orders,
            'total_pages' => $total_pages
        ];
    }

    // Fetch orders using the paginated function
    $orders_data = getAllOrders($conn, $records_per_page, $page, $search, $status);
    $orders = $orders_data['orders'];
    $total_pages = $orders_data['total_pages'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>
<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Manage Orders</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order List</h5>
                <!-- Search and Filter Form -->
                <form method="GET" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by customer name or order ID" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="7" class="text-center">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['id']) ?></td>
                                        <td><?= htmlspecialchars($order['fullname']) ?></td>
                                        <td><?= htmlspecialchars($order['created_at']) ?></td>
                                        <td>$<?= number_format($order['total_money'], 2) ?></td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <span class="badge <?= $order['payment_status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= htmlspecialchars($order['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= $base_url ?>/pages/admin/order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <button class="btn btn-sm btn-warning" onclick="updateOrderStatus(<?= $order['id'] ?>, 'processing')">
                                                <i class="bi bi-gear"></i> Process
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="updateOrderStatus(<?= $order['id'] ?>, 'completed')">
                                                <i class="bi bi-check-circle"></i> Deliver
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="updateOrderStatus(<?= $order['id'] ?>, 'cancelled')">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
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
