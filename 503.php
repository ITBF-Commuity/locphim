<?php
/**
 * Lọc Phim - Trang lỗi 503 (Dịch vụ không khả dụng)
 */

// Thiết lập header 503
header("HTTP/1.0 503 Service Unavailable");
header("Retry-After: 3600"); // 1 giờ

// Thiết lập tiêu đề và mô tả
$page_title = 'Dịch vụ không khả dụng - Lọc Phim';
$page_description = 'Dịch vụ hiện đang tạm ngưng hoạt động để bảo trì. Vui lòng thử lại sau.';

// Thông báo hiển thị cho người dùng
$message = 'Hệ thống đang được nâng cấp để mang lại trải nghiệm tốt hơn. Vui lòng quay lại sau.';

// Nếu được truyền thông báo cụ thể, sử dụng thông báo đó
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="shortcut icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <style>
        :root {
            --primary-color: #ff5722;
            --secondary-color: #2196f3;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --container-bg: #fff;
        }
        
        @media (prefers-color-scheme: dark) {
            :root {
                --text-color: #eee;
                --bg-color: #121212;
                --container-bg: #1e1e1e;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .error-container {
            max-width: 600px;
            padding: 40px;
            background-color: var(--container-bg);
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
            line-height: 1;
        }
        
        h1 {
            font-size: 28px;
            margin: 10px 0 20px;
        }
        
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .social-links {
            margin-top: 30px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: var(--primary-color);
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
        
        /* Thêm animation cho công cụ */
        @keyframes wrench {
            0% { transform: rotate(-12deg); }
            8% { transform: rotate(12deg); }
            10% { transform: rotate(24deg); }
            18% { transform: rotate(-24deg); }
            20% { transform: rotate(-24deg); }
            28% { transform: rotate(24deg); }
            30% { transform: rotate(24deg); }
            38% { transform: rotate(-24deg); }
            40% { transform: rotate(-24deg); }
            48% { transform: rotate(24deg); }
            50% { transform: rotate(24deg); }
            58% { transform: rotate(-24deg); }
            60% { transform: rotate(-24deg); }
            68% { transform: rotate(24deg); }
            75%, 100% { transform: rotate(0deg); }
        }
        
        .fa-wrench {
            display: inline-block;
            animation: wrench 2.5s ease infinite;
            transform-origin: 90% 35%;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 25px;
            }
            
            .error-code {
                font-size: 60px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="/assets/img/logo.svg" alt="Lọc Phim Logo" class="logo">
        <h2 class="error-code">503</h2>
        <div class="icon">🛠️</div>
        <h1>Dịch vụ không khả dụng</h1>
        <p><?php echo $message; ?></p>
        
        <div class="social-links">
            <a href="#" title="Facebook">Facebook</a> |
            <a href="#" title="Twitter">Twitter</a> |
            <a href="mailto:support@locphim.com" title="Email">Email</a>
        </div>
        
        <a href="/login.php" class="login-link">Đăng nhập quản trị</a>
    </div>
</body>
</html>