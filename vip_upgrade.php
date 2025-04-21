<?php
// Định nghĩa URL trang chủ
define('SITE_URL', 'https://localhost');

// Bao gồm các file cần thiết
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Lấy thông tin người dùng hiện tại
$current_user = get_logged_in_user();

// Kiểm tra nếu người dùng đã là VIP
$is_vip = ($current_user['role_id'] == USER_ROLE_VIP);
$vip_expiry_date = $is_vip ? $current_user['vip_expiry'] : null;

// Lấy danh sách gói VIP
$vip_packages = db_fetch_all("SELECT * FROM vip_packages WHERE status = 1 ORDER BY duration ASC");

// Lấy cấu hình thanh toán
$vnpay_tmn_code = VNPAY_TMN_CODE ?: get_setting('vnpay_tmn_code');
$vnpay_hash_secret = VNPAY_HASH_SECRET ?: get_setting('vnpay_hash_secret');
$momo_partner_code = MOMO_PARTNER_CODE ?: get_setting('momo_partner_code');
$momo_access_key = MOMO_ACCESS_KEY ?: get_setting('momo_access_key');

// Biến thông báo
$error = '';
$success = '';

// Xử lý thông báo thanh toán (nếu có)
if (isset($_GET['result']) && $_GET['result'] == 'success') {
    $success = 'Thanh toán thành công! Tài khoản của bạn đã được nâng cấp lên VIP.';
} elseif (isset($_GET['result']) && $_GET['result'] == 'cancel') {
    $error = 'Thanh toán đã bị hủy hoặc không thành công.';
}

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="vip-upgrade-page">
    <div class="container">
        <div class="vip-header">
            <h1>Nâng cấp tài khoản VIP</h1>
            <p>Trải nghiệm xem phim không giới hạn với đặc quyền thành viên VIP</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <!-- Trạng thái VIP -->
        <div class="vip-status">
            <?php if ($is_vip): ?>
            <div class="vip-status-active">
                <div class="vip-badge">
                    <i class="fas fa-crown"></i> VIP
                </div>
                <div class="vip-info">
                    <h3>Bạn đang là thành viên VIP</h3>
                    <p>Thời hạn VIP: đến ngày <?php echo date('d/m/Y', strtotime($vip_expiry_date)); ?></p>
                    <p>Bạn có thể gia hạn thêm thời gian VIP bằng cách mua thêm gói VIP bên dưới.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="vip-status-inactive">
                <div class="vip-badge inactive">
                    <i class="fas fa-user"></i>
                </div>
                <div class="vip-info">
                    <h3>Bạn chưa là thành viên VIP</h3>
                    <p>Nâng cấp ngay để trải nghiệm đầy đủ các tính năng độc quyền!</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Đặc quyền VIP -->
        <div class="vip-benefits">
            <h2>Đặc quyền thành viên VIP</h2>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Xem phim không quảng cáo</h3>
                        <p>Tận hưởng trải nghiệm xem phim liền mạch không bị gián đoạn bởi quảng cáo</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Chất lượng video cao nhất</h3>
                        <p>Truy cập nội dung ở độ phân giải lên đến 4K (nếu có)</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Tải xuống để xem offline</h3>
                        <p>Tải xuống phim và xem mọi lúc, mọi nơi mà không cần kết nối internet</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Xem sớm nội dung mới</h3>
                        <p>Được ưu tiên xem các phim và tập mới nhất trước người dùng thường</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gói VIP -->
        <div class="vip-packages">
            <h2>Chọn gói VIP phù hợp với bạn</h2>
            
            <?php if (empty($vip_packages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Hiện tại chưa có gói VIP nào. Vui lòng quay lại sau.
            </div>
            <?php else: ?>
            <div class="packages-grid">
                <?php foreach ($vip_packages as $package): ?>
                <div class="package-item <?php echo $package['is_popular'] ? 'popular' : ''; ?>">
                    <?php if ($package['is_popular']): ?>
                    <div class="package-popular-badge">Phổ biến nhất</div>
                    <?php endif; ?>
                    
                    <div class="package-header">
                        <h3 class="package-name"><?php echo $package['name']; ?></h3>
                        <div class="package-price">
                            <span class="price-amount"><?php echo number_format($package['price'], 0, ',', '.'); ?> đ</span>
                            <span class="price-period">/<?php echo $package['duration']; ?> tháng</span>
                        </div>
                        <?php if ($package['original_price'] > $package['price']): ?>
                        <div class="package-discount">
                            Tiết kiệm <?php echo number_format(($package['original_price'] - $package['price']) / $package['original_price'] * 100); ?>%
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="package-features">
                        <ul>
                            <li><i class="fas fa-check"></i> Xem phim không quảng cáo</li>
                            <li><i class="fas fa-check"></i> Chất lượng video lên đến 4K</li>
                            <li><i class="fas fa-check"></i> Tải xuống để xem offline</li>
                            <li><i class="fas fa-check"></i> Xem sớm nội dung mới</li>
                            <?php if ($package['duration'] >= 6): ?>
                            <li><i class="fas fa-check"></i> Hỗ trợ đa thiết bị</li>
                            <?php endif; ?>
                            <?php if ($package['duration'] >= 12): ?>
                            <li><i class="fas fa-check"></i> Ưu đãi đặc biệt</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="package-footer">
                        <form action="payment.php" method="post">
                            <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                            <button type="submit" class="btn <?php echo $package['is_popular'] ? 'btn-warning' : 'btn-outline-primary'; ?> w-100">
                                Chọn gói này
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Phương thức thanh toán -->
        <div class="payment-methods">
            <h2>Phương thức thanh toán</h2>
            <div class="payment-icons">
                <div class="payment-icon">
                    <img src="assets/images/payment/vnpay.png" alt="VNPAY">
                    <span>VNPAY</span>
                </div>
                <div class="payment-icon">
                    <img src="assets/images/payment/momo.png" alt="MoMo">
                    <span>MoMo</span>
                </div>
                <div class="payment-icon">
                    <img src="assets/images/payment/visa.png" alt="Visa">
                    <span>Visa</span>
                </div>
                <div class="payment-icon">
                    <img src="assets/images/payment/mastercard.png" alt="Mastercard">
                    <span>Mastercard</span>
                </div>
            </div>
        </div>
        
        <!-- Câu hỏi thường gặp -->
        <div class="vip-faq">
            <h2>Câu hỏi thường gặp</h2>
            <div class="accordion" id="vipFaqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                            VIP có những đặc quyền gì?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#vipFaqAccordion">
                        <div class="accordion-body">
                            Thành viên VIP được xem phim không quảng cáo, truy cập nội dung ở chất lượng cao nhất (lên đến 4K), tải xuống phim để xem offline, và được ưu tiên xem các nội dung mới nhất.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                            Làm thế nào để thanh toán?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#vipFaqAccordion">
                        <div class="accordion-body">
                            Chúng tôi chấp nhận nhiều phương thức thanh toán bao gồm VNPAY, MoMo, thẻ tín dụng/ghi nợ (Visa, Mastercard). Sau khi chọn gói VIP, bạn sẽ được chuyển đến trang thanh toán để hoàn tất giao dịch.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                            Tôi có thể hủy đăng ký VIP không?
                        </button>
                    </h2>
                    <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#vipFaqAccordion">
                        <div class="accordion-body">
                            Hiện tại chúng tôi không hỗ trợ hủy đăng ký giữa chừng. Khi bạn mua gói VIP, bạn sẽ có quyền truy cập VIP cho đến khi hết hạn gói. Bạn có thể quyết định có gia hạn hay không khi gói VIP của bạn sắp hết hạn.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                            Tôi có thể xem VIP trên nhiều thiết bị không?
                        </button>
                    </h2>
                    <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#vipFaqAccordion">
                        <div class="accordion-body">
                            Có, bạn có thể đăng nhập và sử dụng tài khoản VIP trên nhiều thiết bị khác nhau. Tuy nhiên, số lượng thiết bị có thể xem cùng lúc sẽ phụ thuộc vào gói VIP bạn đăng ký.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading5">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                            Làm thế nào để gia hạn VIP?
                        </button>
                    </h2>
                    <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#vipFaqAccordion">
                        <div class="accordion-body">
                            Bạn có thể gia hạn VIP bất kỳ lúc nào bằng cách mua thêm gói VIP mới. Thời hạn VIP mới sẽ được cộng vào thời hạn hiện tại của bạn.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
