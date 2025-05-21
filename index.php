<?php
/**
 * Lọc Phim - Trang chính
 */

// Load file cấu hình
require_once 'config.php';

// Khởi tạo biến
$currentPage = 'home';
$params = [];
$pageTitle = SITE_NAME;
$pageDescription = SITE_DESCRIPTION;
$ogImage = 'assets/images/logo.png';
$pageContent = '';

// Xử lý URL Rewrite
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
if (substr($request_uri, 0, strlen($base_path)) == $base_path) {
    $request_uri = substr($request_uri, strlen($base_path));
}

$request_uri = trim($request_uri, '/');

// Định tuyến
$routes = [
    '^$' => 'home',
    '^trang-chu$' => 'home',
    '^the-loai/([^/]+)$' => 'category',
    '^quoc-gia/([^/]+)$' => 'country',
    '^nam/([0-9]+)$' => 'year',
    '^danh-sach/([^/]+)$' => 'listing',
    '^tim-kiem$' => 'search',
    '^phim/([^/]+)/([0-9]+)$' => 'movie',
    '^phim/([^/]+)/([0-9]+)/tap-([0-9]+)$' => 'episode',
    '^dien-vien/([^/]+)/([0-9]+)$' => 'actor',
    '^dang-nhap$' => 'login',
    '^dang-ky$' => 'register',
    '^dang-xuat$' => 'logout',
    '^quen-mat-khau$' => 'forgot_password',
    '^dat-lai-mat-khau/([^/]+)$' => 'reset_password',
    '^tai-khoan$' => 'profile',
    '^tai-khoan/([^/]+)$' => 'profile',
    '^vip$' => 'vip',
    '^thanh-toan$' => 'payment',
    '^vnpay-return$' => 'vnpay_return',
    '^momo-return$' => 'momo_return',
    '^admin$' => 'admin',
    '^admin/([^/]+)$' => 'admin_section',
    '^ajax/([^/]+)$' => 'ajax',
    '^api/([^/]+)$' => 'api',
    '^([0-9]+)$' => 'movie_by_id'
];

$page = 'home';
$matched = false;

foreach ($routes as $pattern => $route) {
    if (preg_match('#' . $pattern . '#', $request_uri, $matches)) {
        $page = $route;
        $matched = true;
        
        // Xác định các tham số từ URL
        switch ($page) {
            case 'category':
                $params['category_slug'] = urldecode($matches[1]);
                break;
                
            case 'country':
                $params['country_slug'] = urldecode($matches[1]);
                break;
                
            case 'year':
                $params['year'] = (int)$matches[1];
                break;
                
            case 'listing':
                $params['listing_type'] = urldecode($matches[1]);
                break;
                
            case 'search':
                $params['keyword'] = isset($_GET['q']) ? trim($_GET['q']) : '';
                break;
                
            case 'movie':
                $params['movie_slug'] = urldecode($matches[1]);
                $params['movie_id'] = (int)$matches[2];
                break;
                
            case 'episode':
                $params['movie_slug'] = urldecode($matches[1]);
                $params['movie_id'] = (int)$matches[2];
                $params['episode_number'] = (int)$matches[3];
                break;
                
            case 'actor':
                $params['actor_slug'] = urldecode($matches[1]);
                $params['actor_id'] = (int)$matches[2];
                break;
                
            case 'reset_password':
                $params['token'] = $matches[1];
                break;
                
            case 'admin_section':
                $params['section'] = $matches[1];
                break;
                
            case 'ajax':
                $params['action'] = $matches[1];
                break;
                
            case 'api':
                $params['endpoint'] = $matches[1];
                break;
                
            case 'profile':
                // Nếu đường dẫn là /tai-khoan/[tab]
                if (isset($matches[1])) {
                    $_GET['tab'] = $matches[1];
                }
                break;
                
            case 'movie_by_id':
                $params['movie_id'] = (int)$matches[1];
                $movie = $db->get("SELECT * FROM movies WHERE id = ?", [$params['movie_id']]);
                if ($movie) {
                    header('Location: ' . url('phim/' . $movie['slug'] . '/' . $movie['id']));
                    exit;
                } else {
                    $page = '404';
                }
                break;
        }
        
        break;
    }
}

// Nếu không tìm thấy trang nào khớp, hiển thị trang 404
if (!$matched) {
    $page = '404';
}

// Kiểm tra quyền truy cập
$restricted_pages = [
    'profile' => 'logged_in',
    'profile_edit' => 'logged_in',
    'profile_favorites' => 'logged_in',
    'profile_history' => 'logged_in',
    'profile_settings' => 'logged_in',
    'admin' => 'admin',
    'admin_section' => 'admin',
    'logout' => 'logged_in'
];

if (isset($restricted_pages[$page])) {
    $permission = $restricted_pages[$page];
    
    if (!has_permission($permission)) {
        if ($permission === 'logged_in') {
            // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . url('dang-nhap'));
            exit;
        } else {
            // Hiển thị trang 403 (Forbidden)
            $page = '403';
        }
    }
}

// Kiểm tra các trang đặc biệt
if ($page === 'logout') {
    // Xử lý đăng xuất
    session_destroy();
    header('Location: ' . url(''));
    exit;
} elseif (strpos($page, 'ajax_') === 0 || $page === 'ajax') {
    // Xử lý các yêu cầu AJAX
    require_once 'includes/ajax_handler.php';
    exit;
} elseif (strpos($page, 'api_') === 0 || $page === 'api') {
    // Xử lý các yêu cầu API
    require_once 'includes/api_handler.php';
    exit;
}

// Xử lý Flash Message
if (isset($_SESSION['toast']) && isset($_SESSION['toast_type'])) {
    $toastMessage = $_SESSION['toast'];
    $toastType = $_SESSION['toast_type'];
    unset($_SESSION['toast']);
    unset($_SESSION['toast_type']);
}

// Gán giá trị cho biến $currentPage
$currentPage = $page;

// Lấy dữ liệu từ session
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = $db->get("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Xử lý yêu cầu trang
try {
    ob_start();
    
    // Load file trang
    $file_path = 'pages/' . $page . '.php';
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        require_once 'pages/404.php';
    }
    
    // Lấy nội dung đã buffer
    if (!isset($pageContent) || empty($pageContent)) {
        $pageContent = ob_get_clean();
    } else {
        ob_end_clean();
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        $pageContent = '<div class="error-message">Error: ' . $e->getMessage() . '</div>';
    } else {
        $pageContent = '<div class="error-message">Có lỗi xảy ra khi tải trang. Vui lòng thử lại sau.</div>';
    }
}

// Render giao diện
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // Nếu là yêu cầu AJAX, chỉ trả về nội dung trang
    echo $pageContent;
} else {
    // Hiển thị trang đầy đủ
    require_once 'includes/header.php';
    echo $pageContent;
    require_once 'includes/footer.php';
}