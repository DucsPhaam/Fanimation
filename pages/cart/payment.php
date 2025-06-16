<?php
ob_start(); // Bắt đầu output buffering
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;
include $header_url;

// Khởi tạo biến lỗi
$error = null;

// Kiểm tra order_id từ URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $error = "Mã đơn hàng không hợp lệ. Vui lòng kiểm tra lại.";
}

// Nếu không có lỗi, lấy thông tin đơn hàng
$order = null;
if (!$error) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    if ($stmt === false) {
        $error = "Lỗi truy vấn cơ sở dữ liệu: " . $conn->error;
        error_log("Prepare failed: " . $conn->error . " (Query: SELECT * FROM orders WHERE id = ?)");
    } else {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            $error = "Không tìm thấy đơn hàng với mã #$order_id.";
        }
        $stmt->close();
    }
}

// Nếu không có lỗi, lấy danh sách sản phẩm trong đơn hàng
$order_details = [];
if (!$error) {
    $stmt = $conn->prepare("SELECT oi.*, p.name, 
                       COALESCE((SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.u_primary = 1 LIMIT 1), 
                                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id LIMIT 1)) AS image_url 
                       FROM order_items oi 
                       JOIN product_variants pv ON oi.product_variant_id = pv.id 
                       JOIN products p ON pv.product_id = p.id 
                       WHERE oi.order_id = ?");
    if ($stmt === false) {
        $error = "Lỗi truy vấn chi tiết đơn hàng: " . $conn->error;
        error_log("Prepare failed: " . $conn->error . " (Query: SELECT oi.*, p.name, ... FROM order_items oi ...)");
    } else {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $order_details[] = $row;
        }
        if (empty($order_details)) {
            $error = "Không tìm thấy chi tiết đơn hàng với mã #$order_id.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Thanh Toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="container mx-auto my-5 w-75">
        <h1 class="text-3xl font-bold text-center mb-5">Thông Tin Thanh Toán</h1>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Lỗi:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-2xl font-semibold mb-4">Thông Tin Đơn Hàng</h2>
                    <p><strong>Mã Đơn Hàng:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
                    <p><strong>Họ Tên:</strong> <?php echo htmlspecialchars($order['fullname']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Số Điện Thoại:</strong> <?php echo htmlspecialchars($order['phone_number']); ?></p>
                    <p><strong>Địa Chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>Ghi Chú:</strong> <?php echo htmlspecialchars($order['note'] ?: 'Không có ghi chú'); ?></p>
                    <p><strong>Tổng Tiền:</strong> <?php echo number_format($order['total_money'], 0, '', '.'); ?> $</p>
                    <p><strong>Trạng Thái Thanh Toán:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                </div>
                <div>
                    <h2 class="text-2xl font-semibold mb-4">Danh Sách Sản Phẩm</h2>
                    <?php foreach ($order_details as $item): ?>
                        <div class="flex items-center mb-4">
                            <img src="<?php echo '../' . htmlspecialchars($item['image_url'] ?? 'assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-16 w-16 object-cover mr-4">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p>Số lượng: <?php echo $item['quantity']; ?></p>
                                <p>Giá: <?php echo number_format($item['price'], 0, '', '.'); ?> $</p>
                                <p>Tổng: <?php echo number_format($item['total_money'], 0, '', '.'); ?> $</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Phần Thông Tin Chuyển Khoản -->
            <div class="mt-6">
                <h2 class="text-2xl font-semibold mb-4">Thông Tin Chuyển Khoản</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p><strong>Ví điện tử:</strong> Viettel Money</p>
                        <p><strong>Số Tài Khoản:</strong> 0231253646</p>
                        <p><strong>Chủ Tài Khoản:</strong> Vu Cong Thanh</p>
                        <p><strong>Nội Dung Chuyển Khoản:</strong> Thanh toán đơn hàng #<?php echo htmlspecialchars($order['id']); ?></p>
                        <p class="text-red-500 mt-2">Vui lòng chuyển khoản số tiền <strong><?php echo number_format($order['total_money'], 0, '', '.'); ?> $</strong> để hoàn tất đơn hàng.</p>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Quét mã QR để thanh toán</h3>
                        <img src="../../assets/images/bank.jpg" alt="QR Code Thanh Toán" class="mx-auto h-48 w-48 object-contain">
                        <p class="text-gray-600 mt-2">Sử dụng ứng dụng ngân hàng để quét mã QR và thanh toán.</p>
                    </div>
                </div>
            </div>

            <!-- Phần Xác Nhận Thanh Toán -->
            <div class="mt-6 text-center">
                <form method="POST" action="confirm_payment.php" id="paymentForm">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id'] ?? ''); ?>">
                    <?php
                    if (!isset($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded" id="confirmPayment">Xác Nhận Thanh Toán</button>
                </form>
                <p class="text-red-500 mt-2">Lưu ý: Chỉ nhấn xác nhận khi đã hoàn tất chuyển khoản.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php
    if (!defined('FOOTER_INCLUDED')) {
        define('FOOTER_INCLUDED', true);
        include $footer_url;
    }
    ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#confirmPayment').click(function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Xác nhận thanh toán',
                    text: 'Bạn có chắc chắn đã chuyển khoản và muốn xác nhận thanh toán cho đơn hàng này?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Đang xử lý...',
                            text: 'Vui lòng chờ trong giây lát.',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                $('#paymentForm').submit();
                            }
                        });
                    }
                });
            });
        });
    </script>

    <?php
    ob_end_flush(); // Kết thúc và gửi bộ đệm đầu ra
    ?>