<?php
/**
 * Tệp chứa các hàm quản lý phụ đề và ngôn ngữ âm thanh
 */

/**
 * Lấy danh sách phụ đề cho một tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @return array Danh sách phụ đề
 */
function get_subtitles($episode_id) {
    $query = "SELECT * FROM subtitles WHERE episode_id = ? ORDER BY language_name ASC";
    return db_fetch_all($query, [$episode_id]);
}

/**
 * Lấy thông tin phụ đề theo ngôn ngữ
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ
 * @return array|null Thông tin phụ đề hoặc null nếu không tìm thấy
 */
function get_subtitle_by_language($episode_id, $language_code) {
    $query = "SELECT * FROM subtitles WHERE episode_id = ? AND language_code = ?";
    return db_query_single($query, [$episode_id, $language_code]);
}

/**
 * Thêm hoặc cập nhật phụ đề cho tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ (vi, en, jp...)
 * @param string $language_name Tên ngôn ngữ (Tiếng Việt, English...)
 * @param string $subtitle_file Đường dẫn đến file phụ đề
 * @return int|bool ID của phụ đề đã thêm/cập nhật hoặc false nếu thất bại
 */
function add_or_update_subtitle($episode_id, $language_code, $language_name, $subtitle_file) {
    // Kiểm tra xem phụ đề đã tồn tại chưa
    $existing = get_subtitle_by_language($episode_id, $language_code);
    
    $now = date('Y-m-d H:i:s');
    
    if ($existing) {
        // Cập nhật phụ đề hiện có
        $query = "UPDATE subtitles SET 
                  language_name = ?, 
                  subtitle_file = ?, 
                  updated_at = ? 
                  WHERE id = ?";
        
        db_query($query, [$language_name, $subtitle_file, $now, $existing['id']]);
        return $existing['id'];
    } else {
        // Thêm phụ đề mới
        $query = "INSERT INTO subtitles 
                  (episode_id, language_code, language_name, subtitle_file, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        db_query($query, [$episode_id, $language_code, $language_name, $subtitle_file, $now, $now]);
        return db_last_insert_id();
    }
}

/**
 * Xóa phụ đề
 * 
 * @param int $subtitle_id ID của phụ đề
 * @return bool true nếu xóa thành công, false nếu thất bại
 */
function delete_subtitle($subtitle_id) {
    // Lấy thông tin phụ đề trước khi xóa
    $subtitle = db_query_single("SELECT * FROM subtitles WHERE id = ?", [$subtitle_id]);
    if (!$subtitle) {
        return false;
    }
    
    // Xóa file phụ đề nếu cần
    $file_path = __DIR__ . '/../' . $subtitle['subtitle_file'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Xóa bản ghi từ cơ sở dữ liệu
    db_query("DELETE FROM subtitles WHERE id = ?", [$subtitle_id]);
    return true;
}

/**
 * Lấy danh sách ngôn ngữ âm thanh cho một tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @return array Danh sách ngôn ngữ âm thanh
 */
function get_audio_tracks($episode_id) {
    $query = "SELECT * FROM audio_tracks WHERE episode_id = ? ORDER BY language_name ASC";
    return db_fetch_all($query, [$episode_id]);
}

/**
 * Lấy thông tin ngôn ngữ âm thanh theo mã ngôn ngữ
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ
 * @return array|null Thông tin ngôn ngữ âm thanh hoặc null nếu không tìm thấy
 */
function get_audio_track_by_language($episode_id, $language_code) {
    $query = "SELECT * FROM audio_tracks WHERE episode_id = ? AND language_code = ?";
    return db_query_single($query, [$episode_id, $language_code]);
}

/**
 * Thêm hoặc cập nhật ngôn ngữ âm thanh cho tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ (vi, en, jp...)
 * @param string $language_name Tên ngôn ngữ (Tiếng Việt, English...)
 * @param string $audio_url Đường dẫn đến file âm thanh
 * @return int|bool ID của ngôn ngữ âm thanh đã thêm/cập nhật hoặc false nếu thất bại
 */
function add_or_update_audio_track($episode_id, $language_code, $language_name, $audio_url) {
    // Kiểm tra xem ngôn ngữ âm thanh đã tồn tại chưa
    $existing = get_audio_track_by_language($episode_id, $language_code);
    
    $now = date('Y-m-d H:i:s');
    
    if ($existing) {
        // Cập nhật ngôn ngữ âm thanh hiện có
        $query = "UPDATE audio_tracks SET 
                  language_name = ?, 
                  audio_url = ?, 
                  updated_at = ? 
                  WHERE id = ?";
        
        db_query($query, [$language_name, $audio_url, $now, $existing['id']]);
        return $existing['id'];
    } else {
        // Thêm ngôn ngữ âm thanh mới
        $query = "INSERT INTO audio_tracks 
                  (episode_id, language_code, language_name, audio_url, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        db_query($query, [$episode_id, $language_code, $language_name, $audio_url, $now, $now]);
        return db_last_insert_id();
    }
}

/**
 * Xóa ngôn ngữ âm thanh
 * 
 * @param int $audio_id ID của ngôn ngữ âm thanh
 * @return bool true nếu xóa thành công, false nếu thất bại
 */
function delete_audio_track($audio_id) {
    // Xóa bản ghi từ cơ sở dữ liệu
    db_query("DELETE FROM audio_tracks WHERE id = ?", [$audio_id]);
    return true;
}

/**
 * Cập nhật ngôn ngữ phụ đề mặc định cho tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ phụ đề mặc định
 * @return bool true nếu cập nhật thành công, false nếu thất bại
 */
function set_default_subtitle($episode_id, $language_code) {
    $query = "UPDATE episodes SET default_subtitle = ? WHERE id = ?";
    db_query($query, [$language_code, $episode_id]);
    return true;
}

/**
 * Cập nhật ngôn ngữ âm thanh mặc định cho tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ âm thanh mặc định
 * @return bool true nếu cập nhật thành công, false nếu thất bại
 */
function set_default_audio($episode_id, $language_code) {
    $query = "UPDATE episodes SET default_audio = ? WHERE id = ?";
    db_query($query, [$language_code, $episode_id]);
    return true;
}

/**
 * Lấy danh sách ngôn ngữ phổ biến
 * 
 * @return array Danh sách ngôn ngữ phổ biến
 */
function get_common_languages() {
    return [
        'vi' => 'Tiếng Việt',
        'en' => 'Tiếng Anh',
        'fr' => 'Tiếng Pháp',
        'de' => 'Tiếng Đức',
        'ja' => 'Tiếng Nhật',
        'ko' => 'Tiếng Hàn',
        'zh' => 'Tiếng Trung',
        'es' => 'Tiếng Tây Ban Nha',
        'ru' => 'Tiếng Nga',
        'th' => 'Tiếng Thái'
    ];
}

/**
 * Lấy tùy chọn phụ đề và âm thanh của người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param int $movie_id ID của phim
 * @param int $episode_id ID của tập phim
 * @return array|null Tùy chọn phụ đề và âm thanh của người dùng hoặc null nếu không tìm thấy
 */
function get_user_subtitle_audio_preferences($user_id, $movie_id, $episode_id) {
    $query = "SELECT * FROM user_preferences 
              WHERE user_id = ? AND movie_id = ? AND episode_id = ?";
    return db_query_single($query, [$user_id, $movie_id, $episode_id]);
}

/**
 * Tải phụ đề lên server
 * 
 * @param array $file Thông tin file từ $_FILES
 * @param int $episode_id ID của tập phim
 * @param string $language_code Mã ngôn ngữ
 * @return string|bool Đường dẫn đến file phụ đề hoặc false nếu tải lên thất bại
 */
function upload_subtitle_file($file, $episode_id, $language_code) {
    // Tạo thư mục nếu chưa tồn tại
    $target_dir = __DIR__ . '/../uploads/subtitles/' . $episode_id;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Xác định đuôi file
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['vtt', 'srt', 'ass', 'ssa'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    // Tạo tên file mới
    $new_filename = $episode_id . '_' . $language_code . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . '/' . $new_filename;
    
    // Di chuyển file tải lên vào thư mục đích
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'uploads/subtitles/' . $episode_id . '/' . $new_filename;
    }
    
    return false;
}