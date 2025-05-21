<?php
/**
 * Lọc Phim - Trang xem phim
 * 
 * File hiển thị trang xem phim và tập phim
 */

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$episodeNumber = isset($_GET['episode']) ? intval($_GET['episode']) : 1;

if (empty($slug)) {
    // Nếu không có slug, chuyển hướng về trang chủ
    redirect('/');
}

// Lấy thông tin phim từ database
$movie = $db->get("
    SELECT m.*, 
           " . dbGroupConcat('g.name') . " AS genres,
           " . dbGroupConcat('g.slug') . " AS genre_slugs,
           " . dbGroupConcat('c.name') . " AS countries,
           " . dbGroupConcat('c.slug') . " AS country_slugs
    FROM movies m
    LEFT JOIN movie_genres mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    LEFT JOIN movie_countries mc ON m.id = mc.movie_id
    LEFT JOIN countries c ON mc.country_id = c.id
    WHERE m.slug = ? AND m.status = 'published'
    GROUP BY m.id, m.title, m.slug, m.poster, m.backdrop, m.overview, m.release_date, m.rating, m.duration, m.is_vip, m.type, m.status, m.views
", [$slug]);

if (!$movie) {
    // Nếu không tìm thấy phim, chuyển hướng đến trang 404
    redirect('/404');
}

// Kiểm tra quyền xem phim VIP
if ($movie['is_vip'] && !isVip()) {
    // Chuyển hướng đến trang VIP
    redirect('/vip?from=watch&slug=' . $slug . '&episode=' . $episodeNumber);
}

// Lấy thông tin tập hiện tại
$episode = $db->get("
    SELECT e.*
    FROM episodes e
    WHERE e.movie_id = ? AND e.episode_number = ? AND e.status = 'published'
    LIMIT 1
", [$movie['id'], $episodeNumber]);

if (!$episode && $episodeNumber > 1) {
    // Nếu không tìm thấy tập, chuyển về tập 1
    redirect('/xem/' . $slug . '/1');
}

// Nếu là phim lẻ và không có tập nào, tạo tập ảo
if (!$episode && $movie['type'] == 'single') {
    $episode = [
        'id' => 0,
        'movie_id' => $movie['id'],
        'episode_number' => 1,
        'title' => $movie['title'],
        'overview' => $movie['overview'],
        'duration' => $movie['duration'],
        'views' => $movie['views'],
        'status' => 'published'
    ];
}

// Lấy danh sách tập phim
$episodes = [];
if ($movie['type'] == 'series') {
    $episodes = $db->getAll("
        SELECT e.*
        FROM episodes e
        WHERE e.movie_id = ? AND e.status = 'published'
        ORDER BY e.episode_number ASC
    ", [$movie['id']]);
}

// Lấy thông tin tập trước và tập sau
$prevEpisode = null;
$nextEpisode = null;

if ($movie['type'] == 'series') {
    // Tập trước
    $prevEpisode = $db->get("
        SELECT e.episode_number
        FROM episodes e
        WHERE e.movie_id = ? AND e.episode_number < ? AND e.status = 'published'
        ORDER BY e.episode_number DESC
        LIMIT 1
    ", [$movie['id'], $episodeNumber]);
    
    // Tập sau
    $nextEpisode = $db->get("
        SELECT e.episode_number
        FROM episodes e
        WHERE e.movie_id = ? AND e.episode_number > ? AND e.status = 'published'
        ORDER BY e.episode_number ASC
        LIMIT 1
    ", [$movie['id'], $episodeNumber]);
}

// Lấy thông tin các nguồn video
$sources = $db->getAll("
    SELECT vs.*
    FROM video_sources vs
    WHERE vs.episode_id = ?
    ORDER BY vs.quality DESC
", [$episode['id']]);

// Lấy thông tin phụ đề
$subtitles = $db->getAll("
    SELECT s.*
    FROM subtitles s
    WHERE s.episode_id = ?
    ORDER BY s.language ASC
", [$episode['id']]);

// Cập nhật lượt xem
$db->execute("UPDATE episodes SET views = views + 1 WHERE id = ?", [$episode['id']]);
$db->execute("UPDATE movies SET views = views + 1 WHERE id = ?", [$movie['id']]);

// Kiểm tra đã xem tới đâu
$watchProgress = 0;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $progress = $db->get("
        SELECT current_time
        FROM watch_progress
        WHERE user_id = ? AND episode_id = ?
    ", [$currentUser['id'], $episode['id']]);
    
    if ($progress) {
        $watchProgress = $progress['current_time'];
    }
}

// Lấy danh sách phim đề xuất
$recommendedMovies = $db->getAll("
    SELECT m.id, m.title, m.slug, m.poster, m.rating, m.duration, m.type, m.is_vip, m.release_date, m.views
    FROM movies m
    JOIN movie_genres mg ON m.id = mg.movie_id
    JOIN movie_genres mg2 ON mg.genre_id = mg2.genre_id
    WHERE mg2.movie_id = ? AND m.id != ? AND m.status = 'published'
    GROUP BY m.id
    ORDER BY m.views DESC, m.release_date DESC
    LIMIT 6
", [$movie['id'], $movie['id']]);

// Kiểm tra phim có trong danh sách yêu thích không
$isFavorite = false;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $favoriteCheck = $db->get("
        SELECT id FROM favorites 
        WHERE user_id = ? AND movie_id = ?
    ", [$currentUser['id'], $movie['id']]);
    
    $isFavorite = !empty($favoriteCheck);
}

// Custom meta tags
$customMetaTitle = 'Xem phim ' . htmlspecialchars($movie['title']);
if ($movie['type'] == 'series') {
    $customMetaTitle .= ' - Tập ' . $episodeNumber;
}
$customMetaTitle .= ' | ' . SITE_NAME;

$customMetaDescription = htmlspecialchars(truncate($movie['overview'], 160));
$customMetaKeywords = 'xem phim, ' . htmlspecialchars($movie['title']) . ', ' . htmlspecialchars($movie['genres']);
$customMetaImage = url($movie['poster'] ? $movie['poster'] : '/assets/images/default-poster.jpg');

// Custom CSS
$customCss = '/assets/css/watch.css';

// Inline JavaScript
$inlineJs = "
    var movieId = " . $movie['id'] . ";
    var episodeId = " . $episode['id'] . ";
    var episodeNumber = " . $episodeNumber . ";
    var watchProgress = " . $watchProgress . ";
    var isVip = " . (isVip() ? 'true' : 'false') . ";
    var isLoggedIn = " . (isLoggedIn() ? 'true' : 'false') . ";
";

// Load header
include_once INCLUDES_PATH . '/header.php';
?>

<!-- Breadcrumb -->
<?php
$breadcrumbs = [
    'Phim' => url('/phim-moi'),
    $movie['title'] => url('/phim/' . $movie['slug'])
];

if ($movie['type'] == 'series') {
    $breadcrumbs['Tập ' . $episodeNumber] = '';
}

echo breadcrumb($breadcrumbs);
?>

<div class="container mt-3">
    <div class="watch-wrapper">
        <div class="watch-main">
            <!-- Video Player -->
            <div class="video-player-wrapper">
                <div class="video-player" data-movie-id="<?php echo $movie['id']; ?>" data-episode-id="<?php echo $episode['id']; ?>">
                    <video id="main-video" controls preload="metadata" poster="<?php echo url($movie['backdrop'] ? $movie['backdrop'] : '/assets/images/default-backdrop.jpg'); ?>">
                        <?php if (!empty($sources)): ?>
                            <?php foreach ($sources as $source): ?>
                                <source src="<?php echo $source['source_url']; ?>" type="video/mp4" data-quality="<?php echo $source['quality']; ?>" title="<?php echo $source['quality']; ?>p">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <source src="#" type="video/mp4">
                        <?php endif; ?>
                        
                        <?php if (!empty($subtitles)): ?>
                            <?php foreach ($subtitles as $subtitle): ?>
                                <track kind="subtitles" src="<?php echo $subtitle['subtitle_url']; ?>" srclang="<?php echo $subtitle['language']; ?>" label="<?php echo $subtitle['language_name']; ?>" <?php echo $subtitle['is_default'] ? 'default' : ''; ?>>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        Trình duyệt của bạn không hỗ trợ thẻ video.
                    </video>
                    
                    <div class="video-controls">
                        <div class="video-progress">
                            <div class="progress">
                                <div class="progress-bar" style="width: 0%"></div>
                            </div>
                            <div class="progress-time">
                                <span class="current-time">00:00</span>
                                <span class="duration">00:00</span>
                            </div>
                        </div>
                        
                        <div class="video-buttons">
                            <div class="video-left-buttons">
                                <button class="video-button play-btn">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="video-button pause-btn" style="display: none;">
                                    <i class="fas fa-pause"></i>
                                </button>
                                
                                <button class="video-button volume-btn">
                                    <i class="fas fa-volume-up"></i>
                                </button>
                                <div class="volume-slider-container">
                                    <input type="range" class="volume-slider" min="0" max="1" step="0.1" value="1">
                                </div>
                                
                                <?php if (!empty($prevEpisode)): ?>
                                    <a href="<?php echo url('/xem/' . $movie['slug'] . '/' . $prevEpisode['episode_number']); ?>" class="video-button prev-episode-btn" title="Tập trước">
                                        <i class="fas fa-step-backward"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($nextEpisode)): ?>
                                    <a href="<?php echo url('/xem/' . $movie['slug'] . '/' . $nextEpisode['episode_number']); ?>" class="video-button next-episode-btn" title="Tập sau">
                                        <i class="fas fa-step-forward"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="video-right-buttons">
                                <?php if (!empty($sources) && count($sources) > 1): ?>
                                    <div class="dropdown quality-dropdown">
                                        <button class="video-button dropdown-toggle quality-btn" title="Chất lượng">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu quality-menu">
                                            <?php foreach ($sources as $source): ?>
                                                <div class="quality-option<?php echo $source === reset($sources) ? ' active' : ''; ?>" data-quality="<?php echo $source['quality']; ?>">
                                                    <?php echo $source['quality']; ?>p
                                                    <?php if ($source['quality'] > 720 && !isVip()): ?>
                                                        <span class="vip-badge">VIP</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($subtitles)): ?>
                                    <div class="dropdown subtitle-dropdown">
                                        <button class="video-button dropdown-toggle subtitle-btn" title="Phụ đề">
                                            <i class="fas fa-closed-captioning"></i>
                                        </button>
                                        <div class="dropdown-menu subtitle-menu">
                                            <div class="subtitle-option active" data-track-id="off">Tắt phụ đề</div>
                                            <?php foreach ($subtitles as $index => $subtitle): ?>
                                                <div class="subtitle-option" data-track-id="<?php echo $index; ?>">
                                                    <?php echo $subtitle['language_name']; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="video-button fullscreen-btn" title="Toàn màn hình">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="video-overlay">
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    
                    <div class="video-loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
            
            <!-- Episode Info -->
            <div class="episode-info">
                <h1 class="movie-title">
                    <a href="<?php echo url('/phim/' . $movie['slug']); ?>"><?php echo htmlspecialchars($movie['title']); ?></a>
                    <?php if ($movie['type'] == 'series'): ?>
                        <span class="episode-number">Tập <?php echo $episodeNumber; ?></span>
                    <?php endif; ?>
                </h1>
                
                <?php if (!empty($episode['title'])): ?>
                    <div class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></div>
                <?php endif; ?>
                
                <div class="movie-meta">
                    <div class="movie-meta-item">
                        <i class="fas fa-eye"></i> <?php echo number_format($episode['views']); ?> lượt xem
                    </div>
                    <div class="movie-meta-item">
                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?>
                    </div>
                    <div class="movie-meta-item">
                        <i class="fas fa-star"></i> <?php echo number_format($movie['rating'], 1); ?>
                    </div>
                </div>
                
                <div class="episode-actions">
                    <button class="btn btn-outline share-btn">
                        <i class="fas fa-share-alt"></i> Chia sẻ
                    </button>
                    
                    <button class="btn btn-outline favorite-btn<?php echo $isFavorite ? ' active' : ''; ?>" data-movie-id="<?php echo $movie['id']; ?>">
                        <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i> 
                        <?php echo $isFavorite ? 'Đã yêu thích' : 'Yêu thích'; ?>
                    </button>
                    
                    <button class="btn btn-outline report-btn" data-movie-id="<?php echo $movie['id']; ?>" data-episode-id="<?php echo $episode['id']; ?>">
                        <i class="fas fa-exclamation-circle"></i> Báo lỗi
                    </button>
                </div>
                
                <?php if (!empty($episode['overview'])): ?>
                    <div class="episode-overview">
                        <h3>Nội dung tập phim</h3>
                        <div class="episode-overview-content">
                            <?php echo nl2br(htmlspecialchars($episode['overview'])); ?>
                        </div>
                    </div>
                <?php elseif (!empty($movie['overview'])): ?>
                    <div class="episode-overview">
                        <h3>Nội dung phim</h3>
                        <div class="episode-overview-content">
                            <?php echo nl2br(htmlspecialchars($movie['overview'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Comments -->
            <div class="comment-section">
                <h3 class="section-title">Bình luận</h3>
                
                <?php if (isLoggedIn()): ?>
                    <div class="comment-form">
                        <form action="<?php echo url('/api/add-comment'); ?>" method="POST" class="comment-form-inner">
                            <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                            <input type="hidden" name="episode_id" value="<?php echo $episode['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <textarea name="content" placeholder="Nhập bình luận của bạn..." class="comment-textarea" required></textarea>
                            
                            <div class="comment-actions">
                                <button type="submit" class="btn btn-primary comment-submit">Gửi bình luận</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="comment-login-prompt">
                        <p>Vui lòng <a href="<?php echo url('/dang-nhap?redirect=' . urlencode(currentUrl())); ?>">đăng nhập</a> để bình luận.</p>
                    </div>
                <?php endif; ?>
                
                <div class="comment-list" id="comment-list">
                    <!-- Các bình luận sẽ được load bằng JavaScript -->
                    <div class="loading">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="watch-sidebar">
            <!-- Episodes List -->
            <?php if ($movie['type'] == 'series' && !empty($episodes)): ?>
                <div class="episodes-section">
                    <h3 class="section-title">Danh sách tập</h3>
                    
                    <div class="episode-list">
                        <?php foreach ($episodes as $ep): ?>
                            <a href="<?php echo url('/xem/' . $movie['slug'] . '/' . $ep['episode_number']); ?>" class="episode-item<?php echo $ep['episode_number'] == $episodeNumber ? ' active' : ''; ?><?php echo $ep['episode_number'] < $episodeNumber ? ' watched' : ''; ?>" data-episode="<?php echo $ep['id']; ?>">
                                <div class="episode-number"><?php echo $ep['episode_number']; ?></div>
                                <div class="episode-title">
                                    <span class="episode-name">
                                        <?php echo !empty($ep['title']) ? htmlspecialchars($ep['title']) : 'Tập ' . $ep['episode_number']; ?>
                                    </span>
                                    <span class="episode-views">
                                        <i class="fas fa-eye"></i> <?php echo number_format($ep['views']); ?>
                                    </span>
                                </div>
                                <div class="episode-progress" data-episode="<?php echo $ep['id']; ?>"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Recommended Movies -->
            <div class="recommended-section">
                <h3 class="section-title">Phim đề xuất</h3>
                
                <div class="recommended-movies">
                    <?php if (!empty($recommendedMovies)): ?>
                        <?php foreach ($recommendedMovies as $recMovie): ?>
                            <div class="recommended-movie">
                                <a href="<?php echo url('/phim/' . $recMovie['slug']); ?>" class="recommended-movie-thumbnail">
                                    <img src="<?php echo url($recMovie['poster'] ? $recMovie['poster'] : '/assets/images/default-poster.jpg'); ?>" alt="<?php echo htmlspecialchars($recMovie['title']); ?>">
                                    
                                    <?php if ($recMovie['is_vip']): ?>
                                        <div class="movie-badge movie-badge-vip">VIP</div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="recommended-movie-info">
                                    <h4 class="recommended-movie-title">
                                        <a href="<?php echo url('/phim/' . $recMovie['slug']); ?>"><?php echo htmlspecialchars($recMovie['title']); ?></a>
                                    </h4>
                                    
                                    <div class="recommended-movie-meta">
                                        <div class="recommended-movie-meta-item">
                                            <i class="fas fa-star"></i> <?php echo number_format($recMovie['rating'], 1); ?>
                                        </div>
                                        <div class="recommended-movie-meta-item">
                                            <i class="fas fa-eye"></i> <?php echo number_format($recMovie['views']); ?>
                                        </div>
                                    </div>
                                    
                                    <a href="<?php echo url('/xem/' . $recMovie['slug'] . '/1'); ?>" class="btn btn-primary btn-sm">Xem ngay</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Không có phim đề xuất.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal" id="reportModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Báo cáo lỗi</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                    <input type="hidden" name="episode_id" value="<?php echo $episode['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="reportType" class="form-label">Loại lỗi</label>
                        <select name="type" id="reportType" class="form-input" required>
                            <option value="">Chọn loại lỗi</option>
                            <option value="video">Lỗi video</option>
                            <option value="subtitle">Lỗi phụ đề</option>
                            <option value="audio">Lỗi âm thanh</option>
                            <option value="loading">Không tải được phim</option>
                            <option value="other">Lỗi khác</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reportDescription" class="form-label">Mô tả chi tiết</label>
                        <textarea name="description" id="reportDescription" class="form-input" rows="4" placeholder="Mô tả chi tiết về lỗi bạn gặp phải..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Gửi báo cáo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal" id="shareModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Chia sẻ phim</h3>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="share-url">
                    <input type="text" id="shareUrl" class="form-input" value="<?php echo currentUrl(); ?>" readonly>
                    <button id="copyUrl" class="btn btn-outline">
                        <i class="fas fa-copy"></i> Sao chép
                    </button>
                </div>
                
                <div class="share-social">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(currentUrl()); ?>" target="_blank" class="share-social-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(currentUrl()); ?>&text=<?php echo urlencode('Xem phim ' . $movie['title'] . ' tại ' . SITE_NAME); ?>" target="_blank" class="share-social-btn twitter">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Chia sẻ phim ' . $movie['title']); ?>&body=<?php echo urlencode('Xem phim ' . $movie['title'] . ' tại: ' . currentUrl()); ?>" class="share-social-btn email">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Load footer
include_once INCLUDES_PATH . '/footer.php';
?>