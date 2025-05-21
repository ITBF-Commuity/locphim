<?php
/**
 * Lọc Phim - Hệ thống template
 * 
 * File này chứa các hàm xử lý template
 */

/**
 * Render template
 * 
 * @param string $template Tên template (không bao gồm .php)
 * @param array $data Dữ liệu truyền vào template
 * @param bool $return Trả về nội dung template thay vì hiển thị
 * @return string|void Nội dung template nếu $return = true
 */
function render($template, $data = [], $return = false) {
    // Đường dẫn đến file template
    $templateFile = VIEWS_DIR . '/' . $template . '.php';
    
    // Kiểm tra xem file template có tồn tại không
    if (!file_exists($templateFile)) {
        throw new Exception("Template không tồn tại: $template");
    }
    
    // Trích xuất dữ liệu để sử dụng trong template
    extract($data);
    
    // Bắt đầu output buffering
    ob_start();
    
    // Include file template
    include $templateFile;
    
    // Lấy nội dung buffer
    $content = ob_get_clean();
    
    if ($return) {
        return $content;
    }
    
    echo $content;
}

/**
 * Render layout
 * 
 * @param string $layout Tên layout (không bao gồm .php)
 * @param string $template Tên template (không bao gồm .php)
 * @param array $data Dữ liệu truyền vào template
 * @param bool $return Trả về nội dung layout thay vì hiển thị
 * @return string|void Nội dung layout nếu $return = true
 */
function render_layout($layout, $template, $data = [], $return = false) {
    // Render template
    $content = render($template, $data, true);
    
    // Thêm content vào data
    $data['content'] = $content;
    
    // Render layout
    return render('layouts/' . $layout, $data, $return);
}

/**
 * Render partial
 * 
 * @param string $partial Tên partial (không bao gồm .php)
 * @param array $data Dữ liệu truyền vào partial
 * @param bool $return Trả về nội dung partial thay vì hiển thị
 * @return string|void Nội dung partial nếu $return = true
 */
function render_partial($partial, $data = [], $return = false) {
    return render('partials/' . $partial, $data, $return);
}

/**
 * Render component
 * 
 * @param string $component Tên component (không bao gồm .php)
 * @param array $data Dữ liệu truyền vào component
 * @param bool $return Trả về nội dung component thay vì hiển thị
 * @return string|void Nội dung component nếu $return = true
 */
function render_component($component, $data = [], $return = false) {
    return render('components/' . $component, $data, $return);
}

/**
 * Escape HTML
 * 
 * @param string $text Văn bản cần escape
 * @return string Văn bản đã escape
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Hiển thị văn bản có định dạng
 * 
 * @param string $text Văn bản cần hiển thị
 * @return string Văn bản đã xử lý
 */
function format_text($text) {
    // Escape HTML
    $text = e($text);
    
    // Chuyển đổi URLs thành links
    $text = preg_replace('/(https?:\/\/[^\s<>"\']+)/i', '<a href="$1" target="_blank">$1</a>', $text);
    
    // Chuyển đổi xuống dòng thành <br>
    $text = nl2br($text);
    
    return $text;
}

/**
 * Lấy URL hiện tại
 * 
 * @return string URL hiện tại
 */
function current_url() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Lấy URL canonical
 * 
 * @return string URL canonical
 */
function canonical_url() {
    // URL hiện tại
    $url = current_url();
    
    // Loại bỏ tham số query string
    $url = preg_replace('/\?.*$/', '', $url);
    
    return $url;
}

/**
 * Kiểm tra xem có đang ở trang nào
 * 
 * @param string $page Tên trang
 * @return bool true nếu đúng, ngược lại là false
 */
function is_page($page) {
    // Lấy URL hiện tại
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Xóa query string
    $currentUrl = preg_replace('/\?.*$/', '', $currentUrl);
    
    // Xóa / ở đầu và cuối
    $currentUrl = trim($currentUrl, '/');
    
    // Nếu là trang chủ
    if ($page === 'home' && $currentUrl === '') {
        return true;
    }
    
    // Thực hiện kiểm tra
    return $currentUrl === $page || strpos($currentUrl, $page . '/') === 0;
}

/**
 * Kiểm tra xem có đang ở trang phim nào
 * 
 * @param string $slug Slug phim
 * @return bool true nếu đúng, ngược lại là false
 */
function is_movie($slug = null) {
    // Lấy URL hiện tại
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Xóa query string
    $currentUrl = preg_replace('/\?.*$/', '', $currentUrl);
    
    // Xóa / ở đầu và cuối
    $currentUrl = trim($currentUrl, '/');
    
    // Kiểm tra xem có đang ở trang phim không
    if (strpos($currentUrl, 'phim/') !== 0) {
        return false;
    }
    
    // Nếu không có slug thì trả về true
    if ($slug === null) {
        return true;
    }
    
    // Thực hiện kiểm tra với slug
    return $currentUrl === 'phim/' . $slug || strpos($currentUrl, 'phim/' . $slug . '/') === 0;
}

/**
 * Kiểm tra xem có đang ở trang xem phim nào
 * 
 * @param string $slug Slug phim
 * @param int $episode Số tập
 * @return bool true nếu đúng, ngược lại là false
 */
function is_watching($slug = null, $episode = null) {
    // Lấy URL hiện tại
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Xóa query string
    $currentUrl = preg_replace('/\?.*$/', '', $currentUrl);
    
    // Xóa / ở đầu và cuối
    $currentUrl = trim($currentUrl, '/');
    
    // Phân tích URL
    $segments = explode('/', $currentUrl);
    
    // Kiểm tra xem có đang ở trang xem phim không
    if (count($segments) < 3 || $segments[0] !== 'phim') {
        return false;
    }
    
    // Nếu không có slug thì trả về true
    if ($slug === null) {
        return true;
    }
    
    // Kiểm tra slug
    if ($segments[1] !== $slug) {
        return false;
    }
    
    // Nếu không có episode thì trả về true
    if ($episode === null) {
        return true;
    }
    
    // Kiểm tra episode
    return isset($segments[2]) && $segments[2] == $episode;
}

/**
 * Tạo breadcrumb
 * 
 * @param array $items Các mục trong breadcrumb
 * @return string HTML breadcrumb
 */
function breadcrumb($items) {
    $html = '<div class="breadcrumbs">';
    $html .= '<div class="container">';
    $html .= '<ul>';
    $html .= '<li><a href="' . url() . '">Trang chủ</a></li>';
    
    $count = count($items);
    foreach ($items as $i => $item) {
        if ($i === $count - 1) {
            // Mục cuối cùng
            $html .= '<li class="active">' . e($item['text']) . '</li>';
        } else {
            $html .= '<li><a href="' . $item['url'] . '">' . e($item['text']) . '</a></li>';
        }
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Tạo phân trang
 * 
 * @param int $currentPage Trang hiện tại
 * @param int $totalPages Tổng số trang
 * @param string $url URL gốc của trang
 * @param array $params Các tham số query string
 * @return string HTML phân trang
 */
function pagination($currentPage, $totalPages, $url, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    $html .= '<ul>';
    
    // Trang trước
    if ($currentPage > 1) {
        $prevParams = array_merge($params, ['page' => $currentPage - 1]);
        $prevUrl = $url . '?' . http_build_query($prevParams);
        $html .= '<li><a href="' . $prevUrl . '" class="prev">Trang trước</a></li>';
    } else {
        $html .= '<li><span class="prev disabled">Trang trước</span></li>';
    }
    
    // Các trang
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $firstParams = array_merge($params, ['page' => 1]);
        $firstUrl = $url . '?' . http_build_query($firstParams);
        $html .= '<li><a href="' . $firstUrl . '">1</a></li>';
        
        if ($start > 2) {
            $html .= '<li><span class="ellipsis">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li><span class="current">' . $i . '</span></li>';
        } else {
            $pageParams = array_merge($params, ['page' => $i]);
            $pageUrl = $url . '?' . http_build_query($pageParams);
            $html .= '<li><a href="' . $pageUrl . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li><span class="ellipsis">...</span></li>';
        }
        
        $lastParams = array_merge($params, ['page' => $totalPages]);
        $lastUrl = $url . '?' . http_build_query($lastParams);
        $html .= '<li><a href="' . $lastUrl . '">' . $totalPages . '</a></li>';
    }
    
    // Trang tiếp theo
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($params, ['page' => $currentPage + 1]);
        $nextUrl = $url . '?' . http_build_query($nextParams);
        $html .= '<li><a href="' . $nextUrl . '" class="next">Trang tiếp</a></li>';
    } else {
        $html .= '<li><span class="next disabled">Trang tiếp</span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Tạo nút chia sẻ mạng xã hội
 * 
 * @param string $url URL cần chia sẻ
 * @param string $title Tiêu đề
 * @return string HTML nút chia sẻ
 */
function social_share_buttons($url, $title) {
    $html = '<div class="social-share">';
    
    // Facebook
    $html .= '<a href="' . facebook_share_url($url, $title) . '" class="facebook" target="_blank" rel="noopener noreferrer">';
    $html .= '<i class="fab fa-facebook-f"></i>';
    $html .= '</a>';
    
    // Twitter
    $html .= '<a href="' . twitter_share_url($url, $title) . '" class="twitter" target="_blank" rel="noopener noreferrer">';
    $html .= '<i class="fab fa-twitter"></i>';
    $html .= '</a>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị rating bằng sao
 * 
 * @param float $rating Điểm đánh giá (từ 0 đến 10)
 * @param int $max Số sao tối đa
 * @return string HTML rating
 */
function rating_stars($rating, $max = 5) {
    // Chuyển điểm từ thang 10 sang thang $max
    $rating = ($rating / 10) * $max;
    
    $html = '<div class="rating-stars">';
    
    // Hiển thị sao
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= floor($rating)) {
            // Sao đầy
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            // Nửa sao
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            // Sao trống
            $html .= '<i class="far fa-star"></i>';
        }
    }
    
    // Hiển thị điểm số
    $html .= '<span class="rating-value">' . number_format($rating, 1) . '/' . $max . '</span>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị thông báo flash
 * 
 * @param string $name Tên thông báo
 * @param string $message Nội dung thông báo
 */
function flash($name, $message = null) {
    // Nếu message là null thì hiển thị thông báo
    if ($message === null) {
        if (isset($_SESSION['flash'][$name])) {
            $message = $_SESSION['flash'][$name];
            unset($_SESSION['flash'][$name]);
            return $message;
        }
        
        return '';
    }
    
    // Nếu message khác null thì thiết lập thông báo
    $_SESSION['flash'][$name] = $message;
}

/**
 * Kiểm tra xem có thông báo flash không
 * 
 * @param string $name Tên thông báo
 * @return bool true nếu có, ngược lại là false
 */
function has_flash($name) {
    return isset($_SESSION['flash'][$name]);
}

/**
 * Hiển thị thông báo flash với định dạng HTML
 * 
 * @param string $name Tên thông báo
 * @param string $class Class CSS
 * @return string HTML thông báo
 */
function flash_html($name, $class = 'alert') {
    if (!has_flash($name)) {
        return '';
    }
    
    $message = flash($name);
    return '<div class="' . $class . '">' . $message . '</div>';
}

/**
 * Hiển thị thông báo thành công
 * 
 * @param string $message Nội dung thông báo
 */
function flash_success($message) {
    flash('success', $message);
}

/**
 * Hiển thị thông báo lỗi
 * 
 * @param string $message Nội dung thông báo
 */
function flash_error($message) {
    flash('error', $message);
}

/**
 * Hiển thị thông báo HTML thành công
 * 
 * @return string HTML thông báo
 */
function flash_success_html() {
    return flash_html('success', 'alert alert-success');
}

/**
 * Hiển thị thông báo HTML lỗi
 * 
 * @return string HTML thông báo
 */
function flash_error_html() {
    return flash_html('error', 'alert alert-danger');
}

/**
 * Tạo form token input
 * 
 * @return string HTML input token
 */
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . form_token() . '">';
}

/**
 * Kiểm tra CSRF token
 * 
 * @return bool true nếu hợp lệ, ngược lại là false
 */
function csrf_check() {
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    return verify_form_token($_POST['csrf_token']);
}

/**
 * Thiết lập meta tags cho SEO
 * 
 * @param array $meta Thông tin meta
 */
function set_meta_tags($meta = []) {
    global $metaTags;
    
    $defaultMeta = [
        'title' => get_setting('site_title', SITE_NAME),
        'description' => get_setting('site_description', 'Nền tảng xem phim và anime trực tuyến'),
        'keywords' => get_setting('site_keywords', 'phim, anime, xem phim, phim hay, phim mới'),
        'image' => get_setting('site_logo', asset('images/logo.svg')),
        'url' => current_url()
    ];
    
    $metaTags = array_merge($defaultMeta, $meta);
}

/**
 * Hiển thị meta tags
 * 
 * @return string HTML meta tags
 */
function display_meta_tags() {
    global $metaTags;
    
    if (!isset($metaTags)) {
        set_meta_tags();
    }
    
    return meta_tags($metaTags);
}

/**
 * Lấy tiêu đề trang
 * 
 * @return string Tiêu đề trang
 */
function get_page_title() {
    global $metaTags;
    
    if (!isset($metaTags)) {
        set_meta_tags();
    }
    
    return $metaTags['title'];
}

/**
 * Hiển thị thông báo không có dữ liệu
 * 
 * @param string $message Nội dung thông báo
 * @return string HTML thông báo
 */
function empty_state($message = 'Không có dữ liệu') {
    return '<div class="empty-state">' . $message . '</div>';
}

/**
 * Hiển thị card phim
 * 
 * @param array $movie Thông tin phim
 * @return string HTML card phim
 */
function movie_card($movie) {
    if (!isset($movie['title'])) {
        return '';
    }
    
    // Xây dựng card
    $html = '<div class="movie-card">';
    
    // Poster
    $html .= '<a href="' . movie_url($movie) . '" class="movie-card-poster">';
    $html .= '<img src="' . poster_url($movie['poster']) . '" alt="' . e($movie['title']) . '">';
    
    // Overlay
    $html .= '<div class="movie-card-overlay">';
    $html .= '<div class="play-button"><i class="fas fa-play"></i></div>';
    $html .= '</div>';
    
    // Badge
    if (isset($movie['is_vip']) && $movie['is_vip']) {
        $html .= '<div class="movie-card-badge vip">VIP</div>';
    }
    
    // Views
    if (isset($movie['views']) && $movie['views'] > 0) {
        $html .= '<div class="movie-card-views">' . format_views($movie['views']) . ' lượt xem</div>';
    }
    
    $html .= '</a>';
    
    // Nội dung
    $html .= '<div class="movie-card-content">';
    $html .= '<h3 class="movie-card-title">' . e($movie['title']) . '</h3>';
    
    if (isset($movie['original_title']) && !empty($movie['original_title'])) {
        $html .= '<div class="movie-card-subtitle">' . e($movie['original_title']) . '</div>';
    }
    
    $html .= '<div class="movie-card-meta">';
    
    if (isset($movie['release_year']) && $movie['release_year'] > 0) {
        $html .= '<div class="movie-card-year">' . $movie['release_year'] . '</div>';
    }
    
    if (isset($movie['rating']) && $movie['rating'] > 0) {
        $html .= '<div class="movie-card-rating">';
        $html .= '<i class="fas fa-star"></i>';
        $html .= '<span>' . number_format($movie['rating'], 1) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị grid phim
 * 
 * @param array $movies Danh sách phim
 * @param int $columns Số cột
 * @return string HTML grid phim
 */
function movie_grid($movies, $columns = 6) {
    if (empty($movies)) {
        return empty_state('Không có phim nào.');
    }
    
    $html = '<div class="movie-grid movie-grid-' . $columns . '">';
    
    foreach ($movies as $movie) {
        $html .= movie_card($movie);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị slider phim
 * 
 * @param array $movies Danh sách phim
 * @return string HTML slider phim
 */
function movie_slider($movies) {
    if (empty($movies)) {
        return '';
    }
    
    $html = '<div class="movie-slider">';
    $html .= '<div class="movie-slides">';
    
    foreach ($movies as $index => $movie) {
        $activeClass = ($index === 0) ? ' active' : '';
        
        $html .= '<div class="movie-slide' . $activeClass . '">';
        $html .= '<div class="movie-slide-bg" style="background-image: url(\'' . banner_url($movie['banner']) . '\');"></div>';
        
        $html .= '<div class="movie-slide-content">';
        $html .= '<div class="container">';
        $html .= '<h2 class="movie-slide-title">' . e($movie['title']) . '</h2>';
        
        if (isset($movie['original_title']) && !empty($movie['original_title'])) {
            $html .= '<div class="movie-slide-subtitle">' . e($movie['original_title']) . '</div>';
        }
        
        if (isset($movie['description']) && !empty($movie['description'])) {
            $html .= '<div class="movie-slide-description">' . truncate($movie['description'], 200) . '</div>';
        }
        
        $html .= '<div class="movie-slide-meta">';
        
        if (isset($movie['release_year']) && $movie['release_year'] > 0) {
            $html .= '<div class="movie-slide-year">' . $movie['release_year'] . '</div>';
        }
        
        if (isset($movie['rating']) && $movie['rating'] > 0) {
            $html .= '<div class="movie-slide-rating">';
            $html .= '<i class="fas fa-star"></i>';
            $html .= '<span>' . number_format($movie['rating'], 1) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '<div class="movie-slide-buttons">';
        $html .= '<a href="' . movie_url($movie) . '" class="btn btn-primary">Chi tiết</a>';
        $html .= '<a href="' . watch_url($movie) . '" class="btn btn-secondary">Xem ngay</a>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Navigation
    $html .= '<div class="movie-slider-nav">';
    $html .= '<button class="movie-slider-prev"><i class="fas fa-chevron-left"></i></button>';
    
    $html .= '<div class="movie-slider-dots">';
    foreach ($movies as $index => $movie) {
        $activeClass = ($index === 0) ? ' active' : '';
        $html .= '<button class="movie-slider-dot' . $activeClass . '" data-index="' . $index . '"></button>';
    }
    $html .= '</div>';
    
    $html .= '<button class="movie-slider-next"><i class="fas fa-chevron-right"></i></button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị thông tin chi tiết phim
 * 
 * @param array $movie Thông tin phim
 * @return string HTML thông tin chi tiết phim
 */
function movie_detail($movie) {
    if (!isset($movie['title'])) {
        return '';
    }
    
    $html = '<div class="movie-detail">';
    
    // Banner
    $html .= '<div class="movie-detail-banner" style="background-image: url(\'' . banner_url($movie['banner']) . '\');">';
    $html .= '<div class="movie-detail-overlay"></div>';
    
    $html .= '<div class="movie-detail-content">';
    $html .= '<div class="container">';
    
    $html .= '<div class="movie-detail-info">';
    $html .= '<h1 class="movie-detail-title">' . e($movie['title']) . '</h1>';
    
    if (isset($movie['original_title']) && !empty($movie['original_title'])) {
        $html .= '<div class="movie-detail-subtitle">' . e($movie['original_title']) . '</div>';
    }
    
    $html .= '<div class="movie-detail-meta">';
    
    if (isset($movie['release_year']) && $movie['release_year'] > 0) {
        $html .= '<div class="movie-detail-year">Năm: ' . $movie['release_year'] . '</div>';
    }
    
    if (isset($movie['country']) && !empty($movie['country'])) {
        $html .= '<div class="movie-detail-country">Quốc gia: ' . e($movie['country']) . '</div>';
    }
    
    if (isset($movie['duration']) && $movie['duration'] > 0) {
        $html .= '<div class="movie-detail-duration">Thời lượng: ' . $movie['duration'] . ' phút</div>';
    }
    
    if (isset($movie['views']) && $movie['views'] > 0) {
        $html .= '<div class="movie-detail-views">Lượt xem: ' . format_views($movie['views']) . '</div>';
    }
    
    if (isset($movie['rating']) && $movie['rating'] > 0) {
        $html .= '<div class="movie-detail-rating">Đánh giá: ' . rating_stars($movie['rating']) . '</div>';
    }
    
    $html .= '</div>';
    
    if (isset($movie['description']) && !empty($movie['description'])) {
        $html .= '<div class="movie-detail-description">' . format_text($movie['description']) . '</div>';
    }
    
    $html .= '<div class="movie-detail-buttons">';
    $html .= '<a href="' . watch_url($movie) . '" class="btn btn-primary">Xem phim</a>';
    
    if (isset($movie['trailer_url']) && !empty($movie['trailer_url'])) {
        $html .= '<a href="' . $movie['trailer_url'] . '" class="btn btn-secondary trailer-button" data-trailer="' . $movie['trailer_url'] . '">Xem trailer</a>';
    }
    
    $html .= social_share_buttons(current_url(), $movie['title']);
    
    $html .= '</div>';
    
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị player phim
 * 
 * @param array $movie Thông tin phim
 * @param array $episode Thông tin tập phim
 * @param array $sources Danh sách nguồn video
 * @param array $subtitles Danh sách phụ đề
 * @return string HTML player phim
 */
function movie_player($movie, $episode, $sources, $subtitles = []) {
    if (empty($sources)) {
        return '<div class="player-error">Không có nguồn video.</div>';
    }
    
    $html = '<div class="movie-player" id="movie-player">';
    
    // Hiển thị video
    $html .= '<video id="locphim-player" class="video-js vjs-big-play-centered" controls preload="auto" poster="' . poster_url($movie['poster']) . '" data-episode-id="' . $episode['id'] . '">';
    
    // Các nguồn video
    foreach ($sources as $source) {
        $html .= '<source src="' . $source['source_url'] . '" type="video/mp4" label="' . get_actual_quality($source) . '" selected="' . ($source['is_default'] ? 'true' : 'false') . '">';
    }
    
    // Các phụ đề
    foreach ($subtitles as $subtitle) {
        $html .= '<track kind="subtitles" src="' . $subtitle['subtitle_url'] . '" srclang="' . $subtitle['language'] . '" label="' . get_subtitle_language($subtitle['language']) . '" ' . ($subtitle['is_default'] ? 'default' : '') . '>';
    }
    
    $html .= '</video>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Lấy tên ngôn ngữ phụ đề
 * 
 * @param string $language Mã ngôn ngữ
 * @return string Tên ngôn ngữ
 */
function get_subtitle_language($language) {
    $languages = [
        'vi' => 'Tiếng Việt',
        'en' => 'Tiếng Anh',
        'fr' => 'Tiếng Pháp',
        'de' => 'Tiếng Đức',
        'es' => 'Tiếng Tây Ban Nha',
        'it' => 'Tiếng Ý',
        'ja' => 'Tiếng Nhật',
        'ko' => 'Tiếng Hàn',
        'zh' => 'Tiếng Trung',
        'ru' => 'Tiếng Nga'
    ];
    
    return isset($languages[$language]) ? $languages[$language] : $language;
}

/**
 * Hiển thị danh sách tập phim
 * 
 * @param array $movie Thông tin phim
 * @param array $episodes Danh sách tập phim
 * @param int $currentEpisode Tập phim hiện tại
 * @return string HTML danh sách tập phim
 */
function episode_list($movie, $episodes, $currentEpisode = null) {
    if (empty($episodes)) {
        return '<div class="empty-state">Không có tập phim nào.</div>';
    }
    
    $html = '<div class="episode-list">';
    
    // Hiển thị tiêu đề
    $html .= '<div class="episode-list-title">Danh sách tập phim</div>';
    
    // Hiển thị danh sách
    $html .= '<div class="episode-items">';
    
    foreach ($episodes as $episode) {
        $isCurrentEpisode = $currentEpisode !== null && $episode['episode_number'] == $currentEpisode;
        $isVip = is_vip_required($episode, $movie);
        
        $classes = [];
        if ($isCurrentEpisode) {
            $classes[] = 'current';
        }
        if ($isVip) {
            $classes[] = 'vip';
        }
        
        $html .= '<a href="' . watch_url($movie, $episode['episode_number']) . '" class="episode-item' . (!empty($classes) ? ' ' . implode(' ', $classes) : '') . '">';
        
        $html .= '<div class="episode-number">Tập ' . $episode['episode_number'] . '</div>';
        
        if (isset($episode['title']) && !empty($episode['title'])) {
            $html .= '<div class="episode-title">' . e($episode['title']) . '</div>';
        }
        
        if (isset($episode['views']) && $episode['views'] > 0) {
            $html .= '<div class="episode-views">' . format_views($episode['views']) . '</div>';
        }
        
        if ($isVip) {
            $html .= '<div class="episode-vip"><i class="fas fa-crown"></i></div>';
        }
        
        $html .= '</a>';
    }
    
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị danh sách bình luận
 * 
 * @param array $comments Danh sách bình luận
 * @param bool $allowReply Cho phép trả lời bình luận
 * @return string HTML danh sách bình luận
 */
function comment_list($comments, $allowReply = true) {
    if (empty($comments)) {
        return '<div class="empty-state">Chưa có bình luận nào.</div>';
    }
    
    $html = '<div class="comment-list">';
    
    // Hiển thị danh sách
    foreach ($comments as $comment) {
        $html .= comment_item($comment, $allowReply);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị một bình luận
 * 
 * @param array $comment Thông tin bình luận
 * @param bool $allowReply Cho phép trả lời bình luận
 * @return string HTML bình luận
 */
function comment_item($comment, $allowReply = true) {
    $html = '<div class="comment-item" id="comment-' . $comment['id'] . '">';
    
    // Avatar
    $html .= '<div class="comment-avatar">';
    $html .= '<img src="' . avatar_url($comment['user_avatar']) . '" alt="' . e($comment['username']) . '">';
    $html .= '</div>';
    
    // Nội dung
    $html .= '<div class="comment-content">';
    
    // Tiêu đề
    $html .= '<div class="comment-header">';
    $html .= '<div class="comment-username">' . e($comment['username']) . '</div>';
    $html .= '<div class="comment-time">' . time_elapsed($comment['created_at']) . '</div>';
    $html .= '</div>';
    
    // Nội dung bình luận
    $html .= '<div class="comment-text">' . format_text($comment['content']) . '</div>';
    
    // Footer
    $html .= '<div class="comment-footer">';
    
    if ($allowReply) {
        $html .= '<button class="comment-reply-btn" data-comment-id="' . $comment['id'] . '">Trả lời</button>';
    }
    
    $html .= '</div>';
    
    // Form trả lời
    if ($allowReply) {
        $html .= '<div class="comment-reply-form" id="comment-reply-form-' . $comment['id'] . '" style="display: none;">';
        $html .= '<form action="' . url('api/comments/reply') . '" method="post" class="reply-form">';
        $html .= '<input type="hidden" name="parent_id" value="' . $comment['id'] . '">';
        $html .= csrf_input();
        $html .= '<textarea name="content" placeholder="Nhập nội dung trả lời..."></textarea>';
        $html .= '<button type="submit" class="btn btn-primary">Gửi</button>';
        $html .= '</form>';
        $html .= '</div>';
    }
    
    // Hiển thị các trả lời
    if (isset($comment['replies']) && !empty($comment['replies'])) {
        $html .= '<div class="comment-replies">';
        
        foreach ($comment['replies'] as $reply) {
            $html .= comment_item($reply, false);
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị form bình luận
 * 
 * @param int $movieId ID phim
 * @param int|null $episodeId ID tập phim
 * @return string HTML form bình luận
 */
function comment_form($movieId, $episodeId = null) {
    $user = getCurrentUser();
    
    if (!$user) {
        return '<div class="comment-form-login">Vui lòng <a href="' . url('dang-nhap?redirect=' . urlencode(current_url())) . '">đăng nhập</a> để bình luận.</div>';
    }
    
    $html = '<div class="comment-form">';
    $html .= '<form action="' . url('api/comments/add') . '" method="post" id="comment-form">';
    $html .= '<input type="hidden" name="movie_id" value="' . $movieId . '">';
    
    if ($episodeId !== null) {
        $html .= '<input type="hidden" name="episode_id" value="' . $episodeId . '">';
    }
    
    $html .= csrf_input();
    
    $html .= '<div class="comment-form-header">';
    $html .= '<div class="comment-form-avatar">';
    $html .= '<img src="' . avatar_url($user['avatar']) . '" alt="' . e($user['username']) . '">';
    $html .= '</div>';
    $html .= '<div class="comment-form-username">' . e($user['username']) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="comment-form-content">';
    $html .= '<textarea name="content" placeholder="Nhập nội dung bình luận..."></textarea>';
    $html .= '</div>';
    
    $html .= '<div class="comment-form-footer">';
    $html .= '<button type="submit" class="btn btn-primary">Gửi bình luận</button>';
    $html .= '</div>';
    
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Hiển thị form đánh giá
 * 
 * @param int $movieId ID phim
 * @return string HTML form đánh giá
 */
function rating_form($movieId) {
    $user = getCurrentUser();
    
    if (!$user) {
        return '<div class="rating-form-login">Vui lòng <a href="' . url('dang-nhap?redirect=' . urlencode(current_url())) . '">đăng nhập</a> để đánh giá.</div>';
    }
    
    $html = '<div class="rating-form">';
    $html .= '<form action="' . url('api/ratings/add') . '" method="post" id="rating-form">';
    $html .= '<input type="hidden" name="movie_id" value="' . $movieId . '">';
    $html .= csrf_input();
    
    $html .= '<div class="rating-form-stars">';
    
    for ($i = 10; $i >= 1; $i--) {
        $html .= '<input type="radio" name="rating" value="' . $i . '" id="rating-' . $i . '">';
        $html .= '<label for="rating-' . $i . '"><i class="fas fa-star"></i></label>';
    }
    
    $html .= '</div>';
    
    $html .= '<div class="rating-form-footer">';
    $html .= '<button type="submit" class="btn btn-primary">Gửi đánh giá</button>';
    $html .= '</div>';
    
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}