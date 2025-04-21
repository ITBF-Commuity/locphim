<?php
// Trang quản lý nhật ký hệ thống
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
    
    // Kiểm tra bảng logs có tồn tại không
    $logs_table_exists = false;
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='logs'");
    if ($tables->fetchColumn()) {
        $logs_table_exists = true;
    } else {
        // Tạo bảng logs nếu chưa tồn tại
        $db->exec("CREATE TABLE logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            entity_type TEXT,
            entity_id INTEGER,
            ip_address TEXT,
            user_agent TEXT,
            details TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $logs_table_exists = true;
        
        // Thêm một số log mẫu
        $db->exec("INSERT INTO logs (user_id, action, entity_type, entity_id, ip_address, details, created_at) 
                  VALUES (1, 'login', 'users', 1, '127.0.0.1', 'Đăng nhập thành công', datetime('now'))");
        $db->exec("INSERT INTO logs (user_id, action, entity_type, entity_id, ip_address, details, created_at) 
                  VALUES (1, 'create', 'movies', 1, '127.0.0.1', 'Thêm phim mới: Avengers: Endgame', datetime('now', '-1 day'))");
        $db->exec("INSERT INTO logs (user_id, action, entity_type, entity_id, ip_address, details, created_at) 
                  VALUES (1, 'update', 'categories', 1, '127.0.0.1', 'Cập nhật thể loại: Hành động', datetime('now', '-2 day'))");
    }
    
    // Xử lý xóa nhật ký
    if (isset($_POST['clear_logs']) && isset($_POST['confirm_clear'])) {
        $db->exec("DELETE FROM logs");
        $success_message = "Đã xóa tất cả nhật ký hệ thống!";
    }
    
    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Tìm kiếm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = "WHERE action LIKE ? OR entity_type LIKE ? OR details LIKE ?";
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param];
    }
    
    // Lọc theo loại hành động
    $action_filter = isset($_GET['action']) ? $_GET['action'] : 'all';
    if ($action_filter != 'all') {
        if (empty($where_clause)) {
            $where_clause = "WHERE action = ?";
        } else {
            $where_clause .= " AND action = ?";
        }
        $params[] = $action_filter;
    }
    
    // Đếm tổng số nhật ký
    $count_sql = "SELECT COUNT(*) FROM logs $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_logs = $stmt->fetchColumn();
    $total_pages = ceil($total_logs / $limit);
    
    // Lấy danh sách nhật ký
    $sql = "SELECT l.*, u.username 
           FROM logs l 
           LEFT JOIN users u ON l.user_id = u.id 
           $where_clause 
           ORDER BY l.created_at DESC 
           LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh sách loại hành động để lọc
    $action_types_sql = "SELECT DISTINCT action FROM logs ORDER BY action";
    $action_types = $db->query($action_types_sql)->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Nhật ký hệ thống - Quản trị Lọc Phim';

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
                        <a class="nav-link active" href="logs.php">
                            <i class="fas fa-history"></i> Nhật ký
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Nhật ký hệ thống</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportLogs()">
                            <i class="fas fa-download"></i> Xuất logs
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                            <i class="fas fa-trash"></i> Xóa logs
                        </button>
                    </div>
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
                        <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm nhật ký..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <a href="?action=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-secondary <?php echo $action_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                        
                        <?php foreach ($action_types as $type): ?>
                        <a href="?action=<?php echo urlencode($type); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-secondary <?php echo $action_filter == $type ? 'active' : ''; ?>">
                            <?php echo ucfirst($type); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Bảng nhật ký -->
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thời gian</th>
                            <th>Người dùng</th>
                            <th>Hành động</th>
                            <th>Đối tượng</th>
                            <th>ID đối tượng</th>
                            <th>IP</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo $log['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($log['username'] ?? 'Hệ thống'); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        switch ($log['action']) {
                                            case 'login':
                                            case 'logout':
                                                $badge_class = 'bg-primary';
                                                break;
                                            case 'create':
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'update':
                                                $badge_class = 'bg-info';
                                                break;
                                            case 'delete':
                                                $badge_class = 'bg-danger';
                                                break;
                                            case 'error':
                                                $badge_class = 'bg-warning text-dark';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($log['action']); ?></span>
                                    </td>
                                    <td><?php echo $log['entity_type'] ? ucfirst($log['entity_type']) : '-'; ?></td>
                                    <td><?php echo $log['entity_id'] ?: '-'; ?></td>
                                    <td><?php echo $log['ip_address'] ?: '-'; ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Không có nhật ký nào</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action_filter != 'all' ? '&action=' . $action_filter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 3); $i <= min($total_pages, $page + 3); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action_filter != 'all' ? '&action=' . $action_filter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action_filter != 'all' ? '&action=' . $action_filter : ''; ?>" aria-label="Next">
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

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearLogsModalLabel">Xác nhận xóa nhật ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Cảnh báo: Hành động này sẽ xóa tất cả nhật ký hệ thống và không thể khôi phục.
                </div>
                <p>Bạn có chắc chắn muốn tiếp tục?</p>
                <form id="clearLogsForm" action="" method="post">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_clear" name="confirm_clear" required>
                        <label class="form-check-label" for="confirm_clear">
                            Tôi xác nhận muốn xóa tất cả nhật ký
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" form="clearLogsForm" name="clear_logs" class="btn btn-danger">Xóa tất cả nhật ký</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Hàm xuất logs
    function exportLogs() {
        // Tạo tên file logs với timestamp
        const now = new Date();
        const timestamp = now.toISOString().replace(/[:.]/g, '-');
        const filename = `loc-phim-logs-${timestamp}.csv`;
        
        // Giả lập tải xuống
        alert('Đã xuất nhật ký: ' + filename);
    }
</script>

<?php include 'admin_footer.php'; ?>