<?php
// Trang cài đặt hệ thống
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Kết nối database
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Đọc cài đặt hiện tại
    $config_file = '../config.json';
    $config = json_decode(file_get_contents($config_file), true);
    
    // Xử lý cập nhật cài đặt
    if (isset($_POST['save_settings'])) {
        // Cài đặt chung
        $site_title = trim($_POST['site_title']);
        $site_description = trim($_POST['site_description']);
        $site_keywords = trim($_POST['site_keywords']);
        $admin_email = trim($_POST['admin_email']);
        $items_per_page = (int)$_POST['items_per_page'];
        $default_language = trim($_POST['default_language']);
        $default_theme = trim($_POST['default_theme']);
        
        // SEO & Social
        $google_analytics = trim($_POST['google_analytics']);
        $facebook_page = trim($_POST['facebook_page']);
        $twitter_username = trim($_POST['twitter_username']);
        
        // Xác thực & Bảo mật
        $allow_registration = isset($_POST['allow_registration']) ? true : false;
        $email_verification = isset($_POST['email_verification']) ? true : false;
        $captcha_enabled = isset($_POST['captcha_enabled']) ? true : false;
        $auto_approve_comments = isset($_POST['auto_approve_comments']) ? true : false;
        
        // Tùy chọn nâng cao
        $maintenance_mode = isset($_POST['maintenance_mode']) ? true : false;
        $maintenance_message = trim($_POST['maintenance_message'] ?? 'Trang web đang được bảo trì. Vui lòng quay lại sau!');
        $seasonal_theme_enabled = isset($_POST['seasonal_theme_enabled']) ? true : false;
        $active_seasonal_theme = trim($_POST['active_seasonal_theme'] ?? 'none');
        
        // Cập nhật cấu hình
        $config['site']['title'] = $site_title;
        $config['site']['description'] = $site_description;
        $config['site']['keywords'] = $site_keywords;
        $config['site']['admin_email'] = $admin_email;
        $config['site']['items_per_page'] = $items_per_page;
        $config['site']['default_language'] = $default_language;
        $config['site']['default_theme'] = $default_theme;
        
        $config['seo']['google_analytics'] = $google_analytics;
        $config['social']['facebook_page'] = $facebook_page;
        $config['social']['twitter_username'] = $twitter_username;
        
        $config['auth']['allow_registration'] = $allow_registration;
        $config['auth']['email_verification'] = $email_verification;
        $config['auth']['captcha_enabled'] = $captcha_enabled;
        $config['comments']['auto_approve'] = $auto_approve_comments;
        
        // Cài đặt nâng cao
        $config['site']['maintenance_mode'] = $maintenance_mode;
        $config['site']['maintenance_message'] = $maintenance_message;
        $config['site']['seasonal_theme_enabled'] = $seasonal_theme_enabled;
        $config['site']['active_seasonal_theme'] = $active_seasonal_theme;
        
        // Lưu cấu hình
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        
        $success_message = "Đã lưu cài đặt thành công!";
    }
    
    // Xử lý làm sạch bộ nhớ đệm
    if (isset($_POST['clear_cache'])) {
        // Xóa các file cache
        $cache_dir = '../cache';
        if (is_dir($cache_dir)) {
            $files = scandir($cache_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $file != '.gitkeep') {
                    unlink("$cache_dir/$file");
                }
            }
        }
        $success_message = "Đã xóa bộ nhớ đệm thành công!";
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Cài đặt hệ thống - Quản trị Lọc Phim';

// Bao gồm header quản trị
include 'admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">
                            <i class="fas fa-film"></i> Quản lý phim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-list"></i> Thể loại
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Người dùng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments"></i> Bình luận
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog"></i> Cài đặt
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="google_drive.php">
                            <i class="fab fa-google-drive"></i> Google Drive
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Báo cáo</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history"></i> Nhật ký
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Cài đặt hệ thống</h1>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Cài đặt chung</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab" aria-controls="seo" aria-selected="false">SEO & Social</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="auth-tab" data-bs-toggle="tab" data-bs-target="#auth" type="button" role="tab" aria-controls="auth" aria-selected="false">Xác thực & Bảo mật</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">Nâng cao</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="tab-content" id="settingsTabsContent">
                                    <!-- Cài đặt chung -->
                                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                        <div class="mb-3">
                                            <label for="site_title" class="form-label">Tên website</label>
                                            <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($config['site']['title'] ?? 'Lọc Phim'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_description" class="form-label">Mô tả website</label>
                                            <textarea class="form-control" id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($config['site']['description'] ?? 'Trang web xem phim và anime trực tuyến'); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_keywords" class="form-label">Từ khóa</label>
                                            <input type="text" class="form-control" id="site_keywords" name="site_keywords" value="<?php echo htmlspecialchars($config['site']['keywords'] ?? 'phim, anime, xem phim, phim HD'); ?>">
                                            <small class="text-muted">Phân cách bằng dấu phẩy</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="admin_email" class="form-label">Email quản trị</label>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($config['site']['admin_email'] ?? 'admin@locphim.com'); ?>" required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="items_per_page" class="form-label">Số mục trên mỗi trang</label>
                                                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="<?php echo (int)($config['site']['items_per_page'] ?? 24); ?>" min="10" max="100" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="default_language" class="form-label">Ngôn ngữ mặc định</label>
                                                    <select class="form-select" id="default_language" name="default_language">
                                                        <option value="vi" <?php echo ($config['site']['default_language'] ?? 'vi') == 'vi' ? 'selected' : ''; ?>>Tiếng Việt</option>
                                                        <option value="en" <?php echo ($config['site']['default_language'] ?? 'vi') == 'en' ? 'selected' : ''; ?>>English</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="default_theme" class="form-label">Giao diện mặc định</label>
                                                    <select class="form-select" id="default_theme" name="default_theme">
                                                        <option value="light" <?php echo ($config['site']['default_theme'] ?? 'dark') == 'light' ? 'selected' : ''; ?>>Sáng</option>
                                                        <option value="dark" <?php echo ($config['site']['default_theme'] ?? 'dark') == 'dark' ? 'selected' : ''; ?>>Tối</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO & Social -->
                                    <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                                        <div class="mb-3">
                                            <label for="google_analytics" class="form-label">Google Analytics ID</label>
                                            <input type="text" class="form-control" id="google_analytics" name="google_analytics" value="<?php echo htmlspecialchars($config['seo']['google_analytics'] ?? ''); ?>" placeholder="UA-XXXXX-Y">
                                            <small class="text-muted">Để trống nếu không sử dụng</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="facebook_page" class="form-label">URL Facebook Page</label>
                                            <input type="text" class="form-control" id="facebook_page" name="facebook_page" value="<?php echo htmlspecialchars($config['social']['facebook_page'] ?? ''); ?>" placeholder="https://facebook.com/locphim">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="twitter_username" class="form-label">Twitter Username</label>
                                            <div class="input-group">
                                                <span class="input-group-text">@</span>
                                                <input type="text" class="form-control" id="twitter_username" name="twitter_username" value="<?php echo htmlspecialchars($config['social']['twitter_username'] ?? ''); ?>" placeholder="locphim">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Xác thực & Bảo mật -->
                                    <div class="tab-pane fade" id="auth" role="tabpanel" aria-labelledby="auth-tab">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="allow_registration" name="allow_registration" <?php echo ($config['auth']['allow_registration'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="allow_registration">Cho phép đăng ký tài khoản mới</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="email_verification" name="email_verification" <?php echo ($config['auth']['email_verification'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_verification">Yêu cầu xác minh email</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="captcha_enabled" name="captcha_enabled" <?php echo ($config['auth']['captcha_enabled'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="captcha_enabled">Bật CAPTCHA khi đăng nhập/đăng ký</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_approve_comments" name="auto_approve_comments" <?php echo ($config['comments']['auto_approve'] ?? false) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="auto_approve_comments">Tự động phê duyệt bình luận</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Nâng cao -->
                                    <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Cảnh báo: Các cài đặt trong phần này có thể ảnh hưởng đến hiệu suất và hoạt động của trang web.
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-header">Bộ nhớ đệm</div>
                                            <div class="card-body">
                                                <p>Xóa bộ nhớ đệm sẽ làm mới các file cache, nhưng có thể làm chậm trang web cho đến khi cache được xây dựng lại.</p>
                                                <button type="submit" name="clear_cache" class="btn btn-warning">
                                                    <i class="fas fa-trash"></i> Xóa bộ nhớ đệm
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-header">Chế độ bảo trì</div>
                                            <div class="card-body">
                                                <p>Khi kích hoạt chế độ bảo trì, người dùng thông thường sẽ thấy trang thông báo bảo trì. Các tài khoản quản trị viên vẫn có thể truy cập bình thường.</p>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo (!empty($config['site']['maintenance_mode'])) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="maintenance_mode">Kích hoạt chế độ bảo trì</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="maintenance_message" class="form-label">Thông báo bảo trì</label>
                                                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo htmlspecialchars($config['site']['maintenance_message'] ?? 'Trang web đang được bảo trì. Vui lòng quay lại sau!'); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-header">Tùy chọn giao diện theo mùa</div>
                                            <div class="card-body">
                                                <p>Kích hoạt giao diện theo mùa sẽ áp dụng theme đặc biệt cho các dịp lễ, sự kiện.</p>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="seasonal_theme_enabled" name="seasonal_theme_enabled" <?php echo (!empty($config['site']['seasonal_theme_enabled'])) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="seasonal_theme_enabled">Kích hoạt giao diện theo mùa</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="active_seasonal_theme" class="form-label">Giao diện theo mùa hiện tại</label>
                                                    <select class="form-select" id="active_seasonal_theme" name="active_seasonal_theme">
                                                        <option value="none" <?php echo ($config['site']['active_seasonal_theme'] ?? 'none') == 'none' ? 'selected' : ''; ?>>Không sử dụng</option>
                                                        <option value="christmas" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == 'christmas' ? 'selected' : ''; ?>>Giáng sinh</option>
                                                        <option value="tet" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == 'tet' ? 'selected' : ''; ?>>Tết âm lịch</option>
                                                        <option value="halloween" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == 'halloween' ? 'selected' : ''; ?>>Halloween</option>
                                                        <option value="trung-thu" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == 'trung-thu' ? 'selected' : ''; ?>>Trung Thu</option>
                                                        <option value="quoc-khanh" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == 'quoc-khanh' ? 'selected' : ''; ?>>Quốc Khánh (2/9)</option>
                                                        <option value="30-4" <?php echo ($config['site']['active_seasonal_theme'] ?? '') == '30-4' ? 'selected' : ''; ?>>Giải Phóng Miền Nam (30/4)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-grid">
                                    <button type="submit" name="save_settings" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu cài đặt
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">Thông tin hệ thống</div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    PHP Version
                                    <span class="badge bg-primary"><?php echo phpversion(); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    SQLite Version
                                    <span class="badge bg-primary"><?php echo $db->query('SELECT sqlite_version()')->fetchColumn(); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Database Size
                                    <span class="badge bg-primary"><?php echo round(filesize($db_file) / 1024 / 1024, 2); ?> MB</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Server Time
                                    <span class="badge bg-primary"><?php echo date('Y-m-d H:i:s'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">Hỗ trợ & Tài liệu</div>
                        <div class="card-body">
                            <p>Nếu bạn cần trợ giúp hoặc hướng dẫn sử dụng hệ thống:</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-book"></i> <a href="#" target="_blank">Tài liệu hướng dẫn</a></li>
                                <li><i class="fas fa-question-circle"></i> <a href="#" target="_blank">Câu hỏi thường gặp</a></li>
                                <li><i class="fas fa-envelope"></i> <a href="mailto:support@locphim.com">Liên hệ hỗ trợ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'admin_footer.php'; ?>