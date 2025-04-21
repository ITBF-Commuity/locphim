<?php
/**
 * Lấy tiến độ xem phim/anime
 * 
 * File này xử lý yêu cầu AJAX để lấy tiến độ xem video
 */

// Khởi tạo session và các cài đặt cơ bản
require_once '../init.php';

// Khai báo header JSON
header('Content-Type: application/json');

// Kiểm tra tham số
if (!isset($_GET['episode_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin tập phim'
    ]);
    exit;
}

$episode_id = intval($_GET['episode_id']);

// Kiểm tra đăng nhập
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode([
        'success' => false,
        'message' => 'Người dùng chưa đăng nhập'
    ]);
    exit;
}

$user_id = $current_user['id'];

// Kiểm tra xem tập phim có tồn tại không
$episode = db_fetch_row("SELECT * FROM episodes WHERE id = ? AND status = 1", [$episode_id]);

if (!$episode) {
    echo json_encode([
        'success' => false,
        'message' => 'Tập phim không tồn tại hoặc đã bị gỡ bỏ'
    ]);
    exit;
}

// Lấy tiến độ xem
$history = db_fetch_row(
    "SELECT * FROM watch_history WHERE user_id = ? AND episode_id = ?", 
    [$user_id, $episode_id]
);

if ($history) {
    // Tính toán thời lượng tối đa từ thông tin tập phim
    $duration = $episode['duration'] > 0 ? $episode['duration'] : 0;
    
    echo json_encode([
        'success' => true,
        'progress' => floatval($history['progress']),
        'duration' => $duration,
        'watched_at' => $history['watched_at']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa có tiến độ xem'
    ]);
}