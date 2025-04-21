<?php
/**
 * AJAX xử lý thông báo
 */

// Bao gồm các file cần thiết
require_once '../init.php';
require_once '../config.php';
require_once '../db_connect.php';
require_once '../functions.php';
require_once '../auth.php';

// Kiểm tra đăng nhập
$current_user = get_logged_in_user();
if (!$current_user) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập để sử dụng tính năng này']);
    exit;
}

// Kiểm tra phương thức request
$method = $_SERVER['REQUEST_METHOD'];

// Lấy danh sách thông báo
if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $notifications = get_user_notifications($current_user['id'], $limit, $offset);
    
    // Format thời gian
    foreach ($notifications as &$notification) {
        $notification['formatted_time'] = format_time($notification['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => count_unread_notifications($current_user['id'])
    ]);
    exit;
}

// Đánh dấu thông báo đã đọc
if ($method === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'mark_read' && isset($_POST['notification_id'])) {
        $notification_id = intval($_POST['notification_id']);
        $result = mark_notification_as_read($notification_id, $current_user['id']);
        
        echo json_encode([
            'success' => $result > 0,
            'unread_count' => count_unread_notifications($current_user['id'])
        ]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $result = mark_all_notifications_as_read($current_user['id']);
        
        echo json_encode([
            'success' => true,
            'unread_count' => 0
        ]);
        exit;
    }
}

// Phương thức không được hỗ trợ
http_response_code(405);
echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
exit;