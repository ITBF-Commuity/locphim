<?php
// Trang profile của người dùng
// Khởi tạo hệ thống
require_once 'init.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$user = null;

try {
    // Kết nối database
    $db = new PDO('sqlite:' . SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Nếu không tìm thấy người dùng, đăng xuất
        header('Location: logout.php');
        exit;
    }
    
    // Xử lý cập nhật thông tin cá nhân
    if (isset($_POST['update_info'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        
        // Kiểm tra email đã tồn tại chưa (nếu thay đổi)
        if ($email !== $user['email']) {
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $user_id]);
            if ($check_stmt->fetchColumn() > 0) {
                $error = "Email đã được sử dụng bởi tài khoản khác.";
            }
        }
        
        if (!isset($error)) {
            $update_stmt = $db->prepare("UPDATE users SET 
                full_name = ?, 
                email = ?, 
                phone = ?, 
                bio = ?,
                updated_at = datetime('now') 
                WHERE id = ?");
            
            $result = $update_stmt->execute([$full_name, $email, $phone, $bio, $user_id]);
            
            if ($result) {
                // Cập nhật thông tin thành công
                $success_message = "Thông tin cá nhân đã được cập nhật thành công.";
                
                // Cập nhật lại thông tin người dùng
                $stmt = $db->prepare("SELECT u.*, r.name as role_name 
                                     FROM users u 
                                     JOIN roles r ON u.role_id = r.id 
                                     WHERE u.id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Có lỗi xảy ra khi cập nhật thông tin.";
            }
        }
    }
    
    // Xử lý đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Kiểm tra mật khẩu hiện tại
        if (!password_verify($current_password, $user['password'])) {
            $password_error = "Mật khẩu hiện tại không chính xác.";
        } else if (strlen($new_password) < 6) {
            $password_error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        } else if ($new_password !== $confirm_password) {
            $password_error = "Mật khẩu xác nhận không khớp.";
        } else {
            // Cập nhật mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $db->prepare("UPDATE users SET 
                password = ?, 
                updated_at = datetime('now') 
                WHERE id = ?");
            
            $result = $update_stmt->execute([$hashed_password, $user_id]);
            
            if ($result) {
                $password_success = "Mật khẩu đã được cập nhật thành công.";
            } else {
                $password_error = "Có lỗi xảy ra khi cập nhật mật khẩu.";
            }
        }
    }
    
    // Xử lý cập nhật avatar
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = 'uploads/avatars/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                // Cập nhật avatar trong database
                $avatar_path = '/uploads/avatars/' . $new_filename;
                
                $update_stmt = $db->prepare("UPDATE users SET 
                    avatar = ?, 
                    updated_at = datetime('now') 
                    WHERE id = ?");
                
                $result = $update_stmt->execute([$avatar_path, $user_id]);
                
                if ($result) {
                    $success_message = "Avatar đã được cập nhật thành công.";
                    
                    // Cập nhật lại thông tin người dùng
                    $stmt = $db->prepare("SELECT u.*, r.name as role_name 
                                     FROM users u 
                                     JOIN roles r ON u.role_id = r.id 
                                     WHERE u.id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Có lỗi xảy ra khi cập nhật avatar.";
                }
            } else {
                $error = "Có lỗi xảy ra khi tải lên avatar.";
            }
        } else {
            $error = "Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG hoặc GIF.";
        }
    }
    
    // Lấy danh sách phim yêu thích
    $favorites_stmt = $db->prepare("SELECT m.*, 
                                   (SELECT COUNT(*) FROM favorites WHERE movie_id = m.id) as favorite_count,
                                   f.created_at as favorited_at 
                                   FROM favorites f 
                                   JOIN movies m ON f.movie_id = m.id 
                                   WHERE f.user_id = ? 
                                   ORDER BY f.created_at DESC 
                                   LIMIT 6");
    $favorites_stmt->execute([$user_id]);
    $favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy lịch sử xem gần đây
    $history_stmt = $db->prepare("SELECT m.*, e.episode_number, e.title as episode_title, 
                                 w.progress, w.watched_at 
                                 FROM watch_history w 
                                 JOIN movies m ON w.movie_id = m.id 
                                 LEFT JOIN episodes e ON w.episode_id = e.id 
                                 WHERE w.user_id = ? 
                                 ORDER BY w.watched_at DESC 
                                 LIMIT 6");
    $history_stmt->execute([$user_id]);
    $watch_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy đánh giá của người dùng
    $ratings_stmt = $db->prepare("SELECT r.*, m.title, m.slug, m.thumbnail 
                                 FROM ratings r 
                                 JOIN movies m ON r.movie_id = m.id 
                                 WHERE r.user_id = ? 
                                 ORDER BY r.created_at DESC 
                                 LIMIT 6");
    $ratings_stmt->execute([$user_id]);
    $ratings = $ratings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy thông báo của người dùng
    $notifications_stmt = $db->prepare("SELECT * FROM notifications 
                                      WHERE user_id = ? 
                                      ORDER BY created_at DESC 
                                      LIMIT 10");
    $notifications_stmt->execute([$user_id]);
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Thông tin cá nhân - Lọc Phim';

// Bao gồm header
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar menu -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="avatar-wrapper mb-3">
                        <img src="<?php echo !empty($user['avatar']) ? $user['avatar'] : 'assets/img/default-avatar.svg'; ?>" 
                             alt="Avatar" 
                             class="rounded-circle img-thumbnail" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h5>
                    <p class="card-text text-muted small"><?php echo htmlspecialchars($user['role_name']); ?></p>
                    <p class="card-text text-muted small">Thành viên từ: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    
                    <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#updateAvatarModal">
                        <i class="fas fa-camera"></i> Đổi ảnh đại diện
                    </button>
                </div>
                
                <div class="list-group list-group-flush">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i> Thông tin cá nhân
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-key me-2"></i> Đổi mật khẩu
                    </a>
                    <a href="#favorites" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-heart me-2"></i> Phim yêu thích
                    </a>
                    <a href="#history" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-history me-2"></i> Lịch sử xem
                    </a>
                    <a href="#ratings" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-star me-2"></i> Đánh giá của tôi
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i> Thông báo
                    </a>
                    <?php if ($user['role_id'] == 1): ?>
                    <a href="admin/index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Trang quản trị
                    </a>
                    <?php endif; ?>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
            
            <?php if (!empty($user['vip_expires_at']) && strtotime($user['vip_expires_at']) > time()): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-crown text-warning"></i> Thành viên VIP
                    </h5>
                    <p class="card-text">Hết hạn: <?php echo date('d/m/Y', strtotime($user['vip_expires_at'])); ?></p>
                    <a href="vip_upgrade.php" class="btn btn-warning btn-sm w-100">Gia hạn VIP</a>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title">Nâng cấp tài khoản</h5>
                    <p class="card-text">Trở thành thành viên VIP để xem phim chất lượng cao và không bị giới hạn.</p>
                    <a href="vip_upgrade.php" class="btn btn-warning btn-sm w-100">Nâng cấp VIP</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="tab-content">
                        <!-- Thông tin cá nhân -->
                        <div class="tab-pane fade show active" id="profile">
                            <h4 class="mb-4">Thông tin cá nhân</h4>
                            
                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Giới thiệu bản thân</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_info" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu thay đổi
                                </button>
                            </form>
                        </div>
                        
                        <!-- Đổi mật khẩu -->
                        <div class="tab-pane fade" id="password">
                            <h4 class="mb-4">Đổi mật khẩu</h4>
                            
                            <?php if (isset($password_success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $password_success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $password_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                        
                        <!-- Phim yêu thích -->
                        <div class="tab-pane fade" id="favorites">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4>Phim yêu thích</h4>
                                <a href="favorites.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                            </div>
                            
                            <?php if (!empty($favorites)): ?>
                            <div class="row row-cols-2 row-cols-md-3 g-3">
                                <?php foreach ($favorites as $movie): ?>
                                <div class="col">
                                    <div class="card h-100 border-0 shadow-sm movie-card">
                                        <a href="detail.php?slug=<?php echo $movie['slug']; ?>">
                                            <img src="<?php echo !empty($movie['thumbnail']) ? $movie['thumbnail'] : 'assets/img/default-thumbnail.svg'; ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        </a>
                                        <div class="card-body p-2">
                                            <h6 class="card-title mb-0">
                                                <a href="detail.php?slug=<?php echo $movie['slug']; ?>" class="text-decoration-none text-reset">
                                                    <?php echo htmlspecialchars($movie['title']); ?>
                                                </a>
                                            </h6>
                                            <div class="small text-muted d-flex justify-content-between align-items-center mt-2">
                                                <span><?php echo $movie['type'] == 'movie' ? 'Phim' : 'Anime'; ?></span>
                                                <span><?php echo $movie['release_year']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa thêm phim nào vào danh sách yêu thích.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Lịch sử xem -->
                        <div class="tab-pane fade" id="history">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4>Lịch sử xem</h4>
                                <a href="history.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                            </div>
                            
                            <?php if (!empty($watch_history)): ?>
                            <div class="list-group">
                                <?php foreach ($watch_history as $item): ?>
                                <a href="<?php echo ($item['type'] == 'movie') ? 'player.php?slug=' . $item['slug'] : 'player.php?slug=' . $item['slug'] . '&episode=' . $item['episode_number']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($item['thumbnail']) ? $item['thumbnail'] : 'assets/img/default-thumbnail.svg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                             class="me-3" 
                                             style="width: 60px; height: 36px; object-fit: cover;">
                                             
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php if ($item['type'] !== 'movie' && !empty($item['episode_number'])): ?>
                                                        Tập <?php echo $item['episode_number']; ?>
                                                        <?php if (!empty($item['episode_title'])): ?>
                                                            - <?php echo htmlspecialchars($item['episode_title']); ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </small>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['watched_at'])); ?></small>
                                            </div>
                                            
                                            <?php if (!empty($item['progress']) && $item['progress'] > 0): ?>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $item['progress']; ?>%"></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa xem phim nào gần đây.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Đánh giá của tôi -->
                        <div class="tab-pane fade" id="ratings">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4>Đánh giá của tôi</h4>
                                <a href="my_ratings.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                            </div>
                            
                            <?php if (!empty($ratings)): ?>
                            <div class="list-group">
                                <?php foreach ($ratings as $rating): ?>
                                <a href="detail.php?slug=<?php echo $rating['slug']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($rating['thumbnail']) ? $rating['thumbnail'] : 'assets/img/default-thumbnail.svg'; ?>" 
                                             alt="<?php echo htmlspecialchars($rating['title']); ?>" 
                                             class="me-3" 
                                             style="width: 60px; height: 36px; object-fit: cover;">
                                             
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($rating['title']); ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= round($rating['rating'] / 2)): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-warning"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="ms-1"><?php echo number_format($rating['rating'], 1); ?>/10</span>
                                                </div>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($rating['created_at'])); ?></small>
                                            </div>
                                            
                                            <?php if (!empty($rating['comment'])): ?>
                                            <p class="small text-muted mt-1 mb-0"><?php echo htmlspecialchars(substr($rating['comment'], 0, 100)); ?><?php echo (strlen($rating['comment']) > 100) ? '...' : ''; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa đánh giá phim nào.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Thông báo -->
                        <div class="tab-pane fade" id="notifications">
                            <h4 class="mb-4">Thông báo</h4>
                            
                            <?php if (!empty($notifications)): ?>
                            <div class="list-group">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action <?php echo ($notification['read'] == 0) ? 'bg-light' : ''; ?>">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <?php if (!empty($notification['link'])): ?>
                                    <a href="<?php echo $notification['link']; ?>" class="btn btn-sm btn-link p-0">Xem chi tiết</a>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                Không có thông báo nào.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal cập nhật avatar -->
<div class="modal fade" id="updateAvatarModal" tabindex="-1" aria-labelledby="updateAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateAvatarModalLabel">Cập nhật ảnh đại diện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="avatar" class="form-label">Chọn ảnh đại diện mới</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*" required>
                        <div class="form-text">Chấp nhận file JPG, JPEG, PNG hoặc GIF</div>
                    </div>
                    
                    <div class="mt-3" id="imagePreviewContainer" style="display: none;">
                        <label class="form-label">Xem trước:</label>
                        <div class="text-center">
                            <img id="imagePreview" src="#" alt="Xem trước ảnh đại diện" 
                                 class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_avatar" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xem trước ảnh đại diện khi chọn file
    const avatarInput = document.getElementById('avatar');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    
    avatarInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block';
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Xử lý hiện tab dựa vào hash URL
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`.list-group-item[href="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>