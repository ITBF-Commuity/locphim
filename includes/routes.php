<?php
/**
 * Lọc Phim - File routes
 * 
 * File xử lý các đường dẫn URL của website
 */

/**
 * Mảng các route URL và tên file PHP tương ứng
 * 
 * @var array
 */
$routes = [
    // Trang chủ
    'home' => 'home.php',
    
    // Trang phim
    'movie' => 'movie.php',
    
    // Trang xem phim
    'watch' => 'watch.php',
    
    // Trang thể loại
    'category' => 'category.php',
    'categories' => 'categories.php',
    
    // Trang quốc gia
    'country' => 'country.php',
    'countries' => 'countries.php',
    
    // Trang phim lẻ, phim bộ, anime, phim chiếu rạp
    'movies' => 'movies.php',
    
    // Trang tìm kiếm
    'search' => 'search.php',
    
    // Trang bảng xếp hạng
    'ranking' => 'ranking.php',
    
    // Trang người dùng
    'account' => 'account.php',
    'favorites' => 'favorites.php',
    'history' => 'history.php',
    
    // Trang đăng nhập, đăng ký, quên mật khẩu
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'logout' => 'auth/logout.php',
    'forgot-password' => 'auth/forgot-password.php',
    'reset-password' => 'auth/reset-password.php',
    
    // Trang VIP và thanh toán
    'vip' => 'vip.php',
    'payment' => 'payment.php',
    'payment-success' => 'payment-success.php',
    
    // Trang thông tin
    'about' => 'about.php',
    'contact' => 'contact.php',
    'terms' => 'terms.php',
    'privacy' => 'privacy.php',
    'faq' => 'faq.php',
    'dmca' => 'dmca.php',
    
    // Trang lỗi
    '404' => '404.php'
];

/**
 * Hàm lấy tên file PHP từ tên route
 * 
 * @param string $routeName Tên route
 * @return string|false Tên file PHP hoặc false nếu không tìm thấy
 */
function getRouteFile($routeName) {
    global $routes;
    
    return isset($routes[$routeName]) ? $routes[$routeName] : false;
}

/**
 * Hàm lấy URL của route
 * 
 * @param string $routeName Tên route
 * @param array $params Tham số URL
 * @return string URL của route
 */
function getRouteUrl($routeName, $params = []) {
    // Mảng các route URL
    $routeUrls = [
        // Trang chủ
        'home' => '/',
        
        // Trang phim
        'movie' => '/phim/{slug}',
        
        // Trang xem phim
        'watch' => '/xem/{slug}/{episode}',
        
        // Trang thể loại
        'category' => '/the-loai/{slug}',
        'categories' => '/the-loai',
        
        // Trang quốc gia
        'country' => '/quoc-gia/{slug}',
        'countries' => '/quoc-gia',
        
        // Trang phim lẻ, phim bộ, anime, phim chiếu rạp
        'movies' => '/phim-le', // Mặc định là phim lẻ
        
        // Trang tìm kiếm
        'search' => '/tim-kiem',
        
        // Trang bảng xếp hạng
        'ranking' => '/bang-xep-hang',
        
        // Trang người dùng
        'account' => '/tai-khoan',
        'favorites' => '/yeu-thich',
        'history' => '/lich-su-xem',
        
        // Trang đăng nhập, đăng ký, quên mật khẩu
        'login' => '/dang-nhap',
        'register' => '/dang-ky',
        'logout' => '/dang-xuat',
        'forgot-password' => '/quen-mat-khau',
        'reset-password' => '/dat-lai-mat-khau',
        
        // Trang VIP và thanh toán
        'vip' => '/vip',
        'payment' => '/thanh-toan',
        'payment-success' => '/thanh-toan/thanh-cong',
        
        // Trang thông tin
        'about' => '/gioi-thieu',
        'contact' => '/lien-he',
        'terms' => '/dieu-khoan-su-dung',
        'privacy' => '/chinh-sach-bao-mat',
        'faq' => '/faq',
        'dmca' => '/dmca',
        
        // Trang lỗi
        '404' => '/404'
    ];
    
    // Kiểm tra xem route có tồn tại không
    if (!isset($routeUrls[$routeName])) {
        return '/';
    }
    
    // Lấy URL của route
    $url = $routeUrls[$routeName];
    
    // Các trường hợp đặc biệt
    if ($routeName === 'movies' && isset($params['type'])) {
        switch ($params['type']) {
            case 'single':
                $url = '/phim-le';
                break;
            case 'series':
                $url = '/phim-bo';
                break;
            case 'theater':
                $url = '/phim-chieu-rap';
                break;
        }
    } elseif ($routeName === 'movies' && isset($params['category']) && $params['category'] === 'anime') {
        $url = '/anime';
    }
    
    // Thay thế các tham số trong URL
    foreach ($params as $key => $value) {
        if ($key !== 'type' && $key !== 'category') {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
    }
    
    // Thêm query string nếu còn tham số
    $queryParams = [];
    foreach ($params as $key => $value) {
        if ($key !== 'type' && $key !== 'category' && strpos($url, '{' . $key . '}') === false) {
            $queryParams[$key] = $value;
        }
    }
    
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    return $url;
}

/**
 * Hàm xử lý route
 * 
 * @param string $routeName Tên route
 * @param array $params Tham số URL
 * @return void
 */
function handleRoute($routeName, $params = []) {
    $routeFile = getRouteFile($routeName);
    
    if ($routeFile === false) {
        // Không tìm thấy route, chuyển hướng đến trang 404
        header('HTTP/1.0 404 Not Found');
        include PAGES_PATH . '/404.php';
        exit;
    }
    
    // Thiết lập tham số URL
    foreach ($params as $key => $value) {
        $_GET[$key] = $value;
    }
    
    // Bao gồm file route
    include PAGES_PATH . '/' . $routeFile;
    exit;
}