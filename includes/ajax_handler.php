<?php
/**
 * File xử lý các yêu cầu AJAX
 */

// Bao gồm file cấu hình và hàm
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

// Khởi tạo database
$db = new Database();

// Lấy thông tin từ request
$ajax_action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Chuẩn bị kết quả
$result = [
    'success' => false,
    'message' => 'Đã xảy ra lỗi',
    'data' => null
];

// Xử lý các action
switch ($ajax_action) {
    // Tăng lượt xem phim
    case 'increment-view':
        // Lấy ID phim
        $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
        
        if ($movie_id > 0) {
            // Tăng lượt xem
            increment_movie_views($movie_id);
            
            $result = [
                'success' => true,
                'message' => 'Đã tăng lượt xem',
                'data' => null
            ];
        } else {
            $result['message'] = 'ID phim không hợp lệ';
        }
        break;
        
    // Tăng lượt xem tập phim
    case 'increment-episode-view':
        // Lấy ID tập phim
        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        
        if ($episode_id > 0) {
            // Tăng lượt xem
            increment_episode_views($episode_id);
            
            $result = [
                'success' => true,
                'message' => 'Đã tăng lượt xem tập phim',
                'data' => null
            ];
        } else {
            $result['message'] = 'ID tập phim không hợp lệ';
        }
        break;
        
    // Lưu thời gian xem
    case 'track-watch-time':
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user_id'])) {
            $result['message'] = 'Bạn cần đăng nhập để lưu tiến độ xem';
            break;
        }
        
        // Lấy dữ liệu
        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        $current_time = isset($_POST['current_time']) ? floatval($_POST['current_time']) : 0;
        $duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;
        
        if ($episode_id > 0 && $current_time > 0) {
            // Tính phần trăm đã xem
            $percent_watched = ($duration > 0) ? ($current_time / $duration) * 100 : 0;
            
            // Kiểm tra xem đã có watch_history chưa
            $history = $db->get("SELECT * FROM watch_history 
                              WHERE user_id = ? AND episode_id = ?", 
                             [$_SESSION['user_id'], $episode_id]);
            
            if ($history) {
                // Cập nhật
                $db->update('watch_history', 
                            ['watched_seconds' => $current_time, 
                             'completed' => ($percent_watched >= 90) ? true : false,
                             'updated_at' => date('Y-m-d H:i:s')], 
                            'id = ?', 
                            [$history['id']]);
            } else {
                // Thêm mới
                $insertData = [
                    'user_id' => $_SESSION['user_id'],
                    'episode_id' => $episode_id,
                    'watched_seconds' => $current_time,
                    'completed' => ($percent_watched >= 90) ? true : false,
                ];
                
                // Thêm các trường timestamp tùy theo loại database
                if ($db->getDbType() === 'pgsql') {
                    $insertData['last_watched_at'] = date('Y-m-d H:i:s');
                } else {
                    $insertData['created_at'] = date('Y-m-d H:i:s');
                    $insertData['updated_at'] = date('Y-m-d H:i:s');
                }
                
                $db->insert('watch_history', $insertData);
            }
            
            $result = [
                'success' => true,
                'message' => 'Đã lưu tiến độ xem',
                'data' => [
                    'current_time' => $current_time,
                    'percent_watched' => $percent_watched
                ]
            ];
        } else {
            $result['message'] = 'Dữ liệu không hợp lệ';
        }
        break;
        
    // Đánh dấu đã xem
    case 'track-watch-complete':
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user_id'])) {
            $result['message'] = 'Bạn cần đăng nhập để đánh dấu đã xem';
            break;
        }
        
        // Lấy dữ liệu
        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        $duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;
        
        if ($episode_id > 0) {
            // Kiểm tra xem đã có watch_history chưa
            $history = $db->get("SELECT * FROM watch_history 
                              WHERE user_id = ? AND episode_id = ?", 
                             [$_SESSION['user_id'], $episode_id]);
            
            if ($history) {
                // Cập nhật
                $updateData = [
                    'watched_seconds' => $duration, 
                    'completed' => true
                ];
                
                // Thêm các trường timestamp tùy theo loại database
                if ($db->getDbType() === 'pgsql') {
                    $updateData['last_watched_at'] = date('Y-m-d H:i:s');
                } else {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                }
                
                $db->update('watch_history', $updateData, 'id = ?', [$history['id']]);
            } else {
                // Thêm mới
                $insertData = [
                    'user_id' => $_SESSION['user_id'],
                    'episode_id' => $episode_id,
                    'watched_seconds' => $duration,
                    'completed' => true
                ];
                
                // Thêm các trường timestamp tùy theo loại database
                if ($db->getDbType() === 'pgsql') {
                    $insertData['last_watched_at'] = date('Y-m-d H:i:s');
                } else {
                    $insertData['created_at'] = date('Y-m-d H:i:s');
                    $insertData['updated_at'] = date('Y-m-d H:i:s');
                }
                
                $db->insert('watch_history', $insertData);
            }
            
            $result = [
                'success' => true,
                'message' => 'Đã đánh dấu xem hoàn thành',
                'data' => null
            ];
        } else {
            $result['message'] = 'Dữ liệu không hợp lệ';
        }
        break;
        
    // Thêm bình luận
    case 'add-comment':
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user_id'])) {
            $result['message'] = 'Bạn cần đăng nhập để bình luận';
            break;
        }
        
        // Lấy dữ liệu
        $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        
        if ($movie_id > 0 && !empty($content)) {
            // Thêm comment
            $comment_id = $db->insert('comments', [
                'user_id' => $_SESSION['user_id'],
                'movie_id' => $movie_id,
                'parent_id' => $parent_id,
                'content' => $content,
                'likes' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Lấy thông tin user
            $user = $db->get("SELECT username, avatar FROM users WHERE id = ?", [$_SESSION['user_id']]);
            
            $result = [
                'success' => true,
                'message' => 'Đã thêm bình luận',
                'data' => [
                    'id' => $comment_id,
                    'user_id' => $_SESSION['user_id'],
                    'movie_id' => $movie_id,
                    'parent_id' => $parent_id,
                    'content' => $content,
                    'likes' => 0,
                    'username' => $user['username'],
                    'user_avatar' => $user['avatar'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_at_formatted' => 'Vừa xong'
                ]
            ];
        } else {
            $result['message'] = 'Dữ liệu không hợp lệ';
        }
        break;
        
    // Thích bình luận
    case 'like-comment':
        // Lấy dữ liệu
        $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        
        if ($comment_id > 0) {
            // Tăng lượt thích
            $db->execute("UPDATE comments SET likes = likes + 1 WHERE id = ?", [$comment_id]);
            
            // Lấy số lượt thích mới
            $likes = $db->getValue("SELECT likes FROM comments WHERE id = ?", [$comment_id]);
            
            $result = [
                'success' => true,
                'message' => 'Đã thích bình luận',
                'data' => [
                    'comment_id' => $comment_id,
                    'likes' => $likes
                ]
            ];
        } else {
            $result['message'] = 'ID bình luận không hợp lệ';
        }
        break;
        
    // Thêm/Xóa yêu thích
    case 'toggle-favorite':
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user_id'])) {
            $result['message'] = 'Bạn cần đăng nhập để thực hiện chức năng này';
            break;
        }
        
        // Lấy dữ liệu
        $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
        
        if ($movie_id > 0) {
            // Kiểm tra xem đã yêu thích chưa
            $favorite = $db->get("SELECT * FROM favorites 
                               WHERE user_id = ? AND movie_id = ?", 
                              [$_SESSION['user_id'], $movie_id]);
            
            if ($favorite) {
                // Xóa yêu thích
                $db->delete('favorites', 
                           'user_id = ? AND movie_id = ?', 
                           [$_SESSION['user_id'], $movie_id]);
                
                $is_favorite = false;
                $message = 'Đã xóa khỏi danh sách yêu thích';
            } else {
                // Thêm yêu thích
                $db->insert('favorites', [
                    'user_id' => $_SESSION['user_id'],
                    'movie_id' => $movie_id,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $is_favorite = true;
                $message = 'Đã thêm vào danh sách yêu thích';
            }
            
            $result = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'movie_id' => $movie_id,
                    'is_favorite' => $is_favorite
                ]
            ];
        } else {
            $result['message'] = 'ID phim không hợp lệ';
        }
        break;
        
    // Mặc định
    default:
        $result['message'] = 'Action không hợp lệ';
        break;
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;