<?php
/**
 * Tệp chứa các hàm sử dụng trong admin
 * Lọc Phim - Admin Functions
 */

// Định nghĩa hằng số bảo vệ tệp
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Lấy giá trị cài đặt từ CSDL
 */
function get_setting($key, $default = '') {
    $sql = "SELECT value FROM settings WHERE setting_key = ?";
    $result = db_query($sql, [$key], false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return $row['value'];
        }
    } else {
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['value'];
        }
    }
    
    return $default;
}

/**
 * Cập nhật giá trị cài đặt
 */
function update_setting($key, $value) {
    // Kiểm tra xem cài đặt đã tồn tại chưa
    $sql = "SELECT id FROM settings WHERE setting_key = ?";
    $result = db_query($sql, [$key], false);
    
    $exists = false;
    if (get_config('db.type') === 'postgresql') {
        $exists = pg_num_rows($result) > 0;
    } else {
        $exists = $result->num_rows > 0;
    }
    
    if ($exists) {
        // Cập nhật cài đặt hiện tại
        $sql = "UPDATE settings SET value = ?, updated_at = NOW() WHERE setting_key = ?";
        db_query($sql, [$value, $key]);
    } else {
        // Tạo cài đặt mới
        $sql = "INSERT INTO settings (setting_key, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        db_query($sql, [$key, $value]);
    }
    
    return true;
}

/**
 * Tạo token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Xác thực CSRF token
 */
function verify_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        set_flash_message('error', 'Lỗi bảo mật: CSRF token không hợp lệ.');
        header('Location: index.php');
        exit;
    }
    
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash_message('error', 'Lỗi bảo mật: CSRF token không khớp.');
        header('Location: index.php');
        exit;
    }
    
    return true;
}

/**
 * Ghi log hành động admin
 */
function log_admin_action($action, $details = '') {
    // Đảm bảo bảng logs tồn tại
    check_and_create_admin_logs_table();
    
    $user_id = $_SESSION['admin_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $sql = "INSERT INTO admin_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    db_query($sql, [$user_id, $action, $details, $ip]);
}

/**
 * Kiểm tra và tạo bảng admin_logs nếu chưa tồn tại
 */
function check_and_create_admin_logs_table() {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        $sql = "
            CREATE TABLE IF NOT EXISTS admin_logs (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $sql = "
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
    }
    
    db_query($sql);
}

/**
 * Kiểm tra và tạo bảng permissions
 */
function check_and_create_permissions_table() {
    $db_type = get_config('db.type');
    
    // Bảng phân quyền
    if ($db_type === 'postgresql') {
        $tables_sql = "
            CREATE TABLE IF NOT EXISTS admin_roles (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS admin_permissions (
                id SERIAL PRIMARY KEY,
                role_id INT NOT NULL,
                permission VARCHAR(100) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(role_id, permission)
            );
            
            CREATE TABLE IF NOT EXISTS admin_user_roles (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, role_id)
            );
        ";
    } else {
        $tables_sql = "
            CREATE TABLE IF NOT EXISTS admin_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE IF NOT EXISTS admin_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT NOT NULL,
                permission VARCHAR(100) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(role_id, permission)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE IF NOT EXISTS admin_user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }
    
    // Tạo các bảng
    $queries = explode(';', $tables_sql);
    foreach ($queries as $query) {
        if (trim($query) !== '') {
            db_query($query);
        }
    }
    
    // Kiểm tra xem có role "Super Admin" chưa
    $sql = "SELECT id FROM admin_roles WHERE name = 'Super Admin'";
    $result = db_query($sql, [], false);
    
    $exists = false;
    if ($db_type === 'postgresql') {
        $exists = pg_num_rows($result) > 0;
    } else {
        $exists = $result->num_rows > 0;
    }
    
    // Tạo role Super Admin nếu chưa tồn tại
    if (!$exists) {
        $sql = "INSERT INTO admin_roles (name, description) VALUES ('Super Admin', 'Quyền quản trị cao nhất')";
        $result = db_query($sql);
        
        $role_id = 0;
        if ($db_type === 'postgresql') {
            $role_id = $result['insert_id'];
        } else {
            $role_id = $result['insert_id'];
        }
        
        // Danh sách tất cả quyền
        $permissions = [
            'manage_users', 'view_users', 'add_user', 'edit_user', 'delete_user',
            'manage_anime', 'view_anime', 'add_anime', 'edit_anime', 'delete_anime',
            'manage_episodes', 'view_episodes', 'add_episode', 'edit_episode', 'delete_episode',
            'manage_categories', 'view_categories', 'add_category', 'edit_category', 'delete_category',
            'manage_comments', 'view_comments', 'edit_comment', 'delete_comment',
            'manage_settings', 'view_settings', 'edit_settings',
            'manage_maintenance', 'enable_maintenance', 'disable_maintenance',
            'manage_api', 'edit_api_settings',
            'view_logs', 'delete_logs',
            'manage_roles', 'view_roles', 'add_role', 'edit_role', 'delete_role',
            'manage_backups', 'create_backup', 'restore_backup',
            'manage_cache', 'clear_cache',
            'manage_seo', 'edit_seo', 'generate_sitemap'
        ];
        
        // Thêm tất cả quyền cho Super Admin
        foreach ($permissions as $permission) {
            $sql = "INSERT INTO admin_permissions (role_id, permission) VALUES (?, ?)";
            db_query($sql, [$role_id, $permission]);
        }
        
        // Thêm quyền Super Admin cho user ID 1 (admin đầu tiên)
        $sql = "SELECT id FROM admin_user_roles WHERE user_id = 1 AND role_id = ?";
        $result = db_query($sql, [$role_id], false);
        
        $user_role_exists = false;
        if ($db_type === 'postgresql') {
            $user_role_exists = pg_num_rows($result) > 0;
        } else {
            $user_role_exists = $result->num_rows > 0;
        }
        
        if (!$user_role_exists) {
            $sql = "INSERT INTO admin_user_roles (user_id, role_id) VALUES (1, ?)";
            db_query($sql, [$role_id]);
        }
    }
}

/**
 * Kiểm tra quyền admin
 */
function check_admin_permission($permission, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['admin_id'] ?? 0;
    }
    
    // Kiểm tra xem user có phải là Super Admin không
    $sql = "
        SELECT r.name 
        FROM admin_roles r 
        JOIN admin_user_roles ur ON r.id = ur.role_id 
        WHERE ur.user_id = ? AND r.name = 'Super Admin'
    ";
    $result = db_query($sql, [$user_id], false);
    
    $is_super_admin = false;
    if (get_config('db.type') === 'postgresql') {
        $is_super_admin = pg_num_rows($result) > 0;
    } else {
        $is_super_admin = $result && $result->num_rows > 0;
    }
    
    // Super Admin có tất cả quyền
    if ($is_super_admin) {
        return true;
    }
    
    // Kiểm tra quyền cụ thể
    $sql = "
        SELECT p.permission 
        FROM admin_permissions p 
        JOIN admin_user_roles ur ON p.role_id = ur.role_id 
        WHERE ur.user_id = ? AND p.permission = ?
    ";
    $result = db_query($sql, [$user_id, $permission], false);
    
    if (get_config('db.type') === 'postgresql') {
        return pg_num_rows($result) > 0;
    } else {
        return $result && $result->num_rows > 0;
    }
}

/**
 * Yêu cầu quyền admin
 */
function require_admin_permission($permission) {
    $admin = require_admin_login();
    
    if (!check_admin_permission($permission)) {
        set_flash_message('error', 'Bạn không có quyền truy cập chức năng này.');
        header('Location: index.php');
        exit;
    }
    
    return $admin;
}

/**
 * Kiểm tra xem quyền đã được chọn cho role hay chưa
 */
function is_permission_checked($role_id, $permission) {
    $sql = "SELECT id FROM admin_permissions WHERE role_id = ? AND permission = ?";
    $result = db_query($sql, [$role_id, $permission], false);
    
    if (get_config('db.type') === 'postgresql') {
        return pg_num_rows($result) > 0;
    } else {
        return $result && $result->num_rows > 0;
    }
}

/**
 * Lấy biểu tượng cho nhóm quyền
 */
function get_group_icon($group) {
    $icons = [
        'users' => 'fas fa-users',
        'anime' => 'fas fa-tv',
        'episodes' => 'fas fa-film',
        'categories' => 'fas fa-list',
        'comments' => 'fas fa-comments',
        'settings' => 'fas fa-cog',
        'maintenance' => 'fas fa-tools',
        'api' => 'fas fa-plug',
        'logs' => 'fas fa-history',
        'roles' => 'fas fa-user-shield',
        'backups' => 'fas fa-database',
        'cache' => 'fas fa-bolt',
        'seo' => 'fas fa-search'
    ];
    
    return $icons[$group] ?? 'fas fa-check';
}

/**
 * Lấy thông báo flash
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    return null;
}

/**
 * Định dạng thời gian đã trôi qua
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'năm',
        'm' => 'tháng',
        'w' => 'tuần',
        'd' => 'ngày',
        'h' => 'giờ',
        'i' => 'phút',
        's' => 'giây',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    
    return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
}

/**
 * Kiểm tra chế độ bảo trì
 */
function check_maintenance_mode() {
    // Kiểm tra cài đặt maintenance_mode
    $maintenance_mode = get_setting('maintenance_mode', '0');
    
    // Kiểm tra nếu người dùng là admin thì vẫn truy cập được
    if ($maintenance_mode === '1' && (!isset($_SESSION['admin_id']) || !is_admin_logged_in())) {
        // Chuyển hướng đến trang bảo trì
        header('Location: /maintenance.php');
        exit;
    }
}

/**
 * Kiểm tra yêu cầu máy chủ
 */
function check_server_requirements() {
    $requirements = [
        'php_version' => [
            'name' => 'Phiên bản PHP',
            'requirement' => '>= 7.4.0',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'current' => PHP_VERSION
        ],
        'gd' => [
            'name' => 'GD Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('gd'),
            'current' => extension_loaded('gd') ? 'Bật' : 'Tắt'
        ],
        'mysqli' => [
            'name' => 'MySQLi Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('mysqli'),
            'current' => extension_loaded('mysqli') ? 'Bật' : 'Tắt'
        ],
        'pgsql' => [
            'name' => 'PostgreSQL Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('pgsql'),
            'current' => extension_loaded('pgsql') ? 'Bật' : 'Tắt'
        ],
        'pdo' => [
            'name' => 'PDO Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('pdo'),
            'current' => extension_loaded('pdo') ? 'Bật' : 'Tắt'
        ],
        'curl' => [
            'name' => 'cURL Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('curl'),
            'current' => extension_loaded('curl') ? 'Bật' : 'Tắt'
        ],
        'json' => [
            'name' => 'JSON Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('json'),
            'current' => extension_loaded('json') ? 'Bật' : 'Tắt'
        ],
        'fileinfo' => [
            'name' => 'Fileinfo Extension',
            'requirement' => 'Bật',
            'status' => extension_loaded('fileinfo'),
            'current' => extension_loaded('fileinfo') ? 'Bật' : 'Tắt'
        ],
        'uploads_writable' => [
            'name' => 'Thư mục uploads',
            'requirement' => 'Có quyền ghi',
            'status' => is_writable('uploads'),
            'current' => is_writable('uploads') ? 'Có quyền ghi' : 'Không có quyền ghi'
        ],
        'cache_writable' => [
            'name' => 'Thư mục cache',
            'requirement' => 'Có quyền ghi',
            'status' => is_writable('cache'),
            'current' => is_writable('cache') ? 'Có quyền ghi' : 'Không có quyền ghi'
        ]
    ];
    
    // Kiểm tra tổng thể
    $overall_status = true;
    foreach ($requirements as $req) {
        if (!$req['status']) {
            $overall_status = false;
            break;
        }
    }
    
    return [
        'status' => $overall_status,
        'requirements' => $requirements
    ];
}

/**
 * Lấy thông tin hệ thống
 */
function get_system_info() {
    $system_info = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_type' => get_config('db.type'),
        'database_version' => '',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time') . ' giây',
        'max_input_time' => ini_get('max_input_time') . ' giây',
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'operating_system' => PHP_OS,
        'architecture' => php_uname('m'),
    ];
    
    // Lấy phiên bản CSDL
    $conn = db_connect();
    if (get_config('db.type') === 'postgresql') {
        $result = pg_query($conn, "SELECT version()");
        if ($result) {
            $system_info['database_version'] = pg_fetch_result($result, 0, 0);
        }
    } else {
        $result = mysqli_query($conn, "SELECT VERSION()");
        if ($result) {
            $row = mysqli_fetch_row($result);
            $system_info['database_version'] = $row[0];
        }
    }
    
    return $system_info;
}

/**
 * Kiểm tra bảng CSDL
 */
function check_database_tables() {
    $db_type = get_config('db.type');
    $conn = db_connect();
    $tables = [];
    
    if ($db_type === 'postgresql') {
        $sql = "SELECT table_name, pg_size_pretty(pg_total_relation_size(table_name::text)) as size, 
                (SELECT count(*) FROM " . ($db_type === 'postgresql' ? 'information_schema.columns' : 'information_schema.COLUMNS') . " 
                WHERE table_name = t.table_name) as columns,
                (SELECT count(*) FROM $table_name) as rows
                FROM information_schema.tables t
                WHERE table_schema = 'public'
                ORDER BY table_name";
        $result = pg_query($conn, $sql);
        
        while ($row = pg_fetch_assoc($result)) {
            $tables[] = [
                'name' => $row['table_name'],
                'size' => $row['size'],
                'columns' => $row['columns'],
                'rows' => $row['rows']
            ];
        }
    } else {
        $db_name = get_config('db.database');
        $sql = "SELECT table_name, 
                round((data_length + index_length) / 1024, 2) as size,
                (SELECT count(*) FROM information_schema.COLUMNS 
                WHERE table_schema = DATABASE() AND table_name = t.table_name) as columns,
                table_rows as rows
                FROM information_schema.TABLES t
                WHERE table_schema = ?
                ORDER BY table_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $db_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $tables[] = [
                'name' => $row['TABLE_NAME'],
                'size' => $row['size'] . ' KB',
                'columns' => $row['columns'],
                'rows' => $row['rows']
            ];
        }
    }
    
    return $tables;
}

/**
 * Tạo lại file .htaccess
 */
function generate_htaccess() {
    $htaccess_file = dirname(dirname(__FILE__)) . '/.htaccess';
    
    // Nội dung .htaccess
    $htaccess_content = <<<EOT
# Lọc Phim - Tệp cấu hình .htaccess
# Được tạo tự động bởi hệ thống

# Bật mod_rewrite
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Chuyển hướng www đến non-www
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^(.*)$ http://%1/$1 [R=301,L]
    
    # URL thân thiện
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    
    # Quy tắc chuyển hướng cho trang phim
    RewriteRule ^anime/([^/]+)/?$ watch.php?slug=$1 [QSA,L]
    RewriteRule ^anime/([^/]+)/episode-([0-9]+)/?$ watch.php?slug=$1&episode=$2 [QSA,L]
    
    # Quy tắc chuyển hướng cho danh mục
    RewriteRule ^category/([^/]+)/?$ category.php?slug=$1 [QSA,L]
    
    # Quy tắc chuyển hướng cho trang tĩnh
    RewriteRule ^page/([^/]+)/?$ page.php?slug=$1 [QSA,L]
</IfModule>

# Bật nén GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>

# Bộ nhớ đệm trình duyệt
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType video/mp4 "access plus 1 month"
    ExpiresByType video/webm "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/json "access plus 1 day"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"
    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>

# Cài đặt ETags
<IfModule mod_headers.c>
    <FilesMatch "\.(ico|jpg|jpeg|png|gif|webp|css|js)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
    <FilesMatch "\.(html|htm)$">
        Header set Cache-Control "max-age=7200, private, must-revalidate"
    </FilesMatch>
    <FilesMatch "\.(pdf)$">
        Header set Cache-Control "max-age=86400, public"
    </FilesMatch>
    <FilesMatch "\.(mp4|webm)$">
        Header set Cache-Control "max-age=2592000, public"
    </FilesMatch>
    Header unset ETag
    Header unset Last-Modified
</IfModule>

# Bảo vệ tệp
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(config\.php|\.env|composer\.json|composer\.lock)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Tăng cường bảo mật
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>

# Chặn truy cập vào thư mục
Options -Indexes
EOT;
    
    // Ghi vào file
    $result = file_put_contents($htaccess_file, $htaccess_content);
    
    if ($result === false) {
        return [
            'success' => false,
            'message' => 'Không thể tạo file .htaccess. Vui lòng kiểm tra quyền ghi.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Đã tạo lại file .htaccess thành công.'
    ];
}

/**
 * Xóa cache
 */
function clear_cache() {
    $cache_dir = dirname(dirname(__FILE__)) . '/cache';
    
    if (!file_exists($cache_dir) || !is_dir($cache_dir)) {
        return [
            'success' => false,
            'message' => 'Thư mục cache không tồn tại.'
        ];
    }
    
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $count = 0;
    foreach ($objects as $object) {
        if ($object->isFile()) {
            unlink($object->getPathname());
            $count++;
        }
    }
    
    return [
        'success' => true,
        'message' => "Đã xóa $count tệp cache thành công."
    ];
}

/**
 * Tính toán thống kê cache
 */
function calculate_cache_stats($dir) {
    $size = 0;
    $files = 0;
    
    if (is_dir($dir)) {
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($objects as $object) {
            if ($object->isFile()) {
                $size += $object->getSize();
                $files++;
            }
        }
    }
    
    return [
        'size' => $size,
        'files' => $files
    ];
}

/**
 * Định dạng kích thước
 */
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Tìm tất cả hình ảnh trong thư mục
 */
function find_images($dir) {
    $images = [];
    
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = $file->getPathname();
                }
            }
        }
    }
    
    return $images;
}

/**
 * Lấy màu cho loại hành động trong logs
 */
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

/**
 * Định dạng tên hành động
 */
function format_action_name($action) {
    $action = str_replace('_', ' ', $action);
    return ucwords($action);
}

/**
 * Tạo query string cho phân trang
 */
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

/**
 * Tạo redirect
 */
function redirect($url, $permanent = false) {
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    exit();
}

/**
 * Lấy current URL
 */
function get_current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    
    return $protocol . $domain . $path;
}

/**
 * Hiển thị trang bảo trì
 */
function display_maintenance_page() {
    // Chuyển hướng đến trang bảo trì
    include dirname(dirname(__FILE__)) . '/maintenance.php';
    exit;
}