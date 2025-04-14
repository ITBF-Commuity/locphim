<?php
// Định nghĩa hằng số để bảo vệ các file include
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Include file cấu hình
require_once 'config.php';

/**
 * Hàm sanitize dữ liệu đầu vào
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Chuyển đổi câu truy vấn MySQL sang PostgreSQL
 */
function convert_mysql_to_postgres($sql) {
    // Thay thế auto_increment với SERIAL
    $sql = str_replace('AUTO_INCREMENT', 'SERIAL', $sql);
    
    // Thay thế ENGINE=InnoDB
    $sql = preg_replace('/ENGINE\s*=\s*[a-zA-Z0-9_]+/i', '', $sql);
    
    // Thay thế các phần tử cụ thể của MySQL như varchar với VARCHAR
    $sql = str_ireplace('varchar', 'VARCHAR', $sql);
    
    // Xử lý LIMIT x OFFSET y
    if (preg_match('/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', $sql, $matches)) {
        if (isset($matches[2])) {
            // Đã có cả LIMIT và OFFSET
            $sql = preg_replace('/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', 'LIMIT $1 OFFSET $2', $sql);
        }
    }
    
    // Xử lý ON DUPLICATE KEY UPDATE -> ON CONFLICT ... DO UPDATE
    if (stripos($sql, 'ON DUPLICATE KEY UPDATE') !== false) {
        // PostgreSQL sử dụng cú pháp khác cho việc này, ví dụ:
        // INSERT INTO table (id, col) VALUES (1, 'val') ON CONFLICT (id) DO UPDATE SET col = EXCLUDED.col;
        // Cần xác định khóa xung đột và cột cần cập nhật
        
        // Đây là xử lý đơn giản cho trường hợp cụ thể trong code
        if (stripos($sql, 'INSERT INTO watch_history') !== false) {
            $sql = str_replace(
                'ON DUPLICATE KEY UPDATE current_time = ?, updated_at = NOW()',
                'ON CONFLICT (user_id, anime_id, episode_id) DO UPDATE SET playback_time = EXCLUDED.playback_time, updated_at = NOW()',
                $sql
            );
        }
    }
    
    return $sql;
}

/**
 * Xử lý truy vấn SQL an toàn cho PostgreSQL
 */
function db_query($sql, $params = [], $fetch_all = false) {
    $conn = db_connect();
    $db_type = get_config('db.type');
    
    try {
        if ($db_type === 'postgresql') {
            // Chuyển đổi câu truy vấn từ MySQL sang PostgreSQL
            $sql = convert_mysql_to_postgres($sql);
            
            // Chuyển các placeholders từ ? sang $1, $2, v.v.
            $count = 0;
            $sql = preg_replace_callback('/\?/', function($matches) use (&$count) {
                $count++;
                return '$' . $count;
            }, $sql);
            
            // Thực thi truy vấn
            $result = pg_query_params($conn, $sql, $params);
            
            if (!$result) {
                throw new Exception("PostgreSQL query error: " . pg_last_error($conn));
            }
            
            if (stripos($sql, 'INSERT') === 0) {
                // Lấy ID mới chèn vào
                $insert_id = pg_last_oid($result);
                $affected_rows = pg_affected_rows($result);
                
                return [
                    'affected_rows' => $affected_rows,
                    'insert_id' => $insert_id
                ];
            } elseif (stripos($sql, 'UPDATE') === 0 || stripos($sql, 'DELETE') === 0) {
                // Truy vấn UPDATE hoặc DELETE
                $affected_rows = pg_affected_rows($result);
                
                return [
                    'affected_rows' => $affected_rows,
                    'insert_id' => 0
                ];
            } else {
                // Truy vấn SELECT
                if ($fetch_all) {
                    $rows = [];
                    while ($row = pg_fetch_assoc($result)) {
                        $rows[] = $row;
                    }
                    return $rows;
                } else {
                    // Trả về result để xử lý sau
                    return $result;
                }
            }
        } else {
            // MySQL fallback (mã xử lý cũ)
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
            }
            
            if (!empty($params)) {
                $types = '';
                $bind_params = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    $bind_params[] = $param;
                }
                
                $bind_names = [];
                for ($i = 0; $i < count($bind_params); $i++) {
                    $bind_name = 'param' . $i;
                    $$bind_name = $bind_params[$i];
                    $bind_names[] = &$$bind_name;
                }
                
                array_unshift($bind_names, $types);
                call_user_func_array([$stmt, 'bind_param'], $bind_names);
            }
            
            $stmt->execute();
            
            $result = $stmt->get_result();
            
            if ($result === false && $stmt->errno) {
                throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
            }
            
            if ($result) {
                if ($fetch_all) {
                    return $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    return $result;
                }
            } else {
                // Truy vấn INSERT, UPDATE, DELETE
                return [
                    'affected_rows' => $stmt->affected_rows,
                    'insert_id' => $stmt->insert_id
                ];
            }
        }
    } catch (Exception $e) {
        if (get_config('site.debug')) {
            die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
        } else {
            error_log("Database error: " . $e->getMessage());
            die("Lỗi hệ thống. Vui lòng thử lại sau.");
        }
    }
}

/**
 * Lấy thông tin anime
 */
function get_anime($id = null, $limit = 10, $offset = 0, $filters = []) {
    if ($id !== null) {
        // Lấy thông tin chi tiết về 1 anime
        $sql = "SELECT a.*, c.name as category_name 
                FROM anime a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE a.id = ?";
        
        $result = db_query($sql, [$id], false);
        
        if (get_config('db.type') === 'postgresql') {
            return pg_fetch_assoc($result);
        } else {
            return $result->fetch_assoc();
        }
    } else {
        // Xây dựng câu truy vấn với bộ lọc
        $sql = "SELECT a.*, c.name as category_name 
                FROM anime a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE 1=1";
        
        $params = [];
        
        // Thêm các điều kiện lọc
        if (!empty($filters)) {
            if (isset($filters['category_id'])) {
                $sql .= " AND a.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (isset($filters['search'])) {
                $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
                $search_term = "%" . $filters['search'] . "%";
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            if (isset($filters['year'])) {
                $sql .= " AND a.release_year = ?";
                $params[] = $filters['year'];
            }
            
            if (isset($filters['sort'])) {
                switch ($filters['sort']) {
                    case 'newest':
                        $sql .= " ORDER BY a.release_date DESC";
                        break;
                    case 'oldest':
                        $sql .= " ORDER BY a.release_date ASC";
                        break;
                    case 'views':
                        $sql .= " ORDER BY a.views DESC";
                        break;
                    case 'rating':
                        $sql .= " ORDER BY a.rating DESC";
                        break;
                    default:
                        $sql .= " ORDER BY a.id DESC";
                }
            } else {
                $sql .= " ORDER BY a.id DESC";
            }
        } else {
            $sql .= " ORDER BY a.id DESC";
        }
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return db_query($sql, $params, true);
    }
}

/**
 * Lấy danh sách các tập phim
 */
function get_episodes($anime_id) {
    $sql = "SELECT * FROM episodes WHERE anime_id = ? ORDER BY episode_number ASC";
    return db_query($sql, [$anime_id], true);
}

/**
 * Lấy thông tin chi tiết tập phim
 */
function get_episode($episode_id) {
    $sql = "SELECT e.*, a.title as anime_title, a.thumbnail as anime_thumbnail 
            FROM episodes e 
            JOIN anime a ON e.anime_id = a.id 
            WHERE e.id = ?";
    
    $result = db_query($sql, [$episode_id], false);
    
    if (get_config('db.type') === 'postgresql') {
        return pg_fetch_assoc($result);
    } else {
        return $result->fetch_assoc();
    }
}

/**
 * Cập nhật lượt xem
 */
function update_views($anime_id, $episode_id = null) {
    $conn = db_connect();
    $db_type = get_config('db.type');
    
    try {
        if ($db_type === 'postgresql') {
            // PostgreSQL transaction
            pg_query($conn, "BEGIN");
            
            // Cập nhật lượt xem cho anime
            $sql1 = "UPDATE anime SET views = views + 1 WHERE id = $1";
            $result1 = pg_query_params($conn, $sql1, [$anime_id]);
            
            // Nếu có episode_id, cập nhật lượt xem cho tập
            if ($episode_id) {
                $sql2 = "UPDATE episodes SET views = views + 1 WHERE id = $1";
                $result2 = pg_query_params($conn, $sql2, [$episode_id]);
            }
            
            pg_query($conn, "COMMIT");
        } else {
            // MySQL transaction
            $conn->begin_transaction();
            
            // Cập nhật lượt xem cho anime
            $sql1 = "UPDATE anime SET views = views + 1 WHERE id = ?";
            db_query($sql1, [$anime_id]);
            
            // Nếu có episode_id, cập nhật lượt xem cho tập
            if ($episode_id) {
                $sql2 = "UPDATE episodes SET views = views + 1 WHERE id = ?";
                db_query($sql2, [$episode_id]);
            }
            
            $conn->commit();
        }
    } catch (Exception $e) {
        if ($db_type === 'postgresql') {
            pg_query($conn, "ROLLBACK");
        } else {
            $conn->rollback();
        }
        error_log("Error updating views: " . $e->getMessage());
    }
}

/**
 * Lấy bảng xếp hạng
 */
function get_ranking($limit = 10) {
    $sql = "SELECT * FROM anime ORDER BY views DESC LIMIT ?";
    return db_query($sql, [$limit], true);
}

/**
 * Lưu lịch sử xem
 */
function save_watch_history($user_id, $anime_id, $episode_id, $playback_time) {
    $db_type = get_config('db.type');
    
    if ($db_type === 'postgresql') {
        $sql = "INSERT INTO watch_history (user_id, anime_id, episode_id, playback_time, updated_at) 
                VALUES ($1, $2, $3, $4, NOW()) 
                ON CONFLICT (user_id, anime_id, episode_id) DO UPDATE 
                SET playback_time = $4, updated_at = NOW()";
        
        return db_query($sql, [
            $user_id, $anime_id, $episode_id, $playback_time
        ]);
    } else {
        $sql = "INSERT INTO watch_history (user_id, anime_id, episode_id, playback_time, updated_at) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE current_time = ?, updated_at = NOW()";
        
        return db_query($sql, [
            $user_id, $anime_id, $episode_id, $playback_time, $playback_time
        ]);
    }
}

/**
 * Lấy lịch sử xem của người dùng
 */
function get_watch_history($user_id, $anime_id = null, $episode_id = null) {
    $db_type = get_config('db.type');
    $field_name = ($db_type === 'postgresql') ? 'playback_time' : 'current_time';
    
    if ($anime_id && $episode_id) {
        $sql = "SELECT * FROM watch_history WHERE user_id = ? AND anime_id = ? AND episode_id = ?";
        $result = db_query($sql, [$user_id, $anime_id, $episode_id], false);
        
        if ($db_type === 'postgresql') {
            return pg_fetch_assoc($result);
        } else {
            return $result->fetch_assoc();
        }
    } else {
        $sql = "SELECT wh.*, a.title as anime_title, a.thumbnail as anime_thumbnail, e.episode_number 
                FROM watch_history wh 
                JOIN anime a ON wh.anime_id = a.id 
                JOIN episodes e ON wh.episode_id = e.id 
                WHERE wh.user_id = ? 
                ORDER BY wh.updated_at DESC 
                LIMIT 20";
        
        return db_query($sql, [$user_id], true);
    }
}

/**
 * Xử lý yêu thích
 */
function toggle_favorite($user_id, $anime_id) {
    $db_type = get_config('db.type');
    
    // Kiểm tra xem đã yêu thích chưa
    $sql_check = "SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?";
    $result = db_query($sql_check, [$user_id, $anime_id], false);
    
    if ($db_type === 'postgresql') {
        $has_favorite = pg_num_rows($result) > 0;
    } else {
        $has_favorite = $result->num_rows > 0;
    }
    
    if ($has_favorite) {
        // Đã yêu thích, xóa khỏi danh sách
        $sql_delete = "DELETE FROM favorites WHERE user_id = ? AND anime_id = ?";
        db_query($sql_delete, [$user_id, $anime_id]);
        return false; // Đã bỏ yêu thích
    } else {
        // Chưa yêu thích, thêm vào danh sách
        $sql_insert = "INSERT INTO favorites (user_id, anime_id, created_at) VALUES (?, ?, NOW())";
        db_query($sql_insert, [$user_id, $anime_id]);
        return true; // Đã yêu thích
    }
}

/**
 * Kiểm tra anime có trong danh sách yêu thích
 */
function is_favorite($user_id, $anime_id) {
    $db_type = get_config('db.type');
    
    $sql = "SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?";
    $result = db_query($sql, [$user_id, $anime_id], false);
    
    if ($db_type === 'postgresql') {
        return pg_num_rows($result) > 0;
    } else {
        return $result->num_rows > 0;
    }
}

/**
 * Lấy danh sách yêu thích
 */
function get_favorites($user_id) {
    $sql = "SELECT f.*, a.title, a.thumbnail, a.release_year, a.rating 
            FROM favorites f 
            JOIN anime a ON f.anime_id = a.id 
            WHERE f.user_id = ? 
            ORDER BY f.created_at DESC";
    
    return db_query($sql, [$user_id], true);
}

/**
 * Kiểm tra quyền VIP
 */
function check_vip_status($user_id) {
    $db_type = get_config('db.type');
    
    $sql = "SELECT * FROM vip_members WHERE user_id = ? AND expire_date > NOW()";
    $result = db_query($sql, [$user_id], false);
    
    if ($db_type === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return false;
}

/**
 * Kiểm tra quyền xem nội dung
 */
function can_access_content($user_id, $resolution = '480p') {
    $vip_status = check_vip_status($user_id);
    
    // Nếu độ phân giải nhỏ hơn hoặc bằng 480p, cho phép tất cả xem
    if (in_array($resolution, ['360p', '480p'])) {
        return true;
    }
    
    // Nếu không phải VIP và độ phân giải > 480p
    if (!$vip_status) {
        return false;
    }
    
    // Các cấp độ VIP và quyền truy cập
    $vip_levels = [
        1 => ['720p'], // VIP Bạc: 720p
        2 => ['720p', '1080p'], // VIP Vàng: 1080p
        3 => ['720p', '1080p', '4K'] // VIP Kim Cương: 4K
    ];
    
    return in_array($resolution, $vip_levels[$vip_status['level']]);
}

/**
 * Tạo thumbnail từ URL
 */
function get_thumbnail($url, $size = 'medium') {
    // Tạo SVG mặc định nếu URL trống
    if (empty($url)) {
        // Tạo màu ngẫu nhiên làm background
        $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
        $bg_color = $colors[array_rand($colors)];
        
        // Kích thước dựa theo tham số
        $width = 300;
        $height = 169;
        
        if ($size == 'small') {
            $width = 120;
            $height = 68;
        } elseif ($size == 'large') {
            $width = 600;
            $height = 338;
        }
        
        // Tạo SVG placeholder
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">
            <rect width="100%" height="100%" fill="'.$bg_color.'"/>
            <text x="50%" y="50%" font-family="Arial" font-size="24" text-anchor="middle" fill="#ffffff">No Image</text>
        </svg>';
        
        // Convert SVG to data URL
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
    
    // Xử lý trường hợp URL đã đầy đủ
    if (strpos($url, 'http') === 0) {
        return $url;
    }
    
    // Xử lý data URL
    if (strpos($url, 'data:image') === 0) {
        return $url;
    }
    
    // Xử lý thumbnails nếu $size khác 'medium'
    if ($size == 'small' && file_exists('uploads/images/thumbnails/' . basename($url))) {
        return 'uploads/images/thumbnails/' . basename($url);
    } elseif ($size == 'webp' && file_exists('uploads/images/webp/' . basename($url, pathinfo($url, PATHINFO_EXTENSION)) . 'webp')) {
        return 'uploads/images/webp/' . basename($url, pathinfo($url, PATHINFO_EXTENSION)) . 'webp';
    }
    
    // Xử lý URL tương đối
    if (file_exists($url)) {
        return $url;
    } else if (file_exists('uploads/images/' . $url)) {
        return 'uploads/images/' . $url;
    }
    
    // Fallback tới SVG mặc định nếu không tìm thấy hình ảnh
    return 'data:image/svg+xml;base64,'.base64_encode($svg);
}

/**
 * Tạo thông báo flash
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Hiển thị thông báo flash
 */
function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash']);
    }
}

/**
 * Format thời gian
 */
function format_time($timestamp) {
    $now = time();
    $diff = $now - strtotime($timestamp);
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' ngày trước';
    } else {
        return date('d/m/Y', strtotime($timestamp));
    }
}

/**
 * Tạo slug
 */
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#u',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#u',
        '#(ì|í|ị|ỉ|ĩ)#u',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#u',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#u',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#u',
        '#(đ)#u',
        '#[^a-z0-9\-\_]#i',
    );
    
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        '-',
    );
    
    $string = strtolower(preg_replace($search, $replace, $string));
    $string = preg_replace('/(-)+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Format tiền tệ
 */
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}
?>