<?php
/**
 * Các hàm tiện ích chung cho trang web Lọc Phim
 */

require_once __DIR__ . '/../db_connect.php';

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
              ORDER BY total_views DESC, m.created_at DESC
              LIMIT ? OFFSET ?";
              
    $result = db_query($query, $params);
    
    // Ảnh mẫu 
    $thumbnails = [
        'https://images.unsplash.com/photo-1601850494422-3cf14624b0b3',
        'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f',
        'https://images.unsplash.com/photo-1625189659340-887baac3ea32',
        'https://images.unsplash.com/photo-1602416222941-a72a356dab04',
        'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1',
        'https://images.unsplash.com/photo-1485846234645-a62644f84728',
        'https://images.unsplash.com/photo-1485095329183-d0797cdc5676',
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba'
    ];
    
    foreach ($result as &$item) {
        // Nếu thumbnail trống, dùng ảnh mẫu
        if (empty($item['thumbnail'])) {
            $item['thumbnail'] = $thumbnails[array_rand($thumbnails)];
        }
        
        // Nếu poster trống, dùng thumbnail
        if (empty($item['poster'])) {
            $item['poster'] = $item['thumbnail'];
        }
        
        // Nếu rating null thì gán giá trị mặc định
        if (is_null($item['rating'])) {
            $item['rating'] = rand(70, 95) / 10;
        }
        
        // Nếu total_views null thì gán giá trị mặc định
        if (is_null($item['total_views'])) {
            $item['total_views'] = rand(50000, 500000);
        }
        
        // Nếu favorites_count null thì gán giá trị mặc định
        if (is_null($item['favorites_count'])) {
            $item['favorites_count'] = rand(1000, 50000);
        }
        
        // Thêm số tập nếu là anime và chưa có
        if ($item['type'] === 'anime' && !isset($item['episodes_count'])) {
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
            if ($item['type'] === 'movie') {
                $item['description'] = 'Một bộ phim hấp dẫn với cốt truyện sâu sắc và dàn diễn viên tài năng. Khám phá những tình tiết bất ngờ và cảm xúc mãnh liệt trong cuộc phiêu lưu điện ảnh này.';
            } else {
                $item['description'] = 'Một bộ anime đầy màu sắc với các nhân vật đáng yêu và những câu chuyện kỳ diệu. Hãy theo dõi cuộc phiêu lưu đầy bất ngờ và cảm xúc trong thế giới anime tuyệt vời này.';
            }
        }
    }
    
    return $result;
}

/**
 * Lấy nội dung được đánh giá cao nhất
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
              (SELECT AVG(rating) FROM ratings WHERE movie_id = m.id) as rating,
              (SELECT COUNT(*) FROM favorites WHERE movie_id = m.id) as favorites_count
              FROM movies m 
              LEFT JOIN episodes e ON m.id = e.movie_id AND e.episode_number = (
                SELECT MAX(episode_number) FROM episodes WHERE movie_id = m.id
              )
              LEFT JOIN watch_history wh ON m.id = wh.movie_id
              LEFT JOIN ratings r ON m.id = r.movie_id
              WHERE m.status = 1 $typeCondition
              GROUP BY m.id, e.episode_number
              ORDER BY rating DESC, total_views DESC, m.created_at DESC
              LIMIT ? OFFSET ?";
              
    $result = db_query($query, $params);
    
    // Ảnh mẫu 
    $thumbnails = [
        'https://images.unsplash.com/photo-1601850494422-3cf14624b0b3',
        'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f',
        'https://images.unsplash.com/photo-1625189659340-887baac3ea32',
        'https://images.unsplash.com/photo-1602416222941-a72a356dab04',
        'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1',
        'https://images.unsplash.com/photo-1485846234645-a62644f84728',
        'https://images.unsplash.com/photo-1485095329183-d0797cdc5676',
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba'
    ];
    
    foreach ($result as &$item) {
        // Nếu thumbnail trống, dùng ảnh mẫu
        if (empty($item['thumbnail'])) {
            $item['thumbnail'] = $thumbnails[array_rand($thumbnails)];
        }
        
        // Nếu poster trống, dùng thumbnail
        if (empty($item['poster'])) {
            $item['poster'] = $item['thumbnail'];
        }
        
        // Nếu rating null thì gán giá trị mặc định
        if (is_null($item['rating'])) {
            $item['rating'] = rand(80, 95) / 10; // Top rated nên ratings cao hơn
        }
        
        // Nếu total_views null thì gán giá trị mặc định
        if (is_null($item['total_views'])) {
            $item['total_views'] = rand(30000, 400000);
        }
        
        // Nếu favorites_count null thì gán giá trị mặc định
        if (is_null($item['favorites_count'])) {
            $item['favorites_count'] = rand(1000, 50000);
        }
        
        // Thêm số tập nếu là anime và chưa có
        if ($item['type'] === 'anime' && !isset($item['episodes_count'])) {
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
            if ($item['type'] === 'movie') {
                $item['description'] = 'Một bộ phim được đánh giá cao với cốt truyện sâu sắc và dàn diễn viên tài năng. Khám phá những tình tiết bất ngờ và cảm xúc mãnh liệt trong cuộc phiêu lưu điện ảnh này.';
            } else {
                $item['description'] = 'Một bộ anime đánh giá cao với các nhân vật đáng yêu và những câu chuyện kỳ diệu. Hãy theo dõi cuộc phiêu lưu đầy bất ngờ và cảm xúc trong thế giới anime tuyệt vời này.';
            }
        }
    }
    
    return $result;
}

/**
 * Lấy nội dung được thêm vào yêu thích nhiều nhất
 * 
 * @param int $limit Số lượng bản ghi cần lấy
 * @param int $offset Vị trí bắt đầu
 * @param string $type Loại nội dung ('anime', 'movie' hoặc '' cho tất cả)
 * @return array Danh sách phim/anime
 */
function get_most_favorite_content($limit = 10, $offset = 0, $type = '') {
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
              LEFT JOIN favorites f ON m.id = f.movie_id
              WHERE m.status = 1 $typeCondition
              GROUP BY m.id, e.episode_number
              ORDER BY favorites_count DESC, m.created_at DESC
              LIMIT ? OFFSET ?";
              
    $result = db_query($query, $params);
    
    // Ảnh mẫu 
    $thumbnails = [
        'https://images.unsplash.com/photo-1601850494422-3cf14624b0b3',
        'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f',
        'https://images.unsplash.com/photo-1625189659340-887baac3ea32',
        'https://images.unsplash.com/photo-1602416222941-a72a356dab04',
        'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1',
        'https://images.unsplash.com/photo-1485846234645-a62644f84728',
        'https://images.unsplash.com/photo-1485095329183-d0797cdc5676',
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba'
    ];
    
    foreach ($result as &$item) {
        // Nếu thumbnail trống, dùng ảnh mẫu
        if (empty($item['thumbnail'])) {
            $item['thumbnail'] = $thumbnails[array_rand($thumbnails)];
        }
        
        // Nếu poster trống, dùng thumbnail
        if (empty($item['poster'])) {
            $item['poster'] = $item['thumbnail'];
        }
        
        // Nếu rating null thì gán giá trị mặc định
        if (is_null($item['rating'])) {
            $item['rating'] = rand(70, 95) / 10;
        }
        
        // Nếu total_views null thì gán giá trị mặc định
        if (is_null($item['total_views'])) {
            $item['total_views'] = rand(30000, 300000);
        }
        
        // Nếu favorites_count null thì gán giá trị mặc định
        if (is_null($item['favorites_count'])) {
            $item['favorites_count'] = rand(5000, 100000);
        }
        
        // Thêm số tập nếu là anime và chưa có
        if ($item['type'] === 'anime' && !isset($item['episodes_count'])) {
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
            if ($item['type'] === 'movie') {
                $item['description'] = 'Một bộ phim được yêu thích với cốt truyện sâu sắc và dàn diễn viên tài năng. Khám phá những tình tiết bất ngờ và cảm xúc mãnh liệt trong cuộc phiêu lưu điện ảnh này.';
            } else {
                $item['description'] = 'Một bộ anime được yêu thích với các nhân vật đáng yêu và những câu chuyện kỳ diệu. Hãy theo dõi cuộc phiêu lưu đầy bất ngờ và cảm xúc trong thế giới anime tuyệt vời này.';
            }
        }
    }
    
    return $result;
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
 * Lấy thông tin người dùng hiện tại
 * 
 * @return array|null Thông tin người dùng hoặc null nếu chưa đăng nhập
 */
function get_current_user_info() {
    return isset($GLOBALS['CURRENT_USER']) ? $GLOBALS['CURRENT_USER'] : null;
}

/**
 * Lấy theme hiện tại
 * 
 * @return string Theme hiện tại ('light' hoặc 'dark')
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
    
    return $user['role_slug'] === 'admin';
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
    if ($user['role_slug'] === 'vip') {
        return true;
    }
    
    // Nếu là admin cũng được xem nội dung VIP
    if ($user['role_slug'] === 'admin') {
        return true;
    }
    
    // Kiểm tra thời hạn VIP
    if (isset($user['premium_until']) && !empty($user['premium_until'])) {
        $now = new DateTime();
        $premium_until = new DateTime($user['premium_until']);
        
        return $now < $premium_until;
    }
    
    return false;
}
?>