<?php
/**
 * Lọc Phim - Trang đăng ký
 */

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
    redirect($redirect);
    exit;
}

// Xử lý đăng ký
$error = '';
$success = '';
$formData = [
    'username' => '',
    'email' => '',
    'name' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'name' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
    ];
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate form
    if (empty($formData['username']) || empty($formData['email']) || empty($formData['name']) || empty($password) || empty($confirmPassword)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif ($password !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu không khớp';
    } else {
        // Đăng ký người dùng
        $result = register(
            $formData['username'],
            $formData['email'],
            $password,
            $formData['name'],
            $formData['phone'] ?: null
        );
        
        if ($result['success']) {
            // Nếu đăng ký thành công, đăng nhập người dùng
            $loginResult = login($formData['username'], $password);
            
            if ($loginResult['success']) {
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
                redirect($redirect);
                exit;
            } else {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.';
                $formData = [
                    'username' => '',
                    'email' => '',
                    'name' => '',
                    'phone' => '',
                ];
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Tiêu đề trang
$pageTitle = 'Đăng ký - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Đăng ký tài khoản tại <?php echo SITE_NAME; ?> để xem phim và anime không giới hạn">
    <meta name="keywords" content="đăng ký, tài khoản, xem phim, anime, phim online">
    
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
            max-width: 500px;
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
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
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
        
        .terms {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .terms input {
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .terms a {
            color: var(--primary-color);
            text-decoration: none;
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
            
            <h1 class="auth-title">Đăng ký tài khoản</h1>
            
            <?php if (!empty($error)): ?>
            <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="form-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="/dang-ky<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Tên đăng nhập <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                    <small>Chỉ được sử dụng chữ cái, số và dấu gạch dưới, từ 4-20 ký tự.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label">Họ và tên <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Mật khẩu <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-input" required>
                        <small>Tối thiểu 6 ký tự.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                
                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">Tôi đồng ý với <a href="/dieu-khoan-su-dung" target="_blank">Điều khoản sử dụng</a> và <a href="/chinh-sach-bao-mat" target="_blank">Chính sách bảo mật</a> của <?php echo SITE_NAME; ?></label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
            </form>
            
            <div class="auth-footer">
                Đã có tài khoản? <a href="/dang-nhap<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Đăng nhập</a>
            </div>
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
            const form = document.querySelector('form');
            
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
            
            // Kiểm tra tên đăng nhập hợp lệ
            const usernameInput = document.getElementById('username');
            
            usernameInput.addEventListener('input', function() {
                const username = usernameInput.value;
                const usernameRegex = /^[a-zA-Z0-9_]{4,20}$/;
                
                if (!usernameRegex.test(username)) {
                    usernameInput.setCustomValidity('Tên đăng nhập phải từ 4-20 ký tự và chỉ chứa chữ cái, số, và dấu gạch dưới');
                } else {
                    usernameInput.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>