<?php
/**
 * Lọc Phim - API thêm bình luận
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
if (!isset($input['movie_id']) || !isset($input['content'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Xác thực người dùng
session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

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
    // Kiểm tra nội dung bình luận
    $content = trim($input['content']);
    if (empty($content)) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Comment content cannot be empty']);
        exit;
    }
    
    // Kiểm tra độ dài bình luận
    if (mb_strlen($content) > 1000) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Comment content too long (max 1000 characters)']);
        exit;
    }
    
    // Xử lý reply
    $parentId = null;
    if (preg_match('/^@reply:(\d+)\s+(.+)$/s', $content, $matches)) {
        $parentId = $matches[1];
        $content = $matches[2];
        
        // Kiểm tra tồn tại của comment cha
        $parentComment = $db->get("
            SELECT * FROM comments 
            WHERE id = :id AND movie_id = :movie_id
        ", [
            'id' => $parentId,
            'movie_id' => $input['movie_id']
        ]);
        
        if (!$parentComment) {
            $parentId = null; // Nếu không tìm thấy comment cha, thì coi như comment mới
        }
    }
    
    // Lọc nội dung nhạy cảm
    $content = filterSensitiveContent($content);
    
    // Thêm bình luận mới
    $db->query("
        INSERT INTO comments (user_id, movie_id, episode_id, parent_id, content)
        VALUES (:user_id, :movie_id, :episode_id, :parent_id, :content)
    ", [
        'user_id' => $userId,
        'movie_id' => $input['movie_id'],
        'episode_id' => isset($input['episode_id']) ? $input['episode_id'] : null,
        'parent_id' => $parentId,
        'content' => $content
    ]);
    
    // Lấy ID bình luận vừa thêm
    $commentId = $db->lastInsertId();
    
    // Lấy thông tin người dùng
    $user = $db->get("
        SELECT username, avatar FROM users
        WHERE id = :id
    ", ['id' => $userId]);
    
    // Tạo response
    $response = [
        'success' => true,
        'message' => 'Comment added successfully',
        'comment' => [
            'id' => $commentId,
            'user_id' => $userId,
            'username' => $user['username'],
            'avatar' => $user['avatar'],
            'movie_id' => $input['movie_id'],
            'episode_id' => isset($input['episode_id']) ? $input['episode_id'] : null,
            'parent_id' => $parentId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Ghi log lỗi
    error_log('Error adding comment: ' . $e->getMessage());
    
    // Trả về thông báo lỗi
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

/**
 * Lọc nội dung nhạy cảm
 * 
 * @param string $content Nội dung cần lọc
 * @return string Nội dung đã lọc
 */
function filterSensitiveContent($content) {
    // Danh sách từ khóa nhạy cảm
    $sensitiveWords = [
        // Từ chửi tục, ngôn từ xúc phạm
        'đụ', 'địt', 'đéo', 'đm', 'đmm', 'dmm', 'clm', 'cặc', 'lồn', 'buồi', 'dái', 
        'đít', 'đĩ', 'cave', 'thằng chó', 'con chó', 'mẹ mày', 'cmm', 'vcl', 'vl', 'cc',
        
        // Từ ngữ phản cảm tiếng Anh
        'fuck', 'shit', 'bitch', 'dick', 'pussy', 'asshole', 'cunt', 'bastard', 'motherfucker'
    ];
    
    // Thay thế từ nhạy cảm bằng dấu *
    foreach ($sensitiveWords as $word) {
        // Thay thế cả từ có dấu và không dấu
        $pattern = '/\b' . preg_quote($word, '/') . '\b/ui';
        $replacement = str_repeat('*', mb_strlen($word));
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    return $content;
}