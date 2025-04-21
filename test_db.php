<?php
require_once 'config.php';
require_once 'db_connect.php';

try {
    // Kết nối đến cơ sở dữ liệu
    $pdo = db_connect();
    echo "Kết nối cơ sở dữ liệu thành công!<br>";
    
    // Hiển thị thông tin cơ sở dữ liệu
    echo "Loại cơ sở dữ liệu: " . DB_TYPE . "<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    
    // Kiểm tra một số bảng
    $tables = ['users', 'categories', 'movies', 'episodes'];
    
    foreach ($tables as $table) {
        $query = "SELECT COUNT(*) FROM $table";
        $count = db_fetch_value($query);
        echo "Số bản ghi trong bảng $table: $count<br>";
    }
    
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>