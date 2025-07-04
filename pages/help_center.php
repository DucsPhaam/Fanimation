<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;

// Lấy thông tin người dùng nếu đã đăng nhập
$user_data = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name, email, phone, address FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
?>
<style>
    .icon {
        color: rgb(133, 55, 167);
    }
    .required::after {
        content: " *";
        color: #a94442;
    }
    .drag-area {
        border: 2px dashed #ccc;
        height: auto;
        width: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 20px;
        background-color: #f9f9f9;
        color: #666;
        position: relative;
        overflow: hidden;
    }
    .drag-area.active {
        border: 2px solid #007bff;
        background-color: #e9f0f8;
    }
    .drag-area .preview img {
        max-width: 100px;
        margin: 5px;
    }
    .bttsubmit {
        background-color: #922492;
        border: none;
        padding: 0.7rem;
        width: 6rem;
        border-radius: 3px;
        font-weight: bold;
        color: #fff;
        cursor: pointer;
    }
    .bttsubmit:hover {
        background-color: #520a52;
    }
</style>

<div id="about-us">
    <div id="contactCarousel" class="carousel slide">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="../assets/images/banners/contact_us.jpg" alt="Contact Us Image" class="d-block w-100">
                <div class="carousel-content">
                    <h1>Contact</h1>
                </div>
            </div>
        </div>
    </div>
    <div class="justify-items-center mx-auto w-75 mt-3">
        <p class="fs-2 fw-semibold text-center">Learn more about us</p>
        <p class="fs-5 fw-normal">Fanimation strives hard to be environmentally friendly. We encourage you to browse our products online, which includes all the latest information on our great products and styles. If you are in need of additional information not found on our web site or would just like to learn more about the company in general, please contact us by any of the following methods or simply fill out our request information form below. For product and shipping issues please fill out our product support form.</p>
    </div>

    <div class="container my-5 mx-auto w-75">
        <div class="row g-4">
            <div class="col-md-4 text-start">
                <i class="icon bi bi-geo-alt fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Location</h5>
                    <p class="mb-0">10983 Bennett Parkway</p>
                    <p class="mb-0">Zionsville, IN 46077</p>
                    <p class="mb-0">Phone: 888.567.2055</p>
                    <p>Fax: 866.482.5215</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-card-list fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Product Support</h5>
                    <p>Every Fanimation fan is backed by our firm commitment to quality materials and manufacturing.</p>
                    <p class="fw-bold">Get product support</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-file-earmark-text fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Marketing</h5>
                    <p>If you need additional marketing materials that aren't presented in our press room or have other marketing and public relations related questions, please contact:</p>
                    <p class="fw-bold">press@fanimation.com</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-chat-dots fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Suggestions</h5>
                    <p>Fanimation wants to enhance your experience. If you have suggestions on how we can better serve you, please contact:</p>
                    <p class="fw-bold">suggestions@fanimation.com</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-send-fill fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Find a Sales Agent</h5>
                    <p>Fanimation works with sales agents throughout the United States and worldwide to assist you with selling our product.</p>
                    <p class="fw-bold">Find your agent</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-person-circle fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Careers</h5>
                    <p>Find something on our website that is not working the way it should? Contact us so that we can improve your experience on our website:</p>
                    <p class="fw-bold">careers@fanimation.com</p>
                </div>
            </div>
            <div class="col-md-4 text-start">
                <i class="icon bi bi-pc-display-horizontal fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">WEBMASTER</h5>
                    <p>Interested in working at Fanimation? Email your resume to:</p>
                    <p class="fw-bold">webmaster@fanimation.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="contact-tech" class="bg-light">
    <div class="justify-items-center mx-auto w-75 mt-3">
        <p class="fs-2 fw-semibold text-center">Questions? Contact tech support</p>
    </div>
    <div class="container">
        <form id="supportForm" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col">
                    <label class="required">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo isset($user_data['name']) ? htmlspecialchars($user_data['name']) : ''; ?>" required>
                </div>
                <div class="col">
                    <label class="required">Phone number</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>" required>
                </div>
                <div class="col">
                    <label class="required">Email address</label>
                    <input type="email" class="form-control" name="email" value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="required">Address</label>
                    <input type="text" class="form-control" name="address" placeholder="Street address" value="<?php echo isset($user_data['address']) ? htmlspecialchars($user_data['address']) : ''; ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label>Product name</label>
                    <input type="text" class="form-control" name="product_name">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="required">Upload photo/video of fan</label>
                    <div class="drag-area">
                        <span>Drop files here or</span>
                        <button type="button" class="btn btn-primary">Select files</button>
                        <input type="file" class="form-control-file" name="files[]" accept="image/jpeg,image/gif,image/png,application/pdf,video/mp4,video/heic,video/hevc" multiple style="display: none;">
                    </div>
                    <small class="text-muted">Accepted file types: jpg, gif, png, pdf, mp4, heif, hevc, Max. file size: 39 MB, Max. files: 4.</small>
                </div>
            </div>
            <div class="row mb-3 problem-description">
                <div class="col">
                    <label class="required">Description of problem</label>
                    <textarea class="form-control" name="description" maxlength="280" placeholder="Accident! Full description of problem" required></textarea>
                    <small class="char-count">0 of 280 max characters</small>
                </div>
            </div>
            <button type="submit" class="bttsubmit btn btn-primary">Submit</button>
        </form>
    </div>
</div>

<!-- Thêm SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Fanimation/assets/js/help_center.js"></script>
<?php
mysqli_close($conn);
include $footer_url;
?>
