<?php
/**
 * Lọc Phim - Trang gói VIP
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nâng cấp VIP - Lọc Phim</title>
    <meta name="description" content="Nâng cấp tài khoản VIP để xem phim không giới hạn với chất lượng HD, không quảng cáo">
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
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .page-description {
            margin-bottom: 40px;
            text-align: center;
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .vip-packages {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .package-card {
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .package-header {
            background-color: var(--primary-color);
            padding: 15px;
            text-align: center;
        }
        
        .package-title {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .package-price {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .package-duration {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .package-body {
            padding: 20px;
        }
        
        .package-features {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .package-features li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .package-features li:last-child {
            border-bottom: none;
        }
        
        .package-features i {
            color: var(--success-color);
        }
        
        .package-footer {
            padding: 0 20px 20px;
            text-align: center;
        }
        
        .payment-methods {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .payment-method {
            padding: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .payment-method:hover,
        .payment-method.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .payment-method img {
            height: 24px;
            width: auto;
        }
        
        .benefits {
            max-width: 800px;
            margin: 0 auto 40px;
        }
        
        .benefits-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .benefit-card {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
        }
        
        .benefit-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .benefit-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .benefit-description {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }
        
        .comparison-table {
            width: 100%;
            max-width: 900px;
            margin: 0 auto 40px;
            border-collapse: collapse;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .comparison-table th {
            background-color: rgba(0, 0, 0, 0.5);
            font-size: 1.1rem;
        }
        
        .comparison-table .feature-name {
            text-align: left;
            font-weight: 500;
        }
        
        .comparison-table .check {
            color: var(--success-color);
        }
        
        .comparison-table .x {
            color: var(--danger-color);
        }
        
        .payment-form {
            display: none;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: var(--border-radius);
        }
        
        .payment-form h3 {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .payment-form .btn {
            width: 100%;
            margin-top: 10px;
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
            .vip-packages {
                flex-direction: column;
                align-items: center;
            }
            
            .package-card {
                width: 100%;
                max-width: 400px;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .comparison-table {
                font-size: 0.9rem;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .benefits-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .comparison-table {
                font-size: 0.8rem;
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
                    <?php if ($currentUser): ?>
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
                    <?php else: ?>
                    <a href="/dang-nhap" class="btn btn-outline">Đăng nhập</a>
                    <a href="/dang-ky" class="btn btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <h1 class="page-title">Nâng cấp tài khoản VIP</h1>
            <p class="page-description">
                Trở thành thành viên VIP để tận hưởng trải nghiệm xem phim tốt nhất với chất lượng lên đến 4K, không quảng cáo và nhiều đặc quyền độc quyền khác.
            </p>
            
            <?php if (!empty(get_flash_message('error'))): ?>
            <div class="alert alert-danger">
                <?php echo get_flash_message('error'); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty(get_flash_message('success'))): ?>
            <div class="alert alert-success">
                <?php echo get_flash_message('success'); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($currentUser['is_vip'] && $currentUser['vip_expired_at']): ?>
            <div class="alert alert-success">
                Bạn đang là thành viên VIP! Thời hạn VIP của bạn sẽ hết hạn vào: <?php echo date('d/m/Y H:i', strtotime($currentUser['vip_expired_at'])); ?>
            </div>
            <?php endif; ?>
            
            <div class="vip-packages">
                <?php foreach ($packages as $package): ?>
                <div class="package-card">
                    <div class="package-header">
                        <h2 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h2>
                        <div class="package-price"><?php echo number_format($package['price'], 0, ',', '.'); ?>đ</div>
                        <div class="package-duration"><?php echo htmlspecialchars($package['duration']); ?> ngày</div>
                    </div>
                    <div class="package-body">
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> Xem phim không giới hạn</li>
                            <li><i class="fas fa-check"></i> Chất lượng video lên đến 4K</li>
                            <li><i class="fas fa-check"></i> Không quảng cáo</li>
                            <li><i class="fas fa-check"></i> Ưu tiên máy chủ phát nhanh</li>
                            <li><i class="fas fa-check"></i> Hỗ trợ đa nền tảng</li>
                        </ul>
                    </div>
                    <div class="package-footer">
                        <button class="btn btn-primary buy-btn" data-package-id="<?php echo $package['id']; ?>" data-package-price="<?php echo $package['price']; ?>">Mua ngay</button>
                        
                        <div class="payment-methods">
                            <div class="payment-method" data-method="momo">
                                <img src="/assets/images/momo-logo.png" alt="MoMo">
                            </div>
                            <div class="payment-method" data-method="vnpay">
                                <img src="/assets/images/vnpay-logo.png" alt="VNPAY">
                            </div>
                            <div class="payment-method" data-method="stripe">
                                <img src="/assets/images/stripe-logo.png" alt="Stripe">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div id="payment-form" class="payment-form">
                <h3>Xác nhận thanh toán</h3>
                <form id="momo-form" action="/thanh-toan/momo" method="POST">
                    <input type="hidden" name="package_id" id="momo-package-id">
                    <button type="submit" class="btn btn-primary">Thanh toán với MoMo</button>
                </form>
                
                <form id="vnpay-form" action="/thanh-toan/vnpay" method="POST">
                    <input type="hidden" name="package_id" id="vnpay-package-id">
                    <button type="submit" class="btn btn-primary">Thanh toán với VNPAY</button>
                </form>
                
                <form id="stripe-form" action="/thanh-toan/stripe" method="POST">
                    <input type="hidden" name="package_id" id="stripe-package-id">
                    <button type="submit" class="btn btn-primary">Thanh toán với Stripe</button>
                </form>
            </div>
            
            <div class="benefits">
                <h2 class="benefits-title">Lợi ích của thành viên VIP</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-photo-video"></i>
                        </div>
                        <h3 class="benefit-title">Chất lượng HD/4K</h3>
                        <p class="benefit-description">Trải nghiệm phim với chất lượng hình ảnh cao nhất, từ HD đến 4K tùy vào thiết bị của bạn.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <h3 class="benefit-title">Không quảng cáo</h3>
                        <p class="benefit-description">Tận hưởng trải nghiệm xem phim không bị gián đoạn bởi quảng cáo hoặc banner.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <h3 class="benefit-title">Máy chủ ưu tiên</h3>
                        <p class="benefit-description">Truy cập vào máy chủ phát nhanh, giảm thiểu thời gian chờ đợi và buffer.</p>
                    </div>
                </div>
            </div>
            
            <h2 class="benefits-title">So sánh quyền lợi</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Tính năng</th>
                        <th>Người dùng thường</th>
                        <th>Thành viên VIP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature-name">Xem phim không giới hạn</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Độ phân giải video</td>
                        <td>Tối đa 480p</td>
                        <td>Lên đến 4K</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Quảng cáo</td>
                        <td><i class="fas fa-times x"></i></td>
                        <td><i class="fas fa-check check"></i> Không quảng cáo</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Tải xuống phim</td>
                        <td><i class="fas fa-times x"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Máy chủ phát nhanh</td>
                        <td><i class="fas fa-times x"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Truy cập phim mới sớm</td>
                        <td><i class="fas fa-times x"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                </tbody>
            </table>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buyButtons = document.querySelectorAll('.buy-btn');
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentForm = document.getElementById('payment-form');
            const momoForm = document.getElementById('momo-form');
            const vnpayForm = document.getElementById('vnpay-form');
            const stripeForm = document.getElementById('stripe-form');
            
            const momoPackageIdInput = document.getElementById('momo-package-id');
            const vnpayPackageIdInput = document.getElementById('vnpay-package-id');
            const stripePackageIdInput = document.getElementById('stripe-package-id');
            
            let selectedPackageId = null;
            let selectedMethod = null;
            
            // Xử lý nút Mua ngay
            buyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    selectedPackageId = this.getAttribute('data-package-id');
                    
                    // Reset form
                    paymentMethods.forEach(method => method.classList.remove('active'));
                    selectedMethod = null;
                    
                    // Hiển thị form thanh toán
                    paymentForm.style.display = 'block';
                    
                    // Cuộn đến form thanh toán
                    paymentForm.scrollIntoView({ behavior: 'smooth' });
                    
                    // Ẩn các form thanh toán
                    momoForm.style.display = 'none';
                    vnpayForm.style.display = 'none';
                    stripeForm.style.display = 'none';
                    
                    // Cập nhật package_id cho các form
                    momoPackageIdInput.value = selectedPackageId;
                    vnpayPackageIdInput.value = selectedPackageId;
                    stripePackageIdInput.value = selectedPackageId;
                });
            });
            
            // Xử lý chọn phương thức thanh toán
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Xóa class active của tất cả phương thức
                    paymentMethods.forEach(m => m.classList.remove('active'));
                    
                    // Thêm class active cho phương thức được chọn
                    this.classList.add('active');
                    
                    // Lưu phương thức được chọn
                    selectedMethod = this.getAttribute('data-method');
                    
                    // Ẩn tất cả các form
                    momoForm.style.display = 'none';
                    vnpayForm.style.display = 'none';
                    stripeForm.style.display = 'none';
                    
                    // Hiển thị form tương ứng
                    if (selectedMethod === 'momo') {
                        momoForm.style.display = 'block';
                    } else if (selectedMethod === 'vnpay') {
                        vnpayForm.style.display = 'block';
                    } else if (selectedMethod === 'stripe') {
                        stripeForm.style.display = 'block';
                    }
                });
            });
        });
    </script>
</body>
</html>