<?php
/**
 * Trang quản lý cài đặt chung
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Cài Đặt Chung';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý cài đặt
$admin = require_admin_permission('manage_settings');

// Các nhóm cài đặt
$setting_groups = [
    'general' => 'Cài đặt chung',
    'appearance' => 'Giao diện',
    'comments' => 'Bình luận',
    'video' => 'Video',
    'homepage' => 'Trang chủ',
    'pagination' => 'Phân trang',
    'contact' => 'Liên hệ',
    'social' => 'Mạng xã hội',
    'seo' => 'SEO và Meta'
];

// Xử lý lưu cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy nhóm cài đặt hiện tại
    $current_group = $_POST['group'] ?? 'general';
    
    // Cập nhật các cài đặt
    foreach ($_POST as $key => $value) {
        // Bỏ qua các field không phải cài đặt
        if (in_array($key, ['csrf_token', 'group'])) {
            continue;
        }
        
        // Lưu cài đặt
        update_setting($key, $value);
    }
    
    // Ghi log
    log_admin_action('update_settings', "Cập nhật nhóm cài đặt: $current_group");
    
    // Hiển thị thông báo thành công
    set_flash_message('success', 'Đã lưu các cài đặt thành công!');
    
    // Chuyển hướng để tránh gửi lại form
    header("Location: settings.php?group=$current_group");
    exit;
}

// Xác định nhóm cài đặt hiện tại
$current_group = isset($_GET['group']) && array_key_exists($_GET['group'], $setting_groups) 
                ? $_GET['group'] 
                : 'general';

// Lấy tất cả cài đặt của nhóm hiện tại
$sql = "SELECT * FROM settings WHERE \"group\" = ? ORDER BY id";
$result = db_query($sql, [$current_group]);

$settings = [];
if (get_config('db.type') === 'postgresql') {
    while ($row = pg_fetch_assoc($result)) {
        $settings[$row['key']] = $row;
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['key']] = $row;
    }
}

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Cài Đặt Hệ Thống</h1>
        <p class="admin-page-subtitle">Quản lý các cài đặt cơ bản của trang web</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="row no-gutters">
            <div class="col-md-3 border-right">
                <div class="settings-nav">
                    <div class="list-group list-group-flush">
                        <?php foreach ($setting_groups as $group_key => $group_name): ?>
                            <a href="settings.php?group=<?php echo $group_key; ?>" class="list-group-item list-group-item-action <?php echo $current_group === $group_key ? 'active' : ''; ?>">
                                <i class="fas fa-<?php echo get_group_icon($group_key); ?> mr-2"></i> <?php echo $group_name; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="p-4">
                    <h2 class="mb-4"><?php echo $setting_groups[$current_group]; ?></h2>
                    
                    <form method="post" action="settings.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="group" value="<?php echo $current_group; ?>">
                        
                        <?php if ($current_group === 'general'): ?>
                            <div class="form-group">
                                <label for="site_name">Tên trang web</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo get_setting('site_name', 'Lọc Phim'); ?>">
                                <small class="form-text text-muted">Tên trang web sẽ được hiển thị trên tiêu đề và các vị trí khác.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Mô tả trang web</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo get_setting('site_description', 'Trang xem phim và anime trực tuyến'); ?></textarea>
                                <small class="form-text text-muted">Mô tả ngắn gọn về trang web của bạn.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_logo">Logo trang web</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="site_logo" name="site_logo" value="<?php echo get_setting('site_logo', ''); ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#mediaModal" data-field="site_logo">Chọn</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Đường dẫn đến logo trang web. Kích thước đề xuất: 200x50px.</small>
                                <?php if (get_setting('site_logo')): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo get_setting('site_logo'); ?>" alt="Site Logo" style="max-height: 50px;" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_favicon">Favicon</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="site_favicon" name="site_favicon" value="<?php echo get_setting('site_favicon', ''); ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#mediaModal" data-field="site_favicon">Chọn</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Đường dẫn đến favicon. Kích thước đề xuất: 32x32px.</small>
                                <?php if (get_setting('site_favicon')): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo get_setting('site_favicon'); ?>" alt="Favicon" class="img-thumbnail" style="max-height: 32px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="footer_text">Văn bản footer</label>
                                <textarea class="form-control" id="footer_text" name="footer_text" rows="2"><?php echo get_setting('footer_text', '© ' . date('Y') . ' Lọc Phim. Tất cả quyền được bảo lưu.'); ?></textarea>
                                <small class="form-text text-muted">Văn bản hiển thị ở cuối trang.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" value="1" <?php echo get_setting('maintenance_mode') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="maintenance_mode">Bật chế độ bảo trì</label>
                                </div>
                                <small class="form-text text-muted">Khi bật, trang web sẽ hiển thị thông báo bảo trì cho tất cả người dùng trừ quản trị viên.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="maintenance_message">Thông báo bảo trì</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo get_setting('maintenance_message', 'Trang web đang được bảo trì. Vui lòng quay lại sau.'); ?></textarea>
                                <small class="form-text text-muted">Thông báo hiển thị cho người dùng khi trang web trong chế độ bảo trì.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'appearance'): ?>
                            <div class="form-group">
                                <label for="theme_color">Màu chủ đề</label>
                                <input type="color" class="form-control" id="theme_color" name="theme_color" value="<?php echo get_setting('theme_color', '#007bff'); ?>" style="height: 40px;">
                                <small class="form-text text-muted">Màu chủ đạo cho trang web.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="dark_mode" name="dark_mode" value="1" <?php echo get_setting('dark_mode') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="dark_mode">Bật chế độ tối mặc định</label>
                                </div>
                                <small class="form-text text-muted">Trang web sẽ được hiển thị ở chế độ tối theo mặc định.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="custom_css">CSS tùy chỉnh</label>
                                <textarea class="form-control" id="custom_css" name="custom_css" rows="5"><?php echo get_setting('custom_css', ''); ?></textarea>
                                <small class="form-text text-muted">Thêm CSS tùy chỉnh cho trang web.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'comments'): ?>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="allow_comments" name="allow_comments" value="1" <?php echo get_setting('allow_comments', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="allow_comments">Cho phép bình luận</label>
                                </div>
                                <small class="form-text text-muted">Cho phép người dùng đăng bình luận trên video và tập phim.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="comment_moderation" name="comment_moderation" value="1" <?php echo get_setting('comment_moderation') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="comment_moderation">Kiểm duyệt bình luận</label>
                                </div>
                                <small class="form-text text-muted">Khi bật, bình luận mới sẽ cần được phê duyệt trước khi hiển thị.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="comments_per_page">Số bình luận mỗi trang</label>
                                <input type="number" class="form-control" id="comments_per_page" name="comments_per_page" value="<?php echo get_setting('comments_per_page', '10'); ?>" min="5" max="100">
                                <small class="form-text text-muted">Số lượng bình luận được hiển thị trên mỗi trang.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'video'): ?>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="show_related_videos" name="show_related_videos" value="1" <?php echo get_setting('show_related_videos', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="show_related_videos">Hiển thị video liên quan</label>
                                </div>
                                <small class="form-text text-muted">Hiển thị danh sách video liên quan khi xem video.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="auto_play_next" name="auto_play_next" value="1" <?php echo get_setting('auto_play_next', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="auto_play_next">Tự động phát tập tiếp theo</label>
                                </div>
                                <small class="form-text text-muted">Tự động phát tập tiếp theo khi tập hiện tại kết thúc.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="default_video_quality">Chất lượng video mặc định</label>
                                <select class="form-control" id="default_video_quality" name="default_video_quality">
                                    <option value="360p" <?php echo get_setting('default_video_quality') === '360p' ? 'selected' : ''; ?>>360p</option>
                                    <option value="480p" <?php echo get_setting('default_video_quality') === '480p' ? 'selected' : ''; ?>>480p</option>
                                    <option value="720p" <?php echo get_setting('default_video_quality', '720p') === '720p' ? 'selected' : ''; ?>>720p (HD)</option>
                                    <option value="1080p" <?php echo get_setting('default_video_quality') === '1080p' ? 'selected' : ''; ?>>1080p (Full HD)</option>
                                    <option value="auto" <?php echo get_setting('default_video_quality') === 'auto' ? 'selected' : ''; ?>>Tự động</option>
                                </select>
                                <small class="form-text text-muted">Chất lượng video mặc định khi người dùng xem video.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'homepage'): ?>
                            <div class="form-group">
                                <label for="homepage_featured_limit">Số lượng video nổi bật</label>
                                <input type="number" class="form-control" id="homepage_featured_limit" name="homepage_featured_limit" value="<?php echo get_setting('homepage_featured_limit', '5'); ?>" min="1" max="10">
                                <small class="form-text text-muted">Số lượng video nổi bật hiển thị trên trang chủ.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="homepage_latest_limit">Số lượng video mới nhất</label>
                                <input type="number" class="form-control" id="homepage_latest_limit" name="homepage_latest_limit" value="<?php echo get_setting('homepage_latest_limit', '12'); ?>" min="4" max="24">
                                <small class="form-text text-muted">Số lượng video mới nhất hiển thị trên trang chủ.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="homepage_popular_limit">Số lượng video phổ biến</label>
                                <input type="number" class="form-control" id="homepage_popular_limit" name="homepage_popular_limit" value="<?php echo get_setting('homepage_popular_limit', '12'); ?>" min="4" max="24">
                                <small class="form-text text-muted">Số lượng video phổ biến hiển thị trên trang chủ.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'pagination'): ?>
                            <div class="form-group">
                                <label for="items_per_page">Số mục trên mỗi trang</label>
                                <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="<?php echo get_setting('items_per_page', '20'); ?>" min="10" max="100">
                                <small class="form-text text-muted">Số lượng mục hiển thị trên mỗi trang (áp dụng cho danh sách phim, danh mục, v.v.).</small>
                            </div>
                        
                        <?php elseif ($current_group === 'contact'): ?>
                            <div class="form-group">
                                <label for="admin_email">Email quản trị viên</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo get_setting('admin_email', 'admin@locphim.com'); ?>">
                                <small class="form-text text-muted">Email nhận thông báo từ hệ thống.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Email liên hệ</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo get_setting('contact_email', 'contact@locphim.com'); ?>">
                                <small class="form-text text-muted">Email hiển thị trên trang liên hệ và nhận phản hồi từ người dùng.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'social'): ?>
                            <div class="form-group">
                                <label for="social_facebook">Facebook</label>
                                <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="<?php echo get_setting('social_facebook', ''); ?>" placeholder="https://facebook.com/yourpage">
                                <small class="form-text text-muted">Liên kết đến trang Facebook.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_twitter">Twitter</label>
                                <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?php echo get_setting('social_twitter', ''); ?>" placeholder="https://twitter.com/yourusername">
                                <small class="form-text text-muted">Liên kết đến trang Twitter.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_instagram">Instagram</label>
                                <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="<?php echo get_setting('social_instagram', ''); ?>" placeholder="https://instagram.com/yourusername">
                                <small class="form-text text-muted">Liên kết đến trang Instagram.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_youtube">YouTube</label>
                                <input type="url" class="form-control" id="social_youtube" name="social_youtube" value="<?php echo get_setting('social_youtube', ''); ?>" placeholder="https://youtube.com/yourchannel">
                                <small class="form-text text-muted">Liên kết đến kênh YouTube.</small>
                            </div>
                        
                        <?php elseif ($current_group === 'seo'): ?>
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
                                <label for="google_site_verification">Google Site Verification</label>
                                <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" value="<?php echo get_setting('google_site_verification', ''); ?>" placeholder="Mã xác minh từ Google Search Console">
                                <small class="form-text text-muted">Mã xác minh từ Google Search Console.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="analytics_code">Mã Analytics</label>
                                <textarea class="form-control" id="analytics_code" name="analytics_code" rows="5" placeholder="<!-- Google Analytics Code -->"><?php echo get_setting('analytics_code', ''); ?></textarea>
                                <small class="form-text text-muted">Mã theo dõi Google Analytics hoặc các dịch vụ phân tích khác.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_sitemap" name="enable_sitemap" value="1" <?php echo get_setting('enable_sitemap', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_sitemap">Tạo Sitemap tự động</label>
                                </div>
                                <small class="form-text text-muted">Tự động tạo và cập nhật sitemap XML cho các bộ máy tìm kiếm.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_seo_urls" name="enable_seo_urls" value="1" <?php echo get_setting('enable_seo_urls', '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_seo_urls">Bật URL thân thiện với SEO</label>
                                </div>
                                <small class="form-text text-muted">Sử dụng URL dễ đọc và thân thiện với các bộ máy tìm kiếm.</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Lưu cài đặt
                            </button>
                            <a href="settings.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-redo mr-1"></i> Đặt lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Hàm lấy icon cho nhóm cài đặt
function get_group_icon($group) {
    $icons = [
        'general' => 'cog',
        'appearance' => 'palette',
        'comments' => 'comments',
        'video' => 'film',
        'homepage' => 'home',
        'pagination' => 'list',
        'contact' => 'envelope',
        'social' => 'share-alt',
        'seo' => 'search'
    ];
    
    return $icons[$group] ?? 'cog';
}

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>