<?php
/**
 * Trang đăng nhập quản trị
 * Lọc Phim - Admin Panel
 */

// Bắt đầu session
session_start();

// Kết nối file cấu hình
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Kiểm tra đã đăng nhập chưa
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

// Biến lưu trạng thái
$error = '';
$success = '';

// Xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập.';
    } else {
        // Xác thực người dùng
        $result = admin_login($username, $password);
        
        if ($result['success']) {
            // Đăng nhập thành công
            if ($remember) {
                // Lưu cookie đăng nhập
                $token = bin2hex(random_bytes(32));
                $expires = time() + (86400 * 30); // 30 ngày
                
                // Lưu token vào cơ sở dữ liệu
                $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                db_query($sql, [$token, $result['user']['id']]);
                
                // Thiết lập cookie
                setcookie('admin_remember', $result['user']['id'] . ':' . $token, $expires, '/');
            }
            
            // Ghi log đăng nhập
            log_admin_action('login', 'Đăng nhập thành công', $result['user']['id']);
            
            // Chuyển hướng đến trang chính
            header('Location: index.php');
            exit;
        } else {
            // Đăng nhập thất bại
            $error = $result['message'];
            
            // Ghi log đăng nhập thất bại
            log_admin_action('login_failed', "Đăng nhập thất bại: {$username} - {$result['message']}", null);
        }
    }
}

// Kiểm tra thông báo hết hạn phiên
$session_expired = isset($_GET['expired']) && $_GET['expired'] == 1;

// Tiêu đề trang
$page_title = 'Đăng Nhập Quản Trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $page_title; ?> - Lọc Phim</title>
    
    <!-- Favicon -->
    <?php $site_favicon = get_setting('site_favicon', '/assets/images/favicon.ico'); ?>
    <link rel="shortcut icon" href="<?php echo $site_favicon; ?>">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-card {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
        }
        
        .login-header {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .login-logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-logo-icon {
            margin-right: 10px;
            font-size: 32px;
        }
        
        .login-title {
            font-size: 16px;
            color: var(--secondary-color);
            margin: 10px 0 0;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .login-form .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .login-form .form-control {
            padding: 12px 15px 12px 45px;
            height: auto;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .login-form .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-color: #80bdff;
        }
        
        .login-form .form-icon {
            position: absolute;
            left: 15px;
            top: 13px;
            color: #aaa;
        }
        
        .login-form .btn {
            padding: 12px 15px;
            font-weight: 600;
            border-radius: 5px;
        }
        
        .login-form .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .login-form .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            background-color: #f8f9fa;
        }
        
        .login-footer-text {
            font-size: 14px;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .login-footer-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-footer-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 0 15px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if ($session_expired): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i> Phiên đăng nhập của bạn đã hết hạn. Vui lòng đăng nhập lại.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-logo">
                    <span class="login-logo-icon"><i class="fas fa-film"></i></span>
                    <span>Lọc Phim</span>
                </h1>
                <p class="login-title">Đăng nhập vào trang quản trị</p>
            </div>
            
            <div class="login-body">
                <form class="login-form" method="post" action="">
                    <div class="form-group">
                        <i class="fas fa-user form-icon"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập hoặc Email" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="../forgot-password.php" class="text-muted">Quên mật khẩu?</a>
                </div>
            </div>
            
            <div class="login-footer">
                <p class="login-footer-text">
                    Quay lại <a href="../index.php" class="login-footer-link">trang chủ</a>
                </p>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> Lọc Phim. Phiên bản 1.0</span>
        </div>
    </div>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>