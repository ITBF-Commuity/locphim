<?php
// Tiêu đề trang
$page_title = 'Bảng xếp hạng';

// Include header
include 'header.php';

// Lấy loại bảng xếp hạng
$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'views';
$time = isset($_GET['time']) ? sanitize_input($_GET['time']) : 'all';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Lấy danh sách thể loại
$categories_sql = "SELECT * FROM categories ORDER BY name ASC";
$categories = db_query($categories_sql, [], true);

// Xây dựng bộ lọc
$filters = ['sort' => $type];
if ($category > 0) {
    $filters['category_id'] = $category;
}

// Lấy danh sách bảng xếp hạng
$limit = 30;
$offset = 0;

if ($time == 'week') {
    // Top trong tuần
    $sql = "SELECT a.*, c.name as category_name, COUNT(v.id) as view_count 
            FROM anime a 
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN views v ON a.id = v.anime_id AND v.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            WHERE 1=1";
    
    if ($category > 0) {
        $sql .= " AND a.category_id = ?";
        $params = [$category];
    } else {
        $params = [];
    }
    
    $sql .= " GROUP BY a.id
              ORDER BY view_count DESC, a.rating DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $ranking = db_query($sql, $params, true);
} elseif ($time == 'month') {
    // Top trong tháng
    $sql = "SELECT a.*, c.name as category_name, COUNT(v.id) as view_count 
            FROM anime a 
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN views v ON a.id = v.anime_id AND v.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE 1=1";
    
    if ($category > 0) {
        $sql .= " AND a.category_id = ?";
        $params = [$category];
    } else {
        $params = [];
    }
    
    $sql .= " GROUP BY a.id
              ORDER BY view_count DESC, a.rating DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $ranking = db_query($sql, $params, true);
} else {
    // Top tất cả thời gian
    $ranking = get_anime(null, $limit, $offset, $filters);
}

// Lấy danh sách top anime mới
$newest_anime = get_anime(null, 10, 0, ['sort' => 'newest']);

// Lấy danh sách top anime đánh giá cao
$top_rated_anime = get_anime(null, 10, 0, ['sort' => 'rating']);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bảng xếp hạng</li>
    </ol>
</nav>

<!-- Tiêu đề trang -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title">
        <i class="fas fa-trophy text-warning me-2"></i> Bảng xếp hạng anime
    </h1>
</div>

<!-- Bộ lọc -->
<div class="card mb-4">
    <div class="card-body">
        <form action="ranking.php" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Sắp xếp theo</label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="views" <?php echo $type == 'views' ? 'selected' : ''; ?>>Lượt xem</option>
                    <option value="rating" <?php echo $type == 'rating' ? 'selected' : ''; ?>>Đánh giá</option>
                    <option value="newest" <?php echo $type == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="time" class="form-label">Thời gian</label>
                <select class="form-select" id="time" name="time" onchange="this.form.submit()">
                    <option value="all" <?php echo $time == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="week" <?php echo $time == 'week' ? 'selected' : ''; ?>>7 ngày qua</option>
                    <option value="month" <?php echo $time == 'month' ? 'selected' : ''; ?>>30 ngày qua</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="category" class="form-label">Thể loại</label>
                <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                    <option value="0">Tất cả thể loại</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Bảng xếp hạng chính -->
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">
                    <?php if ($type == 'views'): ?>
                        <i class="fas fa-eye me-2"></i> Top anime nhiều lượt xem
                    <?php elseif ($type == 'rating'): ?>
                        <i class="fas fa-star me-2"></i> Top anime đánh giá cao
                    <?php else: ?>
                        <i class="fas fa-calendar-alt me-2"></i> Anime mới nhất
                    <?php endif; ?>
                    
                    <?php if ($time == 'week'): ?>
                        (7 ngày qua)
                    <?php elseif ($time == 'month'): ?>
                        (30 ngày qua)
                    <?php endif; ?>
                    
                    <?php if ($category > 0): ?>
                        - <?php 
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $category) {
                                    echo $cat['name'];
                                    break;
                                }
                            }
                        ?>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">Hạng</th>
                                <th width="15%">Ảnh</th>
                                <th>Thông tin</th>
                                <th width="15%" class="text-center">
                                    <?php if ($type == 'views'): ?>
                                        Lượt xem
                                    <?php elseif ($type == 'rating'): ?>
                                        Đánh giá
                                    <?php else: ?>
                                        Ngày phát hành
                                    <?php endif; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ranking) > 0): ?>
                                <?php foreach ($ranking as $index => $anime): ?>
                                    <tr class="<?php echo $index < 3 ? 'table-warning' : ''; ?>">
                                        <td class="text-center align-middle">
                                            <span class="rank-number <?php echo $index < 3 ? 'top-rank' : ''; ?>">
                                                <?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="anime-detail.php?id=<?php echo $anime['id']; ?>">
                                                <img src="<?php echo get_thumbnail($anime['thumbnail'], 'small'); ?>" class="img-fluid rounded" alt="<?php echo $anime['title']; ?>">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="anime-detail.php?id=<?php echo $anime['id']; ?>" class="h6 fw-bold d-block text-decoration-none"><?php echo $anime['title']; ?></a>
                                            <div class="small text-muted">
                                                <span class="me-2"><i class="fas fa-calendar-alt"></i> <?php echo $anime['release_year']; ?></span>
                                                <span class="me-2"><i class="fas fa-list"></i> <?php echo $anime['category_name']; ?></span>
                                                <span><i class="fas fa-film"></i> <?php echo $anime['episode_count']; ?> tập</span>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($type == 'views'): ?>
                                                <span class="badge bg-success fs-6">
                                                    <i class="fas fa-eye me-1"></i> <?php echo number_format($anime['views']); ?>
                                                </span>
                                            <?php elseif ($type == 'rating'): ?>
                                                <span class="badge bg-warning text-dark fs-6">
                                                    <i class="fas fa-star me-1"></i> <?php echo number_format($anime['rating'], 1); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark fs-6">
                                                    <i class="fas fa-calendar-day me-1"></i> <?php echo date('d/m/Y', strtotime($anime['release_date'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                                        <p class="lead">Không có dữ liệu để hiển thị.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar bảng xếp hạng phụ -->
    <div class="col-lg-4">
        <!-- Top anime mới -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h3 class="h5 mb-0">
                    <i class="fas fa-calendar-alt me-2"></i> Anime mới nhất
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($newest_anime as $index => $anime): ?>
                        <li class="list-group-item">
                            <div class="d-flex align-items-center">
                                <span class="rank-number small me-2"><?php echo $index + 1; ?></span>
                                <a href="anime-detail.php?id=<?php echo $anime['id']; ?>" class="d-flex align-items-center flex-grow-1">
                                    <img src="<?php echo get_thumbnail($anime['thumbnail'], 'small'); ?>" class="sidebar-thumbnail me-3" alt="<?php echo $anime['title']; ?>" width="50">
                                    <div>
                                        <h6 class="mb-0 text-truncate" style="max-width: 180px;"><?php echo $anime['title']; ?></h6>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y', strtotime($anime['release_date'])); ?>
                                        </small>
                                    </div>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer text-center">
                    <a href="anime.php?sort=newest" class="btn btn-sm btn-outline-success">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Top anime đánh giá cao -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h3 class="h5 mb-0">
                    <i class="fas fa-star me-2"></i> Đánh giá cao nhất
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_rated_anime as $index => $anime): ?>
                        <li class="list-group-item">
                            <div class="d-flex align-items-center">
                                <span class="rank-number small me-2"><?php echo $index + 1; ?></span>
                                <a href="anime-detail.php?id=<?php echo $anime['id']; ?>" class="d-flex align-items-center flex-grow-1">
                                    <img src="<?php echo get_thumbnail($anime['thumbnail'], 'small'); ?>" class="sidebar-thumbnail me-3" alt="<?php echo $anime['title']; ?>" width="50">
                                    <div>
                                        <h6 class="mb-0 text-truncate" style="max-width: 180px;"><?php echo $anime['title']; ?></h6>
                                        <div class="rating-stars">
                                            <?php 
                                                $rating = $anime['rating'];
                                                $full_stars = floor($rating);
                                                $half_star = $rating - $full_stars >= 0.5;
                                                
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $full_stars) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    } elseif ($i == $full_stars + 1 && $half_star) {
                                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-warning"></i>';
                                                    }
                                                }
                                            ?>
                                            <span class="ms-1 text-muted"><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer text-center">
                    <a href="anime.php?sort=rating" class="btn btn-sm btn-outline-warning">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>
