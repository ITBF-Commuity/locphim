<?php
/**
 * Lọc Phim - Controller phim
 * Quản lý hiển thị, xem phim, thể loại
 */

class MovieController {
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
     * Hiển thị chi tiết phim
     * 
     * @param int $movieId ID của phim
     */
    public function detail($movieId) {
        // Lấy thông tin phim
        $movie = $this->db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
        
        if (!$movie) {
            // Phim không tồn tại
            include_once PAGES_PATH . '/404.php';
            return;
        }
        
        // Cập nhật lượt xem phim
        $this->db->query(
            "UPDATE movies SET views = views + 1 WHERE id = :id",
            ['id' => $movieId]
        );
        
        // Lấy danh sách tập phim
        $episodes = $this->db->getAll(
            "SELECT * FROM episodes WHERE movie_id = :movie_id ORDER BY episode_number ASC",
            ['movie_id' => $movieId]
        );
        
        // Lấy thể loại của phim
        $categories = $this->db->getAll(
            "SELECT c.* 
            FROM categories c
            JOIN movie_categories mc ON c.id = mc.category_id
            WHERE mc.movie_id = :movie_id
            ORDER BY c.name",
            ['movie_id' => $movieId]
        );
        
        // Kiểm tra trạng thái yêu thích
        $isFavorite = false;
        if ($this->currentUser) {
            $favorite = $this->db->get(
                "SELECT * FROM favorites WHERE user_id = :user_id AND movie_id = :movie_id",
                [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId
                ]
            );
            $isFavorite = !!$favorite;
        }
        
        // Lấy phim liên quan
        $relatedMovies = [];
        if (!empty($categories)) {
            $categoryIds = array_column($categories, 'id');
            $categoryIdPlaceholders = implode(',', array_fill(0, count($categoryIds), '?'));
            
            $params = $categoryIds;
            $params[] = $movieId;
            
            $relatedMovies = $this->db->getAll(
                "SELECT DISTINCT m.* 
                FROM movies m
                JOIN movie_categories mc ON m.id = mc.movie_id
                WHERE mc.category_id IN ($categoryIdPlaceholders)
                AND m.id != ?
                ORDER BY m.views DESC
                LIMIT 10",
                $params
            );
        }
        
        // Kiểm tra đánh giá của người dùng
        $userRating = null;
        if ($this->currentUser) {
            $userRating = $this->db->get(
                "SELECT * FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id",
                [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId
                ]
            );
        }
        
        // Lấy bình luận của phim
        $comments = $this->db->getAll(
            "SELECT c.*, u.username, u.avatar
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.movie_id = :movie_id AND c.parent_id IS NULL
            ORDER BY c.created_at DESC
            LIMIT 20",
            ['movie_id' => $movieId]
        );
        
        // Lấy bình luận con
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->db->getAll(
                "SELECT c.*, u.username, u.avatar
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.parent_id = :parent_id
                ORDER BY c.created_at ASC",
                ['parent_id' => $comment['id']]
            );
        }
        
        // Lấy tiến trình xem gần đây
        $watchProgress = null;
        if ($this->currentUser) {
            $watchProgress = $this->db->get(
                "SELECT * FROM watch_progress 
                WHERE user_id = :user_id AND movie_id = :movie_id
                ORDER BY updated_at DESC
                LIMIT 1",
                [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId
                ]
            );
        }
        
        // Hiển thị trang chi tiết phim
        include_once PAGES_PATH . '/movies/detail.php';
    }
    
    /**
     * Hiển thị trang xem phim
     * 
     * @param int $movieId ID của phim
     * @param int $episodeId ID của tập phim
     */
    public function watch($movieId, $episodeId) {
        // Lấy thông tin phim
        $movie = $this->db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
        
        if (!$movie) {
            // Phim không tồn tại
            include_once PAGES_PATH . '/404.php';
            return;
        }
        
        // Lấy thông tin tập phim
        $episode = $this->db->get("SELECT * FROM episodes WHERE id = :id AND movie_id = :movie_id", [
            'id' => $episodeId,
            'movie_id' => $movieId
        ]);
        
        if (!$episode) {
            // Tập phim không tồn tại
            include_once PAGES_PATH . '/404.php';
            return;
        }
        
        // Nếu là phim VIP, kiểm tra người dùng có quyền xem không
        $canAccessVip = can_access_vip_content($this->currentUser);
        
        if ($movie['is_vip'] && !$canAccessVip) {
            // Chuyển hướng đến trang nâng cấp VIP
            include_once PAGES_PATH . '/payment/vip-required.php';
            return;
        }
        
        // Lấy danh sách tập phim
        $episodes = $this->db->getAll(
            "SELECT * FROM episodes WHERE movie_id = :movie_id ORDER BY episode_number ASC",
            ['movie_id' => $movieId]
        );
        
        // Lấy tiến trình xem của người dùng
        $watchTime = 0;
        if ($this->currentUser) {
            $progress = $this->db->get(
                "SELECT * FROM watch_progress 
                WHERE user_id = :user_id AND movie_id = :movie_id AND episode_id = :episode_id",
                [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId,
                    'episode_id' => $episodeId
                ]
            );
            
            if ($progress) {
                $watchTime = $progress['watch_time'];
            }
            
            // Cập nhật lịch sử xem
            $existingHistory = $this->db->get(
                "SELECT * FROM watch_history 
                WHERE user_id = :user_id AND movie_id = :movie_id AND episode_id = :episode_id",
                [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId,
                    'episode_id' => $episodeId
                ]
            );
            
            if ($existingHistory) {
                $this->db->update('watch_history', [
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', [
                    'id' => $existingHistory['id']
                ]);
            } else {
                $this->db->insert('watch_history', [
                    'user_id' => $this->currentUser['id'],
                    'movie_id' => $movieId,
                    'episode_id' => $episodeId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Cập nhật lượt xem tập phim
        $this->db->query(
            "UPDATE episodes SET views = views + 1 WHERE id = :id",
            ['id' => $episodeId]
        );
        
        // Xác định độ phân giải tối đa cho người dùng
        $maxResolution = $canAccessVip ? VIP_MAX_RESOLUTION : FREE_MAX_RESOLUTION;
        
        // Xác định xem có hiển thị quảng cáo không
        $showAds = ADS_ENABLED && !$canAccessVip;
        
        // Lấy cấu hình quảng cáo
        $adPoints = [];
        if ($showAds) {
            if (ADS_PREROLL) {
                $adPoints[] = 0; // Quảng cáo đầu video
            }
            
            if (ADS_MIDROLL) {
                $midrollTimes = explode(',', ADS_MIDROLL_TIME);
                foreach ($midrollTimes as $time) {
                    $adPoints[] = (int)$time; // Quảng cáo giữa video
                }
            }
        }
        
        // Tạo danh sách các độ phân giải có sẵn
        $availableResolutions = get_available_resolutions($episode);
        
        // Lọc các độ phân giải không được phép cho người dùng thường
        if (!$canAccessVip) {
            $allowedResolutions = get_allowed_resolutions();
            $availableResolutions = array_filter($availableResolutions, function($resolution) use ($allowedResolutions) {
                return in_array($resolution, $allowedResolutions);
            });
        }
        
        // Hiển thị trang xem phim
        include_once PAGES_PATH . '/movies/watch.php';
    }
    
    /**
     * Hiển thị danh sách phim lẻ
     */
    public function singleMovies() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách phim lẻ
        $movies = $this->db->getAll(
            "SELECT * FROM movies 
            WHERE id NOT IN (
                SELECT DISTINCT movie_id FROM episodes WHERE episode_number > 1
            )
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số phim
        $totalMovies = $this->db->getOne(
            "SELECT COUNT(*) FROM movies 
            WHERE id NOT IN (
                SELECT DISTINCT movie_id FROM episodes WHERE episode_number > 1
            )"
        );
        
        $totalPages = ceil($totalMovies / $limit);
        
        // Hiển thị trang phim lẻ
        include_once PAGES_PATH . '/movies/list.php';
    }
    
    /**
     * Hiển thị danh sách phim bộ
     */
    public function seriesMovies() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách phim bộ
        $movies = $this->db->getAll(
            "SELECT m.*, 
                   (SELECT COUNT(*) FROM episodes WHERE movie_id = m.id) as episode_count
            FROM movies m
            WHERE m.id IN (
                SELECT DISTINCT movie_id FROM episodes WHERE episode_number > 1
            )
            ORDER BY m.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số phim
        $totalMovies = $this->db->getOne(
            "SELECT COUNT(*) FROM movies 
            WHERE id IN (
                SELECT DISTINCT movie_id FROM episodes WHERE episode_number > 1
            )"
        );
        
        $totalPages = ceil($totalMovies / $limit);
        
        // Hiển thị trang phim bộ
        include_once PAGES_PATH . '/movies/list.php';
    }
    
    /**
     * Hiển thị danh sách anime
     */
    public function anime() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách anime
        $movies = $this->db->getAll(
            "SELECT m.*, 
                   (SELECT COUNT(*) FROM episodes WHERE movie_id = m.id) as episode_count
            FROM movies m
            WHERE m.is_anime = TRUE
            ORDER BY m.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số anime
        $totalMovies = $this->db->getOne(
            "SELECT COUNT(*) FROM movies WHERE is_anime = TRUE"
        );
        
        $totalPages = ceil($totalMovies / $limit);
        
        // Hiển thị trang anime
        include_once PAGES_PATH . '/movies/list.php';
    }
    
    /**
     * Hiển thị danh sách phim chiếu rạp
     */
    public function theaterMovies() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách phim chiếu rạp
        $movies = $this->db->getAll(
            "SELECT * FROM movies 
            WHERE is_theater = TRUE
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số phim
        $totalMovies = $this->db->getOne(
            "SELECT COUNT(*) FROM movies WHERE is_theater = TRUE"
        );
        
        $totalPages = ceil($totalMovies / $limit);
        
        // Hiển thị trang phim chiếu rạp
        include_once PAGES_PATH . '/movies/list.php';
    }
    
    /**
     * Hiển thị danh sách phim mới
     */
    public function recentMovies() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách phim mới
        $movies = $this->db->getAll(
            "SELECT m.*, 
                   (SELECT COUNT(*) FROM episodes WHERE movie_id = m.id) as episode_count
            FROM movies m
            ORDER BY m.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số phim
        $totalMovies = $this->db->getOne("SELECT COUNT(*) FROM movies");
        
        $totalPages = ceil($totalMovies / $limit);
        
        // Hiển thị trang phim mới
        include_once PAGES_PATH . '/movies/list.php';
    }
    
    /**
     * Hiển thị danh sách tập phim mới
     */
    public function recentEpisodes() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Lấy danh sách tập phim mới
        $episodes = $this->db->getAll(
            "SELECT e.*, m.title as movie_title, m.poster
            FROM episodes e
            JOIN movies m ON e.movie_id = m.id
            ORDER BY e.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset
            ]
        );
        
        // Đếm tổng số tập phim
        $totalEpisodes = $this->db->getOne("SELECT COUNT(*) FROM episodes");
        
        $totalPages = ceil($totalEpisodes / $limit);
        
        // Hiển thị trang tập phim mới
        include_once PAGES_PATH . '/movies/episodes.php';
    }
    
    /**
     * Cập nhật trạng thái yêu thích phim
     */
    public function toggleFavorite() {
        if (!$this->currentUser) {
            // Yêu cầu đăng nhập
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này']);
            exit;
        }
        
        $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        
        if (!$movieId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phim']);
            exit;
        }
        
        // Kiểm tra phim có tồn tại không
        $movie = $this->db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
        
        if (!$movie) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phim không tồn tại']);
            exit;
        }
        
        // Kiểm tra trạng thái yêu thích hiện tại
        $favorite = $this->db->get(
            "SELECT * FROM favorites WHERE user_id = :user_id AND movie_id = :movie_id",
            [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId
            ]
        );
        
        if ($favorite) {
            // Nếu đã yêu thích, hủy yêu thích
            $this->db->delete('favorites', 'id = :id', ['id' => $favorite['id']]);
            $isFavorite = false;
            $message = 'Đã xóa khỏi danh sách yêu thích';
        } else {
            // Nếu chưa yêu thích, thêm vào yêu thích
            $this->db->insert('favorites', [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $isFavorite = true;
            $message = 'Đã thêm vào danh sách yêu thích';
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $message
        ]);
        exit;
    }
    
    /**
     * Cập nhật thời gian xem phim
     */
    public function updateWatchTime() {
        if (!$this->currentUser) {
            // Không cần thiết phải trả về lỗi cho người dùng không đăng nhập
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }
        
        $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $episodeId = isset($_POST['episode_id']) ? (int)$_POST['episode_id'] : 0;
        $watchTime = isset($_POST['watch_time']) ? (int)$_POST['watch_time'] : 0;
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
        
        if (!$movieId || !$episodeId || $watchTime < 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }
        
        // Kiểm tra tập phim có tồn tại không
        $episode = $this->db->get(
            "SELECT * FROM episodes WHERE id = :id AND movie_id = :movie_id",
            [
                'id' => $episodeId,
                'movie_id' => $movieId
            ]
        );
        
        if (!$episode) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }
        
        // Kiểm tra tiến trình xem đã tồn tại chưa
        $progress = $this->db->get(
            "SELECT * FROM watch_progress 
            WHERE user_id = :user_id AND movie_id = :movie_id AND episode_id = :episode_id",
            [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId,
                'episode_id' => $episodeId
            ]
        );
        
        if ($progress) {
            // Cập nhật tiến trình
            $this->db->update('watch_progress', [
                'watch_time' => $watchTime,
                'duration' => $duration,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [
                'id' => $progress['id']
            ]);
        } else {
            // Tạo tiến trình mới
            $this->db->insert('watch_progress', [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId,
                'episode_id' => $episodeId,
                'watch_time' => $watchTime,
                'duration' => $duration,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Xác định xem đã xem hết video chưa
        $completed = false;
        if ($duration > 0 && $watchTime >= $duration * 0.9) {
            $completed = true;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'completed' => $completed
        ]);
        exit;
    }
    
    /**
     * Thêm đánh giá phim
     */
    public function addRating() {
        if (!$this->currentUser) {
            // Yêu cầu đăng nhập
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá phim']);
            exit;
        }
        
        $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        
        if (!$movieId || $rating < 1 || $rating > 5) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
            exit;
        }
        
        // Kiểm tra phim có tồn tại không
        $movie = $this->db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
        
        if (!$movie) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phim không tồn tại']);
            exit;
        }
        
        // Kiểm tra đánh giá đã tồn tại chưa
        $existingRating = $this->db->get(
            "SELECT * FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id",
            [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId
            ]
        );
        
        if ($existingRating) {
            // Cập nhật đánh giá
            $this->db->update('ratings', [
                'rating' => $rating,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [
                'id' => $existingRating['id']
            ]);
        } else {
            // Tạo đánh giá mới
            $this->db->insert('ratings', [
                'user_id' => $this->currentUser['id'],
                'movie_id' => $movieId,
                'rating' => $rating,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Cập nhật điểm đánh giá trung bình của phim
        $avgRating = $this->db->getOne(
            "SELECT AVG(rating) FROM ratings WHERE movie_id = :movie_id",
            ['movie_id' => $movieId]
        );
        
        $this->db->update('movies', [
            'rating' => $avgRating,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $movieId
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'rating' => $rating,
            'avg_rating' => round($avgRating, 1),
            'message' => 'Đánh giá của bạn đã được ghi nhận'
        ]);
        exit;
    }
    
    /**
     * Thêm bình luận
     */
    public function addComment() {
        if (!$this->currentUser) {
            // Yêu cầu đăng nhập
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để bình luận']);
            exit;
        }
        
        $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $episodeId = isset($_POST['episode_id']) ? (int)$_POST['episode_id'] : null;
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        if (!$movieId || empty($content)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
            exit;
        }
        
        // Kiểm tra phim có tồn tại không
        $movie = $this->db->get("SELECT * FROM movies WHERE id = :id", ['id' => $movieId]);
        
        if (!$movie) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phim không tồn tại']);
            exit;
        }
        
        // Kiểm tra bình luận cha nếu có
        if ($parentId) {
            $parentComment = $this->db->get("SELECT * FROM comments WHERE id = :id", ['id' => $parentId]);
            
            if (!$parentComment) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Bình luận cha không tồn tại']);
                exit;
            }
        }
        
        // Thêm bình luận mới
        $commentId = $this->db->insert('comments', [
            'user_id' => $this->currentUser['id'],
            'movie_id' => $movieId,
            'episode_id' => $episodeId,
            'parent_id' => $parentId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Bình luận của bạn đã được gửi',
            'comment' => [
                'id' => $commentId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'user' => [
                    'username' => $this->currentUser['username'],
                    'avatar' => $this->currentUser['avatar']
                ]
            ]
        ]);
        exit;
    }
    
    /**
     * Lấy danh sách video theo độ phân giải
     * 
     * @param array $episode Thông tin tập phim
     * @return array Danh sách độ phân giải có sẵn
     */
    private function get_available_resolutions($episode) {
        $resolutions = ['360p', '480p', '720p', '1080p', '1440p', '2160p'];
        
        // Trong trường hợp thực tế, bạn sẽ kiểm tra tồn tại của các file video
        // với các độ phân giải khác nhau. Ở đây chúng ta giả định tất cả đều có sẵn.
        return $resolutions;
    }
}