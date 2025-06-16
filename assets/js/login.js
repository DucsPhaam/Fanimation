document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Ngăn form gửi mặc định
        

        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'process_login.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                const errorMessage = document.getElementById('error-message');
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Sử dụng SweetAlert2 thay vì alert
                            Swal.fire({
                                title: 'Thành công!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = '../index.php'; // Chuyển hướng
                            });
                        } else {
                            errorMessage.textContent = response.message;
                            errorMessage.classList.remove('d-none');
                        }
                    } catch (e) {
                        console.error('Lỗi phân tích JSON:', e, 'Phản hồi:', xhr.responseText);
                        errorMessage.textContent = 'Lỗi hệ thống, vui lòng thử lại sau.';
                        errorMessage.classList.remove('d-none');
                    }
                } else {
                    console.error('Lỗi server:', xhr.status, xhr.responseText);
                    errorMessage.textContent = 'Lỗi kết nối server (mã ' + xhr.status + ').';
                    errorMessage.classList.remove('d-none');
                }
            }
        };
        xhr.send(formData);
    });