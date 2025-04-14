<?php
/**
 * Trang quản lý SEO và tối ưu hóa
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'SEO & Tối Ưu Hóa';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý cài đặt
$admin = require_admin_permission('manage_settings');

// Xử lý lưu cài đặt SEO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy tab hiện tại
    $current_tab = $_POST['tab'] ?? 'general';
    
    // Cập nhật các cài đặt
    foreach ($_POST as $key => $value) {
        // Bỏ qua các field không phải cài đặt
        if (in_array($key, ['csrf_token', 'tab', 'action'])) {
            continue;
        }
        
        // Lưu cài đặt
        update_setting($key, $value);
    }
    
    // Kiểm tra nếu có hành động đặc biệt
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_sitemap') {
        // Gọi hàm tạo sitemap
        $result = generate_sitemap();
        
        if ($result['success']) {
            set_flash_message('success', 'Đã tạo Sitemap thành công! ' . $result['message']);
        } else {
            set_flash_message('error', 'Lỗi khi tạo Sitemap: ' . $result['message']);
        }
    } elseif ($action === 'clear_cache') {
        // Gọi hàm xóa cache
        $result = clear_cache();
        
        if ($result['success']) {
            set_flash_message('success', 'Đã xóa cache thành công! ' . $result['message']);
        } else {
            set_flash_message('error', 'Lỗi khi xóa cache: ' . $result['message']);
        }
    } else {
        // Ghi log
        log_admin_action('update_seo', "Cập nhật cài đặt SEO - tab: $current_tab");
        
        // Hiển thị thông báo thành công
        set_flash_message('success', 'Đã lưu cài đặt SEO thành công!');
    }
    
    // Chuyển hướng để tránh gửi lại form
    header("Location: seo.php?tab=$current_tab");
    exit;
}

// Xác định tab hiện tại
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
$valid_tabs = ['general', 'meta', 'sitemap', 'robots', 'optimization', 'analytics'];

if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'general';
}

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">SEO & Tối Ưu Hóa</h1>
        <p class="admin-page-subtitle">Quản lý cài đặt SEO và tối ưu hóa trang web</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <ul class="nav nav-tabs mb-4" id="seoTabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'general' ? 'active' : ''; ?>" href="seo.php?tab=general">
                    <i class="fas fa-cog mr-1"></i> Cài Đặt Chung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'meta' ? 'active' : ''; ?>" href="seo.php?tab=meta">
                    <i class="fas fa-tags mr-1"></i> Meta Tags
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'sitemap' ? 'active' : ''; ?>" href="seo.php?tab=sitemap">
                    <i class="fas fa-sitemap mr-1"></i> Sitemap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'robots' ? 'active' : ''; ?>" href="seo.php?tab=robots">
                    <i class="fas fa-robot mr-1"></i> Robots.txt
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'optimization' ? 'active' : ''; ?>" href="seo.php?tab=optimization">
                    <i class="fas fa-tachometer-alt mr-1"></i> Tối Ưu Hóa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab === 'analytics' ? 'active' : ''; ?>" href="seo.php?tab=analytics">
                    <i class="fas fa-chart-line mr-1"></i> Analytics
                </a>
            </li>
        </ul>
        
        <div class="tab-content">
            <?php if ($current_tab === 'general'): ?>
                <form method="post" action="seo.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="tab" value="general">
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_seo" name="enable_seo" value="1" <?php echo get_setting('enable_seo', '1') == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_seo">Bật tính năng SEO</label>
                        </div>
                        <small class="form-text text-muted">Bật hoặc tắt tất cả các tính năng SEO.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_seo_urls" name="enable_seo_urls" value="1" <?php echo get_setting('enable_seo_urls', '1') == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_seo_urls">Bật URL thân thiện với SEO</label>
                        </div>
                        <small class="form-text text-muted">Sử dụng URL dễ đọc và thân thiện với các bộ máy tìm kiếm.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_title_format">Định dạng tiêu đề trang</label>
                        <input type="text" class="form-control" id="site_title_format" name="site_title_format" value="<?php echo get_setting('site_title_format', '{page_title} | {site_name}'); ?>">
                        <small class="form-text text-muted">
                            Định dạng tiêu đề cho các trang web. Sử dụng các biến: {page_title}, {site_name}, {category}, {tag}, {separator}.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="title_separator">Dấu phân cách tiêu đề</label>
                        <select class="form-control" id="title_separator" name="title_separator">
                            <option value="|" <?php echo get_setting('title_separator', '|') === '|' ? 'selected' : ''; ?>>Thanh đứng (|)</option>
                            <option value="-" <?php echo get_setting('title_separator') === '-' ? 'selected' : ''; ?>>Gạch ngang (-)</option>
                            <option value="&raquo;" <?php echo get_setting('title_separator') === '&raquo;' ? 'selected' : ''; ?>>Dấu mũi tên (&raquo;)</option>
                            <option value="&bull;" <?php echo get_setting('title_separator') === '&bull;' ? 'selected' : ''; ?>>Dấu chấm tròn (&bull;)</option>
                            <option value=":" <?php echo get_setting('title_separator') === ':' ? 'selected' : ''; ?>>Dấu hai chấm (:)</option>
                        </select>
                        <small class="form-text text-muted">Ký tự sử dụng để phân tách các phần trong tiêu đề trang.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="canonical_url">URL Chính Thức (Canonical URL)</label>
                        <input type="url" class="form-control" id="canonical_url" name="canonical_url" value="<?php echo get_setting('canonical_url', ''); ?>" placeholder="https://locphim.com">
                        <small class="form-text text-muted">URL chính thức của trang web. Để trống để sử dụng URL hiện tại.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="anime_keywords">Từ khóa Anime</label>
                        <textarea class="form-control" id="anime_keywords" name="anime_keywords" rows="3"><?php echo get_setting('anime_keywords', 'anime, phim hoạt hình Nhật Bản, anime vietsub, xem anime, otaku, manga, anime mùa, anime mới nhất, anime hay'); ?></textarea>
                        <small class="form-text text-muted">Các từ khóa liên quan đến anime, được phân tách bằng dấu phẩy.</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Lưu cài đặt
                        </button>
                    </div>
                </form>
            
            <?php elseif ($current_tab === 'meta'): ?>
                <form method="post" action="seo.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="tab" value="meta">
                    
                    <div class="form-group">
                        <label for="meta_keywords">Từ khóa Meta (Keywords)</label>
                        <textarea class="form-control" id="meta_keywords" name="meta_keywords" rows="3"><?php echo get_setting('meta_keywords', 'anime, phim, phim hoạt hình, xem phim, xem anime'); ?></textarea>
                        <small class="form-text text-muted">Các từ khóa meta được phân tách bằng dấu phẩy. Các từ khóa này sẽ được sử dụng trên tất cả các trang.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Mô tả Meta (Description)</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo get_setting('meta_description', get_setting('site_description', 'Trang xem phim và anime trực tuyến')); ?></textarea>
                        <small class="form-text text-muted">Mô tả meta mặc định cho trang web. Được sử dụng khi không có mô tả cụ thể cho trang.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="og_image">Hình ảnh OpenGraph mặc định</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="og_image" name="og_image" value="<?php echo get_setting('og_image', ''); ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#mediaModal" data-field="og_image">Chọn</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Hình ảnh mặc định được sử dụng khi chia sẻ trang lên mạng xã hội. Kích thước đề xuất: 1200x630px.</small>
                        <?php if (get_setting('og_image')): ?>
                            <div class="mt-2">
                                <img src="<?php echo get_setting('og_image'); ?>" alt="OpenGraph Image" style="max-height: 120px;" class="img-thumbnail">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_card_type">Loại Twitter Card</label>
                        <select class="form-control" id="twitter_card_type" name="twitter_card_type">
                            <option value="summary" <?php echo get_setting('twitter_card_type', 'summary') === 'summary' ? 'selected' : ''; ?>>Summary</option>
                            <option value="summary_large_image" <?php echo get_setting('twitter_card_type') === 'summary_large_image' ? 'selected' : ''; ?>>Summary with Large Image</option>
                        </select>
                        <small class="form-text text-muted">Loại thẻ Twitter được sử dụng khi chia sẻ trang lên Twitter.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_username">Tên người dùng Twitter</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@</span>
                            </div>
                            <input type="text" class="form-control" id="twitter_username" name="twitter_username" value="<?php echo get_setting('twitter_username', ''); ?>" placeholder="username">
                        </div>
                        <small class="form-text text-muted">Tên người dùng Twitter của bạn, không bao gồm ký tự @.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_meta_author" name="enable_meta_author" value="1" <?php echo get_setting('enable_meta_author', '1') == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_meta_author">Hiển thị thông tin tác giả trong meta</label>
                        </div>
                        <small class="form-text text-muted">Bật để hiển thị thông tin tác giả trong meta tags.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_og_meta" name="enable_og_meta" value="1" <?php echo get_setting('enable_og_meta', '1') == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_og_meta">Bật OpenGraph Meta Tags</label>
                        </div>
                        <small class="form-text text-muted">Bật để thêm OpenGraph meta tags cho mạng xã hội.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enable_twitter_cards" name="enable_twitter_cards" value="1" <?php echo get_setting('enable_twitter_cards', '1') == '1' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="enable_twitter_cards">Bật Twitter Cards</label>
                        </div>
                        <small class="form-text text-muted">Bật để thêm Twitter Card meta tags.</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Lưu cài đặt
                        </button>
                    </div>
                </form>
            
            <?php elseif ($current_tab === 'sitemap'): ?>
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="seo.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="tab" value="sitemap">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_sitemap" name="enable_sitemap" value="1" <?php echo get_setting('enable_sitemap', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_sitemap">Tạo Sitemap tự động</label>
                                </div>
                                <small class="form-text text-muted">Tự động tạo và cập nhật sitemap XML cho các bộ máy tìm kiếm.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="sitemap_frequency">Tần suất cập nhật</label>
                                <select class="form-control" id="sitemap_frequency" name="sitemap_frequency">
                                    <option value="always" <?php echo get_setting('sitemap_frequency', 'daily') === 'always' ? 'selected' : ''; ?>>Always</option>
                                    <option value="hourly" <?php echo get_setting('sitemap_frequency') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                                    <option value="daily" <?php echo get_setting('sitemap_frequency', 'daily') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo get_setting('sitemap_frequency') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo get_setting('sitemap_frequency') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="yearly" <?php echo get_setting('sitemap_frequency') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    <option value="never" <?php echo get_setting('sitemap_frequency') === 'never' ? 'selected' : ''; ?>>Never</option>
                                </select>
                                <small class="form-text text-muted">Tần suất cập nhật mặc định cho các trang trong sitemap.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="sitemap_priority">Độ ưu tiên mặc định</label>
                                <select class="form-control" id="sitemap_priority" name="sitemap_priority">
                                    <option value="1.0" <?php echo get_setting('sitemap_priority', '0.5') === '1.0' ? 'selected' : ''; ?>>1.0 (Cao nhất)</option>
                                    <option value="0.9" <?php echo get_setting('sitemap_priority') === '0.9' ? 'selected' : ''; ?>>0.9</option>
                                    <option value="0.8" <?php echo get_setting('sitemap_priority') === '0.8' ? 'selected' : ''; ?>>0.8</option>
                                    <option value="0.7" <?php echo get_setting('sitemap_priority') === '0.7' ? 'selected' : ''; ?>>0.7</option>
                                    <option value="0.6" <?php echo get_setting('sitemap_priority') === '0.6' ? 'selected' : ''; ?>>0.6</option>
                                    <option value="0.5" <?php echo get_setting('sitemap_priority', '0.5') === '0.5' ? 'selected' : ''; ?>>0.5 (Mặc định)</option>
                                    <option value="0.4" <?php echo get_setting('sitemap_priority') === '0.4' ? 'selected' : ''; ?>>0.4</option>
                                    <option value="0.3" <?php echo get_setting('sitemap_priority') === '0.3' ? 'selected' : ''; ?>>0.3</option>
                                    <option value="0.2" <?php echo get_setting('sitemap_priority') === '0.2' ? 'selected' : ''; ?>>0.2</option>
                                    <option value="0.1" <?php echo get_setting('sitemap_priority') === '0.1' ? 'selected' : ''; ?>>0.1 (Thấp nhất)</option>
                                </select>
                                <small class="form-text text-muted">Độ ưu tiên mặc định cho các trang trong sitemap.</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Bao gồm trong Sitemap</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_home" name="include_home" value="1" <?php echo get_setting('include_home', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="include_home">Trang chủ</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_videos" name="include_videos" value="1" <?php echo get_setting('include_videos', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="include_videos">Trang phim</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_episodes" name="include_episodes" value="1" <?php echo get_setting('include_episodes', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="include_episodes">Trang tập phim</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_categories" name="include_categories" value="1" <?php echo get_setting('include_categories', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="include_categories">Trang danh mục</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="include_static" name="include_static" value="1" <?php echo get_setting('include_static', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="include_static">Trang tĩnh (Giới thiệu, Điều khoản, v.v.)</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sitemap_exclude">Loại trừ URL</label>
                                <textarea class="form-control" id="sitemap_exclude" name="sitemap_exclude" rows="3" placeholder="Nhập các URL để loại trừ, mỗi URL một dòng"><?php echo get_setting('sitemap_exclude', ''); ?></textarea>
                                <small class="form-text text-muted">Danh sách các URL cần loại trừ khỏi sitemap, mỗi URL một dòng.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                
                                <button type="submit" name="action" value="generate_sitemap" class="btn btn-success ml-2">
                                    <i class="fas fa-sync mr-1"></i> Tạo Sitemap Ngay
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Thông tin Sitemap</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $sitemap_path = dirname(dirname(__FILE__)) . '/sitemap.xml';
                                $sitemap_url = dirname(dirname($_SERVER['REQUEST_URI'])) . '/sitemap.xml';
                                
                                if (file_exists($sitemap_path)) {
                                    $sitemap_size = filesize($sitemap_path);
                                    $sitemap_modified = filemtime($sitemap_path);
                                    $sitemap_urls = count_sitemap_urls($sitemap_path);
                                    ?>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Đường dẫn Sitemap:</span>
                                            <a href="<?php echo $sitemap_url; ?>" target="_blank"><?php echo $sitemap_url; ?></a>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Kích thước:</span>
                                            <span><?php echo format_size($sitemap_size); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Lần cập nhật cuối:</span>
                                            <span><?php echo date('d/m/Y H:i:s', $sitemap_modified); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Số lượng URL:</span>
                                            <span><?php echo $sitemap_urls; ?></span>
                                        </li>
                                    </ul>
                                <?php } else { ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> Sitemap chưa được tạo. Hãy nhấn nút "Tạo Sitemap Ngay" để tạo sitemap.
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Thông báo Sitemap</h5>
                            </div>
                            <div class="card-body">
                                <p>Sau khi tạo Sitemap, bạn nên thông báo cho các công cụ tìm kiếm về sitemap của bạn:</p>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Google Search Console:</span>
                                        <a href="https://search.google.com/search-console" target="_blank">Thông báo</a>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Bing Webmaster Tools:</span>
                                        <a href="https://www.bing.com/webmasters/" target="_blank">Thông báo</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            
            <?php elseif ($current_tab === 'robots'): ?>
                <form method="post" action="seo.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="tab" value="robots">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_robots_txt" name="enable_robots_txt" value="1" <?php echo get_setting('enable_robots_txt', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_robots_txt">Bật tệp robots.txt</label>
                                </div>
                                <small class="form-text text-muted">Tạo tệp robots.txt để kiểm soát việc thu thập thông tin của các bộ máy tìm kiếm.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="robots_txt_content">Nội dung tệp robots.txt</label>
                                <textarea class="form-control" id="robots_txt_content" name="robots_txt_content" rows="12" style="font-family: monospace;"><?php echo get_setting('robots_txt_content', "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /includes/\nDisallow: /setup-database.php\nDisallow: /install.php\n\nSitemap: " . get_site_url() . "/sitemap.xml"); ?></textarea>
                                <small class="form-text text-muted">Nội dung tệp robots.txt. Tệp này hướng dẫn các bộ máy tìm kiếm các trang nào nên hoặc không nên thu thập thông tin.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Trợ giúp</h5>
                                </div>
                                <div class="card-body">
                                    <h6>Hướng dẫn cơ bản:</h6>
                                    <ul>
                                        <li><strong>User-agent: *</strong> - Áp dụng cho tất cả các robot</li>
                                        <li><strong>Allow: /</strong> - Cho phép truy cập tất cả các trang</li>
                                        <li><strong>Disallow: /admin/</strong> - Chặn truy cập vào thư mục admin</li>
                                        <li><strong>Sitemap: URL</strong> - Chỉ định vị trí của sitemap</li>
                                    </ul>
                                    
                                    <h6>Ví dụ về robots.txt:</h6>
                                    <pre class="bg-light p-2" style="font-size: 12px;">User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/

Sitemap: https://locphim.com/sitemap.xml</pre>
                                    
                                    <p>
                                        <a href="https://developers.google.com/search/docs/advanced/robots/intro" target="_blank">Tìm hiểu thêm về robots.txt</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            
            <?php elseif ($current_tab === 'optimization'): ?>
                <form method="post" action="seo.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="tab" value="optimization">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-4">Tối ưu hóa trang web</h5>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_minify_html" name="enable_minify_html" value="1" <?php echo get_setting('enable_minify_html', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_minify_html">Tối ưu hóa HTML</label>
                                </div>
                                <small class="form-text text-muted">Loại bỏ khoảng trắng và nhận xét không cần thiết từ HTML để giảm kích thước trang.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_minify_css" name="enable_minify_css" value="1" <?php echo get_setting('enable_minify_css', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_minify_css">Tối ưu hóa CSS</label>
                                </div>
                                <small class="form-text text-muted">Kết hợp và nén các tệp CSS để giảm kích thước và số lượng yêu cầu HTTP.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_minify_js" name="enable_minify_js" value="1" <?php echo get_setting('enable_minify_js', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_minify_js">Tối ưu hóa JavaScript</label>
                                </div>
                                <small class="form-text text-muted">Kết hợp và nén các tệp JavaScript để giảm kích thước và số lượng yêu cầu HTTP.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_lazy_load" name="enable_lazy_load" value="1" <?php echo get_setting('enable_lazy_load', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_lazy_load">Bật tải lười biếng cho hình ảnh</label>
                                </div>
                                <small class="form-text text-muted">Chỉ tải hình ảnh khi chúng hiển thị trong khung nhìn, giúp cải thiện thời gian tải trang ban đầu.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_browser_caching" name="enable_browser_caching" value="1" <?php echo get_setting('enable_browser_caching', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_browser_caching">Bật bộ nhớ đệm trình duyệt</label>
                                </div>
                                <small class="form-text text-muted">Đặt tiêu đề HTTP để kích hoạt bộ nhớ đệm trình duyệt cho các tài nguyên tĩnh như CSS, JS và hình ảnh.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_gzip" name="enable_gzip" value="1" <?php echo get_setting('enable_gzip', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_gzip">Bật nén GZIP</label>
                                </div>
                                <small class="form-text text-muted">Bật nén GZIP để giảm kích thước tài nguyên được gửi đến trình duyệt.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="cache_lifetime">Thời gian sống của bộ nhớ đệm (giây)</label>
                                <input type="number" class="form-control" id="cache_lifetime" name="cache_lifetime" value="<?php echo get_setting('cache_lifetime', '3600'); ?>" min="0" max="86400">
                                <small class="form-text text-muted">Thời gian (tính bằng giây) mà bộ nhớ đệm sẽ được lưu trữ trước khi hết hạn. 0 = vô thời hạn.</small>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                
                                <button type="submit" name="action" value="clear_cache" class="btn btn-danger ml-2">
                                    <i class="fas fa-trash mr-1"></i> Xóa tất cả bộ nhớ đệm
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Thông tin bộ nhớ đệm</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $cache_dir = dirname(dirname(__FILE__)) . '/cache';
                                    $cache_size = 0;
                                    $cache_files = 0;
                                    
                                    if (file_exists($cache_dir) && is_dir($cache_dir)) {
                                        $objects = new RecursiveIteratorIterator(
                                            new RecursiveDirectoryIterator($cache_dir),
                                            RecursiveIteratorIterator::SELF_FIRST
                                        );
                                        
                                        foreach ($objects as $object) {
                                            if ($object->isFile()) {
                                                $cache_size += $object->getSize();
                                                $cache_files++;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Tổng dung lượng bộ nhớ đệm:</span>
                                            <span><?php echo format_size($cache_size); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Số lượng tệp trong bộ nhớ đệm:</span>
                                            <span><?php echo $cache_files; ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Mẹo tối ưu hóa</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Nén hình ảnh trước khi tải lên
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Sử dụng định dạng hình ảnh hiện đại (WebP, AVIF)
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Tối ưu hóa CSS và JavaScript
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Giảm thiểu số lượng yêu cầu HTTP
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Sử dụng CDN cho tài nguyên tĩnh
                                        </li>
                                    </ul>
                                    
                                    <a href="https://web.dev/performance-optimizing-content-efficiency/" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                        Tìm hiểu thêm về tối ưu hóa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            
            <?php elseif ($current_tab === 'analytics'): ?>
                <form method="post" action="seo.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="tab" value="analytics">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="google_analytics_id">ID Google Analytics</label>
                                <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" value="<?php echo get_setting('google_analytics_id', ''); ?>" placeholder="UA-XXXXXXXX-X hoặc G-XXXXXXXXXX">
                                <small class="form-text text-muted">ID Google Analytics của bạn (UA-XXXXXXXX-X cho Universal Analytics hoặc G-XXXXXXXXXX cho GA4).</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="google_tag_manager_id">ID Google Tag Manager</label>
                                <input type="text" class="form-control" id="google_tag_manager_id" name="google_tag_manager_id" value="<?php echo get_setting('google_tag_manager_id', ''); ?>" placeholder="GTM-XXXXXXX">
                                <small class="form-text text-muted">ID Google Tag Manager của bạn (GTM-XXXXXXX). Để trống nếu không sử dụng.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="google_site_verification">Mã xác minh Google Search Console</label>
                                <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" value="<?php echo get_setting('google_site_verification', ''); ?>" placeholder="Mã xác minh từ Google Search Console">
                                <small class="form-text text-muted">Mã xác minh từ Google Search Console (meta tag content value).</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="facebook_pixel_id">ID Facebook Pixel</label>
                                <input type="text" class="form-control" id="facebook_pixel_id" name="facebook_pixel_id" value="<?php echo get_setting('facebook_pixel_id', ''); ?>" placeholder="XXXXXXXXXXXXXXXX">
                                <small class="form-text text-muted">ID Facebook Pixel của bạn. Để trống nếu không sử dụng.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="analytics_code">Mã theo dõi tùy chỉnh</label>
                                <textarea class="form-control" id="analytics_code" name="analytics_code" rows="6" placeholder="<!-- Đặt mã theo dõi tùy chỉnh ở đây -->"><?php echo get_setting('analytics_code', ''); ?></textarea>
                                <small class="form-text text-muted">Mã theo dõi tùy chỉnh sẽ được thêm vào trước thẻ đóng &lt;/head&gt;.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_analytics" name="enable_analytics" value="1" <?php echo get_setting('enable_analytics', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_analytics">Bật theo dõi phân tích</label>
                                </div>
                                <small class="form-text text-muted">Bật hoặc tắt tất cả các công cụ phân tích web.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="anonymize_ip" name="anonymize_ip" value="1" <?php echo get_setting('anonymize_ip', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="anonymize_ip">Ẩn danh hóa IP</label>
                                </div>
                                <small class="form-text text-muted">Kích hoạt tính năng ẩn danh IP trong Google Analytics.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="track_logged_in_users" name="track_logged_in_users" value="1" <?php echo get_setting('track_logged_in_users', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="track_logged_in_users">Theo dõi người dùng đã đăng nhập</label>
                                </div>
                                <small class="form-text text-muted">Nếu tắt, các công cụ phân tích sẽ không theo dõi quản trị viên và người dùng đã đăng nhập.</small>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Trợ giúp Analytics</h5>
                                </div>
                                <div class="card-body">
                                    <h6>Các liên kết hữu ích:</h6>
                                    <ul>
                                        <li><a href="https://analytics.google.com/" target="_blank">Google Analytics</a></li>
                                        <li><a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                                        <li><a href="https://tagmanager.google.com/" target="_blank">Google Tag Manager</a></li>
                                        <li><a href="https://www.facebook.com/business/help/952192354843755" target="_blank">Facebook Pixel</a></li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Tuân thủ GDPR:</h6>
                                    <p>Để tuân thủ GDPR và các quy định về quyền riêng tư, bạn nên:</p>
                                    <ul>
                                        <li>Sử dụng tính năng ẩn danh IP</li>
                                        <li>Bật thông báo cookie</li>
                                        <li>Chỉ thu thập dữ liệu khi có sự đồng ý</li>
                                        <li>Cập nhật chính sách bảo mật</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Hàm lấy URL của trang web
function get_site_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    return $protocol . $domain;
}

// Hàm định dạng kích thước
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// Hàm đếm số URL trong sitemap
function count_sitemap_urls($file) {
    if (!file_exists($file)) {
        return 0;
    }
    
    $content = file_get_contents($file);
    return substr_count($content, '<url>');
}

// Hàm tạo sitemap
function generate_sitemap() {
    // Đây chỉ là mô phỏng, trong thực tế sẽ thu thập URL từ cơ sở dữ liệu
    $home_url = get_site_url();
    
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    // Trang chủ
    $sitemap .= '  <url>' . PHP_EOL;
    $sitemap .= '    <loc>' . $home_url . '/</loc>' . PHP_EOL;
    $sitemap .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
    $sitemap .= '    <changefreq>' . get_setting('sitemap_frequency', 'daily') . '</changefreq>' . PHP_EOL;
    $sitemap .= '    <priority>1.0</priority>' . PHP_EOL;
    $sitemap .= '  </url>' . PHP_EOL;
    
    // Trang danh mục, trang phim, v.v. sẽ được thêm ở đây
    
    $sitemap .= '</urlset>';
    
    // Ghi sitemap vào tệp
    $sitemap_file = dirname(dirname(__FILE__)) . '/sitemap.xml';
    file_put_contents($sitemap_file, $sitemap);
    
    return [
        'success' => true,
        'message' => 'Sitemap đã được tạo và lưu tại ' . $sitemap_file
    ];
}

// Hàm xóa cache
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
        'message' => "Đã xóa $count tệp cache."
    ];
}

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>