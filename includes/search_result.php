<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;

$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 12; // Number of products per page

$category = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$color = isset($_GET['color']) && is_numeric($_GET['color']) ? intval($_GET['color']) : '';
$brand = isset($_GET['brand']) && is_numeric($_GET['brand']) ? intval($_GET['brand']) : '';

if ($category && is_numeric($category)) {
    $category_name = getCategoryName($category);
}

$result = getProducts($conn, $records_per_page, $page, $search, $category, $min_price, $max_price, $color, $brand);
$products = $result['products'];
$total_pages = $result['total_pages'];
$total_records = $result['total_records'];

$categories = getCategories($conn);
$colors = getColors($conn);
$brands = getBrands($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm - Fanimation</title>
    <meta name="description" content="Kết quả tìm kiếm sản phẩm tại Fanimation. Tìm kiếm quạt trần, quạt đứng, và phụ kiện chất lượng cao.">
    <meta name="keywords" content="quạt trần, quạt đứng, quạt tường, phụ kiện, Fanimation">
    <link rel="icon" href="<?php echo $logo_url; ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztZQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
    <style>
        .color-options {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin: 5px 0;
            min-height: 25px;
            border: 1px solid #ccc;
        }
        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #fff;
            cursor: pointer;
            display: inline-block;
            box-sizing: border-box;
            outline: 1px solid #000;
            transition: transform 0.2s;
        }
        .color-circle:hover {
            transform: scale(1.2);
        }
        .product-card .image-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: opacity 0.3s ease;
        }
        .product-card a {
            text-decoration: none;
            color: inherit;
        }
        .product-card .card-title,
        .product-card .card-text {
            text-decoration: none;
        }
        .rating {
            margin-bottom: 5px;
        }
        .debug {
            color: red;
            font-size: 12px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .filter-section h5 {
            margin-bottom: 15px;
        }
        .filter-section .form-control {
            margin-bottom: 10px;
        }
        .row {
            margin-right: 15px;
        }
        @media (min-width: 992px) {
            .col-lg-2-4 {
                flex: 0 0 20%;
                max-width: 20%;
            }
        }
    </style>
</head>
<body>
    <?php include $header_url; ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"</h2>
        <p class="text-center">Tìm thấy <?php echo $total_records; ?> sản phẩm</p>

        <?php if ($category && is_numeric($category)) { ?>
        <div class="w-90 mx-auto">
            <div class="d-flex justify-content-start mb-2">
                <p class="mb-0 text-dark">
                    <a href="<?php echo $index_url; ?>" class="link text-dark text-decoration-none">Home</a> / 
                    <a href="<?php echo $products_url; ?>" class="link text-dark text-decoration-none">Products</a> / 
                    <?php echo htmlspecialchars($category_name); ?>
                </p>
            </div>
        </div>
        <?php } else { ?>
        <div class="w-90 mx-auto">
            <div class="d-flex justify-content-start mb-2">
                <p class="mb-0 text-dark">
                    <a href="<?php echo $index_url; ?>" class="link text-dark text-decoration-none">Home</a> / 
                    <a href="<?php echo $products_url; ?>" class="link text-dark text-decoration-none">Products</a>
                </p>
            </div>
        </div>
        <?php } ?>

        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="filter-section">
                    <h5>Lọc sản phẩm</h5>
                    <form method="GET" action="<?php echo $base_url . '/includes/search_result.php'; ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <div class="mb-3">
                            <label for="category" class="form-label">Danh mục</label>
                            <select class="form-control" name="category" id="category">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo array_search($cat, $categories) + 1; ?>" <?php echo $category == (array_search($cat, $categories) + 1) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="min_price" class="form-label">Giá tối thiểu ($)</label>
                            <input type="number" class="form-control" name="min_price" id="min_price" value="<?php echo htmlspecialchars($min_price); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="max_price" class="form-label">Giá tối đa ($)</label>
                            <input type="number" class="form-control" name="max_price" id="max_price" value="<?php echo htmlspecialchars($max_price); ?>" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="color" class="form-label">Màu sắc</label>
                            <select class="form-control" name="color" id="color">
                                <option value="">Tất cả màu sắc</option>
                                <?php foreach ($colors as $color_id => $hex_code): ?>
                                    <option value="<?php echo $color_id; ?>" <?php echo $color == $color_id ? 'selected' : ''; ?> style="background-color: <?php echo htmlspecialchars($hex_code); ?>; color: <?php echo (strtolower($hex_code) == '#000000') ? '#fff' : '#000'; ?>;">
                                        <?php echo htmlspecialchars($hex_code); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="brand" class="form-label">Thương hiệu</label>
                            <select class="form-control" name="brand" id="brand">
                                <option value="">Tất cả thương hiệu</option>
                                <?php foreach ($brands as $brand_id => $brand_name): ?>
                                    <option value="<?php echo $brand_id; ?>" <?php echo $brand == $brand_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Áp dụng bộ lọc</button>
                            <a href="<?php echo $base_url . '/includes/search_result.php?search=' . urlencode($search); ?>" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Product Listing -->
            <div class="col-lg-9 col-md-8">
                <div class="row">
                    <?php if (empty($products)): ?>
                        <div class="col-12 text-center">
                            <p>Không tìm thấy sản phẩm nào phù hợp.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-2-4 col-md-4 col-sm-6 mb-4 position-relative">
                                <a href="<?php echo $product_detail_url . '?id=' . htmlspecialchars($product['product_id']); ?>" style="text-decoration: none;">
                                    <div class="card product-card h-auto position-relative">
                                        <div class="image-container">
                                            <div class="rating">
                                                <p class="card-text fw-bold d-flex text-start gap-1">
                                                    <?php echo number_format($product['average_rating'] ?? 0, 1); ?><i class="bi bi-star-fill"></i>
                                                </p>
                                            </div>
                                            <img src="<?php echo htmlspecialchars($product['product_image'] ?? '/Fanimation/assets/images/products/default.jpg'); ?>" 
                                                 class="card-img-top current-image" 
                                                 alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product'); ?>" 
                                                 id="main-image-<?php echo $product['product_id']; ?>" 
                                                 data-default-image="<?php echo htmlspecialchars($product['product_image'] ?? '/Fanimation/assets/images/products/default.jpg'); ?>">
                                        </div>
                                        <div class="card-body text-center">
                                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></h5>
                                            <p class="card-text fw-bold"><?php echo number_format($product['product_price'] ?? 0, 0, '', '.'); ?> $</p>
                                            <div class="color-options">
                                                <?php
                                                $stmt = $conn->prepare("SELECT pv.color_id, c.hex_code, pi.image_url
                                                                    FROM product_variants pv
                                                                    JOIN colors c ON pv.color_id = c.id
                                                                    LEFT JOIN product_images pi ON pv.product_id = pi.product_id AND pv.color_id = pi.color_id
                                                                    WHERE pv.product_id = ?");
                                                $stmt->bind_param("i", $product['product_id']);
                                                $stmt->execute();
                                                $variants = $stmt->get_result();

                                                if ($variants->num_rows > 0) {
                                                    while ($variant = $variants->fetch_assoc()) {
                                                        $hex_color = $variant['hex_code'];
                                                        $image_url = !empty($variant['image_url']) ? "/Fanimation/assets/images/products/" . htmlspecialchars(basename($variant['image_url'])) : "/Fanimation/assets/images/products/default.jpg";
                                                        echo "<div class='color-circle' style='background-color: $hex_color !important;' title='Color: $hex_color' data-image='$image_url' data-product-id='{$product['product_id']}'></div>";
                                                    }
                                                } else {
                                                    echo "<div class='debug'>No variants found for product ID: {$product['product_id']}</div>";
                                                }
                                                $stmt->close();
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mb-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link bg-danger border-0" 
                                           href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&color=<?php echo urlencode($color); ?>&brand=<?php echo urlencode($brand); ?>&page=<?php echo $page - 1; ?>" 
                                           aria-label="Previous">
                                            <span aria-hidden="true">«</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link bg-danger border-0" 
                                           href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&color=<?php echo urlencode($color); ?>&brand=<?php echo urlencode($brand); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link bg-danger border-0" 
                                           href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&color=<?php echo urlencode($color); ?>&brand=<?php echo urlencode($brand); ?>&page=<?php echo $page + 1; ?>" 
                                           aria-label="Next">
                                            <span aria-hidden="true">»</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include $footer_url; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.color-circle').forEach(circle => {
            const productId = circle.getAttribute('data-product-id');
            const defaultImage = document.getElementById('main-image-' + productId)?.getAttribute('data-default-image');

            circle.addEventListener('mouseover', function() {
                const imageUrl = this.getAttribute('data-image');
                const mainImage = document.getElementById('main-image-' + productId);
                if (mainImage && imageUrl) {
                    mainImage.src = imageUrl;
                    console.log('Hovering over color, new image src:', imageUrl);
                } else {
                    console.log('Error: mainImage or imageUrl not found', { productId, imageUrl });
                }
            });

            circle.addEventListener('mouseout', function() {
                const mainImage = document.getElementById('main-image-' + productId);
                if (mainImage && defaultImage) {
                    mainImage.src = defaultImage;
                    console.log('Mouse out, reverting to default image:', defaultImage);
                } else {
                    console.log('Error: mainImage or defaultImage not found', { productId, defaultImage });
                }
            });
        });
        console.log('Number of color circles:', document.querySelectorAll('.color-circle').length);
    });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
