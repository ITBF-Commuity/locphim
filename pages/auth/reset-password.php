<?php
/**
 * Lọc Phim - Trang đặt lại mật khẩu
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('/');
    exit;
}

// Lấy token từ URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('/quen-mat-khau');
    exit;
}

// Xử lý đặt lại mật khẩu
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif ($password !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        $result = resetPassword($token, $password);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Tiêu đề trang
$pageTitle = 'Đặt lại mật khẩu - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Đặt lại mật khẩu tài khoản <?php echo SITE_NAME; ?>">
    <meta name="keywords" content="đặt lại mật khẩu, tài khoản, xem phim, anime">
    
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
        
        .password-strength {
            height: 5px;
            margin-top: 8px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .strength-weak {
            background-color: #ff4444;
        }
        
        .strength-medium {
            background-color: #ffbb33;
        }
        
        .strength-strong {
            background-color: #00C851;
        }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            text-align: right;
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
            
            <h1 class="auth-title">Đặt lại mật khẩu</h1>
            
            <?php if (!empty($error)): ?>
            <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="form-success">
                <?php echo $success; ?>
                <div class="auth-footer" style="margin-top: 10px;">
                    <a href="/dang-nhap" class="btn btn-primary btn-block">Đăng nhập ngay</a>
                </div>
            </div>
            <?php else: ?>
            <form action="/dat-lai-mat-khau?token=<?php echo urlencode($token); ?>" method="POST">
                <div class="form-group">
                    <label for="password" class="form-label">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="form-input" required autofocus>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="strength-text" id="passwordStrengthText"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Đặt lại mật khẩu</button>
            </form>
            
            <div class="auth-footer">
                Trở về <a href="/dang-nhap">Đăng nhập</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include INCLUDES_PATH . '/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const passwordStrengthText = document.getElementById('passwordStrengthText');
            const form = document.querySelector('form');
            
            if (!passwordInput || !confirmPasswordInput) return;
            
            // Kiểm tra mật khẩu khớp nhau
            function validatePassword() {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Mật khẩu không khớp');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
            
            passwordInput.addEventListener('input', validatePassword);
            confirmPasswordInput.addEventListener('input', validatePassword);
            
            // Kiểm tra độ mạnh của mật khẩu
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                let strength = 0;
                
                if (password.length >= 6) {
                    strength += 1;
                }
                
                if (password.match(/[A-Z]/)) {
                    strength += 1;
                }
                
                if (password.match(/[0-9]/)) {
                    strength += 1;
                }
                
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 1;
                }
                
                let width = '0%';
                let strengthClass = '';
                let strengthText = '';
                
                switch (strength) {
                    case 0:
                        width = '0%';
                        strengthClass = '';
                        strengthText = '';
                        break;
                    case 1:
                        width = '25%';
                        strengthClass = 'strength-weak';
                        strengthText = 'Yếu';
                        break;
                    case 2:
                        width = '50%';
                        strengthClass = 'strength-medium';
                        strengthText = 'Trung bình';
                        break;
                    case 3:
                        width = '75%';
                        strengthClass = 'strength-medium';
                        strengthText = 'Khá mạnh';
                        break;
                    case 4:
                        width = '100%';
                        strengthClass = 'strength-strong';
                        strengthText = 'Mạnh';
                        break;
                }
                
                passwordStrengthBar.style.width = width;
                passwordStrengthBar.className = 'password-strength-bar';
                if (strengthClass) {
                    passwordStrengthBar.classList.add(strengthClass);
                }
                passwordStrengthText.textContent = strengthText;
            });
        });
    </script>
</body>
</html>