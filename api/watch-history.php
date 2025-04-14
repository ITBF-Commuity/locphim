<?php
// API lưu lịch sử xem và lấy thông tin thời gian xem của người dùng
define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../functions.php';
require_once '../auth.php';

// Kiểm tra và tạo bảng watch_history nếu chưa tồn tại
function check_and_create_watch_history_table() {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        // Kiểm tra bảng tồn tại
        $conn = db_connect();
        $check_table_sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public'
            AND table_name = 'watch_history'
        )";
        
        $result = pg_query($conn, $check_table_sql);
        $row = pg_fetch_row($result);
        
        if ($row[0] == 'f') {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE watch_history (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    video_id VARCHAR(100) NOT NULL,
                    episode_id INTEGER,
                    playback_time NUMERIC(10, 2) DEFAULT 0,
                    duration NUMERIC(10, 2) DEFAULT 0,
                    percentage_watched NUMERIC(5, 2) DEFAULT 0,
                    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX watch_history_user_id_idx ON watch_history (user_id);
                CREATE INDEX watch_history_video_id_idx ON watch_history (video_id);
                CREATE INDEX watch_history_episode_id_idx ON watch_history (episode_id);
            ";
            
            pg_query($conn, $create_table_sql);
            return pg_last_error($conn) === '';
        }
        
        return true;
    } else {
        // MySQL
        $conn = db_connect();
        $check_table_sql = "SHOW TABLES LIKE 'watch_history'";
        $result = $conn->query($check_table_sql);
        
        if ($result->num_rows == 0) {
            // Tạo bảng nếu chưa tồn tại
            $create_table_sql = "
                CREATE TABLE watch_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    video_id VARCHAR(100) NOT NULL,
                    episode_id INT,
                    playback_time DECIMAL(10, 2) DEFAULT 0,
                    duration DECIMAL(10, 2) DEFAULT 0,
                    percentage_watched DECIMAL(5, 2) DEFAULT 0,
                    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (user_id),
                    INDEX (video_id),
                    INDEX (episode_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $conn->query($create_table_sql);
            return $conn->error === '';
        }
        
        return true;
    }
}

// Lưu thông tin lịch sử xem
function save_watch_history($user_id, $video_id, $playback_time, $duration, $episode_id = null) {
    // Tính phần trăm đã xem
    $percentage = ($duration > 0) ? ($playback_time / $duration) * 100 : 0;
    $percentage = min(99.99, $percentage); // Tối đa 99.99% (để phân biệt với đã xem hết)
    
    // Kiểm tra xem đã có lịch sử xem cho video này chưa
    $check_sql = "SELECT id FROM watch_history WHERE user_id = ? AND video_id = ? AND (episode_id = ? OR (episode_id IS NULL AND ? IS NULL))";
    $check_params = [$user_id, $video_id, $episode_id, $episode_id];
    $check_result = db_query($check_sql, $check_params, false);
    
    $exists = false;
    $existing_id = null;
    
    if (get_config('db.type') === 'postgresql') {
        $exists = pg_num_rows($check_result) > 0;
        if ($exists) {
            $row = pg_fetch_assoc($check_result);
            $existing_id = $row['id'];
        }
    } else {
        $exists = $check_result->num_rows > 0;
        if ($exists) {
            $row = $check_result->fetch_assoc();
            $existing_id = $row['id'];
        }
    }
    
    if ($exists && $existing_id) {
        // Cập nhật lịch sử xem hiện có
        $update_sql = "
            UPDATE watch_history SET 
                playback_time = ?,
                duration = ?,
                percentage_watched = ?,
                last_watched = NOW()
            WHERE id = ?
        ";
        
        $update_params = [
            $playback_time,
            $duration,
            $percentage,
            $existing_id
        ];
        
        $result = db_query($update_sql, $update_params);
        return $result['affected_rows'] > 0;
    } else {
        // Tạo lịch sử xem mới
        $insert_sql = "
            INSERT INTO watch_history (
                user_id, video_id, episode_id, playback_time, duration, percentage_watched, last_watched, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        $insert_params = [
            $user_id,
            $video_id,
            $episode_id,
            $playback_time,
            $duration,
            $percentage
        ];
        
        $result = db_query($insert_sql, $insert_params);
        return $result['affected_rows'] > 0;
    }
}

// Đánh dấu video đã xem hết
function mark_video_completed($user_id, $video_id, $episode_id = null) {
    // Kiểm tra xem đã có lịch sử xem cho video này chưa
    $check_sql = "SELECT id, duration FROM watch_history WHERE user_id = ? AND video_id = ? AND (episode_id = ? OR (episode_id IS NULL AND ? IS NULL))";
    $check_params = [$user_id, $video_id, $episode_id, $episode_id];
    $check_result = db_query($check_sql, $check_params, false);
    
    $exists = false;
    $existing_id = null;
    $duration = 0;
    
    if (get_config('db.type') === 'postgresql') {
        $exists = pg_num_rows($check_result) > 0;
        if ($exists) {
            $row = pg_fetch_assoc($check_result);
            $existing_id = $row['id'];
            $duration = $row['duration'];
        }
    } else {
        $exists = $check_result->num_rows > 0;
        if ($exists) {
            $row = $check_result->fetch_assoc();
            $existing_id = $row['id'];
            $duration = $row['duration'];
        }
    }
    
    if ($exists && $existing_id) {
        // Cập nhật lịch sử xem hiện có
        $update_sql = "
            UPDATE watch_history SET 
                playback_time = ?,
                percentage_watched = 100,
                last_watched = NOW()
            WHERE id = ?
        ";
        
        $update_params = [
            $duration,
            $existing_id
        ];
        
        $result = db_query($update_sql, $update_params);
        return $result['affected_rows'] > 0;
    } else if ($duration > 0) {
        // Tạo lịch sử xem mới nếu biết thời lượng
        $insert_sql = "
            INSERT INTO watch_history (
                user_id, video_id, episode_id, playback_time, duration, percentage_watched, last_watched, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 100, NOW(), NOW()
            )
        ";
        
        $insert_params = [
            $user_id,
            $video_id,
            $episode_id,
            $duration,
            $duration
        ];
        
        $result = db_query($insert_sql, $insert_params);
        return $result['affected_rows'] > 0;
    }
    
    return false;
}

// Lấy lịch sử xem của người dùng
function get_user_watch_history($user_id, $limit = 20, $offset = 0) {
    $sql = "
        SELECT wh.*, 
            v.title as video_title, 
            v.thumbnail as video_thumbnail,
            e.title as episode_title,
            e.episode_number
        FROM watch_history wh
        LEFT JOIN videos v ON wh.video_id = v.video_id
        LEFT JOIN episodes e ON wh.episode_id = e.id
        WHERE wh.user_id = ?
        ORDER BY wh.last_watched DESC
        LIMIT ? OFFSET ?
    ";
    
    $params = [$user_id, $limit, $offset];
    $result = db_query($sql, $params, true);
    
    return $result;
}

// Lấy thông tin thời gian xem của video cụ thể
function get_video_watch_time($user_id, $video_id, $episode_id = null) {
    $sql = "
        SELECT * FROM watch_history 
        WHERE user_id = ? AND video_id = ? AND (episode_id = ? OR (episode_id IS NULL AND ? IS NULL))
    ";
    
    $params = [$user_id, $video_id, $episode_id, $episode_id];
    $result = db_query($sql, $params, false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

// Xóa lịch sử xem
function delete_watch_history($user_id, $history_id = null) {
    if ($history_id) {
        // Xóa một mục cụ thể
        $sql = "DELETE FROM watch_history WHERE id = ? AND user_id = ?";
        $params = [$history_id, $user_id];
    } else {
        // Xóa tất cả lịch sử của người dùng
        $sql = "DELETE FROM watch_history WHERE user_id = ?";
        $params = [$user_id];
    }
    
    $result = db_query($sql, $params);
    return $result['affected_rows'] > 0;
}

// Kiểm tra và tạo bảng watch_history nếu cần
check_and_create_watch_history_table();

// Xử lý request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đảm bảo người dùng đã đăng nhập
    if (!is_logged_in()) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    // Lấy thông tin người dùng
    $current_user = get_current_user();
    $user_id = $current_user['id'];
    
    // Lấy dữ liệu từ request
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data || !isset($data['video_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $video_id = $data['video_id'];
    $current_time = isset($data['current_time']) ? floatval($data['current_time']) : 0;
    $duration = isset($data['duration']) ? floatval($data['duration']) : 0;
    $episode_id = isset($data['episode_id']) ? intval($data['episode_id']) : null;
    
    // Lưu lịch sử xem
    $result = save_watch_history($user_id, $video_id, $current_time, $duration, $episode_id);
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Watch history saved' : 'Failed to save watch history',
        'data' => [
            'user_id' => $user_id,
            'video_id' => $video_id,
            'current_time' => $current_time,
            'duration' => $duration,
            'episode_id' => $episode_id,
            'percentage' => ($duration > 0) ? ($current_time / $duration) * 100 : 0
        ]
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Đảm bảo người dùng đã đăng nhập
    if (!is_logged_in()) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    // Lấy thông tin người dùng
    $current_user = get_current_user();
    $user_id = $current_user['id'];
    
    // Xác định hành động
    $action = isset($_GET['action']) ? $_GET['action'] : 'history';
    
    if ($action === 'video_time') {
        // Lấy thông tin thời gian xem của video cụ thể
        $video_id = isset($_GET['video_id']) ? $_GET['video_id'] : null;
        $episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : null;
        
        if (!$video_id) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Video ID is required']);
            exit;
        }
        
        $watch_time = get_video_watch_time($user_id, $video_id, $episode_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $watch_time !== null,
            'data' => $watch_time
        ]);
    } elseif ($action === 'history') {
        // Lấy lịch sử xem
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $history = get_user_watch_history($user_id, $limit, $offset);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Đảm bảo người dùng đã đăng nhập
    if (!is_logged_in()) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    // Lấy thông tin người dùng
    $current_user = get_current_user();
    $user_id = $current_user['id'];
    
    // Xóa lịch sử xem
    $history_id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $result = delete_watch_history($user_id, $history_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Watch history deleted' : 'Failed to delete watch history'
    ]);
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}