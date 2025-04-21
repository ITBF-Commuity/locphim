<?php
// Định nghĩa URL trang chủ
define('SITE_URL', 'https://localhost');

// Bao gồm các file cần thiết
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Kiểm tra token
$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    header('Location: forgot_password.php');
    exit();
}

// Kiểm tra token có hợp lệ không
$reset_data = db_fetch_row(
    "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1",
    array($token)
);

if (!$reset_data) {
    $error = 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn';
    $invalid_token = true;
} else {
    $invalid_token = false;
}

// Biến thông báo
$error = '';
$success = '';

// Xử lý form đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalid_token) {
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($new_password)) {
        $error = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Đặt lại mật khẩu
        $result = reset_password($token, $new_password);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form">
                <div class="auth-header">
                    <h2>Đặt lại mật khẩu</h2>
                    <p>Vui lòng nhập mật khẩu mới cho tài khoản của bạn</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p><a href="login.php" class="btn btn-primary mt-2">Đăng nhập ngay</a></p>
                </div>
                <?php elseif (!$invalid_token): ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Đặt lại mật khẩu</button>
                </form>
                
                <?php else: ?>
                <div class="auth-links mt-3 text-center">
                    <a href="forgot_password.php" class="btn btn-primary">Yêu cầu đặt lại mật khẩu mới</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý hiển thị/ẩn mật khẩu
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Thay đổi icon
                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
