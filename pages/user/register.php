<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

if (isset($_SESSION['user_id'])) {
    header("Location: ". $index_url);
    exit;
}
include $header_url;
?>
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
    function enableSubmitBtn(){
        document.getElementById("submitBtn").disabled = false;
    }
</script>
<div class="container my-5 justify-content-center">
    <div class="form-container">
        <h2 class="mb-3 fw-bold fs-2 text-center">ĐĂNG KÝ TÀI KHOẢN</h2>
        <div id="error-message" class="alert alert-danger d-none"></div>
        <form id="register-form" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Họ và tên</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập họ và tên" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Nhập email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Nhập mật khẩu" required>
            </div>
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LfscForAAAAAEWbB1cS4Qd2bhADcJoxwZjT0xz7" data-callback="enableSubmitBtn"></div>
            </div>
            <button type="submit" class="btn btn-danger" id="submitBtn" disabled="disabled"><span class="fw-bold fs-6">ĐĂNG KÝ</span></button>
            <p class="mt-3 text-center">Đã có tài khoản? <a href="login.php" class="text-danger">Đăng nhập</a></p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/register.js"></script>

<?php
include $footer_url;
?>