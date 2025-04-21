<?php
/**
 * Lưu tiến độ xem phim/anime
 * 
 * File này xử lý yêu cầu AJAX để lưu tiến độ xem video
 */

// Khởi tạo session và các cài đặt cơ bản
require_once '../init.php';

// Khai báo header JSON
header('Content-Type: application/json');

// Kiểm tra tham số
if (!isset($_POST['episode_id']) || !isset($_POST['movie_id']) || !isset($_POST['progress'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]);
    exit;
}

$episode_id = intval($_POST['episode_id']);
$movie_id = intval($_POST['movie_id']);
$progress = floatval($_POST['progress']);
$duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;

// Kiểm tra xem tập phim có tồn tại không
$episode = db_fetch_row("SELECT * FROM episodes WHERE id = ? AND status = 1", [$episode_id]);

if (!$episode) {
    echo json_encode([
        'success' => false,
        'message' => 'Tập phim không tồn tại hoặc đã bị gỡ bỏ'
    ]);
    exit;
}

// Lưu tiến độ cho người dùng đã đăng nhập
$current_user = get_current_user_info();
if ($current_user) {
    $user_id = $current_user['id'];
    
    // Kiểm tra xem đã có bản ghi tiến độ hay chưa
    $history = db_fetch_row(
        "SELECT * FROM watch_history WHERE user_id = ? AND episode_id = ?", 
        [$user_id, $episode_id]
    );
    
    if ($history) {
        // Cập nhật tiến độ
        db_query(
            "UPDATE watch_history SET progress = ?, watched_at = NOW() WHERE id = ?",
            [$progress, $history['id']]
        );
    } else {
        // Tạo bản ghi tiến độ mới
        db_query(
            "INSERT INTO watch_history (user_id, movie_id, episode_id, progress, watched_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$user_id, $movie_id, $episode_id, $progress]
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu tiến độ xem thành công'
    ]);
} else {
    // Đối với người dùng chưa đăng nhập, vẫn trả về thành công
    // Tiến độ đã được lưu bởi JavaScript trong localStorage
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu tiến độ xem vào localStorage'
    ]);
}