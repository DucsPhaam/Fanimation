<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
    function getProducts($conn, $records_per_page, $page, $search = '', $category = '', $min_price = '', $max_price = '', $color = '', $brand = '') {
        if (!$conn->ping()) {
            error_log("Kết nối cơ sở dữ liệu đã bị đóng trong getProducts.");
            return ['products' => [], 'total_pages' => 0, 'total_records' => 0];
        }

        $offset = ($page - 1) * $records_per_page;

        $query = "SELECT 
                    p.id AS product_id, 
                    p.name AS product_name, 
                    p.description, 
                    p.price AS product_price, 
                    p.category_id, 
                    p.brand_id, 
                    p.created_at,
                    cat.name AS category_name,
                    b.name AS brand_name,
                    COALESCE(
                        (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.u_primary = 1 LIMIT 1),
                        (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id LIMIT 1),
                        '/Fanimation/assets/images/products/default.jpg'
                    ) AS product_image,
                    GROUP_CONCAT(DISTINCT c.hex_code) AS color_hex_codes,
                    MAX(pv.stock) AS total_stock,
                    AVG(f.rating) AS average_rating
                FROM products p
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                LEFT JOIN colors c ON pv.color_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN feedbacks f ON p.id = f.product_id
                WHERE 1=1";

        $count_query = "SELECT COUNT(DISTINCT p.id) AS total 
                        FROM products p
                        LEFT JOIN product_variants pv ON p.id = pv.product_id
                        LEFT JOIN colors c ON pv.color_id = c.id
                        LEFT JOIN categories cat ON p.category_id = cat.id
                        LEFT JOIN brands b ON p.brand_id = b.id
                        LEFT JOIN feedbacks f ON p.id = f.product_id
                        WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR cat.name LIKE ?)";
            $count_query .= " AND (p.name LIKE ? OR cat.name LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }

        if (!empty($category)) {
            $query .= " AND p.category_id = ?";
            $count_query .= " AND p.category_id = ?";
            $params[] = $category;
            $types .= 'i';
        }

        if ($min_price !== '') {
            $query .= " AND p.price >= ?";
            $count_query .= " AND p.price >= ?";
            $params[] = $min_price;
            $types .= 'd';
        }

        if ($max_price !== '') {
            $query .= " AND p.price <= ?";
            $count_query .= " AND p.price <= ?";
            $params[] = $max_price;
            $types .= 'd';
        }

        if (!empty($color)) {
            $query .= " AND pv.color_id = ?";
            $count_query .= " AND pv.color_id = ?";
            $params[] = $color;
            $types .= 'i';
        }

        if (!empty($brand)) {
            $query .= " AND p.brand_id = ?";
            $count_query .= " AND p.brand_id = ?";
            $params[] = $brand;
            $types .= 'i';
        }

        $query .= " GROUP BY p.id, p.name, p.description, p.price, p.category_id, p.brand_id, p.created_at, cat.name, b.name";
        $query .= " ORDER BY p.id LIMIT ? OFFSET ?";
        $params[] = $records_per_page;
        $params[] = $offset;
        $types .= 'ii';

        $count_stmt = $conn->prepare($count_query);
        if ($count_stmt === false) {
            error_log("Lỗi chuẩn bị truy vấn đếm: " . $conn->error);
            return ['products' => [], 'total_pages' => 0, 'total_records' => 0];
        }

        if (!empty($params)) {
            $count_params = array_slice($params, 0, count($params) - 2);
            $count_types = substr($types, 0, strlen($types) - 2);
            if (!empty($count_params)) {
                $count_stmt->bind_param($count_types, ...$count_params);
            }
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_records = $count_result->fetch_assoc()['total'] ?? 0;
        $count_stmt->close();

        $total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 0;

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Lỗi chuẩn bị truy vấn chính: " . $conn->error);
            return ['products' => [], 'total_pages' => 0, 'total_records' => 0];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();

        return [
            'products' => $products,
            'total_pages' => $total_pages,
            'total_records' => $total_records
        ];
    }
require_once $function_url;

$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 12; // Number of products per page

$category = isset($_GET['category']) ? intval($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$color = isset($_GET['color']) ? intval($_GET['color']) : '';
$brand = isset($_GET['brand']) ? intval($_GET['brand']) : '';

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
        .product-card { height: 100%; }
        .product-card img { height: 200px; object-fit: cover; }
        .pagination .page-link { color: #000; }
        .pagination .page-item.active .page-link { background-color: #007bff; border-color: #007bff; }
        .filter-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; }
        .filter-section label { font-weight: bold; }
    </style>
</head>
<body>
    <?php include $header_url; ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"</h2>
        <p class="text-center">Tìm thấy <?php echo $total_records; ?> sản phẩm</p>

        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <div class="filter-section">
                    <h4>Lọc sản phẩm</h4>
                    <form method="GET" action="search_result.php">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        
                        <div class="mb-3">
                            <label for="category">Danh mục</label>
                            <select class="form-select" name="category" id="category">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo array_search($cat, $categories) + 1; ?>" <?php echo $category == array_search($cat, $categories) + 1 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="min_price">Giá tối thiểu ($)</label>
                            <input type="number" class="form-control" name="min_price" id="min_price" value="<?php echo htmlspecialchars($min_price); ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="max_price">Giá tối đa ($)</label>
                            <input type="number" class="form-control" name="max_price" id="max_price" value="<?php echo htmlspecialchars($max_price); ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="color">Màu sắc</label>
                            <select class="form-select" name="color" id="color">
                                <option value="">Tất cả màu sắc</option>
                                <?php foreach ($colors as $color_id => $hex_code): ?>
                                    <option value="<?php echo $color_id; ?>" <?php echo $color == $color_id ? 'selected' : ''; ?> style="background-color: <?php echo htmlspecialchars($hex_code); ?>;">
                                        <?php echo htmlspecialchars($hex_code); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="brand">Thương hiệu</label>
                            <select class="form-select" name="brand" id="brand">
                                <option value="">Tất cả thương hiệu</option>
                                <?php foreach ($brands as $brand_id => $brand_name): ?>
                                    <option value="<?php echo $brand_id; ?>" <?php echo $brand == $brand_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Áp dụng bộ lọc</button>
                    </form>
                </div>
            </div>

            <!-- Product Listing -->
            <div class="col-md-9">
                <?php if (empty($products)): ?>
                    <p class="text-center">Không tìm thấy sản phẩm nào phù hợp.</p>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card product-card">
                                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                        <p class="card-text">Danh mục: <?php echo htmlspecialchars($product['category_name']); ?></p>
                                        <p class="card-text">Thương hiệu: <?php echo htmlspecialchars($product['brand_name']); ?></p>
                                        <p class="card-text">Giá: <?php echo number_format($product['product_price'], 0, '', '.'); ?> $</p>
                                        <p class="card-text">Tồn kho: <?php echo $product['total_stock'] > 0 ? $product['total_stock'] : 'Hết hàng'; ?></p>
                                        <?php if ($product['average_rating']): ?>
                                            <p class="card-text">Đánh giá: <?php echo number_format($product['average_rating'], 1); ?>/5 <i class="fas fa-star text-warning"></i></p>
                                        <?php endif; ?>
                                        <a href="<?php echo $product_url . '?id=' . $product['product_id']; ?>" class="btn btn-primary">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&color=<?php echo $color; ?>&brand=<?php echo $brand; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&color=<?php echo $color; ?>&brand=<?php echo $brand; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&color=<?php echo $color; ?>&brand=<?php echo $brand; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include $footer_url; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>
