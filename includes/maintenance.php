<?php
/**
 * Lọc Phim - Xử lý chế độ bảo trì
 * 
 * File này xử lý trang thông báo bảo trì khi website ở chế độ bảo trì
 */

// Kiểm tra cài đặt chế độ bảo trì từ config
$config_file = dirname(__FILE__) . '/../config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    $maintenance_mode = isset($config['site']['maintenance_mode']) ? $config['site']['maintenance_mode'] : false;
    $maintenance_message = isset($config['site']['maintenance_message']) ? $config['site']['maintenance_message'] : 'Trang web đang được bảo trì. Vui lòng quay lại sau!';
} else {
    $maintenance_mode = false;
    $maintenance_message = 'Trang web đang được bảo trì. Vui lòng quay lại sau!';
}

// Kiểm tra người dùng hiện tại có phải admin không (sử dụng trong bối cảnh bảo trì)
function is_maintenance_admin() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

// Xử lý chuyển hướng khi ở chế độ bảo trì
function check_maintenance_mode() {
    global $maintenance_mode, $maintenance_message;
    
    // Nếu không ở chế độ bảo trì hoặc là admin thì bỏ qua
    if (!$maintenance_mode || is_maintenance_admin()) {
        return;
    }
    
    // Nếu đang ở trang login thì bỏ qua
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page == 'login.php') {
        return;
    }
    
    // Hiển thị trang bảo trì
    display_maintenance_page($maintenance_message);
    exit;
}

/**
 * Hiển thị trang bảo trì
 * 
 * @param string $message Thông báo bảo trì
 */
function display_maintenance_page($message) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 giờ
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì - Lọc Phim</title>
    <link rel="shortcut icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .maintenance-container {
            max-width: 600px;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 28px;
            color: #ff5722;
            margin-bottom: 20px;
        }
        
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ff5722;
        }
        
        .social-links {
            margin-top: 30px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: #ff5722;
        }
        
        .login-link {
            margin-top: 30px;
            display: inline-block;
            color: #777;
            font-size: 14px;
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .maintenance-container {
                padding: 25px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <img src="/assets/img/logo.svg" alt="Lọc Phim Logo" class="logo">
        <div class="icon">🛠️</div>
        <h1>Trang Web Đang Bảo Trì</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <p>Chúng tôi đang nâng cấp để mang lại trải nghiệm tốt hơn cho bạn. Vui lòng quay lại sau.</p>
        
        <div class="social-links">
            <a href="#" title="Facebook">Facebook</a> |
            <a href="#" title="Twitter">Twitter</a> |
            <a href="mailto:support@locphim.com" title="Email">Email</a>
        </div>
        
        <a href="login.php" class="login-link">Đăng nhập quản trị</a>
    </div>
</body>
</html>
<?php
}
?>