<?php
/**
 * Lọc Phim - Trang thanh toán
 */

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = url('thanh-toan' . (isset($_GET['plan']) ? '?plan=' . $_GET['plan'] : ''));
    header('Location: ' . url('dang-nhap'));
    exit;
}

// Lấy thông tin người dùng
$user = $db->get("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$user) {
    $_SESSION['error'] = 'Không tìm thấy thông tin tài khoản, vui lòng đăng nhập lại.';
    header('Location: ' . url('dang-nhap'));
    exit;
}

// Cấu hình các gói VIP
$vipPlans = [
    1 => [
        'id' => 1,
        'name' => 'VIP 1 Tháng',
        'description' => 'Gói VIP 1 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 49000,
        'duration' => 30, // ngày
    ],
    2 => [
        'id' => 2,
        'name' => 'VIP 3 Tháng',
        'description' => 'Gói VIP 3 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 129000,
        'duration' => 90, // ngày
        'discount' => 12, // % giảm giá so với mua lẻ
    ],
    3 => [
        'id' => 3,
        'name' => 'VIP 6 Tháng',
        'description' => 'Gói VIP 6 tháng với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 239000,
        'duration' => 180, // ngày
        'discount' => 19, // % giảm giá so với mua lẻ
    ],
    4 => [
        'id' => 4,
        'name' => 'VIP 1 Năm',
        'description' => 'Gói VIP 1 năm với đầy đủ tính năng, xem phim không quảng cáo, chất lượng cao nhất.',
        'price' => 429000,
        'duration' => 365, // ngày
        'discount' => 27, // % giảm giá so với mua lẻ
    ],
];

// Xác định gói VIP được chọn
$selectedPlanId = isset($_GET['plan']) ? (int)$_GET['plan'] : 1;
if (!isset($vipPlans[$selectedPlanId])) {
    $selectedPlanId = 1;
}
$selectedPlan = $vipPlans[$selectedPlanId];

// Xử lý form thanh toán
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    if (empty($paymentMethod)) {
        $error = 'Vui lòng chọn phương thức thanh toán';
    } else {
        // Tạo giao dịch trong database
        $transactionId = 'TX' . time() . rand(1000, 9999);
        $createdAt = date('Y-m-d H:i:s');
        
        $db->execute("INSERT INTO transactions (id, user_id, plan_id, amount, status, payment_method, created_at) 
                      VALUES (?, ?, ?, ?, 'pending', ?, ?)", 
                      [$transactionId, $user['id'], $selectedPlan['id'], $selectedPlan['price'], $paymentMethod, $createdAt]);
        
        // Chuyển hướng tới trang thanh toán tương ứng
        switch ($paymentMethod) {
            case 'vnpay':
                // Chuyển hướng sang thanh toán VNPay
                create_vnpay_payment($transactionId, $selectedPlan['price'], $selectedPlan['name']);
                break;
                
            case 'momo':
                // Chuyển hướng sang thanh toán MoMo
                create_momo_payment($transactionId, $selectedPlan['price'], $selectedPlan['name']);
                break;
                
            case 'stripe':
                // Chuyển hướng sang thanh toán Stripe
                header('Location: ' . url('stripe-payment?tx=' . $transactionId));
                exit;
                break;
                
            default:
                $error = 'Phương thức thanh toán không hợp lệ';
                break;
        }
    }
}

// Hàm tạo thanh toán VNPay
function create_vnpay_payment($transactionId, $amount, $planName) {
    // Thông tin từ config
    $vnp_TmnCode = VNPAY_TERMINAL_ID;
    $vnp_HashSecret = VNPAY_SECRET_KEY;
    $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
    $vnp_Returnurl = SITE_URL . '/payment-callback/vnpay';
    
    $vnp_TxnRef = $transactionId;
    $vnp_OrderInfo = "Thanh toán gói " . $planName . " - " . SITE_NAME;
    $vnp_OrderType = "billpayment";
    $vnp_Amount = $amount * 100;
    $vnp_Locale = "vn";
    $vnp_BankCode = "";
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    
    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
    );
    
    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }
    
    $vnp_Url = $vnp_Url . "?" . $query;
    
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }
    
    header('Location: ' . $vnp_Url);
    exit;
}

// Hàm tạo thanh toán MoMo
function create_momo_payment($transactionId, $amount, $planName) {
    // Thông tin từ config
    $endpoint = "https://test-payment.momo.vn/gw_payment/transactionProcessor";
    $partnerCode = MOMO_PARTNER_CODE;
    $accessKey = MOMO_ACCESS_KEY;
    $secretKey = MOMO_SECRET_KEY;
    $returnUrl = SITE_URL . '/payment-callback/momo';
    $notifyUrl = SITE_URL . '/payment-callback/momo-ipn';
    
    $orderId = $transactionId;
    $orderInfo = "Thanh toán gói " . $planName . " - " . SITE_NAME;
    $amount = (string)$amount;
    $requestId = $transactionId;
    
    $rawHash = "partnerCode=" . $partnerCode .
               "&accessKey=" . $accessKey .
               "&requestId=" . $requestId .
               "&amount=" . $amount .
               "&orderId=" . $orderId .
               "&orderInfo=" . $orderInfo .
               "&returnUrl=" . $returnUrl .
               "&notifyUrl=" . $notifyUrl .
               "&extraData=";
    
    $signature = hash_hmac("sha256", $rawHash, $secretKey);
    
    $data = array(
        'partnerCode' => $partnerCode,
        'accessKey' => $accessKey,
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'returnUrl' => $returnUrl,
        'notifyUrl' => $notifyUrl,
        'extraData' => '',
        'requestType' => 'captureMoMoWallet',
        'signature' => $signature
    );
    
    $result = execPostRequest($endpoint, json_encode($data));
    $jsonResult = json_decode($result, true);
    
    if (isset($jsonResult['payUrl']) && !empty($jsonResult['payUrl'])) {
        header('Location: ' . $jsonResult['payUrl']);
        exit;
    } else {
        // Xử lý lỗi
        $_SESSION['error'] = 'Có lỗi xảy ra khi kết nối với MoMo. Vui lòng thử lại sau.';
        header('Location: ' . url('thanh-toan?plan=' . $selectedPlan['id']));
        exit;
    }
}

// Hàm gửi POST request
function execPostRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Set title và description cho trang
$pageTitle = 'Thanh Toán - ' . SITE_NAME;
$pageDescription = 'Thanh toán gói VIP ' . $selectedPlan['name'] . ' - ' . SITE_NAME;

// Bắt đầu output buffering
ob_start();
?>

<div class="payment-page">
    <div class="payment-header">
        <h1>Thanh Toán</h1>
        <p>Hoàn tất thanh toán để nâng cấp tài khoản VIP</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="payment-container">
        <div class="order-summary">
            <h2>Thông Tin Đơn Hàng</h2>
            
            <div class="order-details">
                <div class="plan-info">
                    <div class="plan-name"><?php echo htmlspecialchars($selectedPlan['name']); ?></div>
                    <div class="plan-price"><?php echo number_format($selectedPlan['price'], 0, ',', '.'); ?> ₫</div>
                </div>
                
                <div class="plan-description">
                    <?php echo htmlspecialchars($selectedPlan['description']); ?>
                </div>
                
                <div class="plan-features">
                    <ul>
                        <li>✓ Xem phim chất lượng cao lên đến 4K</li>
                        <li>✓ Không quảng cáo</li>
                        <li>✓ Tải phim về máy để xem offline</li>
                        <li>✓ Thời hạn: <?php echo ($selectedPlan['duration'] >= 30) ? (floor($selectedPlan['duration'] / 30) . ' tháng') : ($selectedPlan['duration'] . ' ngày'); ?></li>
                        <?php if (isset($selectedPlan['discount'])): ?>
                            <li>✓ Tiết kiệm: <?php echo $selectedPlan['discount']; ?>% so với mua lẻ</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="customer-info">
                <h3>Thông Tin Khách Hàng</h3>
                <div class="info-item">
                    <strong>Họ tên:</strong> <?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?>
                </div>
                <div class="info-item">
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div class="info-item">
                    <strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?>
                </div>
            </div>
            
            <div class="order-total">
                <div class="total-label">Tổng cộng:</div>
                <div class="total-price"><?php echo number_format($selectedPlan['price'], 0, ',', '.'); ?> ₫</div>
            </div>
        </div>
        
        <div class="payment-methods">
            <h2>Chọn Phương Thức Thanh Toán</h2>
            
            <form method="POST" action="<?php echo url('thanh-toan?plan=' . $selectedPlan['id']); ?>">
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="vnpay" checked>
                        <div class="payment-option-content">
                            <img src="<?php echo url('assets/images/payment/vnpay.svg'); ?>" alt="VNPay">
                            <div class="payment-name">VNPay</div>
                        </div>
                        <div class="payment-description">
                            Thanh toán qua VNPay bằng quét mã QR hoặc Internet Banking
                        </div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="momo">
                        <div class="payment-option-content">
                            <img src="<?php echo url('assets/images/payment/momo.svg'); ?>" alt="MoMo">
                            <div class="payment-name">MoMo</div>
                        </div>
                        <div class="payment-description">
                            Thanh toán qua ví điện tử MoMo
                        </div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="stripe">
                        <div class="payment-option-content">
                            <img src="<?php echo url('assets/images/payment/stripe.svg'); ?>" alt="Stripe">
                            <div class="payment-name">Thẻ tín dụng/ghi nợ</div>
                        </div>
                        <div class="payment-description">
                            Thanh toán an toàn qua Stripe với thẻ Visa, MasterCard, JCB,...
                        </div>
                    </label>
                </div>
                
                <div class="payment-actions">
                    <a href="<?php echo url('vip'); ?>" class="btn btn-outline btn-back">Quay lại</a>
                    <button type="submit" class="btn btn-primary btn-pay">Thanh Toán Ngay</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="payment-footer">
        <div class="secure-payment">
            <i class="fas fa-lock"></i> Thanh toán an toàn & bảo mật
        </div>
        <div class="payment-logos">
            <img src="<?php echo url('assets/images/payment/visa.svg'); ?>" alt="Visa">
            <img src="<?php echo url('assets/images/payment/mastercard.svg'); ?>" alt="Mastercard">
            <img src="<?php echo url('assets/images/payment/vnpay.svg'); ?>" alt="VNPay">
            <img src="<?php echo url('assets/images/payment/momo.svg'); ?>" alt="MoMo">
        </div>
    </div>
</div>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>