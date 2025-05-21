<?php
/**
 * Lọc Phim - Cấu hình
 */

// Bắt đầu phiên làm việc
session_start();

// Hiển thị lỗi (tắt trong môi trường sản xuất)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình chung
define('SITE_NAME', 'Lọc Phim');
define('SITE_DESCRIPTION', 'Xem phim lẻ, phim bộ và anime mới nhất');
define('SITE_URL', 'https://locphim.vn');
define('BASE_URL', '/');
define('DEBUG_MODE', true); // Tắt trong môi trường sản xuất
define('CACHE_VERSION', '1.0.0');

// Cấu hình Database
define('DB_TYPE', 'pgsql'); // mysql, pgsql, sqlite
define('DB_HOST', isset($_ENV['PGHOST']) ? $_ENV['PGHOST'] : 'localhost');
define('DB_PORT', isset($_ENV['PGPORT']) ? $_ENV['PGPORT'] : '5432');
define('DB_NAME', isset($_ENV['PGDATABASE']) ? $_ENV['PGDATABASE'] : 'locphim');
define('DB_USER', isset($_ENV['PGUSER']) ? $_ENV['PGUSER'] : 'postgres');
define('DB_PASS', isset($_ENV['PGPASSWORD']) ? $_ENV['PGPASSWORD'] : '');

// Cấu hình VNPAY
define('VNPAY_TMN_CODE', '');
define('VNPAY_HASH_SECRET', '');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', SITE_URL . '/vnpay-return');

// Cấu hình MOMO
define('MOMO_PARTNER_CODE', '');
define('MOMO_ACCESS_KEY', '');
define('MOMO_SECRET_KEY', '');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/gw_payment/transactionProcessor');
define('MOMO_RETURN_URL', SITE_URL . '/momo-return');
define('MOMO_NOTIFY_URL', SITE_URL . '/momo-notify');

// Cấu hình Stripe
define('STRIPE_SECRET_KEY', '');
define('STRIPE_PUBLIC_KEY', '');

// Cấu hình Upload
define('UPLOAD_DIR', 'uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Cấu hình JWT
define('JWT_SECRET', 'secretkeyforgeneratingtokens');
define('JWT_EXPIRATION', 86400); // 24 giờ

// Cấu hình VIP
define('VIP_PRICES', [
    'monthly' => 50000, // VND
    'quarterly' => 140000, // VND
    'yearly' => 500000 // VND
]);

// Load các file quan trọng
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Khởi tạo kết nối database
$db = new Database();

// Kiểm tra và cập nhật VIP nếu đã hết hạn
if (isset($_SESSION['user_id'])) {
    $user = $db->get("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    
    if ($user && isset($user['is_vip']) && $user['is_vip']) {
        // Kiểm tra cột vip_expires_at có tồn tại và có giá trị không null
        if (isset($user['vip_expires_at']) && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
            // Hết hạn VIP
            $db->update('users', [
                'is_vip' => false,
                'vip_expires_at' => null
            ], "id = ?", [$user['id']]);
            
            // Cập nhật lại thông tin user trong session
            $user['is_vip'] = false;
            $user['vip_expires_at'] = null;
            
            // Thông báo cho người dùng
            $_SESSION['toast'] = 'Tài khoản VIP của bạn đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng đặc quyền.';
            $_SESSION['toast_type'] = 'warning';
        }
    }
}