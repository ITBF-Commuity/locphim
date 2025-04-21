<?php
// Định nghĩa URL trang chủ
define('SITE_URL', 'https://localhost');

// Bao gồm các file cần thiết
require_once 'config.php';
require_once 'db_connect.php';
require_once 'init.php';
require_once 'auth.php';

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

// Lấy danh sách tập phim
$episodes = get_movie_episodes($movie['id']);

// Lấy danh sách thể loại của phim
$categories = get_movie_categories($movie['id']);

// Lấy danh sách bình luận
$comments = get_movie_comments($movie['id'], 10);

// Kiểm tra nếu phim đã trong danh sách yêu thích
$is_favorite = false;
if ($current_user) {
    $is_favorite = is_movie_in_favorites($current_user['id'], $movie['id']);
}

// Lấy tiến trình xem phim
$watch_progress = null;
if ($current_user && !empty($episodes)) {
    $watch_progress = get_user_progress($current_user['id'], $movie['id'], $episodes[0]['id']);
}

// Bao gồm header
require_once 'includes/header.php';
?>

<div class="movie-detail">
    <div class="movie-backdrop" style="background-image: url('<?php echo $movie['backdrop']; ?>')"></div>
    
    <div class="container">
        <div class="movie-info">
            <div class="row">
                <div class="col-md-3">
                    <div class="movie-poster">
                        <img src="<?php echo $movie['thumbnail']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="movie-rating">
                            <span class="rating-score"><?php echo number_format($movie['rating'], 1); ?></span>
                            <div class="rating-stars">
                                <?php
                                $rating = round($movie['rating'] * 2) / 2;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 == $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-count"><?php echo $movie['rating_count']; ?> đánh giá</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="movie-details">
                        <h1 class="movie-title"><?php echo $movie['title']; ?></h1>
                        
                        <?php if (!empty($movie['original_title'])): ?>
                        <div class="movie-original-title"><?php echo $movie['original_title']; ?></div>
                        <?php endif; ?>
                        
                        <div class="movie-meta">
                            <span class="meta-item"><i class="fas fa-calendar-alt"></i> <?php echo date('Y', strtotime($movie['release_date'])); ?></span>
                            <span class="meta-item"><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?></span>
                            <span class="meta-item"><i class="fas fa-closed-captioning"></i> <?php echo $movie['language']; ?></span>
                            <span class="meta-item"><i class="fas fa-eye"></i> <?php echo format_views($movie['views']); ?></span>
                            <span class="meta-item quality-badge"><?php echo $movie['quality']; ?></span>
                        </div>
                        
                        <div class="movie-categories">
                            <?php foreach ($categories as $category): ?>
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="category-tag"><?php echo $category['name']; ?></a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="movie-description">
                            <p><?php echo nl2br($movie['description']); ?></p>
                        </div>
                        
                        <div class="movie-actions">
                            <a href="player.php?slug=<?php echo $slug; ?>" class="btn btn-primary btn-watch">
                                <i class="fas fa-play"></i> Xem phim
                            </a>
                            
                            <?php if ($current_user): ?>
                            <button class="btn btn-outline-primary btn-favorite <?php echo $is_favorite ? 'active' : ''; ?>" data-movie-id="<?php echo $movie['id']; ?>">
                                <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i> 
                                <span><?php echo $is_favorite ? 'Đã thích' : 'Yêu thích'; ?></span>
                            </button>
                            <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-primary">
                                <i class="far fa-heart"></i> Yêu thích
                            </a>
                            <?php endif; ?>
                            
                            <div class="dropdown share-dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="shareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-share-alt"></i> Chia sẻ
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="shareDropdown">
                                    <li><a class="dropdown-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/detail.php?slug=' . $slug); ?>" target="_blank"><i class="fab fa-facebook"></i> Facebook</a></li>
                                    <li><a class="dropdown-item" href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/detail.php?slug=' . $slug); ?>&text=<?php echo urlencode($movie['title']); ?>" target="_blank"><i class="fab fa-twitter"></i> Twitter</a></li>
                                    <li><a class="dropdown-item" href="mailto:?subject=<?php echo urlencode($movie['title']); ?>&body=<?php echo urlencode('Xem phim hay tại: ' . SITE_URL . '/detail.php?slug=' . $slug); ?>"><i class="fas fa-envelope"></i> Email</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danh sách tập phim -->
        <?php if (count($episodes) > 0): ?>
        <div class="episodes-section">
            <div class="section-header">
                <h2>Danh sách tập</h2>
                
                <?php if (count($episodes) > 20): ?>
                <div class="episodes-filter">
                    <button class="btn btn-sm btn-outline-secondary" data-filter="all">Tất cả</button>
                    <button class="btn btn-sm btn-outline-secondary" data-filter="even">Số chẵn</button>
                    <button class="btn btn-sm btn-outline-secondary" data-filter="odd">Số lẻ</button>
                    
                    <?php if ($current_user): ?>
                    <button class="btn btn-sm btn-outline-secondary" data-filter="watched">Đã xem</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="episodes-grid">
                <?php foreach ($episodes as $episode): ?>
                <?php 
                $has_watched = false;
                if ($current_user) {
                    $progress = get_user_progress($current_user['id'], $movie['id'], $episode['id']);
                    $has_watched = $progress && ($progress['progress'] / $episode['duration']) > 0.7;
                }
                ?>
                <a href="player.php?slug=<?php echo $slug; ?>&ep=<?php echo $episode['episode_number']; ?>" 
                   class="episode-item <?php echo $has_watched ? 'watched' : ''; ?>"
                   data-episode="<?php echo $episode['episode_number']; ?>">
                    <div class="episode-number">Tập <?php echo $episode['episode_number']; ?></div>
                    <div class="episode-info">
                        <div class="episode-title"><?php echo $episode['title']; ?></div>
                        <div class="episode-meta">
                            <span class="episode-duration"><i class="fas fa-clock"></i> <?php echo $episode['duration']; ?></span>
                            <span class="episode-date"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($episode['release_date'])); ?></span>
                        </div>
                    </div>
                    <?php if ($has_watched): ?>
                    <div class="episode-watched"><i class="fas fa-check-circle"></i></div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Phim liên quan -->
        <div class="related-section">
            <div class="section-header">
                <h2>Phim tương tự</h2>
            </div>
            
            <div class="movie-grid">
                <?php
                // Lấy phim cùng thể loại
                $category_ids = array();
                foreach ($categories as $cat) {
                    $category_ids[] = $cat['id'];
                }
                
                if (!empty($category_ids)) {
                    $related_movies = db_fetch_all(
                        "SELECT DISTINCT m.* FROM movies m 
                        JOIN movie_categories mc ON m.id = mc.movie_id 
                        WHERE m.id != ? AND mc.category_id IN (" . implode(',', array_fill(0, count($category_ids), '?')) . ") 
                        AND m.status = 1 
                        ORDER BY m.views DESC 
                        LIMIT 6",
                        array_merge(array($movie['id']), $category_ids)
                    );
                    
                    foreach ($related_movies as $related): 
                ?>
                <div class="movie-card">
                    <div class="movie-thumbnail">
                        <a href="detail.php?slug=<?php echo $related['slug']; ?>">
                            <img src="<?php echo $related['thumbnail']; ?>" alt="<?php echo $related['title']; ?>">
                            <div class="movie-overlay">
                                <div class="movie-info">
                                    <span class="movie-quality"><?php echo $related['quality']; ?></span>
                                    <span class="movie-duration"><?php echo $related['duration']; ?></span>
                                </div>
                                <div class="movie-actions">
                                    <button class="btn-play"><i class="fas fa-play"></i></button>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="movie-details">
                        <h3 class="movie-title"><a href="detail.php?slug=<?php echo $related['slug']; ?>"><?php echo $related['title']; ?></a></h3>
                        <div class="movie-meta">
                            <span class="movie-type"><?php echo ucfirst($related['type']); ?></span>
                            <span class="movie-views"><i class="fas fa-eye"></i> <?php echo format_views($related['views']); ?></span>
                        </div>
                    </div>
                </div>
                <?php
                    endforeach;
                }
                ?>
            </div>
        </div>
        
        <!-- Bình luận -->
        <div class="comments-section">
            <div class="section-header">
                <h2>Bình luận (<?php echo db_count("SELECT COUNT(*) FROM comments WHERE movie_id = ?", array($movie['id'])); ?>)</h2>
            </div>
            
            <?php if ($current_user): ?>
            <div class="comment-form">
                <div class="user-avatar">
                    <img src="<?php echo $current_user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="<?php echo $current_user['username']; ?>">
                </div>
                <form id="commentForm" data-movie-id="<?php echo $movie['id']; ?>">
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
                                    <i class="fas fa-thumbs-up"></i> <span><?php echo $comment['likes']; ?></span>
                                </button>
                                <button class="btn-reply" data-comment-id="<?php echo $comment['id']; ?>">
                                    <i class="fas fa-reply"></i> Trả lời
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

<?php
// Bao gồm footer
require_once 'includes/footer.php';
?>
