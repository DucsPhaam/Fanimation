document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#supportForm');
    if (!form) {
        console.error("Support form not found");
        return;
    }

    // Check phone number format
    const phoneInput = form.querySelector("input[name='phone']");
    const phoneError = document.createElement("div");
    phoneError.style.color = "red";
    phoneError.style.display = "none";
    phoneInput.parentElement.appendChild(phoneError);
    phoneInput.addEventListener("input", function() {
        const phonePattern = /^(0[35789][0-9]{8,9})$/;
        if (!phonePattern.test(phoneInput.value)) {
            phoneError.textContent = "Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (10 hoặc 11 chữ số, bắt đầu bằng 03, 05, 07, 08, hoặc 09).";
            phoneError.style.display = "block";
        } else {
            phoneError.style.display = "none";
        }
    });

    // Check email format
    const emailInput = form.querySelector("input[name='email']");
    const emailError = document.createElement("div");
    emailError.style.color = "red";
    emailError.style.display = "none";
    emailInput.parentElement.appendChild(emailError);
    emailInput.addEventListener("input", function() {
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(emailInput.value)) {
            emailError.textContent = "Email không hợp lệ.";
            emailError.style.display = "block";
        } else {
            emailError.style.display = "none";
        }
    });

    // File upload handling
    const dropArea = form.querySelector(".drag-area");
    const fileInput = dropArea.querySelector("input[name='files[]']");
    const selectButton = dropArea.querySelector("button");
    let files = [];

    selectButton.addEventListener("click", () => {
        fileInput.click();
    });

    fileInput.addEventListener("change", function() {
        files = Array.from(this.files).slice(0, 4); // Giới hạn 4 file
        showFiles();
    });

    dropArea.addEventListener("dragover", (event) => {
        event.preventDefault();
        dropArea.classList.add("active");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("active");
    });

    dropArea.addEventListener("drop", (event) => {
        event.preventDefault();
        files = Array.from(event.dataTransfer.files).slice(0, 4); // Giới hạn 4 file
        fileInput.files = event.dataTransfer.files;
        showFiles();
    });

    function showFiles() {
        const validExtensions = ["image/jpeg", "image/gif", "image/png", "application/pdf", "video/mp4", "video/heic", "video/hevc"];
        const maxSize = 39 * 1024 * 1024; // 39MB
        dropArea.innerHTML = '<span>Drop files here or</span><button type="button" class="btn btn-primary">Select files</button><input type="file" class="form-control-file" name="files[]" accept="image/jpeg,image/gif,image/png,application/pdf,video/mp4,video/heic,video/hevc" multiple style="display: none;">';
        files.forEach(file => {
            if (validExtensions.includes(file.type) && file.size <= maxSize) {
                let fileReader = new FileReader();
                fileReader.onload = () => {
                    let fileURL = fileReader.result;
                    let imgTag = `<img src="${fileURL}" alt="${file.name}" style="max-width: 100px; margin: 5px;">`;
                    dropArea.insertAdjacentHTML('beforeend', imgTag);
                };
                fileReader.readAsDataURL(file);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: `File ${file.name} không được hỗ trợ hoặc vượt quá 39MB!`,
                    confirmButtonText: 'OK'
                });
            }
        });
        dropArea.classList.add("active");
    }

    // Character count for description
    const textarea = form.querySelector("textarea[name='description']");
    const charCount = form.querySelector(".char-count");
    textarea.addEventListener("input", function() {
        const maxLength = this.maxLength;
        const currentLength = this.value.length;
        charCount.textContent = `${currentLength} of ${maxLength} max characters`;
    });

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Kiểm tra các trường bắt buộc
        const name = form.querySelector("input[name='name']").value.trim();
        const phone = phoneInput.value.trim();
        const email = emailInput.value.trim();
        const address = form.querySelector("input[name='address']").value.trim();
        const productName = form.querySelector("input[name='product_name']").value.trim();
        const description = textarea.value.trim();
        const phonePattern = /^(0[35789][0-9]{8,9})$/;
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        if (!name || !phone || !email || !address || !description) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Vui lòng điền đầy đủ các trường bắt buộc!',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!phonePattern.test(phone)) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (10 hoặc 11 chữ số, bắt đầu bằng 03, 05, 07, 08, hoặc 09).',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!emailPattern.test(email)) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Email không hợp lệ. Vui lòng nhập đúng định dạng email (ví dụ: example@domain.com).',
                confirmButtonText: 'OK'
            });
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('address', address);
        formData.append('product_name', productName);
        formData.append('description', description);
        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        try {
            const response = await fetch('/Fanimation/pages/submit_support.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const text = await response.text();
            console.log('Phản hồi từ server:', text);
            const result = JSON.parse(text);

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: result.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    form.reset();
                    dropArea.innerHTML = '<span>Drop files here or</span><button type="button" class="btn btn-primary">Select files</button><input type="file" class="form-control-file" name="files[]" accept="image/jpeg,image/gif,image/png,application/pdf,video/mp4,video/heic,video/hevc" multiple style="display: none;">';
                    files = [];
                    window.location.href = '/Fanimation/pages/index.php';
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
});
