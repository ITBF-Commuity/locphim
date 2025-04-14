<?php
// Script cập nhật thông tin anime từ các API
define('SECURE_ACCESS', true);
require_once '../config.php';
require_once 'config.php';
require_once 'client.php';

// Kiểm tra xem bảng anime_api_cache đã tồn tại chưa
function check_and_create_cache_table() {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        // Kiểm tra bảng tồn tại
        $conn = db_connect();
        $check_table_sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public'
            AND table_name = 'anime_api_cache'
        )";
        
        $result = pg_query($conn, $check_table_sql);
        $row = pg_fetch_row($result);
        
        if ($row[0] == 'f') {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE anime_api_cache (
                    id SERIAL PRIMARY KEY,
                    source_id VARCHAR(100),
                    title VARCHAR(255) NOT NULL,
                    alt_title VARCHAR(255),
                    slug VARCHAR(255) NOT NULL,
                    description TEXT,
                    thumbnail VARCHAR(255),
                    banner VARCHAR(255),
                    release_year INTEGER,
                    release_date DATE,
                    status VARCHAR(50) DEFAULT 'unknown',
                    episode_count INTEGER DEFAULT 0,
                    rating NUMERIC(3,1) DEFAULT 0.0,
                    details_json TEXT,
                    trailer_json TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX anime_api_cache_source_id_idx ON anime_api_cache (source_id);
                CREATE INDEX anime_api_cache_title_idx ON anime_api_cache (title);
                CREATE INDEX anime_api_cache_slug_idx ON anime_api_cache (slug);
            ";
            
            pg_query($conn, $create_table_sql);
            return pg_last_error($conn) === '';
        }
        
        return true;
    } else {
        // MySQL
        $conn = db_connect();
        $check_table_sql = "SHOW TABLES LIKE 'anime_api_cache'";
        $result = $conn->query($check_table_sql);
        
        if ($result->num_rows == 0) {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE anime_api_cache (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    source_id VARCHAR(100),
                    title VARCHAR(255) NOT NULL,
                    alt_title VARCHAR(255),
                    slug VARCHAR(255) NOT NULL,
                    description TEXT,
                    thumbnail VARCHAR(255),
                    banner VARCHAR(255),
                    release_year INT,
                    release_date DATE,
                    status VARCHAR(50) DEFAULT 'unknown',
                    episode_count INT DEFAULT 0,
                    rating DECIMAL(3,1) DEFAULT 0.0,
                    details_json TEXT,
                    trailer_json TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (source_id),
                    INDEX (title),
                    INDEX (slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $conn->query($create_table_sql);
            return $conn->error === '';
        }
        
        return true;
    }
}

// Xử lý request
$response = [
    'success' => false,
    'message' => 'No action specified'
];

// Kiểm tra và tạo bảng cache nếu cần
if (!check_and_create_cache_table()) {
    $response['message'] = 'Failed to create cache table';
    echo json_encode($response);
    exit;
}

// Kiểm tra API key nếu cần
$api_key = $_GET['api_key'] ?? '';
$admin_api_key = get_config('site.admin_api_key') ?? '';

if (!empty($admin_api_key) && $api_key !== $admin_api_key) {
    header('HTTP/1.1 401 Unauthorized');
    $response['message'] = 'Invalid API key';
    echo json_encode($response);
    exit;
}

// Xác định action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search':
        // Tìm kiếm anime
        $query = $_GET['query'] ?? '';
        $source = $_GET['source'] ?? 'jikan'; // jikan, kitsu, anilist
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        if (empty($query)) {
            $response['message'] = 'Query parameter is required';
            break;
        }
        
        switch ($source) {
            case 'jikan':
                $result = search_anime_mal($query, $page, $limit);
                break;
            case 'kitsu':
                $result = search_anime_kitsu($query, $page, $limit);
                break;
            case 'anilist':
                $result = search_anime_anilist($query, $page, $limit);
                break;
            default:
                $result = search_anime_mal($query, $page, $limit);
                break;
        }
        
        if ($result['success']) {
            $response = $result;
        } else {
            $response['message'] = $result['error'] ?? 'Search failed';
        }
        break;
    
    case 'get_anime':
        // Lấy thông tin anime
        $id = $_GET['id'] ?? '';
        $source = $_GET['source'] ?? 'jikan';
        
        if (empty($id)) {
            $response['message'] = 'ID parameter is required';
            break;
        }
        
        switch ($source) {
            case 'jikan':
                $result = get_anime_mal($id);
                break;
            case 'kitsu':
                $result = get_anime_kitsu($id);
                break;
            case 'anilist':
                $result = get_anime_anilist($id);
                break;
            default:
                $result = get_anime_mal($id);
                break;
        }
        
        if ($result['success']) {
            $response = $result;
        } else {
            $response['message'] = $result['error'] ?? 'Failed to get anime details';
        }
        break;
    
    case 'update_anime':
        // Cập nhật thông tin anime
        $title = $_GET['title'] ?? '';
        $force = isset($_GET['force']) && $_GET['force'] === 'true';
        
        if (empty($title)) {
            $response['message'] = 'Title parameter is required';
            break;
        }
        
        $result = get_anime_info($title, $force);
        
        if ($result['success']) {
            $response = $result;
        } else {
            $response['message'] = $result['error'] ?? 'Failed to update anime info';
        }
        break;
    
    case 'update_all':
        // Cập nhật tất cả anime trong database
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $force = isset($_GET['force']) && $_GET['force'] === 'true';
        
        // Lấy danh sách anime cần cập nhật
        $sql = "SELECT id, title, release_year FROM anime ORDER BY updated_at ASC LIMIT ?";
        $result = db_query($sql, [$limit], true);
        
        $updated = 0;
        $failed = 0;
        $details = [];
        
        foreach ($result as $anime) {
            $title = $anime['title'];
            $update_result = get_anime_info($title, $force);
            
            if ($update_result['success']) {
                $updated++;
                
                // Cập nhật thông tin vào bảng anime
                $update_anime_sql = "
                    UPDATE anime SET 
                        alt_title = ?,
                        description = ?,
                        thumbnail = ?,
                        banner = ?,
                        status = ?,
                        episode_count = ?,
                        rating = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $anime_data = $update_result['data'];
                
                $update_params = [
                    $anime_data['alt_title'] ?? '',
                    $anime_data['description'] ?? '',
                    $anime_data['thumbnail'] ?? '',
                    $anime_data['banner'] ?? '',
                    $anime_data['status'] ?? 'unknown',
                    $anime_data['episode_count'] ?? 0,
                    $anime_data['rating'] ?? 0,
                    $anime['id']
                ];
                
                db_query($update_anime_sql, $update_params);
                
                $details[] = [
                    'id' => $anime['id'],
                    'title' => $title,
                    'status' => 'success',
                    'source' => $update_result['source']
                ];
            } else {
                $failed++;
                $details[] = [
                    'id' => $anime['id'],
                    'title' => $title,
                    'status' => 'failed',
                    'error' => $update_result['error'] ?? 'Unknown error'
                ];
            }
        }
        
        $response = [
            'success' => true,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($result),
            'details' => $details
        ];
        break;
    
    case 'get_trailer':
        // Tìm trailer từ YouTube
        $title = $_GET['title'] ?? '';
        $year = $_GET['year'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1;
        
        if (empty($title)) {
            $response['message'] = 'Title parameter is required';
            break;
        }
        
        $query = $title;
        if (!empty($year)) {
            $query .= ' ' . $year;
        }
        
        $result = search_trailer($query, $limit);
        
        if ($result['success']) {
            $response = $result;
        } else {
            $response['message'] = $result['error'] ?? 'Failed to get trailer';
        }
        break;
    
    default:
        $response['message'] = 'Invalid action';
        break;
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($response);