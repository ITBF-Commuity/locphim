<?php
/**
 * Script để tạo tài khoản admin mặc định
 */

// Bao gồm file cấu hình và database
require_once 'config.php';
require_once 'includes/database.php';

// Khởi tạo database
$db = new Database();

// Kiểm tra xem đã có admin chưa
$admin = $db->get("SELECT * FROM users WHERE username = ? OR email = ?", ['admin', 'admin@example.com']);

if ($admin) {
    // Nếu đã có admin, cập nhật trạng thái
    echo "Admin đã tồn tại, đang cập nhật trạng thái...\n";
    
    if ($db->getDatabaseType() === 'pgsql') {
        // PostgreSQL không có cột status
        $db->execute("UPDATE users SET 
                    is_admin = TRUE, 
                    is_vip = TRUE
                    WHERE id = ?", [$admin['id']]);
    } else {
        // MySQL/SQLite có cột status
        $db->execute("UPDATE users SET 
                    status = 'active', 
                    is_admin = 1, 
                    is_vip = 1 
                    WHERE id = ?", [$admin['id']]);
    }
    
    echo "Đã cập nhật tài khoản admin thành công.\n";
} else {
    // Nếu chưa có, tạo mới
    echo "Đang tạo tài khoản admin mới...\n";
    
    // Mật khẩu mặc định: password
    $password = password_hash('password', PASSWORD_DEFAULT);
    
    if ($db->getDatabaseType() === 'pgsql') {
        // PostgreSQL không có cột status
        $db->execute("INSERT INTO users 
                    (username, email, phone, password, is_admin, is_vip, created_at, updated_at) 
                    VALUES 
                    (?, ?, ?, ?, TRUE, TRUE, NOW(), NOW())", 
                    ['admin', 'admin@example.com', '0123456789', $password]);
    } else {
        // MySQL/SQLite có cột status
        $db->execute("INSERT INTO users 
                    (username, email, phone, password, status, is_admin, is_vip, created_at, updated_at) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())", 
                    ['admin', 'admin@example.com', '0123456789', $password, 'active', 1, 1]);
    }
    
    echo "Đã tạo tài khoản admin thành công.\n";
    echo "Username: admin\n";
    echo "Password: password\n";
}

echo "Hoàn thành!";
?>