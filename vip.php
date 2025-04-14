<?php
// Trang thành viên VIP
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!is_logged_in()) {
    // Chuyển hướng đến trang đăng nhập
    redirect('login.php?redirect=' . urlencode('vip.php'));
    exit;
}

// Lấy thông tin người dùng hiện tại
$current_user = get_current_user();

// Kiểm tra trạng thái VIP của người dùng
$vip_status = check_vip_status($current_user['id']);

// Lấy danh sách gói VIP từ cấu hình
$vip_config = get_config('vip');
$vip_packages = isset($vip_config['levels']) ? $vip_config['levels'] : [];

// Xử lý giao dịch trả về từ cổng thanh toán
$payment_status = null;
if (isset($_GET['vnp_ResponseCode']) || isset($_GET['resultCode'])) {
    require_once 'api/payment.php';
    
    if (isset($_GET['vnp_ResponseCode'])) {
        $payment_result = process_vnpay_return($_GET);
    } else {
        $payment_result = process_momo_return($_GET);
    }
    
    $payment_status = $payment_result;
}

// Thêm tiêu đề trang
$page_title = 'Thành Viên VIP - Lọc Phim';
$page_description = 'Nâng cấp tài khoản VIP để xem phim chất lượng cao không quảng cáo';

// Tải header
include 'header.php';
?>

<div class="container vip-page my-5">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="vip-header text-center mb-5">
                <h1 class="display-4">Thành Viên VIP</h1>
                <p class="lead text-muted">Nâng cấp tài khoản để có trải nghiệm xem phim tốt nhất</p>
            </div>
            
            <?php if ($payment_status): ?>
            <div class="alert <?php echo $payment_status['success'] ? 'alert-success' : 'alert-danger'; ?> mb-4">
                <h5><?php echo $payment_status['success'] ? 'Thanh toán thành công!' : 'Thanh toán thất bại!'; ?></h5>
                <p><?php echo $payment_status['message']; ?></p>
                <?php if ($payment_status['success']): ?>
                <p>Mã giao dịch: <?php echo $payment_status['transaction_id']; ?></p>
                <p>Số tiền: <?php echo number_format($payment_status['amount'], 0, ',', '.'); ?> VNĐ</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($vip_status): ?>
            <div class="card mb-5 vip-status-card">
                <div class="card-body text-center">
                    <div class="vip-badge mb-3">
                        <i class="fas fa-crown fa-3x text-warning"></i>
                    </div>
                    <h3>Bạn đang là thành viên VIP!</h3>
                    <p class="lead">Gói: <strong><?php echo $vip_config['levels'][$vip_status['level']]['name'] ?? 'VIP ' . $vip_status['level']; ?></strong></p>
                    <div class="row text-center mt-4">
                        <div class="col-md-4">
                            <div class="vip-stat p-3">
                                <i class="fas fa-calendar-alt mb-2"></i>
                                <h5>Ngày hết hạn</h5>
                                <p><?php echo date('d/m/Y', strtotime($vip_status['expire_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vip-stat p-3">
                                <i class="fas fa-video mb-2"></i>
                                <h5>Chất lượng video</h5>
                                <p><?php echo $vip_config['levels'][$vip_status['level']]['resolution'] ?? '1080p'; ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vip-stat p-3">
                                <i class="fas fa-ban mb-2"></i>
                                <h5>Quảng cáo</h5>
                                <p><?php echo $vip_status['ads'] ? 'Có' : 'Không'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <p class="text-center mb-0">Còn <strong><?php echo floor((strtotime($vip_status['expire_date']) - time()) / 86400); ?></strong> ngày sử dụng</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="vip-benefits mb-5">
                <h3 class="text-center mb-4">Đặc quyền thành viên VIP</h3>
                <div class="row text-center">
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-ban fa-2x mb-2 text-danger"></i>
                            <h5>Không quảng cáo</h5>
                            <p>Xem phim không bị gián đoạn bởi quảng cáo</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-film fa-2x mb-2 text-primary"></i>
                            <h5>Chất lượng cao</h5>
                            <p>Xem phim với chất lượng lên đến 4K</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-download fa-2x mb-2 text-success"></i>
                            <h5>Tải phim</h5>
                            <p>Tải phim để xem offline</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-history fa-2x mb-2 text-info"></i>
                            <h5>Xem sớm</h5>
                            <p>Xem các tập mới trước người dùng thường</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-comments fa-2x mb-2 text-warning"></i>
                            <h5>Hỗ trợ ưu tiên</h5>
                            <p>Được hỗ trợ ưu tiên khi cần giúp đỡ</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="benefit-item p-3">
                            <i class="fas fa-cog fa-2x mb-2 text-secondary"></i>
                            <h5>Tùy chỉnh</h5>
                            <p>Tùy chỉnh giao diện theo ý thích</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="vip-packages">
                <h3 class="text-center mb-4">Chọn gói VIP phù hợp</h3>
                <div class="row">
                    <?php foreach ($vip_packages as $level => $package): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 <?php echo $level == 2 ? 'border-primary popular-package' : ''; ?>">
                            <?php if ($level == 2): ?>
                            <div class="ribbon">
                                <span>Phổ biến</span>
                            </div>
                            <?php endif; ?>
                            <div class="card-header text-center">
                                <h4 class="my-0 font-weight-bold"><?php echo $package['name']; ?></h4>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h1 class="card-title pricing-card-title text-center">
                                    <?php echo number_format($package['price'], 0, ',', '.'); ?> <small class="text-muted">VND</small>
                                </h1>
                                <p class="text-center text-muted mb-4"><?php echo $package['duration']; ?> ngày</p>
                                <ul class="list-unstyled mt-3 mb-4">
                                    <li><i class="fas fa-check text-success mr-2"></i> Độ phân giải <?php echo $package['resolution']; ?></li>
                                    <li><i class="fas <?php echo $package['ads'] ? 'fa-times text-danger' : 'fa-check text-success'; ?> mr-2"></i> <?php echo $package['ads'] ? 'Có quảng cáo' : 'Không quảng cáo'; ?></li>
                                    <li><i class="fas fa-check text-success mr-2"></i> Hỗ trợ tải phim</li>
                                    <li><i class="fas fa-check text-success mr-2"></i> Xem trước phim mới</li>
                                </ul>
                                <div class="mt-auto">
                                    <form id="payment-form-<?php echo $level; ?>" action="javascript:void(0);" class="payment-form" method="post">
                                        <input type="hidden" name="vip_level" value="<?php echo $level; ?>">
                                        <input type="hidden" name="vip_duration" value="<?php echo $package['duration']; ?>">
                                        
                                        <div class="form-group">
                                            <select name="payment_method" class="form-control mb-3">
                                                <option value="vnpay">Thanh toán qua VNPay</option>
                                                <option value="momo">Thanh toán qua MoMo</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-lg btn-block <?php echo $level == 2 ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            Nâng cấp ngay
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="vip-faq mt-5">
                <h3 class="text-center mb-4">Câu hỏi thường gặp</h3>
                
                <div class="accordion" id="vipFAQ">
                    <div class="card">
                        <div class="card-header" id="headingOne">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Làm thế nào để trở thành VIP?
                                </button>
                            </h2>
                        </div>
                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#vipFAQ">
                            <div class="card-body">
                                Để trở thành thành viên VIP, bạn chỉ cần chọn gói VIP phù hợp và thanh toán qua VNPay hoặc MoMo. Tài khoản của bạn sẽ được nâng cấp ngay lập tức sau khi thanh toán thành công.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="headingTwo">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Tôi có thể hủy gói VIP không?
                                </button>
                            </h2>
                        </div>
                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#vipFAQ">
                            <div class="card-body">
                                Các gói VIP của chúng tôi là thanh toán một lần và có thời hạn cố định. Bạn không cần phải hủy gói VIP vì nó sẽ tự động hết hạn sau khi kết thúc thời gian sử dụng.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="headingThree">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Làm thế nào để nâng cấp gói VIP hiện tại?
                                </button>
                            </h2>
                        </div>
                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#vipFAQ">
                            <div class="card-body">
                                Nếu bạn đã là thành viên VIP, bạn có thể nâng cấp lên gói cao hơn bất cứ lúc nào. Khi nâng cấp, thời hạn VIP của bạn sẽ được cộng thêm vào thời hạn hiện tại.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="headingFour">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Các phương thức thanh toán được chấp nhận?
                                </button>
                            </h2>
                        </div>
                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#vipFAQ">
                            <div class="card-body">
                                Hiện tại, chúng tôi chấp nhận thanh toán qua VNPay (hỗ trợ nhiều ngân hàng và thẻ tín dụng) và MoMo (ví điện tử).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý form thanh toán
    const paymentForms = document.querySelectorAll('.payment-form');
    
    paymentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('user_id', '<?php echo $current_user['id']; ?>');
            
            // Gửi request tạo thanh toán
            fetch('api/payment.php?action=create_payment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Chuyển hướng đến trang thanh toán
                    window.location.href = data.payment_url;
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi xử lý thanh toán. Vui lòng thử lại sau.');
            });
        });
    });
});
</script>

<style>
.vip-page {
    padding-bottom: 50px;
}

.vip-status-card {
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.vip-badge {
    display: inline-block;
    padding: 15px;
    border-radius: 50%;
    background-color: #fff9e6;
}

.vip-stat {
    background-color: #f8f9fa;
    border-radius: 10px;
    height: 100%;
}

.benefit-item {
    background-color: #f8f9fa;
    border-radius: 10px;
    height: 100%;
    transition: all 0.3s ease;
}

.benefit-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.popular-package {
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(0,123,255,0.2);
    position: relative;
    z-index: 1;
}

.ribbon {
    width: 150px;
    height: 150px;
    overflow: hidden;
    position: absolute;
    top: -10px;
    right: -10px;
    z-index: 1;
}

.ribbon span {
    position: absolute;
    display: block;
    width: 225px;
    padding: 8px 0;
    background-color: #007bff;
    box-shadow: 0 5px 10px rgba(0,0,0,.1);
    color: #fff;
    font-size: 13px;
    text-transform: uppercase;
    text-align: center;
    transform: rotate(45deg);
    right: -25px;
    top: 30px;
}
</style>

<?php include 'footer.php'; ?>