<?php
// Định nghĩa URL trang chủ
define('SITE_URL', 'https://localhost');

// Bao gồm các file cần thiết
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'includes/subtitles.php';

// Kiểm tra đăng nhập qua token (nếu có)
if (!isset($_SESSION['user_id'])) {
    check_remember_token();
}

// Lấy thông tin người dùng hiện tại
$current_user = get_logged_in_user();

// Kiểm tra tham số slug
if (!isset($_GET['slug'])) {
    header('Location: index.php');
    exit();
}

$slug = $_GET['slug'];
$movie = get_movie_by_slug($slug);

if (!$movie) {
    header('Location: index.php');
    exit();
}

// Tăng lượt xem
increase_movie_views($movie['id']);

// Lấy danh sách tập phim
$episodes = get_movie_episodes($movie['id']);

// Xác định tập đang xem
$current_ep = isset($_GET['ep']) ? intval($_GET['ep']) : 1;
$episode = get_episode($movie['id'], $current_ep);

if (!$episode) {
    $episode = $episodes[0] ?? null;
    $current_ep = $episode ? $episode['episode_number'] : 1;
}

// Lấy tiến trình xem phim của người dùng
$watch_progress = null;
if ($current_user) {
    $watch_progress = get_user_progress($current_user['id'], $movie['id'], $episode['id']);
}

// Xác định chất lượng video
$default_quality = can_user_watch_hd($current_user) ? '1080p' : '480p';
$quality = isset($_GET['quality']) ? $_GET['quality'] : $default_quality;

// Kiểm tra quyền xem video chất lượng cao
if (!can_user_watch_hd($current_user) && $quality != '480p') {
    $quality = '480p';
}

// Xác định URL video
$video_url = $episode['source_' . str_replace('p', '', $quality)] ?? $episode['source_480'];

// Lấy danh sách phụ đề
$subtitles = get_subtitles($episode['id']);
$default_subtitle = $episode['default_subtitle'] ?? null;

// Lấy danh sách ngôn ngữ âm thanh
$audio_tracks = get_audio_tracks($episode['id']);
$default_audio = $episode['default_audio'] ?? 'vi';

// Kiểm tra xem có cần hiển thị quảng cáo không
$show_ads = should_show_ads($current_user);

// Lấy thông tin quảng cáo nếu cần
$ads = array();
if ($show_ads) {
    $ads = db_fetch_all("SELECT * FROM ads WHERE status = 1 AND position IN ('pre-roll', 'mid-roll', 'post-roll') ORDER BY RAND() LIMIT 3");
}

// Bao gồm header
require_once 'includes/header.php';
?>

<!-- Phần Player -->
<div class="player-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <!-- Trình phát video -->
                <div class="player-container" data-movie-id="<?php echo $movie['id']; ?>" data-episode-id="<?php echo $episode['id']; ?>">
                    <div id="video-player" class="video-player">
                        <div class="video-loader">
                            <div class="loader-spinner"></div>
                            <p>Video đang tải...</p>
                        </div>
                        <video id="main-player" preload="metadata" poster="<?php echo $movie['thumbnail']; ?>" crossorigin="anonymous" data-source-type="<?php echo $episode['source_' . str_replace('p', '', $quality) . '_type'] ?? 'direct'; ?>">
                            <source src="<?php echo $video_url; ?>" type="video/mp4" data-quality="<?php echo $quality; ?>">
                            <?php foreach ($subtitles as $subtitle): ?>
                            <track kind="subtitles" label="<?php echo $subtitle['language_name']; ?>" srclang="<?php echo $subtitle['language_code']; ?>" src="<?php echo $subtitle['subtitle_file']; ?>" <?php echo ($subtitle['language_code'] === $default_subtitle) ? 'default' : ''; ?>>
                            <?php endforeach; ?>
                            Trình duyệt của bạn không hỗ trợ video HTML5.
                        </video>
                        
                        <!-- Các điều khiển video -->
                        <div class="player-controls">
                            <div class="progress-bar">
                                <div class="progress-bg"></div>
                                <div class="progress-buffered"></div>
                                <div class="progress-current"></div>
                                <div class="progress-hover"></div>
                                <div class="progress-preview">
                                    <div class="preview-thumbnail"></div>
                                    <span class="preview-time">00:00</span>
                                </div>
                            </div>
                            <div class="controls-bottom">
                                <div class="controls-left">
                                    <button class="btn-play"><i class="fa fa-play"></i></button>
                                    <button class="btn-volume"><i class="fa fa-volume-up"></i></button>
                                    <div class="volume-slider">
                                        <div class="volume-progress"></div>
                                    </div>
                                    <div class="time-display">
                                        <span class="current-time">00:00</span>
                                        <span class="time-separator">/</span>
                                        <span class="total-time">00:00</span>
                                    </div>
                                </div>
                                <div class="controls-right">
                                    <?php if (can_user_watch_hd($current_user)): ?>
                                    <div class="quality-selector">
                                        <button class="btn-quality"><?php echo $quality; ?> <i class="fa fa-caret-down"></i></button>
                                        <div class="quality-options">
                                            <a href="?slug=<?php echo $slug; ?>&ep=<?php echo $current_ep; ?>&quality=360p" class="quality-option <?php echo $quality == '360p' ? 'active' : ''; ?>">360p</a>
                                            <a href="?slug=<?php echo $slug; ?>&ep=<?php echo $current_ep; ?>&quality=480p" class="quality-option <?php echo $quality == '480p' ? 'active' : ''; ?>">480p</a>
                                            <a href="?slug=<?php echo $slug; ?>&ep=<?php echo $current_ep; ?>&quality=720p" class="quality-option <?php echo $quality == '720p' ? 'active' : ''; ?>">720p</a>
                                            <a href="?slug=<?php echo $slug; ?>&ep=<?php echo $current_ep; ?>&quality=1080p" class="quality-option <?php echo $quality == '1080p' ? 'active' : ''; ?>">1080p</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="subtitle-selector">
                                        <button class="btn-subtitle"><i class="fa fa-closed-captioning"></i></button>
                                        <div class="subtitle-options">
                                            <div class="subtitle-option-title">Phụ đề</div>
                                            <a href="#" class="subtitle-option" data-value="off">Tắt phụ đề</a>
                                            <?php foreach ($subtitles as $subtitle): ?>
                                            <a href="#" class="subtitle-option <?php echo ($subtitle['language_code'] === $default_subtitle) ? 'active' : ''; ?>" 
                                               data-value="<?php echo $subtitle['language_code']; ?>">
                                                <?php echo $subtitle['language_name']; ?>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="audio-selector">
                                        <button class="btn-audio"><i class="fa fa-volume-up"></i></button>
                                        <div class="audio-options">
                                            <div class="audio-option-title">Ngôn ngữ âm thanh</div>
                                            <?php foreach ($audio_tracks as $track): ?>
                                            <a href="#" class="audio-option <?php echo ($track['language_code'] === $default_audio) ? 'active' : ''; ?>" 
                                               data-value="<?php echo $track['language_code']; ?>"
                                               data-url="<?php echo $track['audio_url']; ?>">
                                                <?php echo $track['language_name']; ?>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <button class="btn-settings"><i class="fa fa-cog"></i></button>
                                    <button class="btn-fullscreen"><i class="fa fa-expand"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Phụ đề -->
                        <div class="subtitles-container"></div>
                        
                        <!-- Thông báo VIP -->
                        <?php if (!can_user_watch_hd($current_user)): ?>
                        <div class="vip-notice">
                            <div class="vip-notice-content">
                                <h3>Nâng cấp lên VIP</h3>
                                <p>Để xem phim với chất lượng cao nhất và không có quảng cáo!</p>
                                <a href="vip_upgrade.php" class="btn btn-vip">Nâng cấp ngay</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Thông tin phim đang phát -->
                    <div class="playing-info">
                        <h1><?php echo $movie['title']; ?> - Tập <?php echo $episode['episode_number']; ?>: <?php echo $episode['title']; ?></h1>
                        <div class="playing-meta">
                            <span class="meta-item"><i class="fa fa-eye"></i> <?php echo format_views($movie['views']); ?> lượt xem</span>
                            <span class="meta-item"><i class="fa fa-calendar"></i> Cập nhật: <?php echo format_time($episode['created_at']); ?></span>
                            <?php if ($current_user): ?>
                            <button class="btn-favorite <?php echo is_movie_in_favorites($current_user['id'], $movie['id']) ? 'active' : ''; ?>" data-movie-id="<?php echo $movie['id']; ?>">
                                <i class="fa <?php echo is_movie_in_favorites($current_user['id'], $movie['id']) ? 'fa-heart' : 'fa-heart-o'; ?>"></i> 
                                <span>Yêu thích</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Danh sách tập -->
                    <div class="episode-list">
                        <div class="episode-header">
                            <h3>Danh sách tập</h3>
                            <div class="episode-filter">
                                <button class="btn-filter active" data-filter="all">Tất cả</button>
                                <button class="btn-filter" data-filter="watched">Đã xem</button>
                            </div>
                        </div>
                        <div class="episode-items">
                            <?php foreach ($episodes as $ep): ?>
                            <a href="?slug=<?php echo $slug; ?>&ep=<?php echo $ep['episode_number']; ?>&quality=<?php echo $quality; ?>" 
                               class="episode-item <?php echo $ep['episode_number'] == $current_ep ? 'active' : ''; ?> <?php echo ($current_user && get_user_progress($current_user['id'], $movie['id'], $ep['id'])) ? 'watched' : ''; ?>">
                                <span class="episode-number">Tập <?php echo $ep['episode_number']; ?></span>
                                <span class="episode-title"><?php echo $ep['title']; ?></span>
                                <?php if ($current_user && ($progress = get_user_progress($current_user['id'], $movie['id'], $ep['id']))): ?>
                                <div class="episode-progress">
                                    <div class="progress-bar" style="width: <?php echo min(100, round(($progress['progress'] / $ep['duration']) * 100)); ?>%"></div>
                                </div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="player-sidebar">
                    <div class="sidebar-section">
                        <h3>Phim liên quan</h3>
                        <div class="related-movies">
                            <?php
                            // Lấy phim cùng thể loại
                            $movie_categories = get_movie_categories($movie['id']);
                            $category_ids = array();
                            foreach ($movie_categories as $cat) {
                                $category_ids[] = $cat['id'];
                            }
                            
                            if (!empty($category_ids)) {
                                $related_movies = db_fetch_all(
                                    "SELECT DISTINCT m.* FROM movies m 
                                    JOIN movie_categories mc ON m.id = mc.movie_id 
                                    WHERE m.id != ? AND mc.category_id IN (" . implode(',', array_fill(0, count($category_ids), '?')) . ") 
                                    AND m.status = 1 
                                    ORDER BY m.views DESC 
                                    LIMIT 5",
                                    array_merge(array($movie['id']), $category_ids)
                                );
                                
                                foreach ($related_movies as $related): 
                            ?>
                            <div class="related-movie">
                                <a href="detail.php?slug=<?php echo $related['slug']; ?>">
                                    <div class="related-thumbnail">
                                        <img src="<?php echo $related['thumbnail']; ?>" alt="<?php echo $related['title']; ?>">
                                        <span class="related-duration"><?php echo $related['duration']; ?></span>
                                    </div>
                                    <div class="related-info">
                                        <h4><?php echo $related['title']; ?></h4>
                                        <div class="related-meta">
                                            <span><i class="fa fa-eye"></i> <?php echo format_views($related['views']); ?></span>
                                            <span><?php echo ucfirst($related['type']); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php 
                                endforeach;
                            } 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bình luận -->
        <div class="row">
            <div class="col-lg-9">
                <div class="comment-section">
                    <h3>Bình luận (<?php echo db_count("SELECT COUNT(*) FROM comments WHERE movie_id = ?", array($movie['id'])); ?>)</h3>
                    
                    <?php if ($current_user): ?>
                    <div class="comment-form">
                        <div class="user-avatar">
                            <img src="<?php echo $current_user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="<?php echo $current_user['username']; ?>">
                        </div>
                        <form id="commentForm" data-movie-id="<?php echo $movie['id']; ?>" data-episode-id="<?php echo $episode['id']; ?>">
                            <textarea name="comment" placeholder="Viết bình luận của bạn..." required></textarea>
                            <button type="submit" class="btn btn-primary">Gửi</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="login-to-comment">
                        <p>Vui lòng <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">đăng nhập</a> để bình luận.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comment-list">
                        <?php
                        $comments = get_movie_comments($movie['id']);
                        if (empty($comments)):
                        ?>
                        <div class="no-comments">
                            <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-avatar">
                                    <img src="<?php echo $comment['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="<?php echo $comment['username']; ?>">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <h4 class="comment-username"><?php echo $comment['username']; ?></h4>
                                        <span class="comment-time"><?php echo format_time($comment['created_at']); ?></span>
                                    </div>
                                    <div class="comment-text">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>
                                    <div class="comment-actions">
                                        <button class="btn-like" data-comment-id="<?php echo $comment['id']; ?>">
                                            <i class="fa fa-thumbs-up"></i> <span><?php echo $comment['likes']; ?></span>
                                        </button>
                                        <button class="btn-reply" data-comment-id="<?php echo $comment['id']; ?>">
                                            <i class="fa fa-reply"></i> Trả lời
                                        </button>
                                    </div>
                                    
                                    <?php
                                    // Lấy các phản hồi cho bình luận này
                                    $replies = db_fetch_all(
                                        "SELECT r.*, u.username, u.avatar 
                                        FROM comment_replies r 
                                        JOIN users u ON r.user_id = u.id 
                                        WHERE r.comment_id = ? 
                                        ORDER BY r.created_at ASC",
                                        array($comment['id'])
                                    );
                                    
                                    if (!empty($replies)):
                                    ?>
                                    <div class="comment-replies">
                                        <?php foreach ($replies as $reply): ?>
                                        <div class="reply-item">
                                            <div class="reply-avatar">
                                                <img src="<?php echo $reply['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="<?php echo $reply['username']; ?>">
                                            </div>
                                            <div class="reply-content">
                                                <div class="reply-header">
                                                    <h5 class="reply-username"><?php echo $reply['username']; ?></h5>
                                                    <span class="reply-time"><?php echo format_time($reply['created_at']); ?></span>
                                                </div>
                                                <div class="reply-text">
                                                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Form phản hồi (ẩn ban đầu) -->
                                    <div class="reply-form" id="replyForm-<?php echo $comment['id']; ?>" style="display: none;">
                                        <?php if ($current_user): ?>
                                        <form data-comment-id="<?php echo $comment['id']; ?>">
                                            <textarea name="reply" placeholder="Viết phản hồi của bạn..." required></textarea>
                                            <div class="reply-actions">
                                                <button type="button" class="btn btn-cancel">Hủy</button>
                                                <button type="submit" class="btn btn-primary">Gửi</button>
                                            </div>
                                        </form>
                                        <?php else: ?>
                                        <p>Vui lòng <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">đăng nhập</a> để phản hồi.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($comments) >= 10): ?>
                    <div class="load-more">
                        <button class="btn btn-outline-primary" id="loadMoreComments" data-movie-id="<?php echo $movie['id']; ?>" data-offset="10">Tải thêm bình luận</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thông tin quảng cáo (sẽ được sử dụng bởi JavaScript) -->
<?php if ($show_ads && !empty($ads)): ?>
<div id="adData" style="display: none;" 
     data-pre-roll="<?php echo htmlspecialchars(json_encode(array_filter($ads, function($ad) { return $ad['position'] == 'pre-roll'; }))); ?>"
     data-mid-roll="<?php echo htmlspecialchars(json_encode(array_filter($ads, function($ad) { return $ad['position'] == 'mid-roll'; }))); ?>"
     data-post-roll="<?php echo htmlspecialchars(json_encode(array_filter($ads, function($ad) { return $ad['position'] == 'post-roll'; }))); ?>">
</div>
<?php endif; ?>

<!-- Lưu tiến trình xem (cho phép JavaScript đọc) -->
<?php if ($watch_progress): ?>
<div id="watchProgress" style="display: none;" data-progress="<?php echo $watch_progress['progress']; ?>"></div>
<?php endif; ?>

<!-- Thêm script cho player -->
<script src="assets/js/player.js"></script>
<script src="assets/js/subtitle_audio_handlers.js"></script>
<script src="assets/js/google_drive_player.js"></script>

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
