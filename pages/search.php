<?php
/**
 * Lọc Phim - Trang tìm kiếm
 */

// Lấy từ khóa tìm kiếm
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Các tham số lọc
$category = isset($_GET['category']) ? $_GET['category'] : '';
$country = isset($_GET['country']) ? $_GET['country'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

// Trang hiện tại
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;

// Kết quả tìm kiếm
$searchResults = [];
$totalResults = 0;
$lastPage = 1;

// Lấy danh sách thể loại và quốc gia
$categories = [];
$countries = [];

// Nếu có từ khóa tìm kiếm, thực hiện tìm kiếm
if (!empty($query)) {
    // Xây dựng URL để gọi API search
    $apiUrl = '/api/search.php?q=' . urlencode($query) . '&page=' . $page . '&limit=' . $limit;
    
    // Thêm các tham số lọc
    if (!empty($category)) {
        $apiUrl .= '&category=' . urlencode($category);
    }
    
    if (!empty($country)) {
        $apiUrl .= '&country=' . urlencode($country);
    }
    
    if (!empty($year)) {
        $apiUrl .= '&year=' . urlencode($year);
    }
    
    if (!empty($type)) {
        $apiUrl .= '&type=' . urlencode($type);
    }
    
    if (!empty($sort)) {
        $apiUrl .= '&sort=' . urlencode($sort);
    }
    
    // Gọi API tìm kiếm
    $searchData = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $apiUrl), true);
    
    if ($searchData && isset($searchData['success']) && $searchData['success']) {
        $searchResults = $searchData['results'];
        $totalResults = $searchData['pagination']['total'];
        $lastPage = $searchData['pagination']['last_page'];
    }
}

// Lấy danh sách thể loại
if (isset($db)) {
    $categories = $db->getAll("SELECT * FROM categories ORDER BY name");
    $countries = $db->getAll("SELECT * FROM countries ORDER BY name");
}

// Lấy danh sách các năm để lọc
$currentYear = date('Y');
$years = range($currentYear, $currentYear - 20);

// Tiêu đề trang
$pageTitle = empty($query) ? 'Tìm kiếm phim' : 'Kết quả tìm kiếm: ' . $query;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <meta name="description" content="Tìm kiếm phim trên <?php echo SITE_NAME; ?>. Tra cứu hàng ngàn bộ phim và anime từ thư viện phong phú.">
    <meta name="keywords" content="tìm kiếm phim, tìm phim online, tìm anime, phim lẻ, phim bộ, <?php echo SITE_KEYWORDS; ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?>">
    <meta property="og:description" content="Tìm kiếm phim trên <?php echo SITE_NAME; ?>. Tra cứu hàng ngàn bộ phim và anime từ thư viện phong phú.">
    <meta property="og:url" content="<?php echo SITE_URL; ?>/tim-kiem">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        /* Trang tìm kiếm */
        .search-page {
            padding: 30px 0;
        }
        
        .advanced-search {
            background-color: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .search-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .search-form-large {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-input-large {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: var(--border-radius) 0 0 var(--border-radius);
            font-size: 16px;
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .search-input-large:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-btn-large {
            background-color: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: 0 25px;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-btn-large:hover {
            background-color: var(--primary-dark);
        }
        
        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .results-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .results-count {
            color: var(--text-gray);
        }
        
        .search-sort {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sort-label {
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .sort-select {
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .sort-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-results-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .search-movie-card {
            background-color: var(--bg-card);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .search-movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .search-movie-poster {
            position: relative;
            padding-top: 150%; /* Tỷ lệ 2:3 */
            overflow: hidden;
        }
        
        .search-movie-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s;
        }
        
        .search-movie-card:hover .search-movie-poster img {
            transform: scale(1.05);
        }
        
        .search-movie-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 12px;
            z-index: 1;
        }
        
        .search-movie-badge.vip {
            background-color: var(--primary-color);
        }
        
        .search-movie-badge.anime {
            background-color: var(--info);
        }
        
        .search-movie-info {
            padding: 15px;
        }
        
        .search-movie-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .search-movie-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .search-movie-year {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-movie-rating {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-movie-rating i {
            color: #f5c518; /* IMDb yellow */
        }
        
        .search-pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination-item {
            margin: 0 5px;
        }
        
        .pagination-link {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--bg-gray);
            color: var(--text-dark);
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .pagination-link:hover {
            background-color: var(--primary-light);
            color: var(--text-light);
        }
        
        .pagination-link.active {
            background-color: var(--primary-color);
            color: var(--text-light);
        }
        
        .pagination-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            color: var(--text-gray);
        }
        
        .no-results {
            text-align: center;
            padding: 50px 0;
        }
        
        .no-results-icon {
            font-size: 50px;
            color: var(--text-gray);
            margin-bottom: 20px;
        }
        
        .no-results-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .no-results-message {
            color: var(--text-gray);
            margin-bottom: 20px;
        }
        
        .no-results-tips {
            max-width: 500px;
            margin: 0 auto;
            margin-bottom: 20px;
            line-height: 1.6;
            text-align: left;
        }
        
        .no-results-tips ul {
            list-style: disc;
            padding-left: 20px;
            margin-bottom: 20px;
        }
        
        /* Media queries */
        @media (max-width: 1200px) {
            .search-results-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .search-results-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .search-form-large {
                flex-direction: column;
            }
            
            .search-input-large {
                border-right: 1px solid var(--border-color);
                border-radius: var(--border-radius) var(--border-radius) 0 0;
                margin-bottom: 0;
            }
            
            .search-btn-large {
                border-radius: 0 0 var(--border-radius) var(--border-radius);
                padding: 12px;
            }
            
            .search-results-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .results-count {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .search-filters {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-group {
                width: 100%;
                min-width: 100%;
            }
            
            .search-results-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="/" class="logo"><?php echo SITE_NAME; ?></a>
                
                <form class="search-form" action="/tim-kiem" method="GET">
                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm phim..." value="<?php echo htmlspecialchars($query); ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="user-actions">
                    <?php if (isset($currentUser)): ?>
                        <div class="dropdown">
                            <a href="#" class="dropdown-toggle">
                                <img src="<?php echo !empty($currentUser['avatar']) ? $currentUser['avatar'] : '/assets/images/default-avatar.jpg'; ?>" alt="<?php echo $currentUser['username']; ?>" class="user-avatar">
                            </a>
                            <div class="dropdown-menu">
                                <a href="/ca-nhan" class="dropdown-item">Trang cá nhân</a>
                                <a href="/danh-sach-yeu-thich" class="dropdown-item">Phim yêu thích</a>
                                <a href="/lich-su-xem" class="dropdown-item">Lịch sử xem</a>
                                <?php if ($currentUser['is_vip']): ?>
                                    <a href="/vip" class="dropdown-item">Tài khoản VIP</a>
                                <?php else: ?>
                                    <a href="/vip" class="dropdown-item">Nâng cấp VIP</a>
                                <?php endif; ?>
                                <a href="/dang-xuat" class="dropdown-item">Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/dang-nhap" class="btn btn-outline">Đăng nhập</a>
                        <a href="/dang-ky" class="btn btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <nav class="main-nav">
            <div class="container">
                <ul class="nav-menu">
                    <li class="nav-item"><a href="/" class="nav-link">Trang chủ</a></li>
                    <li class="nav-item">
                        <a href="/phim-le" class="nav-link">Phim lẻ</a>
                    </li>
                    <li class="nav-item">
                        <a href="/phim-bo" class="nav-link">Phim bộ</a>
                    </li>
                    <li class="nav-item">
                        <a href="/anime" class="nav-link">Anime</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">Thể loại</a>
                        <div class="dropdown-menu">
                            <a href="/the-loai/hanh-dong" class="dropdown-item">Hành Động</a>
                            <a href="/the-loai/tinh-cam" class="dropdown-item">Tình Cảm</a>
                            <a href="/the-loai/hai-huoc" class="dropdown-item">Hài Hước</a>
                            <a href="/the-loai/co-trang" class="dropdown-item">Cổ Trang</a>
                            <a href="/the-loai/tam-ly" class="dropdown-item">Tâm Lý</a>
                            <a href="/the-loai/hinh-su" class="dropdown-item">Hình Sự</a>
                            <a href="/the-loai/vien-tuong" class="dropdown-item">Viễn Tưởng</a>
                            <a href="/the-loai/phieu-luu" class="dropdown-item">Phiêu Lưu</a>
                            <a href="/the-loai/khoa-hoc" class="dropdown-item">Khoa Học</a>
                            <a href="/the-loai/kinh-di" class="dropdown-item">Kinh Dị</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">Quốc gia</a>
                        <div class="dropdown-menu">
                            <a href="/quoc-gia/viet-nam" class="dropdown-item">Việt Nam</a>
                            <a href="/quoc-gia/trung-quoc" class="dropdown-item">Trung Quốc</a>
                            <a href="/quoc-gia/han-quoc" class="dropdown-item">Hàn Quốc</a>
                            <a href="/quoc-gia/nhat-ban" class="dropdown-item">Nhật Bản</a>
                            <a href="/quoc-gia/thai-lan" class="dropdown-item">Thái Lan</a>
                            <a href="/quoc-gia/my" class="dropdown-item">Mỹ</a>
                        </div>
                    </li>
                    <li class="nav-item"><a href="/top-phim" class="nav-link">Xếp hạng</a></li>
                    <?php if (isset($currentUser) && !$currentUser['is_vip']): ?>
                        <li class="nav-item"><a href="/vip" class="nav-link" style="color: gold;"><i class="fas fa-crown"></i> VIP</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    
    <main class="main">
        <div class="container">
            <div class="search-page">
                <!-- Form tìm kiếm nâng cao -->
                <div class="advanced-search">
                    <h1 class="search-title">Tìm kiếm phim</h1>
                    
                    <form action="/tim-kiem" method="GET">
                        <div class="search-form-large">
                            <input type="text" name="q" class="search-input-large" placeholder="Nhập tên phim, diễn viên hoặc từ khóa..." value="<?php echo htmlspecialchars($query); ?>">
                            <button type="submit" class="search-btn-large">Tìm kiếm</button>
                        </div>
                        
                        <div class="search-filters">
                            <div class="filter-group">
                                <label for="category" class="filter-label">Thể loại</label>
                                <select name="category" id="category" class="filter-select">
                                    <option value="">Tất cả thể loại</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['slug']; ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="country" class="filter-label">Quốc gia</label>
                                <select name="country" id="country" class="filter-select">
                                    <option value="">Tất cả quốc gia</option>
                                    <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo $c['slug']; ?>" <?php echo $country === $c['slug'] ? 'selected' : ''; ?>>
                                        <?php echo $c['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="year" class="filter-label">Năm</label>
                                <select name="year" id="year" class="filter-select">
                                    <option value="">Tất cả năm</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="type" class="filter-label">Loại phim</label>
                                <select name="type" id="type" class="filter-select">
                                    <option value="">Tất cả</option>
                                    <option value="movie" <?php echo $type === 'movie' ? 'selected' : ''; ?>>Phim lẻ</option>
                                    <option value="series" <?php echo $type === 'series' ? 'selected' : ''; ?>>Phim bộ</option>
                                    <option value="anime" <?php echo $type === 'anime' ? 'selected' : ''; ?>>Anime</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Kết quả tìm kiếm -->
                <?php if (!empty($query)): ?>
                    <?php if (count($searchResults) > 0): ?>
                        <div class="search-results">
                            <div class="search-results-header">
                                <div>
                                    <h2 class="results-title">Kết quả tìm kiếm: "<?php echo htmlspecialchars($query); ?>"</h2>
                                    <div class="results-count">Tìm thấy <?php echo $totalResults; ?> kết quả</div>
                                </div>
                                
                                <form action="/tim-kiem" method="GET" class="search-sort">
                                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                                    <?php if (!empty($category)): ?>
                                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($country)): ?>
                                    <input type="hidden" name="country" value="<?php echo htmlspecialchars($country); ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($year)): ?>
                                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($type)): ?>
                                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                                    <?php endif; ?>
                                    
                                    <span class="sort-label">Sắp xếp:</span>
                                    <select name="sort" id="sort" class="sort-select" onchange="this.form.submit()">
                                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Liên quan nhất</option>
                                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Mới nhất</option>
                                        <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>Lượt xem</option>
                                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Đánh giá</option>
                                    </select>
                                </form>
                            </div>
                            
                            <div class="search-results-grid">
                                <?php foreach ($searchResults as $movie): ?>
                                <a href="<?php echo $movie['url']; ?>" class="search-movie-card">
                                    <div class="search-movie-poster">
                                        <img src="<?php echo !empty($movie['poster']) ? $movie['poster'] : '/assets/images/default-poster.svg'; ?>" alt="<?php echo $movie['title']; ?>">
                                        
                                        <?php if ($movie['is_vip']): ?>
                                        <div class="search-movie-badge vip">VIP</div>
                                        <?php elseif ($movie['is_anime']): ?>
                                        <div class="search-movie-badge anime">Anime</div>
                                        <?php elseif ($movie['is_series']): ?>
                                        <div class="search-movie-badge">Phim bộ</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="search-movie-info">
                                        <h3 class="search-movie-title"><?php echo $movie['title']; ?></h3>
                                        
                                        <div class="search-movie-meta">
                                            <div class="search-movie-year">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span><?php echo $movie['release_year']; ?></span>
                                            </div>
                                            
                                            <div class="search-movie-rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($movie['rating'], 1); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Phân trang -->
                            <?php if ($lastPage > 1): ?>
                            <div class="search-pagination">
                                <?php if ($page > 1): ?>
                                <div class="pagination-item">
                                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($country) ? '&country=' . urlencode($country) : ''; ?><?php echo !empty($year) ? '&year=' . urlencode($year) : ''; ?><?php echo !empty($type) ? '&type=' . urlencode($type) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" class="pagination-link">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php
                                // Hiển thị các trang
                                $startPage = max(1, $page - 2);
                                $endPage = min($lastPage, $page + 2);
                                
                                // Hiển thị nút đầu trang nếu cần
                                if ($startPage > 1) {
                                    echo '<div class="pagination-item">';
                                    echo '<a href="?q=' . urlencode($query) . '&page=1' . (!empty($category) ? '&category=' . urlencode($category) : '') . (!empty($country) ? '&country=' . urlencode($country) : '') . (!empty($year) ? '&year=' . urlencode($year) : '') . (!empty($type) ? '&type=' . urlencode($type) : '') . (!empty($sort) ? '&sort=' . urlencode($sort) : '') . '" class="pagination-link">1</a>';
                                    echo '</div>';
                                    
                                    if ($startPage > 2) {
                                        echo '<div class="pagination-dots">...</div>';
                                    }
                                }
                                
                                // Hiển thị các trang giữa
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    echo '<div class="pagination-item">';
                                    echo '<a href="?q=' . urlencode($query) . '&page=' . $i . (!empty($category) ? '&category=' . urlencode($category) : '') . (!empty($country) ? '&country=' . urlencode($country) : '') . (!empty($year) ? '&year=' . urlencode($year) : '') . (!empty($type) ? '&type=' . urlencode($type) : '') . (!empty($sort) ? '&sort=' . urlencode($sort) : '') . '" class="pagination-link' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
                                    echo '</div>';
                                }
                                
                                // Hiển thị nút cuối trang nếu cần
                                if ($endPage < $lastPage) {
                                    if ($endPage < $lastPage - 1) {
                                        echo '<div class="pagination-dots">...</div>';
                                    }
                                    
                                    echo '<div class="pagination-item">';
                                    echo '<a href="?q=' . urlencode($query) . '&page=' . $lastPage . (!empty($category) ? '&category=' . urlencode($category) : '') . (!empty($country) ? '&country=' . urlencode($country) : '') . (!empty($year) ? '&year=' . urlencode($year) : '') . (!empty($type) ? '&type=' . urlencode($type) : '') . (!empty($sort) ? '&sort=' . urlencode($sort) : '') . '" class="pagination-link">' . $lastPage . '</a>';
                                    echo '</div>';
                                }
                                ?>
                                
                                <?php if ($page < $lastPage): ?>
                                <div class="pagination-item">
                                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($country) ? '&country=' . urlencode($country) : ''; ?><?php echo !empty($year) ? '&year=' . urlencode($year) : ''; ?><?php echo !empty($type) ? '&type=' . urlencode($type) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" class="pagination-link">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h2 class="no-results-title">Không tìm thấy kết quả</h2>
                            <p class="no-results-message">Không tìm thấy kết quả nào cho từ khóa "<?php echo htmlspecialchars($query); ?>"</p>
                            
                            <div class="no-results-tips">
                                <p>Gợi ý:</p>
                                <ul>
                                    <li>Kiểm tra lỗi chính tả của từ khoá đã nhập</li>
                                    <li>Thử sử dụng từ khóa khác</li>
                                    <li>Thử sử dụng từ khóa ngắn gọn hơn</li>
                                    <li>Thử tìm kiếm với tên phim bằng tiếng Anh</li>
                                </ul>
                            </div>
                            
                            <a href="/" class="btn btn-primary">Quay lại trang chủ</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title"><?php echo SITE_NAME; ?></h3>
                    <p>Trang web xem phim và anime chất lượng cao, cập nhật nhanh nhất.</p>
                    <div class="footer-social">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Thể loại</h3>
                    <div class="footer-links">
                        <a href="/the-loai/hanh-dong/1" class="footer-link">Hành Động</a>
                        <a href="/the-loai/tinh-cam/2" class="footer-link">Tình Cảm</a>
                        <a href="/the-loai/hai-huoc/3" class="footer-link">Hài Hước</a>
                        <a href="/the-loai/co-trang/4" class="footer-link">Cổ Trang</a>
                        <a href="/the-loai/kinh-di/10" class="footer-link">Kinh Dị</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Quốc gia</h3>
                    <div class="footer-links">
                        <a href="/quoc-gia/viet-nam/1" class="footer-link">Việt Nam</a>
                        <a href="/quoc-gia/trung-quoc/2" class="footer-link">Trung Quốc</a>
                        <a href="/quoc-gia/han-quoc/3" class="footer-link">Hàn Quốc</a>
                        <a href="/quoc-gia/thai-lan/4" class="footer-link">Thái Lan</a>
                        <a href="/quoc-gia/my/5" class="footer-link">Mỹ</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Liên kết</h3>
                    <div class="footer-links">
                        <a href="/vip" class="footer-link">Nâng cấp VIP</a>
                        <a href="/lien-he" class="footer-link">Liên hệ</a>
                        <a href="/dieu-khoan-su-dung" class="footer-link">Điều khoản sử dụng</a>
                        <a href="/chinh-sach-bao-mat" class="footer-link">Chính sách bảo mật</a>
                        <a href="/dmca" class="footer-link">DMCA</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.</p>
                <p>Lọc Phim không lưu trữ bất kỳ nội dung nào trên máy chủ của mình. Tất cả nội dung được lấy từ các nguồn bên thứ ba.</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
</body>
</html>