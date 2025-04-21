<?php
/**
 * Lọc Phim - Trang lỗi 500 (Lỗi server)
 */

// Thiết lập header 500
header("HTTP/1.0 500 Internal Server Error");

// Thiết lập tiêu đề và mô tả
$page_title = 'Lỗi máy chủ - Lọc Phim';
$page_description = 'Đã xảy ra lỗi máy chủ. Vui lòng thử lại sau.';

// Thông báo hiển thị cho người dùng
$message = 'Đã xảy ra lỗi trên máy chủ. Chúng tôi đang khắc phục vấn đề này.';

// Nếu được truyền thông báo cụ thể, sử dụng thông báo đó
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Nếu có thông tin lỗi chi tiết và đang ở chế độ debug
$error_detail = '';
if (defined('DEBUG_MODE') && DEBUG_MODE && isset($_GET['error'])) {
    $error_detail = htmlspecialchars($_GET['error']);
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
            --primary-color: #f44336;
            --secondary-color: #ff9800;
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
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.2s;
            margin: 10px;
        }
        
        .btn:hover {
            background-color: #e68a00;
            transform: translateY(-2px);
        }
        
        .error-details {
            margin-top: 30px;
            padding: 15px;
            background-color: rgba(244, 67, 54, 0.1);
            border-radius: 5px;
            text-align: left;
            font-family: monospace;
            overflow-x: auto;
        }
        
        /* Thêm animation cho biểu tượng lỗi */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .fa-exclamation-triangle {
            animation: pulse 2s infinite;
            display: inline-block;
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
        <h2 class="error-code">500</h2>
        <div class="icon">⚠️</div>
        <h1>Lỗi máy chủ nội bộ</h1>
        <p><?php echo $message; ?></p>
        
        <?php if (!empty($error_detail)): ?>
        <div class="error-details">
            <strong>Chi tiết lỗi:</strong>
            <pre><?php echo $error_detail; ?></pre>
        </div>
        <?php endif; ?>
        
        <div>
            <a href="/" class="btn">Trang chủ</a>
            <a href="javascript:location.reload()" class="btn">Thử lại</a>
        </div>
        
        <div class="social-links">
            <a href="#" title="Facebook">Facebook</a> |
            <a href="#" title="Twitter">Twitter</a> |
            <a href="mailto:support@locphim.com" title="Email">Báo cáo lỗi</a>
        </div>
    </div>
</body>
</html>