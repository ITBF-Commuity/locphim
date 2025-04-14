<?php
/**
 * Kiểm tra chế độ bảo trì
 * File này được gọi từ index.php và các trang khác để kiểm tra xem trang web có đang trong chế độ bảo trì không
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối file cấu hình
require_once __DIR__ . '/config.php';

/**
 * Kiểm tra chế độ bảo trì và hiển thị trang bảo trì nếu cần
 * 
 * @return bool True nếu đang trong chế độ bảo trì, False nếu không
 */
function check_maintenance_mode() {
    // Lấy trạng thái bảo trì từ cơ sở dữ liệu
    $maintenance_mode = get_setting('maintenance_mode');
    $is_maintenance = ($maintenance_mode === '1');
    
    // Nếu không trong chế độ bảo trì, trả về false
    if (!$is_maintenance) {
        return false;
    }
    
    // Kiểm tra xem người dùng hiện tại có phải là admin không
    $is_admin = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    
    // Cho phép admin truy cập ngay cả khi đang bảo trì
    if ($is_admin) {
        return false;
    }
    
    // Kiểm tra xem có phải đang truy cập trang admin không
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($current_path, '/admin/') !== false) {
        return false; // Cho phép truy cập trang admin
    }
    
    // Kiểm tra xem có phải đang truy cập các trang đặc biệt không
    $allowed_pages = [
        '/login.php',
        '/admin/login.php',
        '/maintenance.php'
    ];
    
    foreach ($allowed_pages as $page) {
        if (strpos($current_path, $page) !== false) {
            return false; // Cho phép truy cập các trang đặc biệt
        }
    }
    
    // Hiển thị trang bảo trì
    display_maintenance_page();
    exit;
}

/**
 * Hiển thị trang bảo trì
 */
function display_maintenance_page() {
    // Lấy thông báo bảo trì từ cơ sở dữ liệu
    $maintenance_message = get_setting('maintenance_message', 'Trang web đang được bảo trì. Vui lòng quay lại sau.');
    $site_name = get_setting('site_name', 'Lọc Phim');
    $maintenance_end_time = get_setting('maintenance_end_time', '');
    
    // Thiết lập header
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 giờ
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang Bảo Trì - <?php echo $site_name; ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --text-color: #343a40;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .maintenance-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .maintenance-icon {
            font-size: 80px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .maintenance-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .maintenance-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: var(--text-color);
        }
        
        .maintenance-info {
            font-size: 16px;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .maintenance-time {
            display: inline-block;
            padding: 8px 15px;
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
            border-radius: 4px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .maintenance-login {
            margin-top: 30px;
        }
        
        .maintenance-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        
        .maintenance-link:hover {
            background-color: #0069d9;
        }
        
        .maintenance-footer {
            margin-top: 30px;
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .maintenance-container {
                padding: 30px;
                box-shadow: none;
            }
            
            .maintenance-title {
                font-size: 24px;
            }
            
            .maintenance-message {
                font-size: 16px;
            }
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1 class="maintenance-title">Đang Bảo Trì</h1>
        <div class="maintenance-message">
            <?php echo nl2br(htmlspecialchars($maintenance_message)); ?>
        </div>
        
        <?php if (!empty($maintenance_end_time)): ?>
            <div class="maintenance-info">
                Dự kiến hoàn thành vào:
                <div class="maintenance-time">
                    <?php echo htmlspecialchars($maintenance_end_time); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="maintenance-login">
            <p>Bạn là quản trị viên?</p>
            <a href="/admin/login.php" class="maintenance-link">Đăng nhập</a>
        </div>
        
        <div class="maintenance-footer">
            &copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. Tất cả quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * Lấy giá trị cài đặt từ bảng settings
 */
function get_setting($key, $default = null) {
    $sql = "SELECT value FROM settings WHERE key = ?";
    $result = db_query($sql, [$key], false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return $row['value'] ?? $default;
        }
    } else {
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['value'] ?? $default;
        }
    }
    
    return $default;
}

// Kiểm tra chế độ bảo trì khi file được gọi
$is_maintenance_mode = check_maintenance_mode();