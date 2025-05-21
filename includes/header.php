<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : SITE_DESCRIPTION; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo url($_SERVER['REQUEST_URI']); ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : SITE_DESCRIPTION; ?>">
    <meta property="og:image" content="<?php echo isset($ogImage) ? $ogImage : url('assets/images/logo.svg'); ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo url($_SERVER['REQUEST_URI']); ?>">
    <meta property="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle : SITE_NAME; ?>">
    <meta property="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : SITE_DESCRIPTION; ?>">
    <meta property="twitter:image" content="<?php echo isset($ogImage) ? $ogImage : url('assets/images/logo.svg'); ?>">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo url('assets/images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo url('assets/css/locphim.css?v=' . CACHE_VERSION); ?>">
    
    <!-- Dark Mode CSS - Conditionally loaded -->
    <?php if (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1'): ?>
    <link rel="stylesheet" href="<?php echo url('assets/css/dark-mode.css?v=' . CACHE_VERSION); ?>" id="dark-mode-css">
    <?php endif; ?>
    
    <!-- Custom CSS Variables -->
    <style>
        :root {
            --primary-color: #14b8a6;
            --primary-hover: #0d9488;
            --secondary-color: #f97316;
            --secondary-hover: #ea580c;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --bg-color: #ffffff;
            --bg-light: #f8fafc;
            --bg-dark: #020617;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --header-bg: #ffffff;
            --footer-bg: #f1f5f9;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --vip-color: #fbbf24;
            --vip-bg: #fffbeb;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            --border-radius: 0.5rem;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-weight-normal: 400;
            --font-weight-medium: 500;
            --font-weight-bold: 700;
            --transition: all 0.3s ease;
        }
        
        body.dark-mode {
            --primary-color: #14b8a6;
            --primary-hover: #0d9488;
            --secondary-color: #f97316;
            --secondary-hover: #ea580c;
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --bg-color: #0f172a;
            --bg-light: #1e293b;
            --bg-dark: #020617;
            --border-color: #334155;
            --card-bg: #1e293b;
            --header-bg: #0f172a;
            --footer-bg: #0f172a;
            --vip-bg: #422006;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
        }
        
        a {
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header & Navigation */
        .header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--box-shadow);
        }
        
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }
        
        .main-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-color);
            font-weight: var(--font-weight-medium);
            transition: var(--transition);
            position: relative;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -27px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-weight: var(--font-weight-medium);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-hover);
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.1rem;
        }
        
        /* Search */
        .search-box {
            position: relative;
        }
        
        .search-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .search-toggle:hover {
            color: var(--primary-color);
        }
        
        .search-form {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--box-shadow);
            display: none;
            z-index: 10;
        }
        
        .search-form.active {
            display: block;
        }
        
        .search-input-container {
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background-color: var(--bg-light);
            color: var(--text-color);
        }
        
        .search-input:focus {
            outline: none;
        }
        
        .search-button {
            padding: 0.75rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .search-button:hover {
            background-color: var(--primary-hover);
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid var(--primary-color);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 200px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 0.5rem 0;
            display: none;
            z-index: 10;
        }
        
        .user-dropdown.active {
            display: block;
        }
        
        .user-dropdown-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }
        
        .user-name {
            font-weight: var(--font-weight-medium);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 0.5rem 0;
        }
        
        .dropdown-menu {
            list-style: none;
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background-color: var(--bg-light);
            color: var(--primary-color);
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Mobile Navigation */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-color);
            display: none;
            z-index: 200;
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .mobile-menu-close {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .mobile-menu-body {
            padding: 1rem;
        }
        
        .mobile-nav {
            list-style: none;
        }
        
        .mobile-nav-item {
            margin-bottom: 0.5rem;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            font-size: 1.1rem;
            color: var(--text-color);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .mobile-nav-link.active,
        .mobile-nav-link:hover {
            background-color: var(--bg-light);
            color: var(--primary-color);
        }
        
        .mobile-nav-link i {
            width: 24px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-nav {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        /* Dark Mode Toggle */
        .dark-mode-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .dark-mode-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .toast {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            background-color: var(--card-bg);
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease, fadeOut 0.5s ease 2.5s forwards;
            max-width: 350px;
        }
        
        .toast-icon {
            font-size: 1.5rem;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: var(--font-weight-medium);
            margin-bottom: 0.25rem;
        }
        
        .toast-message {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .toast.success .toast-icon {
            color: var(--success-color);
        }
        
        .toast.error .toast-icon {
            color: var(--danger-color);
        }
        
        .toast.warning .toast-icon {
            color: var(--warning-color);
        }
        
        .toast.info .toast-icon {
            color: var(--info-color);
        }
        
        .toast-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .toast-close:hover {
            color: var(--text-color);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                display: none;
            }
        }
    </style>
</head>
<body<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1' ? ' class="dark-mode"' : ''; ?>>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <?php if (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1'): ?>
                        <a href="<?php echo url(''); ?>">
                            <img src="<?php echo url('assets/images/logo-dark.svg'); ?>" alt="<?php echo SITE_NAME; ?>">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo url(''); ?>">
                            <img src="<?php echo url('assets/images/logo.svg'); ?>" alt="<?php echo SITE_NAME; ?>">
                        </a>
                    <?php endif; ?>
                </div>
                
                <ul class="main-nav">
                    <li>
                        <a href="<?php echo url(''); ?>" class="nav-link<?php echo is_current_page('home') ? ' active' : ''; ?>">
                            Trang chủ
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('danh-sach/phim-le'); ?>" class="nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-le') ? ' active' : ''; ?>">
                            Phim lẻ
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('danh-sach/phim-bo'); ?>" class="nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-bo') ? ' active' : ''; ?>">
                            Phim bộ
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('danh-sach/anime'); ?>" class="nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'anime') ? ' active' : ''; ?>">
                            Anime
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('danh-sach/phim-moi'); ?>" class="nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-moi') ? ' active' : ''; ?>">
                            Phim mới
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('danh-sach/phim-xem-nhieu'); ?>" class="nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-xem-nhieu') ? ' active' : ''; ?>">
                            Xem nhiều
                        </a>
                    </li>
                </ul>
                
                <div class="nav-actions">
                    <div class="search-box">
                        <button class="search-toggle" id="search-toggle">
                            <i class="fas fa-search"></i>
                        </button>
                        
                        <div class="search-form" id="search-form">
                            <form action="<?php echo url('tim-kiem'); ?>" method="GET">
                                <div class="search-input-container">
                                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm phim..." required>
                                    <button type="submit" class="search-button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <button class="dark-mode-toggle" id="dark-mode-toggle">
                        <?php if (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1'): ?>
                            <i class="fas fa-sun"></i>
                        <?php else: ?>
                            <i class="fas fa-moon"></i>
                        <?php endif; ?>
                    </button>
                    
                    <?php if (isset($user) && $user): ?>
                        <div class="user-menu">
                            <div class="user-avatar" id="user-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo url($user['avatar']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo url('assets/images/avatars/default.png'); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-dropdown" id="user-dropdown">
                                <div class="user-dropdown-header">
                                    <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                
                                <div class="dropdown-divider"></div>
                                
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="<?php echo url('tai-khoan'); ?>" class="dropdown-item">
                                            <i class="fas fa-user"></i> Tài khoản
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo url('tai-khoan/phim-yeu-thich'); ?>" class="dropdown-item">
                                            <i class="fas fa-heart"></i> Phim yêu thích
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo url('tai-khoan/lich-su-xem'); ?>" class="dropdown-item">
                                            <i class="fas fa-history"></i> Lịch sử xem
                                        </a>
                                    </li>
                                    
                                    <?php if (is_admin()): ?>
                                        <div class="dropdown-divider"></div>
                                        <li>
                                            <a href="<?php echo url('admin'); ?>" class="dropdown-item">
                                                <i class="fas fa-cogs"></i> Quản trị viên
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <div class="dropdown-divider"></div>
                                    
                                    <li>
                                        <a href="<?php echo url('dang-xuat'); ?>" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo url('dang-nhap'); ?>" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    <?php endif; ?>
                    
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <div class="logo">
                <?php if (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1'): ?>
                    <img src="<?php echo url('assets/images/logo-dark.svg'); ?>" alt="<?php echo SITE_NAME; ?>" style="height: 36px;">
                <?php else: ?>
                    <img src="<?php echo url('assets/images/logo.svg'); ?>" alt="<?php echo SITE_NAME; ?>" style="height: 36px;">
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-close" id="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mobile-menu-body">
            <form action="<?php echo url('tim-kiem'); ?>" method="GET" class="mobile-search" style="margin-bottom: 1.5rem;">
                <div class="search-input-container">
                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm phim..." required>
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <ul class="mobile-nav">
                <li class="mobile-nav-item">
                    <a href="<?php echo url(''); ?>" class="mobile-nav-link<?php echo is_current_page('home') ? ' active' : ''; ?>">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('danh-sach/phim-le'); ?>" class="mobile-nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-le') ? ' active' : ''; ?>">
                        <i class="fas fa-film"></i> Phim lẻ
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('danh-sach/phim-bo'); ?>" class="mobile-nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-bo') ? ' active' : ''; ?>">
                        <i class="fas fa-tv"></i> Phim bộ
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('danh-sach/anime'); ?>" class="mobile-nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'anime') ? ' active' : ''; ?>">
                        <i class="fas fa-dragon"></i> Anime
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('danh-sach/phim-moi'); ?>" class="mobile-nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-moi') ? ' active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Phim mới
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('danh-sach/phim-xem-nhieu'); ?>" class="mobile-nav-link<?php echo (is_current_page('listing') && isset($params['listing_type']) && $params['listing_type'] === 'phim-xem-nhieu') ? ' active' : ''; ?>">
                        <i class="fas fa-fire"></i> Xem nhiều
                    </a>
                </li>
                
                <?php if (isset($user) && $user): ?>
                    <li class="mobile-nav-item" style="margin-top: 1.5rem;">
                        <a href="<?php echo url('tai-khoan'); ?>" class="mobile-nav-link<?php echo is_current_page('profile') ? ' active' : ''; ?>">
                            <i class="fas fa-user"></i> Tài khoản
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('tai-khoan/phim-yeu-thich'); ?>" class="mobile-nav-link<?php echo is_current_page('profile_favorites') ? ' active' : ''; ?>">
                            <i class="fas fa-heart"></i> Phim yêu thích
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('tai-khoan/lich-su-xem'); ?>" class="mobile-nav-link<?php echo is_current_page('profile_history') ? ' active' : ''; ?>">
                            <i class="fas fa-history"></i> Lịch sử xem
                        </a>
                    </li>
                    
                    <?php if (is_admin()): ?>
                        <li class="mobile-nav-item">
                            <a href="<?php echo url('admin'); ?>" class="mobile-nav-link<?php echo is_current_page('admin') ? ' active' : ''; ?>">
                                <i class="fas fa-cogs"></i> Quản trị viên
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('dang-xuat'); ?>" class="mobile-nav-link">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mobile-nav-item" style="margin-top: 1.5rem;">
                        <a href="<?php echo url('dang-nhap'); ?>" class="mobile-nav-link">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('dang-ky'); ?>" class="mobile-nav-link">
                            <i class="fas fa-user-plus"></i> Đăng ký
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php if (isset($toastMessage) && isset($toastType)): ?>
            <div class="toast-container">
                <div class="toast <?php echo $toastType; ?>">
                    <div class="toast-icon">
                        <?php if ($toastType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php elseif ($toastType === 'error'): ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php elseif ($toastType === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle"></i>
                        <?php else: ?>
                            <i class="fas fa-info-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">
                            <?php
                            switch ($toastType) {
                                case 'success':
                                    echo 'Thành công';
                                    break;
                                case 'error':
                                    echo 'Lỗi';
                                    break;
                                case 'warning':
                                    echo 'Cảnh báo';
                                    break;
                                default:
                                    echo 'Thông báo';
                            }
                            ?>
                        </div>
                        <div class="toast-message"><?php echo $toastMessage; ?></div>
                    </div>
                    <button class="toast-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>