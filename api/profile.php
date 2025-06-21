<?php
// Kiểm tra trạng thái phiên để tránh trùng session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Bật debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

// Debug phiên
error_log('API Session status: ' . session_status());
error_log('API Session data: ' . print_r($_SESSION, true));
error_log('API Session ID: ' . session_id());

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error()]);
    ob_end_flush();
    exit;
}

// Kiểm tra phiên đăng nhập
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    ob_end_flush();
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$sql = "SELECT name, email, phone, address, city FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
    ob_end_flush();
    exit;
}

$name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : $user['name'];
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : $user['email'];
$phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : $user['phone'];
$address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : $user['address'];
$city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : $user['city'];
$password = isset($_POST['password']) && !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : null;

// Kiểm tra dữ liệu đầu vào
if (empty($name) || empty($email)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Tên và email không được để trống']);
    ob_end_flush();
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    ob_end_flush();
    exit;
}

if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự']);
    ob_end_flush();
    exit;
}

// Kiểm tra email trùng
$sql = "SELECT id FROM users WHERE email = ? AND id != ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
    mysqli_stmt_close($stmt);
    ob_end_flush();
    exit;
}
mysqli_stmt_close($stmt);

// Cập nhật thông tin
$sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?";
$params = [$name, $email, $phone, $address, $city];
$types = 'sssss';

if ($password !== null) {
    $sql .= ", password = ?";
    $params[] = $password;
    $types .= 's';
}

$sql .= " WHERE id = ?";
$params[] = $user_id;
$types .= 'i';

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}

mysqli_stmt_bind_param($stmt, 'sssssi', ...$params);

if (mysqli_stmt_execute($stmt)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
} else {
    error_log('Lỗi cập nhật: ' . mysqli_stmt_error($stmt));
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
ob_end_flush();
exit;
?>