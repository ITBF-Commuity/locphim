<?php
/**
 * AJAX - Lấy cấu trúc bảng và dữ liệu mẫu
 * Lọc Phim - Admin Panel
 */

// Kết nối file chính
require_once dirname(dirname(__DIR__)) . '/config.php';
require_once dirname(dirname(__DIR__)) . '/admin/includes/auth.php';

// Kiểm tra đăng nhập
if (!is_admin_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập.'
    ]);
    exit;
}

// Kiểm tra tham số bảng
$table = $_GET['table'] ?? '';

if (empty($table)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tên bảng.'
    ]);
    exit;
}

// Kiểm tra tên bảng hợp lệ (chỉ chấp nhận các bảng đã biết)
$valid_tables = [
    'users', 'videos', 'episodes', 'categories', 'video_categories',
    'comments', 'ratings', 'video_sources', 'subtitles', 'watch_history',
    'favorites', 'vip_members', 'payment_transactions', 'notifications',
    'reports', 'settings', 'anime_api_cache', 'admin_logs', 'role_permissions'
];

if (!in_array($table, $valid_tables)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Tên bảng không hợp lệ.'
    ]);
    exit;
}

// Lấy thông tin cấu trúc bảng và dữ liệu mẫu
$db_type = get_config('db.type');
$structure = [];
$data = [];
$columns = [];

$conn = db_connect();

try {
    // Lấy cấu trúc bảng
    if ($db_type === 'postgresql') {
        // PostgreSQL
        $structure_sql = "
            SELECT 
                a.attname as name,
                pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
                CASE 
                    WHEN a.attnotnull = false THEN true 
                    ELSE false 
                END as nullable,
                CASE 
                    WHEN ic.column_name IS NOT NULL THEN 'PK'
                    WHEN tc.constraint_type = 'FOREIGN KEY' THEN 'FK'
                    ELSE ''
                END as key,
                pg_get_expr(d.adbin, d.adrelid) as default
            FROM pg_attribute a
            LEFT JOIN pg_catalog.pg_description pgd ON (pgd.objoid = a.attrelid AND pgd.objsubid = a.attnum)
            LEFT JOIN information_schema.key_column_usage kcu ON kcu.column_name = a.attname AND kcu.table_name = '$table'
            LEFT JOIN information_schema.table_constraints tc ON tc.constraint_name = kcu.constraint_name
            LEFT JOIN information_schema.columns ic ON ic.column_name = a.attname AND ic.table_name = '$table' AND ic.is_identity = 'YES'
            LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
            WHERE a.attrelid = '$table'::regclass
            AND a.attnum > 0
            AND NOT a.attisdropped
            ORDER BY a.attnum;
        ";
        
        $structure_result = pg_query($conn, $structure_sql);
        
        while ($row = pg_fetch_assoc($structure_result)) {
            $structure[] = [
                'name' => $row['name'],
                'type' => $row['type'],
                'key' => $row['key'],
                'nullable' => $row['nullable'] === 't',
                'default' => $row['default']
            ];
        }
        
        // Lấy danh sách cột
        $columns_result = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position");
        while ($row = pg_fetch_assoc($columns_result)) {
            $columns[] = $row['column_name'];
        }
        
        // Lấy dữ liệu mẫu (5 bản ghi đầu tiên)
        $data_result = pg_query($conn, "SELECT * FROM $table LIMIT 5");
        while ($row = pg_fetch_assoc($data_result)) {
            $data[] = $row;
        }
    } else {
        // MySQL
        // Lấy cấu trúc bảng
        $structure_sql = "DESCRIBE $table";
        $structure_result = $conn->query($structure_sql);
        
        while ($row = $structure_result->fetch_assoc()) {
            $key = '';
            if ($row['Key'] === 'PRI') {
                $key = 'PK';
            } elseif ($row['Key'] === 'MUL') {
                $key = 'FK';
            } elseif ($row['Key'] === 'UNI') {
                $key = 'UQ';
            }
            
            $structure[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'key' => $key,
                'nullable' => $row['Null'] === 'YES',
                'default' => $row['Default']
            ];
        }
        
        // Lấy danh sách cột
        $columns_result = $conn->query("SHOW COLUMNS FROM $table");
        while ($row = $columns_result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Lấy dữ liệu mẫu (5 bản ghi đầu tiên)
        $data_result = $conn->query("SELECT * FROM $table LIMIT 5");
        while ($row = $data_result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'structure' => $structure,
        'columns' => $columns,
        'data' => $data
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy cấu trúc bảng: ' . $e->getMessage()
    ]);
}