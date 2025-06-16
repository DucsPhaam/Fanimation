<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : $user['name'];
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : $user['email'];
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : $user['phone'];
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : $user['address'];
    $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : $user['city'];
    $password = isset($_POST['password']) && !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : null;

    // Kiểm tra dữ liệu đầu vào
    if (empty($name) || empty($email)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tên và email không được để trống']);
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

    // Kiểm tra độ dài mật khẩu nếu có nhập
    if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự']);
        ob_end_flush();
        exit;
    }

    // Kiểm tra email đã tồn tại (ngoại trừ email của chính người dùng)
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
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

    // Cập nhật thông tin người dùng
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?";
    $params = [$name, $email, $phone, $address, $city];
    $types = 'sssss';

    if ($password) {
        $sql .= ", password = ?";
        $params[] = $password;
        $types .= 's';
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= 'i';

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
        ob_end_flush();
        exit;
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    ob_end_flush();
    exit;
}

?>
<style>
    /* Tăng chiều cao input và bo góc */
    input, select, textarea {
        padding: 5px;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    input:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.4);
        border-color: #6366f1;
    }

    .form-container {
        max-width: 600px;
        background-color: #fff;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        margin: auto;
    }

    .form-title {
        font-size: 1.75rem;
        font-weight: bold;
        margin-bottom: 1.5rem;
        text-align: center;
        color: #1e293b;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #374151;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-submit {
        background-color: #4f46e5;
        color: white;
        padding: 0.75rem;
        font-weight: bold;
        border-radius: 0.75rem;
        width: 100%;
        transition: background-color 0.3s ease;
    }

    .form-submit:hover {
        background-color: #4338ca;
    }

    #message {
        margin-top: 1rem;
        font-weight: 600;
    }
</style>

<div class="container mx-auto p-6">
    <h1 class="form-title">Chỉnh sửa thông tin cá nhân</h1>
    <form id="profileForm" class="form-container">
        <div class="form-group">
            <label for="name" class="form-label">Họ và tên</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full border border-gray-300">
        </div>
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border border-gray-300">
        </div>
        <div class="form-group">
            <label for="phone" class="form-label">Số điện thoại</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full border border-gray-300">
        </div>
        <div class="form-group">
            <label for="address" class="form-label">Địa chỉ</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" class="w-full border border-gray-300">
        </div>
        <div class="form-group">
            <label for="city" class="form-label">Thành phố</label>
            <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" class="w-full border border-gray-300">
        </div>
        <div class="form-group">
            <label for="password" class="form-label">Mật khẩu mới (để trống nếu không muốn thay đổi)</label>
            <input type="password" id="password" name="password" class="w-full border border-gray-300">
        </div>
        <button type="submit" class="form-submit">Cập nhật</button>
    </form>
    <div id="message" class="text-center"></div>
</div>

    <script>
        document.getElementById('profileForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                const messageDiv = document.getElementById('message');
                messageDiv.textContent = result.message;
                messageDiv.className = result.success ? 'text-green-600' : 'text-red-600';
                if (result.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                document.getElementById('message').textContent = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
                document.getElementById('message').className = 'text-red-600';
            }
        });
    </script>
