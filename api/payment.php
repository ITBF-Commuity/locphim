<?php
// API thanh toán cho Lọc Phim
define('SECURE_ACCESS', true);
require_once '../config.php';

// Thiết lập các cấu hình thanh toán
$vnpay_config = get_config('vnpay');
$momo_config = get_config('momo');

/**
 * Tạo thanh toán VNPay
 */
function create_vnpay_payment($order_info) {
    global $vnpay_config;

    // Kiểm tra cấu hình
    if (empty($vnpay_config['merchant_id']) || empty($vnpay_config['secure_hash'])) {
        return [
            'success' => false,
            'message' => 'VNPay configuration is missing'
        ];
    }

    // Chuẩn bị thông tin đơn hàng
    $vnp_TmnCode = $vnpay_config['merchant_id']; // Mã website tại VNPAY
    $vnp_HashSecret = $vnpay_config['secure_hash']; // Chuỗi bí mật
    $vnp_Url = $vnpay_config['url']; // URL thanh toán
    $vnp_ReturnUrl = $vnpay_config['return_url']; // URL trả về

    $vnp_TxnRef = $order_info['order_id']; // Mã đơn hàng
    $vnp_OrderInfo = $order_info['description']; // Thông tin đơn hàng
    $vnp_Amount = $order_info['amount'] * 100; // Số tiền * 100 (VNPay tính theo đơn vị = VND)
    $vnp_Locale = 'vn'; // Ngôn ngữ
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR']; // IP của khách
    $vnp_OrderType = 'vip'; // Loại đơn hàng

    // Thời gian thanh toán
    $startTime = date("YmdHis");
    $expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));

    // Tạo các tham số thanh toán
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
        "vnp_ReturnUrl" => $vnp_ReturnUrl,
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_ExpireDate" => $expire
    );

    // Sắp xếp tham số theo thứ tự a-z
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

    // Tạo URL thanh toán
    $vnp_Url = $vnp_Url . "?" . $query;
    
    // Tạo chữ ký
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }

    // Lưu thông tin đơn hàng vào database
    $sql = "INSERT INTO payment_transactions (
        transaction_id, 
        user_id, 
        amount, 
        payment_method, 
        status, 
        vip_level, 
        vip_duration,
        order_info,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $params = [
        $vnp_TxnRef,
        $order_info['user_id'],
        $order_info['amount'],
        'vnpay',
        'pending',
        $order_info['vip_level'],
        $order_info['vip_duration'],
        $vnp_OrderInfo
    ];

    db_query($sql, $params);

    return [
        'success' => true,
        'payment_url' => $vnp_Url,
        'transaction_id' => $vnp_TxnRef
    ];
}

/**
 * Xử lý kết quả thanh toán VNPay
 */
function process_vnpay_return($get_data) {
    global $vnpay_config;
    
    $vnp_HashSecret = $vnpay_config['secure_hash']; // Chuỗi bí mật
    $vnp_SecureHash = $get_data['vnp_SecureHash']; // Mã hash từ VNPay
    
    // Xóa vnp_SecureHash từ dữ liệu để tính lại hash
    $inputData = $get_data;
    unset($inputData['vnp_SecureHash']);
    
    // Sắp xếp tham số
    ksort($inputData);
    $i = 0;
    $hashData = "";
    
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }
    
    // Tính lại mã hash
    $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
    
    // So sánh mã hash
    if ($secureHash == $vnp_SecureHash) {
        // Mã đơn hàng
        $vnp_TxnRef = $get_data['vnp_TxnRef'];
        // Mã giao dịch tại VNPay
        $vnp_TransactionNo = $get_data['vnp_TransactionNo'];
        // Mã phản hồi
        $vnp_ResponseCode = $get_data['vnp_ResponseCode'];
        // Mã ngân hàng
        $vnp_BankCode = $get_data['vnp_BankCode'] ?? '';
        // Mã tiền tệ
        $vnp_CardType = $get_data['vnp_CardType'] ?? '';
        
        // Kiểm tra mã phản hồi
        if ($vnp_ResponseCode == '00') {
            // Thanh toán thành công
            $transaction_status = 'completed';
            
            // Cập nhật trạng thái giao dịch
            $sql = "UPDATE payment_transactions SET 
                status = ?, 
                payment_gateway_code = ?, 
                bank_code = ?, 
                card_type = ?, 
                updated_at = NOW() 
                WHERE transaction_id = ?";
            
            $params = [
                $transaction_status,
                $vnp_TransactionNo,
                $vnp_BankCode,
                $vnp_CardType,
                $vnp_TxnRef
            ];
            
            db_query($sql, $params);
            
            // Lấy thông tin giao dịch
            $sql = "SELECT * FROM payment_transactions WHERE transaction_id = ?";
            $result = db_query($sql, [$vnp_TxnRef], false);
            
            if (get_config('db.type') === 'postgresql') {
                if (pg_num_rows($result) > 0) {
                    $transaction = pg_fetch_assoc($result);
                    // Kích hoạt VIP cho người dùng
                    activate_vip($transaction);
                }
            } else {
                if ($result->num_rows > 0) {
                    $transaction = $result->fetch_assoc();
                    // Kích hoạt VIP cho người dùng
                    activate_vip($transaction);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Thanh toán thành công',
                'transaction_id' => $vnp_TxnRef,
                'amount' => $get_data['vnp_Amount'] / 100,
                'bank_code' => $vnp_BankCode,
                'card_type' => $vnp_CardType
            ];
        } else {
            // Thanh toán thất bại
            $transaction_status = 'failed';
            
            // Cập nhật trạng thái giao dịch
            $sql = "UPDATE payment_transactions SET 
                status = ?, 
                payment_gateway_code = ?, 
                bank_code = ?, 
                card_type = ?, 
                updated_at = NOW() 
                WHERE transaction_id = ?";
            
            $params = [
                $transaction_status,
                $vnp_TransactionNo,
                $vnp_BankCode,
                $vnp_CardType,
                $vnp_TxnRef
            ];
            
            db_query($sql, $params);
            
            return [
                'success' => false,
                'message' => 'Thanh toán thất bại',
                'transaction_id' => $vnp_TxnRef,
                'response_code' => $vnp_ResponseCode
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Invalid signature'
        ];
    }
}

/**
 * Tạo thanh toán MoMo
 */
function create_momo_payment($order_info) {
    global $momo_config;
    
    // Kiểm tra cấu hình
    if (empty($momo_config['partner_code']) || empty($momo_config['access_key']) || empty($momo_config['secret_key'])) {
        return [
            'success' => false,
            'message' => 'MoMo configuration is missing'
        ];
    }
    
    // Chuẩn bị thông tin đơn hàng
    $partnerCode = $momo_config['partner_code'];
    $accessKey = $momo_config['access_key'];
    $secretKey = $momo_config['secret_key'];
    $endpoint = $momo_config['endpoint'];
    $returnUrl = $momo_config['return_url'];
    $notifyUrl = $momo_config['return_url']; // Có thể sử dụng URL khác
    
    $orderInfo = $order_info['description'];
    $amount = (string)$order_info['amount'];
    $orderId = $order_info['order_id'];
    $requestId = time() . "";
    $extraData = base64_encode(json_encode([
        'vip_level' => $order_info['vip_level'],
        'vip_duration' => $order_info['vip_duration']
    ]));
    
    // Tạo chữ ký
    $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $notifyUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $returnUrl . "&requestId=" . $requestId . "&requestType=captureWallet";
    $signature = hash_hmac('sha256', $rawHash, $secretKey);
    
    // Chuẩn bị dữ liệu gửi đi
    $data = [
        'partnerCode' => $partnerCode,
        'partnerName' => 'Lọc Phim',
        'storeId' => 'LocPhimOnline',
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $returnUrl,
        'ipnUrl' => $notifyUrl,
        'lang' => 'vi',
        'extraData' => $extraData,
        'requestType' => 'captureWallet',
        'signature' => $signature
    ];
    
    // Gọi API MoMo
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return [
            'success' => false,
            'message' => 'Curl error: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    // Xử lý kết quả
    $responseData = json_decode($response, true);
    
    if (isset($responseData['payUrl'])) {
        // Lưu thông tin đơn hàng vào database
        $sql = "INSERT INTO payment_transactions (
            transaction_id, 
            user_id, 
            amount, 
            payment_method, 
            status, 
            vip_level, 
            vip_duration,
            order_info,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $orderId,
            $order_info['user_id'],
            $order_info['amount'],
            'momo',
            'pending',
            $order_info['vip_level'],
            $order_info['vip_duration'],
            $orderInfo
        ];
        
        db_query($sql, $params);
        
        return [
            'success' => true,
            'payment_url' => $responseData['payUrl'],
            'transaction_id' => $orderId
        ];
    } else {
        return [
            'success' => false,
            'message' => $responseData['message'] ?? 'Unknown error',
            'response' => $responseData
        ];
    }
}

/**
 * Xử lý kết quả thanh toán MoMo
 */
function process_momo_return($get_data) {
    global $momo_config;
    
    $partnerCode = $get_data['partnerCode'] ?? '';
    $orderId = $get_data['orderId'] ?? '';
    $requestId = $get_data['requestId'] ?? '';
    $amount = $get_data['amount'] ?? '0';
    $orderInfo = $get_data['orderInfo'] ?? '';
    $orderType = $get_data['orderType'] ?? '';
    $transId = $get_data['transId'] ?? '';
    $resultCode = $get_data['resultCode'] ?? '';
    $message = $get_data['message'] ?? '';
    $payType = $get_data['payType'] ?? '';
    $responseTime = $get_data['responseTime'] ?? '';
    $extraData = $get_data['extraData'] ?? '';
    $signature = $get_data['signature'] ?? '';
    
    // Kiểm tra chữ ký
    $accessKey = $momo_config['access_key'];
    $secretKey = $momo_config['secret_key'];
    
    $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;
    $computedSignature = hash_hmac('sha256', $rawHash, $secretKey);
    
    if ($signature == $computedSignature) {
        // Kiểm tra kết quả thanh toán
        if ($resultCode == '0') {
            // Thanh toán thành công
            $transaction_status = 'completed';
            
            // Cập nhật trạng thái giao dịch
            $sql = "UPDATE payment_transactions SET 
                status = ?, 
                payment_gateway_code = ?, 
                updated_at = NOW() 
                WHERE transaction_id = ?";
            
            $params = [
                $transaction_status,
                $transId,
                $orderId
            ];
            
            db_query($sql, $params);
            
            // Lấy thông tin giao dịch
            $sql = "SELECT * FROM payment_transactions WHERE transaction_id = ?";
            $result = db_query($sql, [$orderId], false);
            
            if (get_config('db.type') === 'postgresql') {
                if (pg_num_rows($result) > 0) {
                    $transaction = pg_fetch_assoc($result);
                    // Kích hoạt VIP cho người dùng
                    activate_vip($transaction);
                }
            } else {
                if ($result->num_rows > 0) {
                    $transaction = $result->fetch_assoc();
                    // Kích hoạt VIP cho người dùng
                    activate_vip($transaction);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Thanh toán thành công',
                'transaction_id' => $orderId,
                'amount' => $amount,
                'trans_id' => $transId,
                'payment_method' => 'momo'
            ];
        } else {
            // Thanh toán thất bại
            $transaction_status = 'failed';
            
            // Cập nhật trạng thái giao dịch
            $sql = "UPDATE payment_transactions SET 
                status = ?, 
                payment_gateway_code = ?, 
                updated_at = NOW() 
                WHERE transaction_id = ?";
            
            $params = [
                $transaction_status,
                $transId,
                $orderId
            ];
            
            db_query($sql, $params);
            
            return [
                'success' => false,
                'message' => $message,
                'transaction_id' => $orderId,
                'result_code' => $resultCode
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Invalid signature'
        ];
    }
}

/**
 * Kích hoạt VIP cho người dùng
 */
function activate_vip($transaction) {
    // Lấy thông tin giao dịch
    $user_id = $transaction['user_id'];
    $vip_level = $transaction['vip_level'];
    $vip_duration = $transaction['vip_duration']; // Số ngày
    
    // Kiểm tra người dùng đã có VIP chưa
    $sql = "SELECT * FROM vip_members WHERE user_id = ? AND expire_date > NOW()";
    $result = db_query($sql, [$user_id], false);
    
    $has_vip = false;
    $current_expire_date = date('Y-m-d H:i:s');
    
    if (get_config('db.type') === 'postgresql') {
        $has_vip = pg_num_rows($result) > 0;
        if ($has_vip) {
            $vip_info = pg_fetch_assoc($result);
            $current_expire_date = $vip_info['expire_date'];
        }
    } else {
        $has_vip = $result->num_rows > 0;
        if ($has_vip) {
            $vip_info = $result->fetch_assoc();
            $current_expire_date = $vip_info['expire_date'];
        }
    }
    
    // Tính toán ngày hết hạn mới
    if ($has_vip) {
        // Nếu đã có VIP, cộng thêm thời gian
        $new_expire_date = date('Y-m-d H:i:s', strtotime($current_expire_date . ' + ' . $vip_duration . ' days'));
        
        // Cập nhật thông tin VIP
        $sql = "UPDATE vip_members SET 
            level = ?, 
            expire_date = ?, 
            updated_at = NOW() 
            WHERE user_id = ?";
        
        $params = [
            $vip_level,
            $new_expire_date,
            $user_id
        ];
        
        db_query($sql, $params);
    } else {
        // Nếu chưa có VIP, tạo mới
        $new_expire_date = date('Y-m-d H:i:s', strtotime('+ ' . $vip_duration . ' days'));
        
        // Xác định trạng thái quảng cáo dựa vào cấp VIP
        $vip_config = get_config('vip');
        $ads = true;
        
        if (isset($vip_config['levels'][$vip_level]['ads'])) {
            $ads = $vip_config['levels'][$vip_level]['ads'];
        }
        
        // Tạo thông tin VIP mới
        $sql = "INSERT INTO vip_members (
            user_id, 
            level, 
            start_date, 
            expire_date, 
            payment_id, 
            amount, 
            payment_method, 
            status, 
            ads, 
            created_at
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'active', ?, NOW())";
        
        $params = [
            $user_id,
            $vip_level,
            $new_expire_date,
            $transaction['transaction_id'],
            $transaction['amount'],
            $transaction['payment_method'],
            $ads
        ];
        
        db_query($sql, $params);
    }
    
    // Tạo thông báo cho người dùng
    $vip_config = get_config('vip');
    $vip_name = $vip_config['levels'][$vip_level]['name'] ?? "VIP Cấp $vip_level";
    $notification_title = "Nâng cấp VIP thành công";
    $notification_content = "Bạn đã được nâng cấp lên $vip_name. Thời hạn sử dụng đến ngày " . date('d/m/Y', strtotime($new_expire_date)) . ".";
    
    $sql = "INSERT INTO notifications (
        user_id, 
        title, 
        content, 
        type, 
        created_at
    ) VALUES (?, ?, ?, 'vip', NOW())";
    
    $params = [
        $user_id,
        $notification_title,
        $notification_content
    ];
    
    db_query($sql, $params);
    
    return true;
}

/**
 * Tạo mã đơn hàng
 */
function generate_order_id($prefix = 'LP') {
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    return $prefix . $timestamp . $random;
}

// Xử lý các request API
$action = $_GET['action'] ?? '';
$response = [
    'success' => false,
    'message' => 'Invalid action'
];

switch ($action) {
    case 'create_payment':
        // Tạo thanh toán mới
        $payment_method = $_POST['payment_method'] ?? '';
        $user_id = $_POST['user_id'] ?? 0;
        $vip_level = $_POST['vip_level'] ?? 1;
        $vip_duration = $_POST['vip_duration'] ?? 30;
        
        // Lấy giá tiền từ cấu hình
        $vip_config = get_config('vip');
        $amount = $vip_config['levels'][$vip_level]['price'] ?? 0;
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($payment_method) || empty($user_id) || $amount <= 0) {
            $response = [
                'success' => false,
                'message' => 'Invalid input data'
            ];
            break;
        }
        
        // Tạo mã đơn hàng
        $order_id = generate_order_id();
        
        // Chuẩn bị thông tin đơn hàng
        $vip_name = $vip_config['levels'][$vip_level]['name'] ?? "VIP Cấp $vip_level";
        $order_info = [
            'order_id' => $order_id,
            'user_id' => $user_id,
            'amount' => $amount,
            'vip_level' => $vip_level,
            'vip_duration' => $vip_duration,
            'description' => "Nâng cấp $vip_name - $vip_duration ngày"
        ];
        
        // Tạo thanh toán
        if ($payment_method == 'vnpay') {
            $result = create_vnpay_payment($order_info);
        } elseif ($payment_method == 'momo') {
            $result = create_momo_payment($order_info);
        } else {
            $result = [
                'success' => false,
                'message' => 'Unsupported payment method'
            ];
        }
        
        $response = $result;
        break;
    
    case 'vnpay_return':
        // Xử lý kết quả thanh toán VNPay
        $response = process_vnpay_return($_GET);
        break;
    
    case 'momo_return':
        // Xử lý kết quả thanh toán MoMo
        $response = process_momo_return($_GET);
        break;
    
    case 'check_payment':
        // Kiểm tra trạng thái thanh toán
        $transaction_id = $_GET['transaction_id'] ?? '';
        
        if (empty($transaction_id)) {
            $response = [
                'success' => false,
                'message' => 'Transaction ID is required'
            ];
            break;
        }
        
        $sql = "SELECT * FROM payment_transactions WHERE transaction_id = ?";
        $result = db_query($sql, [$transaction_id], false);
        
        if (get_config('db.type') === 'postgresql') {
            if (pg_num_rows($result) > 0) {
                $transaction = pg_fetch_assoc($result);
                
                $response = [
                    'success' => true,
                    'status' => $transaction['status'],
                    'transaction_id' => $transaction['transaction_id'],
                    'amount' => $transaction['amount'],
                    'payment_method' => $transaction['payment_method'],
                    'created_at' => $transaction['created_at']
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Transaction not found'
                ];
            }
        } else {
            if ($result->num_rows > 0) {
                $transaction = $result->fetch_assoc();
                
                $response = [
                    'success' => true,
                    'status' => $transaction['status'],
                    'transaction_id' => $transaction['transaction_id'],
                    'amount' => $transaction['amount'],
                    'payment_method' => $transaction['payment_method'],
                    'created_at' => $transaction['created_at']
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Transaction not found'
                ];
            }
        }
        break;
        
    case 'vip_info':
        // Lấy thông tin VIP của người dùng
        $user_id = $_GET['user_id'] ?? 0;
        
        if (empty($user_id)) {
            $response = [
                'success' => false,
                'message' => 'User ID is required'
            ];
            break;
        }
        
        $vip_info = check_vip_status($user_id);
        
        if ($vip_info) {
            $vip_config = get_config('vip');
            $vip_name = $vip_config['levels'][$vip_info['level']]['name'] ?? "VIP Cấp {$vip_info['level']}";
            $vip_resolution = $vip_config['levels'][$vip_info['level']]['resolution'] ?? "480p";
            
            $response = [
                'success' => true,
                'vip' => true,
                'level' => $vip_info['level'],
                'name' => $vip_name,
                'resolution' => $vip_resolution,
                'ads' => $vip_info['ads'],
                'expire_date' => $vip_info['expire_date'],
                'days_remaining' => floor((strtotime($vip_info['expire_date']) - time()) / 86400)
            ];
        } else {
            $response = [
                'success' => true,
                'vip' => false
            ];
        }
        break;
        
    case 'vip_packages':
        // Lấy danh sách gói VIP
        $vip_config = get_config('vip');
        $packages = [];
        
        foreach ($vip_config['levels'] as $level => $info) {
            $packages[] = [
                'level' => $level,
                'name' => $info['name'],
                'price' => $info['price'],
                'duration' => $info['duration'],
                'resolution' => $info['resolution'],
                'ads' => $info['ads']
            ];
        }
        
        $response = [
            'success' => true,
            'packages' => $packages
        ];
        break;
    
    default:
        $response = [
            'success' => false,
            'message' => 'Invalid action'
        ];
        break;
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($response);