<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Lấy thông tin người dùng từ profile.php
global $user;
if (!isset($user)) {
    // Nếu $user không được định nghĩa, lấy từ database
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name, email, phone, address, city FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}
?>
<style>
    /* Tăng chiều cao input và bo góc */
    input,
    select,
    textarea {
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
    <form id="profileForm" class="form-container" method="POST">
        <div class="form-group">
            <label for="name" class="form-label">Họ và tên</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="w-full border border-gray-300" required>
        </div>
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full border border-gray-300" required>
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

<!-- Thêm SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const response = await fetch('/Fanimation/api/profile.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Gửi cookie
        });
        const text = await response.text();
        console.log('Phản hồi từ server:', text); // Debug
        const result = JSON.parse(text);

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: result.message,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: result.message,
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Lỗi:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: 'Đã có lỗi xảy ra: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
});
</script>
