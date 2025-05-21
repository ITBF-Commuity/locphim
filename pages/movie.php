<?php
/**
 * Lọc Phim - Trang chi tiết phim
 */

// Lấy thông tin phim từ route
$movieId = isset($params['movie_id']) ? (int)$params['movie_id'] : 0;

if (empty($movieId)) {
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

// Kiểm tra xem phim có yêu cầu VIP không
$requireVip = $movie['is_vip'] === 1;
$userIsVip = is_vip();

// Tăng lượt xem
$db->execute("UPDATE movies SET views = views + 1 WHERE id = ?", [$movieId]);

// Lấy danh sách tập phim
$episodes = $db->getAll("SELECT * FROM episodes 
                        WHERE movie_id = ? 
                        ORDER BY season_number ASC, episode_number ASC", [$movieId]);

// Lấy tập mới nhất
$latestEpisode = !empty($episodes) ? end($episodes) : null;

// Lấy danh sách thể loại của phim
$categories = $db->getAll("SELECT c.* 
                          FROM categories c 
                          JOIN movie_categories mc ON c.id = mc.category_id 
                          WHERE mc.movie_id = ?", [$movieId]);

// Lấy danh sách diễn viên
$actors = get_movie_actors($movieId);

// Lấy danh sách phim liên quan
$relatedMovies = $db->getAll("SELECT DISTINCT m.*, c.name as country_name 
                             FROM movies m 
                             JOIN movie_categories mc1 ON m.id = mc1.movie_id 
                             JOIN movie_categories mc2 ON mc1.category_id = mc2.category_id 
                             LEFT JOIN countries c ON m.country_id = c.id 
                             WHERE mc2.movie_id = ? AND m.id != ? AND m.status = 'published' 
                             ORDER BY m.views DESC 
                             LIMIT 12", [$movieId, $movieId]);

// Lấy danh sách bình luận
$comments = $db->getAll("SELECT cm.*, u.username, u.avatar 
                        FROM comments cm 
                        JOIN users u ON cm.user_id = u.id 
                        WHERE cm.movie_id = ? AND cm.parent_id IS NULL 
                        ORDER BY cm.created_at DESC
                        LIMIT 20", [$movieId]);

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
$pageTitle = $movie['title'] . ' (' . $movie['release_year'] . ') - ' . SITE_NAME;
$pageDescription = !empty($movie['description']) ? substr(strip_tags($movie['description']), 0, 160) : $movie['title'] . ' - ' . SITE_DESCRIPTION;
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
$breadcrumbs[] = ['name' => $movie['title'], 'url' => ''];

// Bắt đầu output buffering
ob_start();
?>

<div class="movie-detail-page">
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
    
    <!-- Movie Header -->
    <div class="movie-header" style="background-image: url('<?php echo !empty($movie['backdrop']) ? image_url($movie['backdrop']) : ''; ?>');">
        <div class="movie-header-overlay"></div>
        <div class="container">
            <div class="movie-header-content">
                <div class="movie-poster">
                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php if ($requireVip): ?>
                        <div class="vip-badge">VIP</div>
                    <?php endif; ?>
                </div>
                
                <div class="movie-info">
                    <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    
                    <?php if (!empty($movie['original_title'])): ?>
                        <h2 class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></h2>
                    <?php endif; ?>
                    
                    <div class="movie-meta">
                        <?php if (!empty($movie['release_year'])): ?>
                            <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['duration'])): ?>
                            <span class="meta-item duration"><?php echo format_duration($movie['duration']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['quality'])): ?>
                            <span class="meta-item quality"><?php echo htmlspecialchars($movie['quality']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['country_name'])): ?>
                            <span class="meta-item country"><?php echo htmlspecialchars($movie['country_name']); ?></span>
                        <?php endif; ?>
                        
                        <span class="meta-item type">
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
                    
                    <div class="movie-categories">
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo url('the-loai/' . $category['slug']); ?>" class="category-tag">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="movie-rating">
                        <div class="rating-stars">
                            <?php
                            $rating = !empty($movie['imdb_rating']) ? $movie['imdb_rating'] : 0;
                            $fullStars = floor($rating);
                            $halfStar = $rating - $fullStars >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            
                            for ($i = 0; $i < $fullStars; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            
                            <?php if ($halfStar): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                            
                            <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if (!empty($movie['imdb_rating'])): ?>
                            <div class="rating-value"><?php echo $movie['imdb_rating']; ?>/10</div>
                        <?php endif; ?>
                        
                        <div class="view-count">
                            <i class="fas fa-eye"></i> <?php echo format_views($movie['views']); ?> lượt xem
                        </div>
                    </div>
                    
                    <div class="movie-actions">
                        <?php if ($movie['type'] !== 'movie'): ?>
                            <?php if ($latestEpisode): ?>
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-' . $latestEpisode['episode_number']); ?>" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play-circle"></i> Xem ngay
                                </a>
                                <a href="#episodes" class="btn btn-outline btn-lg">
                                    <i class="fas fa-list"></i> Danh sách tập
                                </a>
                            <?php else: ?>
                                <a href="#" class="btn btn-primary btn-lg disabled">
                                    <i class="fas fa-clock"></i> Sắp ra mắt
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-1'); ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-play-circle"></i> Xem phim
                            </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline favorite-button <?php echo $isFavorite ? 'favorited' : ''; ?>" data-movie-id="<?php echo $movie['id']; ?>">
                            <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i>
                            <span><?php echo $isFavorite ? 'Đã yêu thích' : 'Yêu thích'; ?></span>
                        </button>
                        
                        <?php if (!empty($movie['trailer'])): ?>
                            <a href="<?php echo $movie['trailer']; ?>" class="btn btn-outline" target="_blank">
                                <i class="fab fa-youtube"></i> Trailer
                            </a>
                        <?php endif; ?>
                        
                        <div class="share-buttons">
                            <button class="share-button" id="share-fb" data-url="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button class="share-button" id="share-twitter" data-url="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" data-title="<?php echo htmlspecialchars($movie['title']); ?>">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="share-button" id="share-link" data-url="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Movie Content -->
    <div class="movie-content">
        <div class="container">
            <div class="content-layout">
                <div class="main-content">
                    <!-- Movie Description -->
                    <div class="movie-description card">
                        <div class="card-header">
                            <h3 class="card-title">Nội dung phim</h3>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($movie['description'])): ?>
                                <div class="description-text">
                                    <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Chưa có mô tả cho phim này.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Episodes List -->
                    <?php if ($movie['type'] !== 'movie' && !empty($episodes)): ?>
                        <div class="movie-episodes card" id="episodes">
                            <div class="card-header">
                                <h3 class="card-title">Danh sách tập</h3>
                                
                                <?php
                                // Lấy danh sách các season khác nhau
                                $seasons = [];
                                foreach ($episodes as $episode) {
                                    // Kiểm tra có tồn tại key season_number không
                                    $seasonNumber = isset($episode['season_number']) ? $episode['season_number'] : 1;
                                    if (!in_array($seasonNumber, $seasons)) {
                                        $seasons[] = $seasonNumber;
                                    }
                                }
                                sort($seasons);
                                
                                // Nếu có nhiều season
                                if (count($seasons) > 1):
                                ?>
                                <div class="episode-tabs">
                                    <?php foreach ($seasons as $index => $seasonNumber): ?>
                                        <button class="episode-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-target="season-<?php echo $seasonNumber; ?>">
                                            Season <?php echo $seasonNumber; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <?php foreach ($seasons as $index => $seasonNumber): ?>
                                    <div class="episode-content <?php echo $index === 0 ? 'active' : ''; ?>" id="season-<?php echo $seasonNumber; ?>">
                                        <div class="episode-grid">
                                            <?php foreach ($episodes as $episode): ?>
                                                <?php 
                                                $episodeSeasonNumber = isset($episode['season_number']) ? $episode['season_number'] : 1;
                                                if ($episodeSeasonNumber === $seasonNumber): 
                                                ?>
                                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id'] . '/tap-' . $episode['episode_number']); ?>" class="episode-item">
                                                        <div class="episode-number"><?php echo $episode['episode_number']; ?></div>
                                                        <div class="episode-title">
                                                            <?php 
                                                            if (!empty($episode['title']) && $episode['title'] !== 'Tập ' . $episode['episode_number']) {
                                                                echo htmlspecialchars($episode['title']);
                                                            } else {
                                                                echo 'Tập ' . $episode['episode_number'];
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
                    <?php endif; ?>
                    
                    <!-- Comments Section -->
                    <div class="movie-comments card">
                        <div class="card-header">
                            <h3 class="card-title">Bình luận</h3>
                        </div>
                        
                        <div class="card-body">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- Comment Form -->
                                <div class="comment-form-container">
                                    <form id="comment-form" class="comment-form">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
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
                    <!-- Movie Details -->
                    <div class="movie-details card" data-movie-id="<?php echo $movie['id']; ?>">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin phim</h3>
                        </div>
                        
                        <div class="card-body">
                            <ul class="details-list">
                                <li>
                                    <span class="details-label">Trạng thái:</span>
                                    <span class="details-value">
                                        <?php 
                                        if ($movie['type'] === 'movie') {
                                            echo 'Hoàn thành';
                                        } else {
                                            echo !empty($movie['status_name']) ? $movie['status_name'] : ($latestEpisode ? 'Tập ' . $latestEpisode['episode_number'] : 'Đang cập nhật');
                                        }
                                        ?>
                                    </span>
                                </li>
                                
                                <?php if ($movie['type'] !== 'movie'): ?>
                                    <li>
                                        <span class="details-label">Số tập:</span>
                                        <span class="details-value">
                                            <?php echo count($episodes); ?><?php echo !empty($movie['total_episodes']) ? '/' . $movie['total_episodes'] : ''; ?>
                                        </span>
                                    </li>
                                <?php endif; ?>
                                
                                <li>
                                    <span class="details-label">Thời lượng:</span>
                                    <span class="details-value">
                                        <?php 
                                        if (!empty($movie['duration'])) {
                                            echo format_duration($movie['duration']);
                                        } else {
                                            echo 'Chưa cập nhật';
                                        }
                                        ?>
                                    </span>
                                </li>
                                
                                <?php if (!empty($movie['release_date'])): ?>
                                    <li>
                                        <span class="details-label">Ngày phát hành:</span>
                                        <span class="details-value"><?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['country_name'])): ?>
                                    <li>
                                        <span class="details-label">Quốc gia:</span>
                                        <span class="details-value"><?php echo htmlspecialchars($movie['country_name']); ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <li>
                                    <span class="details-label">Thể loại:</span>
                                    <span class="details-value">
                                        <?php if (!empty($categories)): ?>
                                            <?php 
                                            $categoryNames = array_map(function($category) {
                                                return htmlspecialchars($category['name']);
                                            }, $categories);
                                            echo implode(', ', $categoryNames);
                                            ?>
                                        <?php else: ?>
                                            Chưa cập nhật
                                        <?php endif; ?>
                                    </span>
                                </li>
                                
                                <?php if (!empty($actors)): ?>
                                    <li>
                                        <span class="details-label">Diễn viên:</span>
                                        <span class="details-value">
                                            <?php 
                                            $actorNames = array_map(function($actor) {
                                                return htmlspecialchars($actor['name']);
                                            }, $actors);
                                            echo implode(', ', $actorNames);
                                            ?>
                                        </span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie['director'])): ?>
                                    <li>
                                        <span class="details-label">Đạo diễn:</span>
                                        <span class="details-value"><?php echo htmlspecialchars($movie['director']); ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            
                            <?php if ($requireVip && !$userIsVip): ?>
                                <div class="vip-notice">
                                    <div class="vip-icon">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="vip-message">
                                        <p>Phim này yêu cầu tài khoản VIP để xem với chất lượng cao nhất và không có quảng cáo.</p>
                                        <a href="<?php echo url('vip'); ?>" class="btn btn-vip">Nâng cấp VIP ngay</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Related Movies -->
                    <?php if (!empty($relatedMovies)): ?>
                        <div class="related-movies card">
                            <div class="card-header">
                                <h3 class="card-title">Phim liên quan</h3>
                            </div>
                            
                            <div class="card-body">
                                <div class="related-movies-list">
                                    <?php foreach ($relatedMovies as $relatedMovie): ?>
                                        <div class="related-movie">
                                            <div class="related-movie-poster">
                                                <a href="<?php echo url('phim/' . $relatedMovie['slug'] . '/' . $relatedMovie['id']); ?>">
                                                    <img src="<?php echo image_url($relatedMovie['poster']); ?>" alt="<?php echo htmlspecialchars($relatedMovie['title']); ?>" loading="lazy">
                                                </a>
                                                <?php if ($relatedMovie['is_vip']): ?>
                                                    <div class="vip-badge small">VIP</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="related-movie-info">
                                                <h4 class="related-movie-title">
                                                    <a href="<?php echo url('phim/' . $relatedMovie['slug'] . '/' . $relatedMovie['id']); ?>">
                                                        <?php echo htmlspecialchars($relatedMovie['title']); ?>
                                                    </a>
                                                </h4>
                                                <div class="related-movie-meta">
                                                    <?php if (!empty($relatedMovie['release_year'])): ?>
                                                        <span class="year"><?php echo $relatedMovie['release_year']; ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="type">
                                                        <?php 
                                                        switch ($relatedMovie['type']) {
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
                                                                echo ucfirst($relatedMovie['type']);
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Breadcrumbs */
    .breadcrumbs {
        padding: 1rem 0;
        background-color: var(--bg-light);
        border-bottom: 1px solid var(--border-color);
    }
    
    .breadcrumb-list {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .breadcrumb-item {
        font-size: 0.875rem;
    }
    
    .breadcrumb-item a {
        color: var(--text-muted);
    }
    
    .breadcrumb-item a:hover {
        color: var(--primary-color);
    }
    
    .breadcrumb-item span {
        color: var(--text-color);
    }
    
    .breadcrumb-separator {
        margin: 0 0.5rem;
        color: var(--text-muted);
        font-size: 0.75rem;
    }
    
    /* Movie Header */
    .movie-header {
        position: relative;
        background-size: cover;
        background-position: center;
        padding: 3rem 0;
        color: white;
    }
    
    .movie-header-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.6) 100%);
    }
    
    .movie-header-content {
        position: relative;
        display: flex;
        gap: 2rem;
    }
    
    .movie-poster {
        flex-shrink: 0;
        width: 300px;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    
    .movie-poster img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .movie-poster .vip-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--vip-color);
        color: var(--bg-dark);
        font-size: 0.875rem;
        font-weight: bold;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
    }
    
    .movie-info {
        flex: 1;
    }
    
    .movie-title {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: white;
    }
    
    .movie-original-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: normal;
    }
    
    .movie-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .meta-item {
        display: inline-flex;
        align-items: center;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.875rem;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .movie-categories {
        margin-bottom: 1.5rem;
    }
    
    .category-tag {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        transition: var(--transition);
    }
    
    .category-tag:hover {
        background-color: var(--primary-color);
        color: white;
    }
    
    .movie-rating {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .rating-stars {
        color: var(--vip-color);
        font-size: 1.25rem;
    }
    
    .rating-value {
        font-size: 1.25rem;
        font-weight: bold;
    }
    
    .view-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .movie-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .favorite-button {
        color: var(--text-color);
    }
    
    .favorite-button.favorited {
        color: white;
        background-color: var(--danger-color);
        border-color: var(--danger-color);
    }
    
    .share-buttons {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .share-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        transition: var(--transition);
    }
    
    .share-button:hover {
        background-color: var(--primary-color);
    }
    
    /* Movie Content */
    .movie-content {
        padding: 2rem 0;
    }
    
    .content-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    
    /* Card */
    .card {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card-title {
        font-size: 1.25rem;
        margin: 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    /* Movie Description */
    .description-text {
        line-height: 1.7;
    }
    
    /* Movie Episodes */
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
    
    .episode-content {
        display: none;
    }
    
    .episode-content.active {
        display: block;
    }
    
    .episode-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
    
    .episode-number {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .episode-title {
        font-size: 0.875rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Movie Comments */
    .comment-form-container {
        margin-bottom: 2rem;
    }
    
    .comment-form .form-group {
        margin-bottom: 1rem;
    }
    
    .comment-form textarea {
        width: 100%;
        min-height: 100px;
        resize: vertical;
    }
    
    .comment-form .form-actions {
        display: flex;
        justify-content: flex-end;
    }
    
    .login-to-comment {
        text-align: center;
        padding: 1.5rem;
        background-color: var(--bg-light);
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
    }
    
    .comments-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .no-comments {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
    }
    
    .comment {
        display: flex;
        gap: 1rem;
    }
    
    .comment-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .comment-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .comment-content {
        flex: 1;
    }
    
    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .comment-user {
        font-weight: var(--font-weight-medium);
    }
    
    .comment-date {
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    .comment-text {
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }
    
    .comment-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .comment-actions button {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 0.875rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        transition: var(--transition);
    }
    
    .comment-actions button:hover {
        color: var(--primary-color);
    }
    
    .reply-form-container {
        margin-bottom: 1rem;
    }
    
    .reply-form {
        background-color: var(--bg-light);
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
    }
    
    .reply-form textarea {
        width: 100%;
        min-height: 80px;
        resize: vertical;
    }
    
    .reply-form .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .btn-cancel {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
    }
    
    .btn-submit {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        cursor: pointer;
    }
    
    .replies {
        margin-left: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .reply {
        display: flex;
        gap: 1rem;
    }
    
    .reply-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .reply-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .reply-content {
        flex: 1;
    }
    
    .reply-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .reply-user {
        font-weight: var(--font-weight-medium);
    }
    
    .reply-date {
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    .reply-text {
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }
    
    .reply-actions {
        display: flex;
        gap: 1rem;
    }
    
    /* Movie Details */
    .details-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .details-list li {
        display: flex;
        margin-bottom: 0.75rem;
    }
    
    .details-label {
        width: 120px;
        color: var(--text-muted);
        flex-shrink: 0;
    }
    
    .details-value {
        flex: 1;
    }
    
    .vip-notice {
        margin-top: 1.5rem;
        padding: 1rem;
        background-color: var(--vip-bg);
        border-radius: var(--border-radius);
        display: flex;
        gap: 1rem;
    }
    
    .vip-icon {
        font-size: 2rem;
        color: var(--vip-color);
    }
    
    .vip-message p {
        margin-bottom: 0.75rem;
    }
    
    .btn-vip {
        display: inline-block;
        background-color: var(--vip-color);
        color: var(--bg-dark);
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        font-weight: var(--font-weight-medium);
    }
    
    .btn-vip:hover {
        background-color: darken(var(--vip-color), 10%);
        color: var(--bg-dark);
    }
    
    /* Related Movies */
    .related-movies-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .related-movie {
        display: flex;
        gap: 1rem;
    }
    
    .related-movie-poster {
        width: 80px;
        height: 120px;
        border-radius: var(--border-radius);
        overflow: hidden;
        flex-shrink: 0;
        position: relative;
    }
    
    .related-movie-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .vip-badge.small {
        font-size: 0.625rem;
        padding: 0.125rem 0.375rem;
    }
    
    .related-movie-info {
        flex: 1;
    }
    
    .related-movie-title {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    
    .related-movie-title a {
        color: var(--text-color);
    }
    
    .related-movie-title a:hover {
        color: var(--primary-color);
    }
    
    .related-movie-meta {
        display: flex;
        gap: 0.75rem;
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .content-layout {
            grid-template-columns: 1fr;
        }
        
        .movie-header-content {
            flex-direction: column;
        }
        
        .movie-poster {
            width: 200px;
            margin: 0 auto;
        }
        
        .movie-info {
            text-align: center;
        }
        
        .movie-meta,
        .movie-categories,
        .movie-actions {
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .movie-header {
            padding: 2rem 0;
        }
        
        .movie-title {
            font-size: 2rem;
        }
    }
</style>

<?php
// Định nghĩa hàm format_duration nếu chưa có
if (!function_exists('format_duration')) {
    function format_duration($minutes) {
        if (empty($minutes)) {
            return '';
        }
        
        if ($minutes < 60) {
            return $minutes . ' phút';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins == 0) {
            return $hours . 'h';
        }
        
        return $hours . 'h ' . $mins . 'm';
    }
}

// Lấy nội dung đã buffer và gán vào biến pageContent
$pageContent = ob_get_clean();
?>