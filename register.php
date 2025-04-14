<?php
// Tiêu đề trang
$page_title = 'Đăng ký';

// Include header
include 'header.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (is_logged_in()) {
    // Chuyển hướng đến trang chủ
    header('Location: index.php');
    exit;
}

// Khởi tạo các biến
$error = '';
$username = '';
$email = '';
$phone = '';
$success = false;

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra mật khẩu
    if ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Xác thực đăng ký
        $register_result = register_user($username, $email, $phone, $password);
        
        if ($register_result['success']) {
            // Đăng ký thành công
            $success = true;
        } else {
            // Đăng ký thất bại
            $error = $register_result['message'];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow border-0 rounded-3 mt-3">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="my-2">
                    <i class="fas fa-user-plus me-2"></i> Đăng ký tài khoản
                </h4>
            </div>
            
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i> Đăng ký thành công!</h5>
                        <p>Chúng tôi đã gửi email kích hoạt đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư đến và làm theo hướng dẫn để hoàn tất quá trình đăng ký.</p>
                        <hr>
                        <p class="mb-0">Đã có tài khoản? <a href="login.php" class="alert-link">Đăng nhập ngay</a></p>
                    </div>
                <?php else: ?>
                    <!-- Hiển thị lỗi nếu có -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="register.php" method="POST" id="registerForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required autofocus>
                            </div>
                            <div class="form-text">Tên đăng nhập phải có ít nhất 4 ký tự, không chứa ký tự đặc biệt.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                            </div>
                            <div class="form-text">Tùy chọn, nhưng cần thiết cho việc khôi phục tài khoản.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                Tôi đồng ý với <a href="terms.php" target="_blank">Điều khoản sử dụng</a> và <a href="privacy.php" target="_blank">Chính sách bảo mật</a>
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i> Đăng ký
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p>Đã có tài khoản? <a href="login.php" class="text-decoration-none">Đăng nhập ngay</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Script để hiện/ẩn mật khẩu
$extra_js = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const togglePasswordButtons = document.querySelectorAll(".toggle-password");
        
        togglePasswordButtons.forEach(button => {
            button.addEventListener("click", function() {
                const passwordInput = this.previousElementSibling;
                
                // Thay đổi loại input
                const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
                passwordInput.setAttribute("type", type);
                
                // Thay đổi icon
                this.querySelector("i").classList.toggle("fa-eye");
                this.querySelector("i").classList.toggle("fa-eye-slash");
            });
        });
        
        // Kiểm tra form
        const registerForm = document.getElementById("registerForm");
        if (registerForm) {
            registerForm.addEventListener("submit", function(event) {
                const password = document.getElementById("password").value;
                const confirmPassword = document.getElementById("confirm_password").value;
                
                if (password !== confirmPassword) {
                    event.preventDefault();
                    alert("Mật khẩu xác nhận không khớp!");
                }
                
                const username = document.getElementById("username").value;
                if (username.length < 4) {
                    event.preventDefault();
                    alert("Tên đăng nhập phải có ít nhất 4 ký tự!");
                }
                
                if (password.length < 8) {
                    event.preventDefault();
                    alert("Mật khẩu phải có ít nhất 8 ký tự!");
                }
            });
        }
    });
</script>
';

// Include footer
include 'footer.php';
?>
