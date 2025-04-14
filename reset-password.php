<?php
// Tiêu đề trang
$page_title = 'Đặt lại mật khẩu';

// Include header
include 'header.php';

// Kiểm tra token
$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = false;

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

// Xác thực token
$token_data = verify_reset_token($token);

if (!$token_data) {
    $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.';
}

// Xử lý đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else if (strlen($password) < 8) {
        $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } else {
        $result = reset_password($token, $password);
        
        if ($result['success']) {
            $success = true;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow border-0 rounded-3 mt-3">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="my-2">
                    <i class="fas fa-lock me-2"></i> Đặt lại mật khẩu
                </h4>
            </div>
            
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i> Đặt lại mật khẩu thành công!</h5>
                        <p>Mật khẩu của bạn đã được cập nhật. Bạn có thể đăng nhập bằng mật khẩu mới ngay bây giờ.</p>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập ngay
                        </a>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="forgot-password.php" class="btn btn-outline-primary">
                            <i class="fas fa-paper-plane me-2"></i> Yêu cầu liên kết mới
                        </a>
                    </div>
                <?php else: ?>
                    <p class="mb-4">Vui lòng nhập mật khẩu mới cho tài khoản của bạn.</p>
                    
                    <form action="reset-password.php?token=<?php echo $token; ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo $token; ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required autofocus>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Cập nhật mật khẩu
                            </button>
                        </div>
                    </form>
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
    });
</script>
';

// Include footer
include 'footer.php';
?>
