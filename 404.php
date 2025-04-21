<?php
/**
 * Lọc Phim - Trang lỗi 404 (Không tìm thấy)
 */

// Thiết lập header 404
header("HTTP/1.0 404 Not Found");

// Thiết lập tiêu đề và mô tả
$page_title = 'Không tìm thấy trang - Lọc Phim';
$page_description = 'Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.';

// Load header nếu cần thiết
if (file_exists('includes/header.php')) {
    include 'includes/header.php';
} else {
    // Nếu không tìm thấy header, hiển thị trang lỗi độc lập
    ?><!DOCTYPE html>
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
                --secondary-color: #03a9f4;
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
            
            .links {
                margin-top: 30px;
            }
            
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: var(--primary-color);
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background-color 0.3s, transform 0.2s;
                margin: 0 10px;
            }
            
            .btn:hover {
                background-color: #e64a19;
                transform: translateY(-2px);
            }
            
            .search-form {
                margin: 30px 0;
            }
            
            .search-form input {
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                width: 60%;
                font-size: 16px;
            }
            
            .search-form button {
                padding: 10px 15px;
                background-color: var(--primary-color);
                color: white;
                border: none;
                border-radius: 5px;
                margin-left: 10px;
                cursor: pointer;
                font-size: 16px;
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
                
                .search-form input {
                    width: 100%;
                    margin-bottom: 10px;
                }
                
                .search-form button {
                    margin-left: 0;
                    width: 100%;
                }
                
                .btn {
                    display: block;
                    margin: 10px 0;
                }
            }
        </style>
    </head>
    <body>
    <?php
}
?>

<div class="error-container">
    <img src="/assets/img/logo.svg" alt="Lọc Phim Logo" class="logo">
    <h2 class="error-code">404</h2>
    <h1>Không tìm thấy trang</h1>
    <p>Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p>
    
    <div class="search-form">
        <form action="/search.php" method="get">
            <input type="text" name="q" placeholder="Tìm kiếm phim, anime...">
            <button type="submit">Tìm kiếm</button>
        </form>
    </div>
    
    <p>Hoặc bạn có thể:</p>
    
    <div class="links">
        <a href="/" class="btn">Trang chủ</a>
        <a href="/movies.php" class="btn">Xem tất cả phim</a>
    </div>
</div>

<?php
// Load footer nếu cần thiết
if (file_exists('includes/footer.php') && isset($header_loaded)) {
    include 'includes/footer.php';
} else if (!isset($header_loaded)) {
    // Đóng HTML nếu đang hiển thị trang độc lập
    echo '</body></html>';
}
?>