<?php
require_once 'includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

<div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Chỉnh sửa thông tin cá nhân</h1>
        <form id="profileForm" class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Họ và tên</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="city" class="block text-sm font-medium text-gray-700">Thành phố</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu mới (để trống nếu không muốn thay đổi)</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Cập nhật</button>
        </form>
        <div id="message" class="mt-4 text-center"></div>
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