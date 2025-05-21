<?php
/**
 * Lọc Phim - Trang cài đặt database
 * 
 * Trang này sẽ tạo các bảng trong database
 */

// Thiết lập các tham số mặc định
$customMetaTitle = 'Cài đặt Database | ' . SITE_NAME;
$customCss = '/assets/css/main.css';
$bodyClass = 'page-install';

// Biến chứa thông báo
$message = '';
$success = false;

// Xử lý khi người dùng gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Đọc file schema SQL
        if ($dbType === 'postgresql') {
            $sqlFile = DATABASE_PATH . '/schema_postgresql.sql';
        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
            $sqlFile = DATABASE_PATH . '/schema.sql';
        } elseif ($dbType === 'sqlite') {
            $sqlFile = DATABASE_PATH . '/schema_sqlite.sql';
        } else {
            throw new Exception('Loại database không được hỗ trợ: ' . $dbType);
        }

        // Kiểm tra file SQL tồn tại
        if (!file_exists($sqlFile)) {
            throw new Exception('File schema không tồn tại: ' . $sqlFile);
        }

        // Đọc nội dung file SQL
        $sql = file_get_contents($sqlFile);

        // Thực thi các câu lệnh SQL
        $statements = explode(';', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (!empty($statement)) {
                try {
                    $db->execute($statement);
                } catch (Exception $e) {
                    // Ghi log lỗi nhưng vẫn tiếp tục
                    error_log('Lỗi SQL: ' . $e->getMessage() . ' - Query: ' . $statement);
                }
            }
        }

        // Tạo file lock để đánh dấu đã cài đặt
        file_put_contents(BASE_PATH . '/install.lock', time());

        $message = 'Cài đặt database thành công!';
        $success = true;
    } catch (Exception $e) {
        $message = 'Lỗi cài đặt database: ' . $e->getMessage();
    }
}

// Kiểm tra trạng thái cài đặt
$installed = false;
try {
    if ($dbType === 'postgresql') {
        $result = $db->get("SELECT to_regclass('public.users') IS NOT NULL AS exists");
        $installed = $result && $result['exists'];
    } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
        $result = $db->get("SHOW TABLES LIKE 'users'");
        $installed = !empty($result);
    } elseif ($dbType === 'sqlite') {
        $result = $db->get("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $installed = !empty($result);
    }
} catch (Exception $e) {
    // Không làm gì, mặc định là chưa cài đặt
}

// Load header
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm my-5">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3">Cài đặt Database</h1>
                        <p class="text-muted">Trang này sẽ giúp bạn cài đặt cơ sở dữ liệu cho website</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> mb-4">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>Thông tin database hiện tại:</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Loại database:
                                <span><?php echo ucfirst($dbType); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Trạng thái:
                                <span class="badge <?php echo $installed ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $installed ? 'Đã cài đặt' : 'Chưa cài đặt'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Database host:
                                <span><?php echo $dbConfig['host'] ?? 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Database name:
                                <span><?php echo $dbConfig['database'] ?? 'N/A'; ?></span>
                            </li>
                        </ul>
                    </div>

                    <form method="post">
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm-install" name="confirm" required>
                                <label class="form-check-label" for="confirm-install">
                                    Tôi hiểu rằng quá trình này sẽ tạo lại tất cả các bảng trong database. Dữ liệu hiện tại sẽ bị mất.
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="install" class="btn btn-primary" <?php echo $installed && !$success ? 'disabled' : ''; ?>>
                                <?php echo $installed && !$success ? 'Đã cài đặt' : 'Cài đặt database'; ?>
                            </button>
                            <a href="/" class="btn btn-outline-secondary">Quay lại trang chủ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>