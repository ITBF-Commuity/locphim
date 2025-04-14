<?php
/**
 * Trang quản lý hiệu suất và tối ưu tốc độ
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Tối Ưu Hiệu Suất';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý cài đặt
$admin = require_admin_permission('manage_settings');

// Xử lý lưu cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy hành động
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        // Lưu cài đặt tối ưu
        $enable_minify = isset($_POST['enable_minify']) ? '1' : '0';
        $enable_minify_html = isset($_POST['enable_minify_html']) ? '1' : '0';
        $enable_minify_css = isset($_POST['enable_minify_css']) ? '1' : '0';
        $enable_minify_js = isset($_POST['enable_minify_js']) ? '1' : '0';
        $enable_cache = isset($_POST['enable_cache']) ? '1' : '0';
        $enable_lazy_load = isset($_POST['enable_lazy_load']) ? '1' : '0';
        $enable_gzip = isset($_POST['enable_gzip']) ? '1' : '0';
        $enable_browser_caching = isset($_POST['enable_browser_caching']) ? '1' : '0';
        $cache_lifetime = (int)($_POST['cache_lifetime'] ?? 3600);
        
        // Cập nhật cài đặt
        update_setting('enable_minify', $enable_minify);
        update_setting('enable_minify_html', $enable_minify_html);
        update_setting('enable_minify_css', $enable_minify_css);
        update_setting('enable_minify_js', $enable_minify_js);
        update_setting('enable_cache', $enable_cache);
        update_setting('enable_lazy_load', $enable_lazy_load);
        update_setting('enable_gzip', $enable_gzip);
        update_setting('enable_browser_caching', $enable_browser_caching);
        update_setting('cache_lifetime', $cache_lifetime);
        
        // Ghi log
        log_admin_action('update_performance_settings', 'Cập nhật cài đặt hiệu suất');
        
        // Thông báo thành công
        set_flash_message('success', 'Đã lưu cài đặt hiệu suất thành công!');
    } elseif ($action === 'clear_cache') {
        // Xóa cache
        $result = clear_cache();
        
        // Thông báo kết quả
        if ($result['success']) {
            set_flash_message('success', $result['message']);
        } else {
            set_flash_message('error', $result['message']);
        }
        
        // Ghi log
        log_admin_action('clear_cache', 'Xóa bộ nhớ đệm');
    } elseif ($action === 'regenerate_htaccess') {
        // Tạo lại file .htaccess
        $result = generate_htaccess();
        
        // Thông báo kết quả
        if ($result['success']) {
            set_flash_message('success', $result['message']);
        } else {
            set_flash_message('error', $result['message']);
        }
        
        // Ghi log
        log_admin_action('regenerate_htaccess', 'Tạo lại file .htaccess');
    }
    
    // Chuyển hướng để tránh gửi lại form
    header('Location: performance.php');
    exit;
}

// Lấy cài đặt hiệu suất hiện tại
$enable_minify = get_setting('enable_minify', '1');
$enable_minify_html = get_setting('enable_minify_html', '1');
$enable_minify_css = get_setting('enable_minify_css', '1');
$enable_minify_js = get_setting('enable_minify_js', '1');
$enable_cache = get_setting('enable_cache', '1');
$enable_lazy_load = get_setting('enable_lazy_load', '1');
$enable_gzip = get_setting('enable_gzip', '1');
$enable_browser_caching = get_setting('enable_browser_caching', '1');
$cache_lifetime = get_setting('cache_lifetime', '3600');

// Kiểm tra và tạo thư mục cache nếu chưa tồn tại
$cache_dir = dirname(dirname(__FILE__)) . '/cache';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// Tính toán kích thước cache
$cache_size = 0;
$cache_files = 0;
$cache_stats = calculate_cache_stats($cache_dir);
$cache_size = $cache_stats['size'];
$cache_files = $cache_stats['files'];

// Kiểm tra xem file .htaccess có tồn tại không
$htaccess_file = dirname(dirname(__FILE__)) . '/.htaccess';
$htaccess_exists = file_exists($htaccess_file);

// CSRF token
$csrf_token = generate_csrf_token();

/**
 * Tính toán thống kê cache
 */
function calculate_cache_stats($dir) {
    $size = 0;
    $files = 0;
    
    if (is_dir($dir)) {
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($objects as $object) {
            if ($object->isFile()) {
                $size += $object->getSize();
                $files++;
            }
        }
    }
    
    return [
        'size' => $size,
        'files' => $files
    ];
}

/**
 * Xóa cache
 */
function clear_cache() {
    $cache_dir = dirname(dirname(__FILE__)) . '/cache';
    
    if (!file_exists($cache_dir) || !is_dir($cache_dir)) {
        return [
            'success' => false,
            'message' => 'Thư mục cache không tồn tại.'
        ];
    }
    
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $count = 0;
    foreach ($objects as $object) {
        if ($object->isFile()) {
            unlink($object->getPathname());
            $count++;
        }
    }
    
    return [
        'success' => true,
        'message' => "Đã xóa $count tệp cache thành công."
    ];
}

/**
 * Tạo lại file .htaccess
 */
function generate_htaccess() {
    $htaccess_file = dirname(dirname(__FILE__)) . '/.htaccess';
    
    // Nội dung .htaccess
    $htaccess_content = <<<EOT
# Lọc Phim - Tệp cấu hình .htaccess
# Được tạo tự động bởi hệ thống

# Bật mod_rewrite
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Chuyển hướng www đến non-www
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^(.*)$ http://%1/$1 [R=301,L]
    
    # URL thân thiện
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    
    # Quy tắc chuyển hướng cho trang phim
    RewriteRule ^anime/([^/]+)/?$ watch.php?slug=$1 [QSA,L]
    RewriteRule ^anime/([^/]+)/episode-([0-9]+)/?$ watch.php?slug=$1&episode=$2 [QSA,L]
    
    # Quy tắc chuyển hướng cho danh mục
    RewriteRule ^category/([^/]+)/?$ category.php?slug=$1 [QSA,L]
    
    # Quy tắc chuyển hướng cho trang tĩnh
    RewriteRule ^page/([^/]+)/?$ page.php?slug=$1 [QSA,L]
</IfModule>

# Bật nén GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>

# Bộ nhớ đệm trình duyệt
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType video/mp4 "access plus 1 month"
    ExpiresByType video/webm "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/json "access plus 1 day"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"
    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>

# Cài đặt ETags
<IfModule mod_headers.c>
    <FilesMatch "\.(ico|jpg|jpeg|png|gif|webp|css|js)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
    <FilesMatch "\.(html|htm)$">
        Header set Cache-Control "max-age=7200, private, must-revalidate"
    </FilesMatch>
    <FilesMatch "\.(pdf)$">
        Header set Cache-Control "max-age=86400, public"
    </FilesMatch>
    <FilesMatch "\.(mp4|webm)$">
        Header set Cache-Control "max-age=2592000, public"
    </FilesMatch>
    Header unset ETag
    Header unset Last-Modified
</IfModule>

# Bảo vệ tệp
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(config\.php|\.env|composer\.json|composer\.lock)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Tăng cường bảo mật
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>

# Chặn truy cập vào thư mục
Options -Indexes
EOT;
    
    // Ghi vào file
    $result = file_put_contents($htaccess_file, $htaccess_content);
    
    if ($result === false) {
        return [
            'success' => false,
            'message' => 'Không thể tạo file .htaccess. Vui lòng kiểm tra quyền ghi.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Đã tạo lại file .htaccess thành công.'
    ];
}

/**
 * Định dạng kích thước
 */
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Tối Ưu Hiệu Suất</h1>
        <p class="admin-page-subtitle">Quản lý và tối ưu tốc độ tải trang</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-tachometer-alt mr-2"></i> Cài Đặt Hiệu Suất
                </h2>
            </div>
            <div class="admin-card-body">
                <form method="post" action="performance.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_minify" name="enable_minify" <?php echo $enable_minify == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_minify">Bật nén và tối ưu</label>
                        </div>
                        <small class="form-text text-muted">Kích hoạt tính năng nén và tối ưu tổng thể.</small>
                    </div>
                    
                    <div class="optimization-settings ml-4">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_minify_html" name="enable_minify_html" <?php echo $enable_minify_html == '1' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="enable_minify_html">Nén HTML</label>
                            </div>
                            <small class="form-text text-muted">Loại bỏ các khoảng trắng và comment không cần thiết từ HTML.</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_minify_css" name="enable_minify_css" <?php echo $enable_minify_css == '1' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="enable_minify_css">Nén CSS</label>
                            </div>
                            <small class="form-text text-muted">Nén và kết hợp các file CSS để giảm số lượng yêu cầu HTTP.</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_minify_js" name="enable_minify_js" <?php echo $enable_minify_js == '1' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="enable_minify_js">Nén JavaScript</label>
                            </div>
                            <small class="form-text text-muted">Nén và kết hợp các file JavaScript để giảm số lượng yêu cầu HTTP.</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_cache" name="enable_cache" <?php echo $enable_cache == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_cache">Bật bộ nhớ đệm (Cache)</label>
                        </div>
                        <small class="form-text text-muted">Lưu trữ các trang đã tạo trong bộ nhớ đệm để tăng tốc độ tải trang.</small>
                    </div>
                    
                    <div class="form-group ml-4">
                        <label for="cache_lifetime">Thời gian sống của bộ nhớ đệm (giây)</label>
                        <input type="number" class="form-control" id="cache_lifetime" name="cache_lifetime" value="<?php echo $cache_lifetime; ?>" min="60" max="86400">
                        <small class="form-text text-muted">Thời gian mà bộ nhớ đệm được lưu trữ trước khi bị xóa.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_lazy_load" name="enable_lazy_load" <?php echo $enable_lazy_load == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_lazy_load">Bật tải lười biếng (Lazy Loading)</label>
                        </div>
                        <small class="form-text text-muted">Chỉ tải hình ảnh và video khi chúng xuất hiện trong khung nhìn của người dùng.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_gzip" name="enable_gzip" <?php echo $enable_gzip == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_gzip">Bật nén GZIP</label>
                        </div>
                        <small class="form-text text-muted">Nén dữ liệu trước khi gửi đến trình duyệt của người dùng.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_browser_caching" name="enable_browser_caching" <?php echo $enable_browser_caching == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_browser_caching">Bật bộ nhớ đệm trình duyệt</label>
                        </div>
                        <small class="form-text text-muted">Thiết lập tiêu đề HTTP để kích hoạt bộ nhớ đệm trình duyệt.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Lưu cài đặt
                        </button>
                        
                        <?php if ($htaccess_exists): ?>
                            <button type="submit" name="action" value="regenerate_htaccess" class="btn btn-info ml-2">
                                <i class="fas fa-sync mr-1"></i> Tạo lại .htaccess
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="regenerate_htaccess" class="btn btn-warning ml-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Tạo file .htaccess
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-image mr-2"></i> Tối Ưu Hình Ảnh
                </h2>
            </div>
            <div class="admin-card-body">
                <p>Hình ảnh là một trong những yếu tố ảnh hưởng lớn nhất đến hiệu suất trang web. Tối ưu hóa hình ảnh sẽ giúp trang web tải nhanh hơn đáng kể.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i> Các hình ảnh tải lên sau này sẽ tự động được tối ưu hóa. Bạn có thể tối ưu hóa thủ công các hình ảnh đã có sẵn.
                </div>
                
                <form method="post" action="optimize-images.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="compression_level">Mức độ nén</label>
                        <select class="form-control" id="compression_level" name="compression_level">
                            <option value="low">Thấp (giảm 20-30% kích thước, chất lượng cao)</option>
                            <option value="medium" selected>Trung bình (giảm 40-60% kích thước, chất lượng tốt)</option>
                            <option value="high">Cao (giảm 70-80% kích thước, chất lượng thấp hơn)</option>
                        </select>
                        <small class="form-text text-muted">Mức độ nén cao sẽ giảm kích thước hình ảnh nhưng cũng làm giảm chất lượng.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="convert_webp" name="convert_webp" checked>
                            <label class="custom-control-label" for="convert_webp">Chuyển đổi sang định dạng WebP</label>
                        </div>
                        <small class="form-text text-muted">WebP là định dạng hình ảnh hiện đại với kích thước nhỏ hơn và chất lượng tốt hơn.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="resize_large_images" name="resize_large_images" checked>
                            <label class="custom-control-label" for="resize_large_images">Thay đổi kích thước hình ảnh lớn</label>
                        </div>
                        <small class="form-text text-muted">Thay đổi kích thước hình ảnh quá lớn để phù hợp với kích thước hiển thị tối đa.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="generate_thumbnails" name="generate_thumbnails" checked>
                            <label class="custom-control-label" for="generate_thumbnails">Tạo hình ảnh thu nhỏ</label>
                        </div>
                        <small class="form-text text-muted">Tạo các phiên bản thu nhỏ của hình ảnh để sử dụng trong danh sách và trang chủ.</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-compress mr-1"></i> Tối ưu tất cả hình ảnh
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-database mr-2"></i> Thông Tin Bộ Nhớ Đệm
                </h2>
            </div>
            <div class="admin-card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Kích thước bộ nhớ đệm:</span>
                        <span class="font-weight-bold"><?php echo format_size($cache_size); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Số lượng tệp cache:</span>
                        <span class="font-weight-bold"><?php echo number_format($cache_files); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Trạng thái:</span>
                        <span class="badge badge-<?php echo $enable_cache == '1' ? 'success' : 'warning'; ?>">
                            <?php echo $enable_cache == '1' ? 'Đang hoạt động' : 'Đã tắt'; ?>
                        </span>
                    </li>
                </ul>
                
                <form method="post" action="performance.php" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="clear_cache">
                    
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i> Xóa tất cả bộ nhớ đệm
                    </button>
                </form>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-info-circle mr-2"></i> Mẹo Tối Ưu Hiệu Suất
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="performance-tips">
                    <div class="performance-tip">
                        <h5><i class="fas fa-image text-primary mr-2"></i> Tối ưu hình ảnh</h5>
                        <p>Nén và thay đổi kích thước hình ảnh trước khi tải lên. Sử dụng định dạng WebP thay vì JPEG hoặc PNG khi có thể.</p>
                    </div>
                    
                    <div class="performance-tip mt-3">
                        <h5><i class="fas fa-code text-primary mr-2"></i> Tối thiểu hóa JavaScript</h5>
                        <p>Tải JavaScript không đồng bộ và kết hợp nhiều tệp JS thành một tệp duy nhất để giảm số lượng yêu cầu HTTP.</p>
                    </div>
                    
                    <div class="performance-tip mt-3">
                        <h5><i class="fas fa-server text-primary mr-2"></i> Sử dụng CDN</h5>
                        <p>Sử dụng mạng phân phối nội dung (CDN) để phân phối nội dung tĩnh như hình ảnh, CSS và JavaScript.</p>
                    </div>
                    
                    <div class="performance-tip mt-3">
                        <h5><i class="fas fa-database text-primary mr-2"></i> Tối ưu cơ sở dữ liệu</h5>
                        <p>Tối ưu truy vấn cơ sở dữ liệu và thêm các chỉ mục cho các cột được tìm kiếm thường xuyên.</p>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <a href="https://web.dev/performance-optimizing-content-efficiency/" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem thêm mẹo tối ưu
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-tachometer-alt mr-2"></i> Kiểm Tra Hiệu Suất
                </h2>
            </div>
            <div class="admin-card-body">
                <p>Kiểm tra hiệu suất trang web của bạn bằng các công cụ trực tuyến:</p>
                
                <div class="list-group">
                    <a href="https://pagespeed.web.dev/" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Google PageSpeed Insights</h5>
                            <small><i class="fas fa-external-link-alt"></i></small>
                        </div>
                        <p class="mb-1">Phân tích hiệu suất và đưa ra các đề xuất cải thiện.</p>
                    </a>
                    
                    <a href="https://www.webpagetest.org/" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">WebPageTest</h5>
                            <small><i class="fas fa-external-link-alt"></i></small>
                        </div>
                        <p class="mb-1">Kiểm tra hiệu suất chi tiết từ nhiều vị trí và trình duyệt khác nhau.</p>
                    </a>
                    
                    <a href="https://gtmetrix.com/" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">GTmetrix</h5>
                            <small><i class="fas fa-external-link-alt"></i></small>
                        </div>
                        <p class="mb-1">Phân tích tốc độ tải trang và đưa ra các đề xuất cải thiện.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript để xử lý hiển thị các tùy chọn tối ưu
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Hiển thị/ẩn các tùy chọn tối ưu dựa trên trạng thái của checkbox tối ưu
    function toggleOptimizationSettings() {
        const enableMinify = document.getElementById("enable_minify").checked;
        const optimizationSettings = document.querySelector(".optimization-settings");
        
        if (enableMinify) {
            optimizationSettings.style.display = "block";
        } else {
            optimizationSettings.style.display = "none";
        }
    }
    
    // Kiểm tra trạng thái ban đầu
    toggleOptimizationSettings();
    
    // Thêm sự kiện change
    document.getElementById("enable_minify").addEventListener("change", toggleOptimizationSettings);
    
    // Disable các tùy chọn cache nếu cache bị tắt
    function toggleCacheSettings() {
        const enableCache = document.getElementById("enable_cache").checked;
        const cacheLifetime = document.getElementById("cache_lifetime");
        
        cacheLifetime.disabled = !enableCache;
    }
    
    // Kiểm tra trạng thái ban đầu
    toggleCacheSettings();
    
    // Thêm sự kiện change
    document.getElementById("enable_cache").addEventListener("change", toggleCacheSettings);
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>