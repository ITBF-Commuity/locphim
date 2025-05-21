<?php
/**
 * Lọc Phim - File khởi tạo
 * 
 * File này khởi tạo các thành phần cần thiết để ứng dụng hoạt động.
 */

// Kiểm tra file config tồn tại không
if (!file_exists(__DIR__ . '/../config.php')) {
    // Chuyển hướng đến trang cài đặt
    header('Location: /install.php');
    exit;
}

// Load cấu hình
require_once __DIR__ . '/../config.php';

// Load các thành phần cần thiết
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/routes.php';

// Khởi tạo kết nối cơ sở dữ liệu
try {
    $db = new Database();
} catch (Exception $e) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
}

// Khởi tạo session nếu chưa khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy thông tin người dùng hiện tại
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    
    // Nếu không tìm thấy thông tin người dùng thì đăng xuất
    if (!$currentUser) {
        unset($_SESSION['user_id']);
        unset($_SESSION['is_admin']);
        unset($_SESSION['is_vip']);
        
        redirectWithMessage('/dang-nhap', 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.', 'warning');
    }
    
    // Cập nhật thông tin phiên
    $_SESSION['is_admin'] = (bool)$currentUser['is_admin'];
    $_SESSION['is_vip'] = (bool)$currentUser['is_vip'];
    
    // Kiểm tra xem VIP còn hiệu lực không
    if ($currentUser['is_vip'] && !empty($currentUser['vip_expiry'])) {
        $expiry = strtotime($currentUser['vip_expiry']);
        if ($expiry < time()) {
            // VIP đã hết hạn
            $_SESSION['is_vip'] = false;
            
            // Cập nhật vào database
            $db->update('users', ['is_vip' => false], ['id' => $currentUser['id']]);
            
            // Thông báo cho người dùng
            createNotification(
                $currentUser['id'], 
                'Thành viên VIP đã hết hạn', 
                'Gói thành viên VIP của bạn đã hết hạn. Vui lòng gia hạn để tiếp tục tận hưởng các đặc quyền.'
            );
        }
    }
}

// Thiết lập biến $pageTitle mặc định
$pageTitle = SITE_NAME;

// Xử lý route
$routeHandler = handleRouting();
if ($routeHandler) {
    // Bao gồm file xử lý route
    include $routeHandler;
} else {
    // Không tìm thấy route, chuyển hướng đến trang 404
    include PAGES_PATH . '/404.php';
}