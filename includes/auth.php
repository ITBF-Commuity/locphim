<?php
/**
 * Lọc Phim - File xác thực người dùng
 * 
 * File xử lý các chức năng xác thực và phân quyền người dùng
 */

// Thời gian tồn tại của token
define('TOKEN_EXPIRE', 86400); // 24 giờ

/**
 * Lớp Auth để xử lý xác thực người dùng
 */
class Auth {
    /**
     * @var Database
     */
    private $db;
    
    /**
     * @var array|null
     */
    private $user = null;
    
    /**
     * Khởi tạo đối tượng Auth
     * 
     * @param Database $db Đối tượng Database
     */
    public function __construct($db) {
        $this->db = $db;
        $this->checkAuth();
    }
    
    /**
     * Kiểm tra xác thực người dùng
     * 
     * @return void
     */
    private function checkAuth() {
        // Kiểm tra xem có session không
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            
            // Lấy thông tin người dùng từ database
            $user = $this->db->get("SELECT * FROM users WHERE id = ?", [$userId]);
            
            if ($user) {
                $this->user = $user;
                return;
            }
            
            // Nếu không tìm thấy user, xóa session
            $this->logout();
        }
        
        // Kiểm tra xem có cookie không
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // Lấy thông tin token từ database
            $tokenData = $this->db->get(
                "SELECT user_id, expires_at FROM user_tokens WHERE token = ? AND type = 'remember'",
                [$token]
            );
            
            if ($tokenData && strtotime($tokenData['expires_at']) > time()) {
                // Lấy thông tin người dùng từ database
                $user = $this->db->get("SELECT * FROM users WHERE id = ?", [$tokenData['user_id']]);
                
                if ($user) {
                    $this->user = $user;
                    
                    // Thiết lập session
                    $_SESSION['user_id'] = $user['id'];
                    return;
                }
            }
            
            // Nếu token không hợp lệ hoặc hết hạn, xóa cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    /**
     * Đăng nhập người dùng
     * 
     * @param string $username Username hoặc email
     * @param string $password Mật khẩu
     * @param bool $remember Có lưu đăng nhập không
     * @return array Kết quả đăng nhập
     */
    public function login($username, $password, $remember = false) {
        // Kiểm tra xem username có phải là email không
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        // Lấy thông tin người dùng từ database
        $user = $this->db->get(
            "SELECT * FROM users WHERE " . ($isEmail ? "email = ?" : "username = ?"),
            [$username]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
            ];
        }
        
        // Kiểm tra mật khẩu
        if (!verifyPassword($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
            ];
        }
        
        // Kiểm tra tài khoản có bị khóa không
        if ($user['status'] != 'active') {
            return [
                'success' => false,
                'message' => 'Tài khoản đã bị khóa'
            ];
        }
        
        // Lưu session
        $_SESSION['user_id'] = $user['id'];
        $this->user = $user;
        
        // Cập nhật thời gian đăng nhập cuối
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], [
            'id' => $user['id']
        ]);
        
        // Nếu có lưu đăng nhập
        if ($remember) {
            // Tạo token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 30 * 86400); // 30 ngày
            
            // Lưu token vào database
            $this->db->insert('user_tokens', [
                'user_id' => $user['id'],
                'token' => $token,
                'type' => 'remember',
                'expires_at' => $expiresAt
            ]);
            
            // Thiết lập cookie
            setcookie('remember_token', $token, time() + 30 * 86400, '/', '', false, true);
        }
        
        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Đăng ký người dùng
     * 
     * @param string $username Tên đăng nhập
     * @param string $email Email
     * @param string $password Mật khẩu
     * @param string $phone Số điện thoại (tùy chọn)
     * @return array Kết quả đăng ký
     */
    public function register($username, $email, $password, $phone = '') {
        // Kiểm tra username
        if (strlen($username) < 3 || strlen($username) > 20) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập phải từ 3 đến 20 ký tự'
            ];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới'
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
        
        // Kiểm tra số điện thoại nếu có
        if ($phone && !preg_match('/^0[0-9]{9}$/', $phone)) {
            return [
                'success' => false,
                'message' => 'Số điện thoại không hợp lệ'
            ];
        }
        
        // Kiểm tra username và email đã tồn tại chưa
        $existUser = $this->db->get("SELECT id FROM users WHERE username = ?", [$username]);
        
        if ($existUser) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập đã tồn tại'
            ];
        }
        
        $existEmail = $this->db->get("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existEmail) {
            return [
                'success' => false,
                'message' => 'Email đã tồn tại'
            ];
        }
        
        // Kiểm tra số điện thoại đã tồn tại chưa nếu có
        if ($phone) {
            $existPhone = $this->db->get("SELECT id FROM users WHERE phone = ?", [$phone]);
            
            if ($existPhone) {
                return [
                    'success' => false,
                    'message' => 'Số điện thoại đã tồn tại'
                ];
            }
        }
        
        // Tạo mã xác nhận
        $verificationToken = bin2hex(random_bytes(16));
        
        // Tạo người dùng mới
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => hashPassword($password),
            'phone' => $phone,
            'role' => 'user',
            'status' => 'active', // Hoặc 'pending' nếu cần xác nhận email
            'verification_token' => $verificationToken,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi. Vui lòng thử lại.'
            ];
        }
        
        // Tạo hồ sơ người dùng
        $this->db->insert('user_profiles', [
            'user_id' => $userId,
            'fullname' => $username,
            'avatar' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Gửi email xác nhận tài khoản
        // TODO: Thêm code gửi email xác nhận
        
        return [
            'success' => true,
            'message' => 'Đăng ký thành công',
            'user_id' => $userId
        ];
    }
    
    /**
     * Đăng xuất
     * 
     * @return void
     */
    public function logout() {
        // Xóa session
        if (isset($_SESSION['user_id'])) {
            unset($_SESSION['user_id']);
        }
        
        // Xóa cookie nhớ mật khẩu
        if (isset($_COOKIE['remember_token'])) {
            // Xóa token khỏi database
            $this->db->delete('user_tokens', [
                'token' => $_COOKIE['remember_token'],
                'type' => 'remember'
            ]);
            
            // Xóa cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Xóa thông tin người dùng
        $this->user = null;
    }
    
    /**
     * Lấy thông tin người dùng hiện tại
     * 
     * @return array|null Thông tin người dùng hoặc null nếu chưa đăng nhập
     */
    public function getCurrentUser() {
        return $this->user;
    }
    
    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     * 
     * @return bool true nếu đã đăng nhập, false nếu chưa
     */
    public function isLoggedIn() {
        return $this->user !== null;
    }
    
    /**
     * Kiểm tra người dùng có quyền admin không
     * 
     * @return bool true nếu là admin, false nếu không
     */
    public function isAdmin() {
        return $this->user && $this->user['role'] === 'admin';
    }
    
    /**
     * Kiểm tra người dùng có quyền mod không
     * 
     * @return bool true nếu là mod, false nếu không
     */
    public function isModerator() {
        return $this->user && ($this->user['role'] === 'mod' || $this->user['role'] === 'admin');
    }
    
    /**
     * Kiểm tra người dùng có VIP không
     * 
     * @return bool true nếu là VIP, false nếu không
     */
    public function isVip() {
        return $this->user && (
            $this->user['is_vip'] == 1 || 
            $this->user['role'] === 'admin' || 
            $this->user['role'] === 'mod'
        );
    }
    
    /**
     * Tạo và gửi mã đặt lại mật khẩu
     * 
     * @param string $email Email người dùng
     * @return array Kết quả
     */
    public function sendPasswordReset($email) {
        // Kiểm tra email
        $user = $this->db->get("SELECT id, username, email FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email không tồn tại trong hệ thống'
            ];
        }
        
        // Tạo token
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 giờ
        
        // Lưu token vào database
        $this->db->insert('user_tokens', [
            'user_id' => $user['id'],
            'token' => $token,
            'type' => 'password_reset',
            'expires_at' => $expiresAt
        ]);
        
        // Tạo URL reset password
        $resetUrl = url('/dat-lai-mat-khau?token=' . $token);
        
        // Tạo nội dung email
        $subject = 'Đặt lại mật khẩu - ' . SITE_NAME;
        
        $message = "
            <html>
            <head>
                <title>Đặt lại mật khẩu</title>
            </head>
            <body>
                <p>Xin chào {$user['username']},</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu từ bạn. Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu:</p>
                <p><a href='{$resetUrl}' target='_blank'>Đặt lại mật khẩu</a></p>
                <p>Hoặc sao chép URL này vào trình duyệt của bạn: {$resetUrl}</p>
                <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                <p>Trân trọng,</p>
                <p>Đội ngũ " . SITE_NAME . "</p>
            </body>
            </html>
        ";
        
        // Gửi email
        $result = sendMail($user['email'], $subject, $message);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Một email đặt lại mật khẩu đã được gửi đến địa chỉ email của bạn'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi gửi email. Vui lòng thử lại sau.'
            ];
        }
    }
    
    /**
     * Kiểm tra token đặt lại mật khẩu
     * 
     * @param string $token Token đặt lại mật khẩu
     * @return array Kết quả
     */
    public function checkResetToken($token) {
        // Kiểm tra token
        $tokenData = $this->db->get(
            "SELECT user_id, expires_at FROM user_tokens WHERE token = ? AND type = 'password_reset'",
            [$token]
        );
        
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Token không hợp lệ'
            ];
        }
        
        // Kiểm tra thời hạn
        if (strtotime($tokenData['expires_at']) < time()) {
            return [
                'success' => false,
                'message' => 'Token đã hết hạn'
            ];
        }
        
        // Lấy thông tin người dùng
        $user = $this->db->get("SELECT id, username, email FROM users WHERE id = ?", [$tokenData['user_id']]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ];
        }
        
        return [
            'success' => true,
            'user' => $user
        ];
    }
    
    /**
     * Đặt lại mật khẩu
     * 
     * @param string $token Token đặt lại mật khẩu
     * @param string $password Mật khẩu mới
     * @param string $confirmPassword Xác nhận mật khẩu mới
     * @return array Kết quả
     */
    public function resetPassword($token, $password, $confirmPassword) {
        // Kiểm tra mật khẩu
        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'Mật khẩu phải có ít nhất 6 ký tự'
            ];
        }
        
        if ($password !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Mật khẩu xác nhận không khớp'
            ];
        }
        
        // Kiểm tra token
        $tokenCheck = $this->checkResetToken($token);
        
        if (!$tokenCheck['success']) {
            return $tokenCheck;
        }
        
        // Cập nhật mật khẩu
        $result = $this->db->update('users', [
            'password' => hashPassword($password),
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $tokenCheck['user']['id']
        ]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi cập nhật mật khẩu. Vui lòng thử lại.'
            ];
        }
        
        // Xóa token
        $this->db->delete('user_tokens', [
            'token' => $token,
            'type' => 'password_reset'
        ]);
        
        return [
            'success' => true,
            'message' => 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập với mật khẩu mới.'
        ];
    }
    
    /**
     * Tạo token xác thực OAuth
     * 
     * @param int $userId ID người dùng
     * @param string $provider Nhà cung cấp OAuth (google, facebook, etc.)
     * @param string $providerId ID từ nhà cung cấp
     * @return string Token
     */
    public function createOAuthToken($userId, $provider, $providerId) {
        // Tạo token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE);
        
        // Lưu token vào database
        $this->db->insert('user_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'type' => 'oauth',
            'provider' => $provider,
            'provider_id' => $providerId,
            'expires_at' => $expiresAt
        ]);
        
        return $token;
    }
    
    /**
     * Xác thực người dùng qua OAuth
     * 
     * @param string $provider Nhà cung cấp OAuth (google, facebook, etc.)
     * @param string $providerId ID từ nhà cung cấp
     * @param array $userData Thông tin người dùng từ OAuth
     * @return array Kết quả
     */
    public function oauthLogin($provider, $providerId, $userData) {
        // Kiểm tra xem người dùng đã tồn tại chưa
        $oauth = $this->db->get(
            "SELECT user_id FROM oauth_users WHERE provider = ? AND provider_id = ?",
            [$provider, $providerId]
        );
        
        if ($oauth) {
            // Người dùng đã tồn tại, đăng nhập
            $user = $this->db->get("SELECT * FROM users WHERE id = ?", [$oauth['user_id']]);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ];
            }
            
            // Lưu session
            $_SESSION['user_id'] = $user['id'];
            $this->user = $user;
            
            // Cập nhật thời gian đăng nhập cuối
            $this->db->update('users', [
                'last_login' => date('Y-m-d H:i:s')
            ], [
                'id' => $user['id']
            ]);
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } else {
            // Người dùng chưa tồn tại, kiểm tra email
            if (isset($userData['email'])) {
                $existUser = $this->db->get("SELECT * FROM users WHERE email = ?", [$userData['email']]);
                
                if ($existUser) {
                    // Liên kết tài khoản hiện tại với OAuth
                    $this->db->insert('oauth_users', [
                        'user_id' => $existUser['id'],
                        'provider' => $provider,
                        'provider_id' => $providerId,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Lưu session
                    $_SESSION['user_id'] = $existUser['id'];
                    $this->user = $existUser;
                    
                    // Cập nhật thời gian đăng nhập cuối
                    $this->db->update('users', [
                        'last_login' => date('Y-m-d H:i:s')
                    ], [
                        'id' => $existUser['id']
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Đăng nhập thành công',
                        'user' => [
                            'id' => $existUser['id'],
                            'username' => $existUser['username'],
                            'email' => $existUser['email'],
                            'role' => $existUser['role']
                        ]
                    ];
                }
            }
            
            // Tạo người dùng mới
            $username = 'user_' . substr(md5($providerId), 0, 8);
            
            // Kiểm tra username đã tồn tại chưa
            $existUsername = $this->db->get("SELECT id FROM users WHERE username = ?", [$username]);
            
            if ($existUsername) {
                $username = 'user_' . substr(md5($providerId . time()), 0, 8);
            }
            
            // Tạo người dùng mới
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $userData['email'] ?? '',
                'password' => hashPassword(bin2hex(random_bytes(8))),
                'role' => 'user',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại.'
                ];
            }
            
            // Tạo hồ sơ người dùng
            $this->db->insert('user_profiles', [
                'user_id' => $userId,
                'fullname' => $userData['name'] ?? $username,
                'avatar' => $userData['avatar'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Liên kết tài khoản với OAuth
            $this->db->insert('oauth_users', [
                'user_id' => $userId,
                'provider' => $provider,
                'provider_id' => $providerId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Lấy thông tin người dùng
            $user = $this->db->get("SELECT * FROM users WHERE id = ?", [$userId]);
            
            // Lưu session
            $_SESSION['user_id'] = $userId;
            $this->user = $user;
            
            return [
                'success' => true,
                'message' => 'Đăng ký thành công',
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $userData['email'] ?? '',
                    'role' => 'user'
                ]
            ];
        }
    }
}

// Khởi tạo đối tượng Auth
$auth = new Auth($db);

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 * 
 * @return bool true nếu đã đăng nhập, false nếu chưa
 */
function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

/**
 * Kiểm tra người dùng có phải admin không
 * 
 * @return bool true nếu là admin, false nếu không
 */
function isAdmin() {
    global $auth;
    return $auth->isAdmin();
}

/**
 * Kiểm tra người dùng có phải mod không
 * 
 * @return bool true nếu là mod, false nếu không
 */
function isModerator() {
    global $auth;
    return $auth->isModerator();
}

/**
 * Kiểm tra người dùng có VIP không
 * 
 * @return bool true nếu là VIP, false nếu không
 */
function isVip() {
    global $auth;
    return $auth->isVip();
}

/**
 * Lấy thông tin người dùng đang đăng nhập
 * 
 * @return array|null Thông tin người dùng hoặc null nếu chưa đăng nhập
 */
function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

/**
 * Kiểm tra quyền truy cập
 * 
 * @param string $role Quyền cần kiểm tra (user, vip, mod, admin)
 * @return bool true nếu có quyền, false nếu không
 */
function checkRole($role) {
    global $auth;
    
    switch ($role) {
        case 'user':
            return $auth->isLoggedIn();
        case 'vip':
            return $auth->isVip();
        case 'mod':
            return $auth->isModerator();
        case 'admin':
            return $auth->isAdmin();
        default:
            return false;
    }
}