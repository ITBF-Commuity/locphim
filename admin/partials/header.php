<?php
/**
 * Header trang quản trị
 * Lọc Phim - Admin Header
 */

// Yêu cầu đăng nhập
$admin = require_admin_login();

// Định nghĩa hằng số bảo vệ tệp
define('SECURE_ACCESS', true);

// Tạo CSRF token
$csrf_token = generate_csrf_token();

// Kiểm tra chế độ bảo trì
check_maintenance_mode();

// Lấy tên trang
$page_title = $page_title ?? 'Bảng Điều Khiển';
$site_name = get_setting('site_name', 'Lọc Phim');
$full_title = "$page_title - $site_name Admin";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($full_title); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="/admin/assets/css/admin.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --bs-primary: #2563eb;
            --bs-primary-rgb: 37, 99, 235;
            --bs-secondary: #64748b;
            --bs-success: #10b981;
            --bs-info: #0ea5e9;
            --bs-warning: #f59e0b;
            --bs-danger: #ef4444;
            --bs-dark: #1e293b;
            --bs-body-bg: #f8fafc;
            --bs-body-color: #334155;
            --bs-border-color: #e2e8f0;
        }
        
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Sidebar */
        .admin-sidebar {
            background-color: #1e293b;
            width: 280px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1040;
        }
        
        .sidebar-logo {
            padding: 1.5rem;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-logo img {
            max-height: 32px;
            margin-right: 0.75rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-header {
            color: #94a3b8;
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .sidebar-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: var(--bs-primary);
        }
        
        .sidebar-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--bs-primary);
        }
        
        .sidebar-link i {
            width: 1.25rem;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1050;
            display: none;
        }
        
        /* Content */
        .admin-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Navbar */
        .admin-navbar {
            background-color: white;
            border-bottom: 1px solid var(--bs-border-color);
            padding: 0.75rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        /* Cards */
        .admin-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--bs-border-color);
            margin-bottom: 1.5rem;
        }
        
        .admin-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--bs-border-color);
            background-color: #f8fafc;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }
        
        .admin-card-body {
            padding: 1.5rem;
        }
        
        .admin-card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--bs-border-color);
            background-color: #f8fafc;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
        
        /* Page Header */
        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }
        
        .admin-page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: #1e293b;
        }
        
        .admin-page-subtitle {
            color: #64748b;
            margin: 0;
        }
        
        /* Stats Cards */
        .stat-card {
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--bs-border-color);
            height: 100%;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--bs-primary);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .admin-sidebar {
                left: -280px;
            }
            
            .admin-sidebar.show {
                left: 0;
            }
            
            .admin-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .admin-navbar {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1030;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="btn btn-primary sidebar-toggle" type="button" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-logo">
            <img src="/assets/favicon.ico" alt="Logo">
            <span>Quản Trị Lọc Phim</span>
        </div>
        
        <div class="sidebar-nav">
            <div class="sidebar-header">TỔNG QUAN</div>
            <a href="/admin/index.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Bảng Điều Khiển
            </a>
            
            <div class="sidebar-header">QUẢN LÝ PHIM</div>
            <?php if (check_admin_permission('view_anime')): ?>
            <a href="/admin/anime.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'anime.php' ? 'active' : ''; ?>">
                <i class="fas fa-tv"></i> Danh Sách Anime
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('add_anime')): ?>
            <a href="/admin/add-anime.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'add-anime.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Thêm Anime Mới
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('view_episodes')): ?>
            <a href="/admin/episodes.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'episodes.php' ? 'active' : ''; ?>">
                <i class="fas fa-film"></i> Quản Lý Tập Phim
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('view_categories')): ?>
            <a href="/admin/categories.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Quản Lý Thể Loại
            </a>
            <?php endif; ?>
            
            <div class="sidebar-header">THÀNH VIÊN</div>
            <?php if (check_admin_permission('view_users')): ?>
            <a href="/admin/users.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Quản Lý Người Dùng
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('view_roles')): ?>
            <a href="/admin/roles.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'roles.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Phân Quyền Admin
            </a>
            <?php endif; ?>
            
            <div class="sidebar-header">TƯƠNG TÁC</div>
            <?php if (check_admin_permission('view_comments')): ?>
            <a href="/admin/comments.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'comments.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Quản Lý Bình Luận
            </a>
            <?php endif; ?>
            
            <div class="sidebar-header">CÀI ĐẶT</div>
            <?php if (check_admin_permission('view_settings')): ?>
            <a href="/admin/settings.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Cài Đặt Hệ Thống
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('edit_api_settings')): ?>
            <a href="/admin/api-settings.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'api-settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-plug"></i> Tích Hợp API
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('edit_seo')): ?>
            <a href="/admin/seo.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'seo.php' ? 'active' : ''; ?>">
                <i class="fas fa-search"></i> Cài Đặt SEO
            </a>
            <?php endif; ?>
            
            <div class="sidebar-header">HỆ THỐNG</div>
            <?php if (check_admin_permission('view_logs')): ?>
            <a href="/admin/logs.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Nhật Ký Hệ Thống
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('manage_cache')): ?>
            <a href="/admin/performance.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'performance.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Tối Ưu Hiệu Suất
            </a>
            <?php endif; ?>
            
            <?php if (check_admin_permission('manage_maintenance')): ?>
            <a href="/admin/check-config.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'check-config.php' ? 'active' : ''; ?>">
                <i class="fas fa-wrench"></i> Kiểm Tra Hệ Thống
            </a>
            <?php endif; ?>
            
            <div class="sidebar-header">TÀI KHOẢN</div>
            <a href="/admin/profile.php" class="sidebar-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Hồ Sơ Cá Nhân
            </a>
            <a href="/admin/logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i> Đăng Xuất
            </a>
        </div>
    </div>
    
    <!-- Content -->
    <div class="admin-content" id="adminContent">
        <!-- Navbar -->
        <div class="admin-navbar">
            <h1 class="navbar-title"><?php echo htmlspecialchars($page_title); ?></h1>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_username']); ?>&background=random" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                        <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user-circle me-2"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="/admin/settings.php"><i class="fas fa-cog me-2"></i> Cài đặt</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php 
        // Hiển thị thông báo flash
        $flash = get_flash_message();
        if ($flash): 
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="admin-main">