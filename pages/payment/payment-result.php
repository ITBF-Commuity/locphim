<?php
/**
 * Lọc Phim - Trang kết quả thanh toán
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán - Lọc Phim</title>
    <meta name="description" content="Kết quả thanh toán nâng cấp tài khoản VIP Lọc Phim">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #b81d24;
            --background-color: #141414;
            --text-color: #ffffff;
            --light-gray: #f1f3f5;
            --gray: #adb5bd;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-radius: 4px;
            --box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            color: var(--text-color);
            text-decoration: none;
        }
        
        a:hover {
            color: var(--primary-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 15px 0;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .main {
            flex: 1;
            padding: 40px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .result-container {
            max-width: 600px;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
        }
        
        .result-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        
        .result-icon.success {
            color: var(--success-color);
        }
        
        .result-icon.error {
            color: var(--danger-color);
        }
        
        .result-title {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .result-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: var(--gray);
        }
        
        .result-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            border-color: var(--light-gray);
            color: var(--light-gray);
        }
        
        .footer {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 20px 0;
            text-align: center;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .result-container {
                padding: 20px;
                margin: 0 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">Lọc Phim</a>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="result-container">
            <?php if ($success): ?>
            <div class="result-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="result-title">Thanh toán thành công!</h1>
            <p class="result-message"><?php echo htmlspecialchars($message); ?></p>
            <div class="result-actions">
                <a href="/" class="btn btn-primary">Xem phim ngay</a>
                <a href="/tai-khoan/vip" class="btn btn-outline">Xem thông tin VIP</a>
            </div>
            <?php else: ?>
            <div class="result-icon error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="result-title">Thanh toán thất bại</h1>
            <p class="result-message"><?php echo htmlspecialchars($message); ?></p>
            <div class="result-actions">
                <a href="/vip" class="btn btn-primary">Thử lại</a>
                <a href="/" class="btn btn-outline">Về trang chủ</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Lọc Phim. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</body>
</html>