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

// Xác định từ khóa tìm kiếm
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Xác định trang hiện tại
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$items_per_page = 24;
$offset = ($page - 1) * $items_per_page;

// Xác định bộ lọc
$type = isset($_GET['type']) ? $_GET['type'] : null;
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

// Mảng chứa kết quả tìm kiếm
$results = array();
$total_results = 0;

// Lấy danh sách thể loại
$categories = get_categories();

// Thực hiện tìm kiếm khi có từ khóa
if (!empty($keyword)) {
    // Xây dựng điều kiện tìm kiếm
    $conditions = array();
    $params = array();
    
    // Tìm kiếm theo tiêu đề, tiêu đề gốc, mô tả
    $search_fields = array(
        "title LIKE ?",
        "original_title LIKE ?",
        "description LIKE ?"
    );
    
    $search_term = "%{$keyword}%";
    foreach ($search_fields as $field) {
        $conditions[] = $field;
        $params[] = $search_term;
    }
    
    // Điều kiện trạng thái phim
    $base_condition = "((" . implode(") OR (", $conditions) . ")) AND status = 1";
    
    // Lọc theo loại phim
    if ($type) {
        $base_condition .= " AND type = ?";
        $params[] = $type;
    }
    
    // Lọc theo thể loại
    $query = "SELECT DISTINCT m.* FROM movies m";
    
    if ($category_id) {
        $query .= " JOIN movie_categories mc ON m.id = mc.movie_id";
        $base_condition .= " AND mc.category_id = ?";
        $params[] = $category_id;
    }
    
    $query .= " WHERE " . $base_condition;
    
    // Sắp xếp kết quả
    switch ($sort) {
        case 'latest':
            $query .= " ORDER BY m.created_at DESC";
            break;
        case 'popular':
            $query .= " ORDER BY m.views DESC";
            break;
        case 'rating':
            $query .= " ORDER BY m.rating DESC";
            break;
        default:
            // Sắp xếp theo độ phù hợp (mặc định)
            $query .= " ORDER BY (
                CASE 
                    WHEN m.title LIKE ? THEN 10
                    WHEN m.title LIKE ? THEN 5
                    WHEN m.original_title LIKE ? THEN 3
                    ELSE 1
                END
            ) DESC, m.views DESC";
            
            // Thêm các tham số cho độ phù hợp
            $params[] = $keyword . "%"; // Bắt đầu bằng keyword
            $params[] = "% " . $keyword . "%"; // Có chứa keyword sau khoảng trắng
            $params[] = "%{$keyword}%"; // Chứa keyword trong tiêu đề gốc
            break;
    }
    
    // Thêm phân trang
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    
    // Thực hiện truy vấn
    $results = db_fetch_all($query, $params);
    
    // Đếm tổng số kết quả
    $count_params = $params;
    array_pop($count_params); // Bỏ offset
    array_pop($count_params); // Bỏ limit
    
    // Nếu sắp xếp theo độ phù hợp, bỏ thêm 3 tham số cuối
    if ($sort === 'relevance' || empty($sort)) {
        array_pop($count_params);
        array_pop($count_params);
        array_pop($count_params);
    }
    
    $count_query = str_replace("SELECT DISTINCT m.*", "SELECT COUNT(DISTINCT m.id) as total", $query);
    $count_query = preg_replace('/ORDER BY.*?LIMIT/s', 'ORDER BY m.id LIMIT', $count_query);
    $count_query = preg_replace('/LIMIT\s+\?\s+OFFSET\s+\?/i', '', $count_query);
    
    $total_results = db_fetch_row($count_query, $count_params)['total'];
}

$total_pages = ceil($total_results / $items_per_page);

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="search-page">
    <div class="container">
        <div class="search-header">
            <h1>Kết quả tìm kiếm: <?php echo htmlspecialchars($keyword); ?></h1>
            <p>Tìm thấy <?php echo $total_results; ?> kết quả phù hợp</p>
        </div>
        
        <div class="row">
            <!-- Sidebar lọc -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <div class="filter-header">
                        <h3>Bộ lọc tìm kiếm</h3>
                        <a href="search.php?q=<?php echo urlencode($keyword); ?>" class="btn-reset-filter">Reset</a>
                    </div>
                    
                    <form action="search.php" method="get" class="filter-form">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($keyword); ?>">
                        
                        <div class="filter-section">
                            <h4>Loại phim</h4>
                            <div class="filter-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_all" value="" <?php echo !$type ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="type_all">Tất cả</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_movie" value="movie" <?php echo $type === 'movie' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="type_movie">Phim lẻ</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_series" value="series" <?php echo $type === 'series' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="type_series">Phim bộ</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_anime" value="anime" <?php echo $type === 'anime' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="type_anime">Anime</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="filter-section">
                            <h4>Thể loại</h4>
                            <select class="form-select" name="category">
                                <option value="">Tất cả thể loại</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-section">
                            <h4>Sắp xếp</h4>
                            <div class="filter-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort" id="sort_relevance" value="relevance" <?php echo $sort === 'relevance' || !$sort ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sort_relevance">Độ phù hợp</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort" id="sort_latest" value="latest" <?php echo $sort === 'latest' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sort_latest">Mới nhất</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort" id="sort_popular" value="popular" <?php echo $sort === 'popular' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sort_popular">Phổ biến nhất</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort" id="sort_rating" value="rating" <?php echo $sort === 'rating' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sort_rating">Đánh giá cao</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Áp dụng bộ lọc</button>
                    </form>
                </div>
            </div>
            
            <!-- Kết quả tìm kiếm -->
            <div class="col-md-9">
                <?php if (empty($keyword)): ?>
                <div class="empty-search">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Vui lòng nhập từ khóa để tìm kiếm phim.
                    </div>
                </div>
                <?php elseif (empty($results)): ?>
                <div class="no-results">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Không tìm thấy phim phù hợp với từ khóa "<strong><?php echo htmlspecialchars($keyword); ?></strong>".
                    </div>
                    <div class="search-tips">
                        <h3>Gợi ý:</h3>
                        <ul>
                            <li>Kiểm tra lại chính tả của từ khóa tìm kiếm</li>
                            <li>Thử sử dụng từ khóa ngắn hơn hoặc chính xác hơn</li>
                            <li>Thử tìm kiếm bằng tên gốc của phim (tiếng Anh, Nhật, Hàn,...)</li>
                            <li>Sử dụng bộ lọc để mở rộng kết quả tìm kiếm</li>
                        </ul>
                    </div>
                </div>
                <?php else: ?>
                <div class="movie-grid">
                    <?php foreach ($results as $movie): ?>
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
                    $base_url = 'search.php?q=' . urlencode($keyword);
                    if ($type) $base_url .= '&type=' . $type;
                    if ($category_id) $base_url .= '&category=' . $category_id;
                    if ($sort) $base_url .= '&sort=' . $sort;
                    
                    echo generate_pagination($page, $total_pages, $base_url);
                    ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tự động gửi form khi thay đổi loại phim
        const typeRadios = document.querySelectorAll('input[name="type"]');
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        });
        
        // Tự động gửi form khi thay đổi cách sắp xếp
        const sortRadios = document.querySelectorAll('input[name="sort"]');
        sortRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        });
        
        // Tự động gửi form khi thay đổi thể loại
        const categorySelect = document.querySelector('select[name="category"]');
        categorySelect.addEventListener('change', function() {
            document.querySelector('.filter-form').submit();
        });
    });
</script>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
