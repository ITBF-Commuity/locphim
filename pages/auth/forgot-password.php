<?php
/**
 * Lọc Phim - Trang quên mật khẩu
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('/');
    exit;
}

// Xử lý quên mật khẩu
$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Vui lòng nhập địa chỉ email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ';
    } else {
        $result = forgotPassword($email);
        
        if ($result['success']) {
            $success = $result['message'];
            $email = '';
        } else {
            $error = $result['message'];
        }
    }
}

// Tiêu đề trang
$pageTitle = 'Quên mật khẩu - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Đặt lại mật khẩu tài khoản <?php echo SITE_NAME; ?>">
    <meta name="keywords" content="quên mật khẩu, đặt lại mật khẩu, tài khoản, xem phim, anime">
    
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
        
        .auth-description {
            text-align: center;
            margin-bottom: 20px;
            color: var(--text-light);
            font-size: 14px;
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
        
        .form-error {
            color: var(--primary-color);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-success {
            color: #28a745;
            font-size: 14px;
            margin-top: 5px;
            padding: 10px;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: var(--border-radius);
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
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
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
            
            <h1 class="auth-title">Quên mật khẩu</h1>
            
            <p class="auth-description">Nhập địa chỉ email đã đăng ký tài khoản. Chúng tôi sẽ gửi cho bạn liên kết để đặt lại mật khẩu.</p>
            
            <?php if (!empty($error)): ?>
            <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="form-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="/quen-mat-khau" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu</button>
                
                <div class="form-actions">
                    <a href="/dang-nhap" class="auth-link">Trở về đăng nhập</a>
                </div>
            </form>
            
            <div class="auth-footer">
                Chưa có tài khoản? <a href="/dang-ky">Đăng ký ngay</a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include INCLUDES_PATH . '/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
</body>
</html>