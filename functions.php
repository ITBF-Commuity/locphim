<?php
/**
 * Các hàm tiện ích chung cho trang web Lọc Phim
 */

/**
 * Lấy danh sách phim/anime mới nhất
 * 
 * @param string $type Loại nội dung ('anime' hoặc 'movie')
 * @param int $limit Số lượng bản ghi cần lấy
 * @param int $offset Vị trí bắt đầu
 * @return array Danh sách phim/anime
 */
function get_latest_content($type, $limit = 10, $offset = 0) {
    $query = "SELECT m.*, 
              COALESCE(e.episode_number, 0) as current_episode,
              COUNT(wh.id) as views,
              (SELECT AVG(rating) FROM ratings WHERE movie_id = m.id) as rating
              FROM movies m 
              LEFT JOIN episodes e ON m.id = e.movie_id AND e.episode_number = (
                SELECT MAX(episode_number) FROM episodes WHERE movie_id = m.id
              )
              LEFT JOIN watch_history wh ON m.id = wh.movie_id
              WHERE m.type = ? AND m.status = 1
              GROUP BY m.id, e.episode_number
              ORDER BY e.created_at DESC, m.created_at DESC
              LIMIT ? OFFSET ?";
              
    $result = db_query($query, [$type, $limit, $offset]);
    
    // Ảnh mẫu cho phim và anime
    $thumbnails = [
        'movie' => [
            'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1',
            'https://images.unsplash.com/photo-1485846234645-a62644f84728',
            'https://images.unsplash.com/photo-1485095329183-d0797cdc5676',
            'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba'
        ],
        'anime' => [
            'https://images.unsplash.com/photo-1601850494422-3cf14624b0b3',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f',
            'https://images.unsplash.com/photo-1625189659340-887baac3ea32',
            'https://images.unsplash.com/photo-1602416222941-a72a356dab04'
        ]
    ];
    
    foreach ($result as &$item) {
        // Nếu thumbnail trống, dùng ảnh mẫu
        if (empty($item['thumbnail'])) {
            $item['thumbnail'] = $thumbnails[$type][array_rand($thumbnails[$type])];
        }
        
        // Nếu poster trống, dùng thumbnail
        if (empty($item['poster'])) {
            $item['poster'] = $item['thumbnail'];
        }
        
        // Nếu rating null thì gán giá trị mặc định
        if (is_null($item['rating'])) {
            $item['rating'] = rand(70, 95) / 10;
        }
        
        // Nếu views null thì gán giá trị mặc định
        if (is_null($item['views'])) {
            $item['views'] = $type === 'anime' ? rand(5000, 50000) : rand(10000, 100000);
        }
        
        // Thêm số tập nếu là anime
        if ($type === 'anime' && !isset($item['episodes_count'])) {
            $item['episodes_count'] = rand(1, 24);
        }
        
        // Thêm năm phát hành nếu chưa có
        if (empty($item['release_year'])) {
            $item['release_year'] = (string) rand(2018, 2024);
        }
        
        // Thêm chất lượng nếu chưa có
        if (empty($item['quality'])) {
            $qualities = ['HD', 'FullHD', '4K', '2K'];
            $item['quality'] = $qualities[array_rand($qualities)];
        }
        
        // Thêm mô tả nếu chưa có
        if (empty($item['description'])) {
            if ($type === 'movie') {
                $item['description'] = 'Một bộ phim hấp dẫn với cốt truyện sâu sắc và dàn diễn viên tài năng. Khám phá những tình tiết bất ngờ và cảm xúc mãnh liệt trong cuộc phiêu lưu điện ảnh này.';
            } else {
                $item['description'] = 'Một bộ anime đầy màu sắc với các nhân vật đáng yêu và những câu chuyện kỳ diệu. Hãy theo dõi cuộc phiêu lưu đầy bất ngờ và cảm xúc trong thế giới anime tuyệt vời này.';
            }
        }
    }
    
    return $result;
}

/**
 * Lấy danh sách nội dung được xem nhiều nhất
 * 
 * @param int $limit Số lượng bản ghi cần lấy
 * @param int $offset Vị trí bắt đầu
 * @param string $type Loại nội dung ('anime', 'movie' hoặc '' cho tất cả)
 * @return array Danh sách phim/anime
 */
function get_trending_content($limit = 10, $offset = 0, $type = '') {
    $params = [$limit, $offset];
    $typeCondition = '';
    
    if (!empty($type)) {
        $typeCondition = "AND m.type = ?";
        array_unshift($params, $type);
    }
    
    $query = "SELECT m.*, 
              COALESCE(e.episode_number, 0) as current_episode,
              COUNT(wh.id) as total_views,
              (SELECT AVG(rating) FROM ratings WHERE movie_id = m.id) as rating,
              (SELECT COUNT(*) FROM favorites WHERE movie_id = m.id) as favorites_count
              FROM movies m 
              LEFT JOIN episodes e ON m.id = e.movie_id AND e.episode_number = (
                SELECT MAX(episode_number) FROM episodes WHERE movie_id = m.id
              )
              LEFT JOIN watch_history wh ON m.id = wh.movie_id
              WHERE m.status = 1 $typeCondition
              GROUP BY m.id, e.episode_number
              ORDER BY total_views DESC
              LIMIT ? OFFSET ?";
              
    return db_query($query, $params);
}

/**
 * Lấy danh sách nội dung có đánh giá cao nhất
 * 
 * @param int $limit Số lượng bản ghi cần lấy
 * @param int $offset Vị trí bắt đầu
 * @param string $type Loại nội dung ('anime', 'movie' hoặc '' cho tất cả)
 * @return array Danh sách phim/anime
 */
function get_top_rated_content($limit = 10, $offset = 0, $type = '') {
    $params = [$limit, $offset];
    $typeCondition = '';
    
    if (!empty($type)) {
        $typeCondition = "AND m.type = ?";
        array_unshift($params, $type);
    }
    
    $query = "SELECT m.*, 
              COALESCE(e.episode_number, 0) as current_episode,
              COUNT(wh.id) as total_views,
              (SELECT AVG(rating) FROM ratings WHERE movie_id = m.id) as avg_rating,
              (SELECT COUNT(*) FROM favorites WHERE movie_id = m.id) as favorites_count
              FROM movies m 
              LEFT JOIN episodes e ON m.id = e.movie_id AND e.episode_number = (
                SELECT MAX(episode_number) FROM episodes WHERE movie_id = m.id
              )
              LEFT JOIN watch_history wh ON m.id = wh.movie_id
              WHERE m.status = 1 $typeCondition
              GROUP BY m.id, e.episode_number
              HAVING (SELECT AVG(rating) FROM ratings WHERE movie_id = m.id) IS NOT NULL
              ORDER BY avg_rating DESC
              LIMIT ? OFFSET ?";
              
    return db_query($query, $params);
}

/**
 * Chuyển đổi chuỗi thành slug
 * 
 * @param string $string Chuỗi cần chuyển đổi
 * @return string Slug đã được chuyển đổi
 */
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#[^a-z0-9\s-]#',
        '#[\s-]+#',
        '#^-+|-+$#'
    );
    
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        '',
        '-',
        ''
    );
    
    $string = strtolower(preg_replace($search, $replace, $string));
    
    return $string;
}

/**
 * Tạo token ngẫu nhiên
 * 
 * @param int $length Độ dài token
 * @return string Token ngẫu nhiên
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Mã hóa mật khẩu
 * 
 * @param string $password Mật khẩu cần mã hóa
 * @return string Mật khẩu đã được mã hóa
 */
function hash_password($password) {
    return password_hash($password . PASSWORD_SALT, PASSWORD_BCRYPT);
}

/**
 * Kiểm tra mật khẩu
 * 
 * @param string $password Mật khẩu cần kiểm tra
 * @param string $hash Hash của mật khẩu
 * @return bool True nếu mật khẩu khớp, ngược lại False
 */
function verify_password($password, $hash) {
    return password_verify($password . PASSWORD_SALT, $hash);
}

/**
 * Lấy thông tin người dùng hiện tại
 * 
 * @return array|null Thông tin người dùng hoặc null nếu chưa đăng nhập
 */
// Removed duplicate function - using the one at the end of the file instead

/**
 * Kiểm tra người dùng có quyền quản trị hay không
 * 
 * @param array|null $user Thông tin người dùng, mặc định là người dùng hiện tại
 * @return bool True nếu là admin, ngược lại False
 */
function is_admin($user = null) {
    if ($user === null) {
        $user = get_current_user_info();
    }
    
    if (!$user) {
        return false;
    }
    
    return $user['role_name'] === 'admin';
}

/**
 * Kiểm tra người dùng có quyền quản trị nội dung hay không
 * 
 * @param array|null $user Thông tin người dùng, mặc định là người dùng hiện tại
 * @return bool True nếu là moderator hoặc admin, ngược lại False
 */
function is_moderator($user = null) {
    if ($user === null) {
        $user = get_current_user_info();
    }
    
    if (!$user) {
        return false;
    }
    
    return $user['role_name'] === 'moderator' || $user['role_name'] === 'admin';
}

/**
 * Kiểm tra người dùng có phải VIP hay không
 * 
 * @param array|null $user Thông tin người dùng, mặc định là người dùng hiện tại
 * @return bool True nếu là VIP, ngược lại False
 */
function is_vip($user = null) {
    if ($user === null) {
        $user = get_current_user_info();
    }
    
    if (!$user) {
        return false;
    }
    
    // Nếu là VIP role
    if ($user['role_name'] === 'vip') {
        return true;
    }
    
    // Nếu là admin hoặc moderator cũng được xem nội dung VIP
    if ($user['role_name'] === 'admin' || $user['role_name'] === 'moderator') {
        return true;
    }
    
    // Kiểm tra thời hạn VIP
    if (!empty($user['vip_expired_at'])) {
        $now = new DateTime();
        $expired_at = new DateTime($user['vip_expired_at']);
        
        return $now < $expired_at;
    }
    
    return false;
}

/**
 * Chuyển hướng đến URL khác
 * 
 * @param string $url URL đích
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Tạo đường dẫn đầy đủ
 * 
 * @param string $path Đường dẫn tương đối
 * @return string Đường dẫn đầy đủ
 */
function url($path = '') {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Tạo đường dẫn đến tệp asset
 * 
 * @param string $path Đường dẫn tương đối đến tệp asset
 * @return string Đường dẫn đầy đủ đến tệp asset
 */
function asset($path) {
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Format thời gian theo định dạng Việt Nam
 * 
 * @param string $datetime Chuỗi thời gian
 * @param string $format Định dạng thời gian
 * @return string Thời gian đã định dạng
 */
function format_date($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) {
        return '';
    }
    
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Format thời gian dưới dạng "time ago"
 * 
 * @param string $datetime Chuỗi thời gian
 * @return string Thời gian dưới dạng "time ago"
 */
function time_ago($datetime) {
    if (empty($datetime)) {
        return '';
    }
    
    $timestamp = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Vừa xong";
    } else if ($minutes <= 60) {
        return $minutes . " phút trước";
    } else if ($hours <= 24) {
        return $hours . " giờ trước";
    } else if ($days <= 7) {
        return $days . " ngày trước";
    } else if ($weeks <= 4) {
        return $weeks . " tuần trước";
    } else if ($months <= 12) {
        return $months . " tháng trước";
    } else {
        return $years . " năm trước";
    }
}

/**
 * Format số thành dạng có dấu phẩy ngăn cách hàng nghìn
 * 
 * @param int $number Số cần định dạng
 * @return string Số đã định dạng
 */
function format_number($number) {
    return number_format($number, 0, ',', '.');
}

/**
 * Định dạng kích thước file theo đơn vị KB, MB, GB...
 * 
 * @param int $size Kích thước file tính bằng byte
 * @return string Kích thước đã định dạng
 */
function format_file_size($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Rút gọn văn bản
 * 
 * @param string $text Văn bản cần rút gọn
 * @param int $length Độ dài tối đa
 * @param string $suffix Hậu tố cho văn bản bị rút gọn
 * @return string Văn bản đã rút gọn
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Lấy độ dài video từ giây sang định dạng HH:MM:SS
 * 
 * @param int $seconds Độ dài tính bằng giây
 * @return string Độ dài video đã định dạng
 */
function format_duration($seconds) {
    if ($seconds < 3600) {
        return sprintf("%02d:%02d", floor($seconds / 60), $seconds % 60);
    }
    
    return sprintf("%02d:%02d:%02d", floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
}

/**
 * Kiểm tra tập phim có phụ đề không
 * 
 * @param int $episode_id ID của tập phim
 * @return bool True nếu có phụ đề, ngược lại False
 */
function has_subtitles($episode_id) {
    $count = db_fetch_value(
        "SELECT COUNT(*) FROM subtitles WHERE episode_id = ?",
        [$episode_id],
        0
    );
    
    return $count > 0;
}

/**
 * Lấy danh sách phụ đề của tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @return array Danh sách phụ đề
 */
function get_subtitles($episode_id) {
    return db_fetch_all(
        "SELECT * FROM subtitles WHERE episode_id = ? ORDER BY language",
        [$episode_id]
    );
}

/**
 * Lấy URL nguồn video
 * 
 * @param array $episode Thông tin tập phim
 * @return string URL nguồn video
 */
function get_video_url($episode) {
    if ($episode['source_type'] === 'direct') {
        return $episode['source_url'];
    }
    
    if ($episode['source_type'] === 'drive') {
        // Lấy ID Google Drive từ bảng gdrive_sources
        $drive_source = db_fetch_row(
            "SELECT * FROM gdrive_sources WHERE episode_id = ?",
            [$episode['id']]
        );
        
        if ($drive_source) {
            return 'api/gdrive_proxy.php?id=' . $drive_source['drive_id'];
        }
    }
    
    if ($episode['source_type'] === 'youtube') {
        // Trả về URL embed YouTube
        $youtube_id = $episode['source_url'];
        
        if (strpos($youtube_id, 'youtube.com') !== false) {
            // Extract ID từ URL
            preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $youtube_id, $matches);
            $youtube_id = $matches[1] ?? '';
        } elseif (strpos($youtube_id, 'youtu.be') !== false) {
            // Extract ID từ short URL
            $parts = explode('/', $youtube_id);
            $youtube_id = end($parts);
        }
        
        return 'https://www.youtube.com/embed/' . $youtube_id;
    }
    
    return '';
}

/**
 * Kiểm tra người dùng đã yêu thích phim hay chưa
 * 
 * @param int $movie_id ID của phim
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return bool True nếu đã yêu thích, ngược lại False
 */
function is_favorite($movie_id, $user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return false;
        }
        
        $user_id = $user['id'];
    }
    
    $count = db_fetch_value(
        "SELECT COUNT(*) FROM favorites WHERE user_id = ? AND movie_id = ?",
        [$user_id, $movie_id],
        0
    );
    
    return $count > 0;
}

/**
 * Lấy lịch sử xem gần đây của người dùng
 * 
 * @param int $limit Số lượng tối đa
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return array Lịch sử xem gần đây
 */
function get_watch_history($limit = 10, $user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return [];
        }
        
        $user_id = $user['id'];
    }
    
    return db_fetch_all(
        "SELECT wh.*, m.title, m.slug, m.thumbnail, m.poster, m.type, e.episode_number
         FROM watch_history wh
         JOIN movies m ON wh.movie_id = m.id
         JOIN episodes e ON wh.episode_id = e.id
         WHERE wh.user_id = ?
         ORDER BY wh.watched_at DESC
         LIMIT ?",
        [$user_id, $limit]
    );
}

/**
 * Lấy danh sách phim yêu thích của người dùng
 * 
 * @param int $limit Số lượng tối đa
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return array Danh sách phim yêu thích
 */
function get_favorites($limit = 10, $user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return [];
        }
        
        $user_id = $user['id'];
    }
    
    return db_fetch_all(
        "SELECT f.*, m.title, m.slug, m.thumbnail, m.poster, m.type, 
                m.release_year, m.quality, m.description
         FROM favorites f
         JOIN movies m ON f.movie_id = m.id
         WHERE f.user_id = ?
         ORDER BY f.created_at DESC
         LIMIT ?",
        [$user_id, $limit]
    );
}

/**
 * Lấy thông báo chưa đọc
 * 
 * @param int $limit Số lượng tối đa
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return array Thông báo chưa đọc
 */
function get_unread_notifications($limit = 10, $user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return [];
        }
        
        $user_id = $user['id'];
    }
    
    return db_fetch_all(
        "SELECT *
         FROM notifications
         WHERE user_id = ? AND read = false
         ORDER BY created_at DESC
         LIMIT ?",
        [$user_id, $limit]
    );
}

/**
 * Đếm số thông báo chưa đọc
 * 
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return int Số thông báo chưa đọc
 */
function count_unread_notifications($user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return 0;
        }
        
        $user_id = $user['id'];
    }
    
    return db_fetch_value(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read = false",
        [$user_id],
        0
    );
}

/**
 * Lấy cài đặt người dùng
 * 
 * @param int|null $user_id ID của người dùng, mặc định là người dùng hiện tại
 * @return array|null Cài đặt người dùng
 */
function get_user_settings($user_id = null) {
    if ($user_id === null) {
        $user = get_current_user_info();
        
        if (!$user) {
            return null;
        }
        
        $user_id = $user['id'];
    }
    
    return db_fetch_row(
        "SELECT * FROM user_settings WHERE user_id = ?",
        [$user_id]
    );
}

/**
 * Kiểm tra một email có hợp lệ hay không
 * 
 * @param string $email Email cần kiểm tra
 * @return bool True nếu hợp lệ, ngược lại False
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Kiểm tra một số điện thoại có hợp lệ hay không
 * 
 * @param string $phone Số điện thoại cần kiểm tra
 * @return bool True nếu hợp lệ, ngược lại False
 */
function is_valid_phone($phone) {
    // Kiểm tra số điện thoại Việt Nam
    return preg_match('/^(0|\+84)(\d{9})$/', $phone) === 1;
}

/**
 * Kiểm tra một mật khẩu có đủ mạnh hay không
 * 
 * @param string $password Mật khẩu cần kiểm tra
 * @return bool True nếu đủ mạnh, ngược lại False
 */
function is_strong_password($password) {
    // Mật khẩu phải có ít nhất 8 ký tự, 
    // chứa ít nhất 1 chữ hoa, 1 chữ thường và 1 số
    $uppercase = preg_match('/[A-Z]/', $password);
    $lowercase = preg_match('/[a-z]/', $password);
    $number = preg_match('/[0-9]/', $password);
    $length = strlen($password) >= 8;
    
    return $uppercase && $lowercase && $number && $length;
}

/**
 * Gửi thông báo cho người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param string $type Loại thông báo
 * @param string $title Tiêu đề thông báo
 * @param string $message Nội dung thông báo
 * @param string $entity_type Loại đối tượng liên quan (nếu có)
 * @param int $entity_id ID của đối tượng liên quan (nếu có)
 * @return int|bool ID của thông báo hoặc false nếu thất bại
 */
function send_notification($user_id, $type, $title, $message, $entity_type = null, $entity_id = null) {
    return db_insert('notifications', [
        'user_id' => $user_id,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'read' => false,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Tạo token hết hạn
 * 
 * @param string $data Dữ liệu cần mã hóa
 * @param int $expire_time Thời gian hết hạn (giây)
 * @return string Token đã mã hóa
 */
function create_expiring_token($data, $expire_time = 3600) {
    $payload = [
        'data' => $data,
        'expire' => time() + $expire_time
    ];
    
    return base64_encode(json_encode($payload));
}

/**
 * Kiểm tra và lấy dữ liệu từ token hết hạn
 * 
 * @param string $token Token cần kiểm tra
 * @return mixed Dữ liệu hoặc false nếu token không hợp lệ hoặc đã hết hạn
 */
function verify_expiring_token($token) {
    try {
        $payload = json_decode(base64_decode($token), true);
        
        if (!$payload || !isset($payload['data']) || !isset($payload['expire'])) {
            return false;
        }
        
        if ($payload['expire'] < time()) {
            return false;
        }
        
        return $payload['data'];
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Lấy thông tin người dùng hiện tại
 * 
 * @return array|null Thông tin người dùng hiện tại hoặc null nếu chưa đăng nhập
 */
function get_current_user_info() {
    return isset($GLOBALS['CURRENT_USER']) ? $GLOBALS['CURRENT_USER'] : null;
}

/**
 * Lấy theme hiện tại
 * 
 * @return string Theme hiện tại ('light', 'dark' hoặc theme theo mùa)
 */
function get_current_theme() {
    // Kiểm tra cookie
    if (isset($_COOKIE['theme'])) {
        return $_COOKIE['theme'];
    }
    
    // Kiểm tra người dùng đã đăng nhập
    $user = get_current_user_info();
    if ($user && isset($user['theme_preference'])) {
        return $user['theme_preference'];
    }
    
    // Trả về mặc định
    return defined('DEFAULT_THEME') ? DEFAULT_THEME : 'light';
}
?>