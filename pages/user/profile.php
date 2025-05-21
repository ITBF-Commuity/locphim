<?php
/**
 * Lọc Phim - Trang tài khoản người dùng
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi - Lọc Phim</title>
    <meta name="description" content="Quản lý tài khoản Lọc Phim của bạn">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #b81d24;
            --background-color: #141414;
            --text-color: #ffffff;
            --light-gray: #f1f3f5;
            --gray: #adb5bd;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-radius: 4px;
            --box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            color: var(--text-color);
            text-decoration: none;
        }
        
        a:hover {
            color: var(--primary-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 15px 0;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .main-nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        .main-nav a {
            font-weight: 500;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: rgba(0, 0, 0, 0.9);
            min-width: 160px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            z-index: 1;
        }
        
        .dropdown-content a {
            color: var(--text-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }
        
        .dropdown-content a:hover {
            background-color: var(--dark-gray);
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            border-color: var(--light-gray);
            color: var(--light-gray);
        }
        
        .main {
            flex: 1;
            padding: 40px 0;
        }
        
        .page-title {
            font-size: 2rem;
            margin-bottom: 30px;
        }
        
        .user-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
            padding: 20px;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            background-color: var(--primary-color);
            color: white;
            margin-bottom: 10px;
        }
        
        .user-menu {
            list-style: none;
        }
        
        .user-menu li {
            margin-bottom: 10px;
        }
        
        .user-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: var(--border-radius);
            transition: background-color 0.3s;
        }
        
        .user-menu a:hover,
        .user-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-menu i {
            width: 20px;
            text-align: center;
        }
        
        .content {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .account-status {
            margin-bottom: 30px;
        }
        
        .status-box {
            background-color: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-info {
            flex: 1;
        }
        
        .status-info h3 {
            margin-bottom: 5px;
        }
        
        .status-actions {
            flex-shrink: 0;
        }
        
        .recent-activity {
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin: 0;
        }
        
        .view-all {
            font-size: 0.9rem;
        }
        
        .activity-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .activity-item {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .activity-item:hover {
            transform: translateY(-5px);
        }
        
        .activity-poster {
            position: relative;
            padding-top: 150%;
            overflow: hidden;
        }
        
        .activity-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .activity-info {
            padding: 10px;
        }
        
        .activity-title {
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .activity-meta {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .footer {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 40px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: var(--gray);
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            text-align: center;
            border-top: 1px solid var(--dark-gray);
            padding-top: 20px;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        @media (max-width: 991px) {
            .user-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                margin-bottom: 30px;
            }
            
            .user-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .user-menu li {
                margin-bottom: 0;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .status-box {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .status-actions {
                width: 100%;
            }
            
            .status-actions .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">Lọc Phim</a>
                <nav class="main-nav">
                    <ul>
                        <li><a href="/">Trang chủ</a></li>
                        <li><a href="/phim-le">Phim lẻ</a></li>
                        <li><a href="/phim-bo">Phim bộ</a></li>
                        <li><a href="/anime">Anime</a></li>
                        <li><a href="/tim-kiem">Tìm kiếm</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <div class="dropdown">
                        <a href="/tai-khoan"><?php echo htmlspecialchars($currentUser['username']); ?></a>
                        <div class="dropdown-content">
                            <a href="/tai-khoan/thong-tin">Thông tin tài khoản</a>
                            <a href="/tai-khoan/yeu-thich">Phim yêu thích</a>
                            <a href="/tai-khoan/lich-su">Lịch sử xem</a>
                            <?php if ($currentUser['is_vip']): ?>
                            <a href="/tai-khoan/vip">Thông tin VIP</a>
                            <?php else: ?>
                            <a href="/vip">Nâng cấp VIP</a>
                            <?php endif; ?>
                            <a href="/dang-xuat">Đăng xuất</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <h1 class="page-title">Tài khoản của tôi</h1>
            
            <div class="user-layout">
                <div class="sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <img src="<?php echo htmlspecialchars($currentUser['avatar'] ?: '/assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($currentUser['username']); ?>">
                        </div>
                        <h3 class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?></h3>
                        <?php if ($currentUser['is_vip']): ?>
                        <span class="user-status">VIP</span>
                        <?php endif; ?>
                    </div>
                    
                    <ul class="user-menu">
                        <li><a href="/tai-khoan" class="active"><i class="fas fa-home"></i> Tổng quan</a></li>
                        <li><a href="/tai-khoan/thong-tin"><i class="fas fa-user"></i> Thông tin tài khoản</a></li>
                        <li><a href="/tai-khoan/doi-mat-khau"><i class="fas fa-lock"></i> Đổi mật khẩu</a></li>
                        <li><a href="/tai-khoan/yeu-thich"><i class="fas fa-heart"></i> Phim yêu thích</a></li>
                        <li><a href="/tai-khoan/lich-su"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                        <?php if ($currentUser['is_vip']): ?>
                        <li><a href="/tai-khoan/vip"><i class="fas fa-crown"></i> Thông tin VIP</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-film"></i>
                            </div>
                            <div class="stat-value"><?php echo $watchedMoviesCount; ?></div>
                            <div class="stat-label">Phim đã xem</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $watchedEpisodesCount; ?></div>
                            <div class="stat-label">Tập phim đã xem</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo $watchTimeHours; ?></div>
                            <div class="stat-label">Giờ xem phim</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-value"><?php echo $favoritesCount; ?></div>
                            <div class="stat-label">Phim yêu thích</div>
                        </div>
                    </div>
                    
                    <div class="account-status">
                        <?php if ($currentUser['is_vip']): ?>
                        <div class="status-box">
                            <div class="status-info">
                                <h3>Tài khoản VIP</h3>
                                <p>Thời hạn VIP của bạn sẽ hết vào: <strong><?php echo date('d/m/Y H:i', strtotime($currentUser['vip_expired_at'])); ?></strong></p>
                            </div>
                            <div class="status-actions">
                                <a href="/tai-khoan/vip" class="btn btn-primary">Xem thông tin VIP</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="status-box">
                            <div class="status-info">
                                <h3>Nâng cấp tài khoản VIP</h3>
                                <p>Trải nghiệm xem phim tốt hơn với chất lượng HD/4K, không quảng cáo và nhiều đặc quyền độc quyền khác.</p>
                            </div>
                            <div class="status-actions">
                                <a href="/vip" class="btn btn-primary">Nâng cấp ngay</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="recent-activity">
                        <div class="section-header">
                            <h2 class="section-title">Xem gần đây</h2>
                            <a href="/tai-khoan/lich-su" class="view-all">Xem tất cả</a>
                        </div>
                        
                        <div class="activity-list">
                            <?php foreach ($recentHistory as $history): ?>
                            <a href="/phim/<?php echo to_slug($history['movie_title']); ?>/<?php echo $history['movie_id']; ?>/tap/<?php echo $history['episode_id']; ?>" class="activity-item">
                                <div class="activity-poster">
                                    <img src="<?php echo htmlspecialchars($history['poster'] ?: '/assets/images/default-poster.svg'); ?>" alt="<?php echo htmlspecialchars($history['movie_title']); ?>">
                                </div>
                                <div class="activity-info">
                                    <h3 class="activity-title"><?php echo htmlspecialchars($history['movie_title']); ?></h3>
                                    <div class="activity-meta">
                                        <?php 
                                        echo $history['episode_title'] 
                                            ? htmlspecialchars($history['episode_title']) 
                                            : 'Tập ' . $history['episode_number']; 
                                        ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?php echo time_elapsed_string($history['updated_at']); ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentHistory)): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 20px;">
                                <p>Bạn chưa xem phim nào.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">Lọc Phim</h3>
                    <p>Trang xem phim trực tuyến với kho phim chất lượng cao dành cho người Việt.</p>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Danh mục</h3>
                    <ul class="footer-links">
                        <li><a href="/phim-le">Phim lẻ</a></li>
                        <li><a href="/phim-bo">Phim bộ</a></li>
                        <li><a href="/anime">Anime</a></li>
                        <li><a href="/phim-chieu-rap">Phim chiếu rạp</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Thông tin</h3>
                    <ul class="footer-links">
                        <li><a href="/gioi-thieu">Giới thiệu</a></li>
                        <li><a href="/dieu-khoan">Điều khoản sử dụng</a></li>
                        <li><a href="/bao-mat">Chính sách bảo mật</a></li>
                        <li><a href="/lien-he">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Liên hệ</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> support@locphim.com</li>
                        <li><i class="fas fa-phone"></i> 0123 456 789</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Lọc Phim. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>
</body>
</html>