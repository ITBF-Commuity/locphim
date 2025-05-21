<?php
/**
 * Lọc Phim - Controller xác thực
 * Quản lý đăng nhập, đăng ký, đặt lại mật khẩu
 */

class AuthController {
    /**
     * Đối tượng database
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Thông tin người dùng hiện tại
     * 
     * @var array|null
     */
    private $currentUser;
    
    /**
     * Khởi tạo controller
     * 
     * @param Database $db
     * @param array|null $currentUser
     */
    public function __construct($db, $currentUser = null) {
        $this->db = $db;
        $this->currentUser = $currentUser;
    }
    
    /**
     * Hiển thị và xử lý đăng nhập
     */
    public function login() {
        // Nếu đã đăng nhập, chuyển hướng đến trang chủ
        if ($this->currentUser) {
            redirect('/');
        }
        
        $email = '';
        $error = '';
        
        // Xử lý yêu cầu đăng nhập
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
            
            if (empty($email) || empty($password)) {
                $error = 'Vui lòng nhập đầy đủ email và mật khẩu';
            } else {
                // Tìm người dùng theo email hoặc số điện thoại
                $user = $this->db->get(
                    "SELECT * FROM users WHERE email = :email OR phone = :phone",
                    [
                        'email' => $email,
                        'phone' => $email
                    ]
                );
                
                if (!$user) {
                    $error = 'Email hoặc mật khẩu không chính xác';
                } else {
                    // Kiểm tra mật khẩu
                    if (verify_password($password, $user['password'])) {
                        // Đăng nhập thành công
                        $_SESSION['user_id'] = $user['id'];
                        
                        // Nếu chọn ghi nhớ đăng nhập
                        if ($remember) {
                            $token = generate_token();
                            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            $this->db->insert('remember_tokens', [
                                'user_id' => $user['id'],
                                'token' => $token,
                                'expires_at' => $expiry,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            // Đặt cookie hết hạn sau 30 ngày
                            setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                        }
                        
                        // Cập nhật thời gian đăng nhập
                        $this->db->update('users', [
                            'last_login' => date('Y-m-d H:i:s')
                        ], 'id = :id', [
                            'id' => $user['id']
                        ]);
                        
                        // Chuyển hướng đến trang chủ
                        redirect('/');
                    } else {
                        $error = 'Email hoặc mật khẩu không chính xác';
                    }
                }
            }
        }
        
        // Hiển thị trang đăng nhập
        include_once PAGES_PATH . '/auth/login.php';
    }
    
    /**
     * Hiển thị và xử lý đăng ký
     */
    public function register() {
        // Nếu đã đăng nhập, chuyển hướng đến trang chủ
        if ($this->currentUser) {
            redirect('/');
        }
        
        $username = '';
        $email = '';
        $phone = '';
        $error = '';
        $success = '';
        
        // Xử lý yêu cầu đăng ký
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Vui lòng nhập đầy đủ thông tin';
            } elseif (strlen($username) < 3 || strlen($username) > 20) {
                $error = 'Tên người dùng phải từ 3-20 ký tự';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự';
            } elseif ($password !== $password_confirm) {
                $error = 'Xác nhận mật khẩu không khớp';
            } else {
                // Kiểm tra username đã tồn tại chưa
                $existingUser = $this->db->get(
                    "SELECT * FROM users WHERE username = :username",
                    ['username' => $username]
                );
                
                if ($existingUser) {
                    $error = 'Tên người dùng đã tồn tại';
                } else {
                    // Kiểm tra email đã tồn tại chưa
                    $existingEmail = $this->db->get(
                        "SELECT * FROM users WHERE email = :email",
                        ['email' => $email]
                    );
                    
                    if ($existingEmail) {
                        $error = 'Email đã tồn tại';
                    } else {
                        // Kiểm tra số điện thoại đã tồn tại chưa
                        if (!empty($phone)) {
                            $existingPhone = $this->db->get(
                                "SELECT * FROM users WHERE phone = :phone",
                                ['phone' => $phone]
                            );
                            
                            if ($existingPhone) {
                                $error = 'Số điện thoại đã tồn tại';
                            }
                        }
                        
                        if (empty($error)) {
                            // Đăng ký thành công
                            $userId = $this->db->insert('users', [
                                'username' => $username,
                                'email' => $email,
                                'phone' => $phone,
                                'password' => hash_password($password),
                                'role' => 'user',
                                'is_vip' => 0,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            // Đăng nhập ngay
                            $_SESSION['user_id'] = $userId;
                            
                            // Chuyển hướng đến trang chủ
                            redirect('/');
                        }
                    }
                }
            }
        }
        
        // Hiển thị trang đăng ký
        include_once PAGES_PATH . '/auth/register.php';
    }
    
    /**
     * Hiển thị và xử lý quên mật khẩu
     */
    public function forgotPassword() {
        // Nếu đã đăng nhập, chuyển hướng đến trang chủ
        if ($this->currentUser) {
            redirect('/');
        }
        
        $email = '';
        $error = '';
        $success = '';
        
        // Xử lý yêu cầu đặt lại mật khẩu
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            
            if (empty($email)) {
                $error = 'Vui lòng nhập email';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ';
            } else {
                // Tìm người dùng theo email
                $user = $this->db->get(
                    "SELECT * FROM users WHERE email = :email",
                    ['email' => $email]
                );
                
                if (!$user) {
                    $error = 'Email không tồn tại trong hệ thống';
                } else {
                    // Tạo token đặt lại mật khẩu
                    $token = generate_token();
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    $this->db->insert('password_resets', [
                        'email' => $email,
                        'token' => $token,
                        'expires_at' => $expiry,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Tạo liên kết đặt lại mật khẩu
                    $resetLink = base_url("dat-lai-mat-khau/$token");
                    
                    // Gửi email
                    $subject = 'Đặt lại mật khẩu - ' . SITE_NAME;
                    $message = "
                        <h2>Yêu cầu đặt lại mật khẩu</h2>
                        <p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại " . SITE_NAME . ".</p>
                        <p>Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu của bạn:</p>
                        <p><a href='$resetLink'>$resetLink</a></p>
                        <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
                        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                    ";
                    
                    if (send_email($email, $subject, $message)) {
                        $success = 'Chúng tôi đã gửi liên kết đặt lại mật khẩu đến email của bạn';
                    } else {
                        $error = 'Không thể gửi email đặt lại mật khẩu. Vui lòng thử lại sau';
                    }
                }
            }
        }
        
        // Hiển thị trang quên mật khẩu
        include_once PAGES_PATH . '/auth/forgot-password.php';
    }
    
    /**
     * Hiển thị và xử lý đặt lại mật khẩu
     * 
     * @param string|null $token Token đặt lại mật khẩu
     */
    public function resetPassword($token = null) {
        // Nếu đã đăng nhập, chuyển hướng đến trang chủ
        if ($this->currentUser) {
            redirect('/');
        }
        
        $error = '';
        $success = '';
        
        // Nếu không có token trong URL, kiểm tra trong POST
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }
        
        // Nếu không có token, hiển thị trang lỗi
        if (!$token) {
            $error = 'Liên kết đặt lại mật khẩu không hợp lệ';
            include_once PAGES_PATH . '/auth/reset-password.php';
            return;
        }
        
        // Tìm token trong cơ sở dữ liệu
        $reset = $this->db->get(
            "SELECT * FROM password_resets WHERE token = :token",
            ['token' => $token]
        );
        
        // Nếu token không tồn tại hoặc đã hết hạn
        if (!$reset || strtotime($reset['expires_at']) < time()) {
            $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn';
            include_once PAGES_PATH . '/auth/reset-password.php';
            return;
        }
        
        // Xử lý yêu cầu đặt lại mật khẩu
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
            
            if (empty($password)) {
                $error = 'Vui lòng nhập mật khẩu mới';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự';
            } elseif ($password !== $password_confirm) {
                $error = 'Xác nhận mật khẩu không khớp';
            } else {
                // Tìm người dùng theo email
                $user = $this->db->get(
                    "SELECT * FROM users WHERE email = :email",
                    ['email' => $reset['email']]
                );
                
                if (!$user) {
                    $error = 'Người dùng không tồn tại';
                } else {
                    // Cập nhật mật khẩu
                    $this->db->update('users', [
                        'password' => hash_password($password),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', [
                        'id' => $user['id']
                    ]);
                    
                    // Xóa token đặt lại mật khẩu
                    $this->db->delete('password_resets', 'token = :token', ['token' => $token]);
                    
                    $success = 'Mật khẩu của bạn đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.';
                }
            }
        }
        
        // Hiển thị trang đặt lại mật khẩu
        include_once PAGES_PATH . '/auth/reset-password.php';
    }
    
    /**
     * Đăng xuất
     */
    public function logout() {
        // Xóa cookie ghi nhớ đăng nhập
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $this->db->delete('remember_tokens', 'token = :token', ['token' => $token]);
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Xóa session
        session_unset();
        session_destroy();
        
        // Chuyển hướng đến trang đăng nhập
        redirect('/dang-nhap');
    }
}