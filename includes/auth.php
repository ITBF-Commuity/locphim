<?php
/**
 * Hàm xử lý đăng nhập, đăng ký, quên mật khẩu
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

/**
 * Đăng nhập người dùng
 * 
 * @param string $identifier Email hoặc số điện thoại
 * @param string $password Mật khẩu
 * @return array Kết quả đăng nhập
 */
function login_user($identifier, $password) {
    // Kiểm tra đăng nhập bằng email
    $user = null;
    
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $user = get_user_by_email($identifier);
    } else {
        // Kiểm tra đăng nhập bằng số điện thoại
        $user = get_user_by_phone($identifier);
    }
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Tài khoản không tồn tại'
        ];
    }
    
    // Kiểm tra trạng thái tài khoản
    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa kích hoạt'
        ];
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Mật khẩu không chính xác'
        ];
    }
    
    // Tạo session cho người dùng
    $_SESSION['user_id'] = $user['id'];
    
    // Nếu có remember me, tạo token và lưu cookie
    if (isset($_POST['remember']) && $_POST['remember'] == 1) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 ngày
        
        $data = [
            'user_id' => $user['id'],
            'token' => $token,
            'expires' => date('Y-m-d H:i:s', $expires),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('remember_tokens', $data);
        
        setcookie('remember_token', $token, $expires, '/');
    }
    
    return [
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_premium' => $user['is_premium']
        ]
    ];
}

/**
 * Đăng ký người dùng mới
 * 
 * @param string $username Tên người dùng
 * @param string $email Email
 * @param string $phone Số điện thoại (không bắt buộc)
 * @param string $password Mật khẩu
 * @return array Kết quả đăng ký
 */
function register_user($username, $email, $phone, $password) {
    // Kiểm tra username
    if (strlen($username) < 3 || strlen($username) > 50) {
        return [
            'success' => false,
            'message' => 'Tên đăng nhập phải từ 3 đến 50 ký tự'
        ];
    }
    
    // Kiểm tra email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Email không hợp lệ'
        ];
    }
    
    // Kiểm tra mật khẩu
    if (strlen($password) < 6) {
        return [
            'success' => false,
            'message' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ];
    }
    
    // Kiểm tra email đã tồn tại chưa
    $existing_user = get_user_by_email($email);
    if ($existing_user) {
        return [
            'success' => false,
            'message' => 'Email đã được sử dụng'
        ];
    }
    
    // Kiểm tra số điện thoại đã tồn tại chưa (nếu có)
    if (!empty($phone)) {
        $existing_phone = get_user_by_phone($phone);
        if ($existing_phone) {
            return [
                'success' => false,
                'message' => 'Số điện thoại đã được sử dụng'
            ];
        }
    }
    
    // Mã hóa mật khẩu
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Tạo mã xác nhận
    $verification_code = generate_verification_code();
    
    // Lưu thông tin người dùng
    $data = [
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'password' => $password_hash,
        'role' => 'user',
        'status' => 'active', // Tạm thời kích hoạt ngay
        'verification_code' => $verification_code,
        'is_premium' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $user_id = db_insert('users', $data);
    
    if (!$user_id) {
        return [
            'success' => false,
            'message' => 'Đã có lỗi xảy ra khi đăng ký. Vui lòng thử lại.'
        ];
    }
    
    // Gửi email xác nhận (chỉ trong môi trường thực tế)
    /* 
    $verify_url = BASE_URL . '/verify.php?code=' . $verification_code . '&email=' . urlencode($email);
    $message = "
        <html>
        <head>
            <title>Xác nhận tài khoản - Lọc Phim</title>
        </head>
        <body>
            <h2>Chào mừng đến với Lọc Phim!</h2>
            <p>Cảm ơn bạn đã đăng ký tài khoản. Để hoàn tất quá trình đăng ký, vui lòng nhấp vào liên kết bên dưới để xác nhận email của bạn:</p>
            <p><a href='$verify_url'>Xác nhận tài khoản</a></p>
            <p>Hoặc bạn có thể sử dụng mã xác nhận sau: <strong>$verification_code</strong></p>
            <p>Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Đội ngũ Lọc Phim</p>
        </body>
        </html>
    ";
    
    send_email($email, 'Xác nhận tài khoản - Lọc Phim', $message);
    */
    
    return [
        'success' => true,
        'message' => 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.'
    ];
}

/**
 * Xử lý quên mật khẩu
 * 
 * @param string $email Email người dùng
 * @return array Kết quả xử lý
 */
function forgot_password($email) {
    // Kiểm tra email
    $user = get_user_by_email($email);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email không tồn tại trong hệ thống'
        ];
    }
    
    // Tạo token đặt lại mật khẩu
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 giờ
    
    // Lưu token vào database
    $data = [
        'user_id' => $user['id'],
        'token' => $token,
        'expires' => $expires,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    db_insert('password_resets', $data);
    
    // Gửi email với link đặt lại mật khẩu (chỉ trong môi trường thực tế)
    /* 
    $reset_url = BASE_URL . '/pages/reset_password.php?token=' . $token;
    $message = "
        <html>
        <head>
            <title>Đặt lại mật khẩu - Lọc Phim</title>
        </head>
        <body>
            <h2>Đặt lại mật khẩu Lọc Phim</h2>
            <p>Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng nhấp vào liên kết bên dưới để đặt lại mật khẩu của bạn:</p>
            <p><a href='$reset_url'>Đặt lại mật khẩu</a></p>
            <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
            <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Đội ngũ Lọc Phim</p>
        </body>
        </html>
    ";
    
    send_email($email, 'Đặt lại mật khẩu - Lọc Phim', $message);
    */
    
    return [
        'success' => true,
        'message' => 'Hướng dẫn đặt lại mật khẩu đã được gửi đến email của bạn'
    ];
}

/**
 * Đặt lại mật khẩu
 * 
 * @param string $token Token đặt lại mật khẩu
 * @param string $password Mật khẩu mới
 * @return array Kết quả xử lý
 */
function reset_password($token, $password) {
    // Kiểm tra token
    $query = "SELECT * FROM password_resets WHERE token = ? AND expires > NOW() LIMIT 1";
    $reset = db_query_single($query, [$token]);
    
    if (!$reset) {
        return [
            'success' => false,
            'message' => 'Token không hợp lệ hoặc đã hết hạn'
        ];
    }
    
    // Kiểm tra mật khẩu
    if (strlen($password) < 6) {
        return [
            'success' => false,
            'message' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ];
    }
    
    // Mã hóa mật khẩu mới
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Cập nhật mật khẩu
    $data = [
        'password' => $password_hash,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $result = db_update('users', $data, 'id = ?', [$reset['user_id']]);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Đã có lỗi xảy ra khi đặt lại mật khẩu'
        ];
    }
    
    // Xóa token đã sử dụng
    db_delete('password_resets', 'token = ?', [$token]);
    
    return [
        'success' => true,
        'message' => 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.'
    ];
}

/**
 * Đăng xuất người dùng
 */
function logout_user() {
    // Xóa session
    unset($_SESSION['user_id']);
    
    // Xóa cookie remember token
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Xóa token từ database
        db_delete('remember_tokens', 'token = ?', [$token]);
        
        // Xóa cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Hủy toàn bộ session
    session_destroy();
}

/**
 * Kiểm tra và tự động đăng nhập từ remember token
 */
function check_remember_login() {
    if (isset($_SESSION['user_id'])) {
        return true; // Đã đăng nhập
    }
    
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        $query = "SELECT * FROM remember_tokens WHERE token = ? AND expires > NOW() LIMIT 1";
        $token_data = db_query_single($query, [$token]);
        
        if ($token_data) {
            $user = get_user_by_id($token_data['user_id']);
            
            if ($user && $user['status'] === 'active') {
                // Tạo session
                $_SESSION['user_id'] = $user['id'];
                
                // Cập nhật token
                $expires = time() + (30 * 24 * 60 * 60); // 30 ngày
                
                $data = [
                    'expires' => date('Y-m-d H:i:s', $expires)
                ];
                
                db_update('remember_tokens', $data, 'id = ?', [$token_data['id']]);
                
                // Cập nhật cookie
                setcookie('remember_token', $token, $expires, '/');
                
                return true;
            }
        }
        
        // Token không hợp lệ hoặc hết hạn, xóa cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

/**
 * Xác minh tài khoản qua email
 * 
 * @param string $email Email người dùng
 * @param string $code Mã xác nhận
 * @return array Kết quả xác minh
 */
function verify_account($email, $code) {
    $user = get_user_by_email($email);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email không tồn tại trong hệ thống'
        ];
    }
    
    if ($user['status'] === 'active' && empty($user['verification_code'])) {
        return [
            'success' => false,
            'message' => 'Tài khoản đã được xác minh'
        ];
    }
    
    if ($user['verification_code'] !== $code) {
        return [
            'success' => false,
            'message' => 'Mã xác nhận không chính xác'
        ];
    }
    
    // Cập nhật trạng thái và xóa mã xác nhận
    $data = [
        'status' => 'active',
        'verification_code' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $result = db_update('users', $data, 'id = ?', [$user['id']]);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Đã có lỗi xảy ra khi xác minh tài khoản'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Xác minh tài khoản thành công. Bạn có thể đăng nhập ngay bây giờ.'
    ];
}

/**
 * Kiểm tra và khởi tạo session nếu cần
 */
function init_auth_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    check_remember_login();
}

// Khởi tạo session khi include file này
init_auth_session();
