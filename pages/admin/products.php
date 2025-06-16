<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

// Start session for message handling

// Get pagination and filter parameters
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$color = isset($_GET['color']) ? trim($_GET['color']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';

// Fetch products with pagination and filters
$products_data = getProducts($conn, $records_per_page, $page, $search, $category, $min_price, $max_price, $color, $brand);
$products = $products_data['products'];
$total_pages = $products_data['total_pages'];

// Fetch filter options
$categories = getCategories($conn);
$brands = getBrands($conn);
$colors = getColors($conn);

// Handle session messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['success'], $_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQov1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>
<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Manage Products</h1>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="<?= $base_url; ?>/pages/admin/add_product.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New Product</a>
            </div>
            <div class="col-md-6">
                <form class="d-flex flex-wrap gap-2" method="GET" action="">
                    <input type="text" name="search" class="form-control" style="max-width: 200px;" placeholder="Search by name, category, or brand" value="<?= htmlspecialchars($search); ?>">
                    <select name="category" class="form-select" style="max-width: 150px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat); ?>" <?= $category === $cat ? 'selected' : ''; ?>><?= htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="brand" class="form-select" style="max-width: 150px;">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand_id => $brand_name): ?>
                            <option value="<?= htmlspecialchars($brand_id); ?>" <?= $brand == $brand_id ? 'selected' : ''; ?>><?= htmlspecialchars($brand_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="color" class="form-select" style="max-width: 150px;">
                        <option value="">All Colors</option>
                        <?php foreach ($colors as $color_id => $color_hex): ?>
                            <option value="<?= htmlspecialchars($color_id); ?>" <?= $color == $color_id ? 'selected' : ''; ?>><?= htmlspecialchars($color_hex); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="min_price" class="form-control" style="max-width: 100px;" placeholder="Min Price" value="<?= htmlspecialchars($min_price); ?>">
                    <input type="number" name="max_price" class="form-control" style="max-width: 100px;" placeholder="Max Price" value="<?= htmlspecialchars($max_price); ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products && count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_id']); ?></td>
                                    <td><?= htmlspecialchars($product['product_name']); ?></td>
                                    <td><?= htmlspecialchars($product['category_name']); ?></td>
                                    <td><?= htmlspecialchars($product['brand_name']); ?></td>
                                    <td>$<?= number_format($product['product_price'], 2); ?></td>
                                    <td><?= htmlspecialchars($product['total_stock']); ?></td>
                                    <td>
                                        <a href="<?= $base_url; ?>/pages/admin/edit_product.php?id=<?= $product['product_id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                        <button class="btn btn-sm btn-danger delete-product" data-id="<?= $product['product_id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page - 1; ?>&search=<?= urlencode($search); ?>&category=<?= urlencode($category); ?>&brand=<?= urlencode($brand); ?>&color=<?= urlencode($color); ?>&min_price=<?= urlencode($min_price); ?>&max_price=<?= urlencode($max_price); ?>" aria-label="Previous">
                                    <span aria-hidden="true">«</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&category=<?= urlencode($category); ?>&brand=<?= urlencode($brand); ?>&color=<?= urlencode($color); ?>&min_price=<?= urlencode($min_price); ?>&max_price=<?= urlencode($max_price); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page + 1; ?>&search=<?= urlencode($search); ?>&category=<?= urlencode($category); ?>&brand=<?= urlencode($brand); ?>&color=<?= urlencode($color); ?>&min_price=<?= urlencode($min_price); ?>&max_price=<?= urlencode($max_price); ?>" aria-label="Next">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle delete buttons
            document.querySelectorAll('.delete-product').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'You will not be able to recover this product!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '<?= $base_url; ?>/pages/admin/delete_product.php?id=' + productId;
                        }
                    });
                });
            });

            // Display session messages
            <?php if ($success): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?= htmlspecialchars($success); ?>',
                    timer: 2000,
                    showConfirmButton: false
                });
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: '<?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>