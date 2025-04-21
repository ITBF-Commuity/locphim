<?php
/**
 * Thêm/Xóa phim yêu thích
 * 
 * File này xử lý yêu cầu AJAX để thêm hoặc xóa phim khỏi danh sách yêu thích
 */

// Khởi tạo session và các cài đặt cơ bản
require_once '../init.php';

// Khai báo header JSON
header('Content-Type: application/json');

// Kiểm tra đăng nhập
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để sử dụng chức năng này.'
    ]);
    exit;
}

// Kiểm tra tham số movie_id
if (!isset($_POST['movie_id']) || !is_numeric($_POST['movie_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin phim.'
    ]);
    exit;
}

$user_id = $current_user['id'];
$movie_id = intval($_POST['movie_id']);

// Kiểm tra xem phim có tồn tại không
$movie = db_fetch_row("SELECT * FROM movies WHERE id = ? AND status = 1", [$movie_id]);

if (!$movie) {
    echo json_encode([
        'success' => false,
        'message' => 'Phim không tồn tại hoặc đã bị gỡ bỏ.'
    ]);
    exit;
}

// Kiểm tra xem phim đã có trong danh sách yêu thích chưa
$favorite = db_fetch_row(
    "SELECT * FROM favorites WHERE user_id = ? AND movie_id = ?", 
    [$user_id, $movie_id]
);

if ($favorite) {
    // Nếu đã có, xóa khỏi danh sách yêu thích
    db_query(
        "DELETE FROM favorites WHERE user_id = ? AND movie_id = ?", 
        [$user_id, $movie_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa khỏi danh sách yêu thích',
        'is_favorite' => false
    ]);
} else {
    // Nếu chưa có, thêm vào danh sách yêu thích
    db_query(
        "INSERT INTO favorites (user_id, movie_id, created_at) VALUES (?, ?, datetime('now'))",
        [$user_id, $movie_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vào danh sách yêu thích',
        'is_favorite' => true
    ]);
}