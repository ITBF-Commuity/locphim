<?php
// Các hàm xử lý API cho Lọc Phim
define('SECURE_ACCESS', true);
require_once dirname(__DIR__) . '/config.php';

/**
 * Tạo slug từ chuỗi
 */
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#([^a-z0-9]+)#i',
    );
    
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        '-',
    );
    
    $string = strtolower(preg_replace($search, $replace, $string));
    $string = preg_replace('/(-)+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Xử lý response từ Jikan API
 */
function process_jikan_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data'])) {
        $anime = $data['data'];
        
        return [
            'id' => $anime['mal_id'] ?? null,
            'source_id' => 'mal_' . ($anime['mal_id'] ?? ''),
            'title' => $anime['title'] ?? '',
            'alt_title' => $anime['title_english'] ?? '',
            'slug' => create_slug($anime['title'] ?? ''),
            'description' => $anime['synopsis'] ?? '',
            'thumbnail' => $anime['images']['jpg']['large_image_url'] ?? '',
            'banner' => $anime['images']['jpg']['large_image_url'] ?? '',
            'release_year' => isset($anime['year']) ? (int)$anime['year'] : null,
            'release_date' => $anime['aired']['from'] ?? null,
            'status' => map_status($anime['status'] ?? ''),
            'episode_count' => $anime['episodes'] ?? 0,
            'rating' => isset($anime['score']) ? (float)$anime['score'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['mal_id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $anime['genres'] ?? []),
            'source_api' => 'jikan',
            'details' => [
                'type' => $anime['type'] ?? '',
                'duration' => $anime['duration'] ?? '',
                'rating' => $anime['rating'] ?? '',
                'studios' => array_map(function($studio) {
                    return $studio['name'] ?? '';
                }, $anime['studios'] ?? []),
                'source_material' => $anime['source'] ?? '',
                'season' => $anime['season'] ?? '',
                'year' => $anime['year'] ?? '',
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ Kitsu API
 */
function process_kitsu_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data'])) {
        $anime = $data['data'];
        $attributes = $anime['attributes'] ?? [];
        
        return [
            'id' => $anime['id'] ?? null,
            'source_id' => 'kitsu_' . ($anime['id'] ?? ''),
            'title' => $attributes['canonicalTitle'] ?? '',
            'alt_title' => ($attributes['titles']['en'] ?? $attributes['titles']['en_jp'] ?? ''),
            'slug' => create_slug($attributes['canonicalTitle'] ?? ''),
            'description' => $attributes['synopsis'] ?? '',
            'thumbnail' => $attributes['posterImage']['large'] ?? '',
            'banner' => $attributes['coverImage']['large'] ?? '',
            'release_year' => isset($attributes['startDate']) ? (int)substr($attributes['startDate'], 0, 4) : null,
            'release_date' => $attributes['startDate'] ?? null,
            'status' => map_status($attributes['status'] ?? ''),
            'episode_count' => $attributes['episodeCount'] ?? 0,
            'rating' => isset($attributes['averageRating']) ? (float)$attributes['averageRating'] / 10 : 0,
            'categories' => [], // Cần thêm request riêng cho categories
            'source_api' => 'kitsu',
            'details' => [
                'age_rating' => $attributes['ageRating'] ?? '',
                'age_rating_guide' => $attributes['ageRatingGuide'] ?? '',
                'episode_length' => $attributes['episodeLength'] ?? 0,
                'subtype' => $attributes['subtype'] ?? '',
                'original_language' => $attributes['originalLanguage'] ?? '',
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ AniList API
 */
function process_anilist_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data']['Media'])) {
        $anime = $data['data']['Media'];
        
        return [
            'id' => $anime['id'] ?? null,
            'source_id' => 'anilist_' . ($anime['id'] ?? ''),
            'title' => $anime['title']['romaji'] ?? '',
            'alt_title' => $anime['title']['english'] ?? '',
            'slug' => create_slug($anime['title']['romaji'] ?? ''),
            'description' => $anime['description'] ?? '',
            'thumbnail' => $anime['coverImage']['large'] ?? '',
            'banner' => $anime['bannerImage'] ?? '',
            'release_year' => isset($anime['seasonYear']) ? (int)$anime['seasonYear'] : null,
            'release_date' => $anime['startDate']['year'] . '-' . $anime['startDate']['month'] . '-' . $anime['startDate']['day'] ?? null,
            'status' => map_status($anime['status'] ?? ''),
            'episode_count' => $anime['episodes'] ?? 0,
            'rating' => isset($anime['averageScore']) ? (float)$anime['averageScore'] / 10 : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => 0, // AniList không có ID cho thể loại
                    'name' => $genre
                ];
            }, $anime['genres'] ?? []),
            'source_api' => 'anilist',
            'details' => [
                'format' => $anime['format'] ?? '',
                'duration' => $anime['duration'] ?? 0,
                'season' => $anime['season'] ?? '',
                'year' => $anime['seasonYear'] ?? '',
                'source_material' => $anime['source'] ?? '',
                'studios' => array_map(function($studio) {
                    return $studio['name'] ?? '';
                }, $anime['studios']['nodes'] ?? []),
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ TMDB API
 */
function process_tmdb_response($data, $type = 'anime') {
    if ($type === 'movie' && isset($data['id'])) {
        $movie = $data;
        
        return [
            'id' => $movie['id'] ?? null,
            'source_id' => 'tmdb_' . ($movie['id'] ?? ''),
            'title' => $movie['title'] ?? '',
            'alt_title' => $movie['original_title'] ?? '',
            'slug' => create_slug($movie['title'] ?? ''),
            'description' => $movie['overview'] ?? '',
            'thumbnail' => 'https://image.tmdb.org/t/p/w500' . ($movie['poster_path'] ?? ''),
            'banner' => 'https://image.tmdb.org/t/p/original' . ($movie['backdrop_path'] ?? ''),
            'release_year' => isset($movie['release_date']) ? (int)substr($movie['release_date'], 0, 4) : null,
            'release_date' => $movie['release_date'] ?? null,
            'status' => map_status($movie['status'] ?? ''),
            'episode_count' => 1, // Movie chỉ có 1
            'rating' => isset($movie['vote_average']) ? (float)$movie['vote_average'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $movie['genres'] ?? []),
            'source_api' => 'tmdb',
            'details' => [
                'runtime' => $movie['runtime'] ?? 0,
                'budget' => $movie['budget'] ?? 0,
                'revenue' => $movie['revenue'] ?? 0,
                'original_language' => $movie['original_language'] ?? '',
                'production_companies' => array_map(function($company) {
                    return $company['name'] ?? '';
                }, $movie['production_companies'] ?? []),
            ]
        ];
    } elseif ($type === 'tv' && isset($data['id'])) {
        $tv = $data;
        
        return [
            'id' => $tv['id'] ?? null,
            'source_id' => 'tmdb_' . ($tv['id'] ?? ''),
            'title' => $tv['name'] ?? '',
            'alt_title' => $tv['original_name'] ?? '',
            'slug' => create_slug($tv['name'] ?? ''),
            'description' => $tv['overview'] ?? '',
            'thumbnail' => 'https://image.tmdb.org/t/p/w500' . ($tv['poster_path'] ?? ''),
            'banner' => 'https://image.tmdb.org/t/p/original' . ($tv['backdrop_path'] ?? ''),
            'release_year' => isset($tv['first_air_date']) ? (int)substr($tv['first_air_date'], 0, 4) : null,
            'release_date' => $tv['first_air_date'] ?? null,
            'status' => map_status($tv['status'] ?? ''),
            'episode_count' => $tv['number_of_episodes'] ?? 0,
            'rating' => isset($tv['vote_average']) ? (float)$tv['vote_average'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $tv['genres'] ?? []),
            'source_api' => 'tmdb',
            'details' => [
                'number_of_seasons' => $tv['number_of_seasons'] ?? 0,
                'episode_run_time' => $tv['episode_run_time'][0] ?? 0,
                'original_language' => $tv['original_language'] ?? '',
                'production_companies' => array_map(function($company) {
                    return $company['name'] ?? '';
                }, $tv['production_companies'] ?? []),
                'networks' => array_map(function($network) {
                    return $network['name'] ?? '';
                }, $tv['networks'] ?? []),
            ]
        ];
    }
    
    return null;
}

/**
 * Chuyển đổi trạng thái từ các API về định dạng thống nhất
 */
function map_status($status) {
    $status = strtolower($status);
    
    switch ($status) {
        case 'currently airing':
        case 'airing':
        case 'ongoing':
        case 'releasing':
        case 'current':
            return 'ongoing';
        
        case 'finished airing':
        case 'finished':
        case 'completed':
        case 'ended':
            return 'completed';
            
        case 'not yet aired':
        case 'upcoming':
        case 'unreleased':
        case 'planned':
            return 'upcoming';
            
        case 'on hiatus':
        case 'paused':
            return 'hiatus';
            
        case 'cancelled':
        case 'discontinued':
            return 'cancelled';
            
        default:
            return 'unknown';
    }
}

/**
 * Kiểm tra trạng thái VIP của người dùng
 */
function check_vip_status($user_id) {
    $sql = "SELECT * FROM vip_members WHERE user_id = ? AND expire_date > NOW() AND status = 'active'";
    $result = db_query($sql, [$user_id], false);
    
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } else {
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
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
 * Tìm trailer từ YouTube
 */
function search_trailer($query, $max_results = 5) {
    if (!is_api_configured('youtube')) {
        return [
            'success' => false,
            'error' => 'YouTube API is not configured'
        ];
    }
    
    $params = [
        'part' => 'snippet',
        'q' => $query . ' trailer official',
        'type' => 'video',
        'videoDefinition' => 'high',
        'maxResults' => $max_results
    ];
    
    $response = call_youtube_api('/search', $params);
    
    if ($response['success'] && isset($response['data']['items'])) {
        $results = [];
        
        foreach ($response['data']['items'] as $item) {
            $results[] = [
                'id' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'description' => $item['snippet']['description'],
                'thumbnail' => $item['snippet']['thumbnails']['high']['url'],
                'url' => 'https://www.youtube.com/watch?v=' . $item['id']['videoId'],
                'embed_url' => 'https://www.youtube.com/embed/' . $item['id']['videoId'],
            ];
        }
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search trailers on YouTube'
    ];
}