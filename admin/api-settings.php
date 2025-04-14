<?php
/**
 * Trang quản lý API Settings
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Cài Đặt API';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý API
$admin = require_admin_permission('manage_api');

// Xử lý lưu cài đặt API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy dữ liệu từ form
    $api_type = $_POST['api_type'] ?? '';
    
    // Kiểm tra API type hợp lệ
    $valid_api_types = ['youtube', 'anilist', 'tmdb', 'jikan', 'kitsu'];
    
    if (!in_array($api_type, $valid_api_types)) {
        set_flash_message('error', 'Loại API không hợp lệ.');
        header('Location: api-settings.php');
        exit;
    }
    
    // Xử lý từng loại API
    switch ($api_type) {
        case 'youtube':
            $api_key = $_POST['youtube_api_key'] ?? '';
            
            if (empty($api_key)) {
                set_flash_message('error', 'Vui lòng nhập YouTube API Key.');
                header('Location: api-settings.php');
                exit;
            }
            
            // Lưu cài đặt
            $config['youtube']['api_key'] = $api_key;
            update_config('youtube.api_key', $api_key);
            
            // Kiểm tra API key có hoạt động không
            $test_result = test_youtube_api($api_key);
            
            if ($test_result['success']) {
                set_flash_message('success', 'Đã lưu YouTube API Key thành công! API key hoạt động bình thường.');
            } else {
                set_flash_message('warning', 'Đã lưu YouTube API Key, nhưng kiểm tra API không thành công: ' . $test_result['message']);
            }
            
            // Ghi log
            log_admin_action('update_api_settings', 'Cập nhật YouTube API Key');
            break;
            
        case 'anilist':
            $client_id = $_POST['anilist_client_id'] ?? '';
            $client_secret = $_POST['anilist_client_secret'] ?? '';
            
            if (empty($client_id) || empty($client_secret)) {
                set_flash_message('error', 'Vui lòng nhập đầy đủ AniList Client ID và Client Secret.');
                header('Location: api-settings.php');
                exit;
            }
            
            // Lưu cài đặt
            update_config('anilist.client_id', $client_id);
            update_config('anilist.client_secret', $client_secret);
            
            // Kiểm tra API key có hoạt động không
            $test_result = test_anilist_api($client_id, $client_secret);
            
            if ($test_result['success']) {
                set_flash_message('success', 'Đã lưu AniList API thành công! API hoạt động bình thường.');
            } else {
                set_flash_message('warning', 'Đã lưu AniList API, nhưng kiểm tra API không thành công: ' . $test_result['message']);
            }
            
            // Ghi log
            log_admin_action('update_api_settings', 'Cập nhật AniList API');
            break;
            
        case 'tmdb':
            $api_key = $_POST['tmdb_api_key'] ?? '';
            
            if (empty($api_key)) {
                set_flash_message('error', 'Vui lòng nhập TMDB API Key.');
                header('Location: api-settings.php');
                exit;
            }
            
            // Lưu cài đặt
            update_config('tmdb.api_key', $api_key);
            
            // Kiểm tra API key có hoạt động không
            $test_result = test_tmdb_api($api_key);
            
            if ($test_result['success']) {
                set_flash_message('success', 'Đã lưu TMDB API Key thành công! API key hoạt động bình thường.');
            } else {
                set_flash_message('warning', 'Đã lưu TMDB API Key, nhưng kiểm tra API không thành công: ' . $test_result['message']);
            }
            
            // Ghi log
            log_admin_action('update_api_settings', 'Cập nhật TMDB API Key');
            break;
            
        case 'jikan':
            $jikan_enabled = isset($_POST['jikan_enabled']) ? '1' : '0';
            $jikan_rate_limit = $_POST['jikan_rate_limit'] ?? '3';
            
            // Lưu cài đặt
            update_config('jikan.enabled', $jikan_enabled);
            update_config('jikan.rate_limit', $jikan_rate_limit);
            
            // Kiểm tra API có hoạt động không
            $test_result = test_jikan_api();
            
            if ($test_result['success']) {
                set_flash_message('success', 'Đã lưu cài đặt Jikan API thành công! API hoạt động bình thường.');
            } else {
                set_flash_message('warning', 'Đã lưu cài đặt Jikan API, nhưng kiểm tra API không thành công: ' . $test_result['message']);
            }
            
            // Ghi log
            log_admin_action('update_api_settings', 'Cập nhật Jikan API');
            break;
            
        case 'kitsu':
            $kitsu_enabled = isset($_POST['kitsu_enabled']) ? '1' : '0';
            
            // Lưu cài đặt
            update_config('kitsu.enabled', $kitsu_enabled);
            
            // Kiểm tra API có hoạt động không
            $test_result = test_kitsu_api();
            
            if ($test_result['success']) {
                set_flash_message('success', 'Đã lưu cài đặt Kitsu API thành công! API hoạt động bình thường.');
            } else {
                set_flash_message('warning', 'Đã lưu cài đặt Kitsu API, nhưng kiểm tra API không thành công: ' . $test_result['message']);
            }
            
            // Ghi log
            log_admin_action('update_api_settings', 'Cập nhật Kitsu API');
            break;
    }
    
    // Chuyển hướng
    header('Location: api-settings.php');
    exit;
}

// Lấy cài đặt hiện tại
$youtube_api_key = get_config('youtube.api_key', '');
$anilist_client_id = get_config('anilist.client_id', '');
$anilist_client_secret = get_config('anilist.client_secret', '');
$tmdb_api_key = get_config('tmdb.api_key', '');
$jikan_enabled = get_config('jikan.enabled', '1');
$jikan_rate_limit = get_config('jikan.rate_limit', '3');
$kitsu_enabled = get_config('kitsu.enabled', '1');

// CSRF token
$csrf_token = generate_csrf_token();
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Cài Đặt API</h1>
        <p class="admin-page-subtitle">Quản lý các API tích hợp với hệ thống</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <ul class="nav nav-tabs mb-4" id="apiTabs">
            <li class="nav-item">
                <a class="nav-link active" id="youtube-tab" data-toggle="tab" href="#youtube">
                    <i class="fab fa-youtube mr-1"></i> YouTube
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="anilist-tab" data-toggle="tab" href="#anilist">
                    <i class="fas fa-list-alt mr-1"></i> AniList
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tmdb-tab" data-toggle="tab" href="#tmdb">
                    <i class="fas fa-film mr-1"></i> TMDB
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="jikan-tab" data-toggle="tab" href="#jikan">
                    <i class="fas fa-server mr-1"></i> Jikan (MyAnimeList)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="kitsu-tab" data-toggle="tab" href="#kitsu">
                    <i class="fas fa-server mr-1"></i> Kitsu
                </a>
            </li>
        </ul>
        
        <div class="tab-content" id="apiTabsContent">
            <!-- YouTube API -->
            <div class="tab-pane fade show active" id="youtube" role="tabpanel" aria-labelledby="youtube-tab">
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="api-settings.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="api_type" value="youtube">
                            
                            <div class="form-group">
                                <label for="youtube_api_key">YouTube API Key</label>
                                <input type="text" class="form-control" id="youtube_api_key" name="youtube_api_key" value="<?php echo $youtube_api_key; ?>" required>
                                <small class="form-text text-muted">API Key được sử dụng để tìm kiếm và nhúng video YouTube.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="testYoutubeBtn">
                                    <i class="fas fa-check-circle mr-1"></i> Kiểm tra API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Thông tin YouTube API</h5>
                            </div>
                            <div class="card-body">
                                <p>YouTube API được sử dụng để:</p>
                                <ul>
                                    <li>Tìm kiếm trailer phim</li>
                                    <li>Nhúng video YouTube</li>
                                    <li>Lấy thông tin video</li>
                                </ul>
                                <p>Để lấy YouTube API Key:</p>
                                <ol>
                                    <li>Truy cập <a href="https://console.developers.google.com/" target="_blank">Google Developer Console</a></li>
                                    <li>Tạo dự án mới</li>
                                    <li>Kích hoạt YouTube Data API v3</li>
                                    <li>Tạo API Key mới</li>
                                </ol>
                                <a href="https://developers.google.com/youtube/v3/getting-started" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem hướng dẫn
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AniList API -->
            <div class="tab-pane fade" id="anilist" role="tabpanel" aria-labelledby="anilist-tab">
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="api-settings.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="api_type" value="anilist">
                            
                            <div class="form-group">
                                <label for="anilist_client_id">AniList Client ID</label>
                                <input type="text" class="form-control" id="anilist_client_id" name="anilist_client_id" value="<?php echo $anilist_client_id; ?>" required>
                                <small class="form-text text-muted">Client ID được sử dụng để xác thực với AniList API.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="anilist_client_secret">AniList Client Secret</label>
                                <input type="password" class="form-control" id="anilist_client_secret" name="anilist_client_secret" value="<?php echo $anilist_client_secret; ?>" required>
                                <small class="form-text text-muted">Client Secret được sử dụng để xác thực với AniList API.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="testAnilistBtn">
                                    <i class="fas fa-check-circle mr-1"></i> Kiểm tra API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Thông tin AniList API</h5>
                            </div>
                            <div class="card-body">
                                <p>AniList API được sử dụng để:</p>
                                <ul>
                                    <li>Lấy thông tin anime</li>
                                    <li>Lấy lịch phát sóng anime</li>
                                    <li>Tìm kiếm anime</li>
                                    <li>Lấy thông tin chi tiết và phân loại</li>
                                </ul>
                                <p>Để lấy AniList API Key:</p>
                                <ol>
                                    <li>Đăng ký tài khoản tại <a href="https://anilist.co/" target="_blank">AniList</a></li>
                                    <li>Truy cập <a href="https://anilist.co/settings/developer" target="_blank">Developer Settings</a></li>
                                    <li>Tạo Client mới</li>
                                    <li>Lấy Client ID và Client Secret</li>
                                </ol>
                                <a href="https://anilist.gitbook.io/anilist-apiv2-docs/" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem tài liệu API
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TMDB API -->
            <div class="tab-pane fade" id="tmdb" role="tabpanel" aria-labelledby="tmdb-tab">
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="api-settings.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="api_type" value="tmdb">
                            
                            <div class="form-group">
                                <label for="tmdb_api_key">TMDB API Key</label>
                                <input type="text" class="form-control" id="tmdb_api_key" name="tmdb_api_key" value="<?php echo $tmdb_api_key; ?>" required>
                                <small class="form-text text-muted">API Key được sử dụng để truy cập The Movie Database API.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="testTmdbBtn">
                                    <i class="fas fa-check-circle mr-1"></i> Kiểm tra API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Thông tin TMDB API</h5>
                            </div>
                            <div class="card-body">
                                <p>TMDB API được sử dụng để:</p>
                                <ul>
                                    <li>Lấy thông tin phim và TV show</li>
                                    <li>Lấy hình ảnh chất lượng cao</li>
                                    <li>Lấy thông tin diễn viên và nhà sản xuất</li>
                                    <li>Lấy doanh thu và đánh giá phim</li>
                                </ul>
                                <p>Để lấy TMDB API Key:</p>
                                <ol>
                                    <li>Đăng ký tài khoản tại <a href="https://www.themoviedb.org/" target="_blank">TMDB</a></li>
                                    <li>Truy cập <a href="https://www.themoviedb.org/settings/api" target="_blank">API Settings</a></li>
                                    <li>Tạo API Key mới (v3 auth)</li>
                                </ol>
                                <a href="https://developers.themoviedb.org/3/getting-started/introduction" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem tài liệu API
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Jikan API -->
            <div class="tab-pane fade" id="jikan" role="tabpanel" aria-labelledby="jikan-tab">
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="api-settings.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="api_type" value="jikan">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="jikan_enabled" name="jikan_enabled" <?php echo $jikan_enabled == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="jikan_enabled">Bật Jikan API</label>
                                </div>
                                <small class="form-text text-muted">Jikan là API không chính thức của MyAnimeList. Không yêu cầu API key.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="jikan_rate_limit">Giới hạn số lượng yêu cầu (requests/giây)</label>
                                <input type="number" class="form-control" id="jikan_rate_limit" name="jikan_rate_limit" value="<?php echo $jikan_rate_limit; ?>" min="1" max="10">
                                <small class="form-text text-muted">Jikan API giới hạn số lượng yêu cầu. Nên đặt dưới 4 yêu cầu/giây.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="testJikanBtn">
                                    <i class="fas fa-check-circle mr-1"></i> Kiểm tra API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Thông tin Jikan API</h5>
                            </div>
                            <div class="card-body">
                                <p>Jikan API được sử dụng để:</p>
                                <ul>
                                    <li>Lấy thông tin anime từ MyAnimeList</li>
                                    <li>Lấy lịch phát sóng anime</li>
                                    <li>Lấy thông tin đánh giá và xếp hạng</li>
                                </ul>
                                <p>Jikan là API không chính thức (không cần API key), nhưng có một số giới hạn:</p>
                                <ul>
                                    <li>Tối đa 3 yêu cầu/giây</li>
                                    <li>Có thể không ổn định vào một số thời điểm</li>
                                </ul>
                                <a href="https://jikan.moe/" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem tài liệu API
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kitsu API -->
            <div class="tab-pane fade" id="kitsu" role="tabpanel" aria-labelledby="kitsu-tab">
                <div class="row">
                    <div class="col-md-8">
                        <form method="post" action="api-settings.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="api_type" value="kitsu">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="kitsu_enabled" name="kitsu_enabled" <?php echo $kitsu_enabled == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="kitsu_enabled">Bật Kitsu API</label>
                                </div>
                                <small class="form-text text-muted">Kitsu API không yêu cầu API key và không có giới hạn số lượng yêu cầu.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="testKitsuBtn">
                                    <i class="fas fa-check-circle mr-1"></i> Kiểm tra API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Thông tin Kitsu API</h5>
                            </div>
                            <div class="card-body">
                                <p>Kitsu API được sử dụng để:</p>
                                <ul>
                                    <li>Lấy thông tin anime và manga</li>
                                    <li>Lấy thông tin xếp hạng và thể loại</li>
                                    <li>Lấy thông tin nhân vật và diễn viên lồng tiếng</li>
                                </ul>
                                <p>Ưu điểm của Kitsu API:</p>
                                <ul>
                                    <li>Không yêu cầu API key</li>
                                    <li>Không có giới hạn số lượng yêu cầu</li>
                                    <li>Cung cấp nhiều thông tin chi tiết</li>
                                </ul>
                                <a href="https://kitsu.docs.apiary.io/" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                    <i class="fas fa-external-link-alt mr-1"></i> Xem tài liệu API
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Modal kết quả kiểm tra API
?>
<div class="modal fade" id="apiTestModal" tabindex="-1" aria-labelledby="apiTestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiTestModalLabel">Kết quả kiểm tra API</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="apiTestResult">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang kiểm tra...</span>
                        </div>
                        <p class="mt-2">Đang kiểm tra kết nối API...</p>
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
// JavaScript để kiểm tra API
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Chuyển tab từ URL hash
    const hash = window.location.hash;
    if (hash) {
        $(`a[href="${hash}"]`).tab("show");
    }
    
    // Lưu tab đang chọn vào URL hash
    $("a[data-toggle=\'tab\']").on("shown.bs.tab", function(e) {
        window.location.hash = $(e.target).attr("href");
    });
    
    // Kiểm tra YouTube API
    $("#testYoutubeBtn").click(function() {
        const apiKey = $("#youtube_api_key").val();
        
        if (!apiKey) {
            alert("Vui lòng nhập YouTube API Key trước khi kiểm tra.");
            return;
        }
        
        $("#apiTestModal").modal("show");
        $("#apiTestResult").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang kiểm tra...</span>
                </div>
                <p class="mt-2">Đang kiểm tra kết nối đến YouTube API...</p>
            </div>
        `);
        
        $.ajax({
            url: "ajax/test-api.php",
            type: "POST",
            data: {
                api_type: "youtube",
                api_key: apiKey,
                csrf_token: "' . $csrf_token . '"
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#apiTestResult").html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> Kết nối thành công!
                            <hr>
                            <p class="mb-0">API key hoạt động bình thường.</p>
                            ${response.data ? `<p class="mt-2 mb-0">Thông tin: ${response.data}</p>` : ""}
                        </div>
                    `);
                } else {
                    $("#apiTestResult").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i> Kết nối thất bại!
                            <hr>
                            <p class="mb-0">Lỗi: ${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#apiTestResult").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i> Lỗi khi gửi yêu cầu!
                        <hr>
                        <p class="mb-0">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p>
                    </div>
                `);
            }
        });
    });
    
    // Kiểm tra AniList API
    $("#testAnilistBtn").click(function() {
        const clientId = $("#anilist_client_id").val();
        const clientSecret = $("#anilist_client_secret").val();
        
        if (!clientId || !clientSecret) {
            alert("Vui lòng nhập đầy đủ AniList Client ID và Client Secret trước khi kiểm tra.");
            return;
        }
        
        $("#apiTestModal").modal("show");
        $("#apiTestResult").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang kiểm tra...</span>
                </div>
                <p class="mt-2">Đang kiểm tra kết nối đến AniList API...</p>
            </div>
        `);
        
        $.ajax({
            url: "ajax/test-api.php",
            type: "POST",
            data: {
                api_type: "anilist",
                client_id: clientId,
                client_secret: clientSecret,
                csrf_token: "' . $csrf_token . '"
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#apiTestResult").html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> Kết nối thành công!
                            <hr>
                            <p class="mb-0">API key hoạt động bình thường.</p>
                            ${response.data ? `<p class="mt-2 mb-0">Thông tin: ${response.data}</p>` : ""}
                        </div>
                    `);
                } else {
                    $("#apiTestResult").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i> Kết nối thất bại!
                            <hr>
                            <p class="mb-0">Lỗi: ${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#apiTestResult").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i> Lỗi khi gửi yêu cầu!
                        <hr>
                        <p class="mb-0">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p>
                    </div>
                `);
            }
        });
    });
    
    // Kiểm tra TMDB API
    $("#testTmdbBtn").click(function() {
        const apiKey = $("#tmdb_api_key").val();
        
        if (!apiKey) {
            alert("Vui lòng nhập TMDB API Key trước khi kiểm tra.");
            return;
        }
        
        $("#apiTestModal").modal("show");
        $("#apiTestResult").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang kiểm tra...</span>
                </div>
                <p class="mt-2">Đang kiểm tra kết nối đến TMDB API...</p>
            </div>
        `);
        
        $.ajax({
            url: "ajax/test-api.php",
            type: "POST",
            data: {
                api_type: "tmdb",
                api_key: apiKey,
                csrf_token: "' . $csrf_token . '"
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#apiTestResult").html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> Kết nối thành công!
                            <hr>
                            <p class="mb-0">API key hoạt động bình thường.</p>
                            ${response.data ? `<p class="mt-2 mb-0">Thông tin: ${response.data}</p>` : ""}
                        </div>
                    `);
                } else {
                    $("#apiTestResult").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i> Kết nối thất bại!
                            <hr>
                            <p class="mb-0">Lỗi: ${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#apiTestResult").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i> Lỗi khi gửi yêu cầu!
                        <hr>
                        <p class="mb-0">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p>
                    </div>
                `);
            }
        });
    });
    
    // Kiểm tra Jikan API
    $("#testJikanBtn").click(function() {
        const enabled = $("#jikan_enabled").is(":checked");
        
        if (!enabled) {
            alert("Vui lòng bật Jikan API trước khi kiểm tra.");
            return;
        }
        
        $("#apiTestModal").modal("show");
        $("#apiTestResult").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang kiểm tra...</span>
                </div>
                <p class="mt-2">Đang kiểm tra kết nối đến Jikan API...</p>
            </div>
        `);
        
        $.ajax({
            url: "ajax/test-api.php",
            type: "POST",
            data: {
                api_type: "jikan",
                csrf_token: "' . $csrf_token . '"
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#apiTestResult").html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> Kết nối thành công!
                            <hr>
                            <p class="mb-0">Jikan API hoạt động bình thường.</p>
                            ${response.data ? `<p class="mt-2 mb-0">Thông tin: ${response.data}</p>` : ""}
                        </div>
                    `);
                } else {
                    $("#apiTestResult").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i> Kết nối thất bại!
                            <hr>
                            <p class="mb-0">Lỗi: ${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#apiTestResult").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i> Lỗi khi gửi yêu cầu!
                        <hr>
                        <p class="mb-0">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p>
                    </div>
                `);
            }
        });
    });
    
    // Kiểm tra Kitsu API
    $("#testKitsuBtn").click(function() {
        const enabled = $("#kitsu_enabled").is(":checked");
        
        if (!enabled) {
            alert("Vui lòng bật Kitsu API trước khi kiểm tra.");
            return;
        }
        
        $("#apiTestModal").modal("show");
        $("#apiTestResult").html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Đang kiểm tra...</span>
                </div>
                <p class="mt-2">Đang kiểm tra kết nối đến Kitsu API...</p>
            </div>
        `);
        
        $.ajax({
            url: "ajax/test-api.php",
            type: "POST",
            data: {
                api_type: "kitsu",
                csrf_token: "' . $csrf_token . '"
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#apiTestResult").html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> Kết nối thành công!
                            <hr>
                            <p class="mb-0">Kitsu API hoạt động bình thường.</p>
                            ${response.data ? `<p class="mt-2 mb-0">Thông tin: ${response.data}</p>` : ""}
                        </div>
                    `);
                } else {
                    $("#apiTestResult").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i> Kết nối thất bại!
                            <hr>
                            <p class="mb-0">Lỗi: ${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $("#apiTestResult").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i> Lỗi khi gửi yêu cầu!
                        <hr>
                        <p class="mb-0">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p>
                    </div>
                `);
            }
        });
    });
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';

// Các hàm kiểm tra API
function test_youtube_api($api_key) {
    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=1&q=anime&type=video&key=$api_key";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        return [
            'success' => true,
            'message' => 'API key hợp lệ.',
            'data' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'API key không hợp lệ hoặc đã hết quota.'),
            'data' => $response
        ];
    }
}

function test_anilist_api($client_id, $client_secret) {
    $url = "https://graphql.anilist.co";
    $headers = ["Content-Type: application/json", "Accept: application/json"];
    
    $query = '
    query {
        Page(page: 1, perPage: 1) {
            media(type: ANIME, sort: POPULARITY_DESC) {
                id
                title {
                    romaji
                    english
                    native
                }
                type
                format
                episodes
            }
        }
    }
    ';
    
    $data = json_encode(['query' => $query]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data'])) {
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => json_encode($json['data'])
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.',
                'data' => $response
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến AniList API.'),
            'data' => $response
        ];
    }
}

function test_tmdb_api($api_key) {
    $url = "https://api.themoviedb.org/3/movie/popular?api_key=$api_key&language=vi-VN&page=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'API key hợp lệ.',
            'data' => 'Tổng số phim: ' . $json['total_results']
        ];
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'API key không hợp lệ.'),
            'data' => $response
        ];
    }
}

function test_jikan_api() {
    $url = "https://api.jikan.moe/v4/anime/1/full";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data']['title'])) {
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => 'Anime: ' . $json['data']['title']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.',
                'data' => $response
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến Jikan API.'),
            'data' => $response
        ];
    }
}

function test_kitsu_api() {
    $url = "https://kitsu.io/api/edge/anime?page[limit]=1&sort=-averageRating";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json', 'Content-Type: application/vnd.api+json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data'][0]['attributes']['canonicalTitle'])) {
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => 'Anime: ' . $json['data'][0]['attributes']['canonicalTitle']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.',
                'data' => $response
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến Kitsu API.'),
            'data' => $response
        ];
    }
}

/**
 * Cập nhật giá trị trong cấu hình
 */
function update_config($key, $value) {
    global $config;
    
    // Cập nhật trong biến config
    $keys = explode('.', $key);
    $temp = &$config;
    
    foreach ($keys as $i => $k) {
        if ($i === count($keys) - 1) {
            $temp[$k] = $value;
        } else {
            if (!isset($temp[$k]) || !is_array($temp[$k])) {
                $temp[$k] = [];
            }
            $temp = &$temp[$k];
        }
    }
    
    // Lưu vào file config
    $config_file = dirname(dirname(__FILE__)) . '/api/config.php';
    
    if (file_exists($config_file)) {
        $content = file_get_contents($config_file);
        
        // Cập nhật giá trị
        $pattern = '/\$config\[\'(' . str_replace('.', '\']\[\'', $key) . '\')\'\s*=\s*[^;]+;/';
        $replacement = '$config[\'' . str_replace('.', '\']\[\'', $key) . '\'] = ' . var_export($value, true) . ';';
        
        if (preg_match($pattern, $content)) {
            // Cập nhật nếu đã tồn tại
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Thêm mới nếu chưa tồn tại
            $content = str_replace('return $config;', "$replacement\n\nreturn \$config;", $content);
        }
        
        file_put_contents($config_file, $content);
    }
}
?>