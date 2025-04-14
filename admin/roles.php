<?php
/**
 * Trang quản lý phân quyền admin
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Phân Quyền Admin';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản trị viên cấp cao nhất
$admin = require_admin_permission('manage_roles');

// Danh sách các quyền hạn
$permissions = [
    'general' => [
        'title' => 'Chung',
        'permissions' => [
            'view_dashboard' => 'Xem bảng điều khiển',
            'manage_settings' => 'Quản lý cài đặt hệ thống',
            'manage_maintenance' => 'Quản lý chế độ bảo trì',
            'view_logs' => 'Xem nhật ký hệ thống'
        ]
    ],
    'content' => [
        'title' => 'Nội dung',
        'permissions' => [
            'view_videos' => 'Xem danh sách phim',
            'add_videos' => 'Thêm phim mới',
            'edit_videos' => 'Chỉnh sửa phim',
            'delete_videos' => 'Xóa phim',
            'manage_categories' => 'Quản lý danh mục',
            'manage_featured' => 'Quản lý phim nổi bật'
        ]
    ],
    'users' => [
        'title' => 'Người dùng',
        'permissions' => [
            'view_users' => 'Xem danh sách người dùng',
            'add_users' => 'Thêm người dùng mới',
            'edit_users' => 'Chỉnh sửa người dùng',
            'delete_users' => 'Xóa người dùng',
            'manage_roles' => 'Quản lý phân quyền',
            'manage_vip' => 'Quản lý thành viên VIP'
        ]
    ],
    'interaction' => [
        'title' => 'Tương tác',
        'permissions' => [
            'view_comments' => 'Xem bình luận',
            'edit_comments' => 'Chỉnh sửa bình luận',
            'delete_comments' => 'Xóa bình luận',
            'approve_comments' => 'Phê duyệt bình luận',
            'view_ratings' => 'Xem đánh giá',
            'manage_ratings' => 'Quản lý đánh giá',
            'view_reports' => 'Xem báo cáo',
            'handle_reports' => 'Xử lý báo cáo'
        ]
    ],
    'payments' => [
        'title' => 'Thanh toán',
        'permissions' => [
            'view_payments' => 'Xem lịch sử thanh toán',
            'manage_payments' => 'Quản lý thanh toán',
            'manage_vip_packages' => 'Quản lý gói VIP'
        ]
    ],
    'api' => [
        'title' => 'API & Tích hợp',
        'permissions' => [
            'manage_api' => 'Quản lý API',
            'view_api_logs' => 'Xem nhật ký API',
            'manage_api_sources' => 'Quản lý nguồn API'
        ]
    ],
    'system' => [
        'title' => 'Hệ thống',
        'permissions' => [
            'manage_appearance' => 'Quản lý giao diện',
            'manage_backup' => 'Quản lý sao lưu & phục hồi',
            'run_system_tasks' => 'Chạy tác vụ hệ thống',
            'view_system_info' => 'Xem thông tin hệ thống'
        ]
    ]
];

// Xử lý cập nhật quyền
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy dữ liệu từ form
    $role = $_POST['role'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $role_permissions = $_POST['permissions'] ?? [];
    
    // Xác thực dữ liệu
    if (empty($role) || ($role !== 'admin' && $role !== 'moderator') || (!empty($user_id) && $user_id <= 0)) {
        set_flash_message('error', 'Dữ liệu không hợp lệ. Vui lòng thử lại.');
        header('Location: roles.php');
        exit;
    }
    
    // Không thể thay đổi quyền của chính mình
    if ($user_id === (int)$_SESSION['admin_id']) {
        set_flash_message('error', 'Bạn không thể thay đổi quyền của chính mình.');
        header('Location: roles.php');
        exit;
    }
    
    // Lấy thông tin người dùng cần cập nhật
    $sql = "SELECT id, username, role FROM users WHERE id = ?";
    $result = db_query($sql, [$user_id], false);
    
    $user = null;
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
        }
    } else {
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    }
    
    if (!$user) {
        set_flash_message('error', 'Không tìm thấy người dùng.');
        header('Location: roles.php');
        exit;
    }
    
    // Cập nhật vai trò người dùng
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    db_query($sql, [$role, $user_id]);
    
    // Lưu quyền chi tiết vào bảng role_permissions
    
    // Đầu tiên, kiểm tra và tạo bảng role_permissions nếu cần
    check_and_create_permissions_table();
    
    // Xóa các quyền hiện tại
    $sql = "DELETE FROM role_permissions WHERE user_id = ?";
    db_query($sql, [$user_id]);
    
    // Thêm quyền mới
    if (!empty($role_permissions)) {
        foreach ($role_permissions as $permission) {
            $sql = "INSERT INTO role_permissions (user_id, permission, created_at) VALUES (?, ?, NOW())";
            db_query($sql, [$user_id, $permission]);
        }
    }
    
    // Ghi log
    log_admin_action('update_role', "Cập nhật vai trò cho người dùng: {$user['username']} - Vai trò: $role");
    
    // Thông báo thành công
    set_flash_message('success', "Đã cập nhật vai trò và quyền hạn cho người dùng {$user['username']}.");
    
    // Chuyển hướng
    header('Location: roles.php');
    exit;
}

// Lấy danh sách admin và moderator
$sql = "SELECT id, username, email, full_name, role, last_login FROM users WHERE role IN ('admin', 'moderator') ORDER BY role, username";
$result = db_query($sql);

$admins = [];
if (get_config('db.type') === 'postgresql') {
    while ($row = pg_fetch_assoc($result)) {
        $admins[] = $row;
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Lấy người dùng để chỉnh sửa quyền
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$editing_user = null;
$user_permissions = [];

if ($user_id > 0) {
    // Lấy thông tin người dùng
    $sql = "SELECT id, username, email, full_name, role, last_login FROM users WHERE id = ?";
    $result = db_query($sql, [$user_id], false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $editing_user = pg_fetch_assoc($result);
        }
    } else {
        if ($result && $result->num_rows > 0) {
            $editing_user = $result->fetch_assoc();
        }
    }
    
    // Kiểm tra và tạo bảng quyền nếu cần
    check_and_create_permissions_table();
    
    // Lấy quyền hiện tại của người dùng
    $sql = "SELECT permission FROM role_permissions WHERE user_id = ?";
    $result = db_query($sql, [$user_id]);
    
    if (get_config('db.type') === 'postgresql') {
        while ($row = pg_fetch_assoc($result)) {
            $user_permissions[] = $row['permission'];
        }
    } else {
        while ($row = $result->fetch_assoc()) {
            $user_permissions[] = $row['permission'];
        }
    }
}

// CSRF token
$csrf_token = generate_csrf_token();

// Hàm kiểm tra xem quyền có được chọn không
function is_permission_checked($permission, $user_permissions, $editing_user) {
    // Nếu là admin, mặc định có tất cả quyền
    if ($editing_user && $editing_user['role'] === 'admin') {
        return true;
    }
    
    return in_array($permission, $user_permissions);
}

// Hàm kiểm tra và tạo bảng role_permissions nếu cần
function check_and_create_permissions_table() {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        // Kiểm tra bảng tồn tại
        $conn = db_connect();
        $check_table_sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public'
            AND table_name = 'role_permissions'
        )";
        
        $result = pg_query($conn, $check_table_sql);
        $row = pg_fetch_row($result);
        
        if ($row[0] == 'f') {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE role_permissions (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    permission VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP NOT NULL,
                    CONSTRAINT fk_permission_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    CONSTRAINT uq_user_permission UNIQUE (user_id, permission)
                );
                CREATE INDEX role_permissions_user_id_idx ON role_permissions (user_id);
                CREATE INDEX role_permissions_permission_idx ON role_permissions (permission);
            ";
            
            pg_query($conn, $create_table_sql);
            return pg_last_error($conn) === '';
        }
        
        return true;
    } else {
        // MySQL
        $conn = db_connect();
        $check_table_sql = "SHOW TABLES LIKE 'role_permissions'";
        $result = $conn->query($check_table_sql);
        
        if ($result->num_rows == 0) {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE role_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    permission VARCHAR(100) NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY uq_user_permission (user_id, permission),
                    INDEX (permission)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $conn->query($create_table_sql);
            return $conn->error === '';
        }
        
        return true;
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Phân Quyền Admin</h1>
        <p class="admin-page-subtitle">Quản lý vai trò và quyền hạn của quản trị viên</p>
    </div>
    
    <div class="admin-page-actions">
        <a href="users.php?role=admin" class="btn btn-outline-primary">
            <i class="fas fa-users mr-1"></i> Quản Lý Người Dùng
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-users-cog mr-2"></i> Quản Trị Viên
                </h2>
            </div>
            <div class="admin-card-body p-0">
                <div class="list-group list-group-flush admin-users-list">
                    <?php if (empty($admins)): ?>
                        <div class="list-group-item text-center text-muted">
                            Không có quản trị viên nào.
                        </div>
                    <?php else: ?>
                        <?php foreach ($admins as $admin_user): ?>
                            <a href="roles.php?user_id=<?php echo $admin_user['id']; ?>" class="list-group-item list-group-item-action <?php echo $editing_user && $editing_user['id'] === $admin_user['id'] ? 'active' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="admin-user-avatar mr-3">
                                        <div class="admin-user-avatar-text">
                                            <?php echo strtoupper(substr($admin_user['username'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-1 text-truncate"><?php echo $admin_user['username']; ?></h5>
                                            <span class="badge badge-<?php echo $admin_user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                <?php echo $admin_user['role'] === 'admin' ? 'Admin' : 'Mod'; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1 text-truncate"><?php echo $admin_user['email']; ?></p>
                                        <small class="text-muted">
                                            <?php if ($admin_user['last_login']): ?>
                                                Đăng nhập: <?php echo date('d/m/Y H:i', strtotime($admin_user['last_login'])); ?>
                                            <?php else: ?>
                                                Chưa đăng nhập
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                <div class="admin-card-footer">
                    <a href="users-add.php?role=admin" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Thêm Quản Trị Viên
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($editing_user): ?>
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">
                        <i class="fas fa-user-shield mr-2"></i> Phân Quyền: <?php echo $editing_user['username']; ?>
                    </h2>
                </div>
                <div class="admin-card-body">
                    <?php if ($editing_user['id'] === (int)$_SESSION['admin_id']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Bạn không thể chỉnh sửa quyền của chính mình.
                        </div>
                    <?php else: ?>
                        <form method="post" action="roles.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $editing_user['id']; ?>">
                            
                            <div class="form-group">
                                <label for="role">Vai trò</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="admin" <?php echo $editing_user['role'] === 'admin' ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                                    <option value="moderator" <?php echo $editing_user['role'] === 'moderator' ? 'selected' : ''; ?>>Điều hành viên (Moderator)</option>
                                </select>
                                <small class="form-text text-muted">
                                    Quản trị viên có tất cả các quyền. Điều hành viên chỉ có các quyền được chỉ định.
                                </small>
                            </div>
                            
                            <div class="form-group" id="permissionsContainer" <?php echo $editing_user['role'] === 'admin' ? 'style="display: none;"' : ''; ?>>
                                <label>Quyền hạn</label>
                                
                                <div class="permissions-list mt-3">
                                    <?php foreach ($permissions as $group_key => $group): ?>
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input permission-group" id="group_<?php echo $group_key; ?>" data-group="<?php echo $group_key; ?>">
                                                    <label class="custom-control-label" for="group_<?php echo $group_key; ?>">
                                                        <strong><?php echo $group['title']; ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <?php foreach ($group['permissions'] as $permission => $description): ?>
                                                        <div class="col-md-6">
                                                            <div class="custom-control custom-checkbox mb-2">
                                                                <input type="checkbox" class="custom-control-input permission-item" id="<?php echo $permission; ?>" name="permissions[]" value="<?php echo $permission; ?>" data-group="<?php echo $group_key; ?>" <?php echo is_permission_checked($permission, $user_permissions, $editing_user) ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="<?php echo $permission; ?>">
                                                                    <?php echo $description; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu thay đổi
                                </button>
                                <a href="roles.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times mr-1"></i> Hủy
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-card mb-4">
                <div class="admin-card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-user-shield fa-4x text-muted"></i>
                    </div>
                    <h4>Chọn người dùng để phân quyền</h4>
                    <p class="text-muted">Vui lòng chọn một quản trị viên hoặc điều hành viên từ danh sách bên trái để xem và chỉnh sửa quyền hạn.</p>
                </div>
            </div>
            
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">
                        <i class="fas fa-info-circle mr-2"></i> Thông Tin Vai Trò
                    </h2>
                </div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">Quản trị viên (Admin)</h5>
                                </div>
                                <div class="card-body">
                                    <p>Quản trị viên có toàn quyền trên hệ thống, bao gồm:</p>
                                    <ul>
                                        <li>Quản lý người dùng và phân quyền</li>
                                        <li>Quản lý cài đặt hệ thống</li>
                                        <li>Truy cập tất cả các chức năng</li>
                                        <li>Kích hoạt/Tắt chế độ bảo trì</li>
                                        <li>Xem và xóa nhật ký hệ thống</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Điều hành viên (Moderator)</h5>
                                </div>
                                <div class="card-body">
                                    <p>Điều hành viên có các quyền hạn chế, có thể bao gồm:</p>
                                    <ul>
                                        <li>Quản lý nội dung phim và danh mục</li>
                                        <li>Kiểm duyệt bình luận và đánh giá</li>
                                        <li>Xử lý báo cáo từ người dùng</li>
                                        <li>Xem danh sách người dùng</li>
                                        <li>Các quyền khác được phân công</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// JavaScript để xử lý chọn quyền
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Xử lý hiển thị/ẩn danh sách quyền dựa trên vai trò
    const roleSelect = document.getElementById("role");
    const permissionsContainer = document.getElementById("permissionsContainer");
    
    if (roleSelect && permissionsContainer) {
        roleSelect.addEventListener("change", function() {
            if (this.value === "admin") {
                permissionsContainer.style.display = "none";
            } else {
                permissionsContainer.style.display = "block";
            }
        });
    }
    
    // Xử lý checkbox nhóm quyền
    const permissionGroups = document.querySelectorAll(".permission-group");
    const permissionItems = document.querySelectorAll(".permission-item");
    
    // Kiểm tra trạng thái ban đầu của các nhóm
    permissionGroups.forEach(group => {
        const groupName = group.dataset.group;
        const itemsInGroup = document.querySelectorAll(`.permission-item[data-group="${groupName}"]`);
        const checkedItemsInGroup = document.querySelectorAll(`.permission-item[data-group="${groupName}"]:checked`);
        
        if (itemsInGroup.length > 0 && checkedItemsInGroup.length === itemsInGroup.length) {
            group.checked = true;
        } else if (checkedItemsInGroup.length > 0) {
            group.indeterminate = true;
        }
    });
    
    // Xử lý khi click vào checkbox nhóm
    permissionGroups.forEach(group => {
        group.addEventListener("change", function() {
            const groupName = this.dataset.group;
            const itemsInGroup = document.querySelectorAll(`.permission-item[data-group="${groupName}"]`);
            
            itemsInGroup.forEach(item => {
                item.checked = this.checked;
            });
        });
    });
    
    // Xử lý khi click vào checkbox con
    permissionItems.forEach(item => {
        item.addEventListener("change", function() {
            const groupName = this.dataset.group;
            const group = document.querySelector(`.permission-group[data-group="${groupName}"]`);
            const itemsInGroup = document.querySelectorAll(`.permission-item[data-group="${groupName}"]`);
            const checkedItemsInGroup = document.querySelectorAll(`.permission-item[data-group="${groupName}"]:checked`);
            
            if (itemsInGroup.length === checkedItemsInGroup.length) {
                group.checked = true;
                group.indeterminate = false;
            } else if (checkedItemsInGroup.length === 0) {
                group.checked = false;
                group.indeterminate = false;
            } else {
                group.checked = false;
                group.indeterminate = true;
            }
        });
    });
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>