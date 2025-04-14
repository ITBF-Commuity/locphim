<?php
// Tiêu đề trang
$page_title = 'Quên mật khẩu';

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
$success = false;
$email = '';

// Xử lý form quên mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    
    // Kiểm tra email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } else {
        $result = forgot_password($email);
        
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
                    <i class="fas fa-key me-2"></i> Quên mật khẩu
                </h4>
            </div>
            
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i> Đã gửi yêu cầu đặt lại mật khẩu!</h5>
                        <p>Chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu đến <strong><?php echo $email; ?></strong>. Vui lòng kiểm tra hộp thư đến của bạn và làm theo hướng dẫn.</p>
                        <hr>
                        <p class="mb-0">Không nhận được email? Hãy kiểm tra thư mục spam hoặc <a href="forgot-password.php" class="alert-link">thử lại</a>.</p>
                    </div>
                    <div class="text-center mt-4">
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Quay lại đăng nhập
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Hiển thị lỗi nếu có -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="mb-4">Vui lòng nhập địa chỉ email hoặc số điện thoại bạn đã sử dụng để đăng ký tài khoản. Chúng tôi sẽ gửi cho bạn một liên kết để đặt lại mật khẩu.</p>
                    
                    <form action="forgot-password.php" method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email hoặc số điện thoại</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="text" class="form-control" id="email" name="email" value="<?php echo $email; ?>" placeholder="Nhập email đã đăng ký" required autofocus>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Gửi yêu cầu
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Đã nhớ mật khẩu? <a href="login.php" class="text-decoration-none">Đăng nhập</a></p>
                        <p>Chưa có tài khoản? <a href="register.php" class="text-decoration-none">Đăng ký ngay</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Reset Password Token Handling
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_data = verify_reset_token($token);
    
    if ($token_data) {
        // Token hợp lệ, hiển thị form đặt lại mật khẩu
        $reset_form = true;
    } else {
        // Token không hợp lệ
        $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.';
    }
}

// Xử lý đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $result = reset_password($token, $password);
        
        if ($result['success']) {
            // Hiển thị thông báo thành công
            set_flash_message('success', $result['message']);
            header('Location: login.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Include footer
include 'footer.php';
?>
