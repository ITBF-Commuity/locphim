<?php
/**
 * Trang kiểm tra cài đặt hệ thống
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Kiểm Tra Hệ Thống';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản trị cao cấp
$admin = require_admin_permission('manage_settings');

// Kiểm tra và thu thập thông tin hệ thống
function check_server_requirements() {
    $requirements = [
        'php_version' => [
            'name' => 'Phiên bản PHP',
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'message' => 'Lọc Phim yêu cầu PHP 7.4.0 trở lên.'
        ],
        'pdo_extension' => [
            'name' => 'PDO Extension',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('pdo') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('pdo'),
            'message' => 'PDO Extension là bắt buộc để kết nối đến cơ sở dữ liệu.'
        ],
        'pdo_pgsql' => [
            'name' => 'PDO PostgreSQL',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('pdo_pgsql') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('pdo_pgsql'),
            'message' => 'PDO PostgreSQL Extension là bắt buộc nếu sử dụng PostgreSQL.'
        ],
        'pdo_mysql' => [
            'name' => 'PDO MySQL',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('pdo_mysql') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('pdo_mysql'),
            'message' => 'PDO MySQL Extension là bắt buộc nếu sử dụng MySQL.'
        ],
        'gd_extension' => [
            'name' => 'GD Extension',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('gd') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('gd'),
            'message' => 'GD Extension là bắt buộc để xử lý hình ảnh.'
        ],
        'curl_extension' => [
            'name' => 'cURL Extension',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('curl') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('curl'),
            'message' => 'cURL Extension là bắt buộc để gọi API bên ngoài.'
        ],
        'json_extension' => [
            'name' => 'JSON Extension',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('json') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('json'),
            'message' => 'JSON Extension là bắt buộc để xử lý dữ liệu JSON.'
        ],
        'mbstring_extension' => [
            'name' => 'Mbstring Extension',
            'required' => 'Kích hoạt',
            'current' => extension_loaded('mbstring') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => extension_loaded('mbstring'),
            'message' => 'Mbstring Extension là bắt buộc để xử lý chuỗi Unicode.'
        ],
        'memory_limit' => [
            'name' => 'Memory Limit',
            'required' => '128M',
            'current' => ini_get('memory_limit'),
            'status' => (intval(ini_get('memory_limit')) >= 128 || ini_get('memory_limit') == '-1'),
            'message' => 'Giới hạn bộ nhớ nên ít nhất 128M.'
        ],
        'max_execution_time' => [
            'name' => 'Max Execution Time',
            'required' => '30 giây',
            'current' => ini_get('max_execution_time') . ' giây',
            'status' => (intval(ini_get('max_execution_time')) >= 30 || ini_get('max_execution_time') == '0'),
            'message' => 'Thời gian thực thi tối đa nên ít nhất 30 giây.'
        ],
        'file_uploads' => [
            'name' => 'File Uploads',
            'required' => 'Kích hoạt',
            'current' => ini_get('file_uploads') ? 'Kích hoạt' : 'Không kích hoạt',
            'status' => ini_get('file_uploads'),
            'message' => 'File uploads cần được kích hoạt để tải lên tệp.'
        ],
        'uploads_directory' => [
            'name' => 'Thư mục uploads',
            'required' => 'Có thể ghi',
            'current' => is_writable(dirname(dirname(__FILE__)) . '/uploads') ? 'Có thể ghi' : 'Không thể ghi',
            'status' => is_writable(dirname(dirname(__FILE__)) . '/uploads'),
            'message' => 'Thư mục uploads cần được cấp quyền ghi để tải lên tệp.'
        ],
        'cache_directory' => [
            'name' => 'Thư mục cache',
            'required' => 'Có thể ghi',
            'current' => is_writable(dirname(dirname(__FILE__)) . '/cache') ? 'Có thể ghi' : 'Không thể ghi',
            'status' => is_writable(dirname(dirname(__FILE__)) . '/cache'),
            'message' => 'Thư mục cache cần được cấp quyền ghi để lưu trữ bộ nhớ đệm.'
        ]
    ];
    
    // Kiểm tra kết nối cơ sở dữ liệu
    $db_type = get_config('db.type');
    
    try {
        $conn = db_connect();
        
        if ($db_type === 'postgresql') {
            $db_version = pg_version($conn);
            $requirements['database_connection'] = [
                'name' => 'Kết nối PostgreSQL',
                'required' => 'Kết nối thành công',
                'current' => 'Kết nối thành công (v' . $db_version['server'] . ')',
                'status' => true,
                'message' => 'Kết nối đến cơ sở dữ liệu PostgreSQL thành công.'
            ];
        } else {
            $db_version = $conn->server_info;
            $requirements['database_connection'] = [
                'name' => 'Kết nối MySQL',
                'required' => 'Kết nối thành công',
                'current' => 'Kết nối thành công (v' . $db_version . ')',
                'status' => true,
                'message' => 'Kết nối đến cơ sở dữ liệu MySQL thành công.'
            ];
        }
    } catch (Exception $e) {
        $requirements['database_connection'] = [
            'name' => 'Kết nối cơ sở dữ liệu',
            'required' => 'Kết nối thành công',
            'current' => 'Kết nối thất bại: ' . $e->getMessage(),
            'status' => false,
            'message' => 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra cài đặt cơ sở dữ liệu.'
        ];
    }
    
    // Kiểm tra các thư mục cần thiết và tạo nếu chưa tồn tại
    $required_directories = ['uploads', 'cache', 'uploads/images', 'uploads/videos', 'uploads/subtitles', 'cache/thumbnails'];
    
    foreach ($required_directories as $dir) {
        $full_path = dirname(dirname(__FILE__)) . '/' . $dir;
        
        if (!file_exists($full_path)) {
            @mkdir($full_path, 0755, true);
        }
        
        $requirements['directory_' . str_replace('/', '_', $dir)] = [
            'name' => 'Thư mục ' . $dir,
            'required' => 'Tồn tại và có thể ghi',
            'current' => file_exists($full_path) ? (is_writable($full_path) ? 'Tồn tại và có thể ghi' : 'Tồn tại nhưng không thể ghi') : 'Không tồn tại',
            'status' => file_exists($full_path) && is_writable($full_path),
            'message' => 'Thư mục ' . $dir . ' cần tồn tại và có quyền ghi.'
        ];
    }
    
    return $requirements;
}

// Thu thập thông tin cấu hình
function get_system_info() {
    $system_info = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_type' => get_config('db.type'),
        'database_name' => get_config('db.name'),
        'os_info' => PHP_OS,
        'max_upload_size' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'timezone' => date_default_timezone_get(),
        'server_time' => date('Y-m-d H:i:s'),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
    ];
    
    // Thêm thông tin về các API đã cấu hình
    $system_info['youtube_api'] = !empty(get_config('youtube.api_key')) ? 'Đã cấu hình' : 'Chưa cấu hình';
    $system_info['anilist_api'] = (!empty(get_config('anilist.client_id')) && !empty(get_config('anilist.client_secret'))) ? 'Đã cấu hình' : 'Chưa cấu hình';
    $system_info['tmdb_api'] = !empty(get_config('tmdb.api_key')) ? 'Đã cấu hình' : 'Chưa cấu hình';
    
    // Thêm thông tin về các cổng thanh toán
    $system_info['momo_payment'] = !empty(get_config('momo.partner_code')) ? 'Đã cấu hình' : 'Chưa cấu hình';
    $system_info['vnpay_payment'] = !empty(get_config('vnpay.terminal_id')) ? 'Đã cấu hình' : 'Chưa cấu hình';
    
    return $system_info;
}

// Kiểm tra các bảng trong cơ sở dữ liệu
function check_database_tables() {
    $db_type = get_config('db.type');
    $tables = [
        'users', 'videos', 'episodes', 'categories', 'video_categories',
        'comments', 'ratings', 'video_sources', 'subtitles', 'watch_history',
        'favorites', 'vip_members', 'payment_transactions', 'notifications',
        'reports', 'settings', 'anime_api_cache', 'admin_logs', 'role_permissions'
    ];
    
    $table_status = [];
    $conn = db_connect();
    
    foreach ($tables as $table) {
        if ($db_type === 'postgresql') {
            $sql = "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public'
                AND table_name = '$table'
            )";
            
            $result = pg_query($conn, $sql);
            $exists = pg_fetch_row($result)[0] === 't';
            
            if ($exists) {
                $count_sql = "SELECT COUNT(*) FROM $table";
                $count_result = pg_query($conn, $count_sql);
                $count = pg_fetch_row($count_result)[0];
            } else {
                $count = 0;
            }
        } else {
            $sql = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($sql);
            $exists = $result && $result->num_rows > 0;
            
            if ($exists) {
                $count_sql = "SELECT COUNT(*) AS total FROM $table";
                $count_result = $conn->query($count_sql);
                $count = $count_result->fetch_assoc()['total'];
            } else {
                $count = 0;
            }
        }
        
        $table_status[$table] = [
            'exists' => $exists,
            'count' => $exists ? (int)$count : 0
        ];
    }
    
    return $table_status;
}

// Thu thập thông tin
$requirements = check_server_requirements();
$system_info = get_system_info();
$database_tables = check_database_tables();

// Tính tổng trạng thái
$total_requirements = count($requirements);
$passed_requirements = 0;

foreach ($requirements as $req) {
    if ($req['status']) {
        $passed_requirements++;
    }
}

$requirements_percentage = ($passed_requirements / $total_requirements) * 100;

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Kiểm Tra Hệ Thống</h1>
        <p class="admin-page-subtitle">Xem thông tin cấu hình và trạng thái hệ thống</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-clipboard-check mr-2"></i> Yêu Cầu Hệ Thống
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="progress mb-4">
                    <div class="progress-bar bg-<?php echo $requirements_percentage >= 90 ? 'success' : ($requirements_percentage >= 70 ? 'warning' : 'danger'); ?>" 
                         role="progressbar" 
                         style="width: <?php echo $requirements_percentage; ?>%;" 
                         aria-valuenow="<?php echo $requirements_percentage; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?php echo $passed_requirements; ?>/<?php echo $total_requirements; ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Yêu cầu</th>
                                <th>Cần thiết</th>
                                <th>Hiện tại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $req): ?>
                                <tr>
                                    <td><?php echo $req['name']; ?></td>
                                    <td><?php echo $req['required']; ?></td>
                                    <td><?php echo $req['current']; ?></td>
                                    <td>
                                        <?php if ($req['status']): ?>
                                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Đạt</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger" data-toggle="tooltip" title="<?php echo $req['message']; ?>">
                                                <i class="fas fa-times mr-1"></i> Không đạt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-info-circle mr-2"></i> Thông Tin Hệ Thống
                </h2>
            </div>
            <div class="admin-card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th>Phiên bản PHP</th>
                            <td><?php echo $system_info['php_version']; ?></td>
                        </tr>
                        <tr>
                            <th>Phần mềm máy chủ</th>
                            <td><?php echo $system_info['server_software']; ?></td>
                        </tr>
                        <tr>
                            <th>Hệ điều hành</th>
                            <td><?php echo $system_info['os_info']; ?></td>
                        </tr>
                        <tr>
                            <th>Loại cơ sở dữ liệu</th>
                            <td><?php echo ucfirst($system_info['database_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Tên cơ sở dữ liệu</th>
                            <td><?php echo $system_info['database_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Giới hạn tải lên</th>
                            <td><?php echo $system_info['max_upload_size']; ?></td>
                        </tr>
                        <tr>
                            <th>Giới hạn POST</th>
                            <td><?php echo $system_info['post_max_size']; ?></td>
                        </tr>
                        <tr>
                            <th>Múi giờ</th>
                            <td><?php echo $system_info['timezone']; ?></td>
                        </tr>
                        <tr>
                            <th>Thời gian máy chủ</th>
                            <td><?php echo $system_info['server_time']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-plug mr-2"></i> Trạng Thái API
                </h2>
            </div>
            <div class="admin-card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th>YouTube API</th>
                            <td>
                                <?php if ($system_info['youtube_api'] === 'Đã cấu hình'): ?>
                                    <span class="badge badge-success">Đã cấu hình</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa cấu hình</span>
                                    <a href="api-settings.php" class="btn btn-sm btn-outline-primary ml-2">Cấu hình</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>AniList API</th>
                            <td>
                                <?php if ($system_info['anilist_api'] === 'Đã cấu hình'): ?>
                                    <span class="badge badge-success">Đã cấu hình</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa cấu hình</span>
                                    <a href="api-settings.php" class="btn btn-sm btn-outline-primary ml-2">Cấu hình</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>TMDB API</th>
                            <td>
                                <?php if ($system_info['tmdb_api'] === 'Đã cấu hình'): ?>
                                    <span class="badge badge-success">Đã cấu hình</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa cấu hình</span>
                                    <a href="api-settings.php" class="btn btn-sm btn-outline-primary ml-2">Cấu hình</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>MoMo Payment</th>
                            <td>
                                <?php if ($system_info['momo_payment'] === 'Đã cấu hình'): ?>
                                    <span class="badge badge-success">Đã cấu hình</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa cấu hình</span>
                                    <a href="payment-integration.php" class="btn btn-sm btn-outline-primary ml-2">Cấu hình</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>VNPay Payment</th>
                            <td>
                                <?php if ($system_info['vnpay_payment'] === 'Đã cấu hình'): ?>
                                    <span class="badge badge-success">Đã cấu hình</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa cấu hình</span>
                                    <a href="payment-integration.php" class="btn btn-sm btn-outline-primary ml-2">Cấu hình</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h2 class="admin-card-title">
            <i class="fas fa-database mr-2"></i> Trạng Thái Cơ Sở Dữ Liệu
        </h2>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Bảng</th>
                        <th>Trạng thái</th>
                        <th>Số lượng bản ghi</th>
                        <th width="150">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($database_tables as $table => $status): ?>
                        <tr>
                            <td><code><?php echo $table; ?></code></td>
                            <td>
                                <?php if ($status['exists']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Đã tạo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times mr-1"></i> Chưa tạo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $status['exists'] ? number_format($status['count']) : 'N/A'; ?></td>
                            <td>
                                <?php if ($status['exists']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info view-table-btn" data-table="<?php echo $table; ?>">
                                        <i class="fas fa-eye mr-1"></i> Xem
                                    </button>
                                <?php else: ?>
                                    <a href="../setup-database.php" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-database mr-1"></i> Cài đặt
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-tools mr-2"></i> Công Cụ Hệ Thống
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-broom fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Xóa Cache</h5>
                                <p class="card-text">Xóa tất cả các tệp cache để làm mới hệ thống.</p>
                                <form method="post" action="seo.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="tab" value="optimization">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-trash mr-1"></i> Xóa Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-sitemap fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Tạo Sitemap</h5>
                                <p class="card-text">Tạo sitemap.xml mới cho các công cụ tìm kiếm.</p>
                                <form method="post" action="seo.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="tab" value="sitemap">
                                    <input type="hidden" name="action" value="generate_sitemap">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync mr-1"></i> Tạo Sitemap
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Tối Ưu Database</h5>
                                <p class="card-text">Tối ưu hóa các bảng trong cơ sở dữ liệu.</p>
                                <a href="optimize-database.php" class="btn btn-info">
                                    <i class="fas fa-sync mr-1"></i> Tối Ưu Hóa
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <i class="fas fa-hdd fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Sao Lưu Hệ Thống</h5>
                                <p class="card-text">Tạo bản sao lưu của cơ sở dữ liệu và tệp cấu hình.</p>
                                <a href="backup.php" class="btn btn-danger">
                                    <i class="fas fa-download mr-1"></i> Sao Lưu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem bảng -->
<div class="modal fade" id="viewTableModal" tabindex="-1" aria-labelledby="viewTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTableModalLabel">Xem bảng: <span id="tableName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-structure mb-4">
                    <h6>Cấu trúc bảng</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="tableStructure">
                            <thead>
                                <tr>
                                    <th>Cột</th>
                                    <th>Kiểu dữ liệu</th>
                                    <th>Khóa</th>
                                    <th>Có thể NULL</th>
                                    <th>Mặc định</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dữ liệu sẽ được điền bằng AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="table-data">
                    <h6>Dữ liệu mẫu (5 bản ghi đầu tiên)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="tableData">
                            <thead>
                                <!-- Tiêu đề bảng sẽ được điền bằng AJAX -->
                            </thead>
                            <tbody>
                                <!-- Dữ liệu sẽ được điền bằng AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript xử lý xem bảng
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Xử lý nút xem bảng
    const viewTableButtons = document.querySelectorAll(".view-table-btn");
    
    viewTableButtons.forEach(button => {
        button.addEventListener("click", function() {
            const tableName = this.dataset.table;
            
            // Hiển thị tên bảng trong modal
            document.getElementById("tableName").textContent = tableName;
            
            // Lấy cấu trúc bảng bằng AJAX
            fetch("ajax/get-table-structure.php?table=" + tableName)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hiển thị cấu trúc bảng
                        const tableStructure = document.getElementById("tableStructure").querySelector("tbody");
                        tableStructure.innerHTML = "";
                        
                        data.structure.forEach(column => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${column.name}</td>
                                <td>${column.type}</td>
                                <td>${column.key}</td>
                                <td>${column.nullable ? "Có" : "Không"}</td>
                                <td>${column.default !== null ? column.default : ""}</td>
                            `;
                            tableStructure.appendChild(row);
                        });
                        
                        // Hiển thị dữ liệu mẫu
                        const tableData = document.getElementById("tableData");
                        const tableHead = tableData.querySelector("thead");
                        const tableBody = tableData.querySelector("tbody");
                        
                        // Tạo tiêu đề bảng
                        tableHead.innerHTML = "<tr>" + data.columns.map(col => `<th>${col}</th>`).join("") + "</tr>";
                        
                        // Tạo dữ liệu bảng
                        tableBody.innerHTML = "";
                        data.data.forEach(row => {
                            const tr = document.createElement("tr");
                            data.columns.forEach(col => {
                                const td = document.createElement("td");
                                const value = row[col];
                                
                                if (value === null) {
                                    td.innerHTML = "<em class=\'text-muted\'>NULL</em>";
                                } else if (typeof value === "object") {
                                    td.textContent = JSON.stringify(value);
                                } else {
                                    td.textContent = value.toString().length > 50 ? value.toString().substring(0, 50) + "..." : value;
                                }
                                
                                tr.appendChild(td);
                            });
                            tableBody.appendChild(tr);
                        });
                    } else {
                        alert("Lỗi: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Đã xảy ra lỗi khi lấy dữ liệu bảng.");
                });
            
            // Hiển thị modal
            $("#viewTableModal").modal("show");
        });
    });
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>