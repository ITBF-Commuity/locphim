<?php
/**
 * Lọc Phim - Trang cài đặt
 */

// Kiểm tra xem đã cài đặt chưa
if (file_exists('install.lock')) {
    die('Hệ thống đã được cài đặt. Vui lòng xóa file install.lock để cài đặt lại.');
}

// Kiểm tra các extension cần thiết
$required_extensions = [
    'pdo', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'gd', 'mbstring', 'xml', 'json'
];

$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

// Kiểm tra quyền thư mục
$writable_dirs = [
    '.', 'uploads', 'uploads/posters', 'uploads/backdrops', 'uploads/avatars'
];

$not_writable = [];
foreach ($writable_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    if (!is_writable($dir)) {
        $not_writable[] = $dir;
    }
}

// Cấu hình database mặc định
$db_configs = [
    'mysql' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'locphim',
        'user' => 'root',
        'pass' => ''
    ],
    'pgsql' => [
        'type' => 'pgsql',
        'host' => isset($_ENV['PGHOST']) ? $_ENV['PGHOST'] : 'localhost',
        'port' => isset($_ENV['PGPORT']) ? $_ENV['PGPORT'] : '5432',
        'name' => isset($_ENV['PGDATABASE']) ? $_ENV['PGDATABASE'] : 'locphim',
        'user' => isset($_ENV['PGUSER']) ? $_ENV['PGUSER'] : 'postgres',
        'pass' => isset($_ENV['PGPASSWORD']) ? $_ENV['PGPASSWORD'] : ''
    ],
    'sqlite' => [
        'type' => 'sqlite',
        'name' => 'database/database.sqlite'
    ]
];

// Tự động chọn database type dựa trên các extension đã cài đặt
$db_type = 'pgsql'; // mặc định
if (!extension_loaded('pdo_pgsql')) {
    if (extension_loaded('pdo_mysql')) {
        $db_type = 'mysql';
    } elseif (extension_loaded('pdo_sqlite')) {
        $db_type = 'sqlite';
    }
}

// Xử lý form submit
$error = '';
$success = '';
$db_test_result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_db'])) {
        // Kiểm tra kết nối database
        $db_type = $_POST['db_type'];
        $db_config = $db_configs[$db_type];
        
        // Cập nhật thông tin kết nối từ form
        if ($db_type !== 'sqlite') {
            $db_config['host'] = $_POST['db_host'];
            $db_config['port'] = $_POST['db_port'];
            $db_config['name'] = $_POST['db_name'];
            $db_config['user'] = $_POST['db_user'];
            $db_config['pass'] = $_POST['db_pass'];
        } else {
            $db_config['name'] = $_POST['db_name_sqlite'];
        }
        
        // Kiểm tra kết nối
        try {
            $dsn = '';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            switch ($db_type) {
                case 'mysql':
                    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']};charset=utf8mb4";
                    break;
                case 'pgsql':
                    $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']}";
                    break;
                case 'sqlite':
                    $sqlite_path = $db_config['name'];
                    // Tạo thư mục nếu chưa tồn tại
                    $sqlite_dir = dirname($sqlite_path);
                    if (!is_dir($sqlite_dir)) {
                        @mkdir($sqlite_dir, 0755, true);
                    }
                    $dsn = "sqlite:{$sqlite_path}";
                    break;
            }
            
            if ($db_type !== 'sqlite') {
                $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
            } else {
                $pdo = new PDO($dsn, null, null, $options);
            }
            
            $db_test_result = 'success';
        } catch (PDOException $e) {
            $db_test_result = 'error';
            $error = 'Lỗi kết nối database: ' . $e->getMessage();
        }
    } elseif (isset($_POST['install'])) {
        // Lưu cấu hình database vào config.php
        $db_type = $_POST['db_type'];
        $db_config = $db_configs[$db_type];
        
        // Cập nhật thông tin kết nối từ form
        if ($db_type !== 'sqlite') {
            $db_config['host'] = $_POST['db_host'];
            $db_config['port'] = $_POST['db_port'];
            $db_config['name'] = $_POST['db_name'];
            $db_config['user'] = $_POST['db_user'];
            $db_config['pass'] = $_POST['db_pass'];
        } else {
            $db_config['name'] = $_POST['db_name_sqlite'];
        }
        
        // Tạo file config.php
        $config_content = file_get_contents('config.php');
        
        // Thay đổi cấu hình database
        $config_content = preg_replace('/define\(\'DB_TYPE\',\s*\'[^\']*\'\);/', "define('DB_TYPE', '{$db_type}');", $config_content);
        
        if ($db_type !== 'sqlite') {
            $config_content = preg_replace('/define\(\'DB_HOST\',\s*[^;]*;/', "define('DB_HOST', '{$db_config['host']}');", $config_content);
            $config_content = preg_replace('/define\(\'DB_PORT\',\s*[^;]*;/', "define('DB_PORT', '{$db_config['port']}');", $config_content);
            $config_content = preg_replace('/define\(\'DB_NAME\',\s*[^;]*;/', "define('DB_NAME', '{$db_config['name']}');", $config_content);
            $config_content = preg_replace('/define\(\'DB_USER\',\s*[^;]*;/', "define('DB_USER', '{$db_config['user']}');", $config_content);
            $config_content = preg_replace('/define\(\'DB_PASS\',\s*[^;]*;/', "define('DB_PASS', '{$db_config['pass']}');", $config_content);
        } else {
            $config_content = preg_replace('/define\(\'DB_NAME\',\s*[^;]*;/', "define('DB_NAME', '{$db_config['name']}');", $config_content);
        }
        
        // Lưu file config
        file_put_contents('config.php', $config_content);
        
        // Lưu thông tin admin
        $admin_username = trim($_POST['admin_username']);
        $admin_password = trim($_POST['admin_password']);
        $admin_email = trim($_POST['admin_email']);
        
        if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
            $error = 'Vui lòng nhập đầy đủ thông tin tài khoản admin.';
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            // Cài đặt database
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const form = document.createElement("form");
                    form.method = "POST";
                    form.action = "install-db.php";
                    
                    const input1 = document.createElement("input");
                    input1.type = "hidden";
                    input1.name = "install";
                    input1.value = "true";
                    form.appendChild(input1);
                    
                    const input2 = document.createElement("input");
                    input2.type = "hidden";
                    input2.name = "admin_username";
                    input2.value = "' . htmlspecialchars($admin_username) . '";
                    form.appendChild(input2);
                    
                    const input3 = document.createElement("input");
                    input3.type = "hidden";
                    input3.name = "admin_password";
                    input3.value = "' . htmlspecialchars($admin_password) . '";
                    form.appendChild(input3);
                    
                    const input4 = document.createElement("input");
                    input4.type = "hidden";
                    input4.name = "admin_email";
                    input4.value = "' . htmlspecialchars($admin_email) . '";
                    form.appendChild(input4);
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            </script>';
            
            $success = 'Đang tiến hành cài đặt hệ thống...';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Lọc Phim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #14b8a6;
            --primary-hover: #0d9488;
            --secondary-color: #f97316;
            --secondary-hover: #ea580c;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --bg-color: #ffffff;
            --bg-light: #f8fafc;
            --bg-dark: #020617;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --header-bg: #ffffff;
            --footer-bg: #f1f5f9;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            color: var(--text-color);
            background-color: var(--bg-light);
            line-height: 1.5;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 5%;
            right: 5%;
            height: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--bg-color);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
            color: var(--text-muted);
        }
        
        .step.active .step-number {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .step.active .step-label {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .step.completed .step-label {
            color: var(--success-color);
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        
        .requirements {
            margin-bottom: 1.5rem;
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .requirement-item:last-child {
            border-bottom: none;
        }
        
        .requirement-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirement-status {
            font-weight: bold;
        }
        
        .status-success {
            color: var(--success-color);
        }
        
        .status-error {
            color: var(--danger-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        
        .text-center {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cài đặt Lọc Phim</h1>
            <p>Thực hiện cài đặt hệ thống xem phim trực tuyến.</p>
        </div>
        
        <div class="steps">
            <div class="step <?php echo ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['test_db'])) ? 'active' : 'completed'; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Kiểm tra hệ thống</div>
            </div>
            <div class="step <?php echo (isset($_POST['install']) || $db_test_result === 'success') ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Cấu hình hệ thống</div>
            </div>
            <div class="step <?php echo $success ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Hoàn tất</div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="card">
                <h2 class="card-title">Đang cài đặt...</h2>
                <p>Hệ thống đang tiến hành cài đặt cơ sở dữ liệu và tạo tài khoản admin.</p>
                <p>Vui lòng đợi trong giây lát...</p>
                <div class="text-center" style="margin-top: 2rem;">
                    <div style="width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary-color); border-radius: 50%; margin: 0 auto; animation: spin 1s linear infinite;"></div>
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = './';
                }, 5000);
            </script>
        <?php else: ?>
            <form method="POST" action="">
                <div class="card">
                    <h2 class="card-title">Kiểm tra yêu cầu hệ thống</h2>
                    
                    <div class="requirements">
                        <?php if (!empty($missing_extensions)): ?>
                            <div class="alert alert-danger">
                                <p><strong>Lưu ý:</strong> Thiếu một số extension PHP cần thiết. Vui lòng cài đặt các extension sau đây để tiếp tục:</p>
                                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                                    <?php foreach ($missing_extensions as $ext): ?>
                                        <li><?php echo $ext; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="requirement-item">
                            <div class="requirement-name">
                                <i class="fas fa-code"></i>
                                <span>PHP Version (>= 8.0)</span>
                            </div>
                            <div class="requirement-status <?php echo version_compare(PHP_VERSION, '8.0.0') >= 0 ? 'status-success' : 'status-error'; ?>">
                                <?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '8.0.0') >= 0 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>'; ?>
                            </div>
                        </div>
                        
                        <?php foreach ($required_extensions as $ext): ?>
                            <div class="requirement-item">
                                <div class="requirement-name">
                                    <i class="fas fa-puzzle-piece"></i>
                                    <span>PHP Extension: <?php echo $ext; ?></span>
                                </div>
                                <div class="requirement-status <?php echo extension_loaded($ext) ? 'status-success' : 'status-error'; ?>">
                                    <?php echo extension_loaded($ext) ? 'Đã cài đặt <i class="fas fa-check"></i>' : 'Chưa cài đặt <i class="fas fa-times"></i>'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($writable_dirs as $dir): ?>
                            <div class="requirement-item">
                                <div class="requirement-name">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo $dir === '.' ? 'Thư mục gốc' : 'Thư mục: ' . $dir; ?> (Có quyền ghi)</span>
                                </div>
                                <div class="requirement-status <?php echo is_writable($dir) ? 'status-success' : 'status-error'; ?>">
                                    <?php echo is_writable($dir) ? 'Có quyền ghi <i class="fas fa-check"></i>' : 'Không có quyền ghi <i class="fas fa-times"></i>'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h2 class="card-title">Cấu hình cơ sở dữ liệu</h2>
                    
                    <?php if ($db_test_result === 'success'): ?>
                        <div class="alert alert-success">
                            <p><i class="fas fa-check-circle"></i> Kết nối đến cơ sở dữ liệu thành công!</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="db_type">Loại cơ sở dữ liệu</label>
                        <select name="db_type" id="db_type" class="form-control" required>
                            <option value="pgsql" <?php echo $db_type === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
                            <option value="mysql" <?php echo $db_type === 'mysql' ? 'selected' : ''; ?>>MySQL</option>
                            <option value="sqlite" <?php echo $db_type === 'sqlite' ? 'selected' : ''; ?>>SQLite</option>
                        </select>
                    </div>
                    
                    <div id="mysql_pgsql_config" style="<?php echo $db_type === 'sqlite' ? 'display: none;' : ''; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_host">Host</label>
                                <input type="text" name="db_host" id="db_host" class="form-control" value="<?php echo $db_configs[$db_type]['host']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="db_port">Port</label>
                                <input type="text" name="db_port" id="db_port" class="form-control" value="<?php echo $db_configs[$db_type]['port']; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="db_name">Tên cơ sở dữ liệu</label>
                            <input type="text" name="db_name" id="db_name" class="form-control" value="<?php echo $db_configs[$db_type]['name']; ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_user">Tên đăng nhập</label>
                                <input type="text" name="db_user" id="db_user" class="form-control" value="<?php echo $db_configs[$db_type]['user']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="db_pass">Mật khẩu</label>
                                <input type="password" name="db_pass" id="db_pass" class="form-control" value="<?php echo $db_configs[$db_type]['pass']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div id="sqlite_config" style="<?php echo $db_type !== 'sqlite' ? 'display: none;' : ''; ?>">
                        <div class="form-group">
                            <label for="db_name_sqlite">Đường dẫn file SQLite</label>
                            <input type="text" name="db_name_sqlite" id="db_name_sqlite" class="form-control" value="<?php echo $db_configs['sqlite']['name']; ?>" required>
                            <div class="form-help">Đường dẫn tương đối đến file SQLite, ví dụ: database/database.sqlite</div>
                        </div>
                    </div>
                    
                    <div class="text-center" style="margin-top: 1.5rem;">
                        <button type="submit" name="test_db" class="btn btn-primary">Kiểm tra kết nối</button>
                    </div>
                </div>
                
                <?php if ($db_test_result === 'success'): ?>
                    <div class="card">
                        <h2 class="card-title">Thông tin tài khoản admin</h2>
                        
                        <div class="form-group">
                            <label for="admin_username">Tên đăng nhập</label>
                            <input type="text" name="admin_username" id="admin_username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Email</label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Mật khẩu</label>
                            <input type="password" name="admin_password" id="admin_password" class="form-control" required>
                        </div>
                        
                        <div class="text-center" style="margin-top: 1.5rem;">
                            <button type="submit" name="install" class="btn btn-secondary">Cài đặt hệ thống</button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const dbTypeSelect = document.getElementById('db_type');
                    const mysqlPgsqlConfig = document.getElementById('mysql_pgsql_config');
                    const sqliteConfig = document.getElementById('sqlite_config');
                    
                    dbTypeSelect.addEventListener('change', function() {
                        const dbType = this.value;
                        
                        if (dbType === 'sqlite') {
                            mysqlPgsqlConfig.style.display = 'none';
                            sqliteConfig.style.display = 'block';
                        } else {
                            mysqlPgsqlConfig.style.display = 'block';
                            sqliteConfig.style.display = 'none';
                            
                            // Update placeholder values based on the selected database type
                            const dbHost = document.getElementById('db_host');
                            const dbPort = document.getElementById('db_port');
                            const dbName = document.getElementById('db_name');
                            const dbUser = document.getElementById('db_user');
                            const dbPass = document.getElementById('db_pass');
                            
                            if (dbType === 'mysql') {
                                dbHost.value = '<?php echo $db_configs['mysql']['host']; ?>';
                                dbPort.value = '<?php echo $db_configs['mysql']['port']; ?>';
                                dbName.value = '<?php echo $db_configs['mysql']['name']; ?>';
                                dbUser.value = '<?php echo $db_configs['mysql']['user']; ?>';
                                dbPass.value = '<?php echo $db_configs['mysql']['pass']; ?>';
                            } else if (dbType === 'pgsql') {
                                dbHost.value = '<?php echo $db_configs['pgsql']['host']; ?>';
                                dbPort.value = '<?php echo $db_configs['pgsql']['port']; ?>';
                                dbName.value = '<?php echo $db_configs['pgsql']['name']; ?>';
                                dbUser.value = '<?php echo $db_configs['pgsql']['user']; ?>';
                                dbPass.value = '<?php echo $db_configs['pgsql']['pass']; ?>';
                            }
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>