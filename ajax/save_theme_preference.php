<?php
/**
 * Lưu tùy chọn giao diện của người dùng
 * 
 * File này xử lý yêu cầu AJAX để lưu cài đặt theme của người dùng
 */

// Khởi tạo session và các cài đặt cơ bản
require_once '../init.php';

// Khai báo header JSON
header('Content-Type: application/json');

// Kiểm tra đăng nhập
$current_user = get_current_user_info();
if (!$current_user) {
    // Nếu chưa đăng nhập, chỉ lưu vào cookie
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'light';
    
    // Lưu cookie trong 30 ngày
    setcookie('theme', $theme, time() + 30 * 86400, '/');
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu tùy chọn vào cookie.'
    ]);
    exit;
}

// Nếu đã đăng nhập, lưu vào cơ sở dữ liệu
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    $user_id = $current_user['id'];
    
    // Kiểm tra xem đã có bản ghi cài đặt hay chưa
    $settings = db_fetch_row("SELECT * FROM user_settings WHERE user_id = ?", [$user_id]);
    
    if ($settings) {
        // Cập nhật cài đặt
        db_query(
            "UPDATE user_settings SET theme_preference = ?, updated_at = NOW() WHERE user_id = ?",
            [$theme, $user_id]
        );
    } else {
        // Tạo bản ghi cài đặt mới
        db_query(
            "INSERT INTO user_settings (user_id, theme_preference, created_at, updated_at) 
             VALUES (?, ?, NOW(), NOW())",
            [$user_id, $theme]
        );
    }
    
    // Đồng thời lưu vào cookie
    setcookie('theme', $theme, time() + 30 * 86400, '/');
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu tùy chọn giao diện.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin theme.'
    ]);
}