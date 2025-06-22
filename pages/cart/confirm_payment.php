<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;

// Log debug ban đầu
$current_session_id = session_id();
error_log("Debug - confirm_payment.php: Request method = " . $_SERVER['REQUEST_METHOD'] . ", order_id = " . (isset($_POST['order_id']) ? $_POST['order_id'] : 'null') . ", CSRF token = " . (isset($_POST['csrf_token']) ? 'set' : 'not set') . ", Current session_id = $current_session_id");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method for confirm_payment.php");
    die('<div class="container mx-auto my-5 text-center text-red-500">Yêu cầu không hợp lệ.</div>');
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$csrf_token = $_POST['csrf_token'] ?? '';

if ($order_id <= 0 || !isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    error_log("Invalid order_id or CSRF token mismatch: order_id = $order_id, session CSRF = " . ($_SESSION['csrf_token'] ?? 'null') . ", posted CSRF = " . htmlspecialchars($csrf_token));
    die('<div class="container mx-auto my-5 text-center text-red-500">Yêu cầu không hợp lệ. Vui lòng thử lại.</div>');
}

// Xác định identifier
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = !$user_id ? session_id() : null;
$identifier = $user_id ? 'user_id' : 'session_id';
$identifier_value = $user_id ?: $session_id;
error_log("Debug - Identifier: $identifier = " . htmlspecialchars($identifier_value));

// Kiểm tra xem đơn hàng có tồn tại trước khi cập nhật
$check_stmt = $conn->prepare("SELECT id, payment_status, session_id FROM orders WHERE id = ? AND ($identifier = ? OR session_id = ? OR session_id IS NULL)");
if ($check_stmt === false) {
    error_log("Prepare failed (check): " . $conn->error . " (Query: SELECT id FROM orders WHERE id = $order_id AND $identifier = ?)");
    die('<div class="container mx-auto my-5 text-center text-red-500">Lỗi khi kiểm tra đơn hàng.</div>');
}
$check_stmt->bind_param('iss', $order_id, $identifier_value, $session_id); // Sửa 'iis' thành 'iss'
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$order = $check_result->fetch_assoc();

if (!$order) {
    error_log("Order not found for update: order_id = $order_id, $identifier = " . htmlspecialchars($identifier_value) . ", session_id = " . htmlspecialchars($session_id) . ", session_id in DB = " . ($order['session_id'] ?? 'NULL'));
    die('<div class="container mx-auto my-5 text-center text-red-500">Không tìm thấy đơn hàng để xác nhận.</div>');
}
error_log("Debug - Order found: payment_status = " . ($order['payment_status'] ?? 'null') . ", session_id = " . ($order['session_id'] ?? 'NULL'));

// Thực hiện cập nhật chỉ khi payment_status chưa là 'completed'
if ($order['payment_status'] !== 'completed') {
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ? AND $identifier = ? AND payment_status = 'pending'");
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " (Query: UPDATE orders WHERE id = $order_id AND $identifier = ?), Columns: " . json_encode($conn->error_list));
        die('<div class="container mx-auto my-5 text-center text-red-500">Lỗi khi cập nhật trạng thái thanh toán.</div>');
    }

    $stmt->bind_param('ii', $order_id, $identifier_value);
    if ($session_id) {
        $stmt->bind_param('is', $order_id, $identifier_value);
    }
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        error_log("Payment confirmed successfully: order_id = $order_id, Affected rows: $affected_rows");

        // Xóa các bản ghi trong bảng Carts
        $delete_stmt = $conn->prepare("DELETE FROM carts WHERE $identifier = ?");
        if ($delete_stmt === false) {
            error_log("Prepare failed (delete carts): " . $conn->error . " (Query: DELETE FROM carts WHERE $identifier = ?)");
        } else {
            $delete_stmt->bind_param($user_id ? 'i' : 's', $identifier_value);
            $delete_stmt->execute();
            error_log("Cart deleted from DB for $identifier = " . htmlspecialchars($identifier_value) . ", Affected rows: " . $delete_stmt->affected_rows);
            $delete_stmt->close();
        }

        // Xóa giỏ hàng trong session
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
            error_log("Cart cleared from session for order_id = $order_id");
        }

        unset($_SESSION['csrf_token']);
        header('Location: order_success.php?order_id=' . $order_id);
        exit;
    } else {
        error_log("Execute failed: " . $conn->error . " (Query: UPDATE orders WHERE id = $order_id, Error: " . $conn->error . ", Affected rows: " . $stmt->affected_rows);
        die('<div class="container mx-auto my-5 text-center text-red-500">Lỗi khi xác nhận thanh toán.</div>');
    }
    $stmt->close();
} else {
    error_log("Payment already confirmed for order_id = $order_id, skipping update.");
    unset($_SESSION['csrf_token']);
    header('Location: order_success.php?order_id=' . $order_id);
    exit;
}
?>