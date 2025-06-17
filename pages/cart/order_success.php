<?php
ob_start(); // Start output buffering
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;
include $header_url;

// Initialize error variable
$error = null;

// Determine identifier based on login status
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = !$user_id ? session_id() : null;
$identifier = $user_id ? 'user_id' : 'session_id';
$identifier_value = $user_id ?: $session_id;

// Log for debugging
error_log("Debug - order_success.php: user_id = $user_id, session_id = $session_id, order_id = " . (isset($_GET['order_id']) ? $_GET['order_id'] : 'null'));

// Check order_id from URL
if (!isset($_GET['order_id'])) {
    $error = "Không tìm thấy mã đơn hàng.";
}

// Validate order_id
$order_id = (int)$_GET['order_id'];
if ($order_id <= 0) {
    $error = "Mã đơn hàng không hợp lệ.";
}

// Verify order exists and belongs to the user or session
if (!$error) {
    $condition = $user_id ? "user_id = ?" : "session_id = ?";
    $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND $condition");
    if ($stmt === false) {
        $error = "Lỗi khi truy vấn cơ sở dữ liệu.";
        error_log("Prepare failed: " . $conn->error . " (Query: SELECT id, status FROM orders WHERE id = $order_id AND $condition = '$identifier_value')");
    } else {
        if ($session_id) {
            $stmt->bind_param('is', $order_id, $identifier_value);
        } else {
            $stmt->bind_param('ii', $order_id, $identifier_value);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        if (!$order) {
            $error = "Không tìm thấy đơn hàng với mã #$order_id.";
            error_log("Order not found: order_id = $order_id, $identifier = " . htmlspecialchars($identifier_value));
        }
        $stmt->close();
    }
}

// Update stock if order is valid and in 'pending' status
if (!$error && $order['status'] === 'pending') {
    // Start transaction
    $conn->begin_transaction();
    try {
        // Fetch order items
        $stmt = $conn->prepare("SELECT product_variant_id, quantity FROM order_items WHERE order_id = ?");
        if ($stmt === false) {
            throw new Exception("Lỗi truy vấn chi tiết đơn hàng: " . $conn->error);
        }
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($order_items)) {
            throw new Exception("Không tìm thấy chi tiết đơn hàng với mã #$order_id.");
        }

        // Check and update stock for each item
        foreach ($order_items as $item) {
            $variant_id = $item['product_variant_id'];
            $quantity = $item['quantity'];

            // Check current stock
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

            // Update stock
            $stmt = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Lỗi cập nhật tồn kho: " . $conn->error);
            }
            $stmt->bind_param('ii', $quantity, $variant_id);
            $stmt->execute();
            $stmt->close();
        }

        // Update order status to 'processing'
        $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Lỗi cập nhật trạng thái đơn hàng: " . $conn->error);
        }
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $stmt->close();

        // Clear cart for the user or session
        $stmt = $conn->prepare("DELETE FROM carts WHERE $identifier = ?");
        if ($stmt === false) {
            throw new Exception("Lỗi xóa giỏ hàng: " . $conn->error);
        }
        if ($session_id) {
            $stmt->bind_param('s', $identifier_value);
        } else {
            $stmt->bind_param('i', $identifier_value);
        }
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Lỗi khi xử lý đơn hàng: " . $e->getMessage();
        error_log("Stock update failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Hàng Thành Công</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mx-auto my-5 text-center">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Lỗi:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php else: ?>
            <h1 class="text-3xl font-bold mb-5">Đặt Hàng Thành Công</h1>
            <p class="mb-4 text-green-500">Cảm ơn bạn đã đặt hàng! Đơn hàng #<?php echo htmlspecialchars($order_id); ?> đã được ghi nhận.</p>
            <p class="mb-4">Chúng tôi sẽ xử lý và giao hàng sớm nhất có thể.</p>
            <a href="<?php echo $index_url; ?>" class="bg-blue-500 text-white px-4 py-2 rounded">Quay Lại Trang Chủ</a>
        <?php endif; ?>
    </div>
    <?php
    if (!defined('FOOTER_INCLUDED')) {
        define('FOOTER_INCLUDED', true);
        include $footer_url;
    }
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            $('.cart-icon').css('z-index', '1000');
        });
    </script>
</body>
</html>

<?php
ob_end_flush(); // Flush output buffer
mysqli_close($conn);
?>
