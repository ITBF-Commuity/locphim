<?php
// Định nghĩa URL trang chủ
define('SITE_URL', 'https://localhost');

// Bao gồm các file cần thiết
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';

// Kiểm tra đăng nhập qua token (nếu có)
if (!isset($_SESSION['user_id'])) {
    check_remember_token();
}

// Lấy thông tin người dùng hiện tại
$current_user = get_logged_in_user();

// Xác định trang hiện tại
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$items_per_page = 24;
$offset = ($page - 1) * $items_per_page;

// Xác định loại danh sách và tham số lọc
$title = 'Tất cả phim';
$type = isset($_GET['type']) ? $_GET['type'] : null;
$category_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$year = isset($_GET['year']) ? intval($_GET['year']) : null;

// Xây dựng câu truy vấn SQL
$params = array();
$where_conditions = array("status = 1");

// Lọc theo loại
if ($type) {
    $where_conditions[] = "type = ?";
    $params[] = $type;
    $title = ucfirst($type == 'movie' ? 'Phim lẻ' : ($type == 'anime' ? 'Anime' : 'Phim bộ'));
}

// Lọc theo thể loại
if ($category_id) {
    $category = db_fetch_row("SELECT name FROM categories WHERE id = ?", array($category_id));
    if ($category) {
        $title = 'Thể loại: ' . $category['name'];
        
        // Sử dụng JOIN để lọc theo thể loại
        $movies_query = "
            SELECT DISTINCT m.* FROM movies m 
            JOIN movie_categories mc ON m.id = mc.movie_id 
            WHERE mc.category_id = ? AND " . implode(' AND ', $where_conditions);
        
        array_unshift($params, $category_id);
    }
} else {
    $movies_query = "SELECT * FROM movies WHERE " . implode(' AND ', $where_conditions);
}

// Lọc theo năm
if ($year) {
    $movies_query .= " AND YEAR(release_date) = ?";
    $params[] = $year;
    $title .= " - Năm " . $year;
}

// Sắp xếp
switch ($sort) {
    case 'popular':
        $movies_query .= " ORDER BY views DESC";
        $title .= " - Phổ biến nhất";
        break;
    case 'rating':
        $movies_query .= " ORDER BY rating DESC";
        $title .= " - Đánh giá cao nhất";
        break;
    case 'name':
        $movies_query .= " ORDER BY title ASC";
        $title .= " - Theo tên";
        break;
    default:
        $movies_query .= " ORDER BY created_at DESC";
        $title .= " - Mới nhất";
        break;
}

// Thêm phân trang
$movies_query .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

// Lấy danh sách phim
$movies = db_fetch_all($movies_query, $params);

// Đếm tổng số phim để phân trang
$count_params = $params;
array_pop($count_params); // Bỏ offset
array_pop($count_params); // Bỏ limit

$count_query = str_replace("SELECT DISTINCT m.*", "SELECT COUNT(DISTINCT m.id) as total", $movies_query);
$count_query = preg_replace('/LIMIT\s+\?\s+OFFSET\s+\?/i', '', $count_query);
$total_movies = db_fetch_row($count_query, $count_params)['total'];

$total_pages = ceil($total_movies / $items_per_page);

// Danh sách các năm để lọc
$years = db_fetch_all("SELECT DISTINCT YEAR(release_date) as year FROM movies ORDER BY year DESC");

// Lấy danh sách thể loại
$categories = get_categories();

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="category-page">
    <div class="container">
        <div class="row">
            <!-- Sidebar lọc -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <div class="filter-header">
                        <h3>Bộ lọc</h3>
                        <button class="btn-reset-filter">Reset</button>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Loại phim</h4>
                        <div class="filter-options">
                            <a href="?type=movie<?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="filter-option <?php echo $type == 'movie' ? 'active' : ''; ?>">Phim lẻ</a>
                            <a href="?type=series<?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="filter-option <?php echo $type == 'series' ? 'active' : ''; ?>">Phim bộ</a>
                            <a href="?type=anime<?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="filter-option <?php echo $type == 'anime' ? 'active' : ''; ?>">Anime</a>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Thể loại</h4>
                        <div class="filter-options scrollable">
                            <?php foreach ($categories as $category): ?>
                            <a href="?id=<?php echo $category['id']; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="filter-option <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                <?php echo $category['name']; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Năm phát hành</h4>
                        <div class="filter-options scrollable">
                            <?php foreach ($years as $y): ?>
                            <a href="?year=<?php echo $y['year']; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" class="filter-option <?php echo $year == $y['year'] ? 'active' : ''; ?>">
                                <?php echo $y['year']; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danh sách phim -->
            <div class="col-md-9">
                <div class="category-header">
                    <h1><?php echo $title; ?></h1>
                    
                    <div class="sort-options">
                        <label>Sắp xếp:</label>
                        <div class="btn-group" role="group">
                            <a href="?sort=latest<?php echo $type ? '&type=' . $type : ''; ?><?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="btn btn-outline-secondary <?php echo $sort == 'latest' || !$sort ? 'active' : ''; ?>">Mới nhất</a>
                            <a href="?sort=popular<?php echo $type ? '&type=' . $type : ''; ?><?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="btn btn-outline-secondary <?php echo $sort == 'popular' ? 'active' : ''; ?>">Phổ biến</a>
                            <a href="?sort=rating<?php echo $type ? '&type=' . $type : ''; ?><?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="btn btn-outline-secondary <?php echo $sort == 'rating' ? 'active' : ''; ?>">Đánh giá</a>
                            <a href="?sort=name<?php echo $type ? '&type=' . $type : ''; ?><?php echo $category_id ? '&id=' . $category_id : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="btn btn-outline-secondary <?php echo $sort == 'name' ? 'active' : ''; ?>">Tên phim</a>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($movies)): ?>
                <div class="no-results">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Không tìm thấy phim phù hợp với tiêu chí tìm kiếm.
                    </div>
                </div>
                <?php else: ?>
                <div class="movie-grid">
                    <?php foreach ($movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-thumbnail">
                            <a href="detail.php?slug=<?php echo $movie['slug']; ?>">
                                <img src="<?php echo $movie['thumbnail']; ?>" alt="<?php echo $movie['title']; ?>">
                                <div class="movie-overlay">
                                    <div class="movie-info">
                                        <span class="movie-quality"><?php echo $movie['quality']; ?></span>
                                        <span class="movie-duration"><?php echo $movie['duration']; ?></span>
                                    </div>
                                    <div class="movie-actions">
                                        <button class="btn-play"><i class="fas fa-play"></i></button>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="movie-details">
                            <h3 class="movie-title"><a href="detail.php?slug=<?php echo $movie['slug']; ?>"><?php echo $movie['title']; ?></a></h3>
                            <div class="movie-meta">
                                <span class="movie-type"><?php echo ucfirst($movie['type']); ?></span>
                                <span class="movie-views"><i class="fas fa-eye"></i> <?php echo format_views($movie['views']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper">
                    <?php
                    // Tạo URL cơ sở cho phân trang
                    $base_url = '?';
                    if ($type) $base_url .= 'type=' . $type . '&';
                    if ($category_id) $base_url .= 'id=' . $category_id . '&';
                    if ($sort) $base_url .= 'sort=' . $sort . '&';
                    if ($year) $base_url .= 'year=' . $year . '&';
                    
                    echo generate_pagination($page, $total_pages, $base_url);
                    ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
