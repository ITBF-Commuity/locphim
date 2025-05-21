<?php
/**
 * API Đánh dấu tập phim đã xem
 * 
 * Endpoint này đánh dấu một tập phim là đã xem xong
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
if (!isset($data['episode_id']) || !isset($data['movie_id'])) {
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
    
    // Lấy thông tin tập phim để biết thời lượng
    $episode = $db->get("
        SELECT * FROM episodes WHERE id = :id
    ", ['id' => $episodeId]);
    
    if (!$episode) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tập phim'
        ]);
        exit;
    }
    
    // Dữ liệu cần cập nhật hoặc thêm mới
    $progressData = [
        'user_id' => $userId,
        'movie_id' => $movieId,
        'episode_id' => $episodeId,
        'is_completed' => true,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Nếu có thời lượng từ tập phim, cập nhật
    if (!empty($episode['duration'])) {
        $progressData['duration'] = $episode['duration'];
        $progressData['watch_time'] = $episode['duration']; // Đánh dấu là đã xem hết
    }
    
    // Thêm mới hoặc cập nhật
    if ($watchProgress) {
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
                'is_completed' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $db->update('watch_history', [
                'episode_id' => $episodeId,
                'is_completed' => true,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $historyExists['id']]);
        }
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Đã đánh dấu tập phim là đã xem',
        'is_completed' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}