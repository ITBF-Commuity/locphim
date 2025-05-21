<?php
/**
 * Lọc Phim - Trang VIP
 */

// Set title và description cho trang
$pageTitle = 'Gói VIP - ' . SITE_NAME;
$pageDescription = 'Trở thành thành viên VIP để xem phim chất lượng cao, không quảng cáo và nhiều đặc quyền khác.';

// Cấu hình các gói VIP
$vipPlans = [
    [
        'id' => 1,
        'name' => 'VIP 1 Tháng',
        'description' => 'Gói VIP 1 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 49000,
        'duration' => 30, // ngày
        'popular' => false,
    ],
    [
        'id' => 2,
        'name' => 'VIP 3 Tháng',
        'description' => 'Gói VIP 3 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 129000,
        'duration' => 90, // ngày
        'popular' => true,
    ],
    [
        'id' => 3,
        'name' => 'VIP 6 Tháng',
        'description' => 'Gói VIP 6 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 239000,
        'duration' => 180, // ngày
        'popular' => false,
    ],
    [
        'id' => 4,
        'name' => 'VIP 1 Năm',
        'description' => 'Gói VIP 1 năm với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 429000,
        'duration' => 365, // ngày
        'popular' => false,
    ],
];

// Kiểm tra nếu người dùng đã là VIP
$isVip = isset($_SESSION['is_vip']) && $_SESSION['is_vip'] === true;
$vipExpiresAt = null;

if ($isVip && isset($_SESSION['user_id'])) {
    $user = $db->get("SELECT vip_expires_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if ($user && !empty($user['vip_expires_at'])) {
        $vipExpiresAt = $user['vip_expires_at'];
    }
}

// Bắt đầu output buffering
ob_start();
?>

<div class="vip-page">
    <div class="vip-intro">
        <h1>Trải Nghiệm VIP Tại <?php echo SITE_NAME; ?></h1>
        <p>Nâng cấp tài khoản VIP ngay hôm nay để tận hưởng những đặc quyền tuyệt vời, xem phim với chất lượng cao nhất và không bị gián đoạn bởi quảng cáo.</p>
    </div>
    
    <?php if ($isVip): ?>
        <div class="alert alert-success">
            <h3>Bạn hiện là thành viên VIP!</h3>
            <p>Gói VIP của bạn sẽ hết hạn vào: <strong><?php echo date('d/m/Y H:i', strtotime($vipExpiresAt)); ?></strong></p>
            <p>Bạn có thể gia hạn gói VIP bất kỳ lúc nào để tiếp tục tận hưởng đặc quyền.</p>
        </div>
    <?php endif; ?>
    
    <div class="vip-features">
        <div class="vip-feature">
            <i class="fas fa-film"></i>
            <h3>Chất Lượng 4K</h3>
            <p>Xem phim và anime với chất lượng cao nhất lên đến 4K UHD.</p>
        </div>
        
        <div class="vip-feature">
            <i class="fas fa-ban"></i>
            <h3>Không Quảng Cáo</h3>
            <p>Trải nghiệm xem phim liền mạch không bị gián đoạn bởi quảng cáo.</p>
        </div>
        
        <div class="vip-feature">
            <i class="fas fa-download"></i>
            <h3>Tải Phim Về Máy</h3>
            <p>Tải phim, anime yêu thích về máy để xem offline bất kỳ lúc nào.</p>
        </div>
        
        <div class="vip-feature">
            <i class="fas fa-bolt"></i>
            <h3>Xem Sớm</h3>
            <p>Được xem các tập phim, anime mới nhất sớm hơn người dùng thường.</p>
        </div>
        
        <div class="vip-feature">
            <i class="fas fa-server"></i>
            <h3>Đa Máy Chủ</h3>
            <p>Truy cập vào các máy chủ dự phòng với tốc độ cao và ổn định.</p>
        </div>
        
        <div class="vip-feature">
            <i class="fas fa-headset"></i>
            <h3>Hỗ Trợ Ưu Tiên</h3>
            <p>Nhận hỗ trợ kỹ thuật ưu tiên từ đội ngũ chăm sóc khách hàng.</p>
        </div>
    </div>
    
    <div class="vip-plans">
        <?php foreach ($vipPlans as $plan): ?>
            <div class="vip-plan <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                <div class="duration"><?php echo ($plan['duration'] >= 30) ? floor($plan['duration'] / 30) . ' tháng' : $plan['duration'] . ' ngày'; ?></div>
                <div class="price">
                    <?php echo number_format($plan['price'], 0, ',', '.'); ?> ₫
                    <?php if ($plan['duration'] > 30): ?>
                        <small>
                            <?php echo number_format($plan['price'] / ($plan['duration'] / 30), 0, ',', '.'); ?> ₫/tháng
                        </small>
                    <?php endif; ?>
                </div>
                <ul class="features">
                    <li><i class="fas fa-check"></i> Chất lượng lên đến 4K</li>
                    <li><i class="fas fa-check"></i> Không quảng cáo</li>
                    <li><i class="fas fa-check"></i> Tải phim về máy</li>
                    <li><i class="fas fa-check"></i> Xem sớm nội dung mới</li>
                    <li><i class="fas fa-check"></i> Đa máy chủ xem phim</li>
                    <li><i class="fas fa-check"></i> Hỗ trợ ưu tiên</li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo url('thanh-toan?plan=' . $plan['id']); ?>" class="btn btn-primary">
                        <?php echo $isVip ? 'Gia Hạn Ngay' : 'Đăng Ký Ngay'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('dang-nhap?redirect=' . urlencode(url('vip'))); ?>" class="btn btn-primary">
                        Đăng Nhập Để Đăng Ký
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="vip-payment">
        <h2>Phương Thức Thanh Toán An Toàn</h2>
        <p>Chúng tôi hỗ trợ nhiều phương thức thanh toán an toàn và bảo mật</p>
        
        <div class="payment-methods">
            <div class="payment-method">
                <img src="<?php echo url('assets/images/payment/visa.svg'); ?>" alt="Visa">
            </div>
            <div class="payment-method">
                <img src="<?php echo url('assets/images/payment/mastercard.svg'); ?>" alt="Mastercard">
            </div>
            <div class="payment-method">
                <img src="<?php echo url('assets/images/payment/vnpay.svg'); ?>" alt="VNPay">
            </div>
            <div class="payment-method">
                <img src="<?php echo url('assets/images/payment/momo.svg'); ?>" alt="MoMo">
            </div>
            <div class="payment-method">
                <img src="<?php echo url('assets/images/payment/zalopay.svg'); ?>" alt="ZaloPay">
            </div>
        </div>
    </div>
    
    <div class="vip-faq">
        <h2>Câu Hỏi Thường Gặp</h2>
        
        <div class="faq-item">
            <h3>VIP có những ưu đãi gì?</h3>
            <p>Thành viên VIP của <?php echo SITE_NAME; ?> sẽ được xem phim với chất lượng cao nhất (lên đến 4K), không bị gián đoạn bởi quảng cáo, có thể tải phim về máy để xem offline, được xem các tập phim mới sớm hơn, truy cập các máy chủ dự phòng với tốc độ cao và nhận được hỗ trợ kỹ thuật ưu tiên.</p>
        </div>
        
        <div class="faq-item">
            <h3>Tôi có thể đăng ký VIP như thế nào?</h3>
            <p>Để đăng ký VIP, bạn cần đăng nhập vào tài khoản của mình, chọn gói VIP phù hợp và tiến hành thanh toán. Sau khi thanh toán thành công, tài khoản của bạn sẽ được nâng cấp lên VIP ngay lập tức.</p>
        </div>
        
        <div class="faq-item">
            <h3>Tôi có thể sử dụng những phương thức thanh toán nào?</h3>
            <p>Chúng tôi hỗ trợ nhiều phương thức thanh toán bao gồm: Thẻ tín dụng/ghi nợ quốc tế (Visa, Mastercard), VNPay, MoMo, ZaloPay và các phương thức thanh toán phổ biến khác tại Việt Nam.</p>
        </div>
        
        <div class="faq-item">
            <h3>Gói VIP có tự động gia hạn không?</h3>
            <p>Không, các gói VIP của chúng tôi không tự động gia hạn. Bạn sẽ nhận được thông báo trước khi gói VIP của mình hết hạn và có thể chủ động gia hạn nếu muốn.</p>
        </div>
        
        <div class="faq-item">
            <h3>Tôi có thể yêu cầu hoàn tiền không?</h3>
            <p>Chúng tôi không hỗ trợ hoàn tiền cho các gói VIP đã kích hoạt. Vui lòng cân nhắc kỹ trước khi đăng ký.</p>
        </div>
        
        <div class="faq-item">
            <h3>Tôi có thể xem phim trên những thiết bị nào?</h3>
            <p>Bạn có thể xem phim trên tất cả các thiết bị có kết nối internet và trình duyệt web, bao gồm máy tính, điện thoại, máy tính bảng và smart TV. Bạn cũng có thể tải ứng dụng của chúng tôi trên iOS và Android.</p>
        </div>
    </div>
</div>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>