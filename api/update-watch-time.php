<?php
/**
 * API Cập nhật thời gian xem
 * 
 * Endpoint này cập nhật thời gian xem phim của người dùng
 */

// Bao gồm các file cần thiết
require_once '../includes/init.php';

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Kiểm tra người dùng đã đăng nhập chưa
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để sử dụng tính năng này'
    ]);
    exit;
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu
if (!isset($data['episode_id']) || !isset($data['watch_time']) || !isset($data['movie_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$episodeId = (int)$data['episode_id'];
$movieId = (int)$data['movie_id'];
$watchTime = (int)$data['watch_time'];
$duration = isset($data['duration']) ? (int)$data['duration'] : 0;

// Kiểm tra ID tập phim hợp lệ
if ($episodeId <= 0 || $movieId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID tập phim hoặc ID phim không hợp lệ'
    ]);
    exit;
}

try {
    // Kiểm tra xem đã có bản ghi chưa
    $watchProgress = $db->get("
        SELECT * FROM watch_progress 
        WHERE user_id = :user_id AND episode_id = :episode_id
    ", [
        'user_id' => $userId,
        'episode_id' => $episodeId
    ]);
    
    // Dữ liệu cần cập nhật hoặc thêm mới
    $progressData = [
        'user_id' => $userId,
        'movie_id' => $movieId,
        'episode_id' => $episodeId,
        'watch_time' => $watchTime,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Nếu có duration, cập nhật
    if ($duration > 0) {
        $progressData['duration'] = $duration;
    }
    
    // Nếu đã xem hơn 90% thì đánh dấu là đã xem xong
    if ($duration > 0 && $watchTime >= $duration * 0.9) {
        $progressData['is_completed'] = true;
    }
    
    // Thêm mới hoặc cập nhật
    if ($watchProgress) {
        // Nếu thời gian mới nhỏ hơn thời gian đã lưu, không cập nhật
        if ($watchTime < $watchProgress['watch_time']) {
            echo json_encode([
                'success' => true,
                'message' => 'Thời gian xem không được cập nhật (thời gian mới nhỏ hơn)',
                'watch_time' => $watchProgress['watch_time']
            ]);
            exit;
        }
        
        // Cập nhật
        $result = $db->update('watch_progress', $progressData, ['id' => $watchProgress['id']]);
    } else {
        // Thêm mới
        $result = $db->insert('watch_progress', $progressData);
        
        // Cập nhật hoặc thêm mới vào lịch sử xem
        $historyExists = $db->get("
            SELECT * FROM watch_history 
            WHERE user_id = :user_id AND movie_id = :movie_id
        ", [
            'user_id' => $userId,
            'movie_id' => $movieId
        ]);
        
        if (!$historyExists) {
            $db->insert('watch_history', [
                'user_id' => $userId,
                'movie_id' => $movieId,
                'episode_id' => $episodeId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $db->update('watch_history', [
                'episode_id' => $episodeId,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $historyExists['id']]);
        }
        
        // Tăng lượt xem cho tập phim và phim
        $db->query("
            UPDATE episodes
            SET views = views + 1
            WHERE id = :episode_id
        ", ['episode_id' => $episodeId]);
        
        $db->query("
            UPDATE movies
            SET views = views + 1
            WHERE id = :movie_id
        ", ['movie_id' => $movieId]);
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thời gian xem thành công',
        'watch_time' => $watchTime,
        'is_completed' => $progressData['is_completed'] ?? false
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}