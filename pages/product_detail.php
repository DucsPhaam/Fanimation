<?php
ob_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require $db_connect_url;
require $function_url;
include $header_url;

// Khởi tạo session_id cho khách vãng lai nếu chưa có
if (session_id() === '') {
    session_start();
}
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}

// Xử lý gửi đánh giá (giữ nguyên, chỉ cho phép người dùng đăng nhập)
$success = '';
$error = '';
$product_slug = isset($_GET['slug']) ? htmlspecialchars($_GET['slug']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback']) && $product_slug !== '') {
    if (isset($_SESSION['user_id'])) {
        $rating = (int)$_POST['rating'];
        $message = mysqli_real_escape_string($conn, trim($_POST['message']));
        $user_id = (int)$_SESSION['user_id'];

        // Lấy product_id từ slug để sử dụng trong feedback
        $product_query = "SELECT id FROM products WHERE slug = ?";
        $product_stmt = mysqli_prepare($conn, $product_query);
        mysqli_stmt_bind_param($product_stmt, 's', $product_slug);
        mysqli_stmt_execute($product_stmt);
        $product_result = mysqli_stmt_get_result($product_stmt);
        $product_row = mysqli_fetch_assoc($product_result);
        mysqli_stmt_close($product_stmt);
        $product_id = $product_row['id'] ?? 0;

        if ($product_id > 0 && $rating >= 1 && $rating <= 5) {
            $check_query = "SELECT COUNT(*) as count FROM feedbacks WHERE user_id = ? AND product_id = ? AND created_at > NOW() - INTERVAL 1 DAY";
            $check_stmt = mysqli_prepare($conn, $check_query);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, 'ii', $user_id, $product_id);
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($check_stmt);

                if ($row['count'] == 0) {
                    $insert_feedback_query = "INSERT INTO feedbacks (user_id, product_id, message, rating, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'approved')";
                    $insert_feedback_stmt = mysqli_prepare($conn, $insert_feedback_query);
                    if ($insert_feedback_stmt) {
                        mysqli_stmt_bind_param($insert_feedback_stmt, 'iisd', $user_id, $product_id, $message, $rating);
                        if (mysqli_stmt_execute($insert_feedback_stmt)) {
                            $success = "Đánh giá đã được gửi thành công!";
                        } else {
                            $error = "Lỗi khi gửi đánh giá: " . mysqli_stmt_error($insert_feedback_stmt);
                        }
                        mysqli_stmt_close($insert_feedback_stmt);
                    } else {
                        $error = "Lỗi chuẩn bị truy vấn: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Bạn đã gửi đánh giá gần đây, vui lòng thử lại sau 24 giờ!";
                }
            } else {
                $error = "Lỗi kiểm tra trùng lặp: " . mysqli_error($conn);
            }
        } else {
            $error = $product_id > 0 ? "Số sao phải từ 1 đến 5!" : "Sản phẩm không hợp lệ!";
        }
    } else {
        $error = "Vui lòng đăng nhập để gửi đánh giá!";
    }
}

// Lấy danh sách feedback
if ($product_slug === '') {
    $feedback_error = "<div class='alert alert-danger'>Slug sản phẩm không hợp lệ.</div>";
} else {
    // Lấy product_id từ slug
    $product_query = "SELECT id FROM products WHERE slug = ?";
    $product_stmt = mysqli_prepare($conn, $product_query);
    mysqli_stmt_bind_param($product_stmt, 's', $product_slug);
    mysqli_stmt_execute($product_stmt);
    $product_result = mysqli_stmt_get_result($product_stmt);
    $product_row = mysqli_fetch_assoc($product_result);
    mysqli_stmt_close($product_stmt);
    $product_id = $product_row['id'] ?? 0;

    if ($product_id > 0) {
        $stmt = $conn->prepare("
            SELECT f.rating, f.message, f.created_at, u.name
            FROM feedbacks f
            LEFT JOIN users u ON f.user_id = u.id
            WHERE f.product_id = ? AND f.status = 'approved'
            ORDER BY f.created_at DESC
        ");
        if ($stmt === false) {
            $feedback_error = "<div class='alert alert-danger'>Lỗi chuẩn bị truy vấn Feedbacks: " . htmlspecialchars($conn->error) . "</div>";
        } else {
            $stmt->bind_param("i", $product_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $feedback_error = "<div class='alert alert-danger'>Lỗi thực thi truy vấn: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    } else {
        $feedback_error = "<div class='alert alert-danger'>Sản phẩm không tồn tại.</div>";
    }
}

// Lấy chi tiết sản phẩm
$product = getProductBySlug($conn, $product_slug);
if (!$product) {
    echo "<div class='alert alert-danger'>Sản phẩm không tồn tại hoặc đã hết hàng.</div>";
    include $footer_url;
    mysqli_close($conn);
    exit;
}

// Lấy thông tin chi tiết sản phẩm từ bảng product_details
$product_details = getProductDetailsById($conn, $product['product_id']);

// Lấy hình ảnh và tồn kho theo màu
$images = [];
$stocks_by_color = [];
$sql = "SELECT DISTINCT pv.color_id, c.hex_code, 
        COALESCE(
            (SELECT pi.image_url 
             FROM product_images pi 
             WHERE pi.product_id = pv.product_id 
             AND pi.color_id = pv.color_id 
             AND pi.u_primary = 1 
             LIMIT 1),
            (SELECT pi.image_url 
             FROM product_images pi 
             WHERE pi.product_id = pv.product_id 
             AND pi.color_id = pv.color_id 
             LIMIT 1),
            '/Fanimation/assets/images/products/default.jpg'
        ) AS image_url, 
        pv.stock
        FROM product_variants pv
        JOIN colors c ON pv.color_id = c.id
        WHERE pv.product_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo "<div class='debug'>Lỗi chuẩn bị truy vấn hình ảnh: " . htmlspecialchars($conn->error) . "</div>";
} else {
    $stmt->bind_param('i', $product['product_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $images[] = [
                'image_url' => $row['image_url'],
                'color_id' => $row['color_id'],
                'hex_code' => $row['hex_code']
            ];
            $stocks_by_color[$row['color_id']] = $row['stock'] ?? 0;
        }
    } else {
        echo "<div class='debug'>Lỗi thực thi truy vấn hình ảnh: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
}
?>

<style>
    .color-options {
        display: flex;
        justify-content: start;
        align-items: center;
        gap: 5px;
        margin: 5px 0;
        min-height: 25px;
        border: 1px solid #ccc;
        padding: 5px;
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

    .color-circle.selected {
        border: 2px solid #ff0000;
    }

    .product-card .image-container {
        position: relative;
        width: 100%;
        height: 400px;
        overflow: hidden;
    }

    .product-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.3s ease;
    }

    .rating {
        margin-bottom: 5px;
    }

    .debug {
        color: red;
        font-size: 12px;
    }

    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        gap: 5px;
        justify-content: flex-end;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 1.5rem;
        color: #ccc;
        cursor: pointer;
        transition: color 0.2s;
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #ffc107;
    }

    .star-rating input:checked+label {
        color: #ffc107;
    }

    .nav-links {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        margin-bottom: 20px;
        align-items: center;
        justify-content: center;
    }

    .nav-links a {
        text-decoration: none;
        color: #333;
        font-weight: 500;
        padding-bottom: 5px;
        transition: color 0.3s, border-bottom 0.3s;
        cursor: pointer;
    }

    .nav-links a:hover {
        color: #ff0000;
        border-bottom: 2px solid #ff0000;
    }

    .nav-links a.active {
        color: #ff0000;
        border-bottom: 2px solid #ff0000;
    }

    #details-section {
        width: 100%;
        margin: 0 auto;
        transition: opacity 0.3s ease, height 0.3s ease;
        padding: 50px 0 50px 0;
    }

    #details-section table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    #details-section th,
    #details-section td {
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        text-align: left;
    }

    #details-section th {
        font-weight: bold;
        background-color: #f8f9fa;
    }

    #feedback-section {
        transition: opacity 0.3s ease, height 0.3s ease;
    }

    .hidden {
        display: none;
        opacity: 0;
        height: 0;
        overflow: hidden;
    }
</style>

<div class="w-90 mx-auto">
    <div class="d-flex justify-content-start mb-2">
        <p class="mb-0 text-dark">
            <a href="index.php" class="link text-dark text-decoration-none">Home</a> /
            <a href="products.php" class="link text-dark text-decoration-none">Products</a> /
            <?php echo htmlspecialchars($product['product_name'] ?? 'Product'); ?>
        </p>
    </div>
</div>

<div class="w-75 mx-auto">
    <?php if ($product): ?>
        <div class="row">
            <div class="col-md-6">
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($product['product_image'] ?: '/Fanimation/assets/images/products/default.jpg'); ?>"
                                    class="card-img-top current-image"
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                    id="main-image-<?php echo $product['product_id']; ?>"
                                    data-default-image="<?php echo htmlspecialchars($product['product_image'] ?: '/Fanimation/assets/images/products/default.jpg'); ?>">
                            </div>
                        </div>
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item">
                                <div class="image-container">
                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                        class="d-block w-100"
                                        alt="<?php echo htmlspecialchars($product['product_name']) . ' - Color ' . $image['color_id']; ?>"
                                        data-color-id="<?php echo $image['color_id']; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <p><strong>Thương hiệu:</strong> <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></p>
                <h1 class="mb-4"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                <p class="card-text fw-bold small d-flex align-items-center gap-1">
                    <?php
                    $rating = $product['average_rating'] ?? 0;
                    for ($i = 1; $i <= 5; $i++):
                        if ($i <= floor($rating)):
                    ?>
                            <i class="bi bi-star-fill text-warning"></i>
                        <?php elseif ($i - 0.5 <= $rating): ?>
                            <i class="bi bi-star-half text-warning"></i>
                        <?php else: ?>
                            <i class="bi bi-star text-warning"></i>
                    <?php endif;
                    endfor; ?>
                    <span class="ms-1"><?php echo number_format($rating, 1); ?></span>
                </p>

                <p><strong>Mã màu:</strong></p>
                <div class="color-options mb-3">
                    <?php
                    if (!empty($images)) {
                        foreach ($images as $image):
                            $color_id = $image['color_id'];
                            $color_hex = $image['hex_code'];
                            $stock = $stocks_by_color[$color_id] ?? 0;
                    ?>
                            <div class="color-circle"
                                style="background-color: <?php echo $color_hex; ?> !important;"
                                title="Color: Color ID <?php echo $color_id; ?> (Stock: <?php echo $stock; ?>)"
                                data-image="<?php echo htmlspecialchars($image['image_url']); ?>"
                                data-product-id="<?php echo $product['product_id']; ?>"
                                data-color-id="<?php echo $color_id; ?>"
                                data-stock="<?php echo $stock; ?>">
                            </div>
                        <?php endforeach;
                    } else { ?>
                        <div class='debug'>Không có màu sắc nào cho sản phẩm này.</div>
                        <input type="hidden" id="color_id" value="0">
                    <?php } ?>
                </div>
                <p><strong>Tồn kho:</strong> <span id="stock-display">Vui lòng chọn màu để xem tồn kho</span></p>
                <p><strong>Số lượng:</strong></p>
                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['total_stock'] ?? 0; ?>" class="form-control w-25 d-inline" required>

                <p><strong>Giá:</strong></p>
                <div class="fs-1 fw-bold text-danger">
                    <?php echo number_format($product['product_price'], 0, ',', '.'); ?>$
                </div>

                <button class="btn btn-danger add-to-cart"
                    data-id="<?php echo $product['product_id']; ?>">
                    MUA NGAY
                </button>
            </div>
        </div>

        <!-- Liên kết Đánh giá sản phẩm và Chi tiết sản phẩm -->
        <div class="nav-links">
            <a class="tab-link active" data-target="feedback-section">Đánh giá sản phẩm</a>
            <a class="tab-link" data-target="details-section">Chi tiết sản phẩm</a>
        </div>

        <!-- Phần Chi tiết sản phẩm -->
        <div id="details-section" class="mt-8 hidden">
            <h2 class="text-2xl font-semibold mb-4">Chi tiết sản phẩm</h2>
            <?php if ($product_details): ?>
                <table>
                    <?php if ($product_details['size']): ?>
                        <tr>
                            <th>Kích thước</th>
                            <td><?php echo htmlspecialchars($product_details['size']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['material']): ?>
                        <tr>
                            <th>Chất liệu</th>
                            <td><?php echo htmlspecialchars($product_details['material']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['motor_type']): ?>
                        <tr>
                            <th>Loại động cơ</th>
                            <td><?php echo htmlspecialchars($product_details['motor_type']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($product_details['blade_count'])): ?>
                        <tr>
                            <th>Số cánh quạt</th>
                            <td><?php echo $product_details['blade_count']; ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($product_details['light_kit_included'])): ?>
                        <tr>
                            <th>Bộ đèn</th>
                            <td><?php echo $product_details['light_kit_included'] ? 'Có' : 'Không'; ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (isset($product_details['remote_control'])): ?>
                        <tr>
                            <th>Điều khiển từ xa</th>
                            <td><?php echo $product_details['remote_control'] ? 'Có' : 'Không'; ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['airflow_cfm']): ?>
                        <tr>
                            <th>Lưu lượng gió</th>
                            <td><?php echo $product_details['airflow_cfm']; ?> CFM</td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['power_consumption']): ?>
                        <tr>
                            <th>Công suất tiêu thụ</th>
                            <td><?php echo $product_details['power_consumption']; ?> Watt</td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['warranty_years']): ?>
                        <tr>
                            <th>Bảo hành</th>
                            <td><?php echo $product_details['warranty_years']; ?> năm</td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($product_details['additional_info']): ?>
                        <tr>
                            <th>Thông tin bổ sung</th>
                            <td><?php echo htmlspecialchars($product_details['additional_info']); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            <?php else: ?>
                <p>Chưa có thông tin chi tiết cho sản phẩm này.</p>
            <?php endif; ?>
        </div>

        <!-- Phần Đánh giá sản phẩm -->
        <div id="feedback-section" class="mt-8">
            <h2 class="text-2xl font-semibold mb-4">Đánh giá sản phẩm</h2>
            <?php if (!empty($success)): ?>
                <p class='text-green-500 mb-4'><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class='text-red-500 mb-4'><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($feedback_error)): ?>
                <?php echo $feedback_error; ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Số Sao</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>" class="star"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Bình Luận</label>
                        <textarea name="message" class="form-control" rows="4" maxlength="1000" required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn btn-danger">Gửi Đánh Giá</button>
                </form>
            <?php else: ?>
                <p>Vui lòng <a href="<?php echo htmlspecialchars($login_url); ?>" class="text-blue-500">đăng nhập</a> để gửi đánh giá!</p>
            <?php endif; ?>

            <div class="mt-6">
                <?php if (empty($feedbacks)): ?>
                    <p>Chưa có đánh giá nào cho sản phẩm này.</p>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="border-b py-4">
                            <p><strong><?php echo htmlspecialchars($feedback['name'] ?? 'Ẩn danh'); ?></strong> -
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-secondary'; ?>"></i>
                                <?php endfor; ?>
                            </p>
                            <p><?php echo htmlspecialchars($feedback['message']); ?></p>
                            <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($feedback['created_at']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Sản phẩm không tồn tại hoặc đã hết hàng.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackSection = document.getElementById('feedback-section');
    const detailsSection = document.getElementById('details-section');

    document.querySelectorAll('.tab-link').forEach(link => {
        link.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');

            // Cập nhật trạng thái active cho tab
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Hiển thị/xấu nội dung
            if (targetId === 'feedback-section') {
                feedbackSection.classList.remove('hidden');
                detailsSection.classList.add('hidden');
            } else if (targetId === 'details-section') {
                detailsSection.classList.remove('hidden');
                feedbackSection.classList.add('hidden');
            }

            // Cuộn mượt đến phần nội dung
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 50,
                    behavior: 'smooth'
                });
            }
        });
    });

    const carousel = document.querySelector('#productCarousel');
    const mainImage = document.getElementById('main-image-<?php echo $product['product_id'] ?? 0; ?>');
    const defaultImage = mainImage ? mainImage.getAttribute('data-default-image') : '';
    const stockDisplay = document.getElementById('stock-display');
    const quantityInput = document.getElementById('quantity');
    const addToCartButton = document.querySelector('.add-to-cart');
    let selectedColorId = null;

    document.querySelectorAll('.color-circle').forEach(circle => {
        circle.addEventListener('click', function() {
            selectedColorId = this.getAttribute('data-color-id');
            const imageUrl = this.getAttribute('data-image');
            const stock = parseInt(this.getAttribute('data-stock')) || 0;

            // Cập nhật giao diện màu được chọn
            document.querySelectorAll('.color-circle').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            // Cập nhật hình ảnh
            if (mainImage && imageUrl) {
                mainImage.src = imageUrl;
                document.querySelectorAll('#productCarousel .carousel-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.querySelector('img').getAttribute('data-color-id') === selectedColorId) {
                        item.classList.add('active');
                    }
                });
                console.log('Selected color ID:', selectedColorId, 'Image:', imageUrl);
            }

            // Cập nhật tồn kho và số lượng tối đa
            if (stockDisplay) {
                stockDisplay.textContent = stock > 0 ? `${stock} sản phẩm có sẵn` : 'Hết hàng';
            }
            if (quantityInput) {
                quantityInput.max = stock > 0 ? stock : 1;
                if (parseInt(quantityInput.value) > stock) {
                    quantityInput.value = stock > 0 ? stock : 1;
                }
            }
        });

        circle.addEventListener('mouseover', function() {
            const imageUrl = this.getAttribute('data-image');
            if (mainImage && imageUrl) {
                mainImage.src = imageUrl;
                console.log('Hovering over color, new image src:', imageUrl);
            }
        });

        circle.addEventListener('mouseout', function() {
            if (mainImage && defaultImage && selectedColorId === null) {
                mainImage.src = defaultImage;
                console.log('Mouse out, reverting to default image:', defaultImage);
            }
        });
    });

    if (addToCartButton) {
        addToCartButton.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const quantity = document.getElementById('quantity').value;

            if (!selectedColorId || selectedColorId === '0' || selectedColorId === null) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn màu',
                    text: 'Vui lòng chọn màu sắc trước khi thêm vào giỏ hàng!'
                });
                return;
            }

            const stock = parseInt(document.querySelector('.color-circle.selected').getAttribute('data-stock'));
            if (parseInt(quantity) > stock) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi số lượng',
                    text: 'Số lượng vượt quá tồn kho!'
                });
                return;
            }

            console.log('Sending to add_to_cart:', {
                action: 'add',
                productId,
                colorId: selectedColorId,
                quantity,
                sessionId: '<?php echo htmlspecialchars($_SESSION['guest_session_id']); ?>'
            });

            fetch('/Fanimation/pages/cart/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&color_id=${selectedColorId}&quantity=${quantity}&session_id=<?php echo htmlspecialchars($_SESSION['guest_session_id']); ?>`
            })
            .then(response => {
                console.log('Response status:', response.status, 'URL:', response.url);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const cartData = JSON.parse(text);
                    console.log('Response from add_to_cart:', cartData);
                    if (cartData.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: cartData.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '/Fanimation/pages/cart/cart.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: cartData.message || 'Lỗi không xác định từ máy chủ'
                        });
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Phản hồi không phải JSON hợp lệ'
                    });
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Lỗi khi thêm vào giỏ hàng: ' + error.message
                });
            });
        });
    }

    console.log('Number of color circles:', document.querySelectorAll('.color-circle').length);
});
</script>

<?php
error_log("Received POST data: " . print_r($_POST, true));
ob_end_flush();
include $footer_url;
mysqli_close($conn);
?>
