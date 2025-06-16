    document.getElementById('register-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Ngăn form gửi mặc định

        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'process_register.php', true);
        xhr.onreadystatechange = function() {
            const errorMessage = document.getElementById('error-message');
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                title: 'Thành công!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = 'login.php'; // Chuyển hướng
                            });
                        } else {
                            errorMessage.textContent = response.message;
                            errorMessage.classList.remove('d-none');
                        }
                    } catch (e) {
                        errorMessage.textContent = 'Lỗi xử lý phản hồi từ server';
                        errorMessage.classList.remove('d-none');
                    }
                } else {
                    errorMessage.textContent = 'Lỗi server: ' + xhr.status;
                    errorMessage.classList.remove('d-none');
                }
            }
        };
        xhr.send(formData);
    });