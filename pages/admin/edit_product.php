<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

$errors = [];
$success = '';
$product = null;

// Get product ID from URL
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product data
if ($product_id > 0) {
    $product = getProductById($conn, $product_id);
    if (!$product) {
        $errors[] = 'Product not found.';
    }
} else {
    $errors[] = 'Invalid product ID.';
}

// Fetch product details
$product_details = [];
if ($product_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM product_details WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product_details = mysqli_fetch_assoc($result) ?: [];
    mysqli_stmt_close($stmt);
}

// Fetch product variants
$variants = [];
if ($product_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT color_id, stock FROM product_variants WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $variants[] = $row;
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $motor_type = trim($_POST['motor_type'] ?? '');
    $blade_count = (int)($_POST['blade_count'] ?? 0);
    $light_kit_included = (int)($_POST['light_kit_included'] ?? 0);
    $remote_control = (int)($_POST['remote_control'] ?? 0);
    $airflow_cfm = (int)($_POST['airflow_cfm'] ?? 0);
    $power_consumption = (int)($_POST['power_consumption'] ?? 0);
    $warranty_years = (int)($_POST['warranty_years'] ?? 0);
    $additional_info = trim($_POST['additional_info'] ?? '');
    $colors = $_POST['colors'] ?? [];
    $stocks = $_POST['stocks'] ?? [];

    // Validation
    if (empty($name)) $errors[] = 'Product name is required.';
    if ($category_id <= 0) $errors[] = 'Please select a category.';
    if ($brand_id <= 0) $errors[] = 'Please select a brand.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if (empty($colors) || empty($stocks)) $errors[] = 'At least one color and stock quantity are required.';
    if ($blade_count < 0) $errors[] = 'Blade count cannot be negative.';
    if ($airflow_cfm < 0) $errors[] = 'Airflow CFM cannot be negative.';
    if ($power_consumption < 0) $errors[] = 'Power consumption cannot be negative.';
    if ($warranty_years < 0) $errors[] = 'Warranty years cannot be negative.';

    // Validate colors and stocks
    foreach ($colors as $index => $color_id) {
        if (!isset($stocks[$index]) || (int)$stocks[$index] < 0) {
            $errors[] = 'Invalid stock quantity for color ID ' . $color_id;
        }
    }

    // Check if product name exists for another product
    $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE name = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, 'si', $name, $product_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
        $errors[] = 'Product name already exists.';
    }
    mysqli_stmt_close($stmt);

    // If no errors, update product
    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // Update product
            $query = "UPDATE products SET name = ?, description = ?, category_id = ?, brand_id = ?, price = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssiiid', $name, $description, $category_id, $brand_id, $price, $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update product: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Update or insert product details
            $query = "SELECT id FROM product_details WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $details_id = mysqli_fetch_assoc($result)['id'] ?? null;
            mysqli_stmt_close($stmt);

            if ($details_id) {
                $query = "UPDATE product_details SET size = ?, material = ?, motor_type = ?, blade_count = ?, light_kit_included = ?, remote_control = ?, airflow_cfm = ?, power_consumption = ?, warranty_years = ?, additional_info = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ssssiiiisii', $size, $material, $motor_type, $blade_count, $light_kit_included, $remote_control, $airflow_cfm, $power_consumption, $warranty_years, $additional_info, $details_id);
            } else {
                $query = "INSERT INTO product_details (product_id, size, material, motor_type, blade_count, light_kit_included, remote_control, airflow_cfm, power_consumption, warranty_years, additional_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'isssiiiissi', $product_id, $size, $material, $motor_type, $blade_count, $light_kit_included, $remote_control, $airflow_cfm, $power_consumption, $warranty_years, $additional_info);
            }
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update product details: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Delete existing variants
            $stmt = mysqli_prepare($conn, "DELETE FROM product_variants WHERE product_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete existing variants: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Insert new variants
            foreach ($colors as $index => $color_id) {
                $stock = (int)$stocks[$index];
                $query = "INSERT INTO product_variants (product_id, color_id, stock) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iii', $product_id, $color_id, $stock);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to create product variant: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }

            // Commit transaction
            mysqli_commit($conn);
            $_SESSION['success'] = 'Product updated successfully!';
            header("Location: $base_url/pages/admin/products.php");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

// Fetch categories, brands, and colors for dropdowns
$categories = getCategories($conn);
$brands = getBrands($conn);
$colors = getColors($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Edit Product</title>
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
        <h1 class="mb-4">Edit Product</h1>
        <div class="card">
            <div class="card-body">
                <?php if ($product): ?>
                    <form method="POST" action="" id="editProductForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"><?= htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= array_search($cat, $categories) + 1; ?>" <?= $product['category_id'] == (array_search($cat, $categories) + 1) ? 'selected' : ''; ?>><?= htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Brand</label>
                            <select class="form-select" id="brand_id" name="brand_id" required>
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $id => $brand): ?>
                                    <option value="<?= htmlspecialchars($id); ?>" <?= $product['brand_id'] == $id ? 'selected' : ''; ?>><?= htmlspecialchars($brand); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($product['product_price']); ?>" required>
                        </div>
                        <!-- Product Details -->
                        <div class="mb-3">
                            <label for="size" class="form-label">Size (e.g., 52 inches)</label>
                            <input type="text" class="form-control" id="size" name="size" value="<?= htmlspecialchars($product_details['size'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="material" class="form-label">Material (e.g., Wood, Metal)</label>
                            <input type="text" class="form-control" id="material" name="material" value="<?= htmlspecialchars($product_details['material'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="motor_type" class="form-label">Motor Type (e.g., AC, DC)</label>
                            <input type="text" class="form-control" id="motor_type" name="motor_type" value="<?= htmlspecialchars($product_details['motor_type'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="blade_count" class="form-label">Blade Count</label>
                            <input type="number" class="form-control" id="blade_count" name="blade_count" value="<?= htmlspecialchars($product_details['blade_count'] ?? ''); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Light Kit Included</label>
                            <select class="form-select" name="light_kit_included">
                                <option value="0" <?= ($product_details['light_kit_included'] ?? 0) == 0 ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?= ($product_details['light_kit_included'] ?? 0) == 1 ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remote Control</label>
                            <select class="form-select" name="remote_control">
                                <option value="0" <?= ($product_details['remote_control'] ?? 0) == 0 ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?= ($product_details['remote_control'] ?? 0) == 1 ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="airflow_cfm" class="form-label">Airflow CFM</label>
                            <input type="number" class="form-control" id="airflow_cfm" name="airflow_cfm" value="<?= htmlspecialchars($product_details['airflow_cfm'] ?? ''); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="power_consumption" class="form-label">Power Consumption (Watt)</label>
                            <input type="number" class="form-control" id="power_consumption" name="power_consumption" value="<?= htmlspecialchars($product_details['power_consumption'] ?? ''); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="warranty_years" class="form-label">Warranty Years</label>
                            <input type="number" class="form-control" id="warranty_years" name="warranty_years" value="<?= htmlspecialchars($product_details['warranty_years'] ?? ''); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="additional_info" class="form-label">Additional Info</label>
                            <textarea class="form-control" id="additional_info" name="additional_info"><?= htmlspecialchars($product_details['additional_info'] ?? ''); ?></textarea>
                        </div>
                        <!-- End Product Details -->
                        <div class="mb-3">
                            <label class="form-label">Colors and Stock</label>
                            <div id="color-stock-container">
                                <?php foreach ($variants as $index => $variant): ?>
                                    <div class="color-stock-row mb-2 d-flex gap-2">
                                        <select name="colors[]" class="form-select" style="width: 200px;" required>
                                            <option value="">Select Color</option>
                                            <?php foreach ($colors as $id => $hex): ?>
                                                <option value="<?= htmlspecialchars($id); ?>" <?= $variant['color_id'] == $id ? 'selected' : ''; ?>><?= htmlspecialchars($hex); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="stocks[]" class="form-control" style="width: 100px;" value="<?= htmlspecialchars($variant['stock']); ?>" required>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addColorStockRow()">Add Another Color</button>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                        <a href="<?= $base_url; ?>/pages/admin/products.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    </form>
                <?php else: ?>
                    <p class="text-danger">Cannot edit product due to errors.</p>
                    <a href="<?= $base_url; ?>/pages/admin/products.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Products</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editProductForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to save these changes?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            }

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

        function addColorStockRow() {
            const container = document.getElementById('color-stock-container');
            const row = document.createElement('div');
            row.className = 'color-stock-row mb-2 d-flex gap-2';
            row.innerHTML = `
                <select name="colors[]" class="form-select" style="width: 200px;" required>
                    <option value="">Select Color</option>
                    <?php foreach ($colors as $id => $hex): ?>
                        <option value="<?= htmlspecialchars($id); ?>"><?= htmlspecialchars($hex); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="stocks[]" class="form-control" style="width: 100px;" placeholder="Stock" required>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>