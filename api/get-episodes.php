<?php
/**
 * API Lấy danh sách tập phim
 * 
 * Endpoint này trả về danh sách tập phim dựa trên ID phim
 */

// Bao gồm các file cần thiết
require_once '../includes/init.php';

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Lấy tham số
$movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

// Kiểm tra tham số
if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID phim không hợp lệ'
    ]);
    exit;
}

try {
    // Lấy thông tin phim
    $movie = $db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
    
    // Kiểm tra phim có tồn tại không
    if (!$movie) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy phim'
        ]);
        exit;
    }
    
    // Lấy danh sách tập phim
    $episodes = $db->getAll("
        SELECT * FROM episodes 
        WHERE movie_id = :movie_id 
        ORDER BY episode_number ASC
    ", ['movie_id' => $movieId]);
    
    // Kiểm tra có tập phim không
    if (empty($episodes)) {
        echo json_encode([
            'success' => true,
            'count' => 0,
            'episodes' => []
        ]);
        exit;
    }
    
    // Nếu người dùng đã đăng nhập, lấy thông tin xem phim
    $watchProgress = [];
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        
        // Lấy tiến trình xem của tất cả tập phim
        $progress = $db->getAll("
            SELECT * FROM watch_progress 
            WHERE user_id = :user_id AND movie_id = :movie_id
        ", [
            'user_id' => $userId,
            'movie_id' => $movieId
        ]);
        
        // Chuyển đổi thành mảng với key là episode_id
        foreach ($progress as $item) {
            $watchProgress[$item['episode_id']] = $item;
        }
    }
    
    // Xử lý dữ liệu trả về
    $result = [];
    foreach ($episodes as $episode) {
        $episodeData = [
            'id' => $episode['id'],
            'title' => $episode['title'],
            'episode_number' => $episode['episode_number'],
            'duration' => $episode['duration'],
            'thumbnail' => $episode['thumbnail'] ?? '/assets/images/default-thumbnail.jpg',
            'views' => $episode['views'],
            'is_vip' => (bool)$episode['is_vip']
        ];
        
        // Thêm thông tin tiến trình xem nếu có
        if (isset($watchProgress[$episode['id']])) {
            $progress = $watchProgress[$episode['id']];
            $episodeData['watch_progress'] = [
                'watch_time' => $progress['watch_time'],
                'duration' => $progress['duration'],
                'is_completed' => (bool)$progress['is_completed'],
                'percent' => $progress['duration'] > 0 ? min(100, round(($progress['watch_time'] / $progress['duration']) * 100)) : 0
            ];
        }
        
        $result[] = $episodeData;
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'count' => count($result),
        'episodes' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}