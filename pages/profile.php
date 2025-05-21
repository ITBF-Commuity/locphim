<?php
/**
 * Lọc Phim - Trang hồ sơ người dùng
 */

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = url('tai-khoan');
    header('Location: ' . url('dang-nhap'));
    exit;
}

// Lấy thông tin người dùng
$user = $db->get("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$user) {
    $_SESSION['error'] = 'Không tìm thấy thông tin tài khoản, vui lòng đăng nhập lại.';
    header('Location: ' . url('dang-nhap'));
    exit;
}

// Xác định tab đang hoạt động
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
$allowedTabs = ['info', 'favorites', 'history', 'settings', 'vip'];

if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'info';
}

// Lấy lịch sử xem phim
$history = [];
if ($activeTab === 'history' || $activeTab === 'info') {
    try {
        // Kiểm tra cấu trúc của bảng watch_history
        $dbType = $db->getDbType();
        
        // Xác định tên các cột phù hợp với loại database
        if ($dbType === 'pgsql') {
            // PostgreSQL columns
            $timeColumn = 'h.created_at';
            $completedColumn = 'h.is_completed';
            
            $query = "SELECT h.id, h.user_id, h.episode_id, 0 as watched_seconds, {$completedColumn} as completed, 
                    {$timeColumn} as created_at, 
                    e.movie_id as movie_id, m.title, m.slug, m.poster, m.type 
                    FROM watch_history h 
                    JOIN episodes e ON h.episode_id = e.id
                    JOIN movies m ON e.movie_id = m.id 
                    WHERE h.user_id = ? 
                    ORDER BY {$timeColumn} DESC LIMIT 10";
        } else {
            // MySQL/SQLite columns
            $timeColumn = 'h.created_at';
            $completedColumn = 'h.completed';
            
            $query = "SELECT h.id, h.user_id, h.episode_id, h.watched_seconds, {$completedColumn} as completed, 
                    {$timeColumn} as created_at, 
                    m.id as movie_id, m.title, m.slug, m.poster, m.type 
                    FROM watch_history h 
                    JOIN episodes e ON h.episode_id = e.id
                    JOIN movies m ON e.movie_id = m.id 
                    WHERE h.user_id = ? 
                    ORDER BY {$timeColumn} DESC LIMIT 10";
        }
        
        $history = $db->getAll($query, [$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Lỗi lấy lịch sử xem: " . $e->getMessage());
    }
}

// Lấy phim yêu thích
$favorites = [];
if ($activeTab === 'favorites' || $activeTab === 'info') {
    $query = "SELECT f.*, m.title, m.original_title, m.slug, m.poster, m.release_year, m.type 
              FROM favorites f 
              JOIN movies m ON f.movie_id = m.id 
              WHERE f.user_id = ? 
              ORDER BY f.created_at DESC LIMIT 10";
    $favorites = $db->getAll($query, [$_SESSION['user_id']]);
}

// Lấy thông tin VIP
$vipInfo = null;
if ($activeTab === 'vip' || $activeTab === 'info') {
    if ($user['is_vip']) {
        $vipInfo = $db->get("SELECT * FROM vip_subscriptions WHERE user_id = ? ORDER BY expires_at DESC LIMIT 1", [$_SESSION['user_id']]);
        if ($vipInfo) {
            // Tính số ngày còn lại
            $now = new DateTime();
            $expiresAt = new DateTime($vipInfo['expires_at']);
            $diff = $now->diff($expiresAt);
            $vipInfo['days_left'] = $diff->days;

            // Lấy thông tin giao dịch
            $transaction = $db->get("SELECT * FROM vip_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$_SESSION['user_id']]);
            if ($transaction) {
                $vipInfo['transaction_date'] = $transaction['created_at'];
                $vipInfo['amount'] = $transaction['amount'];
                $vipInfo['payment_method'] = $transaction['payment_method'];
                $vipInfo['plan_id'] = $transaction['plan_id'];
            }
        }
    }
}

// Xử lý form cập nhật thông tin
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($activeTab) {
        case 'info':
            // Cập nhật thông tin cơ bản
            $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
            
            // Cập nhật avatar nếu có
            $avatar = '';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = upload_avatar($_FILES['avatar'], $user['id']);
                if (!$avatar) {
                    $error = 'Có lỗi xảy ra khi tải lên ảnh đại diện.';
                }
            }
            
            // Cập nhật thông tin vào database
            $data = [
                'fullname' => $fullname,
                'phone' => $phone,
                'bio' => $bio,
            ];
            
            if ($avatar) {
                $data['avatar'] = $avatar;
            }
            
            $updated = $db->update('users', $data, 'id = ?', [$user['id']]);
            
            if ($updated) {
                $success = 'Thông tin cá nhân đã được cập nhật thành công!';
                
                // Refresh user data
                $user = $db->get("SELECT * FROM users WHERE id = ?", [$user['id']]);
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật thông tin cá nhân.';
            }
            break;
            
        case 'settings':
            // Cập nhật mật khẩu
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Kiểm tra mật khẩu hiện tại
            if (empty($current_password)) {
                $error = 'Vui lòng nhập mật khẩu hiện tại.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Mật khẩu hiện tại không chính xác.';
            } elseif (empty($new_password)) {
                $error = 'Vui lòng nhập mật khẩu mới.';
            } elseif (strlen($new_password) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Xác nhận mật khẩu mới không khớp.';
            } else {
                // Cập nhật mật khẩu mới
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updated = $db->update('users', ['password' => $hashedPassword], 'id = ?', [$user['id']]);
                
                if ($updated) {
                    $success = 'Mật khẩu đã được cập nhật thành công!';
                } else {
                    $error = 'Có lỗi xảy ra khi cập nhật mật khẩu.';
                }
            }
            break;
    }
}

// Hàm xử lý upload avatar
function upload_avatar($file, $userId) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Kiểm tra loại file
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Kiểm tra kích thước
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Tạo tên file mới
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
    
    // Đường dẫn upload
    $uploadDir = UPLOADS_PATH . '/avatars';
    $uploadPath = $uploadDir . '/' . $newFileName;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return 'uploads/avatars/' . $newFileName;
    }
    
    return false;
}

// Lấy danh sách phim yêu thích
$favorites = [];
if ($activeTab === 'favorites') {
    $favorites = $db->getAll("SELECT m.*, f.created_at as favorited_at 
                              FROM favorites f 
                              JOIN movies m ON f.movie_id = m.id 
                              WHERE f.user_id = ? 
                              ORDER BY f.created_at DESC", [$user['id']]);
}

// Lấy lịch sử xem
$history = [];
if ($activeTab === 'history') {
    try {
        // Kiểm tra cấu trúc của bảng watch_history
        $dbType = $db->getDbType();
        
        // Xác định tên các cột phù hợp với loại database
        if ($dbType === 'pgsql') {
            // PostgreSQL columns
            $timeColumn = 'w.created_at';
            $completedColumn = 'w.is_completed';
            
            $history = $db->getAll("SELECT w.id, w.user_id, w.episode_id, {$completedColumn} as completed, 
                                {$timeColumn} as created_at,
                                e.id as episode_id, e.movie_id as movie_id, m.title, m.slug, m.poster, m.type
                                FROM watch_history w 
                                JOIN episodes e ON w.episode_id = e.id
                                JOIN movies m ON e.movie_id = m.id 
                                WHERE w.user_id = ? 
                                ORDER BY {$timeColumn} DESC", [$user['id']]);
        } else {
            // MySQL/SQLite columns
            $timeColumn = 'w.created_at';
            $completedColumn = 'w.completed';
            
            $history = $db->getAll("SELECT w.id, w.user_id, w.episode_id, {$completedColumn} as completed, 
                                {$timeColumn} as created_at,
                                e.id as episode_id, m.id as movie_id, m.title, m.slug, m.poster, m.type
                                FROM watch_history w 
                                JOIN episodes e ON w.episode_id = e.id
                                JOIN movies m ON e.movie_id = m.id 
                                WHERE w.user_id = ? 
                                ORDER BY {$timeColumn} DESC", [$user['id']]);
        }
    } catch (Exception $e) {
        error_log("Lỗi lấy lịch sử xem: " . $e->getMessage());
    }
}

// Lấy thông tin gói VIP
$vipInfo = null;
if ($user['is_vip'] && !empty($user['vip_expires_at'])) {
    $vipInfo = [
        'expires_at' => $user['vip_expires_at'],
        'days_left' => ceil((strtotime($user['vip_expires_at']) - time()) / (60 * 60 * 24)),
    ];
    
    // Lấy thông tin giao dịch gần nhất
    $latestTransaction = $db->get("SELECT * FROM transactions 
                                  WHERE user_id = ? AND status = 'completed' 
                                  ORDER BY created_at DESC LIMIT 1", [$user['id']]);
    
    if ($latestTransaction) {
        $vipInfo['plan_id'] = $latestTransaction['plan_id'];
        $vipInfo['amount'] = $latestTransaction['amount'];
        $vipInfo['payment_method'] = $latestTransaction['payment_method'];
        $vipInfo['transaction_date'] = $latestTransaction['created_at'];
    }
}

// Set title và description cho trang
$pageTitle = 'Tài Khoản - ' . SITE_NAME;
$pageDescription = 'Quản lý tài khoản cá nhân tại ' . SITE_NAME;

// Bắt đầu output buffering
ob_start();
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-cover">
            <div class="profile-avatar">
                <img src="<?php echo !empty($user['avatar']) ? url($user['avatar']) : url('assets/images/avatars/default.png'); ?>" alt="Avatar">
                <?php if ($user['is_vip']): ?>
                    <div class="vip-indicator"><i class="fas fa-crown"></i></div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if (!empty($user['fullname'])): ?>
                    <p class="fullname"><?php echo htmlspecialchars($user['fullname']); ?></p>
                <?php endif; ?>
                <div class="profile-stats">
                    <div class="stat">
                        <div class="value"><?php echo !empty($favorites) ? count($favorites) : 0; ?></div>
                        <div class="label">Yêu thích</div>
                    </div>
                    <div class="stat">
                        <div class="value"><?php echo !empty($history) ? count($history) : 0; ?></div>
                        <div class="label">Đã xem</div>
                    </div>
                    <?php if ($user['is_vip'] && isset($vipInfo) && !empty($vipInfo)): ?>
                    <div class="stat vip-stat">
                        <div class="value"><?php echo isset($vipInfo['days_left']) ? $vipInfo['days_left'] : 0; ?></div>
                        <div class="label">Ngày VIP còn lại</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="profile-nav">
            <a href="<?php echo url('tai-khoan/info'); ?>" class="nav-item <?php echo $activeTab === 'info' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Thông tin cá nhân
            </a>
            <a href="<?php echo url('tai-khoan/favorites'); ?>" class="nav-item <?php echo $activeTab === 'favorites' ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i> Phim yêu thích
            </a>
            <a href="<?php echo url('tai-khoan/history'); ?>" class="nav-item <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Lịch sử xem
            </a>
            <a href="<?php echo url('tai-khoan/vip'); ?>" class="nav-item <?php echo $activeTab === 'vip' ? 'active' : ''; ?>">
                <i class="fas fa-crown"></i> Thông tin VIP
            </a>
            <a href="<?php echo url('tai-khoan/settings'); ?>" class="nav-item <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Cài đặt
            </a>
            <a href="<?php echo url('dang-xuat'); ?>" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>
    
    <div class="profile-content">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php switch ($activeTab): 
            case 'info': ?>
                <h1>Thông tin cá nhân</h1>
                <p>Cập nhật thông tin cá nhân của bạn</p>
                
                <form class="profile-form" method="POST" action="<?php echo url('tai-khoan/info'); ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="avatar" class="form-label">Ảnh đại diện</label>
                        <div class="avatar-upload">
                            <img src="<?php echo !empty($user['avatar']) ? url($user['avatar']) : url('assets/images/avatars/default.png'); ?>" alt="Avatar" class="avatar-preview">
                            <input type="file" id="avatar" name="avatar" accept="image/*">
                            <button type="button" class="btn btn-outline">Chọn ảnh</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <p class="form-text">Tên đăng nhập không thể thay đổi</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <p class="form-text">Email không thể thay đổi</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname" class="form-label">Họ tên</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Nhập họ tên của bạn" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="Nhập số điện thoại" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Giới thiệu</label>
                        <textarea id="bio" name="bio" rows="4" class="form-control" placeholder="Giới thiệu về bản thân"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </form>
                <?php break; ?>
                
            case 'favorites': ?>
                <h1>Phim yêu thích</h1>
                <p>Danh sách phim bạn đã thích</p>
                
                <?php if (empty($favorites)): ?>
                    <div class="empty-results">
                        <i class="fas fa-heart-broken"></i>
                        <h3>Chưa có phim yêu thích</h3>
                        <p>Bạn chưa thêm phim nào vào danh sách yêu thích.</p>
                        <a href="<?php echo url(''); ?>" class="btn btn-primary">Khám phá phim ngay</a>
                    </div>
                <?php else: ?>
                    <div class="favorites-list">
                        <?php foreach ($favorites as $movie): ?>
                            <div class="favorite-item">
                                <div class="favorite-thumbnail">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                    </a>
                                </div>
                                <div class="favorite-info">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="favorite-title">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                    
                                    <?php if (!empty($movie['original_title']) && $movie['original_title'] != $movie['title']): ?>
                                        <div class="favorite-original-title">
                                            <?php echo htmlspecialchars($movie['original_title']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="favorite-meta">
                                        <?php if (!empty($movie['release_year'])): ?>
                                            <span class="year"><?php echo $movie['release_year']; ?></span>
                                        <?php endif; ?>
                                        
                                        <span class="type">
                                            <?php 
                                            switch ($movie['type']) {
                                                case 'movie':
                                                    echo 'Phim Lẻ';
                                                    break;
                                                case 'series':
                                                    echo 'Phim Bộ';
                                                    break;
                                                case 'anime':
                                                    echo 'Anime';
                                                    break;
                                                default:
                                                    echo ucfirst($movie['type']);
                                            }
                                            ?>
                                        </span>
                                        
                                        <span class="favorited-at"><i class="fas fa-clock"></i> <?php echo date('d/m/Y', strtotime($movie['favorited_at'])); ?></span>
                                    </div>
                                    
                                    <button class="favorite-remove btn-remove" data-movie-id="<?php echo $movie['id']; ?>">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php break; ?>
                
            case 'history': ?>
                <h1>Lịch sử xem</h1>
                <p>Danh sách phim bạn đã xem</p>
                
                <?php if (empty($history)): ?>
                    <div class="empty-results">
                        <i class="fas fa-film"></i>
                        <h3>Chưa có lịch sử xem</h3>
                        <p>Bạn chưa xem phim nào.</p>
                        <a href="<?php echo url(''); ?>" class="btn btn-primary">Khám phá phim ngay</a>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($history as $item): ?>
                            <div class="history-item">
                                <div class="history-thumbnail">
                                    <a href="<?php echo url('phim/' . $item['slug'] . '/' . $item['id'] . '/tap-' . $item['episode_number']); ?>">
                                        <img src="<?php echo image_url($item['poster']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </a>
                                </div>
                                <div class="history-info">
                                    <a href="<?php echo url('phim/' . $item['slug'] . '/' . $item['id']); ?>" class="history-title">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    
                                    <div class="history-meta">
                                        <span class="episode">
                                            <?php if ($item['type'] !== 'movie'): ?>
                                                <?php if (isset($item['episode_id']) && !empty($item['episode_id'])): ?>
                                                    Tập <?php echo isset($item['episode_number']) ? $item['episode_number'] : '1'; ?>
                                                <?php else: ?>
                                                    Tập 1
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Phim lẻ
                                            <?php endif; ?>
                                        </span>
                                        
                                        <span class="watched-at"><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="history-progress">
                                        <div class="history-progress-bar" style="width: 50%"></div>
                                    </div>
                                    <div class="history-progress-text">
                                        Đã xem một phần
                                    </div>
                                    
                                    <div class="history-actions">
                                        <a href="<?php echo url('phim/' . $item['slug'] . '/' . $item['id'] . (isset($item['episode_id']) ? '/tap-' . (isset($item['episode_number']) ? $item['episode_number'] : '1') : '')); ?>" class="btn btn-sm btn-primary">
                                            <?php echo isset($item['completed']) && $item['completed'] ? 'Xem lại' : 'Tiếp tục xem'; ?>
                                        </a>
                                        <button class="history-remove btn-remove" data-history-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php break; ?>
                
            case 'vip': ?>
                <h1>Thông tin VIP</h1>
                <p>Chi tiết về gói VIP của bạn</p>
                
                <?php if ($user['is_vip'] && isset($vipInfo) && !empty($vipInfo)): ?>
                    <div class="vip-info">
                        <div class="vip-status">
                            <div class="vip-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="vip-details">
                                <h3>Tài khoản VIP</h3>
                                <p>Hết hạn: <?php echo !empty($vipInfo['expires_at']) ? date('d/m/Y H:i', strtotime($vipInfo['expires_at'])) : 'Không xác định'; ?> 
                                (còn <?php echo isset($vipInfo['days_left']) ? $vipInfo['days_left'] : 0; ?> ngày)</p>
                            </div>
                        </div>
                        
                        <?php if (isset($vipInfo['transaction_date'])): ?>
                            <div class="vip-transaction">
                                <h3>Giao dịch gần nhất</h3>
                                <table class="profile-table">
                                    <tr>
                                        <td class="label">Gói VIP:</td>
                                        <td>
                                            <?php
                                            switch ($vipInfo['plan_id']) {
                                                case 1:
                                                    echo 'VIP 1 Tháng';
                                                    break;
                                                case 2:
                                                    echo 'VIP 3 Tháng';
                                                    break;
                                                case 3:
                                                    echo 'VIP 6 Tháng';
                                                    break;
                                                case 4:
                                                    echo 'VIP 1 Năm';
                                                    break;
                                                default:
                                                    echo 'Gói VIP #' . $vipInfo['plan_id'];
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Số tiền:</td>
                                        <td><?php echo number_format($vipInfo['amount'], 0, ',', '.'); ?> ₫</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Phương thức:</td>
                                        <td>
                                            <?php
                                            switch ($vipInfo['payment_method']) {
                                                case 'vnpay':
                                                    echo 'VNPay';
                                                    break;
                                                case 'momo':
                                                    echo 'MoMo';
                                                    break;
                                                case 'stripe':
                                                    echo 'Thẻ tín dụng/ghi nợ';
                                                    break;
                                                default:
                                                    echo ucfirst($vipInfo['payment_method']);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Ngày giao dịch:</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($vipInfo['transaction_date'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vip-benefits">
                            <h3>Đặc quyền VIP</h3>
                            <ul>
                                <li><i class="fas fa-check"></i> Xem phim chất lượng cao nhất (lên đến 4K)</li>
                                <li><i class="fas fa-check"></i> Không bị gián đoạn bởi quảng cáo</li>
                                <li><i class="fas fa-check"></i> Tải phim về máy để xem offline</li>
                                <li><i class="fas fa-check"></i> Được xem các tập phim mới sớm hơn</li>
                                <li><i class="fas fa-check"></i> Truy cập các máy chủ dự phòng với tốc độ cao</li>
                                <li><i class="fas fa-check"></i> Hỗ trợ kỹ thuật ưu tiên</li>
                            </ul>
                        </div>
                        
                        <div class="vip-actions">
                            <h3>Gia hạn gói VIP</h3>
                            <p>Để tiếp tục tận hưởng đặc quyền VIP, hãy gia hạn gói VIP trước khi hết hạn.</p>
                            <a href="<?php echo url('vip'); ?>" class="btn btn-primary">Gia hạn ngay</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="vip-upsell">
                        <div class="vip-upsell-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3>Bạn chưa là thành viên VIP</h3>
                        <p>Nâng cấp tài khoản của bạn lên VIP để tận hưởng những đặc quyền tuyệt vời!</p>
                        <div class="vip-benefits">
                            <ul>
                                <li><i class="fas fa-check"></i> Xem phim chất lượng cao nhất (lên đến 4K)</li>
                                <li><i class="fas fa-check"></i> Không bị gián đoạn bởi quảng cáo</li>
                                <li><i class="fas fa-check"></i> Tải phim về máy để xem offline</li>
                                <li><i class="fas fa-check"></i> Được xem các tập phim mới sớm hơn</li>
                                <li><i class="fas fa-check"></i> Truy cập các máy chủ dự phòng với tốc độ cao</li>
                                <li><i class="fas fa-check"></i> Hỗ trợ kỹ thuật ưu tiên</li>
                            </ul>
                        </div>
                        <a href="<?php echo url('vip'); ?>" class="btn btn-primary">Nâng cấp VIP ngay</a>
                    </div>
                <?php endif; ?>
                <?php break; ?>
                
            case 'settings': ?>
                <h1>Cài đặt tài khoản</h1>
                <p>Thay đổi mật khẩu và cài đặt bảo mật</p>
                
                <div class="profile-tabs">
                    <div class="tab-buttons">
                        <button class="tab-button active" data-tab="change-password">Đổi mật khẩu</button>
                        <button class="tab-button" data-tab="notifications">Thông báo</button>
                        <button class="tab-button" data-tab="privacy">Quyền riêng tư</button>
                    </div>
                    
                    <div class="tab-content active" id="change-password">
                        <form class="profile-form" method="POST" action="<?php echo url('tai-khoan/settings'); ?>">
                            <div class="form-group">
                                <label for="current_password">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" required>
                                <p class="form-hint">Tối thiểu 6 ký tự</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                        </form>
                    </div>
                    
                    <div class="tab-content" id="notifications">
                        <form class="profile-form">
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="email_notifications" checked>
                                    <span class="checkmark"></span>
                                    Nhận thông báo qua email khi có tập phim mới
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="push_notifications" checked>
                                    <span class="checkmark"></span>
                                    Nhận thông báo đẩy trên trình duyệt
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="vip_notifications" checked>
                                    <span class="checkmark"></span>
                                    Nhận thông báo về khuyến mãi và ưu đãi VIP
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                        </form>
                    </div>
                    
                    <div class="tab-content" id="privacy">
                        <form class="profile-form">
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="public_profile" checked>
                                    <span class="checkmark"></span>
                                    Cho phép người khác xem hồ sơ của tôi
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="public_favorites">
                                    <span class="checkmark"></span>
                                    Cho phép người khác xem danh sách phim yêu thích của tôi
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="public_history">
                                    <span class="checkmark"></span>
                                    Cho phép người khác xem lịch sử xem phim của tôi
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                        </form>
                    </div>
                </div>
                <?php break; ?>
        <?php endswitch; ?>
    </div>
</div>

<script>
// Xử lý xóa phim yêu thích
document.querySelectorAll('.favorite-remove').forEach(button => {
    button.addEventListener('click', function() {
        const movieId = this.dataset.movieId;
        if (confirm('Bạn có chắc chắn muốn xóa phim này khỏi danh sách yêu thích?')) {
            fetch('<?php echo url('api/toggle-favorite'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'movie_id=' + movieId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && !data.is_favorite) {
                    // Xóa phần tử khỏi DOM
                    this.closest('.favorite-item').remove();
                    
                    // Nếu không còn phim nào, hiển thị thông báo trống
                    if (document.querySelectorAll('.favorite-item').length === 0) {
                        const emptyElement = document.createElement('div');
                        emptyElement.className = 'empty-results';
                        emptyElement.innerHTML = `
                            <i class="fas fa-heart-broken"></i>
                            <h3>Chưa có phim yêu thích</h3>
                            <p>Bạn chưa thêm phim nào vào danh sách yêu thích.</p>
                            <a href="<?php echo url(''); ?>" class="btn btn-primary">Khám phá phim ngay</a>
                        `;
                        document.querySelector('.favorites-list').replaceWith(emptyElement);
                    }
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xử lý yêu cầu của bạn.');
            });
        }
    });
});

// Xử lý xóa lịch sử xem
document.querySelectorAll('.history-remove').forEach(button => {
    button.addEventListener('click', function() {
        const historyId = this.dataset.historyId;
        if (confirm('Bạn có chắc chắn muốn xóa mục này khỏi lịch sử xem?')) {
            fetch('<?php echo url('api/delete-history'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'history_id=' + historyId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Xóa phần tử khỏi DOM
                    this.closest('.history-item').remove();
                    
                    // Nếu không còn lịch sử, hiển thị thông báo trống
                    if (document.querySelectorAll('.history-item').length === 0) {
                        const emptyElement = document.createElement('div');
                        emptyElement.className = 'empty-results';
                        emptyElement.innerHTML = `
                            <i class="fas fa-film"></i>
                            <h3>Chưa có lịch sử xem</h3>
                            <p>Bạn chưa xem phim nào.</p>
                            <a href="<?php echo url(''); ?>" class="btn btn-primary">Khám phá phim ngay</a>
                        `;
                        document.querySelector('.history-list').replaceWith(emptyElement);
                    }
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xử lý yêu cầu của bạn.');
            });
        }
    });
});
</script>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>