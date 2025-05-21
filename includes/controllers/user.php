<?php
/**
 * Lọc Phim - Controller xử lý tài khoản người dùng
 */

class UserController {
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
     * Hiển thị trang hồ sơ người dùng
     */
    public function profile() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Lấy thống kê xem phim
        $totalWatchTime = $this->db->getOne(
            "SELECT SUM(watched_time) 
            FROM watch_history 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        $totalWatchTime = $totalWatchTime ? $totalWatchTime : 0;
        $watchTimeHours = floor($totalWatchTime / 3600);
        $watchTimeMinutes = floor(($totalWatchTime % 3600) / 60);
        
        // Lấy số phim đã xem
        $watchedMoviesCount = $this->db->getOne(
            "SELECT COUNT(DISTINCT movie_id) 
            FROM watch_history 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        // Lấy số tập phim đã xem
        $watchedEpisodesCount = $this->db->getOne(
            "SELECT COUNT(DISTINCT episode_id) 
            FROM watch_history 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        // Lấy số phim yêu thích
        $favoritesCount = $this->db->getOne(
            "SELECT COUNT(*) 
            FROM favorites 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        // Lấy lịch sử xem gần đây
        // Sử dụng cả created_at hoặc last_watched_at (tùy theo database)
        $timeColumn = $this->db->getDbType() === 'pgsql' ? 'wh.last_watched_at' : 'wh.updated_at';
        
        $recentHistory = $this->db->getAll(
            "SELECT wh.id, wh.user_id, wh.episode_id, wh.watched_seconds, wh.completed, 
            $timeColumn as updated_at,
            m.id as movie_id, m.title as movie_title, m.poster, 
            e.title as episode_title, e.episode_number 
            FROM watch_history wh
            JOIN episodes e ON wh.episode_id = e.id
            JOIN movies m ON e.movie_id = m.id
            WHERE wh.user_id = :user_id
            ORDER BY $timeColumn DESC
            LIMIT 5",
            ['user_id' => $this->currentUser['id']]
        );
        
        // Hiển thị trang hồ sơ
        include_once PAGES_PATH . '/user/profile.php';
    }
    
    /**
     * Hiển thị danh sách phim yêu thích
     */
    public function favorites() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Phân trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Lấy danh sách phim yêu thích
        $favorites = $this->db->getAll(
            "SELECT f.*, m.* 
            FROM favorites f
            JOIN movies m ON f.movie_id = m.id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'user_id' => $this->currentUser['id'],
                'limit' => $perPage,
                'offset' => $offset
            ]
        );
        
        // Lấy tổng số phim yêu thích
        $totalFavorites = $this->db->getOne(
            "SELECT COUNT(*) 
            FROM favorites 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        $totalPages = ceil($totalFavorites / $perPage);
        
        // Hiển thị trang phim yêu thích
        include_once PAGES_PATH . '/user/favorites.php';
    }
    
    /**
     * Hiển thị lịch sử xem phim
     */
    public function history() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Phân trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Lấy lịch sử xem phim
        // Kiểm tra cấu trúc của bảng watch_history
        $dbType = $this->db->getDbType();
        
        if ($dbType === 'pgsql') {
            // PostgreSQL columns
            $timeColumn = 'wh.created_at';
            $completedColumn = 'wh.is_completed';
            
            $history = $this->db->getAll(
                "SELECT wh.id, wh.user_id, wh.episode_id, 0 as watched_seconds, {$completedColumn} as completed, 
                {$timeColumn} as updated_at,
                e.movie_id as movie_id, m.title as movie_title, m.poster, 
                e.title as episode_title, e.episode_number 
                FROM watch_history wh
                JOIN episodes e ON wh.episode_id = e.id
                JOIN movies m ON e.movie_id = m.id
                WHERE wh.user_id = :user_id
                ORDER BY {$timeColumn} DESC
                LIMIT :limit OFFSET :offset",
                [
                    'user_id' => $this->currentUser['id'],
                    'limit' => $perPage,
                    'offset' => $offset
                ]
            );
        } else {
            // MySQL/SQLite columns
            $timeColumn = 'wh.updated_at';
            $completedColumn = 'wh.completed';
            
            $history = $this->db->getAll(
                "SELECT wh.id, wh.user_id, wh.episode_id, wh.watched_seconds, {$completedColumn} as completed, 
                {$timeColumn} as updated_at,
                m.id as movie_id, m.title as movie_title, m.poster, 
                e.title as episode_title, e.episode_number 
                FROM watch_history wh
                JOIN episodes e ON wh.episode_id = e.id
                JOIN movies m ON e.movie_id = m.id
                WHERE wh.user_id = :user_id
                ORDER BY {$timeColumn} DESC
                LIMIT :limit OFFSET :offset",
                [
                    'user_id' => $this->currentUser['id'],
                    'limit' => $perPage,
                    'offset' => $offset
                ]
            );
        }
        
        // Lấy tổng số lịch sử xem
        $totalHistory = $this->db->getOne(
            "SELECT COUNT(*) 
            FROM watch_history 
            WHERE user_id = :user_id",
            ['user_id' => $this->currentUser['id']]
        );
        
        $totalPages = ceil($totalHistory / $perPage);
        
        // Hiển thị trang lịch sử xem
        include_once PAGES_PATH . '/user/history.php';
    }
    
    /**
     * Hiển thị và xử lý form cập nhật thông tin tài khoản
     */
    public function updateProfile() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        $error = '';
        $success = '';
        $user = $this->currentUser;
        
        // Xử lý form cập nhật thông tin
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Kiểm tra form token
            if (!isset($_POST['form_token']) || !verify_form_token($_POST['form_token'])) {
                $error = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
            } else {
                // Lấy dữ liệu từ form
                $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
                
                // Kiểm tra email
                if (empty($email) || !is_valid_email($email)) {
                    $error = 'Email không hợp lệ.';
                }
                // Kiểm tra số điện thoại
                elseif (!empty($phone) && !is_valid_phone($phone)) {
                    $error = 'Số điện thoại không hợp lệ.';
                } else {
                    // Kiểm tra xem email hoặc số điện thoại đã tồn tại chưa
                    $existingUser = $this->db->get(
                        "SELECT * FROM users 
                        WHERE (email = :email OR (phone IS NOT NULL AND phone = :phone)) 
                        AND id <> :id",
                        [
                            'email' => $email,
                            'phone' => $phone,
                            'id' => $user['id']
                        ]
                    );
                    
                    if ($existingUser) {
                        if ($existingUser['email'] === $email) {
                            $error = 'Email đã được sử dụng bởi tài khoản khác.';
                        } elseif (!empty($phone) && $existingUser['phone'] === $phone) {
                            $error = 'Số điện thoại đã được sử dụng bởi tài khoản khác.';
                        }
                    } else {
                        // Cập nhật thông tin tài khoản
                        $this->db->update('users', [
                            'full_name' => $fullName,
                            'email' => $email,
                            'phone' => $phone,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', [
                            'id' => $user['id']
                        ]);
                        
                        // Cập nhật lại thông tin người dùng hiện tại
                        $user = $this->db->get("SELECT * FROM users WHERE id = :id", ['id' => $user['id']]);
                        $_SESSION['user'] = $user;
                        
                        $success = 'Cập nhật thông tin tài khoản thành công.';
                    }
                }
            }
        }
        
        // Hiển thị form cập nhật thông tin
        include_once PAGES_PATH . '/user/update-profile.php';
    }
    
    /**
     * Hiển thị và xử lý form đổi mật khẩu
     */
    public function changePassword() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        $error = '';
        $success = '';
        
        // Xử lý form đổi mật khẩu
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Kiểm tra form token
            if (!isset($_POST['form_token']) || !verify_form_token($_POST['form_token'])) {
                $error = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
            } else {
                // Lấy dữ liệu từ form
                $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
                $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
                $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
                
                // Kiểm tra mật khẩu hiện tại
                if (empty($currentPassword) || !check_password($currentPassword, $this->currentUser['password'])) {
                    $error = 'Mật khẩu hiện tại không chính xác.';
                }
                // Kiểm tra mật khẩu mới
                elseif (empty($newPassword) || strlen($newPassword) < 6) {
                    $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
                }
                // Kiểm tra xác nhận mật khẩu
                elseif ($newPassword !== $confirmPassword) {
                    $error = 'Xác nhận mật khẩu không khớp.';
                } else {
                    // Cập nhật mật khẩu
                    $this->db->update('users', [
                        'password' => hash_password($newPassword),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', [
                        'id' => $this->currentUser['id']
                    ]);
                    
                    $success = 'Đổi mật khẩu thành công.';
                }
            }
        }
        
        // Hiển thị form đổi mật khẩu
        include_once PAGES_PATH . '/user/change-password.php';
    }
    
    /**
     * Hiển thị thông tin VIP
     */
    public function vipInfo() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Kiểm tra người dùng có VIP không
        if (!$this->currentUser['is_vip']) {
            redirect('/vip');
            return;
        }
        
        // Lấy thông tin gói VIP hiện tại
        $currentPackage = null;
        
        // Lấy lịch sử VIP
        $vipHistory = $this->db->getAll(
            "SELECT vh.*, vp.name as package_name, vp.duration, po.payment_method
            FROM vip_history vh
            JOIN vip_packages vp ON vh.package_id = vp.id
            JOIN payment_orders po ON vh.order_id = po.id
            WHERE vh.user_id = :user_id
            ORDER BY vh.created_at DESC",
            ['user_id' => $this->currentUser['id']]
        );
        
        if (!empty($vipHistory)) {
            $currentPackage = $vipHistory[0];
        }
        
        // Tính thời gian còn lại
        $remainingTime = 0;
        if ($this->currentUser['vip_expired_at']) {
            $now = new DateTime();
            $expiredAt = new DateTime($this->currentUser['vip_expired_at']);
            $diff = $now->diff($expiredAt);
            $remainingTime = $diff->days;
        }
        
        // Hiển thị trang thông tin VIP
        include_once PAGES_PATH . '/user/vip-info.php';
    }
    
    /**
     * Xử lý xóa lịch sử xem phim
     * 
     * @param int $historyId ID lịch sử xem (nếu xóa một mục cụ thể)
     */
    public function deleteHistory($historyId = null) {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Xóa toàn bộ lịch sử
        if ($historyId === null) {
            $this->db->query(
                "DELETE FROM watch_history WHERE user_id = :user_id",
                ['user_id' => $this->currentUser['id']]
            );
            
            set_flash_message('success', 'Đã xóa toàn bộ lịch sử xem phim.');
        }
        // Xóa một mục lịch sử cụ thể
        else {
            $this->db->query(
                "DELETE FROM watch_history 
                WHERE id = :id AND user_id = :user_id",
                [
                    'id' => $historyId,
                    'user_id' => $this->currentUser['id']
                ]
            );
            
            set_flash_message('success', 'Đã xóa mục lịch sử xem phim.');
        }
        
        // Chuyển hướng về trang lịch sử xem
        redirect('/tai-khoan/lich-su');
    }
    
    /**
     * Xử lý tải lên ảnh đại diện
     */
    public function uploadAvatar() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        $error = '';
        $success = '';
        
        // Xử lý form tải lên ảnh đại diện
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Kiểm tra form token
            if (!isset($_POST['form_token']) || !verify_form_token($_POST['form_token'])) {
                $error = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
            }
            // Kiểm tra file tải lên
            elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Vui lòng chọn ảnh đại diện.';
            } else {
                $file = $_FILES['avatar'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                
                // Kiểm tra loại file
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($fileExt, $allowedExtensions)) {
                    $error = 'Chỉ chấp nhận các file hình ảnh (jpg, jpeg, png, gif).';
                }
                // Kiểm tra kích thước file (tối đa 2MB)
                elseif ($fileSize > 2 * 1024 * 1024) {
                    $error = 'Kích thước file không được vượt quá 2MB.';
                } else {
                    // Tạo tên file mới để tránh trùng lặp
                    $newFileName = 'avatar_' . $this->currentUser['id'] . '_' . uniqid() . '.' . $fileExt;
                    $uploadPath = UPLOADS_PATH . '/avatars/' . $newFileName;
                    
                    // Tạo thư mục uploads/avatars nếu chưa có
                    if (!file_exists(UPLOADS_PATH . '/avatars')) {
                        mkdir(UPLOADS_PATH . '/avatars', 0755, true);
                    }
                    
                    // Di chuyển file tải lên vào thư mục đích
                    if (move_uploaded_file($fileTmpName, $uploadPath)) {
                        // Cập nhật đường dẫn ảnh đại diện trong database
                        $avatarUrl = '/uploads/avatars/' . $newFileName;
                        
                        $this->db->update('users', [
                            'avatar' => $avatarUrl,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', [
                            'id' => $this->currentUser['id']
                        ]);
                        
                        // Cập nhật lại thông tin người dùng hiện tại
                        $this->currentUser = $this->db->get("SELECT * FROM users WHERE id = :id", ['id' => $this->currentUser['id']]);
                        $_SESSION['user'] = $this->currentUser;
                        
                        $success = 'Cập nhật ảnh đại diện thành công.';
                    } else {
                        $error = 'Có lỗi xảy ra khi tải lên ảnh đại diện.';
                    }
                }
            }
        }
        
        // Hiển thị form tải lên ảnh đại diện
        include_once PAGES_PATH . '/user/upload-avatar.php';
    }
}