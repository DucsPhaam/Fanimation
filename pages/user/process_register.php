<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
// Bật hiển thị lỗi để debug (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đảm bảo không có khoảng trắng hoặc đầu ra trước khi gửi JSON
ob_start();

session_start();
require_once $db_connect_url;

// Đảm bảo yêu cầu là POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    ob_end_flush();
    exit;
}

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error()]);
    ob_end_flush();
    exit;
}

// Lấy và xử lý dữ liệu đầu vào
$name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : '';
$role = in_array($_POST['role'] ?? '', ['customer']) ? $_POST['role'] : 'customer';

// Kiểm tra dữ liệu đầu vào
if (empty($name) || empty($email) || empty($_POST['password'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    ob_end_flush();
    exit;
}

// Kiểm tra độ dài mật khẩu
if (strlen($_POST['password']) < 8) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự']);
    ob_end_flush();
    exit;
}

// Kiểm tra định dạng email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    ob_end_flush();
    exit;
}

// Kiểm tra email đã tồn tại
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
    mysqli_stmt_close($stmt);
    ob_end_flush();
    exit;
}
mysqli_stmt_close($stmt);

// Thêm người dùng mới
$sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $password, $role);

if (mysqli_stmt_execute($stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Đăng ký thành công!']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi đăng ký: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
ob_end_flush();
exit;
?>