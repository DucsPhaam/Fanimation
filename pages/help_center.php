<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;
?>

<style>
    .icon {
        color: rgb(133, 55, 167);
    }
</style>

<div id="about-us">
    <div id="contactCarousel" class="carousel slide">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="../assets/images/banners/contact_us.jpg" alt="Contact Us Image" class="d-block w-100">
                <div class="carousel-content">
                    <h1 class="">Contact</h1>
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
            <!-- Location Section -->
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

            <!-- Product Support Section -->
            <div class="col-md-4 text-start">
                <i class="icon bi bi-card-list fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Product Support</h5>
                    <p>Every Fanimation fan is backed by our firm commitment to quality materials and manufacturing.</p>
                    <p class="fw-bold">Get product support</p>
                </div>
            </div>

            <!-- Marketing Section -->
            <div class="col-md-4 text-start">
                <i class="icon bi bi-file-earmark-text fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Marketing</h5>
                    <p>If you need additional marketing materials that aren't presented in our press room or have other marketing and public relations related questions, please contact:</p>
                    <p class="fw-bold">press@fanimation.com</p>
                </div>
            </div>

            <!-- Suggestions Section -->
            <div class="col-md-4 text-start">
                <i class="icon bi bi-chat-dots fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Suggestions</h5>
                    <p>Fanimation wants to enhance your experience. If you have suggestions on how we can better serve you, please contact:</p>
                    <p class="fw-bold">suggestions@fanimation.com</p>
                </div>
            </div>

            <!-- Find a Sales Agent Section -->
            <div class="col-md-4 text-start">
                <i class="icon bi bi-send-fill fs-2 mb-2"></i>
                <div class="text-start">
                    <h5 class="fw-bold text-uppercase">Find a Sales Agent</h5>
                    <p>Fanimation works with sales agents throughout the United States and worldwide to assist you with selling our product.</p>
                    <p class="fw-bold">Find your agent</p>
                </div>
            </div>

            <!-- Careers Section -->
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

<style src="../assets/css/help_center.php"></style>

<div id="contact-tech" class="bg-light">
    <div class="justify-items-center mx-auto w-75 mt-3">
        <p class="fs-2 fw-semibold text-center">Questions? Contact tech support</p>
    </div>
    <div class="container">
        <div class="row mb-3">
            <div class="col">
                <label class="required">Name</label>
                <div class="row">
                    <div class="col">
                        <input type="text" class="form-control" placeholder="First">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Last">
                    </div>
                </div>
            </div>
            <div class="col">
                <label class="required">Phone number</label>
                <input type="tel" class="form-control">
            </div>
            <div class="col">
                <label class="required">Email address</label>
                <input type="email" class="form-control">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <label class="required">Address</label>
                <input type="text" class="form-control" placeholder="Street address">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <label class="required">Product name</label>
                <input type="text" class="form-control">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <label class="required">Upload photo/video of fan</label>
                <div class="drag-area">
                    <span>Drop files here or</span>
                    <button class="btn btn-primary">Select files</button>
                    <input type="file" class="form-control-file" accept="image/jpeg,image/gif,image/png,application/pdf,video/mp4,video/heic,video/hevc" multiple style="display: none;">
                </div>
                <small class="text-muted">Accepted file types: jpg, gif, png, pdf, mp4, heif, hevc, Max. file size: 39 MB, Max. files: 4.</small>
            </div>
        </div>

        <div class="row mb-3 problem-description">
            <div class="col">
                <label class="required">Description of problem</label>
                <textarea class="form-control" maxlength="280" placeholder="Accident! Full description of problem"></textarea>
                <small class="char-count">0 of 280 max characters</small>
            </div>
        </div>
        <button type="submit" class="bttsubmit">Submit</button>
    </div>
</div>

<script src="../assets/js/help_center.js"></script>
<?php
mysqli_close($conn);
include $footer_url;
?>