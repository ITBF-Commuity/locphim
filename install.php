<?php
// Tập tin cài đặt hệ thống Lọc Phim
session_start();

// Kiểm tra đã cài đặt chưa
$config_file = 'config.php';
$installed = file_exists($config_file) && filesize($config_file) > 0;

// Các bước cài đặt
$steps = [
    1 => 'Yêu cầu hệ thống',
    2 => 'Cấu hình cơ sở dữ liệu',
    3 => 'Cấu hình trang web',
    4 => 'Tạo tài khoản Admin',
    5 => 'Hoàn tất cài đặt'
];

// Xác định bước hiện tại
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > count($steps)) {
    $current_step = 1;
}

// Kiểm tra nếu đã cài đặt và không phải đang cố tình cài đặt lại
if ($installed && !isset($_GET['reinstall'])) {
    $current_step = 5;
}

// Xử lý biểu mẫu gửi đi
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case 2:
            // Xử lý cấu hình cơ sở dữ liệu
            $db_type = $_POST['db_type'] ?? '';
            $db_host = $_POST['db_host'] ?? '';
            $db_port = $_POST['db_port'] ?? '';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_password = $_POST['db_password'] ?? '';
            
            // Kiểm tra dữ liệu
            if (empty($db_type) || empty($db_host) || empty($db_name) || empty($db_user)) {
                $error_message = 'Vui lòng điền đầy đủ thông tin cơ sở dữ liệu.';
            } else {
                // Kiểm tra kết nối cơ sở dữ liệu
                $conn = null;
                $conn_error = '';
                
                try {
                    if ($db_type === 'mysql') {
                        // Kết nối MySQL
                        $conn = new mysqli($db_host, $db_user, $db_password, '', $db_port);
                        
                        if ($conn->connect_error) {
                            $conn_error = 'Không thể kết nối đến MySQL: ' . $conn->connect_error;
                        } else {
                            // Kiểm tra và tạo cơ sở dữ liệu nếu chưa tồn tại
                            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                                $conn_error = 'Không thể tạo cơ sở dữ liệu: ' . $conn->error;
                            } else {
                                // Chọn cơ sở dữ liệu
                                $conn->select_db($db_name);
                            }
                        }
                    } else {
                        // Kết nối PostgreSQL
                        $pg_conn_string = "host=$db_host port=$db_port user=$db_user password=$db_password";
                        $conn = pg_connect($pg_conn_string);
                        
                        if (!$conn) {
                            $conn_error = 'Không thể kết nối đến PostgreSQL: ' . pg_last_error();
                        } else {
                            // Kiểm tra cơ sở dữ liệu tồn tại chưa
                            $db_exists = false;
                            $check_db_result = pg_query($conn, "SELECT 1 FROM pg_database WHERE datname = '$db_name'");
                            if ($check_db_result) {
                                $db_exists = pg_num_rows($check_db_result) > 0;
                            }
                            
                            if (!$db_exists) {
                                // Tạo cơ sở dữ liệu mới
                                if (!pg_query($conn, "CREATE DATABASE $db_name")) {
                                    $conn_error = 'Không thể tạo cơ sở dữ liệu: ' . pg_last_error($conn);
                                }
                            }
                            
                            // Đóng kết nối và kết nối lại với cơ sở dữ liệu mới
                            pg_close($conn);
                            $pg_conn_string .= " dbname=$db_name";
                            $conn = pg_connect($pg_conn_string);
                            
                            if (!$conn) {
                                $conn_error = 'Không thể kết nối đến cơ sở dữ liệu PostgreSQL: ' . pg_last_error();
                            }
                        }
                    }
                } catch (Exception $e) {
                    $conn_error = 'Lỗi kết nối: ' . $e->getMessage();
                }
                
                if (!empty($conn_error)) {
                    $error_message = $conn_error;
                } else {
                    // Lưu thông tin cấu hình vào session
                    $_SESSION['install_db'] = [
                        'type' => $db_type,
                        'host' => $db_host,
                        'port' => $db_port,
                        'name' => $db_name,
                        'user' => $db_user,
                        'password' => $db_password
                    ];
                    
                    // Tiến hành cài đặt cơ sở dữ liệu
                    $install_result = install_database($conn, $db_type, $db_name);
                    
                    if ($install_result['success']) {
                        $success_message = 'Kết nối cơ sở dữ liệu thành công và đã cài đặt cấu trúc bảng.';
                        
                        // Chuyển đến bước tiếp theo
                        header('Location: install.php?step=3');
                        exit;
                    } else {
                        $error_message = 'Lỗi khi cài đặt cơ sở dữ liệu: ' . $install_result['message'];
                    }
                }
            }
            break;
            
        case 3:
            // Xử lý cấu hình trang web
            $site_url = $_POST['site_url'] ?? '';
            $site_name = $_POST['site_name'] ?? '';
            $site_description = $_POST['site_description'] ?? '';
            
            if (empty($site_url) || empty($site_name)) {
                $error_message = 'Vui lòng điền đầy đủ thông tin trang web.';
            } else {
                // Lưu thông tin cấu hình vào session
                $_SESSION['install_site'] = [
                    'url' => $site_url,
                    'name' => $site_name,
                    'description' => $site_description
                ];
                
                // Chuyển đến bước tiếp theo
                header('Location: install.php?step=4');
                exit;
            }
            break;
            
        case 4:
            // Xử lý tạo tài khoản admin
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
            
            if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
                $error_message = 'Vui lòng điền đầy đủ thông tin tài khoản admin.';
            } elseif ($admin_password !== $admin_password_confirm) {
                $error_message = 'Mật khẩu xác nhận không khớp.';
            } elseif (strlen($admin_password) < 8) {
                $error_message = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } else {
                // Lưu thông tin admin vào session
                $_SESSION['install_admin'] = [
                    'username' => $admin_username,
                    'email' => $admin_email,
                    'password' => $admin_password
                ];
                
                // Tạo file cấu hình
                $config_result = create_config_file();
                
                if ($config_result['success']) {
                    // Tạo tài khoản admin
                    $admin_result = create_admin_account();
                    
                    if ($admin_result['success']) {
                        $success_message = 'Tạo tài khoản admin thành công.';
                        
                        // Chuyển đến bước hoàn tất
                        header('Location: install.php?step=5');
                        exit;
                    } else {
                        $error_message = 'Lỗi khi tạo tài khoản admin: ' . $admin_result['message'];
                    }
                } else {
                    $error_message = 'Lỗi khi tạo file cấu hình: ' . $config_result['message'];
                }
            }
            break;
    }
}

// Hàm kiểm tra yêu cầu hệ thống
function check_system_requirements() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP Version',
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'mysql' => [
            'name' => 'MySQL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('mysqli') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('mysqli')
        ],
        'postgresql' => [
            'name' => 'PostgreSQL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('pgsql') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pgsql')
        ],
        'pdo' => [
            'name' => 'PDO Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo')
        ],
        'json' => [
            'name' => 'JSON Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('json')
        ],
        'curl' => [
            'name' => 'cURL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('curl')
        ],
        'gd' => [
            'name' => 'GD Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('gd')
        ],
        'file_permissions' => [
            'name' => 'File Permissions',
            'required' => 'Writable',
            'current' => is_writable('./') ? 'Writable' : 'Not Writable',
            'status' => is_writable('./')
        ]
    ];
    
    $all_passed = true;
    foreach ($requirements as $req) {
        if (!$req['status']) {
            $all_passed = false;
            break;
        }
    }
    
    return [
        'requirements' => $requirements,
        'passed' => $all_passed
    ];
}

// Hàm cài đặt cơ sở dữ liệu
function install_database($conn, $db_type, $db_name) {
    try {
        if ($db_type === 'mysql') {
            // Đọc file SQL cho MySQL
            $sql_file = file_get_contents('database/mysql_schema.sql');
            
            // Chia các câu lệnh SQL
            $sql_commands = explode(';', $sql_file);
            
            // Thực thi từng câu lệnh
            foreach ($sql_commands as $sql) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    if (!$conn->query($sql)) {
                        return [
                            'success' => false,
                            'message' => 'Lỗi SQL: ' . $conn->error . ' trong câu lệnh: ' . $sql
                        ];
                    }
                }
            }
        } else {
            // Đọc file SQL cho PostgreSQL
            $sql_file = file_get_contents('database/postgresql_schema.sql');
            
            // Chia các câu lệnh SQL
            $sql_commands = explode(';', $sql_file);
            
            // Thực thi từng câu lệnh
            foreach ($sql_commands as $sql) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    if (!pg_query($conn, $sql)) {
                        return [
                            'success' => false,
                            'message' => 'Lỗi SQL: ' . pg_last_error($conn) . ' trong câu lệnh: ' . $sql
                        ];
                    }
                }
            }
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

// Hàm tạo file cấu hình
function create_config_file() {
    try {
        if (!isset($_SESSION['install_db']) || !isset($_SESSION['install_site'])) {
            return [
                'success' => false,
                'message' => 'Thiếu thông tin cấu hình.'
            ];
        }
        
        $db = $_SESSION['install_db'];
        $site = $_SESSION['install_site'];
        
        // Tạo nội dung file cấu hình
        $config_content = "<?php
// File cấu hình Lọc Phim
// Tạo tự động bởi trình cài đặt - " . date('Y-m-d H:i:s') . "

// Cấu hình cơ sở dữ liệu
\$config = [
    'db' => [
        'type' => '" . $db['type'] . "',
        'host' => '" . $db['host'] . "',
        'port' => '" . $db['port'] . "',
        'name' => '" . $db['name'] . "',
        'user' => '" . $db['user'] . "',
        'password' => '" . $db['password'] . "'
    ],
    'site' => [
        'url' => '" . $site['url'] . "',
        'name' => '" . $site['name'] . "',
        'description' => '" . $site['description'] . "',
        'admin_email' => '" . ($_SESSION['install_admin']['email'] ?? '') . "',
        'admin_api_key' => '" . bin2hex(random_bytes(16)) . "'
    ],
    'vip' => [
        'levels' => [
            1 => [
                'name' => 'VIP Cơ bản',
                'price' => 50000,
                'duration' => 30,
                'resolution' => '720p',
                'ads' => true
            ],
            2 => [
                'name' => 'VIP Pro',
                'price' => 100000,
                'duration' => 30,
                'resolution' => '1080p',
                'ads' => false
            ],
            3 => [
                'name' => 'VIP Premium',
                'price' => 200000,
                'duration' => 30,
                'resolution' => '4K',
                'ads' => false
            ]
        ]
    ],
    'vnpay' => [
        'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'return_url' => '" . $site['url'] . "/vip.php',
        'merchant_id' => '',
        'secure_hash' => ''
    ],
    'momo' => [
        'endpoint' => 'https://test-payment.momo.vn/gw_payment/transactionProcessor',
        'return_url' => '" . $site['url'] . "/vip.php',
        'partner_code' => '',
        'access_key' => '',
        'secret_key' => ''
    ]
];

// Hàm lấy cấu hình
function get_config(\$key = null) {
    global \$config;
    
    if (\$key === null) {
        return \$config;
    }
    
    \$keys = explode('.', \$key);
    \$value = \$config;
    
    foreach (\$keys as \$k) {
        if (!isset(\$value[\$k])) {
            return null;
        }
        \$value = \$value[\$k];
    }
    
    return \$value;
}

// Kết nối cơ sở dữ liệu
function db_connect() {
    \$db_type = get_config('db.type');
    \$db_host = get_config('db.host');
    \$db_port = get_config('db.port');
    \$db_name = get_config('db.name');
    \$db_user = get_config('db.user');
    \$db_password = get_config('db.password');
    
    if (\$db_type === 'mysql') {
        // Kết nối MySQL
        \$conn = new mysqli(\$db_host, \$db_user, \$db_password, \$db_name, \$db_port);
        
        if (\$conn->connect_error) {
            die('Kết nối cơ sở dữ liệu thất bại: ' . \$conn->connect_error);
        }
        
        // Thiết lập UTF-8
        \$conn->set_charset('utf8mb4');
        
        return \$conn;
    } else {
        // Kết nối PostgreSQL
        \$conn_string = \"host={\$db_host} port={\$db_port} dbname={\$db_name} user={\$db_user} password={\$db_password}\";
        \$conn = pg_connect(\$conn_string);
        
        if (!\$conn) {
            die('Kết nối cơ sở dữ liệu thất bại: ' . pg_last_error());
        }
        
        return \$conn;
    }
}

// Hàm truy vấn cơ sở dữ liệu
function db_query(\$sql, \$params = [], \$fetch_all = true) {
    \$db_type = get_config('db.type');
    \$conn = db_connect();
    
    if (\$db_type === 'mysql') {
        // MySQL
        \$stmt = \$conn->prepare(\$sql);
        
        if (!\$stmt) {
            return [
                'success' => false,
                'error' => \$conn->error,
                'affected_rows' => 0
            ];
        }
        
        if (!empty(\$params)) {
            // Tạo các tham số cho bind_param
            \$types = '';
            \$bind_params = [];
            
            foreach (\$params as \$param) {
                if (is_int(\$param)) {
                    \$types .= 'i';
                } elseif (is_float(\$param)) {
                    \$types .= 'd';
                } elseif (is_string(\$param)) {
                    \$types .= 's';
                } else {
                    \$types .= 's';
                    \$param = (string)\$param;
                }
                
                \$bind_params[] = \$param;
            }
            
            // Tương đương với $stmt->bind_param('ssi', $param1, $param2, $param3)
            \$stmt->bind_param(\$types, ...\$bind_params);
        }
        
        \$stmt->execute();
        
        if (\$fetch_all) {
            \$result = \$stmt->get_result();
            \$data = [];
            
            while (\$row = \$result->fetch_assoc()) {
                \$data[] = \$row;
            }
            
            \$stmt->close();
            \$conn->close();
            
            return \$data;
        } else {
            \$result = \$stmt->get_result();
            \$stmt->close();
            
            // Khi sử dụng cho câu lệnh INSERT, UPDATE, DELETE
            \$affected_rows = \$conn->affected_rows;
            \$insert_id = \$conn->insert_id;
            
            \$conn->close();
            
            return [
                'result' => \$result,
                'affected_rows' => \$affected_rows,
                'insert_id' => \$insert_id
            ];
        }
    } else {
        // PostgreSQL
        // Xử lý tham số cho truy vấn PostgreSQL
        if (!empty(\$params)) {
            \$i = 1;
            foreach (\$params as \$param) {
                \$placeholder = is_int(\$param) ? \$i : '\\$' . \$i;
                
                // Tránh SQL injection bằng cách escape giá trị
                if (is_string(\$param)) {
                    \$param = pg_escape_string(\$conn, \$param);
                    \$param = \"'{\$param}'\";
                } elseif (is_null(\$param)) {
                    \$param = 'NULL';
                }
                
                \$sql = preg_replace('/\\?/', \$param, \$sql, 1);
                \$i++;
            }
        }
        
        \$result = pg_query(\$conn, \$sql);
        
        if (!\$result) {
            return [
                'success' => false,
                'error' => pg_last_error(\$conn),
                'affected_rows' => 0
            ];
        }
        
        if (\$fetch_all) {
            \$data = [];
            while (\$row = pg_fetch_assoc(\$result)) {
                \$data[] = \$row;
            }
            
            pg_free_result(\$result);
            pg_close(\$conn);
            
            return \$data;
        } else {
            // Khi sử dụng cho câu lệnh INSERT, UPDATE, DELETE
            \$affected_rows = pg_affected_rows(\$result);
            
            // Lấy ID vừa chèn
            \$insert_id = null;
            
            if (strpos(strtolower(\$sql), 'insert') === 0) {
                // Trích xuất tên bảng từ câu lệnh INSERT
                preg_match('/insert\\s+into\\s+([^\\s\\(]+)/i', \$sql, \$matches);
                if (isset(\$matches[1])) {
                    \$table_name = trim(\$matches[1]);
                    \$seq_name = \$table_name . '_id_seq';
                    \$id_result = pg_query(\$conn, \"SELECT CURRVAL('{\$seq_name}')\");
                    
                    if (\$id_result) {
                        \$id_row = pg_fetch_row(\$id_result);
                        \$insert_id = \$id_row[0];
                        pg_free_result(\$id_result);
                    }
                }
            }
            
            pg_free_result(\$result);
            pg_close(\$conn);
            
            return [
                'result' => \$result,
                'affected_rows' => \$affected_rows,
                'insert_id' => \$insert_id
            ];
        }
    }
}

// Hàm điều hướng trang
function redirect(\$url) {
    header('Location: ' . \$url);
    exit;
}

// Hàm thiết lập flash message
function set_flash_message(\$type, \$message) {
    if (!isset(\$_SESSION)) {
        session_start();
    }
    
    \$_SESSION['flash_message'] = [
        'type' => \$type,
        'message' => \$message
    ];
}

// Hàm lấy flash message
function get_flash_message() {
    if (!isset(\$_SESSION)) {
        session_start();
    }
    
    if (isset(\$_SESSION['flash_message'])) {
        \$flash = \$_SESSION['flash_message'];
        unset(\$_SESSION['flash_message']);
        return \$flash;
    }
    
    return null;
}

// Hàm lấy URL hiện tại
function get_current_url() {
    \$protocol = isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    \$host = \$_SERVER['HTTP_HOST'];
    \$uri = \$_SERVER['REQUEST_URI'];
    
    return \$protocol . '://' . \$host . \$uri;
}
";
        
        // Ghi file cấu hình
        if (file_put_contents('config.php', $config_content) === false) {
            return [
                'success' => false,
                'message' => 'Không thể ghi file cấu hình.'
            ];
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

// Hàm tạo tài khoản admin
function create_admin_account() {
    try {
        if (!isset($_SESSION['install_admin'])) {
            return [
                'success' => false,
                'message' => 'Thiếu thông tin tài khoản admin.'
            ];
        }
        
        $admin = $_SESSION['install_admin'];
        
        // Tạo tài khoản admin trong cơ sở dữ liệu
        require_once 'config.php';
        
        // Chuẩn bị dữ liệu
        $username = $admin['username'];
        $email = $admin['email'];
        $password = password_hash($admin['password'], PASSWORD_DEFAULT);
        $role = 'admin';
        $status = 'active';
        
        // Kiểm tra user đã tồn tại chưa
        $db_type = get_config('db.type');
        $conn = db_connect();
        
        if ($db_type === 'mysql') {
            $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc email đã tồn tại.'
                ];
            }
            
            // Thêm tài khoản admin
            $insert_sql = "INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('sssss', $username, $email, $password, $role, $status);
            $stmt->execute();
            
            if ($stmt->affected_rows <= 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể tạo tài khoản admin: ' . $conn->error
                ];
            }
        } else {
            // PostgreSQL
            $check_sql = "SELECT id FROM users WHERE username = $1 OR email = $2";
            $check_result = pg_query_params($conn, $check_sql, [$username, $email]);
            
            if (pg_num_rows($check_result) > 0) {
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc email đã tồn tại.'
                ];
            }
            
            // Thêm tài khoản admin
            $insert_sql = "INSERT INTO users (username, email, password, role, status, created_at) VALUES ($1, $2, $3, $4, $5, NOW()) RETURNING id";
            $insert_result = pg_query_params($conn, $insert_sql, [$username, $email, $password, $role, $status]);
            
            if (!$insert_result || pg_affected_rows($insert_result) <= 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể tạo tài khoản admin: ' . pg_last_error($conn)
                ];
            }
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Lọc Phim</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
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
            top: 24px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background-color: #f8f9fa;
            padding: 0 10px;
            text-align: center;
        }
        .step-number {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }
        .step.active .step-number {
            background-color: #007bff;
            color: #fff;
        }
        .step.completed .step-number {
            background-color: #28a745;
            color: #fff;
        }
        .step-name {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .step.active .step-name {
            color: #007bff;
            font-weight: bold;
        }
        .step.completed .step-name {
            color: #28a745;
        }
        .install-card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .requirement-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .requirement-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="install-header">
            <h1>Cài đặt Lọc Phim</h1>
            <p class="text-muted">Trang xem phim và anime trực tuyến</p>
        </div>
        
        <div class="steps">
            <?php foreach ($steps as $step_number => $step_name): ?>
                <div class="step <?php echo $step_number < $current_step ? 'completed' : ($step_number == $current_step ? 'active' : ''); ?>">
                    <div class="step-number">
                        <?php if ($step_number < $current_step): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <?php echo $step_number; ?>
                        <?php endif; ?>
                    </div>
                    <div class="step-name"><?php echo $step_name; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card install-card">
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php switch ($current_step): ?>
                    <?php case 1: ?>
                        <!-- Bước 1: Yêu cầu hệ thống -->
                        <h3 class="card-title mb-4">Yêu cầu hệ thống</h3>
                        
                        <?php $system_check = check_system_requirements(); ?>
                        
                        <div class="requirements mb-4">
                            <?php foreach ($system_check['requirements'] as $req): ?>
                                <div class="requirement-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo $req['name']; ?></strong>
                                        <div class="text-muted small">Yêu cầu: <?php echo $req['required']; ?></div>
                                    </div>
                                    <div>
                                        <span class="status-badge badge <?php echo $req['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $req['current']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($system_check['passed']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-2"></i> Hệ thống của bạn đáp ứng tất cả các yêu cầu cài đặt.
                            </div>
                            <div class="text-center">
                                <a href="install.php?step=2" class="btn btn-primary">Tiếp tục <i class="fas fa-arrow-right ml-2"></i></a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i> Hệ thống của bạn không đáp ứng tất cả các yêu cầu cài đặt. Vui lòng kiểm tra và cài đặt các thành phần còn thiếu.
                            </div>
                        <?php endif; ?>
                        <?php break; ?>
                    
                    <?php case 2: ?>
                        <!-- Bước 2: Cấu hình cơ sở dữ liệu -->
                        <h3 class="card-title mb-4">Cấu hình cơ sở dữ liệu</h3>
                        
                        <form method="post" action="install.php?step=2">
                            <div class="form-group">
                                <label for="db_type">Loại cơ sở dữ liệu</label>
                                <select name="db_type" id="db_type" class="form-control" required>
                                    <option value="mysql" <?php echo (isset($_POST['db_type']) && $_POST['db_type'] === 'mysql') ? 'selected' : ''; ?>>MySQL</option>
                                    <option value="postgresql" <?php echo (isset($_POST['db_type']) && $_POST['db_type'] === 'postgresql') ? 'selected' : ''; ?>>PostgreSQL</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="db_host">Máy chủ</label>
                                <input type="text" name="db_host" id="db_host" class="form-control" value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                                <small class="form-text text-muted">Thường là "localhost" hoặc "127.0.0.1"</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="db_port">Cổng</label>
                                <input type="text" name="db_port" id="db_port" class="form-control" value="<?php echo $_POST['db_port'] ?? '3306'; ?>" required>
                                <small class="form-text text-muted">MySQL thường là 3306, PostgreSQL thường là 5432</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="db_name">Tên cơ sở dữ liệu</label>
                                <input type="text" name="db_name" id="db_name" class="form-control" value="<?php echo $_POST['db_name'] ?? 'loc_phim'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="db_user">Tên người dùng</label>
                                <input type="text" name="db_user" id="db_user" class="form-control" value="<?php echo $_POST['db_user'] ?? 'root'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="db_password">Mật khẩu</label>
                                <input type="password" name="db_password" id="db_password" class="form-control" value="<?php echo $_POST['db_password'] ?? ''; ?>">
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Kiểm tra kết nối <i class="fas fa-database ml-2"></i></button>
                            </div>
                        </form>
                        <?php break; ?>
                    
                    <?php case 3: ?>
                        <!-- Bước 3: Cấu hình trang web -->
                        <h3 class="card-title mb-4">Cấu hình trang web</h3>
                        
                        <?php
                        // Lấy URL hiện tại làm giá trị mặc định
                        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                        $current_url = rtrim($current_url, '/');
                        ?>
                        
                        <form method="post" action="install.php?step=3">
                            <div class="form-group">
                                <label for="site_url">URL trang web</label>
                                <input type="url" name="site_url" id="site_url" class="form-control" value="<?php echo $_POST['site_url'] ?? $current_url; ?>" required>
                                <small class="form-text text-muted">URL gốc của trang web, không có dấu "/" ở cuối</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_name">Tên trang web</label>
                                <input type="text" name="site_name" id="site_name" class="form-control" value="<?php echo $_POST['site_name'] ?? 'Lọc Phim'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Mô tả trang web</label>
                                <textarea name="site_description" id="site_description" class="form-control" rows="3"><?php echo $_POST['site_description'] ?? 'Trang xem phim và anime trực tuyến'; ?></textarea>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Tiếp tục <i class="fas fa-arrow-right ml-2"></i></button>
                            </div>
                        </form>
                        <?php break; ?>
                    
                    <?php case 4: ?>
                        <!-- Bước 4: Tạo tài khoản Admin -->
                        <h3 class="card-title mb-4">Tạo tài khoản Admin</h3>
                        
                        <form method="post" action="install.php?step=4">
                            <div class="form-group">
                                <label for="admin_username">Tên đăng nhập</label>
                                <input type="text" name="admin_username" id="admin_username" class="form-control" value="<?php echo $_POST['admin_username'] ?? 'admin'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_email">Email</label>
                                <input type="email" name="admin_email" id="admin_email" class="form-control" value="<?php echo $_POST['admin_email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_password">Mật khẩu</label>
                                <input type="password" name="admin_password" id="admin_password" class="form-control" required>
                                <small class="form-text text-muted">Mật khẩu phải có ít nhất 8 ký tự</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_password_confirm">Xác nhận mật khẩu</label>
                                <input type="password" name="admin_password_confirm" id="admin_password_confirm" class="form-control" required>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Tạo tài khoản <i class="fas fa-user-plus ml-2"></i></button>
                            </div>
                        </form>
                        <?php break; ?>
                    
                    <?php case 5: ?>
                        <!-- Bước 5: Hoàn tất cài đặt -->
                        <h3 class="card-title mb-4">Cài đặt hoàn tất</h3>
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                            <h4>Chúc mừng! Bạn đã cài đặt Lọc Phim thành công.</h4>
                            <p class="text-muted">Bạn có thể bắt đầu sử dụng trang web của mình.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Trang chủ</h5>
                                        <p>Xem trang web của bạn</p>
                                        <a href="index.php" class="btn btn-primary btn-block">Đến trang chủ</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Quản trị</h5>
                                        <p>Đăng nhập vào trang quản trị</p>
                                        <a href="admin/" class="btn btn-info btn-block">Đăng nhập quản trị</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Vì lý do bảo mật, bạn nên xóa file <strong>install.php</strong> sau khi cài đặt.
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>
            </div>
        </div>
        
        <div class="text-center text-muted small">
            <p>Lọc Phim - Phiên bản 1.0</p>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Cập nhật cổng mặc định theo loại database
        document.addEventListener('DOMContentLoaded', function() {
            const dbTypeSelect = document.getElementById('db_type');
            const dbPortInput = document.getElementById('db_port');
            
            if (dbTypeSelect && dbPortInput) {
                dbTypeSelect.addEventListener('change', function() {
                    if (this.value === 'mysql') {
                        dbPortInput.value = '3306';
                    } else {
                        dbPortInput.value = '5432';
                    }
                });
            }
        });
    </script>
</body>
</html>