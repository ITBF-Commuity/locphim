<?php
/**
 * Lọc Phim - Trang xem phim
 */

// Kiểm tra người dùng đã đăng nhập chưa nếu là phim VIP
if (isset($movie) && isset($episode) && $movie['is_vip'] && (!isset($currentUser) || !$currentUser['is_vip'])) {
    echo '<script>window.location.href = "/vip?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '";</script>';
    exit;
}

// Lấy danh sách tập phim
$episodes = [];
if (isset($movie) && isset($db)) {
    $episodes = $db->getAll("
        SELECT * FROM episodes 
        WHERE movie_id = :movie_id 
        ORDER BY episode_number ASC
    ", ['movie_id' => $movie['id']]);
}

// Lấy danh sách bình luận
$comments = [];
if (isset($movie) && isset($db)) {
    $comments = $db->getAll("
        SELECT c.*, u.username, u.avatar 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.movie_id = :movie_id AND c.episode_id " . (isset($episode) ? "= :episode_id" : "IS NULL") . "
        ORDER BY c.created_at DESC
        LIMIT 20
    ", [
        'movie_id' => $movie['id'],
        'episode_id' => isset($episode) ? $episode['id'] : null
    ]);
}

// Lấy danh sách phim liên quan
$relatedMovies = [];
if (isset($movie) && isset($db)) {
    $relatedMovies = $db->getAll("
        SELECT DISTINCT m.* 
        FROM movies m
        JOIN movie_categories mc1 ON m.id = mc1.movie_id
        JOIN movie_categories mc2 ON mc1.category_id = mc2.category_id
        WHERE mc2.movie_id = :movie_id AND m.id != :movie_id
        LIMIT 6
    ", ['movie_id' => $movie['id']]);
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
    <title><?php echo isset($movie) ? $movie['title'] . (isset($episode) ? ' - Tập ' . $episode['episode_number'] : '') : 'Xem phim'; ?> - <?php echo SITE_NAME; ?></title>
    
    <?php if (isset($movie)): ?>
    <meta name="description" content="<?php echo htmlspecialchars(substr($movie['description'], 0, 160)); ?>">
    <meta name="keywords" content="xem phim <?php echo htmlspecialchars($movie['title']); ?>, <?php echo SITE_KEYWORDS; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($movie['title']); ?> - <?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($movie['description'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $movie['poster']; ?>">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="video.movie">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <?php endif; ?>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        /* Player Page Styles */
        .player-wrapper {
            display: flex;
            flex-wrap: wrap;
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: calc(100vh - var(--header-height));
        }
        
        .player-main {
            flex: 0 0 70%;
            max-width: 70%;
        }
        
        .player-sidebar {
            flex: 0 0 30%;
            max-width: 30%;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
            max-height: 100vh;
        }
        
        .player-container {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background-color: #000;
        }
        
        .player-container iframe,
        .player-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .player-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .player-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .player-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
        }
        
        .control-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .control-btn.active {
            color: var(--primary-color);
        }
        
        .player-title {
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        
        .player-source {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .player-source:hover {
            border-color: var(--primary-color);
        }
        
        .player-source.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .player-info {
            padding: 20px;
        }
        
        .movie-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            color: var(--text-gray);
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
            color: var(--text-light);
            margin-left: 5px;
        }
        
        .movie-description {
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .movie-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .movie-tag {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .movie-tag:hover {
            background-color: var(--primary-color);
        }
        
        .movie-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .movie-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .movie-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .movie-btn.primary {
            background-color: var(--primary-color);
        }
        
        .movie-btn.primary:hover {
            background-color: var(--primary-dark);
        }
        
        .episode-list {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .episode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .episode-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .episode-filter {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .episode-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        
        .episode-item {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .episode-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .episode-item.active {
            background-color: var(--primary-color);
        }
        
        .episode-item.vip {
            position: relative;
            overflow: hidden;
        }
        
        .episode-item.vip::before {
            content: 'VIP';
            position: absolute;
            top: 0;
            right: 0;
            background-color: gold;
            color: black;
            font-size: 10px;
            padding: 2px 5px;
        }
        
        .comments-section {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .comments-header {
            margin-bottom: 15px;
        }
        
        .comments-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .comment-form {
            margin-bottom: 20px;
        }
        
        .comment-input {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            margin-bottom: 10px;
            resize: none;
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .comment-submit {
            background-color: var(--primary-color);
            color: var(--text-light);
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .comment-submit:hover {
            background-color: var(--primary-dark);
        }
        
        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .comment-item {
            display: flex;
            gap: 10px;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
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
            margin-bottom: 5px;
        }
        
        .comment-username {
            font-weight: 600;
        }
        
        .comment-date {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .comment-text {
            line-height: 1.5;
            font-size: 14px;
        }
        
        .comment-actions {
            display: flex;
            gap: 15px;
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-gray);
        }
        
        .comment-action {
            cursor: pointer;
        }
        
        .comment-action:hover {
            color: var(--primary-color);
        }
        
        .related-section {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .related-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .related-movie {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .related-movie:hover {
            transform: translateY(-5px);
        }
        
        .related-poster {
            position: relative;
            padding-top: 150%; /* 2:3 aspect ratio */
        }
        
        .related-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .related-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0) 100%);
        }
        
        .related-name {
            font-weight: 500;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .player-wrapper {
                flex-direction: column;
            }
            
            .player-main,
            .player-sidebar {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .episode-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .episode-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .player-controls {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .player-title {
                max-width: 100%;
            }
            
            .episode-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .related-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }
        
        /* Video Player Overlay */
        .video-player {
            position: relative;
        }
        
        .player-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .video-player:hover .player-overlay {
            opacity: 1;
        }
        
        .play-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: rgba(229, 9, 20, 0.8);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .play-btn i {
            font-size: 30px;
        }
        
        .play-btn:hover {
            background-color: rgba(229, 9, 20, 1);
            transform: scale(1.1);
        }
        
        .big-play-btn {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: rgba(229, 9, 20, 0.8);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
        }
        
        .big-play-btn i {
            font-size: 40px;
        }
        
        .big-play-btn:hover {
            background-color: rgba(229, 9, 20, 1);
        }
        
        /* Custom Video Controls */
        .custom-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0) 100%);
            padding: 15px;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .video-player:hover .custom-controls {
            opacity: 1;
        }
        
        .progress-container {
            width: 100%;
            height: 5px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            position: relative;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 5px;
            width: 0%;
            position: relative;
            transition: width 0.1s linear;
        }
        
        .progress-handle {
            width: 14px;
            height: 14px;
            background-color: var(--primary-color);
            border-radius: 50%;
            position: absolute;
            right: -7px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }
        
        .progress-bar:hover .progress-handle {
            display: block;
        }
        
        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .controls-left,
        .controls-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .control-time {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .volume-container {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .volume-slider {
            width: 60px;
            -webkit-appearance: none;
            background-color: rgba(255, 255, 255, 0.2);
            height: 4px;
            border-radius: 4px;
        }
        
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: var(--primary-color);
            cursor: pointer;
        }
        
        /* Quality & Speed Dropdowns */
        .quality-dropdown,
        .speed-dropdown {
            position: relative;
        }
        
        .dropdown-options {
            position: absolute;
            bottom: 100%;
            right: 0;
            background-color: rgba(0, 0, 0, 0.9);
            border-radius: 4px;
            padding: 10px 0;
            width: 120px;
            display: none;
            margin-bottom: 10px;
        }
        
        .dropdown-options.active {
            display: block;
        }
        
        .dropdown-item {
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-item.active {
            color: var(--primary-color);
        }
        
        /* Ad Container */
        .ad-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 20;
        }
        
        .ad-content {
            width: 80%;
            max-width: 600px;
            text-align: center;
        }
        
        .ad-skip {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .ad-skip.active {
            background-color: var(--primary-color);
        }
        
        .ad-countdown {
            margin-right: 5px;
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
    
    <main class="player-wrapper">
        <?php if (isset($movie) && isset($episode)): ?>
            <!-- Player Main Content -->
            <div class="player-main">
                <!-- Video Player -->
                <div class="player-container">
                    <?php if ($movie['is_vip'] && (!isset($currentUser) || !$currentUser['is_vip'])): ?>
                        <!-- VIP Required Message -->
                        <div class="vip-required" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px;">
                            <i class="fas fa-crown" style="font-size: 50px; color: gold; margin-bottom: 20px;"></i>
                            <h2>Tính năng VIP</h2>
                            <p style="margin: 15px 0; max-width: 600px;">Nội dung này chỉ dành cho thành viên VIP. Hãy nâng cấp tài khoản để xem nội dung chất lượng cao, không quảng cáo.</p>
                            <a href="/vip" class="btn btn-primary" style="margin-top: 20px;">Nâng cấp VIP</a>
                        </div>
                    <?php elseif (!empty($episode['video_url'])): ?>
                        <!-- Video Player -->
                        <div class="video-player">
                            <video id="main-video" poster="<?php echo !empty($episode['thumbnail']) ? $episode['thumbnail'] : $movie['poster']; ?>" preload="metadata">
                                <?php
                                // Xác định độ phân giải tối đa cho user
                                $maxResolution = isset($currentUser) && $currentUser['is_vip'] ? VIP_MAX_RESOLUTION : FREE_MAX_RESOLUTION;
                                
                                // Video sources
                                $videoSources = [
                                    ['url' => $episode['video_url'], 'quality' => 'Tự động'],
                                ];
                                
                                // Thêm các nguồn video theo độ phân giải
                                $resolutions = ['360p', '480p', '720p', '1080p', '1440p', '2160p'];
                                foreach ($resolutions as $resolution) {
                                    $field = 'video_' . strtolower($resolution);
                                    if (!empty($episode[$field])) {
                                        // Kiểm tra quyền xem độ phân giải
                                        $canWatch = true;
                                        if (in_array($resolution, ['720p', '1080p', '1440p', '2160p'])) {
                                            $canWatch = isset($currentUser) && $currentUser['is_vip'];
                                        }
                                        
                                        if ($canWatch) {
                                            $videoSources[] = [
                                                'url' => $episode[$field],
                                                'quality' => $resolution
                                            ];
                                        }
                                    }
                                }
                                
                                // Hiển thị nguồn video
                                foreach ($videoSources as $source) {
                                    echo '<source src="' . $source['url'] . '" type="video/mp4" data-quality="' . $source['quality'] . '">';
                                }
                                ?>
                                Trình duyệt của bạn không hỗ trợ thẻ video.
                            </video>
                            
                            <!-- Big Play Button -->
                            <div class="big-play-btn" id="big-play-btn">
                                <i class="fas fa-play"></i>
                            </div>
                            
                            <!-- Custom Controls -->
                            <div class="custom-controls">
                                <div class="progress-container" id="progress-container">
                                    <div class="progress-bar" id="progress-bar">
                                        <div class="progress-handle"></div>
                                    </div>
                                </div>
                                
                                <div class="controls-row">
                                    <div class="controls-left">
                                        <button class="control-btn" id="play-btn">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        
                                        <button class="control-btn" id="pause-btn" style="display: none;">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        
                                        <div class="volume-container">
                                            <button class="control-btn" id="volume-btn">
                                                <i class="fas fa-volume-up"></i>
                                            </button>
                                            <input type="range" class="volume-slider" id="volume-slider" min="0" max="1" step="0.1" value="1">
                                        </div>
                                        
                                        <div class="control-time">
                                            <span id="current-time">00:00</span> / <span id="duration">00:00</span>
                                        </div>
                                    </div>
                                    
                                    <div class="controls-right">
                                        <div class="quality-dropdown">
                                            <button class="control-btn" id="quality-btn">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            
                                            <div class="dropdown-options" id="quality-options">
                                                <?php foreach ($videoSources as $index => $source): ?>
                                                <div class="dropdown-item <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                                                    <?php echo $source['quality']; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="speed-dropdown">
                                            <button class="control-btn" id="speed-btn">
                                                <i class="fas fa-tachometer-alt"></i>
                                            </button>
                                            
                                            <div class="dropdown-options" id="speed-options">
                                                <div class="dropdown-item" data-speed="0.5">0.5x</div>
                                                <div class="dropdown-item" data-speed="0.75">0.75x</div>
                                                <div class="dropdown-item active" data-speed="1">1x</div>
                                                <div class="dropdown-item" data-speed="1.25">1.25x</div>
                                                <div class="dropdown-item" data-speed="1.5">1.5x</div>
                                                <div class="dropdown-item" data-speed="2">2x</div>
                                            </div>
                                        </div>
                                        
                                        <button class="control-btn" id="fullscreen-btn">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!isset($currentUser) || !$currentUser['is_vip']): ?>
                            <!-- Ad Container (for non-VIP users) -->
                            <div class="ad-container" id="ad-container" style="display: none;">
                                <div class="ad-content">
                                    <img src="/assets/images/ads/ad-example.jpg" alt="Advertisement" style="max-width: 100%;">
                                </div>
                                <div class="ad-skip" id="ad-skip">
                                    <span class="ad-countdown" id="ad-countdown">5</span> Bỏ qua quảng cáo
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Placeholder for missing video -->
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #000; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 50px; color: #e50914; margin-bottom: 20px;"></i>
                            <h3>Không tìm thấy video</h3>
                            <p>Video đang được cập nhật. Vui lòng quay lại sau.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Player Controls Below Video -->
                <div class="player-controls">
                    <div class="player-left">
                        <div class="player-title">
                            <?php echo $movie['title']; ?> - Tập <?php echo $episode['episode_number']; ?>
                        </div>
                    </div>
                    
                    <div class="player-right">
                        <button class="movie-btn" id="favorite-btn" data-id="<?php echo $movie['id']; ?>">
                            <i class="<?php echo (isset($currentUser) && isset($isFavorite) && $isFavorite) ? 'fas' : 'far'; ?> fa-heart"></i> Yêu thích
                        </button>
                        
                        <button class="movie-btn" id="share-btn">
                            <i class="fas fa-share-alt"></i> Chia sẻ
                        </button>
                        
                        <button class="movie-btn" id="report-btn">
                            <i class="fas fa-flag"></i> Báo lỗi
                        </button>
                    </div>
                </div>
                
                <!-- Movie Info -->
                <div class="player-info">
                    <h1 class="movie-title"><?php echo $movie['title']; ?></h1>
                    
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
                        
                        <?php if ($movie['is_vip']): ?>
                        <div class="movie-meta-item">
                            <i class="fas fa-crown" style="color: gold;"></i>
                            <span style="color: gold;">VIP</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="movie-description">
                        <?php echo nl2br($movie['description']); ?>
                    </div>
                    
                    <div class="movie-tags">
                        <?php
                        // Lấy thể loại của phim
                        if (isset($db)) {
                            $categories = $db->getAll("
                                SELECT c.* FROM categories c
                                JOIN movie_categories mc ON c.id = mc.category_id
                                WHERE mc.movie_id = :movie_id
                            ", ['movie_id' => $movie['id']]);
                            
                            foreach ($categories as $category) {
                                echo '<a href="/the-loai/' . $category['slug'] . '" class="movie-tag">' . $category['name'] . '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Related Movies -->
                <div class="related-section">
                    <h3 class="related-title">Phim liên quan</h3>
                    
                    <div class="related-grid">
                        <?php foreach ($relatedMovies as $relatedMovie): ?>
                        <a href="/phim/<?php echo $relatedMovie['slug']; ?>" class="related-movie">
                            <div class="related-poster">
                                <img src="<?php echo !empty($relatedMovie['poster']) ? $relatedMovie['poster'] : '/assets/images/default-poster.svg'; ?>" alt="<?php echo $relatedMovie['title']; ?>">
                            </div>
                            <div class="related-info">
                                <div class="related-name"><?php echo $relatedMovie['title']; ?></div>
                            </div>
                            <?php if ($relatedMovie['is_vip']): ?>
                            <div class="vip-badge">VIP</div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Player Sidebar -->
            <div class="player-sidebar">
                <!-- Episode List -->
                <div class="episode-list">
                    <div class="episode-header">
                        <h3 class="episode-title">Danh sách tập</h3>
                        
                        <select class="episode-filter">
                            <option value="all">Tất cả</option>
                            <option value="vip">VIP</option>
                            <option value="free">Miễn phí</option>
                        </select>
                    </div>
                    
                    <div class="episode-grid">
                        <?php foreach ($episodes as $ep): ?>
                        <a href="/phim/<?php echo $movie['slug']; ?>/<?php echo $ep['episode_number']; ?>" 
                           class="episode-item <?php echo $ep['id'] == $episode['id'] ? 'active' : ''; ?> <?php echo $ep['is_vip'] ? 'vip' : ''; ?>">
                            <?php echo $ep['episode_number']; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div class="comments-section">
                    <div class="comments-header">
                        <h3 class="comments-title">Bình luận (<?php echo count($comments); ?>)</h3>
                    </div>
                    
                    <?php if (isset($currentUser)): ?>
                    <div class="comment-form">
                        <form id="comment-form" data-movie-id="<?php echo $movie['id']; ?>" data-episode-id="<?php echo $episode['id']; ?>">
                            <textarea class="comment-input" id="comment-input" rows="3" placeholder="Viết bình luận..."></textarea>
                            <button type="submit" class="comment-submit">Gửi</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div style="margin-bottom: 20px; background-color: rgba(255,255,255,0.1); padding: 15px; border-radius: 4px; text-align: center;">
                        <p>Vui lòng <a href="/dang-nhap" style="color: var(--primary-color);">đăng nhập</a> để bình luận</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comments-list">
                        <?php if (empty($comments)): ?>
                        <div style="text-align: center; padding: 20px; color: var(--text-gray);">
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
                                        <div class="comment-username"><?php echo $comment['username']; ?></div>
                                        <div class="comment-date"><?php echo formatDate($comment['created_at']); ?></div>
                                    </div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="width: 100%; text-align: center; padding: 50px;">
                <h2>Không tìm thấy phim</h2>
                <p>Phim bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p>
                <a href="/" class="btn btn-primary" style="margin-top: 20px;">Quay lại trang chủ</a>
            </div>
        <?php endif; ?>
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
    
    <!-- Hidden form fields for video tracking -->
    <?php if (isset($movie) && isset($episode)): ?>
    <input type="hidden" id="movie-id" value="<?php echo $movie['id']; ?>">
    <input type="hidden" id="episode-id" value="<?php echo $episode['id']; ?>">
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup Video Player
            const video = document.getElementById('main-video');
            if (!video) return;
            
            const bigPlayBtn = document.getElementById('big-play-btn');
            const playBtn = document.getElementById('play-btn');
            const pauseBtn = document.getElementById('pause-btn');
            const volumeBtn = document.getElementById('volume-btn');
            const volumeSlider = document.getElementById('volume-slider');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const currentTimeDisplay = document.getElementById('current-time');
            const durationDisplay = document.getElementById('duration');
            const fullscreenBtn = document.getElementById('fullscreen-btn');
            const qualityBtn = document.getElementById('quality-btn');
            const qualityOptions = document.getElementById('quality-options');
            const speedBtn = document.getElementById('speed-btn');
            const speedOptions = document.getElementById('speed-options');
            
            // Không hiển thị quảng cáo cho VIP
            <?php if (!isset($currentUser) || !$currentUser['is_vip']): ?>
            const adContainer = document.getElementById('ad-container');
            const adSkip = document.getElementById('ad-skip');
            const adCountdown = document.getElementById('ad-countdown');
            let adTimer = 5;
            let adInterval;
            
            // Hiển thị quảng cáo khi video bắt đầu
            video.addEventListener('play', function() {
                if (!localStorage.getItem('ad_shown_<?php echo $episode['id']; ?>')) {
                    showAd();
                }
            }, { once: true });
            
            function showAd() {
                video.pause();
                adContainer.style.display = 'flex';
                
                adInterval = setInterval(function() {
                    adTimer--;
                    adCountdown.textContent = adTimer;
                    
                    if (adTimer <= 0) {
                        clearInterval(adInterval);
                        adSkip.classList.add('active');
                    }
                }, 1000);
                
                adSkip.addEventListener('click', function() {
                    if (adTimer <= 0) {
                        hideAd();
                    }
                });
                
                // Lưu trạng thái đã hiển thị quảng cáo
                localStorage.setItem('ad_shown_<?php echo $episode['id']; ?>', 'true');
            }
            
            function hideAd() {
                clearInterval(adInterval);
                adContainer.style.display = 'none';
                video.play();
            }
            <?php endif; ?>
            
            // Play/Pause
            bigPlayBtn.addEventListener('click', function() {
                video.play();
                bigPlayBtn.style.display = 'none';
                playBtn.style.display = 'none';
                pauseBtn.style.display = 'block';
            });
            
            playBtn.addEventListener('click', function() {
                video.play();
                playBtn.style.display = 'none';
                pauseBtn.style.display = 'block';
            });
            
            pauseBtn.addEventListener('click', function() {
                video.pause();
                pauseBtn.style.display = 'none';
                playBtn.style.display = 'block';
            });
            
            video.addEventListener('play', function() {
                bigPlayBtn.style.display = 'none';
                playBtn.style.display = 'none';
                pauseBtn.style.display = 'block';
            });
            
            video.addEventListener('pause', function() {
                pauseBtn.style.display = 'none';
                playBtn.style.display = 'block';
                if (!video.currentTime) {
                    bigPlayBtn.style.display = 'flex';
                }
            });
            
            video.addEventListener('ended', function() {
                pauseBtn.style.display = 'none';
                playBtn.style.display = 'block';
                bigPlayBtn.style.display = 'flex';
                
                // Đánh dấu video đã xem xong
                markAsCompleted();
            });
            
            // Volume
            volumeBtn.addEventListener('click', function() {
                if (video.muted) {
                    video.muted = false;
                    volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                    volumeSlider.value = video.volume;
                } else {
                    video.muted = true;
                    volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                }
            });
            
            volumeSlider.addEventListener('input', function() {
                video.volume = volumeSlider.value;
                video.muted = false;
                
                if (video.volume === 0) {
                    volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                } else if (video.volume < 0.5) {
                    volumeBtn.innerHTML = '<i class="fas fa-volume-down"></i>';
                } else {
                    volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                }
            });
            
            // Progress
            video.addEventListener('loadedmetadata', function() {
                durationDisplay.textContent = formatTime(video.duration);
            });
            
            video.addEventListener('timeupdate', function() {
                const currentTime = video.currentTime;
                const duration = video.duration;
                
                currentTimeDisplay.textContent = formatTime(currentTime);
                
                if (duration) {
                    const progressPercent = (currentTime / duration) * 100;
                    progressBar.style.width = progressPercent + '%';
                    
                    // Cập nhật thời gian xem mỗi 10 giây
                    if (Math.floor(currentTime) % 10 === 0) {
                        updateWatchTime(currentTime, duration);
                    }
                }
            });
            
            progressContainer.addEventListener('click', function(e) {
                const rect = progressContainer.getBoundingClientRect();
                const pos = (e.clientX - rect.left) / rect.width;
                video.currentTime = pos * video.duration;
            });
            
            // Fullscreen
            fullscreenBtn.addEventListener('click', function() {
                if (video.requestFullscreen) {
                    video.requestFullscreen();
                } else if (video.webkitRequestFullscreen) {
                    video.webkitRequestFullscreen();
                } else if (video.msRequestFullscreen) {
                    video.msRequestFullscreen();
                }
            });
            
            // Quality
            qualityBtn.addEventListener('click', function() {
                qualityOptions.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (!qualityBtn.contains(e.target) && !qualityOptions.contains(e.target)) {
                    qualityOptions.classList.remove('active');
                }
                
                if (!speedBtn.contains(e.target) && !speedOptions.contains(e.target)) {
                    speedOptions.classList.remove('active');
                }
            });
            
            qualityOptions.querySelectorAll('.dropdown-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    const sources = video.querySelectorAll('source');
                    
                    // Lưu trạng thái hiện tại
                    const currentTime = video.currentTime;
                    const isPaused = video.paused;
                    
                    // Thay đổi nguồn video
                    video.src = sources[index].src;
                    
                    // Khôi phục trạng thái
                    video.addEventListener('loadedmetadata', function() {
                        video.currentTime = currentTime;
                        if (!isPaused) {
                            video.play();
                        }
                    }, { once: true });
                    
                    // Cập nhật UI
                    qualityOptions.querySelectorAll('.dropdown-item').forEach(function(item) {
                        item.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    qualityOptions.classList.remove('active');
                });
            });
            
            // Speed
            speedBtn.addEventListener('click', function() {
                speedOptions.classList.toggle('active');
            });
            
            speedOptions.querySelectorAll('.dropdown-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    const speed = parseFloat(this.getAttribute('data-speed'));
                    video.playbackRate = speed;
                    
                    speedOptions.querySelectorAll('.dropdown-item').forEach(function(item) {
                        item.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    speedOptions.classList.remove('active');
                    speedBtn.innerHTML = `<i class="fas fa-tachometer-alt"></i>`;
                });
            });
            
            // Keyboard Controls
            document.addEventListener('keydown', function(e) {
                if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
                    return;
                }
                
                switch(e.key.toLowerCase()) {
                    case ' ':
                    case 'k':
                        if (video.paused) {
                            video.play();
                        } else {
                            video.pause();
                        }
                        e.preventDefault();
                        break;
                    case 'arrowright':
                        video.currentTime += 10;
                        e.preventDefault();
                        break;
                    case 'arrowleft':
                        video.currentTime -= 10;
                        e.preventDefault();
                        break;
                    case 'arrowup':
                        video.volume = Math.min(1, video.volume + 0.1);
                        volumeSlider.value = video.volume;
                        e.preventDefault();
                        break;
                    case 'arrowdown':
                        video.volume = Math.max(0, video.volume - 0.1);
                        volumeSlider.value = video.volume;
                        e.preventDefault();
                        break;
                    case 'f':
                        fullscreenBtn.click();
                        e.preventDefault();
                        break;
                    case 'm':
                        if (video.muted) {
                            video.muted = false;
                            volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                        } else {
                            video.muted = true;
                            volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                        }
                        e.preventDefault();
                        break;
                }
            });
            
            // Lấy thời gian đã xem từ local storage
            const savedTime = localStorage.getItem('watchTime_<?php echo isset($movie) && isset($episode) ? $movie['id'] . '_' . $episode['id'] : ''; ?>');
            if (savedTime) {
                // Nếu thời gian đã xem gần cuối video, không nhảy về vị trí đó
                if (savedTime < video.duration * 0.9) {
                    video.currentTime = parseFloat(savedTime);
                }
            }
            
            // Cập nhật thời gian xem
            function updateWatchTime(currentTime, duration) {
                // Lưu vào local storage
                localStorage.setItem('watchTime_<?php echo isset($movie) && isset($episode) ? $movie['id'] . '_' . $episode['id'] : ''; ?>', currentTime);
                
                // Gửi lên server
                fetch('/api/update-watch-time.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        movie_id: document.getElementById('movie-id').value,
                        episode_id: document.getElementById('episode-id').value,
                        watch_time: currentTime,
                        duration: duration
                    })
                }).catch(error => console.error('Error updating watch time:', error));
            }
            
            // Đánh dấu đã xem xong
            function markAsCompleted() {
                fetch('/api/mark-as-completed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        movie_id: document.getElementById('movie-id').value,
                        episode_id: document.getElementById('episode-id').value
                    })
                }).catch(error => console.error('Error marking as completed:', error));
            }
        });
        
        // Format time from seconds to MM:SS
        function formatTime(seconds) {
            seconds = Math.floor(seconds);
            const minutes = Math.floor(seconds / 60);
            seconds = seconds % 60;
            
            return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Yêu thích phim
        const favoriteBtn = document.getElementById('favorite-btn');
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
                        if (data.isFavorite) {
                            this.querySelector('i').classList.remove('far');
                            this.querySelector('i').classList.add('fas');
                            showNotification('Đã thêm vào danh sách yêu thích');
                        } else {
                            this.querySelector('i').classList.remove('fas');
                            this.querySelector('i').classList.add('far');
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
        
        // Chia sẻ phim
        const shareBtn = document.getElementById('share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function() {
                if (navigator.share) {
                    navigator.share({
                        title: '<?php echo isset($movie) ? $movie['title'] : 'Lọc Phim'; ?>',
                        text: '<?php echo isset($movie) ? 'Xem phim ' . $movie['title'] . ' tại Lọc Phim' : 'Lọc Phim - Xem phim HD miễn phí'; ?>',
                        url: window.location.href
                    })
                    .catch(error => console.error('Error sharing:', error));
                } else {
                    // Fallback for browsers that don't support Web Share API
                    const tempInput = document.createElement('input');
                    document.body.appendChild(tempInput);
                    tempInput.value = window.location.href;
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    
                    showNotification('Đã sao chép đường dẫn vào clipboard');
                }
            });
        }
        
        // Báo lỗi phim
        const reportBtn = document.getElementById('report-btn');
        if (reportBtn) {
            reportBtn.addEventListener('click', function() {
                const reason = prompt('Vui lòng cho biết lỗi bạn gặp phải:');
                if (reason) {
                    fetch('/api/report-issue.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            movie_id: document.getElementById('movie-id').value,
                            episode_id: document.getElementById('episode-id').value,
                            reason: reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Cảm ơn bạn đã báo cáo. Chúng tôi sẽ xem xét và khắc phục sớm.');
                        } else {
                            showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error reporting issue:', error);
                        showNotification('Có lỗi xảy ra. Vui lòng thử lại sau', 'error');
                    });
                }
            });
        }
        
        // Bình luận
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
                        episode_id: this.getAttribute('data-episode-id'),
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
        
        // Reply to comment
        const replyBtns = document.querySelectorAll('.reply-btn');
        replyBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-id');
                const commentInput = document.getElementById('comment-input');
                
                if (!commentInput) return;
                
                commentInput.focus();
                commentInput.value = '@reply:' + commentId + ' ';
            });
        });
        
        // Delete comment
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
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Add notification styles
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                border-radius: 4px;
                color: white;
                z-index: 9999;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s;
            }
            
            .notification.show {
                opacity: 1;
                transform: translateY(0);
            }
            
            .notification.success {
                background-color: var(--success-color);
            }
            
            .notification.error {
                background-color: var(--danger-color);
            }
            
            .notification.warning {
                background-color: var(--warning-color);
            }
            
            .notification.info {
                background-color: var(--info);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>