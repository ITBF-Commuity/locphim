<?php
// Ngăn chặn truy cập trực tiếp khi đã cài đặt
if (file_exists('config.json') && !isset($_GET['force'])) {
    header('Location: index.php');
    exit();
}

// Định nghĩa URL trang chủ để có thể include các file khác
define('SITE_URL', 'https://localhost');

// Biến kiểm tra lỗi
$error = '';
$success = '';

// Danh sách các loại cơ sở dữ liệu hỗ trợ
$supported_db_types = array(
    'mysql' => 'MySQL',
    'postgresql' => 'PostgreSQL',
    'sqlite' => 'SQLite',
    'mariadb' => 'MariaDB'
);

// Xử lý khi form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $db_type = isset($_POST['db_type']) ? $_POST['db_type'] : '';
    $db_host = isset($_POST['db_host']) ? $_POST['db_host'] : '';
    $db_name = isset($_POST['db_name']) ? $_POST['db_name'] : '';
    $db_user = isset($_POST['db_user']) ? $_POST['db_user'] : '';
    $db_pass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
    $db_port = isset($_POST['db_port']) ? $_POST['db_port'] : '';
    $db_file = isset($_POST['db_file']) ? $_POST['db_file'] : 'database/sqlite/loc_phim.db';
    $site_url = isset($_POST['site_url']) ? $_POST['site_url'] : 'https://localhost';
    $admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';
    $admin_username = isset($_POST['admin_username']) ? $_POST['admin_username'] : '';
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Kiểm tra dữ liệu đầu vào
    if (!array_key_exists($db_type, $supported_db_types)) {
        $error = 'Loại cơ sở dữ liệu không hợp lệ';
    } elseif ($db_type !== 'sqlite' && (empty($db_host) || empty($db_name) || empty($db_user))) {
        $error = 'Vui lòng điền đầy đủ thông tin cơ sở dữ liệu';
    } elseif (empty($admin_email) || empty($admin_username) || empty($admin_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin tài khoản quản trị';
    } elseif ($admin_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Thiết lập port mặc định nếu không có
        if (empty($db_port)) {
            switch ($db_type) {
                case 'mysql':
                case 'mariadb':
                    $db_port = '3306';
                    break;
                case 'postgresql':
                    $db_port = '5432';
                    break;
                default:
                    $db_port = '';
            }
        }
        
        // Kiểm tra kết nối cơ sở dữ liệu
        try {
            $pdo = null;
            
            switch ($db_type) {
                case 'mysql':
                case 'mariadb':
                    $pdo = new PDO(
                        "mysql:host=$db_host;port=$db_port",
                        $db_user,
                        $db_pass,
                        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                    );
                    break;
                case 'postgresql':
                    $pdo = new PDO(
                        "pgsql:host=$db_host;port=$db_port",
                        $db_user,
                        $db_pass,
                        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                    );
                    break;
                case 'sqlite':
                    // Tạo thư mục cho SQLite nếu chưa tồn tại
                    $db_dir = dirname($db_file);
                    if (!is_dir($db_dir)) {
                        mkdir($db_dir, 0755, true);
                    }
                    
                    $pdo = new PDO(
                        "sqlite:$db_file",
                        null,
                        null,
                        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                    );
                    break;
            }
            
            // Tạo cơ sở dữ liệu nếu chưa tồn tại (trừ SQLite)
            if ($db_type !== 'sqlite') {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
                $pdo->exec("USE `$db_name`");
            }
            
            // Đọc và thực thi file SQL tương ứng
            $sql_file = "database/{$db_type}/loc_phim.sql";
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                
                // PostgreSQL yêu cầu xử lý đặc biệt
                if ($db_type === 'postgresql') {
                    // Kết nối lại với database cụ thể
                    $pdo = new PDO(
                        "pgsql:host=$db_host;dbname=$db_name;port=$db_port",
                        $db_user,
                        $db_pass,
                        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                    );
                    
                    // PostgreSQL yêu cầu phải thực thi từng câu lệnh
                    $statements = explode(';', $sql);
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                        }
                    }
                } else {
                    // MySQL, MariaDB và SQLite có thể thực thi nhiều câu lệnh cùng lúc
                    $pdo->exec($sql);
                }
                
                // Tạo tài khoản admin
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $admin_role_id = 1; // Admin role
                
                // Thêm admin vào cơ sở dữ liệu
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$admin_username, $admin_email, $hashed_password, $admin_role_id, 1]);
                
                // Tạo file config.json
                $config = array(
                    'db_type' => $db_type,
                    'db_host' => $db_host,
                    'db_name' => $db_name,
                    'db_user' => $db_user,
                    'db_pass' => $db_pass,
                    'db_port' => $db_port,
                    'db_file' => $db_file,
                    'site_url' => $site_url,
                    'admin_email' => $admin_email,
                    'debug_mode' => false,
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'session_lifetime' => 86400,
                    'default_theme' => 'light'
                );
                
                file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT));
                
                $success = 'Cài đặt thành công! <a href="index.php">Quay về trang chủ</a>';
            } else {
                $error = "Không tìm thấy file SQL cho loại cơ sở dữ liệu $db_type";
            }
        } catch (PDOException $e) {
            $error = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Lọc Phim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 2.5rem;
            color: #dc3545;
            font-weight: bold;
        }
        .logo span {
            font-size: 1rem;
            color: #6c757d;
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            position: relative;
        }
        .step.active {
            background-color: #dc3545;
            color: white;
        }
        .step.completed {
            background-color: #28a745;
            color: white;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #e9ecef;
            top: 50%;
            left: 100%;
            transform: translateY(-50%);
        }
        .step.completed:not(:last-child):after {
            background-color: #28a745;
        }
        .form-action {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <h1>Lọc Phim</h1>
                <span>Cài đặt hệ thống</span>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <?php else: ?>
            
            <div class="install-header">
                <h2>Thiết lập ban đầu</h2>
                <p>Vui lòng hoàn thành các bước dưới đây để cài đặt Lọc Phim</p>
            </div>
            
            <div class="step-indicator">
                <div class="step active" data-step="1">1</div>
                <div class="step" data-step="2">2</div>
                <div class="step" data-step="3">3</div>
            </div>
            
            <form method="post" action="install.php">
                <div class="form-step active" data-step="1">
                    <h3>Bước 1: Chọn loại cơ sở dữ liệu</h3>
                    <div class="mb-3">
                        <label for="db_type" class="form-label">Loại cơ sở dữ liệu</label>
                        <select class="form-select" id="db_type" name="db_type" required>
                            <?php foreach ($supported_db_types as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-common">
                        <label for="db_host" class="form-label">Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost">
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-common">
                        <label for="db_name" class="form-label">Tên database</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="loc_phim">
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-common">
                        <label for="db_user" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root">
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-common">
                        <label for="db_pass" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-common">
                        <label for="db_port" class="form-label">Port (để trống sẽ dùng port mặc định)</label>
                        <input type="text" class="form-control" id="db_port" name="db_port">
                    </div>
                    
                    <div class="mb-3 db-fields" id="db-fields-sqlite" style="display: none;">
                        <label for="db_file" class="form-label">Đường dẫn file database</label>
                        <input type="text" class="form-control" id="db_file" name="db_file" value="database/sqlite/loc_phim.db">
                    </div>
                    
                    <div class="form-action">
                        <button type="button" class="btn btn-primary next-step">Tiếp theo</button>
                    </div>
                </div>
                
                <div class="form-step" data-step="2">
                    <h3>Bước 2: Cấu hình trang web</h3>
                    
                    <div class="mb-3">
                        <label for="site_url" class="form-label">URL trang web</label>
                        <input type="text" class="form-control" id="site_url" name="site_url" value="https://localhost" required>
                    </div>
                    
                    <div class="form-action">
                        <button type="button" class="btn btn-secondary prev-step">Quay lại</button>
                        <button type="button" class="btn btn-primary next-step">Tiếp theo</button>
                    </div>
                </div>
                
                <div class="form-step" data-step="3">
                    <h3>Bước 3: Tạo tài khoản quản trị</h3>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-action">
                        <button type="button" class="btn btn-secondary prev-step">Quay lại</button>
                        <button type="submit" class="btn btn-success">Hoàn tất cài đặt</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý hiển thị các trường theo loại database
            const dbTypeSelect = document.getElementById('db_type');
            const dbFieldsCommon = document.querySelectorAll('#db-fields-common');
            const dbFieldsSqlite = document.getElementById('db-fields-sqlite');
            
            function toggleDbFields() {
                const selectedType = dbTypeSelect.value;
                
                if (selectedType === 'sqlite') {
                    dbFieldsCommon.forEach(field => field.style.display = 'none');
                    dbFieldsSqlite.style.display = 'block';
                } else {
                    dbFieldsCommon.forEach(field => field.style.display = 'block');
                    dbFieldsSqlite.style.display = 'none';
                }
            }
            
            dbTypeSelect.addEventListener('change', toggleDbFields);
            toggleDbFields();
            
            // Xử lý chuyển bước
            const steps = document.querySelectorAll('.form-step');
            const stepIndicators = document.querySelectorAll('.step');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = parseInt(this.closest('.form-step').dataset.step);
                    const nextStep = currentStep + 1;
                    
                    // Ẩn bước hiện tại
                    document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
                    
                    // Hiển thị bước tiếp theo
                    document.querySelector(`.form-step[data-step="${nextStep}"]`).classList.add('active');
                    
                    // Cập nhật chỉ báo bước
                    document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
                    document.querySelector(`.step[data-step="${nextStep}"]`).classList.add('active');
                });
            });
            
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = parseInt(this.closest('.form-step').dataset.step);
                    const prevStep = currentStep - 1;
                    
                    // Ẩn bước hiện tại
                    document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
                    
                    // Hiển thị bước trước
                    document.querySelector(`.form-step[data-step="${prevStep}"]`).classList.add('active');
                    
                    // Cập nhật chỉ báo bước
                    document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
                    document.querySelector(`.step[data-step="${prevStep}"]`).classList.remove('completed');
                    document.querySelector(`.step[data-step="${prevStep}"]`).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
