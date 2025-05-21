<?php
/**
 * Lọc Phim - API xóa bình luận
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
if (!isset($input['comment_id'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Xác thực người dùng
session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Kiểm tra người dùng đã đăng nhập chưa
if (!$userId) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Kết nối cơ sở dữ liệu
$db = new Database();

try {
    // Lấy thông tin bình luận
    $comment = $db->get("
        SELECT * FROM comments
        WHERE id = :id
    ", ['id' => $input['comment_id']]);
    
    if (!$comment) {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }
    
    // Kiểm tra quyền xóa bình luận
    if ($comment['user_id'] != $userId && !$isAdmin) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    // Xóa bình luận và các trả lời
    $db->query("
        DELETE FROM comments
        WHERE id = :id OR parent_id = :id
    ", ['id' => $input['comment_id']]);
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
} catch (Exception $e) {
    // Ghi log lỗi
    error_log('Error deleting comment: ' . $e->getMessage());
    
    // Trả về thông báo lỗi
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}