<?php
/**
 * Trang quản lý Google Drive
 */

// Bao gồm các file cần thiết
require_once '../init.php';
require_once '../includes/google_drive.php';

// Kiểm tra quyền admin
if (!is_admin($current_user)) {
    header('Location: ../index.php');
    exit();
}

// Xử lý khi form được gửi đi
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_api_key') {
        // Cập nhật Google API key
        if (isset($_POST['google_api_key'])) {
            $api_key = trim($_POST['google_api_key']);
            
            // Đọc config hiện tại
            $config_file = __DIR__ . '/../config.json';
            $config = json_decode(file_get_contents($config_file), true);
            
            // Cập nhật API key
            $config['google_api_key'] = $api_key;
            
            // Lưu lại config
            file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
            
            $message = 'Đã cập nhật Google API key thành công';
            $message_type = 'success';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_drive_source') {
        // Thêm nguồn từ Google Drive
        if (isset($_POST['episode_id'], $_POST['drive_id'], $_POST['quality'])) {
            $episode_id = intval($_POST['episode_id']);
            $drive_id = trim($_POST['drive_id']);
            $quality = trim($_POST['quality']);
            
            // Kiểm tra dữ liệu đầu vào
            if (empty($drive_id)) {
                $message = 'ID Google Drive không hợp lệ';
                $message_type = 'error';
            } elseif (!in_array($quality, ['360', '480', '720', '1080', '4k'])) {
                $message = 'Chất lượng video không hợp lệ';
                $message_type = 'error';
            } else {
                // Thêm nguồn Google Drive
                $result = add_google_drive_source($episode_id, $drive_id, $quality);
                
                if ($result) {
                    $message = 'Đã thêm nguồn Google Drive thành công';
                    $message_type = 'success';
                } else {
                    $message = 'Có lỗi khi thêm nguồn Google Drive';
                    $message_type = 'error';
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'test_drive') {
        // Kiểm tra file Google Drive
        if (isset($_POST['drive_id'])) {
            $drive_id = trim($_POST['drive_id']);
            
            // Lấy thông tin file
            $file_info = get_google_drive_file_info($drive_id);
            
            if ($file_info) {
                $message = 'Kết nối Google Drive thành công. Thông tin file: ' . $file_info['name'] . ' (' . $file_info['mimeType'] . ')';
                $message_type = 'success';
            } else {
                $message = 'Không thể kết nối đến Google Drive hoặc file không tồn tại';
                $message_type = 'error';
            }
        }
    }
}

// Lấy danh sách phim
$movies = db_fetch_all("SELECT id, title FROM movies ORDER BY title ASC");

// Lấy API key hiện tại
$config_file = __DIR__ . '/../config.json';
$config = json_decode(file_get_contents($config_file), true);
$current_api_key = $config['google_api_key'] ?? '';

// Kiểm tra API key
$api_key_valid = !empty($current_api_key);

// Bao gồm header
$page_title = 'Quản lý Google Drive - Admin';
require_once __DIR__ . '/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-content-header">
        <h2>Quản lý Google Drive</h2>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Cài đặt Google API</h3>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="update_api_key">
                
                <div class="form-group">
                    <label for="google_api_key">Google API Key</label>
                    <div class="input-group">
                        <input type="text" id="google_api_key" name="google_api_key" class="form-control" 
                               value="<?php echo htmlspecialchars($current_api_key); ?>" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        API key được sử dụng để truy cập thông tin file từ Google Drive.
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Tạo API key</a>
                    </small>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Kiểm tra kết nối Google Drive</h3>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="test_drive">
                
                <div class="form-group">
                    <label for="test_drive_id">ID Google Drive</label>
                    <div class="input-group">
                        <input type="text" id="test_drive_id" name="drive_id" class="form-control" 
                               placeholder="VD: 1a2b3c4d5e6f7g8h9i0j" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-info" <?php echo $api_key_valid ? '' : 'disabled'; ?>>
                                Kiểm tra
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Nhập ID của file Google Drive để kiểm tra kết nối. 
                        ID là chuỗi kí tự nằm trong URL của file.
                    </small>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Thêm nguồn Google Drive cho tập phim</h3>
        </div>
        <div class="card-body">
            <form method="post" action="" id="addDriveSourceForm">
                <input type="hidden" name="action" value="add_drive_source">
                
                <div class="form-group">
                    <label for="movie_id">Phim</label>
                    <select id="movie_id" class="form-control" required>
                        <option value="">-- Chọn phim --</option>
                        <?php foreach ($movies as $movie): ?>
                        <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="episode_id">Tập phim</label>
                    <select id="episode_id" name="episode_id" class="form-control" required disabled>
                        <option value="">-- Chọn tập phim --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="drive_id">ID Google Drive</label>
                    <input type="text" id="drive_id" name="drive_id" class="form-control" 
                           placeholder="VD: 1a2b3c4d5e6f7g8h9i0j" required>
                    <small class="form-text text-muted">
                        ID của file Google Drive, có thể lấy từ URL của file.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="quality">Chất lượng video</label>
                    <select id="quality" name="quality" class="form-control" required>
                        <option value="360">360p</option>
                        <option value="480">480p</option>
                        <option value="720">720p</option>
                        <option value="1080">1080p</option>
                        <option value="4k">4K</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" <?php echo $api_key_valid ? '' : 'disabled'; ?>>
                    Thêm nguồn
                </button>
                
                <?php if (!$api_key_valid): ?>
                <div class="alert alert-warning mt-3">
                    Vui lòng cập nhật Google API key trước khi thêm nguồn Google Drive.
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const movieSelect = document.getElementById('movie_id');
    const episodeSelect = document.getElementById('episode_id');
    
    // Lấy danh sách tập khi chọn phim
    movieSelect.addEventListener('change', function() {
        const movieId = this.value;
        
        if (movieId) {
            // Kích hoạt select tập phim
            episodeSelect.disabled = false;
            
            // Xóa các option cũ
            episodeSelect.innerHTML = '<option value="">-- Đang tải tập phim --</option>';
            
            // Tải danh sách tập từ API
            fetch(`../api/episodes.php?movie_id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    // Xóa option "đang tải"
                    episodeSelect.innerHTML = '<option value="">-- Chọn tập phim --</option>';
                    
                    // Thêm các option mới
                    data.forEach(episode => {
                        const option = document.createElement('option');
                        option.value = episode.id;
                        option.textContent = `Tập ${episode.episode_number}: ${episode.title}`;
                        episodeSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Lỗi khi tải danh sách tập:', error);
                    episodeSelect.innerHTML = '<option value="">-- Lỗi khi tải dữ liệu --</option>';
                });
        } else {
            // Vô hiệu hóa select tập phim
            episodeSelect.disabled = true;
            episodeSelect.innerHTML = '<option value="">-- Chọn tập phim --</option>';
        }
    });
});
</script>

<?php
// Bao gồm footer
require_once __DIR__ . '/admin_footer.php';
?>