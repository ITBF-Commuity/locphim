<?php
/**
 * Lọc Phim - Các hàm hỗ trợ
 */

/**
 * Lấy URL đầy đủ từ đường dẫn tương đối
 * 
 * @param string $path Đường dẫn tương đối
 * @return string URL đầy đủ
 */
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Chuyển hướng đến URL khác
 * 
 * @param string $path Đường dẫn tương đối
 */
function redirect($path) {
    header('Location: ' . url($path));
    exit();
}

/**
 * Xác định nếu trang hiện tại là trang được chỉ định
 * 
 * @param string $page Tên trang
 * @return bool True nếu là trang hiện tại
 */
function is_current_page($page) {
    global $currentPage;
    return $currentPage === $page;
}

/**
 * Lấy URL của hình ảnh
 * 
 * @param string $path Đường dẫn của hình ảnh
 * @return string URL đầy đủ của hình ảnh
 */
function image_url($path) {
    if (empty($path)) {
        return url('assets/images/default-poster.svg');
    }
    
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    return url($path);
}

/**
 * Kiểm tra xem người dùng có quyền admin không
 * 
 * @return bool True nếu người dùng là admin
 */
function is_admin() {
    global $user;
    return $user && isset($user['is_admin']) && $user['is_admin'];
}

/**
 * Kiểm tra xem người dùng có quyền truy cập không
 * 
 * @param string $permission Quyền cần kiểm tra
 * @return bool True nếu có quyền
 */
function has_permission($permission) {
    global $user;
    
    switch ($permission) {
        case 'logged_in':
            return $user !== null;
            
        case 'admin':
            return is_admin();
            
        case 'vip':
            return $user && isset($user['is_vip']) && $user['is_vip'];
            
        default:
            return false;
    }
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 * 
 * @return bool True nếu đã đăng nhập
 */
function is_logged_in() {
    return has_permission('logged_in');
}

/**
 * Kiểm tra xem người dùng có quyền VIP không
 * 
 * @return bool True nếu là VIP
 */
function is_vip() {
    return has_permission('vip');
}

/**
 * Tạo token ngẫu nhiên
 * 
 * @param int $length Độ dài token
 * @return string Token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Tạo slug từ chuỗi
 * 
 * @param string $string Chuỗi đầu vào
 * @return string Slug
 */
function create_slug($string) {
    // Chuyển đổi các ký tự tiếng Việt
    $string = strtr($string, [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
        'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
        'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
        'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
        'Đ' => 'D'
    ]);
    
    // Chuyển đổi thành chữ thường
    $string = strtolower($string);
    
    // Xóa các ký tự không phải chữ cái và số
    $string = preg_replace('/[^a-z0-9\s]/', '', $string);
    
    // Thay khoảng trắng bằng dấu gạch ngang
    $string = preg_replace('/\s+/', '-', $string);
    
    // Xóa dấu gạch ngang ở đầu và cuối
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Cắt chuỗi với độ dài nhất định
 * 
 * @param string $string Chuỗi đầu vào
 * @param int $length Độ dài tối đa
 * @param string $append Chuỗi thêm vào nếu chuỗi bị cắt
 * @return string Chuỗi sau khi cắt
 */
function truncate_string($string, $length = 100, $append = '...') {
    if (mb_strlen($string) > $length) {
        $string = mb_substr($string, 0, $length);
        $string = rtrim($string) . $append;
    }
    
    return $string;
}

/**
 * Format số lượng lượt xem
 * 
 * @param int $views Số lượt xem
 * @return string Chuỗi đã format
 */
function format_views($views) {
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    } else {
        return $views;
    }
}

/**
 * Format thời gian phim
 * 
 * @param int $duration Thời lượng (phút)
 * @return string Chuỗi đã format
 */
function format_duration($duration) {
    if ($duration >= 60) {
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        
        if ($minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $hours . 'h';
        }
    } else {
        return $duration . 'm';
    }
}

/**
 * Format ngày
 * 
 * @param string $date Chuỗi ngày
 * @param string $format Format mong muốn
 * @return string Ngày đã format
 */
function format_date($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Hiển thị thời gian tương đối
 * 
 * @param string $date Chuỗi ngày
 * @return string Thời gian tương đối
 */
function time_elapsed($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $minutes = round($diff / 60);
        return $minutes . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 2592000) { // 30 days
        $days = round($diff / 86400);
        return $days . ' ngày trước';
    } elseif ($diff < 31536000) { // 365 days
        $months = round($diff / 2592000);
        return $months . ' tháng trước';
    } else {
        $years = round($diff / 31536000);
        return $years . ' năm trước';
    }
}

/**
 * Lấy định dạng tin nhắn thông báo toast
 * 
 * @param string $message Nội dung thông báo
 * @param string $type Loại thông báo (success, error, warning, info)
 */
function set_toast($message, $type = 'info') {
    $_SESSION['toast'] = $message;
    $_SESSION['toast_type'] = $type;
}

/**
 * Kiểm tra xem một chuỗi có phải là JSON hợp lệ không
 * 
 * @param string $string Chuỗi cần kiểm tra
 * @return bool True nếu là JSON hợp lệ
 */
function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Lấy dữ liệu từ request POST hoặc GET
 * 
 * @param string $key Khóa dữ liệu
 * @param mixed $default Giá trị mặc định
 * @return mixed Giá trị dữ liệu
 */
function input($key, $default = '') {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    } elseif (isset($_GET[$key])) {
        return $_GET[$key];
    } else {
        return $default;
    }
}

/**
 * Tải lên file
 * 
 * @param string $input_name Tên input file
 * @param string $upload_dir Thư mục lưu trữ
 * @param array $allowed_ext Danh sách extension cho phép
 * @param int $max_size Kích thước tối đa (byte)
 * @return array Kết quả tải lên (success, message, file_path)
 */
function upload_file($input_name, $upload_dir = UPLOAD_DIR, $allowed_ext = ALLOWED_IMAGE_EXTENSIONS, $max_size = MAX_UPLOAD_SIZE) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => ''
    ];
    
    // Kiểm tra xem có file được tải lên không
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        $result['message'] = 'Không có file nào được tải lên';
        return $result;
    }
    
    $file = $_FILES[$input_name];
    
    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['message'] = 'File quá lớn';
                break;
            case UPLOAD_ERR_PARTIAL:
                $result['message'] = 'File chỉ được tải lên một phần';
                break;
            default:
                $result['message'] = 'Có lỗi xảy ra khi tải file';
        }
        return $result;
    }
    
    // Kiểm tra kích thước file
    if ($file['size'] > $max_size) {
        $result['message'] = 'File quá lớn (tối đa ' . ($max_size / 1024 / 1024) . 'MB)';
        return $result;
    }
    
    // Lấy extension
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Kiểm tra extension
    if (!in_array($fileExt, $allowed_ext)) {
        $result['message'] = 'Loại file không được hỗ trợ (chỉ hỗ trợ ' . implode(', ', $allowed_ext) . ')';
        return $result;
    }
    
    // Tạo tên file mới để tránh trùng lặp
    $newFileName = md5(uniqid() . $fileName) . '.' . $fileExt;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Đường dẫn đầy đủ đến file
    $targetFilePath = rtrim($upload_dir, '/') . '/' . $newFileName;
    
    // Tải file lên
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        $result['success'] = true;
        $result['file_path'] = $targetFilePath;
    } else {
        $result['message'] = 'Có lỗi xảy ra khi lưu file';
    }
    
    return $result;
}

/**
 * Kiểm tra và tạo thumbnail cho ảnh
 * 
 * @param string $source Đường dẫn ảnh gốc
 * @param string $destination Đường dẫn thumbnail
 * @param int $width Chiều rộng thumbnail
 * @param int $height Chiều cao thumbnail
 * @return bool True nếu tạo thumbnail thành công
 */
function create_thumbnail($source, $destination, $width, $height) {
    // Kiểm tra xem GD có được hỗ trợ không
    if (!extension_loaded('gd')) {
        return false;
    }
    
    // Lấy thông tin ảnh
    list($src_width, $src_height, $src_type) = getimagesize($source);
    
    // Tạo ảnh từ file nguồn
    switch ($src_type) {
        case IMAGETYPE_JPEG:
            $src_img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_img = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $src_img = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Tính toán tỷ lệ
    $ratio = min($width / $src_width, $height / $src_height);
    $new_width = round($src_width * $ratio);
    $new_height = round($src_height * $ratio);
    
    // Tạo ảnh thumbnail
    $dst_img = imagecreatetruecolor($new_width, $new_height);
    
    // Giữ lại độ trong suốt cho PNG
    if ($src_type === IMAGETYPE_PNG) {
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
        $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
        imagefilledrectangle($dst_img, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize ảnh
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
    
    // Lưu ảnh thumbnail
    $result = false;
    switch ($src_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dst_img, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dst_img, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dst_img, $destination);
            break;
    }
    
    // Giải phóng bộ nhớ
    imagedestroy($src_img);
    imagedestroy($dst_img);
    
    return $result;
}

/**
 * Gửi thông báo lỗi JSON
 * 
 * @param string $message Thông báo lỗi
 * @param int $code Mã lỗi HTTP
 */
function json_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * Gửi response JSON
 * 
 * @param mixed $data Dữ liệu cần gửi
 * @param int $code Mã HTTP
 */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Gửi email
 * 
 * @param string $to Địa chỉ email nhận
 * @param string $subject Tiêu đề
 * @param string $message Nội dung email
 * @param string $from Địa chỉ email gửi
 * @return bool True nếu gửi thành công
 */
function send_email($to, $subject, $message, $from = null) {
    if ($from === null) {
        $from = 'noreply@' . $_SERVER['HTTP_HOST'];
    }
    
    $headers = [
        'From' => $from,
        'Reply-To' => $from,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Lấy thông tin phim theo slug và ID
 * 
 * @param string $slug Slug của phim
 * @param int $id ID của phim
 * @return array|null Thông tin phim
 */
function get_movie($slug, $id) {
    global $db;
    
    return $db->get("
        SELECT m.*, c.name as country_name
        FROM movies m
        LEFT JOIN countries c ON m.country_id = c.id
        WHERE m.id = ? AND m.slug = ?
    ", [$id, $slug]);
}

/**
 * Lấy thông tin tập phim
 * 
 * @param int $movie_id ID của phim
 * @param int $episode_number Số tập
 * @return array|null Thông tin tập phim
 */
function get_episode($movie_id, $episode_number) {
    global $db;
    
    return $db->get("
        SELECT *
        FROM episodes
        WHERE movie_id = ? AND episode_number = ?
    ", [$movie_id, $episode_number]);
}

/**
 * Lấy danh sách máy chủ của tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @return array Danh sách máy chủ
 */
function get_servers($episode_id) {
    global $db;
    
    return $db->getAll("
        SELECT *
        FROM servers
        WHERE episode_id = ?
        ORDER BY is_default DESC
    ", [$episode_id]);
}

/**
 * Lấy danh sách phụ đề của tập phim
 * 
 * @param int $episode_id ID của tập phim
 * @return array Danh sách phụ đề
 */
function get_subtitles($episode_id) {
    global $db;
    
    return $db->getAll("
        SELECT *
        FROM subtitles
        WHERE episode_id = ?
        ORDER BY is_default DESC
    ", [$episode_id]);
}

/**
 * Kiểm tra xem phim có trong danh sách yêu thích của người dùng không
 * 
 * @param int $user_id ID của người dùng
 * @param int $movie_id ID của phim
 * @return bool True nếu phim đã được yêu thích
 */
function is_movie_favorited($user_id, $movie_id) {
    global $db;
    
    $result = $db->getValue("
        SELECT COUNT(*)
        FROM favorites
        WHERE user_id = ? AND movie_id = ?
    ", [$user_id, $movie_id]);
    
    return $result > 0;
}

/**
 * Lấy trạng thái xem phim của người dùng
 * 
 * @param int $user_id ID của người dùng
 * @param int $episode_id ID của tập phim
 * @return array|null Thông tin trạng thái xem
 */
function get_watch_status($user_id, $episode_id) {
    global $db;
    
    return $db->get("
        SELECT *
        FROM watch_history
        WHERE user_id = ? AND episode_id = ?
    ", [$user_id, $episode_id]);
}

/**
 * Lấy danh sách thể loại của phim
 * 
 * @param int $movie_id ID của phim
 * @return array Danh sách thể loại
 */
function get_movie_categories($movie_id) {
    global $db;
    
    return $db->getAll("
        SELECT c.*
        FROM categories c
        JOIN movie_categories mc ON c.id = mc.category_id
        WHERE mc.movie_id = ?
    ", [$movie_id]);
}

/**
 * Lấy danh sách diễn viên của phim
 * 
 * @param int $movie_id ID của phim
 * @return array Danh sách diễn viên
 */
function get_movie_actors($movie_id) {
    global $db;
    
    try {
        // Kiểm tra loại database
        $dbType = $db->getDatabaseType();
        
        if ($dbType === 'pgsql') {
            // Với PostgreSQL, thử dùng persons thay vì actors
            return $db->getAll("
                SELECT p.*
                FROM persons p
                JOIN movie_directors md ON p.id = md.person_id
                WHERE md.movie_id = ?
            ", [$movie_id]);
        } else {
            // Với các database khác, dùng cấu trúc ban đầu
            return $db->getAll("
                SELECT a.*
                FROM actors a
                JOIN movie_actors ma ON a.id = ma.actor_id
                WHERE ma.movie_id = ?
            ", [$movie_id]);
        }
    } catch (Exception $e) {
        // Nếu có lỗi, trả về mảng rỗng
        return [];
    }
}

/**
 * Tăng lượt xem cho phim
 * 
 * @param int $movie_id ID của phim
 */
function increment_movie_views($movie_id) {
    global $db;
    
    $db->execute("
        UPDATE movies
        SET views = views + 1
        WHERE id = ?
    ", [$movie_id]);
}

/**
 * Tăng lượt xem cho tập phim
 * 
 * @param int $episode_id ID của tập phim
 */
function increment_episode_views($episode_id) {
    global $db;
    
    $db->execute("
        UPDATE episodes
        SET views = views + 1
        WHERE id = ?
    ", [$episode_id]);
}

/**
 * Tạo token JWT
 * 
 * @param array $payload Dữ liệu token
 * @return string Token JWT
 */
function create_jwt($payload) {
    // Header
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);
    $header = base64_encode($header);
    
    // Payload
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRATION;
    
    $payload = json_encode($payload);
    $payload = base64_encode($payload);
    
    // Signature
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64_encode($signature);
    
    // Token
    return "$header.$payload.$signature";
}

/**
 * Xác thực token JWT
 * 
 * @param string $token Token JWT
 * @return array|bool Dữ liệu token nếu hợp lệ, false nếu không hợp lệ
 */
function verify_jwt($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Xác thực chữ ký
    $valid_signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $valid_signature = base64_encode($valid_signature);
    
    if ($signature !== $valid_signature) {
        return false;
    }
    
    // Giải mã payload
    $payload = base64_decode($payload);
    $payload = json_decode($payload, true);
    
    // Kiểm tra hết hạn
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Format giá tiền
 * 
 * @param float $amount Số tiền
 * @param string $currency Đơn vị tiền tệ
 * @return string Chuỗi giá đã format
 */
function format_money($amount, $currency = 'VND') {
    if ($currency === 'VND') {
        return number_format($amount, 0, ',', '.') . ' đ';
    } else {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }
}

/**
 * Chuyển đổi HTML entities
 * 
 * @param string $string Chuỗi đầu vào
 * @return string Chuỗi đã xử lý
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Tạo CSS cho Dark Mode
 * 
 * @return void In nội dung CSS
 */
function dark_mode_css() {
    echo '<style>
        body.dark-mode {
            --primary-color: #14b8a6;
            --primary-hover: #0d9488;
            --secondary-color: #f97316;
            --secondary-hover: #ea580c;
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --bg-color: #0f172a;
            --bg-light: #1e293b;
            --bg-dark: #020617;
            --border-color: #334155;
            --card-bg: #1e293b;
            --header-bg: #0f172a;
            --footer-bg: #0f172a;
            --vip-bg: #422006;
        }
        
        body.dark-mode .toast {
            background-color: #1e293b;
            color: #e2e8f0;
        }
        
        body.dark-mode .form-control {
            background-color: #1e293b;
            color: #e2e8f0;
        }
    </style>';
}