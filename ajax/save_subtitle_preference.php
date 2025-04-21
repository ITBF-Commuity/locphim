<?php
/**
 * Lưu cài đặt phụ đề của người dùng
 */

// Bao gồm các file cần thiết
require_once '../config.php';
require_once '../db_connect.php';
require_once '../functions.php';
require_once '../auth.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy thông tin người dùng hiện tại
$current_user = get_logged_in_user();

// Kiểm tra tham số bắt buộc
if (!isset($_POST['movie_id']) || !isset($_POST['episode_id']) || !isset($_POST['subtitle'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

$movie_id = intval($_POST['movie_id']);
$episode_id = intval($_POST['episode_id']);
$subtitle = $_POST['subtitle'];

// Kiểm tra xem đã có bản ghi cài đặt chưa
$existing = db_fetch_one("SELECT * FROM user_preferences WHERE user_id = ? AND movie_id = ? AND episode_id = ?", 
                      [$current_user['id'], $movie_id, $episode_id]);

if ($existing) {
    // Cập nhật cài đặt hiện có
    db_execute("UPDATE user_preferences SET subtitle_language = ?, updated_at = NOW() 
              WHERE user_id = ? AND movie_id = ? AND episode_id = ?",
              [$subtitle, $current_user['id'], $movie_id, $episode_id]);
} else {
    // Tạo bản ghi cài đặt mới
    db_execute("INSERT INTO user_preferences (user_id, movie_id, episode_id, subtitle_language, created_at, updated_at) 
              VALUES (?, ?, ?, ?, NOW(), NOW())",
              [$current_user['id'], $movie_id, $episode_id, $subtitle]);
}

// Cập nhật cài đặt mặc định cho tập phim
db_execute("UPDATE episodes SET default_subtitle = ? WHERE id = ?", [$subtitle, $episode_id]);

// Trả về kết quả thành công
header('Content-Type: application/json');
echo json_encode(['success' => true]);