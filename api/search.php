<?php
/**
 * API Tìm kiếm phim
 * 
 * Endpoint này tìm kiếm phim dựa trên từ khóa
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
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 12;
$offset = ($page - 1) * $limit;

// Kiểm tra từ khóa
if (empty($keyword)) {
    echo json_encode([
        'success' => true,
        'message' => 'Từ khóa tìm kiếm không được để trống',
        'count' => 0,
        'movies' => []
    ]);
    exit;
}

try {
    // Tìm kiếm phim
    $keyword = "%" . $keyword . "%";
    
    // Đếm tổng số kết quả
    $totalCount = $db->getOne("
        SELECT COUNT(*) FROM movies 
        WHERE (title LIKE :keyword OR original_title LIKE :keyword) AND is_published = true
    ", ['keyword' => $keyword]);
    
    // Lấy kết quả phim
    $movies = $db->getAll("
        SELECT m.*, c.name as category_name 
        FROM movies m 
        LEFT JOIN movie_categories mc ON m.id = mc.movie_id 
        LEFT JOIN categories c ON mc.category_id = c.id 
        WHERE (m.title LIKE :keyword OR m.original_title LIKE :keyword) AND m.is_published = true 
        GROUP BY m.id 
        ORDER BY m.updated_at DESC 
        LIMIT :limit OFFSET :offset
    ", [
        'keyword' => $keyword,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
    // Xử lý dữ liệu trả về
    $result = [];
    foreach ($movies as $movie) {
        $result[] = [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'],
            'slug' => $movie['slug'],
            'poster' => $movie['poster'] ?? '/assets/images/default-poster.svg',
            'banner' => $movie['banner'] ?? '/assets/images/default-banner.svg',
            'description' => truncateString($movie['description'], 200),
            'release_year' => $movie['release_year'],
            'rating' => $movie['rating'],
            'views' => $movie['views'],
            'is_series' => (bool)$movie['is_series'],
            'is_anime' => (bool)$movie['is_anime'],
            'is_vip' => (bool)$movie['is_vip'],
            'category' => $movie['category_name']
        ];
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'keyword' => $_GET['q'],
        'count' => count($result),
        'total' => $totalCount,
        'page' => $page,
        'total_pages' => ceil($totalCount / $limit),
        'movies' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}