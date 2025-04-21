<?php
/**
 * API proxy cho Google Drive
 * 
 * File này giúp proxy các yêu cầu video từ Google Drive
 * để tránh các hạn chế truy cập và lưu lượng của Google
 */

// Bao gồm các file cần thiết
require_once '../config.php';
require_once '../functions.php';
require_once '../includes/google_drive.php';

// Thiết lập header để cho phép CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Kiểm tra nếu không phải phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Kiểm tra parameter drive_id
if (!isset($_GET['drive_id']) || empty($_GET['drive_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Thiếu ID Google Drive']);
    exit;
}

$drive_id = $_GET['drive_id'];

// Lấy URL trực tiếp từ Google Drive
try {
    $directUrl = get_google_drive_direct_url($drive_id);
    
    if ($directUrl) {
        // Nếu chỉ muốn trả về URL mà không proxy
        if (isset($_GET['redirect']) && $_GET['redirect'] === 'true') {
            // Chuyển hướng đến URL trực tiếp
            header('Location: ' . $directUrl);
            exit;
        } else {
            // Proxy nội dung video
            
            // Set header để báo là video
            header('Content-Type: video/mp4');
            header('Content-Disposition: inline; filename="video.mp4"');
            
            // Gửi dữ liệu video từ Google Drive về client
            readfile($directUrl);
            exit;
        }
    } else {
        // Không thể lấy URL trực tiếp
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Không thể lấy URL video từ Google Drive']);
        exit;
    }
} catch (Exception $e) {
    // Lỗi xử lý
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Lỗi khi xử lý yêu cầu: ' . $e->getMessage()]);
    exit;
}