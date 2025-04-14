<?php
// Include functions và config
define('SECURE_ACCESS', true);
require_once 'functions.php';
require_once 'auth.php';

// Kiểm tra đăng nhập từ cookie
if (function_exists('check_remember_me')) {
    check_remember_me();
}

// Lấy thông tin user
$current_user = null;
// Tạm thời bỏ qua phần lấy thông tin người dùng đến khi hoàn thiện chức năng
/*
if (function_exists('is_logged_in') && is_logged_in()) {
    $current_user = get_user_info();
}
*/

// Lấy tiêu đề trang
$page_title = $page_title ?? 'Xem phim và anime HD';
$site_name = get_config('site.name');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo $site_name; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="generated-icon.png" type="image/png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Video.js -->
    <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (strpos($_SERVER['REQUEST_URI'], 'watch.php') !== false): ?>
    <link rel="stylesheet" href="assets/css/player.css">
    <?php endif; ?>
</head>
<body>
    <!-- Header & Navigation -->
    <header class="bg-dark text-white">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand fw-bold" href="index.php">
                    <i class="fas fa-play-circle text-primary me-2"></i>
                    <?php echo $site_name; ?>
                </a>
                
                <!-- Mobile Toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Navigation -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                Thể loại
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="anime.php?category=1">Hành động</a></li>
                                <li><a class="dropdown-item" href="anime.php?category=2">Hài hước</a></li>
                                <li><a class="dropdown-item" href="anime.php?category=3">Lãng mạn</a></li>
                                <li><a class="dropdown-item" href="anime.php?category=4">Phiêu lưu</a></li>
                                <li><a class="dropdown-item" href="anime.php?category=5">Kinh dị</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="categories.php">Xem tất cả</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="ranking.php">Bảng xếp hạng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="vip.php">VIP</a>
                        </li>
                    </ul>
                    
                    <!-- Search Form -->
                    <form class="d-flex me-2 my-2 my-lg-0" action="search.php" method="get">
                        <input class="form-control me-2 bg-dark text-white" type="search" name="q" placeholder="Tìm kiếm anime..." aria-label="Search">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- User Menu -->
                    <?php if ($current_user): ?>
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if ($current_user['avatar']): ?>
                                    <img src="<?php echo $current_user['avatar']; ?>" class="rounded-circle me-2" width="30" height="30" alt="<?php echo $current_user['username']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-user-circle me-2"></i>
                                <?php endif; ?>
                                <span class="d-none d-md-inline"><?php echo $current_user['username']; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Tài khoản</a></li>
                                <li><a class="dropdown-item" href="favorites.php"><i class="fas fa-heart me-2"></i> Yêu thích</a></li>
                                <li><a class="dropdown-item" href="history.php"><i class="fas fa-history me-2"></i> Lịch sử xem</a></li>
                                <?php if (is_admin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/"><i class="fas fa-tools me-2"></i> Quản trị</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="ms-lg-2">
                            <a href="login.php" class="btn btn-outline-primary me-2">Đăng nhập</a>
                            <a href="register.php" class="btn btn-primary d-none d-lg-inline-block">Đăng ký</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="container py-4">
        <!-- Flash Messages -->
        <?php display_flash_message(); ?>