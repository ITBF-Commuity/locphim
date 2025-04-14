<?php
/**
 * Trang quản lý chế độ bảo trì
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Chế Độ Bảo Trì';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý bảo trì
$admin = require_admin_permission('manage_maintenance');

// Xử lý kích hoạt/tắt chế độ bảo trì
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy hành động
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable_maintenance') {
        // Kích hoạt chế độ bảo trì
        update_setting('maintenance_mode', '1');
        
        // Cập nhật thông báo bảo trì nếu có
        if (isset($_POST['maintenance_message']) && !empty($_POST['maintenance_message'])) {
            update_setting('maintenance_message', $_POST['maintenance_message']);
        }
        
        // Cập nhật thời gian hoàn thành nếu có
        if (isset($_POST['maintenance_end_time']) && !empty($_POST['maintenance_end_time'])) {
            update_setting('maintenance_end_time', $_POST['maintenance_end_time']);
        } else {
            update_setting('maintenance_end_time', '');
        }
        
        // Ghi log
        log_admin_action('enable_maintenance', 'Kích hoạt chế độ bảo trì');
        
        // Hiển thị thông báo thành công
        set_flash_message('success', 'Đã kích hoạt chế độ bảo trì thành công!');
    } elseif ($action === 'disable_maintenance') {
        // Tắt chế độ bảo trì
        update_setting('maintenance_mode', '0');
        
        // Ghi log
        log_admin_action('disable_maintenance', 'Tắt chế độ bảo trì');
        
        // Hiển thị thông báo thành công
        set_flash_message('success', 'Đã tắt chế độ bảo trì thành công!');
    }
    
    // Chuyển hướng để tránh gửi lại form
    header('Location: maintenance.php');
    exit;
}

// Kiểm tra trạng thái bảo trì
$maintenance_mode = check_maintenance_mode();
$maintenance_message = get_setting('maintenance_message', 'Trang web đang được bảo trì. Vui lòng quay lại sau.');
$maintenance_end_time = get_setting('maintenance_end_time', '');

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Chế Độ Bảo Trì</h1>
        <p class="admin-page-subtitle">Quản lý chế độ bảo trì của trang web</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-tools mr-2"></i> Trạng Thái Bảo Trì
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="maintenance-status mb-4">
                    <div class="alert <?php echo $maintenance_mode ? 'alert-warning' : 'alert-success'; ?>">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <?php if ($maintenance_mode): ?>
                                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                                <?php else: ?>
                                    <i class="fas fa-check-circle fa-3x"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="alert-heading mb-1">
                                    <?php echo $maintenance_mode ? 'Chế độ bảo trì đang được kích hoạt!' : 'Trang web đang hoạt động bình thường'; ?>
                                </h4>
                                <p class="mb-0">
                                    <?php if ($maintenance_mode): ?>
                                        Người dùng không thể truy cập trang web. Chỉ quản trị viên mới có thể đăng nhập.
                                    <?php else: ?>
                                        Trang web đang hoạt động bình thường và mọi người dùng đều có thể truy cập.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($maintenance_mode): ?>
                    <form method="post" action="maintenance.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="disable_maintenance">
                        
                        <div class="form-group">
                            <label>Thông báo bảo trì hiện tại:</label>
                            <p class="form-control-plaintext border p-2 bg-light"><?php echo $maintenance_message; ?></p>
                        </div>
                        
                        <?php if (!empty($maintenance_end_time)): ?>
                            <div class="form-group">
                                <label>Thời gian kết thúc dự kiến:</label>
                                <p class="form-control-plaintext border p-2 bg-light"><?php echo $maintenance_end_time; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-power-off mr-1"></i> Tắt Chế Độ Bảo Trì
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="post" action="maintenance.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="enable_maintenance">
                        
                        <div class="form-group">
                            <label for="maintenance_message">Thông báo bảo trì:</label>
                            <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" required><?php echo $maintenance_message; ?></textarea>
                            <small class="form-text text-muted">Thông báo này sẽ được hiển thị cho người dùng khi họ truy cập trang web.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="maintenance_end_time">Thời gian kết thúc dự kiến (tùy chọn):</label>
                            <input type="text" class="form-control" id="maintenance_end_time" name="maintenance_end_time" placeholder="Ví dụ: 18:00 ngày 15/04/2025" value="<?php echo $maintenance_end_time; ?>">
                            <small class="form-text text-muted">Thời gian dự kiến kết thúc bảo trì. Để trống nếu không xác định.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-power-off mr-1"></i> Kích Hoạt Chế Độ Bảo Trì
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-info-circle mr-2"></i> Thông Tin
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="alert alert-info">
                    <h5 class="alert-heading">Về Chế Độ Bảo Trì</h5>
                    <p>Khi kích hoạt chế độ bảo trì, tất cả người dùng không phải là quản trị viên sẽ thấy thông báo bảo trì thay vì nội dung thông thường của trang web.</p>
                    <hr>
                    <p class="mb-0">Chế độ này hữu ích khi bạn cần thực hiện các thay đổi lớn hoặc cập nhật hệ thống.</p>
                </div>
                
                <div class="mt-4">
                    <h5>Lưu ý quan trọng:</h5>
                    <ul class="pl-3">
                        <li>Quản trị viên vẫn có thể đăng nhập và sử dụng trang quản trị ngay cả khi chế độ bảo trì đang được kích hoạt.</li>
                        <li>Người dùng đã đăng nhập sẽ tự động bị đăng xuất khi chế độ bảo trì được kích hoạt.</li>
                        <li>Tất cả các yêu cầu API cũng sẽ bị chặn trong chế độ bảo trì.</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-history mr-2"></i> Lịch Sử Bảo Trì
                </h2>
            </div>
            <div class="admin-card-body p-0">
                <?php
                // Lấy lịch sử bảo trì
                $maintenance_logs_sql = "SELECT action, details, created_at FROM admin_logs WHERE action IN ('enable_maintenance', 'disable_maintenance') ORDER BY created_at DESC LIMIT 5";
                $maintenance_logs_result = db_query($maintenance_logs_sql);
                $maintenance_logs = [];
                
                if (get_config('db.type') === 'postgresql') {
                    while ($row = pg_fetch_assoc($maintenance_logs_result)) {
                        $maintenance_logs[] = $row;
                    }
                } else {
                    while ($row = $maintenance_logs_result->fetch_assoc()) {
                        $maintenance_logs[] = $row;
                    }
                }
                ?>
                
                <?php if (empty($maintenance_logs)): ?>
                    <div class="p-3 text-center text-muted">
                        Chưa có lịch sử bảo trì nào.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($maintenance_logs as $log): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-<?php echo $log['action'] === 'enable_maintenance' ? 'lock' : 'unlock'; ?> mr-2 text-<?php echo $log['action'] === 'enable_maintenance' ? 'warning' : 'success'; ?>"></i>
                                        <?php echo $log['action'] === 'enable_maintenance' ? 'Kích hoạt bảo trì' : 'Tắt bảo trì'; ?>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>