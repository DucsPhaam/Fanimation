<?php
ob_start(); // Bắt đầu output buffering

include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include_once $function_url;
include $header_url;

// Xác định identifier dựa trên trạng thái đăng nhập
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$session_id = !$user_id ? session_id() : null;
$identifier = $user_id ? 'user_id' : 'session_id';
$identifier_value = $user_id ?: $session_id;

// Lấy thông tin người dùng nếu đã đăng nhập
$user_info = [];
if ($user_id) {
    $user_query = "SELECT name, email, phone AS phone, address FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " (Query: $user_query)");
    } else {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_info = $result->fetch_assoc() ?: [];
        $stmt->close();
    }
}

// Sử dụng session_id làm điều kiện chính
$cart_items = [];
$total = 0;
$is_buy_now = isset($_GET['buy_now']);
$checkout_items = isset($_GET['items']) ? explode(',', $_GET['items']) : [];

if ($is_buy_now) {
    // Chế độ "Mua ngay"
    $product_id = isset($_GET['buy_now']) ? intval($_GET['buy_now']) : 0;
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    $color_id = isset($_GET['color_id']) ? intval($_GET['color_id']) : null;

    $query = "SELECT p.id AS product_id, p.name, p.price, 
              COALESCE((SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.u_primary = 1 LIMIT 1), 
                       (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id LIMIT 1)) AS image_url
              FROM Products p 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<pre>Debug Error - Prepare failed: " . $conn->error . " (Query: $query)</pre>";
        error_log("Prepare failed: " . $conn->error . " (Query: $query)");
    } else {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            $product['quantity'] = $quantity;
            $product['color_id'] = $color_id;
            $cart_items[] = $product;
            $total = $product['price'] * $quantity;
        }
        $stmt->close();
    }
} else {
    // Chế độ giỏ hàng
    if (!empty($checkout_items)) {
        $ids = implode(',', array_map('intval', $checkout_items));

        // Debug: Kiểm tra dữ liệu trong bảng Carts
        $condition = $user_id ? "user_id = ?" : "session_id = ?";
        $param = $user_id ?: $session_id;
        $debug_query = "SELECT * FROM Carts WHERE $condition AND id IN ($ids)";
        $stmt_debug = $conn->prepare($debug_query);
        if ($stmt_debug === false) {
            echo "<pre>Debug Error - Debug Prepare failed: " . $conn->error . " (Query: $debug_query)</pre>";
            error_log("Debug Prepare failed: " . $conn->error . " (Query: $debug_query)");
        } else {
            $type = $user_id ? 'i' : 's';
            $stmt_debug->bind_param($type, $param);
            $stmt_debug->execute();
            $debug_result = $stmt_debug->get_result();
            $cart_records = $debug_result->fetch_all(MYSQLI_ASSOC);
            $stmt_debug->close();
        }

        // Truy vấn lấy dữ liệu thông qua product_variant_id
        $condition = $user_id ? "c.user_id = ?" : "c.session_id = ?";
        $query = "SELECT c.id AS cart_id, c.product_variant_id, pv.product_id, p.name, p.price, 
                  COALESCE((SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.u_primary = 1 LIMIT 1), 
                           (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id LIMIT 1)) AS image_url,
                  c.quantity, pv.color_id, COALESCE((SELECT hex_code FROM Colors c2 WHERE c2.id = pv.color_id LIMIT 1), '#000000') AS color_hex
                  FROM Carts c 
                  LEFT JOIN product_variants pv ON c.product_variant_id = pv.id
                  LEFT JOIN Products p ON pv.product_id = p.id 
                  WHERE $condition AND c.id IN ($ids)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
        } else {
            $type = $user_id ? 'i' : 's';
            $stmt->bind_param($type, $param);
            $stmt->execute();
            $result = $stmt->get_result();
            $row_count = $result->num_rows;
            while ($row = $result->fetch_assoc()) {
                if ($row['name']) { // Chỉ kiểm tra name, vì product_variant_id có thể NULL hợp lệ
                    $cart_items[] = $row;
                    $total += $row['price'] * $row['quantity'];
                } else {
                    echo "<pre>Debug Info - Invalid row skipped due to missing name: " . print_r($row, true) . "</pre>";
                    error_log("Invalid row skipped due to missing name: " . print_r($row, true));
                }
            }
            $stmt->close();
        }
    } else {
        echo "<pre>Debug Info - Checkout items is empty or invalid from URL.</pre>";
        error_log("Checkout items is empty or invalid from URL.");
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật: CSRF token không hợp lệ!";
        echo "<pre>Debug Error - CSRF Validation Failed: Form Token: " . ($_POST['csrf_token'] ?? 'Not set') . ", Session Token: " . ($_SESSION['csrf_token'] ?? 'Not set') . "</pre>";
        error_log("CSRF Validation Failed: Form Token: " . ($_POST['csrf_token'] ?? 'Not set') . ", Session Token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
    } else {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);

        // Kiểm tra email
        $email_pattern = '/^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)*(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|aol\.com|example\.com)$/';
        if (!preg_match($email_pattern, $email)) {
            $errors[] = "Email không hợp lệ! Chỉ chấp nhận các domain: gmail.com, yahoo.com, hotmail.com, outlook.com, aol.com, example.com.";
        }

        // Kiểm tra số điện thoại
        $phone_pattern = '/^0[0-9]{9,10}$/';
        if (!preg_match($phone_pattern, $phone)) {
            $errors[] = "Số điện thoại không hợp lệ! Vui lòng nhập số có 10-11 chữ số, bắt đầu bằng 0.";
        }

        if (empty($errors)) {
            $conn->begin_transaction(); // Bắt đầu giao dịch
            try {
                $query = "INSERT INTO orders (user_id, session_id, fullname, email, phone_number, address, note, total_money, status, payment_status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Lỗi prepare: " . $conn->error);
                }
                $stmt->bind_param('isssssss', $user_id, $session_id, $fullname, $email, $phone, $address, $note, $total);
                if (!$stmt->execute()) {
                    throw new Exception("Lỗi khi tạo đơn hàng: " . $conn->error);
                }
                $order_id = $conn->insert_id;
                $stmt->close();

                // Thêm order_items
                foreach ($cart_items as $item) {
                    $price = $item['price'] - ($item['discount'] ?? 0);
                    $subtotal = $price * $item['quantity'];
                    $product_variant_id = $item['product_variant_id'] ?? null; // Sử dụng product_variant_id thay vì product_id
                    $query = "INSERT INTO order_items (order_id, product_variant_id, quantity, price, total_money, payment_method) 
                              VALUES (?, ?, ?, ?, ?, 'online')";
                    $stmt_detail = $conn->prepare($query);
                    if ($stmt_detail === false) {
                        throw new Exception("Lỗi prepare order_items: " . $conn->error);
                    }
                    $stmt_detail->bind_param('iiidd', $order_id, $product_variant_id, $item['quantity'], $price, $subtotal); // Sửa 'iidd' thành 'iiidd'
                    $stmt_detail->execute();
                    $stmt_detail->close();
                }

                // Cập nhật tồn kho
                $stmt = $conn->prepare("SELECT product_variant_id, quantity FROM order_items WHERE order_id = ?");
                if ($stmt === false) {
                    throw new Exception("Lỗi truy vấn chi tiết đơn hàng: " . $conn->error);
                }
                $stmt->bind_param('i', $order_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $order_items = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                foreach ($order_items as $item) {
                    $variant_id = $item['product_variant_id'];
                    $quantity = $item['quantity'];
                    $stmt = $conn->prepare("SELECT stock FROM product_variants WHERE id = ? FOR UPDATE");
                    if ($stmt === false) {
                        throw new Exception("Lỗi truy vấn tồn kho: " . $conn->error);
                    }
                    $stmt->bind_param('i', $variant_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $variant = $result->fetch_assoc();
                    $stmt->close();

                    if (!$variant || $variant['stock'] < $quantity) {
                        throw new Exception("Sản phẩm (variant_id: $variant_id) không đủ tồn kho. Hiện tại: " . ($variant['stock'] ?? 0) . ", yêu cầu: $quantity.");
                    }

                    $stmt = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
                    if ($stmt === false) {
                        throw new Exception("Lỗi cập nhật tồn kho: " . $conn->error);
                    }
                    $stmt->bind_param('ii', $quantity, $variant_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Xóa Carts nếu không phải buy_now
                if (!$is_buy_now && !empty($checkout_items)) {
                    $ids = implode(',', array_map('intval', $checkout_items));
                    $condition = $user_id ? "user_id = ?" : "session_id = ?";
                    $param = $user_id ?: $session_id;
                    $type = $user_id ? 'i' : 's';
                    $query = "DELETE FROM Carts WHERE $condition AND id IN ($ids)";
                    $stmt = $conn->prepare($query);
                    if ($stmt === false) {
                        throw new Exception("Lỗi prepare xóa Carts: " . $conn->error);
                    }
                    $stmt->bind_param($type, $param);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit(); // Hoàn tất giao dịch
                unset($_SESSION['csrf_token']);
                unset($_SESSION['checkout_items']);
                ob_end_clean(); // Xóa bộ đệm trước khi redirect
                header('Location: payment.php?order_id=' . $order_id);
                exit;
            } catch (Exception $e) {
                $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
                $errors[] = "Lỗi khi xử lý đơn hàng: " . $e->getMessage();
                error_log("Checkout failed: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/eProject%201/F/assets/css/style.css"> <!-- Thay đổi đường dẫn phù hợp -->
</head>

<body>
    <div class="container mx-auto my-5">
        <h1 class="text-3xl font-bold text-center mb-5">Thanh Toán</h1>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-semibold mb-4">Thông Tin Đặt Hàng</h2>
                <form method="POST">
                    <?php
                    // Đảm bảo CSRF token được tạo và lưu vào session
                    if (!isset($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Họ Tên</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số Điện Thoại</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa Chỉ</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user_info['address'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Ghi Chú</label>
                        <textarea name="note" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded w-full">Xác Nhận Đặt Hàng</button>
                </form>
            </div>
            <div>
                <h2 class="text-2xl font-semibold mb-4">Tóm tắt Đơn Hàng</h2>
                <?php if (empty($cart_items)): ?>
                    <p class="text-center text-red-500">Không có sản phẩm nào trong đơn hàng.</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item):
                        $color_hex = $item['color_hex'] ?? '#000000';
                        $image_url = $item['image_url'] ?? '';
                    ?>
                        <div class="flex items-center mb-4">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Product" class="h-16 w-16 object-cover mr-4">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($item['name'] ?? 'Tên sản phẩm không có'); ?></p>
                                <p>Số lượng: <?php echo $item['quantity'] ?? 0; ?></p>
                                <p>Màu: <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo htmlspecialchars($color_hex); ?>;"></span></p>
                                <p><?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 0, '', '.'); ?> VNĐ</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="text-xl font-bold">Tổng cộng: <?php echo number_format($total, 0, '', '.'); ?> VNĐ</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    ob_end_flush(); // Kết thúc và gửi bộ đệm đầu ra
    include $footer_url;
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/eProject%201/F/assets/js/main.js"></script> <!-- Thay đổi đường dẫn phù hợp -->
</body>

</html>