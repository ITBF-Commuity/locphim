<?php
/**
 * Lọc Phim - Trang thông tin VIP
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin VIP - Lọc Phim</title>
    <meta name="description" content="Thông tin tài khoản VIP của bạn">
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
        
        .vip-status {
            background-color: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .vip-status-info h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .vip-status-info p {
            color: var(--gray);
        }
        
        .vip-status-info strong {
            color: var(--light-gray);
        }
        
        .vip-status-actions {
            display: flex;
            gap: 10px;
        }
        
        .vip-badge {
            width: 80px;
            height: 80px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .vip-benefits {
            margin-bottom: 30px;
        }
        
        .vip-benefits h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--dark-gray);
            padding-bottom: 10px;
        }
        
        .benefits-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: var(--border-radius);
        }
        
        .benefit-item i {
            color: var(--success-color);
            font-size: 1.5rem;
        }
        
        .vip-history h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--dark-gray);
            padding-bottom: 10px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .history-table th {
            background-color: rgba(0, 0, 0, 0.3);
            font-weight: 500;
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-method img {
            height: 20px;
            width: auto;
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
            .vip-status {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .benefit-item {
                flex-direction: column;
                text-align: center;
            }
            
            .history-table {
                font-size: 0.9rem;
            }
            
            .history-table th,
            .history-table td {
                padding: 8px;
            }
        }
        
        @media (max-width: 576px) {
            .benefits-list {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
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
                            <a href="/tai-khoan/vip">Thông tin VIP</a>
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
                        <span class="user-status">VIP</span>
                    </div>
                    
                    <ul class="user-menu">
                        <li><a href="/tai-khoan"><i class="fas fa-home"></i> Tổng quan</a></li>
                        <li><a href="/tai-khoan/thong-tin"><i class="fas fa-user"></i> Thông tin tài khoản</a></li>
                        <li><a href="/tai-khoan/doi-mat-khau"><i class="fas fa-lock"></i> Đổi mật khẩu</a></li>
                        <li><a href="/tai-khoan/yeu-thich"><i class="fas fa-heart"></i> Phim yêu thích</a></li>
                        <li><a href="/tai-khoan/lich-su"><i class="fas fa-history"></i> Lịch sử xem</a></li>
                        <li><a href="/tai-khoan/vip" class="active"><i class="fas fa-crown"></i> Thông tin VIP</a></li>
                    </ul>
                </div>
                
                <div class="content">
                    <div class="vip-status">
                        <div class="vip-badge">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="vip-status-info">
                            <h3>Bạn đang là thành viên VIP</h3>
                            <p>Thời hạn VIP của bạn sẽ hết vào <strong><?php echo date('d/m/Y H:i', strtotime($currentUser['vip_expired_at'])); ?></strong></p>
                            <p>Thời gian còn lại: <strong><?php echo $remainingTime; ?> ngày</strong></p>
                        </div>
                        <div class="vip-status-actions">
                            <a href="/vip" class="btn btn-primary">Gia hạn VIP</a>
                        </div>
                    </div>
                    
                    <div class="vip-benefits">
                        <h3>Quyền lợi VIP của bạn</h3>
                        <ul class="benefits-list">
                            <li class="benefit-item">
                                <i class="fas fa-video"></i>
                                <div>
                                    <h4>Chất lượng video HD/4K</h4>
                                    <p>Xem phim với chất lượng tốt nhất</p>
                                </div>
                            </li>
                            <li class="benefit-item">
                                <i class="fas fa-ban"></i>
                                <div>
                                    <h4>Không quảng cáo</h4>
                                    <p>Xem phim không bị gián đoạn</p>
                                </div>
                            </li>
                            <li class="benefit-item">
                                <i class="fas fa-server"></i>
                                <div>
                                    <h4>Máy chủ ưu tiên</h4>
                                    <p>Truy cập máy chủ phát nhanh</p>
                                </div>
                            </li>
                            <li class="benefit-item">
                                <i class="fas fa-download"></i>
                                <div>
                                    <h4>Tải xuống phim</h4>
                                    <p>Tải phim để xem offline</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="vip-history">
                        <h3>Lịch sử gói VIP</h3>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Ngày mua</th>
                                    <th>Gói VIP</th>
                                    <th>Thời hạn</th>
                                    <th>Phương thức thanh toán</th>
                                    <th>Số tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vipHistory as $history): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($history['package_name']); ?></td>
                                    <td><?php echo htmlspecialchars($history['duration']); ?> ngày</td>
                                    <td>
                                        <div class="payment-method">
                                            <?php if ($history['payment_method'] === 'momo'): ?>
                                            <img src="/assets/images/momo-logo.png" alt="MoMo">
                                            <span>MoMo</span>
                                            <?php elseif ($history['payment_method'] === 'vnpay'): ?>
                                            <img src="/assets/images/vnpay-logo.png" alt="VNPAY">
                                            <span>VNPAY</span>
                                            <?php elseif ($history['payment_method'] === 'stripe'): ?>
                                            <img src="/assets/images/stripe-logo.png" alt="Stripe">
                                            <span>Stripe</span>
                                            <?php else: ?>
                                            <span><?php echo htmlspecialchars($history['payment_method']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($history['amount'], 0, ',', '.'); ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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