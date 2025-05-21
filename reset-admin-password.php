<?php
/**
 * Script để đặt lại mật khẩu admin
 */

// Bao gồm file cấu hình và database
require_once 'config.php';
require_once 'includes/database.php';

// Mật khẩu mới
$newPassword = 'admin123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Khởi tạo database
$db = new Database();

// Cập nhật mật khẩu cho admin
$admin = $db->get("SELECT * FROM users WHERE username = ? OR email = ?", ['admin', 'admin@example.com']);

if ($admin) {
    echo "Đang đặt lại mật khẩu cho admin...\n";
    
    $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $admin['id']]);
    
    echo "Đã đặt lại mật khẩu thành công.\n";
    echo "Username: admin\n";
    echo "Password: $newPassword\n";
} else {
    echo "Không tìm thấy tài khoản admin!\n";
}

echo "Hoàn thành!";
?>