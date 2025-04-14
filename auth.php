<?php
// Ngăn truy cập trực tiếp vào file
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Đăng ký người dùng mới
 */
function register_user($username, $email, $phone, $password) {
    // Kiểm tra username đã tồn tại
    $sql_check_username = "SELECT * FROM users WHERE username = ?";
    $result_username = db_query($sql_check_username, [$username], false);
    
    $db_type = get_config('db.type');
    $username_exists = false;
    
    if ($db_type === 'postgresql') {
        $username_exists = pg_num_rows($result_username) > 0;
    } else {
        $username_exists = $result_username->num_rows > 0;
    }
    
    if ($username_exists) {
        return [
            'success' => false,
            'message' => 'Tên đăng nhập đã tồn tại.'
        ];
    }
    
    // Kiểm tra email đã tồn tại
    $sql_check_email = "SELECT * FROM users WHERE email = ?";
    $result_email = db_query($sql_check_email, [$email], false);
    
    $email_exists = false;
    if ($db_type === 'postgresql') {
        $email_exists = pg_num_rows($result_email) > 0;
    } else {
        $email_exists = $result_email->num_rows > 0;
    }
    
    if ($email_exists) {
        return [
            'success' => false,
            'message' => 'Email đã được sử dụng.'
        ];
    }
    
    // Kiểm tra số điện thoại đã tồn tại
    if (!empty($phone)) {
        $sql_check_phone = "SELECT * FROM users WHERE phone = ?";
        $result_phone = db_query($sql_check_phone, [$phone], false);
        
        $phone_exists = false;
        if ($db_type === 'postgresql') {
            $phone_exists = pg_num_rows($result_phone) > 0;
        } else {
            $phone_exists = $result_phone->num_rows > 0;
        }
        
        if ($phone_exists) {
            return [
                'success' => false,
                'message' => 'Số điện thoại đã được sử dụng.'
            ];
        }
    }
    
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Tạo token kích hoạt
    $activation_token = bin2hex(random_bytes(32));
    
    // Thêm người dùng vào cơ sở dữ liệu
    $sql_insert = "INSERT INTO users (username, email, phone, password, activation_token, status, created_at) 
                   VALUES (?, ?, ?, ?, ?, 'inactive', NOW())";
    
    $result = db_query($sql_insert, [$username, $email, $phone, $hashed_password, $activation_token]);
    
    if ($result['affected_rows'] > 0) {
        // Gửi email kích hoạt (mã xác thực)
        send_activation_email($email, $username, $activation_token);
        
        return [
            'success' => true,
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để kích hoạt tài khoản.',
            'user_id' => $result['insert_id']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại.'
        ];
    }
}

/**
 * Gửi email kích hoạt
 */
function send_activation_email($email, $username, $token) {
    $site_url = get_config('site.url');
    $site_name = get_config('site.name');
    
    $activation_link = "$site_url/activate.php?token=$token";
    
    $subject = "Kích hoạt tài khoản $site_name";
    
    $message = "
    <html>
    <head>
        <title>Kích hoạt tài khoản</title>
    </head>
    <body>
        <p>Xin chào $username,</p>
        <p>Cảm ơn bạn đã đăng ký tài khoản tại $site_name.</p>
        <p>Vui lòng nhấp vào liên kết dưới đây để kích hoạt tài khoản:</p>
        <p><a href='$activation_link'>Kích hoạt tài khoản</a></p>
        <p>Hoặc sao chép đường dẫn sau vào trình duyệt: $activation_link</p>
        <p>Liên kết này sẽ hết hạn trong vòng 24 giờ.</p>
        <p>Trân trọng,</p>
        <p>Đội ngũ $site_name</p>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $site_name <" . get_config('site.admin_email') . ">" . "\r\n";
    
    // Trong môi trường phát triển, chỉ ghi log thay vì gửi email thật
    if (get_config('site.debug')) {
        error_log("Activation email to $email: $activation_link");
        return true;
    } else {
        return mail($email, $subject, $message, $headers);
    }
}

/**
 * Kích hoạt tài khoản
 */
function activate_account($token) {
    $sql = "SELECT * FROM users WHERE activation_token = ? AND status = 'inactive'";
    $result = db_query($sql, [$token], false);
    
    $db_type = get_config('db.type');
    $user = null;
    
    if ($db_type === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    if ($user) {
        // Kiểm tra token có hết hạn không (24 giờ)
        $created_time = strtotime($user['created_at']);
        $current_time = time();
        
        if (($current_time - $created_time) > 86400) {
            return [
                'success' => false,
                'message' => 'Liên kết kích hoạt đã hết hạn. Vui lòng yêu cầu liên kết mới.'
            ];
        }
        
        // Kích hoạt tài khoản
        $sql_update = "UPDATE users SET status = 'active', activation_token = NULL WHERE id = ?";
        $update_result = db_query($sql_update, [$user['id']]);
        
        if ($update_result['affected_rows'] > 0) {
            return [
                'success' => true,
                'message' => 'Tài khoản đã được kích hoạt thành công. Bạn có thể đăng nhập ngay bây giờ.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể kích hoạt tài khoản. Vui lòng thử lại sau.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Mã kích hoạt không hợp lệ hoặc tài khoản đã được kích hoạt.'
        ];
    }
}

/**
 * Đăng nhập
 */
function login($username_or_email, $password, $remember = false) {
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?)";
    $result = db_query($sql, [$username_or_email, $username_or_email, $username_or_email], false);
    
    $db_type = get_config('db.type');
    $user = null;
    
    if ($db_type === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    if ($user) {
        // Kiểm tra trạng thái tài khoản
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Tài khoản chưa được kích hoạt hoặc đã bị khóa.'
            ];
        }
        
        // Xác thực mật khẩu
        if (password_verify($password, $user['password'])) {
            // Lưu thông tin người dùng vào session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Cập nhật thời gian đăng nhập cuối
            $sql_update = "UPDATE users SET last_login = NOW() WHERE id = ?";
            db_query($sql_update, [$user['id']]);
            
            // Xử lý "Ghi nhớ đăng nhập"
            if ($remember) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $validator);
                $expires = date('Y-m-d H:i:s', time() + 2592000); // 30 ngày
                
                // Lưu token
                $sql_token = "INSERT INTO auth_tokens (user_id, selector, token, expires) VALUES (?, ?, ?, ?)";
                db_query($sql_token, [$user['id'], $selector, $token_hash, $expires]);
                
                // Tạo cookie
                $cookie_value = $selector . ':' . $validator;
                setcookie('remember_me', $cookie_value, time() + 2592000, '/', '', false, true);
            }
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Mật khẩu không đúng.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Tài khoản không tồn tại.'
        ];
    }
}

/**
 * Kiểm tra đăng nhập từ cookie "Ghi nhớ đăng nhập"
 */
function check_remember_me() {
    $db_type = get_config('db.type');
    
    if (isset($_COOKIE['remember_me']) && !isset($_SESSION['logged_in'])) {
        list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
        
        $sql = "SELECT t.*, u.* FROM auth_tokens t JOIN users u ON t.user_id = u.id 
                WHERE t.selector = ? AND t.expires > NOW()";
        
        $result = db_query($sql, [$selector], false);
        
        $row = null;
        
        if ($db_type === 'postgresql') {
            if (pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
            }
        } else {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
            }
        }
        
        if ($row) {
            $token_hash = hash('sha256', $validator);
            
            if (hash_equals($token_hash, $row['token'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['logged_in'] = true;
                
                // Cập nhật thời gian đăng nhập
                $sql_update = "UPDATE users SET last_login = NOW() WHERE id = ?";
                db_query($sql_update, [$row['user_id']]);
                
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Đăng xuất
 */
function logout() {
    // Xóa token "Ghi nhớ đăng nhập" nếu có
    if (isset($_COOKIE['remember_me'])) {
        list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
        
        $sql = "DELETE FROM auth_tokens WHERE selector = ?";
        db_query($sql, [$selector]);
        
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }
    
    // Hủy session
    $_SESSION = [];
    
    // Xóa cookie của session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Hủy phiên
    session_destroy();
    
    return true;
}

/**
 * Kiểm tra đăng nhập
 */
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Lấy thông tin người dùng hiện tại
 */
function get_user_info() {
    if (!is_logged_in()) {
        return null;
    }
    
    $sql = "SELECT id, username, email, phone, role, avatar, created_at, last_login 
            FROM users WHERE id = ?";
    
    $result = db_query($sql, [$_SESSION['user_id']], false);
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
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
 * Quên mật khẩu
 */
function forgot_password($email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    $result = db_query($sql, [$email], false);
    
    $db_type = get_config('db.type');
    $user = null;
    
    if ($db_type === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    if ($user) {
        // Tạo token đặt lại mật khẩu
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 giờ
        
        // Lưu token
        $sql_token = "INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)";
        db_query($sql_token, [$user['id'], $token, $expires]);
        
        // Gửi email đặt lại mật khẩu
        send_reset_password_email($user['email'], $user['username'], $token);
        
        return [
            'success' => true,
            'message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Không tìm thấy tài khoản với email này.'
        ];
    }
}

/**
 * Gửi email đặt lại mật khẩu
 */
function send_reset_password_email($email, $username, $token) {
    $site_url = get_config('site.url');
    $site_name = get_config('site.name');
    
    $reset_link = "$site_url/reset-password.php?token=$token";
    
    $subject = "Đặt lại mật khẩu - $site_name";
    
    $message = "
    <html>
    <head>
        <title>Đặt lại mật khẩu</title>
    </head>
    <body>
        <p>Xin chào $username,</p>
        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại $site_name.</p>
        <p>Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu:</p>
        <p><a href='$reset_link'>Đặt lại mật khẩu</a></p>
        <p>Hoặc sao chép đường dẫn sau vào trình duyệt: $reset_link</p>
        <p>Liên kết này sẽ hết hạn trong vòng 1 giờ.</p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <p>Trân trọng,</p>
        <p>Đội ngũ $site_name</p>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $site_name <" . get_config('site.admin_email') . ">" . "\r\n";
    
    // Trong môi trường phát triển, chỉ ghi log thay vì gửi email thật
    if (get_config('site.debug')) {
        error_log("Reset password email to $email: $reset_link");
        return true;
    } else {
        return mail($email, $subject, $message, $headers);
    }
}

/**
 * Kiểm tra token đặt lại mật khẩu
 */
function verify_reset_token($token) {
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expires > NOW()";
    $result = db_query($sql, [$token], false);
    
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return false;
}

/**
 * Đặt lại mật khẩu
 */
function reset_password($token, $password) {
    $token_data = verify_reset_token($token);
    
    if ($token_data) {
        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu
        $sql_update = "UPDATE users SET password = ? WHERE id = ?";
        $result = db_query($sql_update, [$hashed_password, $token_data['user_id']]);
        
        // Xóa token
        $sql_delete = "DELETE FROM password_resets WHERE token = ?";
        db_query($sql_delete, [$token]);
        
        if ($result['affected_rows'] > 0) {
            return [
                'success' => true,
                'message' => 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể đặt lại mật khẩu. Vui lòng thử lại sau.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
        ];
    }
}

/**
 * Kiểm tra quyền admin
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Yêu cầu đăng nhập
 */
function require_login() {
    if (!is_logged_in()) {
        // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: login.php');
        exit;
    }
}

/**
 * Yêu cầu quyền admin
 */
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}
?>