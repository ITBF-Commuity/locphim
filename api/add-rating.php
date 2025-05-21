<?php
/**
 * Lọc Phim - API đánh giá phim
 * 
 * API này thêm/cập nhật đánh giá của người dùng cho phim
 */

require_once '../includes/init.php';

// Kiểm tra phương thức yêu cầu
check_api_request('POST');

// Lấy dữ liệu từ yêu cầu
$data = get_json_data();

// Kiểm tra đăng nhập
if (!$currentUser) {
    json_response(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá phim'], 401);
}

// Kiểm tra dữ liệu đầu vào
$movieId = isset($data['movie_id']) ? (int)$data['movie_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;

if (!$movieId || $rating < 1 || $rating > 5) {
    json_response(['success' => false, 'message' => 'Thông tin không hợp lệ'], 400);
}

// Kiểm tra phim có tồn tại không
$movie = $db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);

if (!$movie) {
    json_response(['success' => false, 'message' => 'Phim không tồn tại'], 404);
}

// Kiểm tra đánh giá đã tồn tại chưa
$existingRating = $db->get(
    "SELECT * FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id",
    [
        'user_id' => $currentUser['id'],
        'movie_id' => $movieId
    ]
);

if ($existingRating) {
    // Cập nhật đánh giá
    $db->update('ratings', [
        'rating' => $rating,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = :id', [
        'id' => $existingRating['id']
    ]);
} else {
    // Tạo đánh giá mới
    $db->insert('ratings', [
        'user_id' => $currentUser['id'],
        'movie_id' => $movieId,
        'rating' => $rating,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

// Cập nhật điểm đánh giá trung bình của phim
$avgRating = $db->getOne(
    "SELECT AVG(rating) FROM ratings WHERE movie_id = :movie_id",
    ['movie_id' => $movieId]
);

$db->update('movies', [
    'rating' => $avgRating,
    'updated_at' => date('Y-m-d H:i:s')
], 'id = :id', [
    'id' => $movieId
]);

// Trả về kết quả
json_response([
    'success' => true,
    'rating' => $rating,
    'avg_rating' => round($avgRating, 1),
    'message' => 'Đánh giá của bạn đã được ghi nhận'
]);