<?php
/**
 * Trang quản lý nhật ký hệ thống
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Nhật Ký Hệ Thống';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền xem logs
$admin = require_admin_permission('view_logs');

// Xử lý xóa logs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy hành động
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_logs') {
        // Xóa tất cả logs
        $sql = "DELETE FROM admin_logs WHERE 1=1";
        db_query($sql);
        
        // Ghi log cho hành động xóa logs
        log_admin_action('clear_logs', 'Đã xóa tất cả nhật ký hệ thống');
        
        // Thông báo thành công
        set_flash_message('success', 'Đã xóa tất cả nhật ký hệ thống thành công!');
    } elseif ($action === 'delete_selected_logs' && isset($_POST['log_ids']) && is_array($_POST['log_ids'])) {
        // Xóa các logs được chọn
        $log_ids = array_map('intval', $_POST['log_ids']);
        
        if (!empty($log_ids)) {
            $ids_string = implode(',', $log_ids);
            $sql = "DELETE FROM admin_logs WHERE id IN ($ids_string)";
            db_query($sql);
            
            // Ghi log
            $count = count($log_ids);
            log_admin_action('delete_logs', "Đã xóa $count nhật ký đã chọn");
            
            // Thông báo thành công
            set_flash_message('success', "Đã xóa $count nhật ký đã chọn thành công!");
        } else {
            set_flash_message('error', 'Vui lòng chọn ít nhất một nhật ký để xóa.');
        }
    }
    
    // Chuyển hướng để tránh gửi lại form
    header('Location: logs.php');
    exit;
}

// Lấy tham số phân trang và lọc
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 50;
$offset = ($current_page - 1) * $items_per_page;

// Tham số lọc
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Xây dựng câu truy vấn
$params = [];
$where_clauses = [];

if (!empty($filter_action)) {
    $where_clauses[] = "action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_user)) {
    $where_clauses[] = "user_id = ?";
    $params[] = $filter_user;
}

if (!empty($filter_date_from)) {
    $date_from = date('Y-m-d 00:00:00', strtotime($filter_date_from));
    $where_clauses[] = "created_at >= ?";
    $params[] = $date_from;
}

if (!empty($filter_date_to)) {
    $date_to = date('Y-m-d 23:59:59', strtotime($filter_date_to));
    $where_clauses[] = "created_at <= ?";
    $params[] = $date_to;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Lấy tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM admin_logs $where_sql";
$count_result = db_query($count_sql, $params, false);

$total_items = 0;
if (get_config('db.type') === 'postgresql') {
    $total_items = pg_fetch_assoc($count_result)['total'];
} else {
    $total_items = $count_result->fetch_assoc()['total'];
}

// Tính số trang
$total_pages = ceil($total_items / $items_per_page);

// Lấy dữ liệu logs
$sql = "SELECT l.*, u.username 
        FROM admin_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        $where_sql 
        ORDER BY l.created_at DESC 
        LIMIT $items_per_page OFFSET $offset";

$result = db_query($sql, $params);

$logs = [];
if (get_config('db.type') === 'postgresql') {
    while ($row = pg_fetch_assoc($result)) {
        $logs[] = $row;
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Lấy danh sách loại action để lọc
$action_sql = "SELECT DISTINCT action FROM admin_logs ORDER BY action";
$action_result = db_query($action_sql);

$action_types = [];
if (get_config('db.type') === 'postgresql') {
    while ($row = pg_fetch_assoc($action_result)) {
        $action_types[] = $row['action'];
    }
} else {
    while ($row = $action_result->fetch_assoc()) {
        $action_types[] = $row['action'];
    }
}

// Lấy danh sách user để lọc
$users_sql = "SELECT DISTINCT u.id, u.username 
             FROM admin_logs l 
             INNER JOIN users u ON l.user_id = u.id 
             ORDER BY u.username";
$users_result = db_query($users_sql);

$users = [];
if (get_config('db.type') === 'postgresql') {
    while ($row = pg_fetch_assoc($users_result)) {
        $users[$row['id']] = $row['username'];
    }
} else {
    while ($row = $users_result->fetch_assoc()) {
        $users[$row['id']] = $row['username'];
    }
}

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Nhật Ký Hệ Thống</h1>
        <p class="admin-page-subtitle">Xem và quản lý nhật ký hoạt động của quản trị viên</p>
    </div>
    
    <div class="admin-page-actions">
        <form method="post" action="logs.php" class="d-inline-block mr-2" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tất cả nhật ký không? Hành động này không thể hoàn tác.');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="clear_logs">
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash mr-1"></i> Xóa Tất Cả
            </button>
        </form>
        
        <button type="button" class="btn btn-outline-primary" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
            <i class="fas fa-filter mr-1"></i> Bộ Lọc
        </button>
    </div>
</div>

<div class="collapse mb-4" id="filterCollapse">
    <div class="admin-card">
        <div class="admin-card-body">
            <form method="get" action="logs.php" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="action">Loại Hành Động</label>
                        <select class="form-control" id="action" name="action">
                            <option value="">Tất cả hành động</option>
                            <?php foreach ($action_types as $action_type): ?>
                                <option value="<?php echo $action_type; ?>" <?php echo $filter_action === $action_type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $action_type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="user_id">Quản Trị Viên</label>
                        <select class="form-control" id="user_id" name="user_id">
                            <option value="">Tất cả quản trị viên</option>
                            <?php foreach ($users as $id => $username): ?>
                                <option value="<?php echo $id; ?>" <?php echo $filter_user === $id ? 'selected' : ''; ?>>
                                    <?php echo $username; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_from">Từ Ngày</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_to">Đến Ngày</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>">
                    </div>
                </div>
                
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Lọc
                    </button>
                    <a href="logs.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-redo mr-1"></i> Đặt Lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i> Không có nhật ký nào được tìm thấy.
            </div>
        <?php else: ?>
            <form method="post" action="logs.php" id="logsForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="delete_selected_logs">
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAll">
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th>Hành Động</th>
                                <th>Chi Tiết</th>
                                <th>Quản Trị Viên</th>
                                <th>IP</th>
                                <th width="160">Thời Gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="log<?php echo $log['id']; ?>" name="log_ids[]" value="<?php echo $log['id']; ?>">
                                            <label class="custom-control-label" for="log<?php echo $log['id']; ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo get_action_color($log['action']); ?>">
                                            <?php echo format_action_name($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td>
                                        <?php if (!empty($log['username'])): ?>
                                            <a href="logs.php?user_id=<?php echo $log['user_id']; ?>"><?php echo $log['username']; ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">Không xác định</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['ip_address'] ?? '-'; ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <button type="submit" class="btn btn-danger btn-sm delete-selected" disabled>
                            <i class="fas fa-trash mr-1"></i> Xóa Đã Chọn
                        </button>
                    </div>
                    
                    <div>
                        <span class="text-muted">Hiển thị <?php echo count($logs); ?> / <?php echo $total_items; ?> nhật ký</span>
                    </div>
                </div>
            </form>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Phân trang" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="logs.php?page=1<?php echo get_query_string(['page']); ?>" aria-label="Trang đầu">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="logs.php?page=<?php echo $current_page - 1; ?><?php echo get_query_string(['page']); ?>" aria-label="Trang trước">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;&laquo;</span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $current_page) {
                                echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="logs.php?page=' . $i . get_query_string(['page']) . '">' . $i . '</a></li>';
                            }
                        }
                        
                        if ($end_page < $total_pages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="logs.php?page=<?php echo $current_page + 1; ?><?php echo get_query_string(['page']); ?>" aria-label="Trang sau">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="logs.php?page=<?php echo $total_pages; ?><?php echo get_query_string(['page']); ?>" aria-label="Trang cuối">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Hàm định dạng tên hành động
function format_action_name($action) {
    $action = str_replace('_', ' ', $action);
    return ucwords($action);
}

// Hàm lấy màu cho loại hành động
function get_action_color($action) {
    switch ($action) {
        case 'login':
        case 'update_profile':
        case 'update_settings':
        case 'update_api_settings':
        case 'update_performance_settings':
            return 'primary';
        
        case 'login_failed':
        case 'delete_logs':
        case 'clear_logs':
        case 'clear_cache':
        case 'delete_video':
        case 'delete_user':
        case 'delete_comment':
            return 'danger';
        
        case 'update_video':
        case 'update_user':
        case 'update_category':
        case 'add_video':
        case 'add_user':
        case 'add_category':
            return 'success';
        
        case 'enable_maintenance':
        case 'disable_maintenance':
        case 'regenerate_htaccess':
            return 'warning';
        
        default:
            return 'secondary';
    }
}

// Hàm tạo query string cho phân trang
function get_query_string($exclude = []) {
    $params = $_GET;
    
    foreach ($exclude as $param) {
        if (isset($params[$param])) {
            unset($params[$param]);
        }
    }
    
    if (empty($params)) {
        return '';
    }
    
    return '&' . http_build_query($params);
}

// JavaScript cho trang
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Xử lý chọn tất cả
    const selectAllCheckbox = document.getElementById("selectAll");
    const checkboxes = document.querySelectorAll("input[name=\'log_ids[]\']");
    const deleteSelectedButton = document.querySelector(".delete-selected");
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function() {
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateDeleteButtonState();
        });
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            // Kiểm tra nếu tất cả được chọn
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
            }
            
            updateDeleteButtonState();
        });
    });
    
    function updateDeleteButtonState() {
        const checkedCount = document.querySelectorAll("input[name=\'log_ids[]\']:checked").length;
        
        if (deleteSelectedButton) {
            deleteSelectedButton.disabled = checkedCount === 0;
        }
    }
    
    // Xác nhận xóa các mục đã chọn
    const logsForm = document.getElementById("logsForm");
    
    if (logsForm) {
        logsForm.addEventListener("submit", function(e) {
            const checkedCount = document.querySelectorAll("input[name=\'log_ids[]\']:checked").length;
            
            if (checkedCount === 0) {
                e.preventDefault();
                alert("Vui lòng chọn ít nhất một nhật ký để xóa.");
                return false;
            }
            
            if (!confirm(`Bạn có chắc chắn muốn xóa ${checkedCount} nhật ký đã chọn không? Hành động này không thể hoàn tác.`)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Tự động mở bộ lọc nếu có tham số lọc
    if (
        window.location.search.includes("action=") || 
        window.location.search.includes("user_id=") || 
        window.location.search.includes("date_from=") || 
        window.location.search.includes("date_to=")
    ) {
        document.getElementById("filterCollapse").classList.add("show");
    }
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>