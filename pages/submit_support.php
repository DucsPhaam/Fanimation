<?php
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
error_log('Support Session status: ' . session_status());
error_log('Support Session data: ' . print_r($_SESSION, true));
error_log('Support Session ID: ' . session_id());

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error()]);
    ob_end_flush();
    exit;
}

// Kiểm tra dữ liệu đầu vào
$name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
$phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
$address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : '';
$product_name = isset($_POST['product_name']) && !empty($_POST['product_name']) ? mysqli_real_escape_string($conn, trim($_POST['product_name'])) : null;
$description = isset($_POST['description']) ? mysqli_real_escape_string($conn, trim($_POST['description'])) : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($description)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc']);
    ob_end_flush();
    exit;
}

// Kiểm tra định dạng số điện thoại Việt Nam
$phonePattern = '/^(0[35789][0-9]{8,9})$/';
if (!preg_match($phonePattern, $phone)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (10 hoặc 11 chữ số, bắt đầu bằng 03, 05, 07, 08, hoặc 09).']);
    ob_end_flush();
    exit;
}

// Kiểm tra định dạng email
$emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
if (!preg_match($emailPattern, $email)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ. Vui lòng nhập đúng định dạng email (ví dụ: example@domain.com).']);
    ob_end_flush();
    exit;
}

// Kiểm tra email trùng lặp
$check_email_query = "SELECT email FROM contacts WHERE email = ?";
$stmt = mysqli_prepare($conn, $check_email_query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng. Vui lòng chọn email khác.']);
    mysqli_stmt_close($stmt);
    ob_end_flush();
    exit;
}
mysqli_stmt_close($stmt);

// Kiểm tra user_id hợp lệ
if ($user_id) {
    $check_user_query = "SELECT id FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) == 0) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'ID người dùng không hợp lệ.']);
        mysqli_stmt_close($stmt);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Xử lý file upload
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/Uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$files = [];
if (!empty($_FILES['files'])) {
    $valid_extensions = ['image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'video/mp4', 'video/heic', 'video/hevc'];
    $max_size = 39 * 1024 * 1024;

    foreach ($_FILES['files']['name'] as $index => $name) {
        $file_tmp = $_FILES['files']['tmp_name'][$index];
        $file_size = $_FILES['files']['size'][$index];
        $file_type = $_FILES['files']['type'][$index];

        if (in_array($file_type, $valid_extensions) && $file_size <= $max_size) {
            $file_name = uniqid() . '_' . basename($name);
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($file_tmp, $file_path)) {
                $files[] = $file_name;
            } else {
                ob_clean();
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Lỗi khi di chuyển file: ' . $name]);
                ob_end_flush();
                exit;
            }
        } else {
            ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => "File $name không được hỗ trợ hoặc vượt quá 39MB!"]);
            ob_end_flush();
            exit;
        }
    }
}

// Lưu thông tin vào database
$query = "INSERT INTO contacts (name, email, user_id, phone, address, product_name, file_path, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi chuẩn bị câu truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}

$files_json = json_encode($files);
mysqli_stmt_bind_param($stmt, "ssisssss", $name, $email, $user_id, $phone, $address, $product_name, $files_json, $description);

if (mysqli_stmt_execute($stmt)) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'message' => 'Phản hồi của bạn đã được gửi thành công!']);
} else {
    error_log('Lỗi lưu yêu cầu hỗ trợ: ' . mysqli_stmt_error($stmt));
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi phản hồi: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
ob_end_flush();
exit;
?>
