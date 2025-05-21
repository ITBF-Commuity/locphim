<?php
/**
 * Lọc Phim - Modern Header
 * 
 * Header hiện đại cho website với thiết kế Netflix-inspired
 */

// Thiết lập các thông tin mặc định
$pageTitle = $customMetaTitle ?? SITE_NAME . ' - ' . SITE_DESCRIPTION;
$pageDescription = $customMetaDescription ?? SITE_DESCRIPTION;
$pageKeywords = $customMetaKeywords ?? 'phim, phim lẻ, phim bộ, phim hành động, phim tình cảm, phim hài, phim viễn tưởng, anime, hoạt hình';
$pageImage = $customMetaImage ?? url('/assets/images/logo.svg');
$pageCss = $customCss ?? '/assets/css/main.css';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="keywords" content="<?php echo $pageKeywords; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo $pageImage; ?>">
    <meta property="og:url" content="<?php echo currentUrl(); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="<?php echo $pageImage; ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS and Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Modern CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modern.css?v=<?php echo time(); ?>">
    
    <!-- Custom CSS -->
    <?php if (isset($pageCss)): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?><?php echo $pageCss; ?>?v=<?php echo time(); ?>">
    <?php endif; ?>
    
    <!-- Custom Header Tags -->
    <?php if (isset($customHeaderTags)): ?>
        <?php echo $customHeaderTags; ?>
    <?php endif; ?>
    
    <!-- Dark Mode Script -->
    <script>
        // Kiểm tra dark mode từ localStorage
        const isDarkMode = localStorage.getItem('darkMode') === 'false'; // Default is dark mode
        
        // Kiểm tra light-mode
        if (isDarkMode) {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body class="<?php echo (isset($bodyClass) ? $bodyClass : ''); ?>">
    <div class="app-wrapper <?php echo isset($withSidebar) && $withSidebar ? 'with-sidebar' : ''; ?>">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-inner">
                    <div class="d-flex align-items-center">
                        <button id="mobileMenuToggle" class="toggle-button mobile-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <a href="<?php echo url('/'); ?>" class="ml-2">
                            <img src="<?php echo SITE_URL; ?>/assets/images/logo.svg" alt="<?php echo SITE_NAME; ?>" class="logo">
                        </a>
                        
                        <!-- Navigation for Desktop -->
                        <nav class="main-nav ml-4">
                            <ul class="nav-list">
                                <li class="nav-item <?php echo isPath('/') ? 'active' : ''; ?>">
                                    <a href="<?php echo url('/'); ?>" class="nav-link">Trang chủ</a>
                                </li>
                                <li class="nav-item <?php echo isPath('/phim-le') ? 'active' : ''; ?>">
                                    <a href="<?php echo url('/phim-le'); ?>" class="nav-link">Phim lẻ</a>
                                </li>
                                <li class="nav-item <?php echo isPath('/phim-bo') ? 'active' : ''; ?>">
                                    <a href="<?php echo url('/phim-bo'); ?>" class="nav-link">Phim bộ</a>
                                </li>
                                <li class="nav-item <?php echo isPath('/anime') ? 'active' : ''; ?>">
                                    <a href="<?php echo url('/anime'); ?>" class="nav-link">Anime</a>
                                </li>
                                <li class="nav-item <?php echo isPath('/bang-xep-hang') ? 'active' : ''; ?>">
                                    <a href="<?php echo url('/bang-xep-hang'); ?>" class="nav-link">BXH</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    
                    <div class="search-bar">
                        <form action="<?php echo url('/tim-kiem'); ?>" method="get">
                            <input type="text" name="q" class="search-input" placeholder="Tìm kiếm phim, anime..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <button type="submit" class="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="header-actions">
                        <!-- VIP Button -->
                        <?php if (isVip()): ?>
                            <a href="<?php echo url('/tai-khoan/vip'); ?>" class="header-action" title="VIP">
                                <i class="fas fa-crown"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url('/vip'); ?>" class="header-action" title="Nâng cấp VIP">
                                <i class="fas fa-crown"></i>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Notifications -->
                        <a href="#" class="header-action" title="Thông báo">
                            <i class="fas fa-bell"></i>
                        </a>
                        
                        <!-- Dark/Light Mode Toggle -->
                        <button id="darkModeToggle" class="header-action toggle-button" title="Chuyển đổi chế độ tối/sáng">
                            <i class="fas fa-sun light-icon"></i>
                            <i class="fas fa-moon dark-icon"></i>
                        </button>
                        
                        <!-- User Menu -->
                        <?php if (isLoggedIn()): ?>
                            <?php $currentUser = getCurrentUser(); ?>
                            <div class="user-menu">
                                <div class="user-avatar" title="<?php echo htmlspecialchars($currentUser['username']); ?>">
                                    <img src="<?php echo url($currentUser['avatar'] ? $currentUser['avatar'] : 'assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($currentUser['username']); ?>">
                                </div>
                                
                                <div class="user-dropdown">
                                    <a href="<?php echo url('/tai-khoan'); ?>" class="user-dropdown-item">
                                        <i class="fas fa-user"></i> Tài khoản của tôi
                                    </a>
                                    <a href="<?php echo url('/tai-khoan/yeu-thich'); ?>" class="user-dropdown-item">
                                        <i class="fas fa-heart"></i> Phim yêu thích
                                    </a>
                                    <a href="<?php echo url('/tai-khoan/lich-su'); ?>" class="user-dropdown-item">
                                        <i class="fas fa-history"></i> Lịch sử xem
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php if (isAdmin()): ?>
                                        <a href="<?php echo url('/admin'); ?>" class="user-dropdown-item">
                                            <i class="fas fa-cog"></i> Quản trị viên
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    <a href="<?php echo url('/dang-xuat'); ?>" class="user-dropdown-item">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo url('/dang-nhap'); ?>" class="btn btn-sm btn-outline ml-2">Đăng nhập</a>
                            <a href="<?php echo url('/dang-ky'); ?>" class="btn btn-sm ml-2">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        
        <?php if (isset($withSidebar) && $withSidebar): ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-heading">Thể loại</div>
            <ul class="sidebar-menu">
                <?php
                $isInstall = isPath('/install-db');
                $genres = [];
                if (!$isInstall) {
                    try {
                        $genres = $db->getAll("SELECT * FROM genres ORDER BY name ASC");
                    } catch (Exception $e) {
                        // Không làm gì, danh sách thể loại sẽ trống
                    }
                }
                if ($genres):
                    foreach ($genres as $genre):
                ?>
                    <li class="sidebar-item <?php echo isset($_GET['genre_slug']) && $_GET['genre_slug'] == $genre['slug'] ? 'active' : ''; ?>">
                        <a href="<?php echo url('/the-loai/' . $genre['slug']); ?>" class="sidebar-link">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($genre['name']); ?>
                        </a>
                    </li>
                <?php
                    endforeach;
                else:
                ?>
                    <li class="sidebar-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-tag"></i> Chưa có thể loại
                        </a>
                    </li>
                <?php
                endif;
                ?>
            </ul>
            
            <div class="sidebar-heading">Quốc gia</div>
            <ul class="sidebar-menu">
                <?php
                $countries = [];
                if (!$isInstall) {
                    try {
                        $countries = $db->getAll("SELECT * FROM countries ORDER BY name ASC");
                    } catch (Exception $e) {
                        // Không làm gì, danh sách quốc gia sẽ trống
                    }
                }
                if ($countries):
                    foreach ($countries as $country):
                ?>
                    <li class="sidebar-item <?php echo isset($_GET['country_slug']) && $_GET['country_slug'] == $country['slug'] ? 'active' : ''; ?>">
                        <a href="<?php echo url('/quoc-gia/' . $country['slug']); ?>" class="sidebar-link">
                            <i class="fas fa-globe"></i> <?php echo htmlspecialchars($country['name']); ?>
                        </a>
                    </li>
                <?php
                    endforeach;
                else:
                ?>
                    <li class="sidebar-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-globe"></i> Chưa có quốc gia
                        </a>
                    </li>
                <?php
                endif;
                ?>
            </ul>
        </aside>
        <?php endif; ?>
                
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <h2 class="mobile-menu-title">Lọc Phim</h2>
                <button class="mobile-menu-close" id="mobileMenuClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mobile-menu-content">
                <?php if (isLoggedIn()): ?>
                    <?php $currentUser = getCurrentUser(); ?>
                    <div class="mobile-user-section">
                        <div class="mobile-user-avatar">
                            <img src="<?php echo url($currentUser['avatar'] ? $currentUser['avatar'] : 'assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($currentUser['username']); ?>">
                        </div>
                        <div class="mobile-user-info">
                            <div class="mobile-user-name"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mobile-auth-section">
                        <a href="<?php echo url('/dang-nhap'); ?>" class="btn w-100 mb-2">Đăng nhập</a>
                        <a href="<?php echo url('/dang-ky'); ?>" class="btn btn-outline w-100">Đăng ký</a>
                    </div>
                <?php endif; ?>
                
                <div class="mobile-section-title">DANH MỤC</div>
                
                <ul class="mobile-nav-list">
                    <li class="mobile-nav-item <?php echo isPath('/') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/'); ?>" class="mobile-nav-link">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/phim-le') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/phim-le'); ?>" class="mobile-nav-link">
                            <i class="fas fa-film"></i> Phim lẻ
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/phim-bo') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/phim-bo'); ?>" class="mobile-nav-link">
                            <i class="fas fa-tv"></i> Phim bộ
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/anime') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/anime'); ?>" class="mobile-nav-link">
                            <i class="fas fa-dragon"></i> Anime
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/phim-chieu-rap') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/phim-chieu-rap'); ?>" class="mobile-nav-link">
                            <i class="fas fa-ticket-alt"></i> Phim chiếu rạp
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/phim-sap-chieu') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/phim-sap-chieu'); ?>" class="mobile-nav-link">
                            <i class="fas fa-calendar-alt"></i> Phim sắp chiếu
                        </a>
                    </li>
                    <li class="mobile-nav-item <?php echo isPath('/bang-xep-hang') ? 'active' : ''; ?>">
                        <a href="<?php echo url('/bang-xep-hang'); ?>" class="mobile-nav-link">
                            <i class="fas fa-chart-line"></i> BXH
                        </a>
                    </li>
                </ul>
                
                <?php if (isLoggedIn()): ?>
                    <div class="mobile-section-title">TÀI KHOẢN</div>
                    <ul class="mobile-nav-list">
                        <li class="mobile-nav-item">
                            <a href="<?php echo url('/tai-khoan'); ?>" class="mobile-nav-link">
                                <i class="fas fa-user"></i> Tài khoản của tôi
                            </a>
                        </li>
                        <li class="mobile-nav-item">
                            <a href="<?php echo url('/tai-khoan/yeu-thich'); ?>" class="mobile-nav-link">
                                <i class="fas fa-heart"></i> Phim yêu thích
                            </a>
                        </li>
                        <li class="mobile-nav-item">
                            <a href="<?php echo url('/tai-khoan/lich-su'); ?>" class="mobile-nav-link">
                                <i class="fas fa-history"></i> Lịch sử xem
                            </a>
                        </li>
                        <?php if (isVip()): ?>
                            <li class="mobile-nav-item">
                                <a href="<?php echo url('/tai-khoan/vip'); ?>" class="mobile-nav-link">
                                    <i class="fas fa-crown"></i> VIP của tôi
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="mobile-nav-item">
                                <a href="<?php echo url('/vip'); ?>" class="mobile-nav-link">
                                    <i class="fas fa-crown"></i> Nâng cấp VIP
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <li class="mobile-nav-item">
                                <a href="<?php echo url('/admin'); ?>" class="mobile-nav-link">
                                    <i class="fas fa-cog"></i> Quản trị viên
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="mobile-nav-item">
                            <a href="<?php echo url('/dang-xuat'); ?>" class="mobile-nav-link">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                
                <div class="mobile-dark-mode">
                    <span>Dark Mode</span>
                    <label class="dark-mode-switch">
                        <input type="checkbox" id="darkModeToggleMobile" <?php echo isDarkMode() ? '' : 'checked'; ?>>
                        <span class="dark-mode-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php if (isPath('/install-db') || !isDatabaseInstalled()): ?>
                <div class="container">
                    <div class="alert bg-dark radius shadow p-3 mt-3">
                        <h4 class="mb-2"><i class="fas fa-exclamation-triangle text-primary"></i> Lưu ý!</h4>
                        <p>Cơ sở dữ liệu chưa được cài đặt hoặc bạn đang trong quá trình cài đặt.</p>
                        <p class="mb-0">Hãy <a href="<?php echo url('/install-db'); ?>" class="text-primary">Cài đặt cơ sở dữ liệu</a> để sử dụng đầy đủ tính năng.</p>
                    </div>
                </div>
            <?php endif; ?>