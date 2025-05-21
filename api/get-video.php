<?php
/**
 * API Lấy thông tin video
 * 
 * Endpoint này trả về URL của video dựa trên ID tập phim và độ phân giải
 */

// Bao gồm các file cần thiết
require_once '../includes/init.php';

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Lấy tham số
$episodeId = isset($_GET['episode']) ? (int)$_GET['episode'] : 0;
$resolution = isset($_GET['resolution']) ? $_GET['resolution'] : '480p';

// Kiểm tra tham số
if ($episodeId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID tập phim không hợp lệ'
    ]);
    exit;
}

// Lấy thông tin tập phim
try {
    $episode = $db->get("
        SELECT e.*, m.is_vip AS movie_is_vip, m.id AS movie_id
        FROM episodes e
        JOIN movies m ON e.movie_id = m.id
        WHERE e.id = :episode_id
    ", ['episode_id' => $episodeId]);
    
    // Kiểm tra tập phim tồn tại không
    if (!$episode) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tập phim'
        ]);
        exit;
    }
    
    // Kiểm tra quyền truy cập
    $isVip = isset($_SESSION['is_vip']) && $_SESSION['is_vip'];
    $isVipContent = $episode['is_vip'] || $episode['movie_is_vip'];
    
    if ($isVipContent && !$isVip) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn cần là thành viên VIP để xem nội dung này'
        ]);
        exit;
    }
    
    // Kiểm tra độ phân giải
    $maxResolution = $isVip ? '4K' : '480p';
    
    // Chuyển đổi độ phân giải thành giá trị số để so sánh
    $resolutionMap = [
        '360p' => 360,
        '480p' => 480,
        '720p' => 720,
        '1080p' => 1080,
        '1440p' => 1440,
        '2160p' => 2160,
        '4K' => 2160
    ];
    
    $requestedQuality = $resolutionMap[$resolution] ?? 480;
    $maxAllowedQuality = $resolutionMap[$maxResolution];
    
    // Nếu yêu cầu độ phân giải cao hơn mức cho phép
    if ($requestedQuality > $maxAllowedQuality) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn cần là thành viên VIP để xem ở độ phân giải này'
        ]);
        exit;
    }
    
    // Lấy URL video dựa trên độ phân giải
    $videoUrl = '';
    
    switch ($resolution) {
        case '360p':
            $videoUrl = $episode['video_360'] ?? '';
            break;
        case '480p':
            $videoUrl = $episode['video_480'] ?? '';
            break;
        case '720p':
            $videoUrl = $episode['video_720'] ?? '';
            break;
        case '1080p':
            $videoUrl = $episode['video_1080'] ?? '';
            break;
        case '1440p':
            $videoUrl = $episode['video_1440'] ?? '';
            break;
        case '2160p':
        case '4K':
            $videoUrl = $episode['video_2160'] ?? '';
            break;
        default:
            $videoUrl = $episode['video_url'] ?? '';
    }
    
    // Nếu URL không tồn tại cho độ phân giải được yêu cầu
    if (empty($videoUrl)) {
        // Tìm URL cho độ phân giải thấp hơn gần nhất
        if ($resolution !== '360p' && empty($videoUrl)) {
            $videoUrl = $episode['video_480'] ?? '';
        }
        if ($resolution !== '360p' && $resolution !== '480p' && empty($videoUrl)) {
            $videoUrl = $episode['video_720'] ?? '';
        }
        if (empty($videoUrl)) {
            $videoUrl = $episode['video_url'] ?? '';
        }
        
        // Vẫn không tìm thấy URL nào
        if (empty($videoUrl)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy nguồn video cho tập phim này'
            ]);
            exit;
        }
    }
    
    // Trả về URL video
    echo json_encode([
        'success' => true,
        'url' => $videoUrl,
        'resolution' => $resolution,
        'episode_id' => $episodeId,
        'movie_id' => $episode['movie_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}