<?php
/**
 * Lọc Phim - Lưu tùy chọn giao diện theo mùa
 * File xử lý AJAX lưu tùy chọn giao diện theo mùa cho người dùng đã đăng nhập
 */

// Khởi tạo phiên làm việc
session_start();

// Bao gồm cấu hình và kết nối database
require_once '../init.php';

// Chỉ xử lý yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận phương thức POST']);
    exit;
}

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

// Lấy theme từ request
$theme = isset($_POST['theme']) ? trim($_POST['theme']) : 'none';

// Kiểm tra giá trị hợp lệ
$valid_themes = ['none', 'christmas', 'tet', 'halloween'];
if (!in_array($theme, $valid_themes)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Giao diện không hợp lệ']);
    exit;
}

// Kết nối database
try {
    $db = db_connect();
    
    // Kiểm tra xem người dùng đã có tùy chọn chưa
    $stmt = $db->prepare("SELECT id FROM user_preferences WHERE user_id = ? AND preference_type = 'seasonal_theme'");
    $stmt->execute([$_SESSION['user_id']]);
    $preference = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($preference) {
        // Cập nhật tùy chọn
        $stmt = $db->prepare("UPDATE user_preferences SET preference_value = ?, updated_at = NOW() WHERE user_id = ? AND preference_type = 'seasonal_theme'");
        $stmt->execute([$theme, $_SESSION['user_id']]);
    } else {
        // Thêm tùy chọn mới
        $stmt = $db->prepare("INSERT INTO user_preferences (user_id, preference_type, preference_value, created_at, updated_at) VALUES (?, 'seasonal_theme', ?, NOW(), NOW())");
        $stmt->execute([$_SESSION['user_id'], $theme]);
    }
    
    // Cập nhật phiên làm việc
    $_SESSION['seasonal_theme'] = $theme;
    
    // Phản hồi thành công
    echo json_encode(['success' => true, 'message' => 'Đã lưu tùy chọn giao diện theo mùa']);
} catch (PDOException $e) {
    // Xử lý lỗi
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu tùy chọn: ' . $e->getMessage()]);
}