<?php
/**
 * Cấu hình chung cho hệ thống Lọc Phim
 */

// Cấu hình cơ bản
define('SITE_NAME', 'Lọc Phim');
define('SITE_VERSION', '1.0');
define('DEBUG_MODE', true);

// Cấu hình URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
define('SITE_URL', $protocol . $host);
define('ASSETS_URL', SITE_URL . '/assets');

// Cấu hình theme
define('DEFAULT_THEME', 'light');

// Cấu hình cơ sở dữ liệu
// Sử dụng SQLite để tránh vấn đề với PostgreSQL extensions
define('DB_TYPE', 'sqlite'); // mysql, pgsql, sqlite, mariadb
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'loc_phim');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CONNECTION_STRING', '');

// Đường dẫn tới file SQLite
define('SQLITE_PATH', __DIR__ . '/loc_phim.db');

define('DB_CHARSET', 'utf8mb4');

// Cấu hình salt key cho mật khẩu
define('PASSWORD_SALT', 'locphim_salt_key');

// Cấu hình session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Cấu hình upload
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Cấu hình Google Drive
define('GOOGLE_DRIVE_API_KEY', '');

// Cấu hình VNPAY
define('VNPAY_TMN_CODE', '');
define('VNPAY_HASH_SECRET', '');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', SITE_URL . '/payment_return.php');

// Cấu hình MoMo
define('MOMO_PARTNER_CODE', '');
define('MOMO_ACCESS_KEY', '');
define('MOMO_SECRET_KEY', '');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/gw_payment/transactionProcessor');
define('MOMO_RETURN_URL', SITE_URL . '/payment_return.php');
define('MOMO_NOTIFY_URL', SITE_URL . '/payment_notify.php');

// Cấu hình thời gian
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình email
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'no-reply@locphim.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Cấu hình phân trang
define('PER_PAGE', 20);

// Cấu hình cache
define('CACHE_ENABLE', false);
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_TTL', 3600); // 1 giờ

// Các cấu hình khác
define('DEFAULT_AVATAR', ASSETS_URL . '/img/default-avatar.svg');
define('DEFAULT_POSTER', ASSETS_URL . '/img/default-poster.svg');
define('DEFAULT_THUMBNAIL', ASSETS_URL . '/img/default-thumbnail.svg');
?>