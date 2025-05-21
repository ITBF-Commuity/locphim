<?php
/**
 * Lọc Phim - Trang xem tập phim
 */

// Lấy thông tin phim và tập từ route
$movieId = isset($params['movie_id']) ? (int)$params['movie_id'] : 0;
$episodeNumber = isset($params['episode_number']) ? (int)$params['episode_number'] : 0;

if (empty($movieId) || empty($episodeNumber)) {
    require_once 'pages/404.php';
    exit;
}

// Lấy thông tin phim từ database
$movie = $db->get("SELECT m.*, c.name as country_name 
                  FROM movies m 
                  LEFT JOIN countries c ON m.country_id = c.id 
                  WHERE m.id = ? AND m.status = 'published'", [$movieId]);

if (!$movie) {
    require_once 'pages/404.php';
    exit;
}

// Lấy thông tin tập phim
$episode = $db->get("SELECT * FROM episodes WHERE movie_id = ? AND episode_number = ?", 
                    [$movieId, $episodeNumber]);

if (!$episode) {
    // Nếu là phim lẻ và tập 1 không tồn tại, tạo tự động
    if ($movie['type'] === 'movie' && $episodeNumber === 1) {
        // Tạo tập mặc định cho phim lẻ
        $db->execute("INSERT INTO episodes (movie_id, title, season_number, episode_number, status) 
                      VALUES (?, ?, 1, 1, 'published')", 
                      [$movieId, 'Tập 1']);
        
        // Lấy lại thông tin tập vừa tạo
        $episode = $db->get("SELECT * FROM episodes WHERE movie_id = ? AND episode_number = ?", 
                            [$movieId, $episodeNumber]);
    } else {
        require_once 'pages/404.php';
        exit;
    }
}

// Kiểm tra xem phim có yêu cầu VIP không
$requireVip = $movie['is_vip'] === 1;
$userIsVip = is_vip();

// Lấy danh sách tập phim
$episodes = $db->getAll("SELECT * FROM episodes 
                        WHERE movie_id = ? 
                        ORDER BY season_number ASC, episode_number ASC", [$movieId]);

// Lấy thông tin tập trước và tập sau
$prevEpisode = null;
$nextEpisode = null;
foreach ($episodes as $index => $ep) {
    if ($ep['episode_number'] == $episodeNumber) {
        if ($index > 0) {
            $prevEpisode = $episodes[$index - 1];
        }
        if ($index < count($episodes) - 1) {
            $nextEpisode = $episodes[$index + 1];
        }
        break;
    }
}

// Lấy danh sách thể loại của phim
$categories = $db->getAll("SELECT c.* 
                          FROM categories c 
                          JOIN movie_categories mc ON c.id = mc.category_id 
                          WHERE mc.movie_id = ?", [$movieId]);

// Lấy danh sách máy chủ phát
$servers = [];
try {
    // Kiểm tra xem bảng servers có tồn tại hay không
    if ($db->getDatabaseType() === 'pgsql') {
        // PostgreSQL: dùng bảng video_sources thay thế
        $servers = $db->getAll("SELECT id, quality, source_type as server_type, is_default 
                              FROM video_sources 
                              WHERE episode_id = ?
                              ORDER BY is_default DESC, quality DESC", [$episode['id']]);
    } else {
        // MySQL/SQLite: dùng bảng servers
        $servers = $db->getAll("SELECT * FROM servers 
                             WHERE (episode_id = ? OR episode_id IS NULL) 
                             ORDER BY is_default DESC, quality DESC", [$episode['id']]);
    }
} catch (Exception $e) {
    // Nếu có lỗi, sử dụng mảng rỗng
    error_log("Lỗi lấy danh sách servers: " . $e->getMessage());
}

if (empty($servers)) {
    try {
        // Tạo máy chủ mặc định nếu không có
        if ($db->getDatabaseType() === 'pgsql') {
            // PostgreSQL: dùng bảng video_sources
            $serverId = $db->execute("INSERT INTO video_sources 
                                    (episode_id, quality, source_url, source_type, is_default, created_at, updated_at) 
                                    VALUES (?, 'HD', '#', 'direct', TRUE, NOW(), NOW())", 
                                    [$episode['id']]);
            
            // Lấy lại danh sách máy chủ
            $servers = $db->getAll("SELECT id, quality, source_type as server_type, is_default 
                                  FROM video_sources 
                                  WHERE episode_id = ?
                                  ORDER BY is_default DESC, quality DESC", [$episode['id']]);
        } else {
            // MySQL/SQLite: dùng bảng servers
            $serverId = $db->execute("INSERT INTO servers (episode_id, name, url, quality, is_default) 
                                    VALUES (?, 'Máy chủ #1', '#', 'HD', 1)", 
                                    [$episode['id']]);
            
            // Lấy lại danh sách máy chủ
            $servers = $db->getAll("SELECT * FROM servers WHERE episode_id = ?", [$episode['id']]);
        }
    } catch (Exception $e) {
        // Nếu có lỗi, vẫn giữ mảng rỗng
        error_log("Lỗi tạo máy chủ mặc định: " . $e->getMessage());
    }
}

// Lấy máy chủ mặc định
$defaultServer = null;
foreach ($servers as $server) {
    if ($server['is_default']) {
        $defaultServer = $server;
        break;
    }
}

if (!$defaultServer && !empty($servers)) {
    $defaultServer = $servers[0];
}

// Lấy lịch sử xem của người dùng
$watchHistory = null;
if (isset($_SESSION['user_id'])) {
    // Sử dụng cả created_at hoặc last_watched_at (tùy theo database)
    $timeColumn = $db->getDbType() === 'pgsql' ? 'last_watched_at' : 'created_at';
    
    $watchHistory = $db->get("SELECT id, user_id, episode_id, watched_seconds, completed, "
                           . $timeColumn . " as created_at "
                           . "FROM watch_history 
                             WHERE user_id = ? AND episode_id = ?", 
                             [$_SESSION['user_id'], $episode['id']]);
}

// Lấy danh sách bình luận
$comments = $db->getAll("SELECT cm.*, u.username, u.avatar 
                        FROM comments cm 
                        JOIN users u ON cm.user_id = u.id 
                        WHERE (cm.movie_id = ? AND cm.episode_id = ?) 
                           OR (cm.movie_id = ? AND cm.episode_id IS NULL) 
                        ORDER BY cm.created_at DESC
                        LIMIT 20", [$movieId, $episode['id'], $movieId]);

// Lấy danh sách trả lời cho mỗi bình luận
foreach ($comments as &$comment) {
    $comment['replies'] = $db->getAll("SELECT cm.*, u.username, u.avatar 
                                      FROM comments cm 
                                      JOIN users u ON cm.user_id = u.id 
                                      WHERE cm.parent_id = ? 
                                      ORDER BY cm.created_at ASC", [$comment['id']]);
                                      
    // Format thời gian
    $comment['created_at_formatted'] = format_time_ago($comment['created_at']);
    
    // Format thời gian cho các replies
    foreach ($comment['replies'] as &$reply) {
        $reply['created_at_formatted'] = format_time_ago($reply['created_at']);
    }
}

// Kiểm tra xem người dùng đã thích phim chưa
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $favorite = $db->get("SELECT * FROM favorites WHERE user_id = ? AND movie_id = ?", [$_SESSION['user_id'], $movieId]);
    $isFavorite = $favorite !== null;
}

// Set title và description cho trang
$episodeTitle = !empty($episode['title']) && $episode['title'] !== 'Tập ' . $episode['episode_number'] 
    ? $episode['title'] 
    : 'Tập ' . $episode['episode_number'];

// Kiểm tra xem season_number có tồn tại trong mảng không
$seasonText = '';
if (isset($episode['season_number']) && $episode['season_number'] > 1) {
    $seasonText = 'Season ' . $episode['season_number'] . ' - ';
}
$pageTitle = $movie['title'] . ' - ' . $seasonText . $episodeTitle . ' - ' . SITE_NAME;
$pageDescription = 'Xem phim ' . $movie['title'] . ' ' . $seasonText . $episodeTitle . '. ' . substr(strip_tags($movie['description']), 0, 120);
$ogImage = image_url($movie['poster']);

// Tạo breadcrumbs
$breadcrumbs = [
    ['name' => 'Trang chủ', 'url' => url('')],
];

// Thêm thể loại vào breadcrumbs
if (!empty($categories)) {
    $breadcrumbs[] = ['name' => $categories[0]['name'], 'url' => url('the-loai/' . $categories[0]['slug'])];
}

// Thêm tên phim vào breadcrumbs
$breadcrumbs[] = ['name' => $movie['title'], 'url' => url('phim/' . $movie['slug'] . '/' . $movie['id'])];

// Thêm tập phim vào breadcrumbs
$breadcrumbs[] = ['name' => $episodeTitle, 'url' => ''];

// Bắt đầu output buffering
ob_start();
?>

<div class="episode-page">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <div class="container">
            <ul class="breadcrumb-list">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="breadcrumb-item">
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['name']; ?></a>
                        <?php else: ?>
                            <span><?php echo $crumb['name']; ?></span>
                        <?php endif; ?>
                    </li>
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <li class="breadcrumb-separator"><i class="fas fa-angle-right"></i></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Movie Player -->
    <div class="player-section">
        <div class="container">
            <div class="player-wrapper">
                <div class="player-header">
                    <h1 class="movie-title">
                        <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </a> - 
                        <span class="episode-title">
                            <?php if (isset($episode['season_number']) && $episode['season_number'] > 1): ?>
                                Season <?php echo $episode['season_number']; ?> - 
                            <?php endif; ?>
                            <?php echo htmlspecialchars($episodeTitle); ?>
                        </span>
                    </h1>
                    
                    <div class="player-actions">
                        <button class="report-button">
                            <i class="fas fa-exclamation-triangle"></i> Báo lỗi
                        </button>
                        <button class="lights-button" id="lights-toggle">
                            <i class="fas fa-lightbulb"></i> Tắt đèn
                        </button>
                        <button class="settings-button" id="player-settings-button">
                            <i class="fas fa-cog"></i> Cài đặt
                        </button>
                    </div>
                </div>
                
                <div class="player-container" id="player-container">
                    <?php if ($requireVip && !$userIsVip): ?>
                        <!-- VIP notice -->
                        <div class="vip-player-notice">
                            <div class="vip-notice-content">
                                <div class="vip-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <h3>Phim yêu cầu tài khoản VIP</h3>
                                <p>Để xem phim này với chất lượng tốt nhất và không có quảng cáo, bạn cần nâng cấp tài khoản lên VIP.</p>
                                <div class="vip-actions">
                                    <a href="<?php echo url('vip'); ?>" class="btn btn-vip">Nâng cấp VIP ngay</a>
                                    <button class="btn btn-outline watch-free" id="watch-free-btn">Xem phim (có quảng cáo, chất lượng thấp)</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Free player (hidden by default) -->
                        <div class="video-player-wrapper" id="video-player-wrapper" style="display: none;">
                            <video 
                                id="video-player" 
                                class="video-js vjs-big-play-centered vjs-theme-locphim" 
                                controls 
                                preload="auto" 
                                poster="<?php echo image_url($movie['backdrop'] ?: $movie['poster']); ?>"
                                data-movie-id="<?php echo $movie['id']; ?>"
                                data-episode-id="<?php echo $episode['id']; ?>"
                                data-setup='{
                                    "playbackRates": [0.5, 0.75, 1, 1.25, 1.5, 2],
                                    "autoplay": false
                                }'>
                                <source src="<?php echo $defaultServer ? $defaultServer['url'] : '#'; ?>" type="application/x-mpegURL">
                                <p class="vjs-no-js">Để xem video này, vui lòng bật JavaScript và cập nhật trình duyệt của bạn.</p>
                            </video>
                        </div>
                    <?php else: ?>
                        <!-- Regular player -->
                        <div class="video-player-wrapper">
                            <video 
                                id="video-player" 
                                class="video-js vjs-big-play-centered vjs-theme-locphim" 
                                controls 
                                preload="auto" 
                                poster="<?php echo image_url($movie['backdrop'] ?: $movie['poster']); ?>"
                                data-movie-id="<?php echo $movie['id']; ?>"
                                data-episode-id="<?php echo $episode['id']; ?>"
                                data-setup='{
                                    "playbackRates": [0.5, 0.75, 1, 1.25, 1.5, 2],
                                    "autoplay": false
                                }'>
                                <source src="<?php echo $defaultServer ? $defaultServer['url'] : '#'; ?>" type="application/x-mpegURL">
                                <p class="vjs-no-js">Để xem video này, vui lòng bật JavaScript và cập nhật trình duyệt của bạn.</p>
                            </video>
                        </div>
                    <?php endif; ?>
                    
                    <div class="player-controls">
                        <div class="episode-navigation">
                            <?php if ($prevEpisode): ?>
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-' . $prevEpisode['episode_number']); ?>" class="episode-nav prev-episode">
                                    <i class="fas fa-step-backward"></i>
                                    <span>Tập trước</span>
                                </a>
                            <?php else: ?>
                                <span class="episode-nav prev-episode disabled">
                                    <i class="fas fa-step-backward"></i>
                                    <span>Tập trước</span>
                                </span>
                            <?php endif; ?>
                            
                            <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="episode-list-button">
                                <i class="fas fa-list"></i>
                                <span>Danh sách tập</span>
                            </a>
                            
                            <?php if ($nextEpisode): ?>
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-' . $nextEpisode['episode_number']); ?>" class="episode-nav next-episode">
                                    <span>Tập sau</span>
                                    <i class="fas fa-step-forward"></i>
                                </a>
                            <?php else: ?>
                                <span class="episode-nav next-episode disabled">
                                    <span>Tập sau</span>
                                    <i class="fas fa-step-forward"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Độ phân giải sẽ được hiển thị trong menu cài đặt qua JavaScript -->
                        <div id="player-settings-menu" class="player-settings-menu" style="display: none;">
                            <?php if (!empty($servers) && count($servers) > 1): ?>
                                <div class="settings-section">
                                    <h4 class="settings-title">Độ phân giải</h4>
                                    <div class="resolution-options">
                                        <?php foreach ($servers as $index => $server): ?>
                                            <button 
                                                class="resolution-option <?php echo (isset($server['is_default']) && $server['is_default']) ? 'active' : ''; ?>" 
                                                data-server-id="<?php echo isset($server['id']) ? $server['id'] : ''; ?>"
                                                data-server-url="<?php echo isset($server['url']) ? $server['url'] : ''; ?>">
                                                <?php if (isset($server['quality']) && !empty($server['quality'])): ?>
                                                    <?php echo htmlspecialchars($server['quality']); ?>
                                                <?php else: ?>
                                                    <?php echo isset($server['name']) ? htmlspecialchars($server['name']) : 'Mặc định'; ?>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Episode Content -->
    <div class="episode-content">
        <div class="container">
            <div class="content-layout">
                <div class="main-content">
                    <!-- Episode Info -->
                    <div class="episode-info card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <?php echo htmlspecialchars($movie['title']); ?> - 
                                <?php if (isset($episode['season_number']) && $episode['season_number'] > 1): ?>
                                    Season <?php echo $episode['season_number']; ?> - 
                                <?php endif; ?>
                                <?php echo htmlspecialchars($episodeTitle); ?>
                            </h2>
                            
                            <div class="movie-actions">
                                <button class="btn btn-outline favorite-button <?php echo $isFavorite ? 'favorited' : ''; ?>" data-movie-id="<?php echo $movie['id']; ?>">
                                    <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span><?php echo $isFavorite ? 'Đã yêu thích' : 'Yêu thích'; ?></span>
                                </button>
                                
                                <div class="share-buttons">
                                    <button class="share-button" id="share-fb" data-url="<?php echo url($_SERVER['REQUEST_URI']); ?>">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="share-button" id="share-twitter" data-url="<?php echo url($_SERVER['REQUEST_URI']); ?>" data-title="<?php echo htmlspecialchars($pageTitle); ?>">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="share-button" id="share-link" data-url="<?php echo url($_SERVER['REQUEST_URI']); ?>">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($episode['description'])): ?>
                                <div class="episode-description">
                                    <?php echo nl2br(htmlspecialchars($episode['description'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="episode-description">
                                    <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Episode List -->
                    <div class="episodes-list card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách tập</h3>
                            
                            <?php
                            // Lấy danh sách các season khác nhau
                            $seasons = [];
                            foreach ($episodes as $ep) {
                                if (!in_array($ep['season_number'], $seasons)) {
                                    $seasons[] = $ep['season_number'];
                                }
                            }
                            sort($seasons);
                            
                            // Nếu có nhiều season
                            if (count($seasons) > 1):
                            ?>
                            <div class="episode-tabs">
                                <?php foreach ($seasons as $index => $seasonNumber): ?>
                                    <button class="episode-tab <?php echo (isset($episode['season_number']) && $episode['season_number'] === $seasonNumber) ? 'active' : ''; ?>" data-target="season-<?php echo $seasonNumber; ?>">
                                        Season <?php echo $seasonNumber; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <?php foreach ($seasons as $index => $seasonNumber): ?>
                                <div class="episode-content-tab <?php echo (isset($episode['season_number']) && $episode['season_number'] === $seasonNumber) ? 'active' : ''; ?>" id="season-<?php echo $seasonNumber; ?>">
                                    <div class="episode-grid">
                                        <?php foreach ($episodes as $ep): ?>
                                            <?php if ($ep['season_number'] === $seasonNumber): ?>
                                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-' . $ep['episode_number']); ?>" class="episode-item <?php echo $ep['episode_number'] === $episode['episode_number'] ? 'active' : ''; ?>">
                                                    <div class="episode-number"><?php echo $ep['episode_number']; ?></div>
                                                    <div class="episode-title">
                                                        <?php 
                                                        if (!empty($ep['title']) && $ep['title'] !== 'Tập ' . $ep['episode_number']) {
                                                            echo htmlspecialchars($ep['title']);
                                                        } else {
                                                            echo 'Tập ' . $ep['episode_number'];
                                                        }
                                                        ?>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="episode-comments card">
                        <div class="card-header">
                            <h3 class="card-title">Bình luận</h3>
                        </div>
                        
                        <div class="card-body">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- Comment Form -->
                                <div class="comment-form-container">
                                    <form id="comment-form" class="comment-form">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                        <input type="hidden" name="episode_id" value="<?php echo $episode['id']; ?>">
                                        <div class="form-group">
                                            <textarea name="content" placeholder="Viết bình luận của bạn..." required></textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="login-to-comment">
                                    <p>Vui lòng <a href="<?php echo url('dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>">đăng nhập</a> để bình luận.</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Comments List -->
                            <div class="comments-list">
                                <?php if (empty($comments)): ?>
                                    <div class="no-comments">
                                        <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                                            <div class="comment-avatar">
                                                <img src="<?php echo !empty($comment['avatar']) ? url($comment['avatar']) : url('assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                            </div>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <div class="comment-user"><?php echo htmlspecialchars($comment['username']); ?></div>
                                                    <div class="comment-date"><?php echo $comment['created_at_formatted']; ?></div>
                                                </div>
                                                <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                                <div class="comment-actions">
                                                    <button class="like-button" onclick="likeComment(<?php echo $comment['id']; ?>)">
                                                        <i class="far fa-thumbs-up"></i> <span><?php echo $comment['likes']; ?></span>
                                                    </button>
                                                    <?php if (isset($_SESSION['user_id'])): ?>
                                                        <button class="reply-button" data-comment-id="<?php echo $comment['id']; ?>">
                                                            <i class="far fa-comment"></i> Trả lời
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Reply Form Container -->
                                                <div class="reply-form-container" id="reply-form-<?php echo $comment['id']; ?>"></div>
                                                
                                                <!-- Replies List -->
                                                <?php if (!empty($comment['replies'])): ?>
                                                    <div class="replies" id="replies-<?php echo $comment['id']; ?>">
                                                        <?php foreach ($comment['replies'] as $reply): ?>
                                                            <div class="reply" id="reply-<?php echo $reply['id']; ?>">
                                                                <div class="reply-avatar">
                                                                    <img src="<?php echo !empty($reply['avatar']) ? url($reply['avatar']) : url('assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($reply['username']); ?>">
                                                                </div>
                                                                <div class="reply-content">
                                                                    <div class="reply-header">
                                                                        <div class="reply-user"><?php echo htmlspecialchars($reply['username']); ?></div>
                                                                        <div class="reply-date"><?php echo $reply['created_at_formatted']; ?></div>
                                                                    </div>
                                                                    <div class="reply-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                                                                    <div class="reply-actions">
                                                                        <button class="like-button" onclick="likeReply(<?php echo $reply['id']; ?>)">
                                                                            <i class="far fa-thumbs-up"></i> <span><?php echo $reply['likes']; ?></span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="replies" id="replies-<?php echo $comment['id']; ?>"></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="side-content">
                    <!-- Movie Info -->
                    <div class="movie-details card">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin phim</h3>
                        </div>
                        
                        <div class="card-body">
                            <div class="movie-poster-side">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                </a>
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="movie-info-side">
                                <h4 class="movie-title-side">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h4>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title-side">
                                        <?php echo htmlspecialchars($movie['original_title']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="movie-meta-side">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                    
                                    <span class="type">
                                        <?php 
                                        switch ($movie['type']) {
                                            case 'movie':
                                                echo 'Phim Lẻ';
                                                break;
                                            case 'series':
                                                echo 'Phim Bộ';
                                                break;
                                            case 'anime':
                                                echo 'Anime';
                                                break;
                                            default:
                                                echo ucfirst($movie['type']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="category-tags">
                                    <?php foreach ($categories as $category): ?>
                                        <a href="<?php echo url('the-loai/' . $category['slug']); ?>" class="category-tag-small">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php if ($requireVip && !$userIsVip): ?>
                                <div class="vip-notice-side">
                                    <div class="vip-icon-small">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="vip-message-small">
                                        <p>Phim này yêu cầu tài khoản VIP</p>
                                        <a href="<?php echo url('vip'); ?>" class="btn btn-vip btn-sm">Nâng cấp VIP</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recommended Movies -->
                    <div class="recommended-movies card">
                        <div class="card-header">
                            <h3 class="card-title">Có thể bạn thích</h3>
                        </div>
                        
                        <div class="card-body">
                            <div class="recommended-movies-list">
                                <?php
                                // Lấy danh sách đề xuất (đơn giản là phim cùng thể loại)
                                $recommendedMovies = $db->getAll("SELECT DISTINCT m.*, c.name as country_name
                                                                  FROM movies m 
                                                                  JOIN movie_categories mc1 ON m.id = mc1.movie_id 
                                                                  JOIN movie_categories mc2 ON mc1.category_id = mc2.category_id 
                                                                  LEFT JOIN countries c ON m.country_id = c.id
                                                                  WHERE mc2.movie_id = ? AND m.id != ? AND m.status = 'published'
                                                                  ORDER BY m.views DESC
                                                                  LIMIT 6", [$movieId, $movieId]);
                                
                                foreach ($recommendedMovies as $recMovie):
                                ?>
                                    <div class="recommended-movie">
                                        <div class="recommended-movie-poster">
                                            <a href="<?php echo url('phim/' . $recMovie['slug'] . '/' . $recMovie['id']); ?>">
                                                <img src="<?php echo image_url($recMovie['poster']); ?>" alt="<?php echo htmlspecialchars($recMovie['title']); ?>" loading="lazy">
                                            </a>
                                            <?php if ($recMovie['is_vip']): ?>
                                                <div class="vip-badge small">VIP</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="recommended-movie-info">
                                            <h4 class="recommended-movie-title">
                                                <a href="<?php echo url('phim/' . $recMovie['slug'] . '/' . $recMovie['id']); ?>">
                                                    <?php echo htmlspecialchars($recMovie['title']); ?>
                                                </a>
                                            </h4>
                                            <div class="recommended-movie-meta">
                                                <?php if (!empty($recMovie['release_year'])): ?>
                                                    <span class="year"><?php echo $recMovie['release_year']; ?></span>
                                                <?php endif; ?>
                                                
                                                <span class="type">
                                                    <?php 
                                                    switch ($recMovie['type']) {
                                                        case 'movie':
                                                            echo 'Phim Lẻ';
                                                            break;
                                                        case 'series':
                                                            echo 'Phim Bộ';
                                                            break;
                                                        case 'anime':
                                                            echo 'Anime';
                                                            break;
                                                        default:
                                                            echo ucfirst($recMovie['type']);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal" id="report-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Báo cáo lỗi</h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="report-form">
                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                <input type="hidden" name="episode_id" value="<?php echo $episode['id']; ?>">
                
                <div class="form-group">
                    <label>Vấn đề bạn gặp phải</label>
                    <select name="issue_type" required>
                        <option value="">-- Chọn vấn đề --</option>
                        <option value="video_not_playing">Video không phát</option>
                        <option value="wrong_video">Video không đúng phim</option>
                        <option value="audio_issues">Vấn đề về âm thanh</option>
                        <option value="subtitle_issues">Vấn đề về phụ đề</option>
                        <option value="synchronization">Video và âm thanh không đồng bộ</option>
                        <option value="quality_issues">Chất lượng video kém</option>
                        <option value="other">Vấn đề khác</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mô tả chi tiết</label>
                    <textarea name="description" rows="4" placeholder="Vui lòng mô tả chi tiết vấn đề bạn gặp phải..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="cancel-report">Hủy</button>
                    <button type="submit" class="btn btn-primary">Gửi báo cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link to video.js CSS and JS (replace with actual paths) -->
<link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet" />
<script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>

<style>
    /* Player Section */
    .player-section {
        background-color: var(--bg-light);
        padding: 1rem 0;
    }
    
    .player-wrapper {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    
    .player-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .player-header .movie-title {
        font-size: 1.25rem;
        margin: 0;
    }
    
    .player-header .movie-title a {
        color: var(--text-color);
    }
    
    .player-header .movie-title a:hover {
        color: var(--primary-color);
    }
    
    .player-header .episode-title {
        font-weight: normal;
    }
    
    .player-actions {
        display: flex;
        gap: 1rem;
    }
    
    .report-button,
    .lights-button,
    .settings-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        background-color: var(--bg-light);
        color: var(--text-color);
        font-size: 0.875rem;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }
    
    .report-button:hover,
    .lights-button:hover,
    .settings-button:hover {
        background-color: var(--bg-color);
        color: var(--primary-color);
    }
    
    .settings-button {
        position: relative;
    }
    
    .player-container {
        position: relative;
        background-color: #000;
    }
    
    .video-player-wrapper {
        position: relative;
        padding-top: 56.25%; /* 16:9 Aspect Ratio */
    }
    
    .video-js {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    /* Player Controls */
    .player-controls {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
    }
    
    .episode-navigation {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .episode-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        background-color: var(--bg-light);
        color: var(--text-color);
        transition: var(--transition);
    }
    
    .episode-nav:not(.disabled):hover {
        background-color: var(--primary-color);
        color: white;
    }
    
    .episode-nav.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .episode-list-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        background-color: var(--secondary-color);
        color: white;
        transition: var(--transition);
    }
    
    .episode-list-button:hover {
        background-color: var(--secondary-hover);
        color: white;
    }
    
    .server-selection {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .server-label {
        font-weight: var(--font-weight-medium);
        color: var(--text-muted);
    }
    
    .server-options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .server-option {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        background-color: var(--bg-light);
        color: var(--text-color);
        font-size: 0.875rem;
        transition: var(--transition);
        border: 1px solid var(--border-color);
        cursor: pointer;
    }
    
    .server-option:hover,
    .server-option.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .server-quality {
        padding: 0.125rem 0.375rem;
        border-radius: 4px;
        background-color: rgba(255, 255, 255, 0.2);
        font-size: 0.75rem;
    }
    
    /* VIP Notice */
    .vip-player-notice {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 10;
    }
    
    .vip-notice-content {
        max-width: 500px;
        padding: 2rem;
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        text-align: center;
    }
    
    .vip-notice-content .vip-icon {
        font-size: 3rem;
        color: var(--vip-color);
        margin-bottom: 1rem;
    }
    
    .vip-notice-content h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .vip-notice-content p {
        margin-bottom: 1.5rem;
        color: var(--text-muted);
    }
    
    .vip-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn-vip {
        background-color: var(--vip-color);
        color: var(--bg-dark);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        font-weight: var(--font-weight-medium);
        cursor: pointer;
    }
    
    /* Lights Off Mode */
    body.lights-off {
        --bg-color: #000;
        --bg-light: #111;
        --card-bg: #1a1a1a;
        --border-color: #333;
    }
    
    body.lights-off .player-section {
        background-color: #000;
    }
    
    body.lights-off .breadcrumbs,
    body.lights-off .episode-content,
    body.lights-off .header,
    body.lights-off .main-nav,
    body.lights-off .footer {
        display: none;
    }
    
    body.lights-off .player-wrapper {
        border: none;
        border-radius: 0;
    }
    
    /* Episode Content */
    .episode-content {
        padding: 2rem 0;
    }
    
    .content-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    
    /* Episode Info */
    .movie-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .episode-description {
        line-height: 1.7;
    }
    
    /* Episodes List */
    .episode-tabs {
        display: flex;
    }
    
    .episode-tab {
        padding: 0.5rem 1rem;
        background-color: var(--bg-light);
        border: none;
        color: var(--text-color);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .episode-tab.active {
        background-color: var(--primary-color);
        color: white;
    }
    
    .episode-content-tab {
        display: none;
    }
    
    .episode-content-tab.active {
        display: block;
    }
    
    .episode-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.75rem;
    }
    
    .episode-item {
        display: flex;
        flex-direction: column;
        padding: 0.75rem;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background-color: var(--bg-light);
        color: var(--text-color);
        text-align: center;
        transition: var(--transition);
    }
    
    .episode-item:hover {
        border-color: var(--primary-color);
        background-color: var(--primary-color);
        color: white;
    }
    
    .episode-item.active {
        border-color: var(--primary-color);
        background-color: var(--primary-color);
        color: white;
    }
    
    .episode-number {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .episode-title {
        font-size: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Movie Info Side */
    .movie-poster-side {
        margin-bottom: 1rem;
        position: relative;
    }
    
    .movie-poster-side img {
        width: 100%;
        height: auto;
        border-radius: var(--border-radius);
    }
    
    .movie-poster-side .vip-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--vip-color);
        color: var(--bg-dark);
        font-size: 0.75rem;
        font-weight: bold;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    
    .movie-title-side {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    
    .movie-title-side a {
        color: var(--text-color);
    }
    
    .movie-title-side a:hover {
        color: var(--primary-color);
    }
    
    .movie-original-title-side {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }
    
    .movie-meta-side {
        display: flex;
        gap: 0.75rem;
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
    }
    
    .category-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .category-tag-small {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        background-color: var(--bg-light);
        color: var(--text-color);
        font-size: 0.75rem;
        transition: var(--transition);
    }
    
    .category-tag-small:hover {
        background-color: var(--primary-color);
        color: white;
    }
    
    .vip-notice-side {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background-color: var(--vip-bg);
        border-radius: var(--border-radius);
    }
    
    .vip-icon-small {
        font-size: 1.5rem;
        color: var(--vip-color);
    }
    
    .vip-message-small p {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    
    .btn-vip.btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Recommended Movies */
    .recommended-movies-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .recommended-movie {
        display: flex;
        gap: 0.75rem;
    }
    
    .recommended-movie-poster {
        width: 70px;
        height: 105px;
        border-radius: var(--border-radius);
        overflow: hidden;
        flex-shrink: 0;
        position: relative;
    }
    
    .recommended-movie-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .vip-badge.small {
        font-size: 0.625rem;
        padding: 0.125rem 0.375rem;
    }
    
    .recommended-movie-info {
        flex: 1;
    }
    
    .recommended-movie-title {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .recommended-movie-title a {
        color: var(--text-color);
    }
    
    .recommended-movie-title a:hover {
        color: var(--primary-color);
    }
    
    .recommended-movie-meta {
        display: flex;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    
    /* Modal */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        display: none;
    }
    
    .modal.active {
        display: block;
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 500px;
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .modal-title {
        margin: 0;
        font-size: 1.25rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .modal-close:hover {
        color: var(--text-color);
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    /* Video.js Theme */
    .vjs-theme-locphim {
        --vjs-theme-primary: var(--primary-color);
    }
    
    .vjs-theme-locphim .vjs-big-play-button {
        width: 70px;
        height: 70px;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        line-height: 70px;
        font-size: 2.5rem;
        border: none;
    }
    
    .vjs-theme-locphim:hover .vjs-big-play-button,
    .vjs-theme-locphim.vjs-big-play-button:focus {
        background-color: rgba(var(--primary-color), 0.8);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .content-layout {
            grid-template-columns: 1fr;
        }
        
        .episode-grid {
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .player-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .episode-navigation {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .episode-nav,
        .episode-list-button {
            width: 100%;
            justify-content: center;
        }
        
        .server-selection {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .server-options {
            width: 100%;
        }
        
        .server-option {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý đèn tắt/bật
    const lightsToggle = document.getElementById('lights-toggle');
    if (lightsToggle) {
        lightsToggle.addEventListener('click', function() {
            document.body.classList.toggle('lights-off');
        });
    }
    
    // Xử lý chọn máy chủ
    const serverOptions = document.querySelectorAll('.server-option');
    const videoPlayer = document.getElementById('video-player');
    
    if (serverOptions.length && videoPlayer) {
        const player = videojs('video-player');
        
        serverOptions.forEach(option => {
            option.addEventListener('click', function() {
                const serverUrl = this.dataset.serverUrl;
                
                // Cập nhật nguồn video
                player.src(serverUrl);
                player.load();
                
                // Cập nhật UI
                serverOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Xử lý nút xem phim miễn phí
    const watchFreeBtn = document.getElementById('watch-free-btn');
    const videoPlayerWrapper = document.getElementById('video-player-wrapper');
    
    if (watchFreeBtn && videoPlayerWrapper) {
        watchFreeBtn.addEventListener('click', function() {
            document.querySelector('.vip-player-notice').style.display = 'none';
            videoPlayerWrapper.style.display = 'block';
            
            // Khởi tạo player
            const player = videojs('video-player');
            
            // Thêm quảng cáo
            // (Quảng cáo sẽ được xử lý bằng plugin khác)
        });
    }
    
    // Xử lý modal báo cáo
    const reportBtn = document.querySelector('.report-button');
    const reportModal = document.getElementById('report-modal');
    const modalClose = document.querySelector('.modal-close');
    const cancelReport = document.getElementById('cancel-report');
    const reportForm = document.getElementById('report-form');
    
    if (reportBtn && reportModal) {
        reportBtn.addEventListener('click', function() {
            reportModal.classList.add('active');
        });
        
        if (modalClose) {
            modalClose.addEventListener('click', function() {
                reportModal.classList.remove('active');
            });
        }
        
        if (cancelReport) {
            cancelReport.addEventListener('click', function() {
                reportModal.classList.remove('active');
            });
        }
        
        reportModal.querySelector('.modal-overlay').addEventListener('click', function() {
            reportModal.classList.remove('active');
        });
        
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(reportForm);
                
                // Gửi báo cáo lỗi
                fetch('/api/report-issue', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hiển thị thông báo
                        showToast('Báo cáo lỗi đã được gửi thành công!', 'success');
                        
                        // Đóng modal
                        reportModal.classList.remove('active');
                        
                        // Reset form
                        reportForm.reset();
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra khi gửi báo cáo.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi gửi báo cáo.', 'error');
                });
            });
        }
    }
    
    // Khởi tạo các nút chia sẻ
    const shareFb = document.getElementById('share-fb');
    const shareTwitter = document.getElementById('share-twitter');
    const shareLink = document.getElementById('share-link');
    
    if (shareFb) {
        shareFb.addEventListener('click', function() {
            const url = this.dataset.url;
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank');
        });
    }
    
    if (shareTwitter) {
        shareTwitter.addEventListener('click', function() {
            const url = this.dataset.url;
            const title = this.dataset.title;
            window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
        });
    }
    
    if (shareLink) {
        shareLink.addEventListener('click', function() {
            const url = this.dataset.url;
            
            // Tạo input tạm thời
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            
            showToast('Đã sao chép liên kết vào clipboard!', 'success');
        });
    }
});
</script>

<?php
// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>