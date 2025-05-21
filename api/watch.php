<?php
/**
 * Lọc Phim - API xem phim
 * 
 * File xử lý các API liên quan đến xem phim
 */

// Xử lý request dựa trên PATH_INFO và REQUEST_METHOD
$action = $_GET['path'] ?? '';

switch ($action) {
    case 'watch-progress':
        handleWatchProgress();
        break;
    
    case 'mark-completed':
        handleMarkCompleted();
        break;
        
    case 'get-video-source':
        handleGetVideoSource();
        break;
        
    case 'toggle-favorite':
        handleToggleFavorite();
        break;
        
    case 'report-error':
        handleReportError();
        break;
        
    case 'comments':
        handleGetComments();
        break;
        
    case 'add-comment':
        handleAddComment();
        break;
        
    default:
        // API không tồn tại
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API không tồn tại']);
        break;
}

/**
 * Xử lý API cập nhật tiến trình xem
 */
function handleWatchProgress() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['movie_id']) || !isset($data['episode_id']) || !isset($data['current_time']) || !isset($data['duration'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $movieId = intval($data['movie_id']);
    $episodeId = intval($data['episode_id']);
    $currentTime = floatval($data['current_time']);
    $duration = floatval($data['duration']);
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Kiểm tra xem bản ghi đã tồn tại chưa
    $existingRecord = $db->get("
        SELECT id, current_time
        FROM watch_progress
        WHERE user_id = ? AND episode_id = ?
    ", [$userId, $episodeId]);
    
    if ($existingRecord) {
        // Cập nhật
        $db->update('watch_progress', [
            'current_time' => $currentTime,
            'duration' => $duration,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $existingRecord['id']
        ]);
    } else {
        // Thêm mới
        $db->insert('watch_progress', [
            'user_id' => $userId,
            'movie_id' => $movieId,
            'episode_id' => $episodeId,
            'current_time' => $currentTime,
            'duration' => $duration,
            'completed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Đánh dấu đã hoàn thành nếu đã xem > 90% thời lượng
    if ($currentTime > $duration * 0.9) {
        $db->update('watch_progress', [
            'completed' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'user_id' => $userId,
            'episode_id' => $episodeId
        ]);
        
        // Cập nhật lịch sử xem
        $existingHistory = $db->get("
            SELECT id
            FROM watch_history
            WHERE user_id = ? AND movie_id = ?
        ", [$userId, $movieId]);
        
        if ($existingHistory) {
            $db->update('watch_history', [
                'episode_id' => $episodeId,
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $existingHistory['id']
            ]);
        } else {
            $db->insert('watch_history', [
                'user_id' => $userId,
                'movie_id' => $movieId,
                'episode_id' => $episodeId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật tiến trình xem thành công']);
}

/**
 * Xử lý API đánh dấu đã xem xong
 */
function handleMarkCompleted() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['movie_id']) || !isset($data['episode_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $movieId = intval($data['movie_id']);
    $episodeId = intval($data['episode_id']);
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Lấy thông tin tập phim
    $episode = $db->get("
        SELECT duration
        FROM episodes
        WHERE id = ?
    ", [$episodeId]);
    
    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tập phim']);
        return;
    }
    
    // Kiểm tra xem bản ghi đã tồn tại chưa
    $existingRecord = $db->get("
        SELECT id
        FROM watch_progress
        WHERE user_id = ? AND episode_id = ?
    ", [$userId, $episodeId]);
    
    if ($existingRecord) {
        // Cập nhật
        $db->update('watch_progress', [
            'current_time' => $episode['duration'],
            'duration' => $episode['duration'],
            'completed' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $existingRecord['id']
        ]);
    } else {
        // Thêm mới
        $db->insert('watch_progress', [
            'user_id' => $userId,
            'movie_id' => $movieId,
            'episode_id' => $episodeId,
            'current_time' => $episode['duration'],
            'duration' => $episode['duration'],
            'completed' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Cập nhật lịch sử xem
    $existingHistory = $db->get("
        SELECT id
        FROM watch_history
        WHERE user_id = ? AND movie_id = ?
    ", [$userId, $movieId]);
    
    if ($existingHistory) {
        $db->update('watch_history', [
            'episode_id' => $episodeId,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $existingHistory['id']
        ]);
    } else {
        $db->insert('watch_history', [
            'user_id' => $userId,
            'movie_id' => $movieId,
            'episode_id' => $episodeId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Đánh dấu đã xem xong thành công']);
}

/**
 * Xử lý API lấy nguồn video
 */
function handleGetVideoSource() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $movieId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $episodeId = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
    $episodeNumber = isset($_GET['episode']) ? intval($_GET['episode']) : 0;
    $quality = isset($_GET['quality']) ? intval($_GET['quality']) : 0;
    
    // Kiểm tra dữ liệu
    if (!$movieId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Nếu có episode_id, lấy thông tin từ episode_id
    if ($episodeId) {
        $episode = $db->get("
            SELECT e.id, e.movie_id, m.is_vip
            FROM episodes e
            JOIN movies m ON e.movie_id = m.id
            WHERE e.id = ? AND e.status = 'published'
        ", [$episodeId]);
    } 
    // Nếu có episode_number, lấy thông tin từ movie_id và episode_number
    elseif ($episodeNumber) {
        $episode = $db->get("
            SELECT e.id, e.movie_id, m.is_vip
            FROM episodes e
            JOIN movies m ON e.movie_id = m.id
            WHERE e.movie_id = ? AND e.episode_number = ? AND e.status = 'published'
        ", [$movieId, $episodeNumber]);
    } 
    // Nếu là phim lẻ, lấy thông tin từ movie_id
    else {
        $movie = $db->get("
            SELECT id, is_vip
            FROM movies
            WHERE id = ? AND status = 'published'
        ", [$movieId]);
        
        if (!$movie) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy phim']);
            return;
        }
        
        // Tạo episode ảo cho phim lẻ
        $episode = [
            'id' => 0,
            'movie_id' => $movieId,
            'is_vip' => $movie['is_vip']
        ];
    }
    
    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tập phim']);
        return;
    }
    
    // Kiểm tra quyền xem phim VIP
    if ($episode['is_vip'] && !isVip()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn cần là thành viên VIP để xem phim này']);
        return;
    }
    
    // Lấy nguồn video
    $query = "
        SELECT vs.*
        FROM video_sources vs
        WHERE vs.episode_id = ?
    ";
    $params = [$episode['id']];
    
    // Nếu có yêu cầu chất lượng cụ thể
    if ($quality) {
        $query .= " AND vs.quality = ?";
        $params[] = $quality;
    }
    
    // Sắp xếp theo chất lượng giảm dần (nếu không có yêu cầu chất lượng cụ thể)
    if (!$quality) {
        $query .= " ORDER BY vs.quality DESC";
    }
    
    $sources = $db->getAll($query, $params);
    
    if (empty($sources)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nguồn video']);
        return;
    }
    
    // Kiểm tra quyền xem video chất lượng cao
    if (!isVip()) {
        // Lọc ra các nguồn video có chất lượng <= 720p
        $filteredSources = array_filter($sources, function($source) {
            return $source['quality'] <= 720;
        });
        
        // Nếu có nguồn video sau khi lọc, sử dụng chúng
        if (!empty($filteredSources)) {
            $sources = array_values($filteredSources);
        } else {
            // Nếu không có nguồn nào sau khi lọc, lấy nguồn có chất lượng thấp nhất
            usort($sources, function($a, $b) {
                return $a['quality'] - $b['quality'];
            });
            $sources = [$sources[0]];
        }
    }
    
    // Lấy thông tin phụ đề
    $subtitles = $db->getAll("
        SELECT s.*
        FROM subtitles s
        WHERE s.episode_id = ?
    ", [$episode['id']]);
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'source' => $sources[0]['source_url'],
        'sources' => $sources,
        'subtitles' => $subtitles
    ]);
}

/**
 * Xử lý API bật/tắt yêu thích
 */
function handleToggleFavorite() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['movie_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $movieId = intval($data['movie_id']);
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Kiểm tra phim có tồn tại không
    $movie = $db->get("
        SELECT id
        FROM movies
        WHERE id = ?
    ", [$movieId]);
    
    if (!$movie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phim']);
        return;
    }
    
    // Kiểm tra xem đã yêu thích chưa
    $existingFavorite = $db->get("
        SELECT id
        FROM favorites
        WHERE user_id = ? AND movie_id = ?
    ", [$userId, $movieId]);
    
    // Nếu đã yêu thích, xóa khỏi danh sách yêu thích
    if ($existingFavorite) {
        $db->delete('favorites', [
            'id' => $existingFavorite['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi danh sách yêu thích', 'is_favorite' => false]);
    } 
    // Nếu chưa yêu thích, thêm vào danh sách yêu thích
    else {
        $db->insert('favorites', [
            'user_id' => $userId,
            'movie_id' => $movieId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Đã thêm vào danh sách yêu thích', 'is_favorite' => true]);
    }
}

/**
 * Xử lý API báo lỗi
 */
function handleReportError() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['movie_id']) || !isset($data['episode_id']) || !isset($data['type']) || !isset($data['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $movieId = intval($data['movie_id']);
    $episodeId = intval($data['episode_id']);
    $type = sanitizeInput($data['type']);
    $description = sanitizeInput($data['description']);
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Kiểm tra loại lỗi
    $validTypes = ['video', 'subtitle', 'audio', 'loading', 'other'];
    if (!in_array($type, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Loại lỗi không hợp lệ']);
        return;
    }
    
    // Thêm báo lỗi
    $reportId = $db->insert('error_reports', [
        'user_id' => $userId,
        'movie_id' => $movieId,
        'episode_id' => $episodeId,
        'type' => $type,
        'description' => $description,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$reportId) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi gửi báo cáo']);
        return;
    }
    
    echo json_encode(['success' => true, 'message' => 'Gửi báo cáo lỗi thành công. Chúng tôi sẽ kiểm tra và khắc phục sớm nhất.']);
}

/**
 * Xử lý API lấy danh sách bình luận
 */
function handleGetComments() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $movieId = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
    $episodeId = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Kiểm tra dữ liệu
    if (!$movieId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Tính offset
    $offset = ($page - 1) * $limit;
    
    // Lấy danh sách bình luận
    $query = "
        SELECT c.*, u.username, u.avatar, 
               (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) AS like_count
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.movie_id = ?
    ";
    $params = [$movieId];
    
    // Nếu có episode_id, lọc theo episode_id
    if ($episodeId) {
        $query .= " AND c.episode_id = ?";
        $params[] = $episodeId;
    }
    
    // Sắp xếp và giới hạn kết quả
    $query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $comments = $db->getAll($query, $params);
    
    // Lấy tổng số bình luận
    $countQuery = "
        SELECT COUNT(*) AS total
        FROM comments c
        WHERE c.movie_id = ?
    ";
    $countParams = [$movieId];
    
    // Nếu có episode_id, lọc theo episode_id
    if ($episodeId) {
        $countQuery .= " AND c.episode_id = ?";
        $countParams[] = $episodeId;
    }
    
    $countResult = $db->get($countQuery, $countParams);
    $totalComments = $countResult['total'];
    
    // Lấy danh sách bình luận con
    foreach ($comments as &$comment) {
        $replies = $db->getAll("
            SELECT r.*, u.username, u.avatar, 
                   (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = r.id) AS like_count
            FROM comments r
            JOIN users u ON r.user_id = u.id
            WHERE r.parent_id = ?
            ORDER BY r.created_at ASC
        ", [$comment['id']]);
        
        $comment['replies'] = $replies;
        
        // Kiểm tra xem người dùng hiện tại đã thích bình luận này chưa
        if (isLoggedIn()) {
            $currentUser = getCurrentUser();
            
            $liked = $db->get("
                SELECT id
                FROM comment_likes
                WHERE user_id = ? AND comment_id = ?
            ", [$currentUser['id'], $comment['id']]);
            
            $comment['is_liked'] = !empty($liked);
            
            // Kiểm tra xem người dùng hiện tại có phải là tác giả của bình luận không
            $comment['is_owner'] = $comment['user_id'] == $currentUser['id'];
            
            // Kiểm tra xem người dùng hiện tại có quyền xóa bình luận không
            $comment['can_delete'] = $comment['is_owner'] || isAdmin() || isModerator();
            
            // Xử lý bình luận con
            foreach ($comment['replies'] as &$reply) {
                $replyLiked = $db->get("
                    SELECT id
                    FROM comment_likes
                    WHERE user_id = ? AND comment_id = ?
                ", [$currentUser['id'], $reply['id']]);
                
                $reply['is_liked'] = !empty($replyLiked);
                $reply['is_owner'] = $reply['user_id'] == $currentUser['id'];
                $reply['can_delete'] = $reply['is_owner'] || isAdmin() || isModerator();
            }
        } else {
            $comment['is_liked'] = false;
            $comment['is_owner'] = false;
            $comment['can_delete'] = false;
            
            foreach ($comment['replies'] as &$reply) {
                $reply['is_liked'] = false;
                $reply['is_owner'] = false;
                $reply['can_delete'] = false;
            }
        }
    }
    
    // Tính toán thông tin phân trang
    $totalPages = ceil($totalComments / $limit);
    
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'pagination' => [
            'total' => $totalComments,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Xử lý API thêm bình luận
 */
function handleAddComment() {
    global $db;
    
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
        return;
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        return;
    }
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Thử lấy dữ liệu từ form post
        $data = $_POST;
    }
    
    if (!$data || !isset($data['movie_id']) || !isset($data['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $movieId = intval($data['movie_id']);
    $episodeId = isset($data['episode_id']) ? intval($data['episode_id']) : 0;
    $parentId = isset($data['parent_id']) ? intval($data['parent_id']) : 0;
    $content = sanitizeInput($data['content']);
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'];
    
    // Kiểm tra nội dung bình luận
    if (empty($content) || strlen($content) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nội dung bình luận quá ngắn']);
        return;
    }
    
    if (strlen($content) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nội dung bình luận không được vượt quá 1000 ký tự']);
        return;
    }
    
    // Kiểm tra phim có tồn tại không
    $movie = $db->get("
        SELECT id
        FROM movies
        WHERE id = ?
    ", [$movieId]);
    
    if (!$movie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phim']);
        return;
    }
    
    // Nếu có parent_id, kiểm tra bình luận cha có tồn tại không
    if ($parentId) {
        $parentComment = $db->get("
            SELECT id
            FROM comments
            WHERE id = ?
        ", [$parentId]);
        
        if (!$parentComment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bình luận cha']);
            return;
        }
    }
    
    // Thêm bình luận
    $commentId = $db->insert('comments', [
        'user_id' => $userId,
        'movie_id' => $movieId,
        'episode_id' => $episodeId,
        'parent_id' => $parentId,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$commentId) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thêm bình luận']);
        return;
    }
    
    // Lấy thông tin bình luận vừa thêm
    $comment = $db->get("
        SELECT c.*, u.username, u.avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ", [$commentId]);
    
    // Thêm thông tin bổ sung
    $comment['like_count'] = 0;
    $comment['is_liked'] = false;
    $comment['is_owner'] = true;
    $comment['can_delete'] = true;
    $comment['replies'] = [];
    
    echo json_encode(['success' => true, 'message' => 'Thêm bình luận thành công', 'comment' => $comment]);
}