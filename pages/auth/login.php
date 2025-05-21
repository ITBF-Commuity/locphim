<?php
/**
 * Lọc Phim - Trang đăng nhập
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
    redirect($redirect);
    exit;
}

// Xử lý đăng nhập
$error = '';
$username = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $result = login($username, $password, $remember);
        
        if ($result['success']) {
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
            redirect($redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Tiêu đề trang
$pageTitle = 'Đăng nhập - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Đăng nhập vào <?php echo SITE_NAME; ?> để xem phim và anime không giới hạn">
    <meta name="keywords" content="đăng nhập, xem phim, anime, phim online">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <style>
        .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 150px);
            padding: 20px;
        }
        
        .auth-form {
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .auth-logo a {
            font-size: 28px;
            font-weight: var(--font-bold);
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .auth-title {
            text-align: center;
            font-size: 24px;
            font-weight: var(--font-bold);
            margin-bottom: 20px;
            color: var(--text-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: var(--font-medium);
            color: var(--text-color);
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            color: var(--text-color);
            background-color: var(--bg-white);
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-input.error {
            border-color: var(--primary-color);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .form-error {
            color: var(--primary-color);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .forgot-password {
            font-size: 14px;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .forgot-password:hover {
            color: var(--primary-color);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: var(--font-medium);
        }
        
        .social-login {
            margin-top: 20px;
            text-align: center;
        }
        
        .social-login-title {
            position: relative;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .social-login-title::before,
        .social-login-title::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background-color: var(--border-color);
        }
        
        .social-login-title::before {
            left: 0;
        }
        
        .social-login-title::after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            text-decoration: none;
            transition: transform 0.3s;
        }
        
        .social-button:hover {
            transform: translateY(-3px);
        }
        
        .social-facebook {
            background-color: #3b5999;
        }
        
        .social-google {
            background-color: #dd4b39;
        }
        
        .social-twitter {
            background-color: #55acee;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include INCLUDES_PATH . '/header.php'; ?>
    
    <!-- Main Content -->
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-logo">
                <a href="/">Lọc Phim</a>
            </div>
            
            <h1 class="auth-title">Đăng nhập</h1>
            
            <?php if (!empty($error)): ?>
            <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="/dang-nhap<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Tên đăng nhập / Email / Số điện thoại</label>
                    <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember">Ghi nhớ đăng nhập</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                
                <div class="form-actions">
                    <a href="/quen-mat-khau" class="forgot-password">Quên mật khẩu?</a>
                </div>
            </form>
            
            <!--
            <div class="social-login">
                <div class="social-login-title">Hoặc đăng nhập với</div>
                <div class="social-buttons">
                    <a href="#" class="social-button social-facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-button social-google">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-button social-twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                </div>
            </div>
            -->
            
            <div class="auth-footer">
                Chưa có tài khoản? <a href="/dang-ky<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Đăng ký ngay</a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include INCLUDES_PATH . '/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
</body>
</html>