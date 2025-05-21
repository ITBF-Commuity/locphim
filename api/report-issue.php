<?php
/**
 * Lọc Phim - API báo cáo lỗi phim
 */

// Bao gồm các file cần thiết
require_once '../config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/database.php';

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($input['movie_id']) || !isset($input['reason'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Xác thực người dùng
session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Kết nối cơ sở dữ liệu
$db = new Database();

try {
    // Tạo báo cáo lỗi mới
    $db->query("
        INSERT INTO reports (movie_id, episode_id, user_id, reason, status)
        VALUES (:movie_id, :episode_id, :user_id, :reason, 'pending')
    ", [
        'movie_id' => $input['movie_id'],
        'episode_id' => isset($input['episode_id']) ? $input['episode_id'] : null,
        'user_id' => $userId,
        'reason' => $input['reason']
    ]);
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Báo cáo đã được gửi thành công'
    ]);
} catch (Exception $e) {
    // Ghi log lỗi
    error_log('Error reporting issue: ' . $e->getMessage());
    
    // Trả về thông báo lỗi
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}