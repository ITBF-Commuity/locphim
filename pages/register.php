<?php
/**
 * Lọc Phim - Trang đăng ký
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

// Xử lý form đăng ký
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $terms = isset($_POST['terms']) ? (bool)$_POST['terms'] : false;
    
    // Validate form
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'Tên đăng nhập phải có độ dài từ 3 đến 30 ký tự';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
    } elseif (empty($email)) {
        $error = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (empty($phone)) {
        $error = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ';
    } elseif (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $password_confirm) {
        $error = 'Xác nhận mật khẩu không khớp';
    } elseif (!$terms) {
        $error = 'Bạn phải đồng ý với điều khoản sử dụng';
    } else {
        // Kiểm tra xem username, email hoặc phone đã tồn tại chưa
        $existingUser = $db->get("SELECT id FROM users WHERE username = ?", [$username]);
        $existingEmail = $db->get("SELECT id FROM users WHERE email = ?", [$email]);
        $existingPhone = $db->get("SELECT id FROM users WHERE phone = ?", [$phone]);
        
        if ($existingUser) {
            $error = 'Tên đăng nhập đã tồn tại';
        } elseif ($existingEmail) {
            $error = 'Email đã được sử dụng';
        } elseif ($existingPhone) {
            $error = 'Số điện thoại đã được sử dụng';
        } else {
            // Tạo tài khoản mới
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = generate_token(32);
            $createdAt = date('Y-m-d H:i:s');
            
            $success = $db->execute("INSERT INTO users (username, email, phone, password, verification_token, status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, 'pending', ?)", 
                                    [$username, $email, $phone, $hashedPassword, $verificationToken, $createdAt]);
            
            if ($success) {
                // Gửi email xác nhận
                $verificationLink = SITE_URL . '/xac-thuc-tai-khoan?token=' . $verificationToken;
                $subject = 'Xác thực tài khoản ' . SITE_NAME;
                $message = 'Chào ' . $username . ',<br><br>';
                $message .= 'Cảm ơn bạn đã đăng ký tài khoản tại ' . SITE_NAME . '.<br>';
                $message .= 'Vui lòng nhấp vào liên kết sau để xác thực tài khoản của bạn:<br><br>';
                $message .= '<a href="' . $verificationLink . '">' . $verificationLink . '</a><br><br>';
                $message .= 'Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email này.<br><br>';
                $message .= 'Trân trọng,<br>' . SITE_NAME;
                
                // Trong môi trường phát triển, chúng ta chỉ giả lập việc gửi email
                if (DEBUG_MODE) {
                    $success = 'Đăng ký thành công! Trong môi trường thực tế, một email xác thực sẽ được gửi đến ' . $email . '.';
                    $success .= '<br>Liên kết xác thực: <a href="' . $verificationLink . '">' . $verificationLink . '</a>';
                } else {
                    // Gửi email xác thực thực tế (cần cấu hình email trong production)
                    // send_email($email, $subject, $message);
                    $success = 'Đăng ký thành công! Vui lòng kiểm tra email của bạn để xác thực tài khoản.';
                }
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại sau!';
            }
        }
    }
}

// Set title và description cho trang
$pageTitle = 'Đăng Ký - ' . SITE_NAME;
$pageDescription = 'Đăng ký tài khoản ' . SITE_NAME . ' để trải nghiệm xem phim tuyệt vời nhất.';

// Bắt đầu output buffering
ob_start();
?>

<div class="auth-page">
    <div class="auth-header">
        <h1>Đăng Ký</h1>
        <p>Tạo tài khoản mới tại <?php echo SITE_NAME; ?></p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <form class="auth-form" method="POST" action="<?php echo url('dang-ky'); ?>">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                <p class="form-hint">Chỉ sử dụng chữ cái, số và dấu gạch dưới, độ dài 3-30 ký tự</p>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                <p class="form-hint">Định dạng: 0xxxxxxxxx hoặc +84xxxxxxxxx</p>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                <p class="form-hint">Tối thiểu 6 ký tự</p>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Xác nhận mật khẩu</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Nhập lại mật khẩu" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="terms" value="1" <?php echo (isset($_POST['terms']) && $_POST['terms']) ? 'checked' : ''; ?> required>
                    <span class="checkmark"></span>
                    Tôi đồng ý với <a href="<?php echo url('dieu-khoan'); ?>" target="_blank">Điều khoản sử dụng</a> và <a href="<?php echo url('bao-mat'); ?>" target="_blank">Chính sách bảo mật</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Đăng Ký</button>
            
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
    <?php endif; ?>
    
    <div class="auth-footer">
        Bạn đã có tài khoản? <a href="<?php echo url('dang-nhap'); ?>">Đăng nhập ngay</a>
    </div>
</div>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>