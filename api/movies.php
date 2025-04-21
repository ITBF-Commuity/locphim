<?php
/**
 * API lấy thông tin phim
 */

// Khởi tạo ứng dụng
require_once '../init.php';

// Kết nối database
try {
    $db = new PDO('sqlite:' . SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy phim theo ID
    if (isset($_GET['id'])) {
        $movie_id = (int)$_GET['id'];
        
        // Lấy thông tin phim
        $stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$movie) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phim'
            ]);
            exit;
        }
        
        // Lấy danh sách thể loại
        $categories_stmt = $db->prepare("SELECT c.id 
                                       FROM categories c 
                                       JOIN movie_categories mc ON c.id = mc.category_id 
                                       WHERE mc.movie_id = ?");
        $categories_stmt->execute([$movie_id]);
        $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $movie['categories'] = $categories;
        
        // Lấy danh sách tập phim
        if ($movie['type'] != 'movie') {
            $episodes_stmt = $db->prepare("SELECT * FROM episodes WHERE movie_id = ? ORDER BY episode_number");
            $episodes_stmt->execute([$movie_id]);
            $episodes = $episodes_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $movie['episodes'] = $episodes;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $movie
        ]);
        exit;
    }
    
    // Lấy danh sách phim mới nhất
    else if (isset($_GET['latest'])) {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        
        $params = [];
        $type_clause = '';
        
        if (!empty($type)) {
            $type_clause = "AND type = ?";
            $params[] = $type;
        }
        
        $query = "SELECT * FROM movies WHERE status = 1 $type_clause ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $movies
        ]);
        exit;
    }
    
    // Lấy phim theo thể loại
    else if (isset($_GET['category'])) {
        $category_id = (int)$_GET['category'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $query = "SELECT m.* 
                FROM movies m 
                JOIN movie_categories mc ON m.id = mc.movie_id 
                WHERE mc.category_id = ? AND m.status = 1 
                ORDER BY m.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$category_id, $limit, $offset]);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $movies
        ]);
        exit;
    }
    
    // Lấy phim theo slug
    else if (isset($_GET['slug'])) {
        $slug = $_GET['slug'];
        
        $stmt = $db->prepare("SELECT * FROM movies WHERE slug = ? AND status = 1");
        $stmt->execute([$slug]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$movie) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phim'
            ]);
            exit;
        }
        
        // Lấy danh sách thể loại
        $categories_stmt = $db->prepare("SELECT c.id, c.name, c.slug 
                                       FROM categories c 
                                       JOIN movie_categories mc ON c.id = mc.category_id 
                                       WHERE mc.movie_id = ?");
        $categories_stmt->execute([$movie['id']]);
        $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $movie['categories'] = $categories;
        
        // Lấy danh sách tập phim
        if ($movie['type'] != 'movie') {
            $episodes_stmt = $db->prepare("SELECT * FROM episodes WHERE movie_id = ? ORDER BY episode_number");
            $episodes_stmt->execute([$movie['id']]);
            $episodes = $episodes_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $movie['episodes'] = $episodes;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $movie
        ]);
        exit;
    }
    
    // Mặc định trả về danh sách phim
    else {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $query = "SELECT * FROM movies WHERE status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $movies
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}