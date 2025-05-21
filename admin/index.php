<?php
/**
 * Lọc Phim - Trang quản trị
 * 
 * Trang chính của khu vực quản trị
 */

// Include file cấu hình
require_once '../config.php';

// Include các file cần thiết
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth.php';

// Khởi tạo kết nối database
$db = new Database();

// Kiểm tra xem người dùng đã đăng nhập và là admin chưa
requireAdmin();

// Biến lưu thông báo
$message = '';
$messageType = '';

// Lấy trang hiện tại từ tham số
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Biến lưu tiêu đề trang
$pageTitle = 'Quản trị - ' . SITE_NAME;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    
    <style>
        :root {
            --primary-color: #E50914;
            --primary-hover: #B81D24;
            --secondary-color: #221F1F;
            --text-color: #333;
            --text-light: #767676;
            --text-white: #FFF;
            --bg-white: #FFF;
            --bg-light: #F5F5F5;
            --bg-dark: #221F1F;
            --border-color: #DBDBDB;
            --border-light: #EFEFEF;
            --border-radius: 4px;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
            --header-height: 60px;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        /* Dark mode */
        .dark-mode {
            --text-color: #F5F5F5;
            --text-light: #BBBBBB;
            --bg-white: #1A1A1A;
            --bg-light: #2A2A2A;
            --border-color: #444444;
            --border-light: #333333;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            background-color: var(--bg-light);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        
        /* Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-white);
            border-right: 1px solid var(--border-light);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
            transition: all 0.3s;
        }
        
        .sidebar-hidden .sidebar {
            transform: translateX(-100%);
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        .sidebar-hidden .main-content {
            margin-left: 0;
        }
        
        .header {
            height: var(--header-height);
            background-color: var(--bg-white);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        
        .content {
            padding: 20px;
        }
        
        /* Sidebar */
        .sidebar-header {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
        }
        
        .sidebar-logo {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 18px;
            display: none;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 10px 0;
        }
        
        .sidebar-menu-item {
            margin-bottom: 5px;
        }
        
        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 15px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar-menu-link:hover {
            background-color: var(--bg-light);
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu-link.active {
            background-color: var(--bg-light);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .sidebar-menu-icon {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .sidebar-menu-text {
            flex: 1;
        }
        
        .sidebar-dropdown {
            padding-left: 30px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-dropdown.show {
            max-height: 500px;
        }
        
        .sidebar-divider {
            height: 1px;
            background-color: var(--border-light);
            margin: 10px 0;
        }
        
        /* Header */
        .header-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .header-search {
            display: flex;
            align-items: center;
            background-color: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 5px 10px;
            flex: 1;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .header-search-input {
            border: none;
            background: none;
            outline: none;
            color: var(--text-color);
            flex: 1;
            padding: 5px;
            font-size: 14px;
        }
        
        .header-search-btn {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-action-btn {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 18px;
            position: relative;
        }
        
        .header-action-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--primary-color);
            color: white;
            font-size: 10px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }
        
        .header-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--text-color);
        }
        
        .header-user-info {
            display: none;
        }
        
        .header-user-name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .header-user-role {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .header-user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            min-width: 180px;
            z-index: 1000;
            padding: 5px 0;
            display: none;
        }
        
        .header-user:hover .header-user-dropdown {
            display: block;
        }
        
        .header-dropdown-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 14px;
            gap: 8px;
        }
        
        .header-dropdown-item:hover {
            background-color: var(--bg-light);
        }
        
        .header-dropdown-divider {
            height: 1px;
            background-color: var(--border-light);
            margin: 5px 0;
        }
        
        /* Content */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Cards */
        .card {
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-weight: 700;
            font-size: 16px;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-light);
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }
        
        .stat-icon-primary {
            background-color: rgba(229, 9, 20, 0.1);
            color: var(--primary-color);
        }
        
        .stat-icon-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .stat-icon-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .stat-icon-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .stat-progress {
            margin-top: 10px;
            height: 4px;
            background-color: var(--bg-light);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .stat-progress-bar {
            height: 100%;
            border-radius: 2px;
        }
        
        .stat-progress-primary {
            background-color: var(--primary-color);
        }
        
        .stat-progress-success {
            background-color: var(--success-color);
        }
        
        .stat-progress-info {
            background-color: var(--info-color);
        }
        
        .stat-progress-warning {
            background-color: var(--warning-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-info {
            background-color: var(--info-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-light {
            background-color: var(--bg-light);
            color: var(--text-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            background-color: var(--bg-light);
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-lg {
            padding: 10px 20px;
            font-size: 16px;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        
        .table th {
            font-weight: 600;
            color: var(--text-color);
            background-color: var(--bg-light);
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-striped tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-actions {
            display: flex;
            gap: 5px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--bg-white);
            color: var(--text-color);
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--bg-white);
            color: var(--text-color);
            font-size: 14px;
            transition: border-color 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23777' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 12px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check-input {
            margin-right: 8px;
        }
        
        .form-error {
            color: var(--danger-color);
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            border-color: var(--info-color);
            color: var(--info-color);
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-color: var(--warning-color);
            color: var(--warning-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .pagination-item {
            margin: 0 5px;
        }
        
        .pagination-link {
            display: block;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination-link:hover {
            background-color: var(--bg-light);
        }
        
        .pagination-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Modal */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-backdrop.show .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 18px;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 20px;
            padding: 0;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-user-info {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header-toggle {
                display: block;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .header-search {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="/admin" class="sidebar-logo">
                    <i class="fas fa-film"></i> Lọc Phim
                </a>
                <button class="sidebar-toggle" id="sidebarClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-menu">
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="/admin" class="sidebar-menu-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-tachometer-alt"></i>
                            <span class="sidebar-menu-text">Bảng điều khiển</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=movies" class="sidebar-menu-link <?php echo $page === 'movies' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-film"></i>
                            <span class="sidebar-menu-text">Quản lý phim</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=episodes" class="sidebar-menu-link <?php echo $page === 'episodes' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-list-ol"></i>
                            <span class="sidebar-menu-text">Tập phim</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=categories" class="sidebar-menu-link <?php echo $page === 'categories' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-tags"></i>
                            <span class="sidebar-menu-text">Danh mục</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=countries" class="sidebar-menu-link <?php echo $page === 'countries' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-globe"></i>
                            <span class="sidebar-menu-text">Quốc gia</span>
                        </a>
                    </li>
                    
                    <div class="sidebar-divider"></div>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=users" class="sidebar-menu-link <?php echo $page === 'users' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-users"></i>
                            <span class="sidebar-menu-text">Người dùng</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=comments" class="sidebar-menu-link <?php echo $page === 'comments' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-comments"></i>
                            <span class="sidebar-menu-text">Bình luận</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=reports" class="sidebar-menu-link <?php echo $page === 'reports' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-flag"></i>
                            <span class="sidebar-menu-text">Báo cáo</span>
                        </a>
                    </li>
                    
                    <div class="sidebar-divider"></div>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=payments" class="sidebar-menu-link <?php echo $page === 'payments' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-credit-card"></i>
                            <span class="sidebar-menu-text">Thanh toán</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=settings" class="sidebar-menu-link <?php echo $page === 'settings' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-cog"></i>
                            <span class="sidebar-menu-text">Cài đặt</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-item">
                        <a href="/admin?page=logs" class="sidebar-menu-link <?php echo $page === 'logs' ? 'active' : ''; ?>">
                            <i class="sidebar-menu-icon fas fa-history"></i>
                            <span class="sidebar-menu-text">Nhật ký</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <button class="header-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="header-search">
                    <input type="text" class="header-search-input" placeholder="Tìm kiếm...">
                    <button class="header-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn" id="darkModeToggle" title="Chế độ tối">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <button class="header-action-btn" title="Thông báo">
                        <i class="fas fa-bell"></i>
                        <span class="header-action-badge">5</span>
                    </button>
                    
                    <div class="header-user">
                        <div class="header-user-avatar">
                            <?php
                            $currentUser = getCurrentUser();
                            $userInitial = substr($currentUser['username'], 0, 1);
                            echo !empty($currentUser['avatar']) 
                                ? '<img src="' . $currentUser['avatar'] . '" alt="User">' 
                                : '<span>' . strtoupper($userInitial) . '</span>';
                            ?>
                        </div>
                        
                        <div class="header-user-info">
                            <div class="header-user-name"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                            <div class="header-user-role">Quản trị viên</div>
                        </div>
                        
                        <div class="header-user-dropdown">
                            <a href="/tai-khoan" class="header-dropdown-item">
                                <i class="fas fa-user"></i> Tài khoản
                            </a>
                            <a href="/admin?page=settings" class="header-dropdown-item">
                                <i class="fas fa-cog"></i> Cài đặt
                            </a>
                            <div class="header-dropdown-divider"></div>
                            <a href="/" class="header-dropdown-item">
                                <i class="fas fa-home"></i> Trang chủ
                            </a>
                            <a href="/dang-xuat" class="header-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="content">
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php
                // Include trang tương ứng
                switch ($page) {
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'movies':
                        include 'pages/movies.php';
                        break;
                    case 'episodes':
                        include 'pages/episodes.php';
                        break;
                    case 'categories':
                        include 'pages/categories.php';
                        break;
                    case 'countries':
                        include 'pages/countries.php';
                        break;
                    case 'users':
                        include 'pages/users.php';
                        break;
                    case 'comments':
                        include 'pages/comments.php';
                        break;
                    case 'reports':
                        include 'pages/reports.php';
                        break;
                    case 'payments':
                        include 'pages/payments.php';
                        break;
                    case 'settings':
                        include 'pages/settings.php';
                        break;
                    case 'logs':
                        include 'pages/logs.php';
                        break;
                    default:
                        // Nếu không tìm thấy trang, hiển thị trang dashboard
                        include 'pages/dashboard.php';
                        break;
                }
                ?>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                });
            }
            
            // Dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                    
                    // Save preference to localStorage
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('darkMode', 'enabled');
                        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    } else {
                        localStorage.setItem('darkMode', 'disabled');
                        darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    }
                });
                
                // Load preference from localStorage
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.body.classList.add('dark-mode');
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                }
            }
            
            // Dropdown toggle
            const dropdownToggle = document.querySelectorAll('.dropdown-toggle');
            
            dropdownToggle.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parent = this.closest('.sidebar-menu-item');
                    const dropdown = parent.querySelector('.sidebar-dropdown');
                    
                    if (dropdown) {
                        dropdown.classList.toggle('show');
                    }
                });
            });
        });
    </script>
</body>
</html>