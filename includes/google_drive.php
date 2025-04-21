<?php
/**
 * Xử lý Google Drive
 * 
 * Chứa các hàm liên quan đến tích hợp Google Drive
 */

/**
 * Lấy Google API key từ cấu hình
 * 
 * @return string|null API key hoặc null nếu không tìm thấy
 */
function get_google_api_key() {
    $config_file = __DIR__ . '/../config.json';
    
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
        return $config['google_api_key'] ?? null;
    }
    
    return null;
}

/**
 * Lấy thông tin về file Google Drive
 * 
 * @param string $drive_id ID của file Google Drive
 * @return array|null Thông tin file hoặc null nếu không tìm thấy
 */
function get_google_drive_file_info($drive_id) {
    $api_key = get_google_api_key();
    
    if (!$api_key) {
        return null;
    }
    
    $api_url = "https://www.googleapis.com/drive/v3/files/{$drive_id}?fields=id,name,mimeType,size&key={$api_key}";
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Lấy URL trực tiếp từ Google Drive
 * 
 * @param string $drive_id ID của file Google Drive
 * @return string|null URL trực tiếp hoặc null nếu không thể lấy
 */
function get_google_drive_direct_url($drive_id) {
    // Tạo URL để tải xuống file từ Google Drive
    $download_url = "https://drive.google.com/uc?export=download&id={$drive_id}";
    
    // Khởi tạo session curl
    $ch = curl_init();
    
    // Thiết lập các tùy chọn curl
    curl_setopt($ch, CURLOPT_URL, $download_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '');
    
    // Thực hiện request để lấy cookies
    $response = curl_exec($ch);
    
    // Kiểm tra nếu cần xác nhận tải xuống (đối với file lớn)
    if (strpos($response, 'NID=') !== false) {
        // Lấy URL xác nhận từ response
        preg_match('/Location: (.+)/i', $response, $matches);
        if (isset($matches[1])) {
            $confirm_url = trim($matches[1]);
            
            // Thiết lập lại URL
            curl_setopt($ch, CURLOPT_URL, $confirm_url);
            
            // Thực hiện request thứ hai
            $response = curl_exec($ch);
            
            // Lấy URL cuối cùng
            preg_match('/Location: (.+)/i', $response, $matches);
            if (isset($matches[1])) {
                curl_close($ch);
                return trim($matches[1]);
            }
        }
    } else {
        // Đối với file nhỏ, lấy URL trực tiếp
        $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        return $redirect_url;
    }
    
    curl_close($ch);
    return null;
}

/**
 * Thêm nguồn Google Drive cho một tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $drive_id ID của file Google Drive
 * @param string $quality Chất lượng video (360, 480, 720, 1080, 4k)
 * @return bool Kết quả thực hiện
 */
function add_google_drive_source($episode_id, $drive_id, $quality) {
    global $db_type;
    
    // Kiểm tra xem tập phim có tồn tại không
    $episode = db_fetch_one("SELECT * FROM episodes WHERE id = ?", [$episode_id]);
    
    if (!$episode) {
        return false;
    }
    
    // Cập nhật nguồn và loại nguồn
    $quality_column = "source_{$quality}";
    $type_column = "source_{$quality}_type";
    
    // Tạo câu lệnh SQL phù hợp với loại database
    if ($db_type === 'sqlite') {
        $sql = "UPDATE episodes SET {$quality_column} = ?, {$type_column} = 'google_drive' WHERE id = ?";
    } else {
        $sql = "UPDATE episodes SET {$quality_column} = ?, {$type_column} = 'google_drive' WHERE id = ?";
    }
    
    // Thực hiện câu lệnh SQL
    return db_execute($sql, [$drive_id, $episode_id]);
}

/**
 * Lấy danh sách các tập phim sử dụng nguồn Google Drive
 * 
 * @return array Danh sách các tập phim
 */
function get_google_drive_episodes() {
    $sql = "SELECT e.id, e.episode_number, e.title, m.id as movie_id, m.title as movie_title, 
                   e.source_360, e.source_360_type, 
                   e.source_480, e.source_480_type, 
                   e.source_720, e.source_720_type, 
                   e.source_1080, e.source_1080_type, 
                   e.source_4k, e.source_4k_type
            FROM episodes e
            JOIN movies m ON e.movie_id = m.id
            WHERE e.source_360_type = 'google_drive' 
               OR e.source_480_type = 'google_drive'
               OR e.source_720_type = 'google_drive'
               OR e.source_1080_type = 'google_drive'
               OR e.source_4k_type = 'google_drive'
            ORDER BY m.title ASC, e.episode_number ASC";
    
    return db_fetch_all($sql);
}

/**
 * Xóa nguồn Google Drive cho một tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $quality Chất lượng video (360, 480, 720, 1080, 4k)
 * @return bool Kết quả thực hiện
 */
function remove_google_drive_source($episode_id, $quality) {
    // Kiểm tra xem tập phim có tồn tại không
    $episode = db_fetch_one("SELECT * FROM episodes WHERE id = ?", [$episode_id]);
    
    if (!$episode) {
        return false;
    }
    
    // Cập nhật nguồn và loại nguồn
    $quality_column = "source_{$quality}";
    $type_column = "source_{$quality}_type";
    
    // Tạo câu lệnh SQL 
    $sql = "UPDATE episodes SET {$quality_column} = NULL, {$type_column} = 'direct' WHERE id = ?";
    
    // Thực hiện câu lệnh SQL
    return db_execute($sql, [$episode_id]);
}