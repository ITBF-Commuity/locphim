<?php
/**
 * Trang xác thực đăng nhập
 */

require_once 'init.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (get_current_user_info()) {
    redirect('index.php');
}

$errors = [];
$success = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Kiểm tra tài khoản
    if (empty($username)) {
        $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email';
    }
    
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu';
    }
    
    if (empty($errors)) {
        // Kiểm tra đăng nhập bằng username hoặc email
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $user = db_fetch_row(
            "SELECT users.*, roles.name as role_name
             FROM users
             JOIN roles ON users.role_id = roles.id
             WHERE users.$field = ? AND users.status = 1", 
            [$username]
        );
        
        if ($user && verify_password($password, $user['password'])) {
            // Cập nhật thời gian đăng nhập
            db_update('users', [
                'last_login_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$user['id']]);
            
            // Lưu session
            $_SESSION['user_id'] = $user['id'];
            
            // Nếu chọn "Ghi nhớ đăng nhập"
            if ($remember) {
                $token = generate_token();
                $expiry = time() + 30 * 24 * 60 * 60; // 30 ngày
                
                // Lưu token vào cơ sở dữ liệu
                db_update('users', [
                    'remember_token' => $token
                ], 'id = ?', [$user['id']]);
                
                // Đặt cookie
                setcookie('remember_token', $token, $expiry, '/');
            }
            
            // Kiểm tra và tạo cài đặt người dùng nếu chưa có
            $settings = db_fetch_row(
                "SELECT * FROM user_settings WHERE user_id = ?",
                [$user['id']]
            );
            
            if (!$settings) {
                db_insert('user_settings', [
                    'user_id' => $user['id'],
                    'theme_preference' => DEFAULT_THEME,
                    'subtitle_language' => 'vi',
                    'audio_language' => 'vi'
                ]);
            }
            
            // Thông báo đăng nhập
            send_notification(
                $user['id'],
                'security',
                'Đăng nhập thành công',
                'Tài khoản của bạn vừa được đăng nhập từ ' . $_SERVER['REMOTE_ADDR'],
                'login',
                $user['id']
            );
            
            // Chuyển hướng sau khi đăng nhập
            $redirect_to = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            redirect($redirect_to);
        } else {
            $errors[] = 'Tên đăng nhập hoặc mật khẩu không chính xác';
        }
    }
}

// Đặt tiêu đề trang
$page_title = "Đăng nhập - " . SITE_NAME;
$page_description = "Đăng nhập vào " . SITE_NAME . " để truy cập nội dung độc quyền và lưu lại tiến độ xem phim.";
?>

<!DOCTYPE html>
<html lang="vi" data-theme="<?php echo get_current_theme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.svg" type="image/svg+xml">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (get_current_theme() === 'dark'): ?>
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <?php endif; ?>
    
    <style>
        .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .auth-form-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            border-radius: var(--border-radius);
            background-color: var(--bg-card);
            box-shadow: var(--box-shadow);
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo img {
            height: 60px;
            margin-bottom: 1rem;
        }
        
        .auth-title {
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex-grow: 1;
            background-color: var(--border-color);
            height: 1px;
        }
        
        .auth-divider span {
            padding: 0 1rem;
        }
        
        .auth-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .auth-social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--bg-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        .auth-social-btn:hover {
            background-color: var(--bg-color);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-container">
            <div class="auth-logo">
                <a href="index.php">
                    <img src="assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>">
                </a>
                <h1 class="auth-title">Đăng nhập</h1>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="auth.php" class="auth-form">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập hoặc Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập hoặc email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    <a href="forgot_password.php" class="text-decoration-none">Quên mật khẩu?</a>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Đăng nhập</button>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>hoặc</span>
            </div>
            
            <div class="auth-social">
                <a href="#" class="auth-social-btn" title="Đăng nhập bằng Google">
                    <i class="fab fa-google"></i>
                </a>
                <a href="#" class="auth-social-btn" title="Đăng nhập bằng Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="auth-social-btn" title="Đăng nhập bằng Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
            
            <div class="auth-footer">
                Chưa có tài khoản? <a href="register.php" class="text-decoration-none">Đăng ký ngay</a>
            </div>
        </div>
    </div>
    
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>