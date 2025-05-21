<?php
/**
 * Lọc Phim - Trang chi tiết phim
 */

// Kiểm tra xem có dữ liệu phim chưa
if (!isset($movie) || empty($movie)) {
    // Chuyển hướng đến trang 404
    header('Location: /404');
    exit;
}

// Lấy danh sách thể loại phim
$categories = [];
if (isset($db) && isset($movie)) {
    $categories = $db->getAll("
        SELECT c.* FROM categories c
        JOIN movie_categories mc ON c.id = mc.category_id
        WHERE mc.movie_id = :movie_id
    ", ['movie_id' => $movie['id']]);
}

// Lấy danh sách quốc gia phim
$countries = [];
if (isset($db) && isset($movie)) {
    $countries = $db->getAll("
        SELECT c.* FROM countries c
        JOIN movie_countries mc ON c.id = mc.country_id
        WHERE mc.movie_id = :movie_id
    ", ['movie_id' => $movie['id']]);
}

// Lấy danh sách tập phim
$episodes = [];
if (isset($db) && isset($movie)) {
    $episodes = $db->getAll("
        SELECT * FROM episodes
        WHERE movie_id = :movie_id
        ORDER BY episode_number ASC
    ", ['movie_id' => $movie['id']]);
}

// Lấy danh sách phim liên quan
$relatedMovies = [];
if (isset($db) && isset($movie)) {
    $relatedMovies = $db->getAll("
        SELECT DISTINCT m.* 
        FROM movies m
        JOIN movie_categories mc1 ON m.id = mc1.movie_id
        JOIN movie_categories mc2 ON mc1.category_id = mc2.category_id
        WHERE mc2.movie_id = :movie_id AND m.id != :movie_id
        LIMIT 8
    ", ['movie_id' => $movie['id']]);
}

// Lấy danh sách bình luận
$comments = [];
if (isset($db) && isset($movie)) {
    $comments = $db->getAll("
        SELECT c.*, u.username, u.avatar 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.movie_id = :movie_id AND c.episode_id IS NULL
        ORDER BY c.created_at DESC
        LIMIT 10
    ", ['movie_id' => $movie['id']]);
}

// Kiểm tra phim đã được yêu thích chưa
$isFavorite = false;
if (isset($currentUser) && isset($db) && isset($movie)) {
    $favorite = $db->get("
        SELECT id FROM favorites
        WHERE user_id = :user_id AND movie_id = :movie_id
    ", [
        'user_id' => $currentUser['id'],
        'movie_id' => $movie['id']
    ]);
    
    $isFavorite = !empty($favorite);
}

// Định dạng thời lượng
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
    } else {
        return $minutes . ' phút';
    }
}

// Hiện thị xếp hạng
function displayRating($rating) {
    $fullStars = floor($rating / 2);
    $halfStar = $rating % 2 >= 1 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    
    $html = '<div class="rating">';
    
    // Hiển thị sao đầy
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    
    // Hiển thị nửa sao
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Hiển thị sao rỗng
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    $html .= ' <span>' . number_format($rating, 1) . '/10</span>';
    $html .= '</div>';
    
    return $html;
}

// Định dạng ngày
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie['title']; ?> (<?php echo $movie['release_year']; ?>) - <?php echo SITE_NAME; ?></title>
    
    <meta name="description" content="<?php echo htmlspecialchars(substr($movie['description'], 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($movie['title']); ?>, <?php 
        $keywordsArray = array_map(function($cat) { return $cat['name']; }, $categories);
        echo htmlspecialchars(implode(', ', $keywordsArray)); 
    ?>, <?php echo SITE_KEYWORDS; ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($movie['title']); ?> (<?php echo $movie['release_year']; ?>) - <?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($movie['description'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $movie['poster']; ?>">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="video.movie">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        /* Movie Detail Styles */
        .movie-detail-page {
            padding: 30px 0;
        }
        
        .movie-banner {
            position: relative;
            width: 100%;
            height: 400px;
            margin-bottom: 30px;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
        }
        
        .banner-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.6) 50%, rgba(0, 0, 0, 0.4) 100%);
            display: flex;
            align-items: center;
        }
        
        .movie-info-container {
            display: flex;
            margin-bottom: 30px;
        }
        
        .movie-poster-container {
            flex: 0 0 300px;
            margin-right: 30px;
        }
        
        .movie-poster {
            width: 100%;
            height: 450px;
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .movie-badges {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .movie-badge {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .movie-badge.vip {
            background-color: var(--primary-color);
        }
        
        .movie-badge.anime {
            background-color: var(--info);
        }
        
        .movie-badge.series {
            background-color: var(--success);
        }
        
        .movie-badge.theater {
            background-color: #644dff;
        }
        
        .movie-poster-actions {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .movie-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .movie-btn.primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .movie-btn.primary:hover {
            background-color: var(--primary-dark);
        }
        
        .movie-btn.secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-dark);
        }
        
        .movie-btn.secondary:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .movie-btn.vip {
            background-color: gold;
            color: black;
        }
        
        .movie-btn.vip:hover {
            background-color: #ffd700;
        }
        
        .favorite-btn.active {
            color: var(--primary-color);
        }
        
        .movie-detail-content {
            flex: 1;
        }
        
        .movie-title-container {
            margin-bottom: 15px;
        }
        
        .movie-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .movie-original-title {
            font-size: 16px;
            color: var(--text-gray);
            margin-bottom: 10px;
        }
        
        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .movie-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #f5c518; /* IMDb yellow */
        }
        
        .rating span {
            color: var(--text-dark);
            margin-left: 5px;
        }
        
        .movie-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .category-tag {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-dark);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .category-tag:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .movie-description {
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .movie-director, .movie-cast {
            margin-bottom: 15px;
        }
        
        .movie-info-label {
            font-weight: 600;
            margin-right: 5px;
        }
        
        .episodes-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            padding-left: 15px;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .episodes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .episodes-filter {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 8px 15px;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .episodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .episode-item {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 12px 10px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .episode-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .episode-item.vip {
            position: relative;
        }
        
        .episode-item.vip::before {
            content: 'VIP';
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--primary-color);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 0 var(--border-radius) 0 var(--border-radius);
        }
        
        .episode-item.watched::after {
            content: '✓';
            position: absolute;
            top: 0;
            left: 0;
            background-color: var(--success);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: var(--border-radius) 0 var(--border-radius) 0;
        }
        
        .episode-number {
            font-weight: 600;
        }
        
        .episodes-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-dark);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .page-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .related-section {
            margin-bottom: 30px;
        }
        
        .related-movies {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .related-movie-card {
            background-color: var(--bg-card);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .related-movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .related-movie-poster {
            position: relative;
            padding-top: 150%; /* Tỷ lệ 2:3 */
            overflow: hidden;
        }
        
        .related-movie-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s;
        }
        
        .related-movie-card:hover .related-movie-poster img {
            transform: scale(1.05);
        }
        
        .related-movie-info {
            padding: 15px;
        }
        
        .related-movie-title {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 5px;
        }
        
        .related-movie-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .comments-section {
            margin-bottom: 30px;
        }
        
        .comment-form {
            margin-bottom: 20px;
        }
        
        .comment-input {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            resize: none;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .comment-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .comment-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .comment-submit:hover {
            background-color: var(--primary-dark);
        }
        
        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .comment-item {
            display: flex;
            gap: 15px;
        }
        
        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .comment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .comment-author {
            font-weight: 600;
        }
        
        .comment-date {
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .comment-text {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .comment-actions {
            display: flex;
            gap: 15px;
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .comment-action {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .comment-action:hover {
            color: var(--primary-color);
        }
        
        .no-comments {
            text-align: center;
            padding: 30px;
            color: var(--text-gray);
            border: 1px dashed var(--border-color);
            border-radius: var(--border-radius);
        }
        
        /* Media Queries */
        @media (max-width: 992px) {
            .movie-info-container {
                flex-direction: column;
            }
            
            .movie-poster-container {
                margin-right: 0;
                margin-bottom: 20px;
                flex: 0 0 auto;
                width: 100%;
                max-width: 300px;
                margin: 0 auto 20px;
            }
            
            .related-movies {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .movie-banner {
                height: 300px;
            }
            
            .episodes-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
            
            .related-movies {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .movie-banner {
                height: 200px;
            }
            
            .episodes-grid {
                grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            }
            
            .related-movies {
                grid-template-columns: repeat(1, 1fr);
            }
            
            .movie-meta {
                flex-direction: column;
                gap: 8px;
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
                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm phim...">
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
        <!-- Banner phim -->
        <div class="movie-banner">
            <img src="<?php echo !empty($movie['banner']) ? $movie['banner'] : '/assets/images/default-banner.svg'; ?>" alt="<?php echo $movie['title']; ?>" class="banner-img">
            <div class="banner-overlay"></div>
        </div>
        
        <div class="container">
            <div class="movie-detail-page">
                <div class="movie-info-container">
                    <!-- Thông tin bìa phim -->
                    <div class="movie-poster-container">
                        <div class="movie-poster">
                            <img src="<?php echo !empty($movie['poster']) ? $movie['poster'] : '/assets/images/default-poster.svg'; ?>" alt="<?php echo $movie['title']; ?>">
                            
                            <div class="movie-badges">
                                <?php if ($movie['is_vip']): ?>
                                <div class="movie-badge vip">VIP</div>
                                <?php endif; ?>
                                
                                <?php if ($movie['is_anime']): ?>
                                <div class="movie-badge anime">Anime</div>
                                <?php elseif ($movie['is_series']): ?>
                                <div class="movie-badge series">Phim bộ</div>
                                <?php else: ?>
                                <div class="movie-badge">Phim lẻ</div>
                                <?php endif; ?>
                                
                                <?php if ($movie['is_theater']): ?>
                                <div class="movie-badge theater">Chiếu rạp</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="movie-poster-actions">
                            <?php if (!empty($episodes)): ?>
                                <a href="/phim/<?php echo $movie['slug']; ?>/1" class="movie-btn primary">
                                    <i class="fas fa-play"></i> Xem ngay
                                </a>
                            <?php else: ?>
                                <button class="movie-btn primary" disabled>
                                    <i class="fas fa-play"></i> Sắp có
                                </button>
                            <?php endif; ?>
                            
                            <?php if (isset($currentUser)): ?>
                                <button class="movie-btn secondary favorite-btn <?php echo $isFavorite ? 'active' : ''; ?>" data-id="<?php echo $movie['id']; ?>">
                                    <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i> Yêu thích
                                </button>
                            <?php else: ?>
                                <a href="/dang-nhap?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="movie-btn secondary">
                                    <i class="far fa-heart"></i> Yêu thích
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($movie['is_vip'] && (!isset($currentUser) || !$currentUser['is_vip'])): ?>
                                <a href="/vip" class="movie-btn vip">
                                    <i class="fas fa-crown"></i> Nâng cấp VIP
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Thông tin chi tiết phim -->
                    <div class="movie-detail-content">
                        <div class="movie-title-container">
                            <h1 class="movie-title"><?php echo $movie['title']; ?></h1>
                            <?php if (!empty($movie['original_title'])): ?>
                                <div class="movie-original-title"><?php echo $movie['original_title']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="movie-meta">
                            <div class="movie-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo $movie['release_year']; ?></span>
                            </div>
                            
                            <?php if (!empty($movie['duration'])): ?>
                            <div class="movie-meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo formatDuration($movie['duration']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="movie-meta-item">
                                <?php echo displayRating($movie['rating']); ?>
                            </div>
                            
                            <div class="movie-meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo number_format($movie['views']); ?> lượt xem</span>
                            </div>
                            
                            <?php if (!empty($movie['language'])): ?>
                            <div class="movie-meta-item">
                                <i class="fas fa-language"></i>
                                <span><?php echo $movie['language']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie['quality'])): ?>
                            <div class="movie-meta-item">
                                <i class="fas fa-tv"></i>
                                <span><?php echo $movie['quality']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="movie-categories">
                            <?php foreach ($categories as $category): ?>
                                <a href="/the-loai/<?php echo $category['slug']; ?>" class="category-tag"><?php echo $category['name']; ?></a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="movie-description">
                            <?php echo nl2br($movie['description']); ?>
                        </div>
                        
                        <?php if (!empty($movie['director'])): ?>
                        <div class="movie-director">
                            <span class="movie-info-label">Đạo diễn:</span>
                            <span><?php echo $movie['director']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['cast'])): ?>
                        <div class="movie-cast">
                            <span class="movie-info-label">Diễn viên:</span>
                            <span><?php echo $movie['cast']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($countries)): ?>
                        <div class="movie-countries">
                            <span class="movie-info-label">Quốc gia:</span>
                            <?php foreach ($countries as $index => $country): ?>
                                <a href="/quoc-gia/<?php echo $country['slug']; ?>"><?php echo $country['name']; ?></a><?php echo $index < count($countries) - 1 ? ', ' : ''; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($episodes)): ?>
                <!-- Danh sách tập phim -->
                <div class="episodes-section">
                    <h2 class="section-title">Tập phim</h2>
                    
                    <div class="episodes-header">
                        <div class="episodes-count">
                            Tổng số: <strong><?php echo count($episodes); ?></strong> tập
                        </div>
                        
                        <div class="episodes-filter">
                            <div class="filter-tab active" data-filter="all">Tất cả</div>
                            <div class="filter-tab" data-filter="vip">VIP</div>
                            <div class="filter-tab" data-filter="free">Miễn phí</div>
                        </div>
                    </div>
                    
                    <div class="episodes-grid">
                        <?php 
                        // Xử lý hiển thị danh sách tập phim
                        $episodesPerPage = 30;
                        $totalPages = ceil(count($episodes) / $episodesPerPage);
                        $currentEpisodePage = 1;
                        $displayedEpisodes = array_slice($episodes, 0, $episodesPerPage);
                        
                        // Kiểm tra tập đã xem
                        $watchedEpisodes = [];
                        if (isset($currentUser) && isset($db)) {
                            $watched = $db->getAll("
                                SELECT episode_id FROM watch_progress
                                WHERE user_id = :user_id AND movie_id = :movie_id AND is_completed = TRUE
                            ", [
                                'user_id' => $currentUser['id'],
                                'movie_id' => $movie['id']
                            ]);
                            
                            foreach ($watched as $w) {
                                $watchedEpisodes[$w['episode_id']] = true;
                            }
                        }
                        
                        foreach ($displayedEpisodes as $episode): 
                            $isWatched = isset($watchedEpisodes[$episode['id']]);
                            $episodeClasses = [];
                            if ($episode['is_vip']) $episodeClasses[] = 'vip';
                            if ($isWatched) $episodeClasses[] = 'watched';
                        ?>
                            <a href="/phim/<?php echo $movie['slug']; ?>/<?php echo $episode['episode_number']; ?>" class="episode-item <?php echo implode(' ', $episodeClasses); ?>">
                                <div class="episode-number"><?php echo $episode['episode_number']; ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="episodes-pagination">
                        <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                            <div class="page-btn <?php echo $i === $currentEpisodePage ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></div>
                        <?php endfor; ?>
                        
                        <?php if ($totalPages > 5): ?>
                            <div class="page-btn">...</div>
                            <div class="page-btn" data-page="<?php echo $totalPages; ?>"><?php echo $totalPages; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Phim liên quan -->
                <?php if (!empty($relatedMovies)): ?>
                <div class="related-section">
                    <h2 class="section-title">Phim liên quan</h2>
                    
                    <div class="related-movies">
                        <?php foreach ($relatedMovies as $relatedMovie): ?>
                        <a href="/phim/<?php echo $relatedMovie['slug']; ?>" class="related-movie-card">
                            <div class="related-movie-poster">
                                <img src="<?php echo !empty($relatedMovie['poster']) ? $relatedMovie['poster'] : '/assets/images/default-poster.svg'; ?>" alt="<?php echo $relatedMovie['title']; ?>">
                                
                                <?php if ($relatedMovie['is_vip']): ?>
                                <div class="movie-badge vip">VIP</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="related-movie-info">
                                <h3 class="related-movie-title"><?php echo $relatedMovie['title']; ?></h3>
                                
                                <div class="related-movie-meta">
                                    <div><?php echo $relatedMovie['release_year']; ?></div>
                                    <div><i class="fas fa-star" style="color: #f5c518;"></i> <?php echo number_format($relatedMovie['rating'], 1); ?></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Bình luận -->
                <div class="comments-section">
                    <h2 class="section-title">Bình luận</h2>
                    
                    <?php if (isset($currentUser)): ?>
                    <div class="comment-form">
                        <form id="comment-form" data-movie-id="<?php echo $movie['id']; ?>">
                            <textarea id="comment-input" class="comment-input" rows="3" placeholder="Viết bình luận của bạn..."></textarea>
                            <button type="submit" class="comment-submit">Gửi bình luận</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; margin-bottom: 20px; padding: 15px; border: 1px dashed var(--border-color); border-radius: var(--border-radius);">
                        <p>Vui lòng <a href="/dang-nhap?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: var(--primary-color); font-weight: 600;">đăng nhập</a> để bình luận</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comment-list">
                        <?php if (empty($comments)): ?>
                        <div class="no-comments">
                            <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-avatar">
                                    <img src="<?php echo !empty($comment['avatar']) ? $comment['avatar'] : '/assets/images/default-avatar.jpg'; ?>" alt="<?php echo $comment['username']; ?>">
                                </div>
                                
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <div class="comment-author"><?php echo $comment['username']; ?></div>
                                        <div class="comment-date"><?php echo formatDate($comment['created_at']); ?></div>
                                    </div>
                                    
                                    <div class="comment-text">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>
                                    
                                    <div class="comment-actions">
                                        <div class="comment-action reply-btn" data-id="<?php echo $comment['id']; ?>">
                                            <i class="fas fa-reply"></i> Trả lời
                                        </div>
                                        
                                        <?php if (isset($currentUser) && $currentUser['id'] == $comment['user_id']): ?>
                                        <div class="comment-action delete-btn" data-id="<?php echo $comment['id']; ?>">
                                            <i class="fas fa-trash"></i> Xóa
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($comments) >= 10): ?>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="/phim/<?php echo $movie['slug']; ?>/binh-luan" class="btn btn-outline">Xem tất cả bình luận</a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý yêu thích phim
            const favoriteBtn = document.querySelector('.favorite-btn');
            if (favoriteBtn) {
                favoriteBtn.addEventListener('click', function() {
                    const movieId = this.getAttribute('data-id');
                    
                    fetch('/api/toggle-favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            movie_id: movieId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.toggle('active');
                            const heartIcon = this.querySelector('i');
                            
                            if (data.isFavorite) {
                                heartIcon.classList.remove('far');
                                heartIcon.classList.add('fas');
                                showNotification('Đã thêm vào danh sách yêu thích');
                            } else {
                                heartIcon.classList.remove('fas');
                                heartIcon.classList.add('far');
                                showNotification('Đã xóa khỏi danh sách yêu thích');
                            }
                        } else {
                            if (data.error === 'auth') {
                                showNotification('Vui lòng đăng nhập để sử dụng tính năng này', 'error');
                            } else {
                                showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error toggling favorite:', error);
                        showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                    });
                });
            }
            
            // Xử lý bình luận
            const commentForm = document.getElementById('comment-form');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const commentInput = document.getElementById('comment-input');
                    const content = commentInput.value.trim();
                    
                    if (!content) {
                        showNotification('Vui lòng nhập nội dung bình luận', 'error');
                        return;
                    }
                    
                    fetch('/api/add-comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            movie_id: this.getAttribute('data-movie-id'),
                            content: content
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            commentInput.value = '';
                            showNotification('Bình luận của bạn đã được đăng');
                            
                            // Reload page to show the new comment
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error adding comment:', error);
                        showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                    });
                });
            }
            
            // Xử lý trả lời bình luận
            const replyBtns = document.querySelectorAll('.reply-btn');
            replyBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-id');
                    const commentInput = document.getElementById('comment-input');
                    
                    if (commentInput) {
                        commentInput.focus();
                        commentInput.value = '@reply:' + commentId + ' ';
                    } else {
                        // Nếu chưa đăng nhập, chuyển hướng đến trang đăng nhập
                        window.location.href = '/dang-nhap?redirect=' + encodeURIComponent(window.location.pathname);
                    }
                });
            });
            
            // Xử lý xóa bình luận
            const deleteBtns = document.querySelectorAll('.delete-btn');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                        const commentId = this.getAttribute('data-id');
                        
                        fetch('/api/delete-comment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                comment_id: commentId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Bình luận đã được xóa');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting comment:', error);
                            showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                        });
                    }
                });
            });
            
            // Xử lý phân trang danh sách tập phim
            const episodePages = document.querySelectorAll('.page-btn[data-page]');
            episodePages.forEach(page => {
                page.addEventListener('click', function() {
                    const pageNum = parseInt(this.getAttribute('data-page'));
                    
                    // Cập nhật UI
                    document.querySelectorAll('.page-btn').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Tải danh sách tập phim tương ứng
                    loadEpisodePage(pageNum);
                });
            });
            
            // Hàm tải trang danh sách tập phim
            function loadEpisodePage(page) {
                const episodesPerPage = 30;
                const start = (page - 1) * episodesPerPage;
                const end = start + episodesPerPage;
                
                // Gửi AJAX để lấy danh sách tập phim
                fetch('/api/get-episodes.php?movie_id=<?php echo $movie['id']; ?>&page=' + page)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateEpisodesGrid(data.episodes);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading episodes:', error);
                    });
            }
            
            // Hàm cập nhật danh sách tập phim
            function updateEpisodesGrid(episodes) {
                const grid = document.querySelector('.episodes-grid');
                if (!grid) return;
                
                // Xóa danh sách cũ
                grid.innerHTML = '';
                
                // Thêm danh sách mới
                episodes.forEach(episode => {
                    const episodeClasses = [];
                    if (episode.is_vip) episodeClasses.push('vip');
                    if (episode.is_watched) episodeClasses.push('watched');
                    
                    const item = document.createElement('a');
                    item.href = '/phim/<?php echo $movie['slug']; ?>/' + episode.episode_number;
                    item.className = 'episode-item ' + episodeClasses.join(' ');
                    
                    const number = document.createElement('div');
                    number.className = 'episode-number';
                    number.textContent = episode.episode_number;
                    
                    item.appendChild(number);
                    grid.appendChild(item);
                });
            }
            
            // Xử lý lọc danh sách tập phim
            const filterTabs = document.querySelectorAll('.filter-tab');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Cập nhật UI
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Lọc danh sách tập phim
                    const episodeItems = document.querySelectorAll('.episode-item');
                    episodeItems.forEach(item => {
                        if (filter === 'all') {
                            item.style.display = '';
                        } else if (filter === 'vip') {
                            item.style.display = item.classList.contains('vip') ? '' : 'none';
                        } else if (filter === 'free') {
                            item.style.display = !item.classList.contains('vip') ? '' : 'none';
                        }
                    });
                });
            });
            
            // Hiển thị thông báo
            function showNotification(message, type = 'success') {
                // Xóa thông báo cũ nếu có
                const existingNotification = document.querySelector('.notification');
                if (existingNotification) {
                    existingNotification.remove();
                }
                
                // Tạo thông báo mới
                const notification = document.createElement('div');
                notification.className = 'notification ' + type;
                notification.innerText = message;
                
                // Thêm vào DOM
                document.body.appendChild(notification);
                
                // Hiển thị thông báo
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);
                
                // Ẩn thông báo sau 3 giây
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
            
            // Thêm CSS cho thông báo
            const styleElement = document.createElement('style');
            styleElement.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: var(--border-radius);
                    color: white;
                    z-index: 9999;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                    opacity: 0;
                    transform: translateY(-20px);
                    transition: all 0.3s ease;
                }
                .notification.show {
                    opacity: 1;
                    transform: translateY(0);
                }
                .notification.success {
                    background-color: #52c41a;
                }
                .notification.error {
                    background-color: #ff4d4f;
                }
                .notification.warning {
                    background-color: #faad14;
                }
                .notification.info {
                    background-color: #1890ff;
                }
            `;
            document.head.appendChild(styleElement);
        });
    </script>
</body>
</html>