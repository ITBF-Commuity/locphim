<?php
/**
 * API Thêm/xóa phim yêu thích
 * 
 * Endpoint này cho phép người dùng thêm hoặc xóa phim khỏi danh sách yêu thích
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
if (!isset($data['movie_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$movieId = (int)$data['movie_id'];

// Kiểm tra ID phim hợp lệ
if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID phim không hợp lệ'
    ]);
    exit;
}

try {
    // Kiểm tra phim có tồn tại không
    $movie = $db->get("SELECT id, title FROM movies WHERE id = :id", ['id' => $movieId]);
    
    if (!$movie) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy phim'
        ]);
        exit;
    }
    
    // Kiểm tra xem đã thêm vào yêu thích chưa
    $favorite = $db->get("
        SELECT * FROM favorites 
        WHERE user_id = :user_id AND movie_id = :movie_id
    ", [
        'user_id' => $userId,
        'movie_id' => $movieId
    ]);
    
    if ($favorite) {
        // Đã có trong danh sách yêu thích, xóa đi
        $db->delete('favorites', ['id' => $favorite['id']]);
        
        // Trả về kết quả
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa khỏi danh sách yêu thích',
            'is_favorite' => false
        ]);
    } else {
        // Chưa có trong danh sách yêu thích, thêm vào
        $db->insert('favorites', [
            'user_id' => $userId,
            'movie_id' => $movieId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Trả về kết quả
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào danh sách yêu thích',
            'is_favorite' => true
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}