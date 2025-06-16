<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
// Đảm bảo không có khoảng trắng trước <?php
session_start();
require_once $db_connect_url;

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đặt header JSON


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

// Kiểm tra kết nối database
if (!$conn) {
    error_log("Lỗi kết nối database: " . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database: ' . mysqli_connect_error()]);
    exit;
}

$sql = "SELECT id, name, password, role FROM Users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("Lỗi mysqli_prepare: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn database: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $user['password'])) {
        // Lưu thông tin người dùng vào session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Xử lý "Remember me"
        if ($remember) {
            $token = bin2hex(random_bytes(16));
            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->bind_param("si", $token, $user['id']);
            setcookie('remember_token', $token, time() + (86400 * 30), '/');
            // Lưu token vào database nếu cần (cần bảng user_tokens)
        }

        echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;