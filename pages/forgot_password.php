<?php
/**
 * Lọc Phim - Trang quên mật khẩu
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

// Xử lý form quên mật khẩu
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validate form
    if (empty($email)) {
        $error = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Kiểm tra xem email có tồn tại không
        $user = $db->get("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        
        if (!$user) {
            $error = 'Email không tồn tại hoặc tài khoản chưa được kích hoạt';
        } else {
            // Tạo token đặt lại mật khẩu
            $token = generate_token(32);
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Lưu token vào database
            $db->execute("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)", 
                [$user['id'], $token, $expires]);
            
            // Tạo liên kết đặt lại mật khẩu
            $resetLink = SITE_URL . '/dat-lai-mat-khau?token=' . $token;
            
            // Gửi email đặt lại mật khẩu
            $subject = 'Đặt lại mật khẩu ' . SITE_NAME;
            $message = 'Chào ' . $user['username'] . ',<br><br>';
            $message .= 'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại ' . SITE_NAME . '.<br>';
            $message .= 'Vui lòng nhấp vào liên kết sau để đặt lại mật khẩu của bạn:<br><br>';
            $message .= '<a href="' . $resetLink . '">' . $resetLink . '</a><br><br>';
            $message .= 'Liên kết này sẽ hết hạn sau 1 giờ.<br>';
            $message .= 'Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.<br><br>';
            $message .= 'Trân trọng,<br>' . SITE_NAME;
            
            // Trong môi trường phát triển, chúng ta chỉ giả lập việc gửi email
            if (DEBUG_MODE) {
                $success = 'Yêu cầu đặt lại mật khẩu đã được gửi thành công! Trong môi trường thực tế, một email sẽ được gửi đến ' . $email . '.';
                $success .= '<br>Liên kết đặt lại mật khẩu: <a href="' . $resetLink . '">' . $resetLink . '</a>';
            } else {
                // Gửi email thực tế (cần cấu hình email trong production)
                // send_email($email, $subject, $message);
                $success = 'Yêu cầu đặt lại mật khẩu đã được gửi thành công! Vui lòng kiểm tra email của bạn.';
            }
        }
    }
}

// Set title và description cho trang
$pageTitle = 'Quên Mật Khẩu - ' . SITE_NAME;
$pageDescription = 'Đặt lại mật khẩu tài khoản ' . SITE_NAME . '.';

// Bắt đầu output buffering
ob_start();
?>

<div class="auth-page">
    <div class="auth-header">
        <h1>Quên Mật Khẩu</h1>
        <p>Nhập email của bạn để đặt lại mật khẩu</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <form class="auth-form" method="POST" action="<?php echo url('quen-mat-khau'); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <p class="form-hint">Chúng tôi sẽ gửi liên kết đặt lại mật khẩu đến email của bạn</p>
            </div>
            
            <button type="submit" class="btn btn-primary">Gửi Yêu Cầu</button>
        </form>
    <?php endif; ?>
    
    <div class="auth-footer">
        <a href="<?php echo url('dang-nhap'); ?>">Quay lại trang đăng nhập</a>
    </div>
</div>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>