<?php
// Start session first
session_start();

// Ngăn truy cập trực tiếp vào file
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Cấu hình cơ sở dữ liệu - Sử dụng PostgreSQL trên Replit
$config = [
    // Cấu hình database
    'db' => [
        'host' => getenv('PGHOST'),
        'username' => getenv('PGUSER'),
        'password' => getenv('PGPASSWORD'),
        'database' => getenv('PGDATABASE'),
        'port' => getenv('PGPORT'),
        'type' => 'postgresql',
    ],
    
    // Cấu hình website
    'site' => [
        'name' => 'Lọc Phim',
        'url' => 'https://5000-' . getenv('REPL_SLUG') . '-' . getenv('REPL_OWNER') . '.' . getenv('REPL_SLUG'),
        'admin_email' => 'admin@locphim.com',
        'version' => '1.0.0',
        'maintenance' => false,
        'debug' => true,
    ],
    
    // Cấu hình phiên và bảo mật
    'session' => [
        'lifetime' => 86400, // 24 giờ
        'secure' => false, // true cho HTTPS
        'http_only' => true,
    ],
    
    // API VNPAY
    'vnpay' => [
        'merchant_id' => 'DEMO_MERCHANT_ID',
        'secure_hash' => 'DEMO_SECURE_HASH',
        'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'return_url' => 'https://5000-' . getenv('REPL_SLUG') . '-' . getenv('REPL_OWNER') . '.' . getenv('REPL_SLUG') . '/payment-return.php',
    ],
    
    // API MOMO
    'momo' => [
        'partner_code' => 'DEMO_PARTNER_CODE',
        'access_key' => 'DEMO_ACCESS_KEY',
        'secret_key' => 'DEMO_SECRET_KEY',
        'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
        'return_url' => 'https://5000-' . getenv('REPL_SLUG') . '-' . getenv('REPL_OWNER') . '.' . getenv('REPL_SLUG') . '/payment-return.php',
    ],
    
    // Cấu hình VIP
    'vip' => [
        'levels' => [
            1 => [
                'name' => 'VIP Bạc',
                'price' => 50000,
                'duration' => 30, // ngày
                'resolution' => '720p',
                'ads' => true,
            ],
            2 => [
                'name' => 'VIP Vàng',
                'price' => 100000,
                'duration' => 30,
                'resolution' => '1080p',
                'ads' => false,
            ],
            3 => [
                'name' => 'VIP Kim Cương',
                'price' => 250000,
                'duration' => 90,
                'resolution' => '4K',
                'ads' => false,
            ]
        ]
    ],
    
    // Cấu hình tải lên
    'upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'],
        'path' => 'uploads/',
    ],
];

// Trả về cấu hình theo key (nếu được chỉ định)
function get_config($key = null) {
    global $config;
    
    if ($key === null) {
        return $config;
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return null;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối cơ sở dữ liệu
function db_connect() {
    $db_config = get_config('db');
    $conn = null;
    
    try {
        $db_type = $db_config['type'] ?? 'mysqli';
        
        if ($db_type === 'postgresql') {
            // PostgreSQL connection
            $conn_string = sprintf(
                "host=%s port=%s dbname=%s user=%s password=%s",
                $db_config['host'],
                $db_config['port'],
                $db_config['database'],
                $db_config['username'],
                $db_config['password']
            );
            
            $conn = pg_connect($conn_string);
            
            if (!$conn) {
                throw new Exception("PostgreSQL Connection Error: " . pg_last_error());
            }
            
            return $conn;
        } else {
            // MySQL connection (fallback)
            $conn = new mysqli(
                $db_config['host'],
                $db_config['username'],
                $db_config['password'],
                $db_config['database'],
                $db_config['port']
            );
            
            if ($conn->connect_error) {
                throw new Exception("MySQL Connection Error: " . $conn->connect_error);
            }
            
            // Đặt charset
            $conn->set_charset($db_config['charset'] ?? 'utf8mb4');
            
            return $conn;
        }
    } catch (Exception $e) {
        if (get_config('site.debug')) {
            die("Database connection error: " . $e->getMessage());
        } else {
            die("Cannot connect to database. Please contact administrator.");
        }
    }
}

// Khởi tạo phiên làm việc
session_start();
?>