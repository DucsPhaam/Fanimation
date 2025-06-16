<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;

// Get parameters
$records_per_page = 10;
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

// Fetch filter options
$categories = getCategories($conn);
$colors = getColors($conn);
$brands = getBrands($conn);
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
        margin-bottom: 20px;
    }
    .filter-section h5 {
        margin-bottom: 15px;
    }
    .filter-section .form-control {
        margin-bottom: 10px;
    }
    .row{
        margin-right: 15px;
    }
    @media (min-width: 992px) {
        .col-lg-2-4 {
            flex: 0 0 20%;
            max-width: 20%;
        }
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
<?php if(isset($_GET["category"]) && is_numeric($_GET["category"])) { ?>
<div class="w-90 mx-auto">
    <div class="d-flex justify-content-start mb-2">
        <p class="mb-0 text-dark">
            <a href="index.php" class="link text-dark text-decoration-none">Home</a> / <a href="products.php" class="link text-dark text-decoration-none">Products</a> / <?php echo htmlspecialchars($category_name); ?>
        </p>
    </div>
</div>
<?php } else { ?>
<div class="w-90 mx-auto">
    <div class="d-flex justify-content-start mb-2">
        <p class="mb-0 text-dark">
            <a href="index.php" class="link text-dark text-decoration-none">Home</a> / Products
        </p>
    </div>
</div>
<?php } ?>
<div class="w-90 mx-auto">
    <div class="row">
        <!-- Filter Section -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="filter-section">
                <h5>Filter Products</h5>
                <form method="GET" action="products.php">
                    <!-- Search -->
                    <div class="mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <!-- Category Filter -->
                    <div class="mb-3">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Price Range -->
                    <div class="mb-3">
                        <label for="min_price" class="form-label">Min Price</label>
                        <input type="number" name="min_price" id="min_price" class="form-control" placeholder="Min price" value="<?php echo htmlspecialchars($min_price); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="max_price" class="form-label">Max Price</label>
                        <input type="number" name="max_price" id="max_price" class="form-control" placeholder="Max price" value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                    <!-- Color Filter -->
                    <div class="mb-3">
                        <select name="color" class="form-control">
                            <option value="">All Colors</option>
                            <?php foreach ($colors as $color_id => $hex_code): ?>
                                <option value="<?php echo $color_id; ?>" <?php echo $color == $color_id ? 'selected' : ''; ?> style="background-color: <?php echo htmlspecialchars($hex_code); ?>; color: <?php echo (strtolower($hex_code) == '#000000') ? '#fff' : '#000'; ?>;">
                                    <?php echo htmlspecialchars($hex_code); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Brand Filter -->
                    <div class="mb-3">
                        <select name="brand" class="form-control">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $brand_id => $brand_name): ?>
                                <option value="<?php echo $brand_id; ?>" <?php echo $brand == $brand_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Submit and Reset Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="products.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <!-- Product Listing -->
        <div class="col-lg-9 col-md-8">
            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center">
                        <p>No products found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-2-4 col-md-4 col-sm-6 mb-4 position-relative">
                            <a href="product_detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" style="text-decoration: none;">
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
