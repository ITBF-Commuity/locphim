<?php
/**
 * Trang đăng xuất quản trị
 * Lọc Phim - Admin Panel
 */

// Kết nối hệ thống xác thực admin
require_once __DIR__ . '/includes/auth.php';

// Ghi log đăng xuất nếu đã đăng nhập
if (is_admin_logged_in()) {
    log_admin_action('logout', 'Đăng xuất khỏi hệ thống quản trị');
}

// Thực hiện đăng xuất
admin_logout();

// Chuyển hướng về trang đăng nhập
header('Location: login.php');
exit;