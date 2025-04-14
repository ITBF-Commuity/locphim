<?php
/**
 * Xác thực admin
 * Lọc Phim - Admin Authentication
 */

// Định nghĩa hằng số bảo vệ tệp
define('SECURE_ACCESS', true);

// Include các file cần thiết
require_once dirname(dirname(__DIR__)) . '/config.php';
require_once dirname(dirname(__DIR__)) . '/functions.php';
require_once __DIR__ . '/functions.php';

// Bắt đầu phiên làm việc nếu chưa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Xác thực đăng nhập admin
 */
function admin_login($username, $password) {
    // Tìm kiếm người dùng trong CSDL
    $sql = "SELECT * FROM users WHERE username = ? AND is_admin = 1 LIMIT 1";
    $result = db_query($sql, [$username], false);
    
    $user = null;
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    // Nếu không tìm thấy người dùng
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ];
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ];
    }
    
    // Kiểm tra tài khoản có bị khóa không
    if (isset($user['status']) && $user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'
        ];
    }
    
    // Lưu thông tin đăng nhập vào session
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_last_activity'] = time();
    
    // Cập nhật thời gian đăng nhập
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    db_query($sql, [$user['id']]);
    
    return [
        'success' => true,
        'user' => $user
    ];
}

/**
 * Đăng xuất admin
 */
function admin_logout() {
    // Xóa các biến session liên quan đến admin
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_last_activity']);
    
    // Xóa CSRF token
    unset($_SESSION['csrf_token']);
    
    // Xóa các biến flash message
    unset($_SESSION['flash']);
    
    // Tùy chọn: hủy toàn bộ session
    // session_destroy();
    
    return true;
}

/**
 * Kiểm tra đăng nhập admin
 */
function is_admin_logged_in() {
    // Kiểm tra các biến session cần thiết
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        return false;
    }
    
    // Kiểm tra thời gian không hoạt động
    if (isset($_SESSION['admin_last_activity'])) {
        $timeout = get_setting('admin_session_timeout', '3600'); // Mặc định 1 giờ
        if (time() - $_SESSION['admin_last_activity'] > $timeout) {
            // Session hết hạn
            admin_logout();
            return false;
        }
    }
    
    // Cập nhật thời gian hoạt động cuối
    $_SESSION['admin_last_activity'] = time();
    
    return true;
}

/**
 * Lấy thông tin admin hiện tại
 */
function get_current_admin() {
    if (!is_admin_logged_in()) {
        return null;
    }
    
    $admin_id = $_SESSION['admin_id'];
    
    // Lấy thông tin chi tiết từ CSDL
    $sql = "SELECT * FROM users WHERE id = ? AND is_admin = 1 LIMIT 1";
    $result = db_query($sql, [$admin_id], false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Yêu cầu đăng nhập admin
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
        $_SESSION['admin_redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Chuyển hướng đến trang đăng nhập
        header('Location: login.php');
        exit;
    }
    
    return get_current_admin();
}

/**
 * Kiểm tra phiên làm việc và timeout
 * Gọi hàm này trên mỗi trang admin
 */
function check_admin_session() {
    if (is_admin_logged_in()) {
        // Kiểm tra thời gian không hoạt động
        $timeout = get_setting('admin_session_timeout', '3600'); // Mặc định 1 giờ
        if (time() - $_SESSION['admin_last_activity'] > $timeout) {
            // Session hết hạn
            admin_logout();
            
            // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
            $_SESSION['admin_redirect_url'] = $_SERVER['REQUEST_URI'];
            
            // Đặt thông báo
            set_flash_message('warning', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
            
            // Chuyển hướng đến trang đăng nhập
            header('Location: login.php');
            exit;
        }
        
        // Cập nhật thời gian hoạt động cuối
        $_SESSION['admin_last_activity'] = time();
    }
}

/**
 * Lấy đường dẫn chuyển hướng sau khi đăng nhập
 */
function get_admin_redirect_url() {
    if (isset($_SESSION['admin_redirect_url'])) {
        $url = $_SESSION['admin_redirect_url'];
        unset($_SESSION['admin_redirect_url']);
        return $url;
    }
    
    return 'index.php';
}

/**
 * Tạo mật khẩu ngẫu nhiên
 */
function generate_random_password($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Đặt lại mật khẩu admin
 */
function reset_admin_password($email) {
    // Kiểm tra email có tồn tại không
    $sql = "SELECT * FROM users WHERE email = ? AND is_admin = 1 LIMIT 1";
    $result = db_query($sql, [$email], false);
    
    $user = null;
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email không tồn tại trong hệ thống.'
        ];
    }
    
    // Tạo mã xác nhận
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Lưu mã xác nhận vào CSDL
    $sql = "INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())";
    db_query($sql, [$email, $token, $expires]);
    
    // Tạo URL đặt lại mật khẩu
    $reset_url = get_config('site.url') . '/admin/reset-password.php?token=' . $token;
    
    // Gửi email (giả lập)
    $message = "Chào {$user['username']},\n\n";
    $message .= "Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu:\n\n";
    $message .= $reset_url . "\n\n";
    $message .= "Liên kết này sẽ hết hạn sau 1 giờ.\n\n";
    $message .= "Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.\n\n";
    $message .= "Trân trọng,\n";
    $message .= "Ban quản trị Lọc Phim";
    
    $headers = 'From: ' . get_config('site.admin_email') . "\r\n" .
               'Reply-To: ' . get_config('site.admin_email') . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    // Ghi log
    error_log("Password reset requested for admin: $email. Reset URL: $reset_url");
    
    // Trong môi trường thực, sử dụng mail() hoặc thư viện gửi email
    // mail($email, 'Đặt lại mật khẩu - Lọc Phim Admin', $message, $headers);
    
    return [
        'success' => true,
        'message' => 'Hướng dẫn đặt lại mật khẩu đã được gửi đến email của bạn.',
        'debug_reset_url' => $reset_url // Chỉ sử dụng trong môi trường phát triển
    ];
}

/**
 * Xác thực token đặt lại mật khẩu
 */
function verify_reset_token($token) {
    // Kiểm tra token có tồn tại không
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1";
    $result = db_query($sql, [$token], false);
    
    $reset = null;
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $reset = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
        }
    }
    
    if (!$reset) {
        return [
            'success' => false,
            'message' => 'Mã xác nhận không hợp lệ hoặc đã hết hạn.'
        ];
    }
    
    return [
        'success' => true,
        'email' => $reset['email']
    ];
}

/**
 * Thay đổi mật khẩu
 */
function change_password($email, $password) {
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Cập nhật mật khẩu
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $result = db_query($sql, [$hashed_password, $email]);
    
    // Xóa tất cả token đặt lại mật khẩu cho email này
    $sql = "DELETE FROM password_resets WHERE email = ?";
    db_query($sql, [$email]);
    
    return [
        'success' => true,
        'message' => 'Mật khẩu đã được thay đổi thành công.'
    ];
}

/**
 * Kiểm tra và tạo bảng admin_logs nếu chưa tồn tại
 */
function check_and_create_admin_logs_table() {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        $sql = "
            CREATE TABLE IF NOT EXISTS admin_logs (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $sql = "
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
    }
    
    db_query($sql);
}

// Gọi hàm kiểm tra và tạo bảng admin_logs
check_and_create_admin_logs_table();

// Kiểm tra phiên làm việc admin trên mỗi trang
check_admin_session();