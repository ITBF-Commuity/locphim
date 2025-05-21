<?php
/**
 * Lọc Phim - Trang đăng nhập
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

// Xử lý form đăng nhập
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
    
    // Validate form
    if (empty($username)) {
        $error = 'Vui lòng nhập email hoặc số điện thoại';
    } elseif (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu';
    } else {
        // Kiểm tra đăng nhập bằng email hoặc số điện thoại
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // Đăng nhập bằng email
            $user = $db->get("SELECT * FROM users WHERE email = ?", [$username]);
        } else {
            // Đăng nhập bằng số điện thoại hoặc username
            $user = $db->get("SELECT * FROM users WHERE phone = ? OR username = ?", [$username, $username]);
        }
        
        if (!$user) {
            $error = 'Tài khoản không tồn tại';
        } elseif ($db->getDatabaseType() !== 'pgsql' && $user['status'] !== 'active') {
            // Kiểm tra trạng thái chỉ cho MySQL/SQLite
            $error = 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt';
        } elseif (!password_verify($password, $user['password'])) {
            // Ghi nhận đăng nhập thất bại
            if ($db->getDatabaseType() === 'pgsql') {
                // PostgreSQL không có cột failed_logins và last_failed_login
                // Bỏ qua việc ghi nhận thất bại cho PostgreSQL
            } else {
                $db->execute("UPDATE users SET failed_logins = failed_logins + 1, last_failed_login = NOW() WHERE id = ?", [$user['id']]);
            }
            $error = 'Mật khẩu không chính xác';
        } else {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['is_vip'] = (bool)$user['is_vip'];
            
            // Đặt lại số lần đăng nhập thất bại
            try {
                if ($db->getDatabaseType() === 'pgsql') {
                    // PostgreSQL có thể không có một số cột
                    // Bỏ qua việc cập nhật hoàn toàn hoặc chỉ kiểm tra thêm cột
                    // $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                } else {
                    $db->execute("UPDATE users SET failed_logins = 0, last_login = NOW() WHERE id = ?", [$user['id']]);
                }
            } catch (Exception $e) {
                // Bỏ qua lỗi cập nhật last_login, cho phép đăng nhập tiếp tục
                error_log("Lỗi cập nhật last_login: " . $e->getMessage());
            }
            
            // Nếu chọn "Nhớ mật khẩu"
            if ($remember) {
                try {
                    $token = generate_token(32);
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Lưu token vào database
                    $db->execute("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)", 
                        [$user['id'], $token, $expires]);
                    
                    // Lưu token vào cookie
                    setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
                } catch (Exception $e) {
                    // Bỏ qua lỗi remember_tokens, cho phép đăng nhập tiếp tục
                    error_log("Lỗi lưu remember token: " . $e->getMessage());
                }
            }
            
            // Chuyển hướng về trang chủ hoặc trang được yêu cầu trước đó
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : url('');
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        }
    }
}

// Set title và description cho trang
$pageTitle = 'Đăng Nhập - ' . SITE_NAME;
$pageDescription = 'Đăng nhập vào ' . SITE_NAME . ' để trải nghiệm xem phim tuyệt vời nhất.';

// Bắt đầu output buffering
ob_start();
?>

<div class="auth-page">
    <div class="auth-header">
        <h1>Đăng Nhập</h1>
        <p>Chào mừng bạn quay lại với <?php echo SITE_NAME; ?></p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form class="auth-form" method="POST" action="<?php echo url('dang-nhap'); ?>">
        <div class="form-group">
            <label for="username">Email hoặc số điện thoại</label>
            <input type="text" id="username" name="username" placeholder="Nhập email hoặc số điện thoại" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
            <a href="<?php echo url('quen-mat-khau'); ?>" class="forgot-password">Quên mật khẩu?</a>
        </div>
        
        <div class="form-group">
            <label class="checkbox-container">
                <input type="checkbox" name="remember" value="1" <?php echo (isset($_POST['remember']) && $_POST['remember']) ? 'checked' : ''; ?>>
                <span class="checkmark"></span>
                Nhớ mật khẩu
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary">Đăng Nhập</button>
        
        <div class="divider">
            <span>Hoặc</span>
        </div>
        
        <div class="social-login">
            <a href="<?php echo url('dang-nhap/facebook'); ?>" class="btn btn-facebook">
                <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="<?php echo url('dang-nhap/google'); ?>" class="btn btn-google">
                <i class="fab fa-google"></i> Google
            </a>
        </div>
    </form>
    
    <div class="auth-footer">
        Bạn chưa có tài khoản? <a href="<?php echo url('dang-ky'); ?>">Đăng ký ngay</a>
    </div>
</div>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>