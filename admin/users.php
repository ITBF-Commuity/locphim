<?php
// Trang quản lý người dùng
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Kết nối database
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Xử lý thêm người dùng
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role_id = (int)$_POST['role_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Kiểm tra username đã tồn tại chưa
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tên đăng nhập đã tồn tại!";
        } else {
            // Kiểm tra email đã tồn tại chưa
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Email đã tồn tại!";
            } else {
                // Mã hóa mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Thêm người dùng mới
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id, status, created_at, updated_at) 
                                      VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                $stmt->execute([$username, $email, $hashed_password, $role_id, $status]);
                
                $success_message = "Đã thêm người dùng mới thành công!";
            }
        }
    }
    
    // Xử lý cập nhật người dùng
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $email = trim($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Kiểm tra email đã tồn tại chưa (trừ người dùng hiện tại)
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email đã tồn tại!";
        } else {
            // Chuẩn bị câu lệnh SQL
            $sql = "UPDATE users SET email = ?, role_id = ?, status = ?, updated_at = datetime('now')";
            $params = [$email, $role_id, $status];
            
            // Nếu có mật khẩu mới, mã hóa và thêm vào câu lệnh
            if (!empty($_POST['password'])) {
                $new_password = trim($_POST['password']);
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashed_password;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            // Cập nhật người dùng
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $success_message = "Đã cập nhật người dùng thành công!";
        }
    }
    
    // Xử lý xóa người dùng
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Không cho phép xóa chính mình
        if ($user_id == $_SESSION['user_id']) {
            $error = "Bạn không thể xóa tài khoản hiện tại của mình!";
        } else {
            // Xóa người dùng
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $success_message = "Đã xóa người dùng thành công!";
        }
    }
    
    // Lấy danh sách các vai trò
    $roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Tìm kiếm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = "WHERE username LIKE ? OR email LIKE ?";
        $search_param = "%$search%";
        $params = [$search_param, $search_param];
    }
    
    // Lọc theo vai trò
    $role_filter = isset($_GET['role']) ? (int)$_GET['role'] : 0;
    if ($role_filter > 0) {
        if (empty($where_clause)) {
            $where_clause = "WHERE role_id = ?";
        } else {
            $where_clause .= " AND role_id = ?";
        }
        $params[] = $role_filter;
    }
    
    // Lọc theo trạng thái
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    if ($status_filter != 'all') {
        $status_value = ($status_filter == 'active') ? 1 : 0;
        if (empty($where_clause)) {
            $where_clause = "WHERE status = ?";
        } else {
            $where_clause .= " AND status = ?";
        }
        $params[] = $status_value;
    }
    
    // Đếm tổng số người dùng
    $count_sql = "SELECT COUNT(*) FROM users $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
    
    // Lấy danh sách người dùng
    $sql = "SELECT u.*, r.name as role_name 
           FROM users u 
           JOIN roles r ON u.role_id = r.id 
           $where_clause 
           ORDER BY u.id DESC 
           LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Quản lý người dùng - Quản trị Lọc Phim';

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
                        <a class="nav-link active" href="users.php">
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
                <h1 class="h2">Quản lý người dùng</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus"></i> Thêm người dùng
                    </button>
                </div>
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
            
            <!-- Bộ lọc và tìm kiếm -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="get" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                        <a href="?status=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'active' ? 'active' : ''; ?>">Đang hoạt động</a>
                        <a href="?status=inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'inactive' ? 'active' : ''; ?>">Bị khóa</a>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Vai trò
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item <?php echo $role_filter == 0 ? 'active' : ''; ?>" href="?role=0<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>">Tất cả vai trò</a></li>
                            <?php foreach ($roles as $role): ?>
                            <li><a class="dropdown-item <?php echo $role_filter == $role['id'] ? 'active' : ''; ?>" href="?role=<?php echo $role['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>"><?php echo htmlspecialchars($role['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Bảng người dùng -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th>Lần đăng nhập cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                                    <td>
                                        <?php if ($user['status'] == 1): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Bị khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td><?php echo $user['last_login'] ?: 'Chưa đăng nhập'; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo $user['role_id']; ?>"
                                                    data-status="<?php echo $user['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-user"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Không tìm thấy người dùng nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter > 0 ? '&role=' . $role_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal thêm người dùng -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Thêm người dùng mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Vai trò</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" checked>
                        <label class="form-check-label" for="status">Hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Thêm người dùng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa người dùng -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Chỉnh sửa người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Mật khẩu mới (để trống nếu không thay đổi)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label">Vai trò</label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_status" name="status">
                        <label class="form-check-label" for="edit_status">Hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xóa người dùng -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Xác nhận xóa người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    <p>Bạn có chắc chắn muốn xóa người dùng <strong id="delete_username"></strong>?</p>
                    <p class="text-danger">Cảnh báo: Hành động này không thể hoàn tác và sẽ xóa tất cả dữ liệu liên quan đến người dùng này.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Xóa người dùng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý modal chỉnh sửa người dùng
        const editButtons = document.querySelectorAll('.edit-user');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const email = this.getAttribute('data-email');
                const roleId = this.getAttribute('data-role');
                const status = this.getAttribute('data-status');
                
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_role_id').value = roleId;
                document.getElementById('edit_status').checked = status == '1';
            });
        });
        
        // Xử lý modal xóa người dùng
        const deleteButtons = document.querySelectorAll('.delete-user');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('delete_username').textContent = username;
            });
        });
    });
</script>

<?php include 'admin_footer.php'; ?>