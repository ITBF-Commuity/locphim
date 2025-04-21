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

// Biến thông báo
$error = '';
$success = '';

// Kiểm tra tham số gói VIP
if (!isset($_POST['package_id']) && !isset($_GET['package_id'])) {
    header('Location: vip_upgrade.php');
    exit();
}

// Lấy ID gói VIP
$package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : intval($_GET['package_id']);

// Lấy thông tin gói VIP
$package = db_fetch_row("SELECT * FROM vip_packages WHERE id = ? AND status = 1", array($package_id));

if (!$package) {
    header('Location: vip_upgrade.php');
    exit();
}

// Xử lý chọn phương thức thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
    
    // Tạo mã giao dịch
    $transaction_code = 'TX' . time() . rand(1000, 9999);
    
    // Lưu thông tin giao dịch vào database
    $transaction_id = db_insert(
        "INSERT INTO vip_transactions (user_id, package_id, transaction_code, amount, payment_method, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())",
        array($current_user['id'], $package['id'], $transaction_code, $package['price'], $payment_method, 'pending')
    );
    
    // Xử lý thanh toán theo phương thức
    switch ($payment_method) {
        case 'vnpay':
            // Chuyển hướng đến cổng thanh toán VNPAY
            header('Location: payment_gateways/vnpay_checkout.php?transaction_id=' . $transaction_id);
            exit();
            
        case 'momo':
            // Chuyển hướng đến cổng thanh toán MoMo
            header('Location: payment_gateways/momo_checkout.php?transaction_id=' . $transaction_id);
            exit();
            
        case 'bank_transfer':
            // Chuyển hướng đến trang hướng dẫn chuyển khoản
            header('Location: payment_gateways/bank_transfer.php?transaction_id=' . $transaction_id);
            exit();
            
        default:
            $error = 'Phương thức thanh toán không hợp lệ';
    }
}

// Xử lý kết quả thanh toán (callback từ cổng thanh toán)
if (isset($_GET['result'])) {
    $result = $_GET['result'];
    $transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
    
    // Kiểm tra thông tin giao dịch
    $transaction = db_fetch_row("SELECT * FROM vip_transactions WHERE id = ?", array($transaction_id));
    
    if ($transaction && $transaction['user_id'] == $current_user['id']) {
        if ($result === 'success') {
            // Cập nhật trạng thái giao dịch
            db_query(
                "UPDATE vip_transactions SET status = ?, updated_at = NOW() WHERE id = ?",
                array('completed', $transaction_id)
            );
            
            // Lấy thông tin gói VIP
            $package = db_fetch_row("SELECT * FROM vip_packages WHERE id = ?", array($transaction['package_id']));
            
            // Nâng cấp tài khoản lên VIP
            upgrade_to_vip($current_user['id'], $package['duration']);
            
            $success = 'Thanh toán thành công! Tài khoản của bạn đã được nâng cấp lên VIP.';
        } else {
            // Cập nhật trạng thái giao dịch
            db_query(
                "UPDATE vip_transactions SET status = ?, updated_at = NOW() WHERE id = ?",
                array('failed', $transaction_id)
            );
            
            $error = 'Thanh toán không thành công. Vui lòng thử lại sau.';
        }
    }
}

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="payment-page">
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2>Thanh toán</h2>
                <p>Hoàn tất thanh toán để nâng cấp tài khoản VIP</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
                <p><a href="vip_upgrade.php" class="btn btn-outline-primary mt-2">Quay lại</a></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p><a href="user_profile.php" class="btn btn-primary mt-2">Xem tài khoản</a></p>
            </div>
            <?php else: ?>
            
            <div class="payment-content">
                <div class="payment-summary">
                    <h3>Thông tin đơn hàng</h3>
                    <div class="package-info">
                        <div class="package-name"><?php echo $package['name']; ?></div>
                        <div class="package-duration"><?php echo $package['duration']; ?> tháng</div>
                    </div>
                    
                    <div class="price-details">
                        <div class="price-item">
                            <span class="price-label">Giá gói</span>
                            <span class="price-value"><?php echo number_format($package['original_price'], 0, ',', '.'); ?> đ</span>
                        </div>
                        
                        <?php if ($package['original_price'] > $package['price']): ?>
                        <div class="price-item discount">
                            <span class="price-label">Giảm giá</span>
                            <span class="price-value">-<?php echo number_format($package['original_price'] - $package['price'], 0, ',', '.'); ?> đ</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="price-total">
                            <span class="price-label">Tổng thanh toán</span>
                            <span class="price-value"><?php echo number_format($package['price'], 0, ',', '.'); ?> đ</span>
                        </div>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3>Chọn phương thức thanh toán</h3>
                    <form method="post" action="">
                        <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                        
                        <div class="payment-method-options">
                            <div class="payment-method-item">
                                <input type="radio" class="form-check-input" id="method_vnpay" name="payment_method" value="vnpay" checked>
                                <label class="form-check-label" for="method_vnpay">
                                    <img src="assets/images/payment/vnpay.png" alt="VNPAY">
                                    <span>VNPAY</span>
                                </label>
                            </div>
                            
                            <div class="payment-method-item">
                                <input type="radio" class="form-check-input" id="method_momo" name="payment_method" value="momo">
                                <label class="form-check-label" for="method_momo">
                                    <img src="assets/images/payment/momo.png" alt="MoMo">
                                    <span>MoMo</span>
                                </label>
                            </div>
                            
                            <div class="payment-method-item">
                                <input type="radio" class="form-check-input" id="method_bank" name="payment_method" value="bank_transfer">
                                <label class="form-check-label" for="method_bank">
                                    <img src="assets/images/payment/bank.png" alt="Chuyển khoản ngân hàng">
                                    <span>Chuyển khoản ngân hàng</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="payment-actions">
                            <a href="vip_upgrade.php" class="btn btn-outline-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-primary">Tiếp tục thanh toán</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="payment-security">
                <div class="security-icons">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-shield-alt"></i>
                </div>
                <p>Giao dịch được bảo mật bởi các cổng thanh toán uy tín.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
