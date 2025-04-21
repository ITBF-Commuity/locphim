<?php
/**
 * API lấy danh sách tập phim
 */

// Bao gồm các file cần thiết
require_once '../config.php';
require_once '../db_connect.php';
require_once '../functions.php';

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Kiểm tra tham số movie_id
if (!isset($_GET['movie_id']) || empty($_GET['movie_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Thiếu ID phim']);
    exit;
}

$movie_id = intval($_GET['movie_id']);

// Kiểm tra phim tồn tại
$movie = db_fetch_one("SELECT * FROM movies WHERE id = ?", [$movie_id]);
if (!$movie) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Không tìm thấy phim']);
    exit;
}

// Lấy danh sách tập phim
$episodes = db_fetch_all(
    "SELECT id, episode_number, title, description, duration FROM episodes 
     WHERE movie_id = ? 
     ORDER BY episode_number ASC",
    [$movie_id]
);

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($episodes);