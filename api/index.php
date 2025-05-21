<?php
/**
 * Lọc Phim - API Endpoint chính
 * 
 * Xử lý các API chung của website
 */

// Lấy path từ query string
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Xử lý API theo path
switch ($path) {
    case 'install':
        handleInstall();
        break;
    
    case 'ping':
        handlePing();
        break;
    
    case 'settings':
        handleSettings();
        break;
    
    case 'stats':
        handleStats();
        break;
    
    default:
        // API không tồn tại
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API không tồn tại']);
        break;
}

/**
 * Xử lý API cài đặt ban đầu
 */
function handleInstall() {
    global $db, $dbType;
    
    // Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    try {
        // Kiểm tra bảng users đã tồn tại chưa
        $tableExists = false;
        
        if ($dbType === 'postgresql') {
            $result = $db->get("SELECT to_regclass('public.users') IS NOT NULL AS exists");
            $tableExists = $result && $result['exists'];
        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
            $result = $db->get("SHOW TABLES LIKE 'users'");
            $tableExists = !empty($result);
        } elseif ($dbType === 'sqlite') {
            $result = $db->get("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $tableExists = !empty($result);
        }
        
        if ($tableExists) {
            echo json_encode(['success' => true, 'message' => 'Database đã được cài đặt']);
            return;
        }
        
        // Đọc schema SQL tương ứng với loại database
        if ($dbType === 'postgresql') {
            $sqlFile = __DIR__ . '/../database/schema_postgresql.sql';
        } elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
            $sqlFile = __DIR__ . '/../database/schema.sql';
        } elseif ($dbType === 'sqlite') {
            $sqlFile = __DIR__ . '/../database/schema_sqlite.sql';
        } else {
            throw new Exception('Loại database không được hỗ trợ');
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
                $db->execute($statement);
            }
        }
        
        // Tạo file lock để đánh dấu đã cài đặt
        file_put_contents(__DIR__ . '/../install.lock', time());
        
        echo json_encode(['success' => true, 'message' => 'Cài đặt database thành công']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi cài đặt database: ' . $e->getMessage()]);
    }
}

/**
 * Xử lý API ping
 */
function handlePing() {
    global $db;
    
    try {
        // Kiểm tra kết nối database
        $db->execute('SELECT 1');
        
        echo json_encode([
            'success' => true, 
            'time' => date('Y-m-d H:i:s'),
            'status' => 'Database connection OK'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'time' => date('Y-m-d H:i:s'),
            'status' => 'Database connection failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Xử lý API lấy cài đặt
 */
function handleSettings() {
    global $db;
    
    try {
        // Lấy danh sách cài đặt
        $settings = $db->getAll("SELECT name, value FROM settings");
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['name']] = $setting['value'];
        }
        
        echo json_encode(['success' => true, 'settings' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy cài đặt: ' . $e->getMessage()]);
    }
}

/**
 * Xử lý API lấy thống kê
 */
function handleStats() {
    global $db;
    
    try {
        // Lấy thống kê
        $stats = [
            'users' => $db->get("SELECT COUNT(*) AS count FROM users")['count'] ?? 0,
            'movies' => $db->get("SELECT COUNT(*) AS count FROM movies")['count'] ?? 0,
            'episodes' => $db->get("SELECT COUNT(*) AS count FROM episodes")['count'] ?? 0,
            'comments' => $db->get("SELECT COUNT(*) AS count FROM comments")['count'] ?? 0,
            'views' => $db->get("SELECT SUM(views) AS total FROM movies")['total'] ?? 0
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thống kê: ' . $e->getMessage()]);
    }
}