<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Nếu đã đăng nhập, chuyển hướng đến cart.php
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
        <h2 class="mb-3 fw-bold fs-2 text-center">ĐĂNG NHẬP</h2>
        <div id="error-message" class="alert alert-danger d-none"></div>
        <form id="login-form" method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Nhập email của bạn" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu của bạn" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="form-check-label">
                    <input type="checkbox" name="remember" class="form-check-input"> Ghi nhớ tôi
                </label>
                <p class="mb-0">Chưa có tài khoản? <a href="register.php" class="text-danger">Đăng ký</a></p>
            </div>
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LfscForAAAAAEWbB1cS4Qd2bhADcJoxwZjT0xz7" data-callback="enableSubmitBtn"></div>
            </div>
            <button type="submit" class="btn btn-danger" id="submitBtn" disabled="disabled"><span class="fw-bold fs-6">ĐĂNG NHẬP</span></button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/login.js"></script>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>



<?php
mysqli_close($conn);
include $footer_url;
?>