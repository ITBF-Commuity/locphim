<?php
/**
 * Lọc Phim - Trang đặt lại mật khẩu
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

// Xử lý token
$token = isset($_GET['token']) ? $_GET['token'] : '';
$tokenValid = false;
$userId = 0;

if (!empty($token)) {
    // Kiểm tra token có hợp lệ không
    $resetData = $db->get("SELECT pr.*, u.username, u.email FROM password_resets pr
                           JOIN users u ON pr.user_id = u.id
                           WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
                           ORDER BY pr.id DESC LIMIT 1", [$token]);
    
    if ($resetData) {
        $tokenValid = true;
        $userId = $resetData['user_id'];
    }
}

// Xử lý form đặt lại mật khẩu
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    
    // Validate form
    if (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $password_confirm) {
        $error = 'Xác nhận mật khẩu không khớp';
    } else {
        // Cập nhật mật khẩu mới
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updated = $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
        
        if ($updated) {
            // Đánh dấu token đã sử dụng
            $db->execute("UPDATE password_resets SET used = 1 WHERE token = ?", [$token]);
            
            $success = 'Mật khẩu đã được đặt lại thành công! Bạn có thể <a href="' . url('dang-nhap') . '">đăng nhập</a> với mật khẩu mới.';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại sau!';
        }
    }
}

// Set title và description cho trang
$pageTitle = 'Đặt Lại Mật Khẩu - ' . SITE_NAME;
$pageDescription = 'Đặt lại mật khẩu tài khoản ' . SITE_NAME . '.';

// Bắt đầu output buffering
ob_start();
?>

<div class="auth-page">
    <div class="auth-header">
        <h1>Đặt Lại Mật Khẩu</h1>
        <p>Tạo mật khẩu mới cho tài khoản của bạn</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif (!$tokenValid): ?>
        <div class="alert alert-danger">
            Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.<br>
            Vui lòng <a href="<?php echo url('quen-mat-khau'); ?>">yêu cầu đặt lại mật khẩu</a> lại.
        </div>
    <?php else: ?>
        <form class="auth-form" method="POST" action="<?php echo url('dat-lai-mat-khau') . '?token=' . htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">Mật khẩu mới</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu mới" required>
                <p class="form-hint">Tối thiểu 6 ký tự</p>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Xác nhận mật khẩu mới</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Nhập lại mật khẩu mới" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Đặt Lại Mật Khẩu</button>
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