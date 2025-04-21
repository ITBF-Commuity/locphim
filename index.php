<?php
// Trang chủ website Lọc Phim
// Khởi tạo hệ thống
require_once 'init.php';

// Lấy dữ liệu từ database
$featured_movies = get_latest_content('movie', 5); // 5 phim nổi bật
$latest_movies = get_latest_content('movie', 8); // 8 phim mới nhất
$latest_anime = get_latest_content('anime', 8); // 8 anime mới nhất
$top_viewed_movies = get_trending_content(5); // 5 phim/anime xem nhiều nhất
$top_rated_content = get_top_rated_content(5); // 5 phim/anime được đánh giá cao nhất

// Lấy danh sách thể loại
$categories = [];
$categories_query = "SELECT * FROM categories WHERE status = 1 ORDER BY name";
$categories = db_fetch_all($categories_query);

// Đặt tiêu đề và mô tả trang
$page_title = "Lọc Phim - Xem phim và anime trực tuyến chất lượng cao";
$page_description = "Trang web xem phim và anime trực tuyến hàng đầu Việt Nam với nhiều thể loại phim và anime đa dạng, chất lượng cao.";
$page_keywords = "phim trực tuyến, anime, xem phim, phim HD, anime HD, phim lẻ, phim bộ, phim chiếu rạp";
?>

<!DOCTYPE html>
<html lang="vi" data-theme="<?php echo get_current_theme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (get_current_theme() === 'dark'): ?>
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <?php endif; ?>
    
    <?php
    // Kiểm tra giao diện theo mùa
    $seasonal_theme = false;
    $seasonal_css = '';
    
    // Lấy cài đặt giao diện theo mùa từ config
    $config_file = 'config.json';
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
        $seasonal_theme_enabled = isset($config['site']['seasonal_theme_enabled']) ? $config['site']['seasonal_theme_enabled'] : false;
        $active_seasonal_theme = isset($config['site']['active_seasonal_theme']) ? $config['site']['active_seasonal_theme'] : 'none';
        
        if ($seasonal_theme_enabled && $active_seasonal_theme != 'none') {
            $seasonal_theme = true;
            $seasonal_css = $active_seasonal_theme;
        }
    }
    ?>
    
    <?php if ($seasonal_theme && $seasonal_css): ?>
    <link rel="stylesheet" href="assets/css/seasonal/<?php echo $seasonal_css; ?>.css">
    <?php endif; ?>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js" defer></script>
    <script src="assets/js/main.js" defer></script>
    <?php if ($seasonal_theme): ?>
    <script src="assets/js/seasonal_themes.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/img/logo.svg" alt="Lọc Phim" class="logo-img">
                        <span class="logo-text">Lọc Phim</span>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <ul class="nav-menu">
                        <li class="nav-item active">
                            <a href="index.php" class="nav-link">
                                <i class="fas fa-home"></i> Trang chủ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="anime.php" class="nav-link">
                                <i class="fas fa-tv"></i> Anime
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="movies.php" class="nav-link">
                                <i class="fas fa-film"></i> Phim
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle">
                                <i class="fas fa-list"></i> Thể loại
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="category.php?slug=<?php echo $category['slug']; ?>">
                                        <?php echo $category['name']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="search.php" class="nav-link">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <div class="theme-switch">
                        <button id="theme-toggle" class="theme-toggle-btn">
                            <?php if (get_current_theme() === 'dark'): ?>
                            <i class="fas fa-sun"></i>
                            <?php else: ?>
                            <i class="fas fa-moon"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <?php if (get_current_user_info()): ?>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php $current_user = get_current_user_info(); ?>
                            <?php if (!empty($current_user['avatar'])): ?>
                            <img src="<?php echo $current_user['avatar']; ?>" alt="<?php echo $current_user['username']; ?>">
                            <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo substr($current_user['username'], 0, 1); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (is_vip($current_user)): ?>
                            <span class="vip-badge"><i class="fas fa-crown"></i></span>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="user_profile.php"><i class="fas fa-user"></i> Hồ sơ</a></li>
                                <li><a href="favorites.php"><i class="fas fa-heart"></i> Yêu thích</a></li>
                                <li><a href="history.php"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                                <?php if (!is_vip($current_user)): ?>
                                <li><a href="vip_upgrade.php"><i class="fas fa-crown"></i> Nâng cấp VIP</a></li>
                                <?php endif; ?>
                                <?php if (is_admin($current_user)): ?>
                                <li><a href="admin/index.php"><i class="fas fa-cogs"></i> Quản trị</a></li>
                                <?php endif; ?>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline-primary btn-sm">Đăng nhập</a>
                        <a href="register.php" class="btn btn-primary btn-sm">Đăng ký</a>
                    </div>
                    <?php endif; ?>
                    
                    <button class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <div class="mobile-menu">
        <div class="close-menu">
            <button class="close-menu-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-menu-inner">
            <ul class="mobile-nav">
                <li class="active"><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="anime.php"><i class="fas fa-tv"></i> Anime</a></li>
                <li><a href="movies.php"><i class="fas fa-film"></i> Phim</a></li>
                <li><a href="search.php"><i class="fas fa-search"></i> Tìm kiếm</a></li>
                <?php if (get_current_user_info()): ?>
                <?php $current_user = get_current_user_info(); ?>
                <li><a href="user_profile.php"><i class="fas fa-user"></i> Hồ sơ</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Yêu thích</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                <?php if (!is_vip($current_user)): ?>
                <li><a href="vip_upgrade.php"><i class="fas fa-crown"></i> Nâng cấp VIP</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="mobile-categories">
                <h3>Thể loại</h3>
                <ul>
                    <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="category.php?slug=<?php echo $category['slug']; ?>">
                            <?php echo $category['name']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <main class="main-content">
        <!-- Hero Slider -->
        <section class="hero-slider">
            <div class="container">
                <div class="swiper hero-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($featured_movies as $movie): ?>
                        <div class="swiper-slide">
                            <div class="hero-slide" style="background-image: url('<?php echo $movie['poster'] ?: 'assets/img/default-poster.jpg'; ?>');">
                                <div class="slide-content">
                                    <h2 class="slide-title"><?php echo $movie['title']; ?></h2>
                                    <p class="slide-info">
                                        <span class="year"><?php echo $movie['release_year']; ?></span>
                                        <span class="divider">•</span>
                                        <span class="quality"><?php echo $movie['quality']; ?></span>
                                        <span class="divider">•</span>
                                        <span class="type"><?php echo $movie['type'] === 'movie' ? 'Phim' : 'Anime'; ?></span>
                                    </p>
                                    <p class="slide-desc"><?php echo truncate_text($movie['description'], 150); ?></p>
                                    <div class="slide-actions">
                                        <a href="detail.php?slug=<?php echo $movie['slug']; ?>" class="btn btn-primary">
                                            <i class="fas fa-play"></i> Xem ngay
                                        </a>
                                        <a href="#" class="btn btn-outline-light add-to-favorites" data-id="<?php echo $movie['id']; ?>">
                                            <i class="fas fa-heart"></i> Yêu thích
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </section>
        
        <!-- Phim mới cập nhật -->
        <section class="movie-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Phim mới cập nhật</h2>
                    <a href="movies.php" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($latest_movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img src="<?php echo $movie['thumbnail'] ?: 'assets/img/default-thumbnail.jpg'; ?>" alt="<?php echo $movie['title']; ?>">
                            <div class="movie-overlay">
                                <div class="movie-actions">
                                    <a href="detail.php?slug=<?php echo $movie['slug']; ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                    <a href="#" class="btn-favorite" data-id="<?php echo $movie['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                    </a>
                                </div>
                            </div>
                            <?php if ($movie['quality']): ?>
                            <span class="quality-badge"><?php echo $movie['quality']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title">
                                <a href="detail.php?slug=<?php echo $movie['slug']; ?>"><?php echo $movie['title']; ?></a>
                            </h3>
                            <div class="movie-meta">
                                <span class="year"><?php echo $movie['release_year']; ?></span>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($movie['views']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Anime mới cập nhật -->
        <section class="movie-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Anime mới cập nhật</h2>
                    <a href="anime.php" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($latest_anime as $anime): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img src="<?php echo $anime['thumbnail'] ?: 'assets/img/default-thumbnail.jpg'; ?>" alt="<?php echo $anime['title']; ?>">
                            <div class="movie-overlay">
                                <div class="movie-actions">
                                    <a href="detail.php?slug=<?php echo $anime['slug']; ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                    <a href="#" class="btn-favorite" data-id="<?php echo $anime['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                    </a>
                                </div>
                            </div>
                            <span class="episode-badge"><?php echo $anime['episodes_count']; ?> tập</span>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title">
                                <a href="detail.php?slug=<?php echo $anime['slug']; ?>"><?php echo $anime['title']; ?></a>
                            </h3>
                            <div class="movie-meta">
                                <span class="year"><?php echo $anime['release_year']; ?></span>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($anime['views']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Bảng xếp hạng - Top phim xem nhiều nhất -->
        <section class="movie-section bg-alt">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-chart-line"></i> Bảng xếp hạng - Top phim xem nhiều</h2>
                    <a href="rankings.php?type=views" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="ranking-list card-style">
                    <?php foreach ($top_viewed_movies as $index => $movie): ?>
                    <div class="ranking-item">
                        <div class="rank-number"><?php echo $index + 1; ?></div>
                        <div class="rank-poster">
                            <img src="<?php echo $movie['thumbnail'] ?: 'assets/img/default-thumbnail.jpg'; ?>" alt="<?php echo $movie['title']; ?>">
                            <?php if ($movie['quality']): ?>
                            <span class="quality-badge quality-sm"><?php echo $movie['quality']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="rank-info">
                            <h3 class="rank-title">
                                <a href="detail.php?slug=<?php echo $movie['slug']; ?>"><?php echo $movie['title']; ?></a>
                            </h3>
                            <div class="rank-meta">
                                <span class="year"><?php echo $movie['release_year']; ?></span>
                                <span class="divider">•</span>
                                <span class="type"><?php echo $movie['type'] === 'movie' ? 'Phim' : 'Anime'; ?></span>
                                <span class="divider">•</span>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($movie['views']); ?></span>
                            </div>
                            <p class="rank-desc"><?php echo truncate_text($movie['description'], 100); ?></p>
                            <div class="rank-rating">
                                <span class="rating-stars">
                                    <?php
                                    $rating = isset($movie['rating']) ? $movie['rating'] : 0;
                                    $full_stars = floor($rating);
                                    $half_star = $rating - $full_stars >= 0.5;
                                    
                                    // Hiển thị sao đầy đủ
                                    for ($i = 0; $i < $full_stars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }
                                    
                                    // Hiển thị nửa sao nếu có
                                    if ($half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                        $empty_stars = 4 - $full_stars;
                                    } else {
                                        $empty_stars = 5 - $full_stars;
                                    }
                                    
                                    // Hiển thị sao trống
                                    for ($i = 0; $i < $empty_stars; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </span>
                                <span class="rating-value"><?php echo number_format($rating, 1); ?>/5</span>
                            </div>
                        </div>
                        <div class="rank-actions">
                            <a href="detail.php?slug=<?php echo $movie['slug']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i> Xem
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary add-to-favorites" data-id="<?php echo $movie['id']; ?>">
                                <i class="fas fa-heart"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        
        <!-- Đăng ký VIP -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Nâng cấp tài khoản VIP</h2>
                    <p class="cta-desc">Trải nghiệm xem phim tốt nhất với gói VIP của Lọc Phim. Không quảng cáo, chất lượng cao và nhiều tính năng độc quyền khác.</p>
                    <a href="vip_upgrade.php" class="btn btn-lg btn-primary">
                        <i class="fas fa-crown"></i> Nâng cấp ngay
                    </a>
                </div>
            </div>
        </section>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="assets/img/logo.svg" alt="Lọc Phim" class="footer-logo-img">
                    <h2 class="footer-logo-text">Lọc Phim</h2>
                    <p class="footer-desc">Website xem phim và anime trực tuyến hàng đầu Việt Nam</p>
                </div>
                
                <div class="footer-links">
                    <h3 class="footer-title">Liên kết hữu ích</h3>
                    <ul class="footer-menu">
                        <li><a href="index.php">Trang chủ</a></li>
                        <li><a href="anime.php">Anime</a></li>
                        <li><a href="movies.php">Phim</a></li>
                        <li><a href="vip_upgrade.php">Nâng cấp VIP</a></li>
                        <li><a href="contact.php">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="footer-categories">
                    <h3 class="footer-title">Thể loại</h3>
                    <ul class="footer-menu">
                        <?php 
                        $count = 0;
                        foreach ($categories as $category): 
                            if ($count >= 5) break;
                        ?>
                        <li><a href="category.php?slug=<?php echo $category['slug']; ?>"><?php echo $category['name']; ?></a></li>
                        <?php 
                            $count++;
                        endforeach; 
                        ?>
                    </ul>
                </div>
                
                <div class="footer-newsletter">
                    <h3 class="footer-title">Đăng ký nhận thông báo</h3>
                    <p>Nhận thông báo khi có phim mới hoặc các ưu đãi đặc biệt</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Email của bạn" required>
                        <button type="submit" class="btn btn-primary">Đăng ký</button>
                    </form>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">&copy; 2025 Lọc Phim. Tất cả các quyền được bảo lưu.</p>
                <div class="footer-policy-links">
                    <a href="terms.php">Điều khoản sử dụng</a>
                    <a href="privacy.php">Chính sách bảo mật</a>
                    <a href="faq.php">Câu hỏi thường gặp</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Khởi tạo Swiper Slider
        document.addEventListener('DOMContentLoaded', function() {
            const heroSwiper = new Swiper('.hero-swiper', {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
            
            // Toggle Dark/Light Theme
            const themeToggle = document.getElementById('theme-toggle');
            themeToggle?.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Cập nhật biểu tượng
                const icon = themeToggle.querySelector('i');
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
                
                // Lưu theme preference cho user đã đăng nhập
                if (<?php echo get_current_user_info() ? 'true' : 'false'; ?>) {
                    fetch('ajax/save_theme_preference.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'theme=' + newTheme
                    });
                }
            });
            
            // Mobile Menu
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.querySelector('.mobile-menu');
            const closeMenu = document.querySelector('.close-menu-btn');
            
            mobileMenuToggle?.addEventListener('click', function() {
                mobileMenu.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            closeMenu?.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Add to Favorites
            const favoriteButtons = document.querySelectorAll('.btn-favorite, .add-to-favorites');
            favoriteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const movieId = this.getAttribute('data-id');
                    
                    // Kiểm tra đăng nhập
                    if (!<?php echo get_current_user_info() ? 'true' : 'false'; ?>) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        return;
                    }
                    
                    fetch('ajax/toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'movie_id=' + movieId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Đổi trạng thái nút
                            this.classList.toggle('active');
                            
                            // Hiển thị thông báo
                            alert(data.message);
                        } else {
                            alert('Có lỗi xảy ra: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi thêm vào yêu thích');
                    });
                });
            });
        });
    </script>
</body>
</html>