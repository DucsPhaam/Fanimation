<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;

// Get parameters
$records_per_page = 8;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$color = isset($_GET['color']) && is_numeric($_GET['color']) ? (int)$_GET['color'] : '';
$brand = isset($_GET['brand']) && is_numeric($_GET['brand']) ? (int)$_GET['brand'] : '';

if(isset($_GET["category"]) && is_numeric($_GET["category"])){
    $category_name = getCategoryName((int)$_GET["category"]);
}
// Fetch data
$data = getProducts($conn, $records_per_page, $page, $search, $category, $min_price, $max_price, $color, $brand);
$products = $data['products'];
$total_pages = $data['total_pages'];
?>

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
        height: 200px; /* Điều chỉnh kích thước theo cần thiết */
        overflow: hidden;
    }
    .product-card img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Hiển thị toàn bộ ảnh */
        transition: opacity 0.3s ease;
    }
    .product-card a {
        text-decoration: none; /* Loại bỏ gạch dưới cho toàn bộ liên kết */
        color: inherit; /* Giữ màu văn bản từ cha */
    }
    .product-card .card-title,
    .product-card .card-text {
        text-decoration: none; /* Đảm bảo tên và giá không có gạch dưới */
    }
    .rating {
        margin-bottom: 5px;
    }
    .debug {
        color: red;
        font-size: 12px;
    }
</style>
<body>
<div id="contactCarousel" class="carousel slide">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="../assets/images/banners/banner_product_page.jpg" alt="Banner Product Image" class="d-block w-100">
            <div class="carousel-content">
                <h1>Products</h1>
            </div>
        </div>
    </div>
</div>
<?php if(isset($_GET["category"])) {
        $category_name = getCategoryName($_GET["category"]);
?>
<div class="w-90 mx-auto">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-start mb-2">
        <p class="mb-0 text-dark">
            <a href="index.php" class="link text-dark text-decoration-none">Home</a> / <a href="products.php" class="link text-dark text-decoration-none">Products</a> / <?php echo $category_name; ?>
        </p>
    </div>
</div>
<?php }else{?>
<div class="w-90 mx-auto">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-start mb-2">
        <p class="mb-0 text-dark">
            <a href="index.php" class="link text-dark text-decoration-none">Home</a> / Products
        </p>
    </div>
</div>
<?php }?>
<div class="w-75 mx-auto">
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center">
                <p>No products found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4 position-relative">
                    <a href="product_detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" style="text-decoration: none;">
                        <div class="card product-card w-63 h-auto position-relative">
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
                                <p class="card-text fw-bold"><?php echo number_format($product['product_price'] ?? 0, 0, '', '.'); ?>$</p>
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
        <nav aria-label="Page navigation" class="mb-3">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link bg-danger border-0" 
                           href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&color=<?php echo urlencode($color); ?>&brand=<?php echo urlencode($brand); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>
</body>


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

<?php
mysqli_close($conn);
include $footer_url;
?>