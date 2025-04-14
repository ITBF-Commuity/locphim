<?php
// Tiêu đề trang
$page_title = 'Đăng nhập';

// Include header
include 'header.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (is_logged_in()) {
    // Chuyển hướng đến trang chủ
    header('Location: index.php');
    exit;
}

// Xử lý đăng nhập
$error = '';
$username_or_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username_or_email = sanitize_input($_POST['username_or_email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Xác thực người dùng
    $login_result = login($username_or_email, $password, $remember);
    
    if ($login_result['success']) {
        // Đăng nhập thành công
        
        // Kiểm tra xem có URL chuyển hướng không
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect_url);
        } else {
            header('Location: index.php');
        }
        
        exit;
    } else {
        // Đăng nhập thất bại
        $error = $login_result['message'];
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow border-0 rounded-3 mt-3">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="my-2">
                    <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
                </h4>
            </div>
            
            <div class="card-body p-4">
                <!-- Hiển thị lỗi nếu có -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username_or_email" class="form-label">Tên đăng nhập / Email / Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username_or_email" name="username_or_email" value="<?php echo $username_or_email; ?>" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <a href="forgot-password.php" class="text-decoration-none">Quên mật khẩu?</a>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p>Chưa có tài khoản? <a href="register.php" class="text-decoration-none">Đăng ký ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Script để hiện/ẩn mật khẩu
$extra_js = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const togglePassword = document.querySelector(".toggle-password");
        const passwordInput = document.querySelector("#password");
        
        togglePassword.addEventListener("click", function() {
            // Thay đổi loại input
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            
            // Thay đổi icon
            this.querySelector("i").classList.toggle("fa-eye");
            this.querySelector("i").classList.toggle("fa-eye-slash");
        });
    });
</script>
';

// Include footer
include 'footer.php';
?>
