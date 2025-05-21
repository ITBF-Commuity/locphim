<?php
/**
 * Lọc Phim - Controller xử lý thanh toán và VIP
 */

class PaymentController {
    /**
     * Đối tượng database
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Thông tin người dùng hiện tại
     * 
     * @var array|null
     */
    private $currentUser;
    
    /**
     * Khởi tạo controller
     * 
     * @param Database $db
     * @param array|null $currentUser
     */
    public function __construct($db, $currentUser = null) {
        $this->db = $db;
        $this->currentUser = $currentUser;
    }
    
    /**
     * Hiển thị trang gói VIP
     */
    public function vipPackages() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Lấy thông tin gói VIP
        $packages = $this->db->getAll("SELECT * FROM vip_packages ORDER BY duration ASC");
        
        // Hiển thị trang gói VIP
        include_once PAGES_PATH . '/payment/vip-packages.php';
    }
    
    /**
     * Xử lý thanh toán qua MOMO
     */
    public function momoPayment() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Kiểm tra dữ liệu gửi lên
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/vip');
            return;
        }
        
        $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        
        // Lấy thông tin gói VIP
        $package = $this->db->get("SELECT * FROM vip_packages WHERE id = :id", ['id' => $packageId]);
        
        if (!$package) {
            // Gói không tồn tại
            set_flash_message('error', 'Gói VIP không tồn tại.');
            redirect('/vip');
            return;
        }
        
        // Tạo mã đơn hàng
        $orderId = uniqid('LP');
        
        // Lưu thông tin đơn hàng
        $orderData = [
            'user_id' => $this->currentUser['id'],
            'package_id' => $packageId,
            'order_id' => $orderId,
            'amount' => $package['price'],
            'payment_method' => 'momo',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('payment_orders', $orderData);
        
        // Cấu hình MOMO
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey = MOMO_ACCESS_KEY;
        $secretKey = MOMO_SECRET_KEY;
        $orderInfo = "Thanh toán gói VIP " . $package['name'] . " - " . $package['duration'] . " ngày";
        $amount = $package['price'];
        $redirectUrl = url('thanh-toan/ket-qua');
        $ipnUrl = url('api/momo-callback');
        $requestId = $orderId;
        $requestType = "captureWallet";
        $extraData = base64_encode(json_encode(['user_id' => $this->currentUser['id'], 'package_id' => $packageId]));
        
        // Tạo chữ ký
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        $signature = hash_hmac("sha256", $rawHash, $secretKey);
        
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Lọc Phim',
            'storeId' => 'LocPhimVIP',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];
        
        // Gửi yêu cầu đến MOMO
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);
        
        if ($status == 200 && isset($response['payUrl'])) {
            // Chuyển hướng đến trang thanh toán MOMO
            redirect($response['payUrl']);
        } else {
            // Lỗi khi tạo yêu cầu thanh toán
            set_flash_message('error', 'Có lỗi xảy ra khi tạo yêu cầu thanh toán qua MOMO.');
            redirect('/vip');
        }
    }
    
    /**
     * Xử lý thanh toán qua VNPAY
     */
    public function vnpayPayment() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Kiểm tra dữ liệu gửi lên
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/vip');
            return;
        }
        
        $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        
        // Lấy thông tin gói VIP
        $package = $this->db->get("SELECT * FROM vip_packages WHERE id = :id", ['id' => $packageId]);
        
        if (!$package) {
            // Gói không tồn tại
            set_flash_message('error', 'Gói VIP không tồn tại.');
            redirect('/vip');
            return;
        }
        
        // Tạo mã đơn hàng
        $orderId = uniqid('LP');
        
        // Lưu thông tin đơn hàng
        $orderData = [
            'user_id' => $this->currentUser['id'],
            'package_id' => $packageId,
            'order_id' => $orderId,
            'amount' => $package['price'],
            'payment_method' => 'vnpay',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('payment_orders', $orderData);
        
        // Cấu hình VNPAY
        $vnp_TmnCode = VNPAY_TMN_CODE;
        $vnp_HashSecret = VNPAY_HASH_SECRET;
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = url('thanh-toan/ket-qua');
        
        $vnp_TxnRef = $orderId;
        $vnp_OrderInfo = "Thanh toan goi VIP " . $package['name'] . " - " . $package['duration'] . " ngay";
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $package['price'] * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $vnp_BankCode = "";
        $vnp_Bill_Mobile = $this->currentUser['phone'] ?? '';
        $vnp_Bill_Email = $this->currentUser['email'] ?? '';
        $vnp_Bill_FirstName = $this->currentUser['full_name'] ?? $this->currentUser['username'];
        $vnp_Bill_LastName = "";
        $vnp_Bill_Address = "";
        $vnp_Bill_City = "";
        $vnp_Bill_Country = "VN";
        
        $inputData = [
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
            "vnp_Bill_Mobile" => $vnp_Bill_Mobile,
            "vnp_Bill_Email" => $vnp_Bill_Email,
            "vnp_Bill_FirstName" => $vnp_Bill_FirstName,
            "vnp_Bill_LastName" => $vnp_Bill_LastName,
            "vnp_Bill_Address" => $vnp_Bill_Address,
            "vnp_Bill_City" => $vnp_Bill_City,
            "vnp_Bill_Country" => $vnp_Bill_Country
        ];
        
        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
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
        
        // Chuyển hướng đến trang thanh toán VNPAY
        redirect($vnp_Url);
    }
    
    /**
     * Xử lý thanh toán qua Stripe
     */
    public function stripePayment() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Kiểm tra dữ liệu gửi lên
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/vip');
            return;
        }
        
        $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        
        // Lấy thông tin gói VIP
        $package = $this->db->get("SELECT * FROM vip_packages WHERE id = :id", ['id' => $packageId]);
        
        if (!$package) {
            // Gói không tồn tại
            set_flash_message('error', 'Gói VIP không tồn tại.');
            redirect('/vip');
            return;
        }
        
        // Stripe API yêu cầu cài đặt stripe-php
        require_once INCLUDES_PATH . '/libs/stripe-php/init.php';
        
        // Cấu hình Stripe
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        // Tạo mã đơn hàng
        $orderId = uniqid('LP');
        
        // Tạo customer nếu chưa có
        $stripeCustomerId = $this->currentUser['stripe_customer_id'] ?? null;
        
        if (!$stripeCustomerId) {
            $customer = \Stripe\Customer::create([
                'email' => $this->currentUser['email'],
                'name' => $this->currentUser['full_name'] ?? $this->currentUser['username'],
                'metadata' => [
                    'user_id' => $this->currentUser['id']
                ]
            ]);
            
            $stripeCustomerId = $customer->id;
            
            // Cập nhật stripe_customer_id vào database
            $this->db->update('users', [
                'stripe_customer_id' => $stripeCustomerId,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', [
                'id' => $this->currentUser['id']
            ]);
        }
        
        // Tạo payment intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $package['price'] * 100, // Số tiền tính bằng cent
            'currency' => 'usd',
            'customer' => $stripeCustomerId,
            'metadata' => [
                'user_id' => $this->currentUser['id'],
                'package_id' => $packageId,
                'order_id' => $orderId
            ]
        ]);
        
        // Lưu thông tin đơn hàng
        $orderData = [
            'user_id' => $this->currentUser['id'],
            'package_id' => $packageId,
            'order_id' => $orderId,
            'amount' => $package['price'],
            'payment_method' => 'stripe',
            'payment_id' => $paymentIntent->id,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('payment_orders', $orderData);
        
        // Trả về client secret để tiếp tục quá trình thanh toán
        $clientSecret = $paymentIntent->client_secret;
        
        // Hiển thị trang thanh toán Stripe
        include_once PAGES_PATH . '/payment/stripe-checkout.php';
    }
    
    /**
     * Xử lý kết quả thanh toán
     */
    public function paymentResult() {
        // Kiểm tra đăng nhập
        if (!$this->currentUser) {
            // Chuyển hướng đến trang đăng nhập
            redirect('/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        $message = '';
        $success = false;
        
        // Xử lý kết quả từ MOMO
        if (isset($_GET['partnerCode']) && $_GET['partnerCode'] == MOMO_PARTNER_CODE) {
            $orderId = $_GET['orderId'] ?? '';
            $resultCode = $_GET['resultCode'] ?? '';
            
            if ($resultCode == '0') {
                // Thanh toán thành công
                $this->processSuccessfulPayment($orderId);
                $success = true;
                $message = 'Thanh toán thành công! Tài khoản của bạn đã được nâng cấp lên VIP.';
            } else {
                // Thanh toán thất bại
                $this->db->update('payment_orders', [
                    'status' => 'failed',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'order_id = :order_id', [
                    'order_id' => $orderId
                ]);
                
                $message = 'Thanh toán thất bại. Vui lòng thử lại sau.';
            }
        }
        // Xử lý kết quả từ VNPAY
        elseif (isset($_GET['vnp_ResponseCode'])) {
            $orderId = $_GET['vnp_TxnRef'] ?? '';
            $responseCode = $_GET['vnp_ResponseCode'] ?? '';
            
            if ($responseCode == '00') {
                // Thanh toán thành công
                $this->processSuccessfulPayment($orderId);
                $success = true;
                $message = 'Thanh toán thành công! Tài khoản của bạn đã được nâng cấp lên VIP.';
            } else {
                // Thanh toán thất bại
                $this->db->update('payment_orders', [
                    'status' => 'failed',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'order_id = :order_id', [
                    'order_id' => $orderId
                ]);
                
                $message = 'Thanh toán thất bại. Vui lòng thử lại sau.';
            }
        }
        // Xử lý kết quả từ Stripe
        elseif (isset($_GET['payment_intent'])) {
            $paymentIntentId = $_GET['payment_intent'] ?? '';
            
            require_once INCLUDES_PATH . '/libs/stripe-php/init.php';
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                
                if ($paymentIntent->status == 'succeeded') {
                    // Lấy order_id từ metadata
                    $orderId = $paymentIntent->metadata->order_id ?? '';
                    
                    // Thanh toán thành công
                    $this->processSuccessfulPayment($orderId);
                    $success = true;
                    $message = 'Thanh toán thành công! Tài khoản của bạn đã được nâng cấp lên VIP.';
                } else {
                    // Thanh toán thất bại
                    $this->db->update('payment_orders', [
                        'status' => 'failed',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'payment_id = :payment_id', [
                        'payment_id' => $paymentIntentId
                    ]);
                    
                    $message = 'Thanh toán thất bại. Vui lòng thử lại sau.';
                }
            } catch (\Exception $e) {
                $message = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        } else {
            $message = 'Không có thông tin thanh toán.';
        }
        
        // Hiển thị trang kết quả thanh toán
        include_once PAGES_PATH . '/payment/payment-result.php';
    }
    
    /**
     * Xử lý thanh toán thành công
     * 
     * @param string $orderId Mã đơn hàng
     */
    private function processSuccessfulPayment($orderId) {
        // Lấy thông tin đơn hàng
        $order = $this->db->get(
            "SELECT * FROM payment_orders WHERE order_id = :order_id AND user_id = :user_id",
            ['order_id' => $orderId, 'user_id' => $this->currentUser['id']]
        );
        
        if (!$order) {
            return false;
        }
        
        // Cập nhật trạng thái đơn hàng
        $this->db->update('payment_orders', [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $order['id']
        ]);
        
        // Lấy thông tin gói VIP
        $package = $this->db->get("SELECT * FROM vip_packages WHERE id = :id", ['id' => $order['package_id']]);
        
        if (!$package) {
            return false;
        }
        
        // Tính thời gian VIP
        $vipExpiredAt = null;
        
        if ($this->currentUser['is_vip'] && $this->currentUser['vip_expired_at']) {
            // Nếu đã là VIP, thêm thời gian vào thời gian hết hạn hiện tại
            $vipExpiredAt = date('Y-m-d H:i:s', strtotime($this->currentUser['vip_expired_at'] . ' + ' . $package['duration'] . ' days'));
        } else {
            // Nếu chưa là VIP, tính từ hiện tại
            $vipExpiredAt = date('Y-m-d H:i:s', strtotime('+ ' . $package['duration'] . ' days'));
        }
        
        // Cập nhật trạng thái VIP cho người dùng
        $this->db->update('users', [
            'is_vip' => true,
            'vip_expired_at' => $vipExpiredAt,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $this->currentUser['id']
        ]);
        
        // Lưu lịch sử VIP
        $this->db->insert('vip_history', [
            'user_id' => $this->currentUser['id'],
            'package_id' => $package['id'],
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'payment_method' => $order['payment_method'],
            'duration' => $package['duration'],
            'expired_at' => $vipExpiredAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
}