<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
// Bật hiển thị lỗi để debug (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $db_connect_url;

header('Content-Type: application/json; charset=UTF-8');

$identifier = isset($_GET['identifier']) ? $_GET['identifier'] : '';
$identifier_value = isset($_GET['identifier_value']) ? $_GET['identifier_value'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if (empty($identifier) || empty($identifier_value)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin định danh']);
    exit;
}

$sql = "SELECT o.id, o.created_at, o.status, o.total_money, o.payment_status 
        FROM orders o 
        WHERE o.$identifier = ? 
        ORDER BY o.created_at ASC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    exit;
}

$param_type = (is_numeric($identifier_value) ? 'iii' : 'isi');
mysqli_stmt_bind_param($stmt, $param_type, $identifier_value, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        'id' => $row['id'],
        'created_at_formatted' => date('d/m/Y H:i', strtotime($row['created_at'])),
        'status' => $row['status'],
        'total_money' => floatval($row['total_money']),
        'payment_status' => $row['payment_status']
    ];
}
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'orders' => $orders]);

mysqli_close($conn);
?>