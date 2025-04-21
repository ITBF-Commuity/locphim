<?php
/**
 * File khởi tạo ứng dụng Lọc Phim
 * 
 * File này cần được include đầu tiên trong mỗi trang
 */

// Load cấu hình
require_once __DIR__ . '/config.php';

// Load kết nối cơ sở dữ liệu
require_once __DIR__ . '/db_connect.php';

// Load các hàm tiện ích cơ bản
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/functions.php';

// Load module bảo trì
require_once __DIR__ . '/includes/maintenance.php';

// Biến cho người dùng hiện tại và theme
$GLOBALS['CURRENT_USER'] = null;
$GLOBALS['CURRENT_THEME'] = DEFAULT_THEME;

// Kiểm tra phiên đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    // Lấy thông tin người dùng từ cơ sở dữ liệu
    $user = db_fetch_row(
        "SELECT users.*, roles.name as role_name, roles.slug as role_slug, roles.permissions
         FROM users
         JOIN roles ON users.role_id = roles.id
         WHERE users.id = ? AND users.status = 1", 
        [$_SESSION['user_id']]
    );
    
    if ($user) {
        // Ghi đè biến CURRENT_USER bằng thông tin người dùng
        $GLOBALS['CURRENT_USER'] = $user;
        
        // Lấy cài đặt theme của người dùng
        $settings = get_user_settings($user['id']);
        
        if ($settings && isset($settings['theme_preference'])) {
            $GLOBALS['CURRENT_THEME'] = $settings['theme_preference'];
        }
    } else {
        // Nếu không tìm thấy người dùng, xóa session
        unset($_SESSION['user_id']);
    }
} else if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
    // Nếu có cookie "remember_token", thử đăng nhập tự động
    $user = db_fetch_row(
        "SELECT users.*, roles.name as role_name, roles.slug as role_slug, roles.permissions 
         FROM users 
         JOIN roles ON users.role_id = roles.id
         WHERE users.remember_token = ? AND users.status = 1", 
        [$_COOKIE['remember_token']]
    );
    
    if ($user) {
        // Lưu session
        $_SESSION['user_id'] = $user['id'];
        
        // Cập nhật thời gian đăng nhập
        db_update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        // Ghi đè biến CURRENT_USER bằng thông tin người dùng
        $GLOBALS['CURRENT_USER'] = $user;
        
        // Lấy cài đặt theme của người dùng
        $settings = get_user_settings($user['id']);
        
        if ($settings && isset($settings['theme_preference'])) {
            $GLOBALS['CURRENT_THEME'] = $settings['theme_preference'];
        }
    } else {
        // Nếu token không hợp lệ, xóa cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Nếu có tham số 'theme' trong URL, cập nhật theme hiện tại (chỉ cho phiên làm việc này)
if (isset($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'])) {
    // Ghi đè biến CURRENT_THEME
    $GLOBALS['CURRENT_THEME'] = $_GET['theme'];
    
    // Nếu người dùng đã đăng nhập, cập nhật cài đặt
    if ($GLOBALS['CURRENT_USER']) {
        // Lưu cài đặt theme vào cơ sở dữ liệu
        $settings = get_user_settings($GLOBALS['CURRENT_USER']['id']);
        
        if ($settings) {
            db_update('user_settings', [
                'theme_preference' => $_GET['theme'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ?', [$GLOBALS['CURRENT_USER']['id']]);
        } else {
            db_insert('user_settings', [
                'user_id' => $GLOBALS['CURRENT_USER']['id'],
                'theme_preference' => $_GET['theme'],
                'subtitle_language' => 'vi',
                'audio_language' => 'vi',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}

// Đặt múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Đặt locale
setlocale(LC_TIME, 'vi_VN.UTF-8', 'vi_VN', 'vi');

// Kiểm tra chế độ bảo trì
check_maintenance_mode();
?>