<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;
include $header_url;
// Xác định identifier dựa trên trạng thái đăng nhập
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = !$user_id ? session_id() : null;
$identifier = $user_id ? 'user_id' : 'session_id';
$identifier_value = $user_id ?: $session_id;

// Log giá trị để debug
error_log("Debug - order_success.php: user_id = $user_id, session_id = $session_id, order_id = " . (isset($_GET['order_id']) ? $_GET['order_id'] : 'null'));

// Kiểm tra order_id từ URL
if (!isset($_GET['order_id'])) {
    die('<div class="container mx-auto my-5 text-center text-red-500">Không tìm thấy mã đơn hàng.</div>');
}
$order_id = (int)$_GET['order_id'];

// Kiểm tra thông tin đơn hàng
$condition = $user_id ? "user_id = ?" : "session_id = ?";
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND $condition");
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error . " (Query: SELECT id FROM orders WHERE id = $order_id AND $condition = '$identifier_value')");
    die('<div class="container mx-auto my-5 text-center text-red-500">Lỗi khi truy vấn cơ sở dữ liệu.</div>');
}
$stmt->bind_param('ii', $order_id, $identifier_value);
if ($session_id) {
    $stmt->bind_param('is', $order_id, $identifier_value);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Order not found: order_id = $order_id, $identifier = " . htmlspecialchars($identifier_value));
    die('<div class="container mx-auto my-5 text-center text-red-500">Không tìm thấy đơn hàng với mã #' . htmlspecialchars($order_id) . '.</div>');
}
$stmt->close();
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
        <h1 class="text-3xl font-bold mb-5">Đặt Hàng Thành Công</h1>
        <p class="mb-4 text-green-500">Cảm ơn bạn đã đặt hàng! Đơn hàng #<?php echo htmlspecialchars($order_id); ?> đã được ghi nhận.</p>
        <p class="mb-4">Chúng tôi sẽ xử lý và giao hàng sớm nhất có thể.</p>
        <a href="<?php echo $index_url; ?>" class="bg-blue-500 text-white px-4 py-2 rounded">Quay Lại Trang Chủ</a>
    </div>
    <?php include $footer_url; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            updateCartCount();
            $('.cart-icon').css('z-index', '1000');
            // Đảm bảo giỏ hàng được làm mới
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        });

        
    </script>
</body>
</html>
