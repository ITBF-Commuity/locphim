<?php
// Trang đăng xuất

// Bắt đầu session
session_start();

// Xóa tất cả dữ liệu session
$_SESSION = array();

// Xóa cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Xóa cookie remember_token
setcookie('remember_token', '', time() - 3600, '/');

// Chuyển hướng về trang đăng nhập
header('Location: login.php');
exit;
?>