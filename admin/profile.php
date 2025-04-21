<?php
// Trang profile dành cho admin
session_start();

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Kết nối database
$db_file = '../loc_phim.db';
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Nếu không tìm thấy người dùng, đăng xuất
        header('Location: ../logout.php');
        exit;
    }
    
    // Xử lý cập nhật thông tin cá nhân
    if (isset($_POST['update_info'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Kiểm tra email đã tồn tại chưa (nếu thay đổi)
        if ($email !== $user['email']) {
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $user['id']]);
            if ($check_stmt->fetchColumn() > 0) {
                $error = "Email đã được sử dụng bởi tài khoản khác.";
            }
        }
        
        if (!isset($error)) {
            $update_stmt = $db->prepare("UPDATE users SET 
                full_name = ?, 
                email = ?, 
                phone = ?, 
                updated_at = datetime('now') 
                WHERE id = ?");
            
            $result = $update_stmt->execute([$full_name, $email, $phone, $user['id']]);
            
            if ($result) {
                // Cập nhật thông tin thành công
                $success_message = "Thông tin cá nhân đã được cập nhật thành công.";
                
                // Cập nhật lại thông tin người dùng
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
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
            
            $result = $update_stmt->execute([$hashed_password, $user['id']]);
            
            if ($result) {
                $password_success = "Mật khẩu đã được cập nhật thành công.";
            } else {
                $password_error = "Có lỗi xảy ra khi cập nhật mật khẩu.";
            }
        }
    }
    
    // Lấy lịch sử hoạt động gần đây
    $activities_stmt = $db->prepare("SELECT al.*, u.email 
                                    FROM activity_logs al 
                                    LEFT JOIN users u ON al.user_id = u.id 
                                    WHERE al.user_id = ? 
                                    ORDER BY al.created_at DESC 
                                    LIMIT 10");
    $activities_stmt->execute([$user['id']]);
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Xử lý cập nhật avatar
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/avatars/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                // Cập nhật avatar trong database
                $avatar_path = '/uploads/avatars/' . $new_filename;
                
                $update_stmt = $db->prepare("UPDATE users SET 
                    avatar = ?, 
                    updated_at = datetime('now') 
                    WHERE id = ?");
                
                $result = $update_stmt->execute([$avatar_path, $user['id']]);
                
                if ($result) {
                    $success_message = "Avatar đã được cập nhật thành công.";
                    
                    // Cập nhật lại thông tin người dùng
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
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
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Thông tin cá nhân - Quản trị Lọc Phim';

// Bao gồm header quản trị
include 'admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">
                            <i class="fas fa-film"></i> Quản lý phim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-list"></i> Thể loại
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Người dùng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments"></i> Bình luận
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Cài đặt
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="google_drive.php">
                            <i class="fab fa-google-drive"></i> Google Drive
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Báo cáo</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history"></i> Nhật ký
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Thông tin cá nhân</h1>
            </div>
            
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

            <div class="row">
                <!-- Column 1: Avatar and quick info -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <img src="<?php echo !empty($user['avatar']) ? '../' . ltrim($user['avatar'], '/') : '../assets/img/default-avatar.svg'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" 
                                 class="rounded-circle img-thumbnail mb-3"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="badge bg-primary">Quản trị viên</p>
                            
                            <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#updateAvatarModal">
                                <i class="fas fa-camera"></i> Thay đổi ảnh
                            </button>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Ngày tạo:</span>
                                <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Đăng nhập cuối:</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($user['last_login'] ?? $user['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Trạng thái:</span>
                                <span class="badge bg-success">Đang hoạt động</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Column 2: Edit profile and change password -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                        <i class="fas fa-user"></i> Thông tin cá nhân
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                                        <i class="fas fa-key"></i> Đổi mật khẩu
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">
                                        <i class="fas fa-history"></i> Hoạt động gần đây
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="profileTabsContent">
                                <!-- Thông tin cá nhân -->
                                <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                    <form action="" method="post">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Tên đăng nhập</label>
                                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                            <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Họ và tên</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <button type="submit" name="update_info" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Lưu thay đổi
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Đổi mật khẩu -->
                                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
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
                                            <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
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
                                
                                <!-- Hoạt động gần đây -->
                                <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Thời gian</th>
                                                    <th>Hành động</th>
                                                    <th>IP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($activities)): ?>
                                                    <?php foreach($activities as $activity): ?>
                                                        <tr>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Không có hoạt động nào gần đây</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
                        <small class="text-muted">Chấp nhận file JPG, JPEG, PNG hoặc GIF</small>
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
});
</script>

<?php include 'admin_footer.php'; ?>