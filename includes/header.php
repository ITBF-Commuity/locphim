<?php 
/**
 * Header cho tất cả các trang
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy thông tin người dùng nếu đã đăng nhập
$current_user = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/functions.php';
    $current_user = get_user_by_id($_SESSION['user_id']);
}

// Xác định trang hiện tại
$current_page = basename($_SERVER['PHP_SELF']);

// Lấy theme từ user preferences hoặc cookie hoặc mặc định là light
require_once __DIR__ . '/../functions.php';
$theme = get_current_theme();

// Kiểm tra theme theo mùa
$seasonal_theme = false;
$seasonal_css = '';

// Lấy cài đặt giao diện theo mùa từ config
$config_file = __DIR__ . '/../config.json';
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
<!DOCTYPE html>
<html lang="vi" data-theme="<?php echo $theme; ?>"<?php echo $seasonal_theme ? ' data-seasonal-theme="' . $seasonal_css . '"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : SITE_DESCRIPTION; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? $page_keywords : SITE_KEYWORDS; ?>">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg" type="image/svg+xml">
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dark-mode.css">
    <?php if ($seasonal_theme && $seasonal_css): ?>
    <?php
        $allowed_themes = array('christmas', 'tet', 'halloween', 'trung-thu', 'quoc-khanh', '30-4');
        if (in_array($seasonal_css, $allowed_themes)):
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/seasonal/<?php echo $seasonal_css; ?>.css">
    <?php endif; endif; ?>
    <!-- JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dark-mode.js" defer></script>
    <?php if ($seasonal_theme): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/seasonal_themes.js" defer></script>
    <?php endif; ?>
    <?php if (strpos($current_page, 'watch.php') !== false): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/player.js" defer></script>
    <?php endif; ?>
    <?php if (strpos($current_page, 'login.php') !== false || strpos($current_page, 'register.php') !== false || strpos($current_page, 'forgot_password.php') !== false): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/auth.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-wrapper">
                <div class="logo">
                    <a href="<?php echo BASE_URL; ?>/">
                        <svg class="logo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 30">
                            <path d="M10 5h80c2.8 0 5 2.2 5 5v10c0 2.8-2.2 5-5 5H10c-2.8 0-5-2.2-5-5V10c0-2.8 2.2-5 5-5z" fill="var(--primary-color)"/>
                            <text x="50" y="19" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-weight="bold" font-size="12">Lọc Phim</text>
                        </svg>
                    </a>
                </div>
                
                <div class="main-menu">
                    <nav>
                        <ul>
                            <li<?php echo $current_page === 'index.php' ? ' class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>/"><i class="fas fa-home"></i> Trang chủ</a>
                            </li>
                            <li<?php echo $current_page === 'anime.php' ? ' class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>/pages/anime.php"><i class="fas fa-tv"></i> Anime</a>
                            </li>
                            <li<?php echo $current_page === 'movie.php' ? ' class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>/pages/movie.php"><i class="fas fa-film"></i> Phim</a>
                            </li>
                            <li<?php echo $current_page === 'favorites.php' ? ' class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>/pages/favorites.php"><i class="fas fa-heart"></i> Yêu thích</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <form action="<?php echo BASE_URL; ?>/pages/search.php" method="get">
                            <input type="text" name="keyword" placeholder="Tìm kiếm phim..." required>
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    
                    <button class="dark-mode-toggle" id="dark-mode-toggle" aria-label="Chuyển đổi chế độ sáng/tối">
                        <div class="dark-mode-icon">
                            <i class="fas fa-sun sun-icon"></i>
                            <i class="fas fa-moon moon-icon"></i>
                        </div>
                        <span class="toggle-text"><?php echo $theme === 'dark' ? 'Chế độ sáng' : 'Chế độ tối'; ?></span>
                    </button>
                    
                    <?php if ($current_user): ?>
                    <div class="notification-container">
                        <button class="notification-btn" id="notification-btn">
                            <i class="fas fa-bell"></i>
                            <?php $unread_count = count_unread_notifications($current_user['id']); ?>
                            <?php if ($unread_count > 0): ?>
                            <span class="notification-count" id="notification-count"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-dropdown" id="notification-dropdown">
                            <div class="notification-header">
                                <h3>Thông báo</h3>
                                <a href="#" id="mark-all-read">Đánh dấu đã đọc</a>
                            </div>
                            <div class="notification-body">
                                <div class="notification-loading" id="notification-loading">
                                    <div class="loader"></div>
                                    <p>Đang tải thông báo...</p>
                                </div>
                                <div class="notification-list" id="notification-list">
                                    <!-- Thông báo sẽ được tải bằng JavaScript -->
                                </div>
                            </div>
                            <div class="notification-footer">
                                <a href="/pages/notifications.php">Xem tất cả thông báo</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="<?php echo $current_user['profile_image']; ?>" alt="<?php echo $current_user['username']; ?>">
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
                                <li><a href="/pages/profile.php"><i class="fas fa-user"></i> Hồ sơ</a></li>
                                <li><a href="/pages/history.php"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                                <li><a href="/pages/favorites.php"><i class="fas fa-heart"></i> Yêu thích</a></li>
                                <?php if (!is_vip($current_user)): ?>
                                <li><a href="/pages/premium.php"><i class="fas fa-crown"></i> Nâng cấp VIP</a></li>
                                <?php endif; ?>
                                <?php if (is_admin($current_user)): ?>
                                <li><a href="/admin/index.php"><i class="fas fa-cog"></i> Quản trị</a></li>
                                <?php endif; ?>
                                <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="auth-buttons">
                        <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn btn-outline">Đăng nhập</a>
                        <a href="<?php echo BASE_URL; ?>/pages/register.php" class="btn btn-primary">Đăng ký</a>
                    </div>
                    <?php endif; ?>
                    
                    <button class="mobile-menu-toggle" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <div class="mobile-menu">
        <div class="menu-header">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>/">Lọc Phim</a>
            </div>
            <button class="close-menu-btn"><i class="fas fa-times"></i></button>
        </div>
        <nav>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/anime.php"><i class="fas fa-tv"></i> Anime</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/movie.php"><i class="fas fa-film"></i> Phim</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/favorites.php"><i class="fas fa-heart"></i> Yêu thích</a></li>
                <?php if ($current_user): ?>
                <li><a href="<?php echo BASE_URL; ?>/pages/profile.php"><i class="fas fa-user"></i> Hồ sơ</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/history.php"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                <?php if (!$current_user['is_premium']): ?>
                <li><a href="<?php echo BASE_URL; ?>/pages/premium.php"><i class="fas fa-crown"></i> Nâng cấp VIP</a></li>
                <?php endif; ?>
                <?php if ($current_user['role'] === 'admin'): ?>
                <li><a href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-cog"></i> Quản trị</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/api/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/pages/login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
