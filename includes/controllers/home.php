<?php
/**
 * Lọc Phim - Controller trang chủ
 * Quản lý hiển thị trang chủ
 */

class HomeController {
    /**
     * Đối tượng database
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Thông tin người dùng hiện tại
     * 
     * @var array|null
     */
    private $currentUser;
    
    /**
     * Khởi tạo controller
     * 
     * @param Database $db
     * @param array|null $currentUser
     */
    public function __construct($db, $currentUser = null) {
        $this->db = $db;
        $this->currentUser = $currentUser;
    }
    
    /**
     * Hiển thị trang chủ
     */
    public function index() {
        // Lấy phim nổi bật (banner)
        $featuredMovies = $this->db->getAll(
            "SELECT * FROM movies 
            WHERE is_featured = TRUE
            ORDER BY created_at DESC
            LIMIT 5"
        );
        
        // Lấy phim đề xuất
        $recommendedMovies = [];
        if ($this->currentUser) {
            // Nếu người dùng đã đăng nhập, lấy phim dựa trên thể loại họ đã xem
            $watchedCategories = $this->db->getAll(
                "SELECT DISTINCT c.id
                FROM categories c
                JOIN movie_categories mc ON c.id = mc.category_id
                JOIN watch_history wh ON mc.movie_id = wh.movie_id
                WHERE wh.user_id = :user_id
                ORDER BY wh.updated_at DESC
                LIMIT 5",
                ['user_id' => $this->currentUser['id']]
            );
            
            if (!empty($watchedCategories)) {
                $categoryIds = array_column($watchedCategories, 'id');
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                
                // Lấy phim thuộc thể loại đã xem
                $recommendedMovies = $this->db->getAll(
                    "SELECT DISTINCT m.* 
                    FROM movies m
                    JOIN movie_categories mc ON m.id = mc.movie_id
                    WHERE mc.category_id IN ($placeholders)
                    AND m.id NOT IN (
                        SELECT movie_id FROM watch_history WHERE user_id = ?
                    )
                    ORDER BY m.rating DESC, m.views DESC
                    LIMIT 10",
                    array_merge($categoryIds, [$this->currentUser['id']])
                );
            }
        }
        
        // Nếu không có phim đề xuất, lấy phim mới nhất
        if (empty($recommendedMovies)) {
            $recommendedMovies = $this->db->getAll(
                "SELECT * FROM movies 
                ORDER BY created_at DESC
                LIMIT 10"
            );
        }
        
        // Lấy phim chiếu rạp
        $theaterMovies = $this->db->getAll(
            "SELECT * FROM movies 
            WHERE is_theater = TRUE
            ORDER BY created_at DESC
            LIMIT 10"
        );
        
        // Lấy top phim hay nhất
        $topMovies = $this->db->getAll(
            "SELECT * FROM movies 
            ORDER BY rating DESC, views DESC
            LIMIT 5"
        );
        
        // Lấy anime mới
        $animeMovies = $this->db->getAll(
            "SELECT * FROM movies 
            WHERE is_anime = TRUE
            ORDER BY created_at DESC
            LIMIT 10"
        );
        
        // Lấy tập phim mới
        $recentEpisodes = $this->db->getAll(
            "SELECT e.*, m.title as movie_title, m.poster
            FROM episodes e
            JOIN movies m ON e.movie_id = m.id
            ORDER BY e.created_at DESC
            LIMIT 6"
        );
        
        // Hiển thị trang chủ
        include_once PAGES_PATH . '/home.php';
    }
}