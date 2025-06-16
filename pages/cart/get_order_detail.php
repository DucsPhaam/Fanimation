<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';

// Bật hiển thị lỗi để debug (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $db_connect_url;

header('Content-Type: application/json; charset=UTF-8');

// Kiểm tra kết nối database
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database: ' . mysqli_connect_error()]);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = !$user_id ? session_id() : null;
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ']);
    exit;
}

// Kiểm tra đơn hàng
$condition = $user_id ? "o.user_id = ?" : "o.session_id = ?";
$sql = "SELECT o.created_at, o.status, o.total_money, o.payment_status 
        FROM orders o 
        WHERE o.id = ? AND $condition";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    exit;
}

$param_type = $user_id ? 'ii' : 'is';
$bind_value = $user_id ?: $session_id;
mysqli_stmt_bind_param($stmt, $param_type, $order_id, $bind_value);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    mysqli_stmt_close($stmt);
    exit;
}
mysqli_stmt_close($stmt);

// Lấy chi tiết đơn hàng
$sql = "SELECT oi.quantity, oi.price, oi.total_money, p.name 
        FROM order_items oi 
        JOIN product_variants pv ON oi.product_variant_id = pv.id 
        JOIN products p ON pv.product_id = p.id 
        WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn chi tiết: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'items' => $items,
    'total_money' => (float)$order['total_money'],
    'status' => $order['status'],
    'payment_status' => $order['payment_status'],
    'created_at' => date('d/m/Y H:i', strtotime($order['created_at']))
]);

mysqli_close($conn);
?>