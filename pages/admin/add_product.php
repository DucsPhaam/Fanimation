<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $colors = $_POST['colors'] ?? [];
    $stocks = $_POST['stocks'] ?? [];
    $primary_images = $_POST['primary_image'] ?? [];

    // Validation
    if (empty($name)) $errors[] = 'Product name is required.';
    if ($category_id <= 0) $errors[] = 'Please select a category.';
    if ($brand_id <= 0) $errors[] = 'Please select a brand.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if (empty($colors) || empty($stocks)) $errors[] = 'At least one color and stock quantity are required.';

    // Validate colors and stocks
    foreach ($colors as $index => $color_id) {
        if (!isset($stocks[$index]) || (int)$stocks[$index] < 0) {
            $errors[] = 'Invalid stock quantity for color ID ' . $color_id;
        }
    }

    // Validate images
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/images/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $max_images_per_color = 5;

    $image_paths = [];
    foreach ($_FILES['images']['name'] as $color_index => $files) {
        if (!empty($files[0])) {
            if (count($files) > $max_images_per_color) {
                $errors[] = 'Maximum ' . $max_images_per_color . ' images allowed per color (Color index ' . $color_index . ').';
            } else {
                foreach ($files as $file_index => $file_name) {
                    if ($_FILES['images']['error'][$color_index][$file_index] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['images']['tmp_name'][$color_index][$file_index];
                        $file_size = $_FILES['images']['size'][$color_index][$file_index];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        if (!in_array($file_ext, $allowed_extensions)) {
                            $errors[] = 'Invalid file format for image in color index ' . $color_index . '. Allowed formats: ' . implode(', ', $allowed_extensions);
                        } elseif ($file_size > $max_file_size) {
                            $errors[] = 'File size too large for image in color index ' . $color_index . '. Max size: 5MB';
                        } else {
                            $new_file_name = 'product_' . time() . '_' . $color_index . '_' . $file_index . '.' . $file_ext;
                            $is_primary = isset($primary_images[$color_index]) && $primary_images[$color_index] == $file_index ? 1 : 0;
                            $image_paths[$color_index][] = [
                                'tmp_name' => $file_tmp,
                                'new_name' => $new_file_name,
                                'is_primary' => $is_primary
                            ];
                        }
                    } elseif ($_FILES['images']['error'][$color_index][$file_index] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Error uploading image in color index ' . $color_index . ': Error code ' . $_FILES['images']['error'][$color_index][$file_index];
                    }
                }
                // Ensure only one primary image per color
                $primary_count = array_sum(array_column($image_paths[$color_index] ?? [], 'is_primary'));
                if ($primary_count > 1) {
                    $errors[] = 'Only one primary image allowed per color (Color index ' . $color_index . ').';
                } elseif ($primary_count == 0 && !empty($image_paths[$color_index])) {
                    $image_paths[$color_index][0]['is_primary'] = 1;
                }
            }
        }
    }

    // Check if product name exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE name = ?");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
        $errors[] = 'Product name already exists.';
    }
    mysqli_stmt_close($stmt);

    // If no errors, insert product
    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // Insert product
            $query = "INSERT INTO products (name, description, category_id, brand_id, price) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'sssid', $name, $description, $category_id, $brand_id, $price);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to create product: ' . mysqli_error($conn));
            }
            $product_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Insert product variants
            foreach ($colors as $index => $color_id) {
                $stock = (int)$stocks[$index];
                $query = "INSERT INTO product_variants (product_id, color_id, stock) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iii', $product_id, $color_id, $stock);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to create product variant: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);

                // Insert images for this color
                if (isset($image_paths[$index])) {
                    foreach ($image_paths[$index] as $image) {
                        $destination = $upload_dir . $image['new_name'];
                        if (!move_uploaded_file($image['tmp_name'], $destination)) {
                            throw new Exception('Failed to move uploaded image: ' . $image['new_name']);
                        }
                        $image_url = '/Fanimation/assets/images/products/' . $image['new_name'];
                        $query = "INSERT INTO product_images (product_id, color_id, image_url, u_primary) VALUES (?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, 'iisi', $product_id, $color_id, $image_url, $image['is_primary']);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception('Failed to insert image: ' . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }

            // Commit transaction
            mysqli_commit($conn);
            $_SESSION['success'] = 'Product and images created successfully!';
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
    <title>Fanimation - Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQov1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
    <style>
        .image-preview { max-width: 300px; word-break: break-all; }
        .image-item { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Add New Product</h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" id="addProductForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"><?= htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= array_search($cat, $categories) + 1; ?>" <?= ($category_id ?? 0) == (array_search($cat, $categories) + 1) ? 'selected' : ''; ?>><?= htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="brand_id" class="form-label">Brand</label>
                        <select class="form-select" id="brand_id" name="brand_id" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $id => $brand): ?>
                                <option value="<?= htmlspecialchars($id); ?>" <?= ($brand_id ?? 0) == $id ? 'selected' : ''; ?>><?= htmlspecialchars($brand); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($price ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Colors, Stock, and Images</label>
                        <div id="color-stock-container">
                            <div class="color-stock-row mb-2 d-flex gap-2 align-items-center">
                                <select name="colors[]" class="form-select" style="width: 200px;" required>
                                    <option value="">Select Color</option>
                                    <?php foreach ($colors as $id => $hex): ?>
                                        <option value="<?= htmlspecialchars($id); ?>"><?= htmlspecialchars($hex); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="stocks[]" class="form-control" style="width: 100px;" placeholder="Stock" required>
                                <button type="button" class="btn btn-sm btn-info add-image-btn" title="Add Images"><i class="bi bi-image"></i></button>
                                <input type="file" name="images[0][]" class="image-input d-none" multiple accept=".jpg,.jpeg,.png,.gif">
                                <div class="image-preview mt-2"></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addColorStockRow()">Add Another Color</button>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Product</button>
                    <a href="<?= $base_url; ?>/pages/admin/products.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        let rowIndex = 0;

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addProductForm');

            // Handle form submission with confirmation
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to add this product?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, add it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Handle add image button click
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.add-image-btn');
                if (btn) {
                    const input = btn.nextElementSibling;
                    if (input && input.classList.contains('image-input')) {
                        input.click();
                    }
                }
            });

            // Handle image input change
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('image-input')) {
                    const preview = e.target.closest('.color-stock-row').querySelector('.image-preview');
                    const files = Array.from(e.target.files);
                    const colorIndex = e.target.name.match(/images\[(\d+)\]\[\]/)[1];
                    preview.innerHTML = '';
                    if (files.length > 0) {
                        files.forEach((file, index) => {
                            const itemDiv = document.createElement('div');
                            itemDiv.className = 'image-item';
                            itemDiv.innerHTML = `
                                <span>${file.name}</span>
                                <label>
                                    <input type="radio" name="primary_image[${colorIndex}]" value="${index}" class="primary-radio">
                                    Primary
                                </label>
                            `;
                            preview.appendChild(itemDiv);
                        });
                    } else {
                        preview.innerHTML = '<span>No images selected.</span>';
                    }
                }
            });

            // Handle primary radio button change
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('primary-radio')) {
                    // Uncheck all other primary radio buttons
                    document.querySelectorAll('.primary-radio').forEach(radio => {
                        if (radio !== e.target) {
                            radio.checked = false;
                        }
                    });
                }
            });

            // Show errors if any
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
            rowIndex++;
            const container = document.getElementById('color-stock-container');
            const row = document.createElement('div');
            row.className = 'color-stock-row mb-2 d-flex gap-2 align-items-center';
            row.innerHTML = `
                <select name="colors[]" class="form-select" style="width: 200px;" required>
                    <option value="">Select Color</option>
                    <?php foreach ($colors as $id => $hex): ?>
                        <option value="<?= htmlspecialchars($id); ?>"><?= htmlspecialchars($hex); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="stocks[]" class="form-control" style="width: 100px;" placeholder="Stock" required>
                <button type="button" class="btn btn-sm btn-info add-image-btn" title="Add Images"><i class="bi bi-image"></i></button>
                <input type="file" name="images[${rowIndex}][]" class="image-input d-none" multiple accept=".jpg,.jpeg,.png,.gif">
                <div class="image-preview mt-2"></div>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>