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

// Biến thông báo
$error = '';
$success = '';

// Xử lý form quên mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = isset($_POST['email_or_phone']) ? trim($_POST['email_or_phone']) : '';
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($email_or_phone)) {
        $error = 'Vui lòng nhập email hoặc số điện thoại';
    } else {
        // Gửi yêu cầu đặt lại mật khẩu
        $result = forgot_password($email_or_phone);
        
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
                    <h2>Quên mật khẩu</h2>
                    <p>Vui lòng nhập email hoặc số điện thoại của bạn để đặt lại mật khẩu</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p>Vui lòng kiểm tra email hoặc tin nhắn để tiếp tục.</p>
                </div>
                <?php else: ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email_or_phone" class="form-label">Email hoặc Số điện thoại</label>
                        <input type="text" class="form-control" id="email_or_phone" name="email_or_phone" value="<?php echo isset($_POST['email_or_phone']) ? htmlspecialchars($_POST['email_or_phone']) : ''; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Gửi yêu cầu</button>
                </form>
                
                <div class="auth-links mt-3 text-center">
                    <a href="login.php">Quay lại đăng nhập</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
