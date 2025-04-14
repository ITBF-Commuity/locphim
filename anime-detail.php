<?php
// Tiêu đề trang
$page_title = 'Chi tiết anime';

// Include header
include 'header.php';

// Lấy ID anime từ query parameter
$anime_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kiểm tra ID hợp lệ
if ($anime_id <= 0) {
    // Chuyển hướng về trang chủ
    header('Location: index.php');
    exit;
}

// Lấy thông tin chi tiết anime
$anime = get_anime($anime_id);

// Kiểm tra anime tồn tại
if (!$anime) {
    // Hiển thị thông báo lỗi
    echo '<div class="alert alert-danger text-center">
            <h3><i class="fas fa-exclamation-triangle"></i> Không tìm thấy nội dung</h3>
            <p>Anime bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p>
            <a href="index.php" class="btn btn-primary mt-3">Quay lại trang chủ</a>
          </div>';
    
    // Include footer
    include 'footer.php';
    exit;
}

// Cập nhật tiêu đề trang
$page_title = $anime['title'];

// Lấy danh sách các tập
$episodes = get_episodes($anime_id);

// Kiểm tra trạng thái yêu thích
$is_favorite = is_logged_in() ? is_favorite($_SESSION['user_id'], $anime_id) : false;

// Cập nhật lượt xem
update_views($anime_id);

// Chuyển đổi nội dung mô tả
$description = nl2br($anime['description']);

// Lấy anime liên quan (cùng thể loại)
$related_anime = get_anime(null, 6, 0, ['category_id' => $anime['category_id']]);

// Lọc bỏ anime hiện tại khỏi danh sách liên quan
$related_anime = array_filter($related_anime, function($item) use ($anime_id) {
    return $item['id'] != $anime_id;
});
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="anime.php">Anime</a></li>
        <li class="breadcrumb-item"><a href="category.php?id=<?php echo $anime['category_id']; ?>"><?php echo $anime['category_name']; ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo $anime['title']; ?></li>
    </ol>
</nav>

<!-- Thông tin anime -->
<div class="anime-detail mb-5">
    <div class="row">
        <!-- Poster -->
        <div class="col-md-3 col-lg-3 mb-4 mb-md-0">
            <div class="position-relative">
                <img src="<?php echo get_thumbnail($anime['thumbnail']); ?>" class="img-fluid rounded shadow" alt="<?php echo $anime['title']; ?>">
                <div class="rating-badge-lg">
                    <i class="fas fa-star"></i> <?php echo number_format($anime['rating'], 1); ?>
                </div>
            </div>
            
            <!-- Các nút thao tác -->
            <div class="mt-3 d-grid gap-2">
                <?php if (count($episodes) > 0): ?>
                    <a href="watch.php?episode_id=<?php echo $episodes[0]['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-play-circle me-2"></i> Xem ngay
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-clock me-2"></i> Sắp ra mắt
                    </button>
                <?php endif; ?>
                
                <?php if (is_logged_in()): ?>
                    <button id="favoriteBtn" class="btn <?php echo $is_favorite ? 'btn-danger' : 'btn-outline-danger'; ?>" data-anime-id="<?php echo $anime_id; ?>">
                        <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                        <span id="favoriteText"><?php echo $is_favorite ? 'Đã yêu thích' : 'Thêm vào yêu thích'; ?></span>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-danger">
                        <i class="far fa-heart me-2"></i> Đăng nhập để thêm vào yêu thích
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Thông tin chi tiết -->
        <div class="col-md-9 col-lg-9">
            <h1 class="anime-title mb-3"><?php echo $anime['title']; ?></h1>
            
            <?php if (!empty($anime['alternative_title'])): ?>
                <h2 class="anime-alt-title text-muted mb-3"><?php echo $anime['alternative_title']; ?></h2>
            <?php endif; ?>
            
            <div class="anime-metadata mb-4">
                <span class="badge bg-primary me-2"><i class="fas fa-calendar-alt me-1"></i> <?php echo $anime['release_year']; ?></span>
                <span class="badge bg-secondary me-2"><i class="fas fa-list me-1"></i> <?php echo $anime['category_name']; ?></span>
                <span class="badge bg-info me-2"><i class="fas fa-clock me-1"></i> <?php echo $anime['duration']; ?> phút</span>
                <span class="badge bg-success me-2"><i class="fas fa-film me-1"></i> <?php echo $anime['episode_count']; ?> tập</span>
                <span class="badge bg-warning text-dark"><i class="fas fa-eye me-1"></i> <?php echo number_format($anime['views']); ?> lượt xem</span>
            </div>
            
            <div class="anime-synopsis mb-4">
                <h3 class="fw-bold mb-3">Nội dung</h3>
                <p><?php echo $description; ?></p>
            </div>
            
            <?php if (!empty($anime['tags'])): ?>
                <div class="anime-tags mb-4">
                    <h3 class="fw-bold mb-2">Tags</h3>
                    <div>
                        <?php foreach (explode(',', $anime['tags']) as $tag): ?>
                            <a href="search.php?q=<?php echo trim($tag); ?>" class="btn btn-sm btn-outline-secondary me-2 mb-2"><?php echo trim($tag); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($anime['studio'])): ?>
                <div class="anime-studio mb-4">
                    <h3 class="fw-bold mb-2">Studio</h3>
                    <p><?php echo $anime['studio']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Danh sách tập -->
<div class="episodes-section mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-list-ol me-2"></i> Danh sách tập
        </h2>
        
        <?php if (count($episodes) > 20): ?>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary" id="showAllEpisodes">Tất cả</button>
                <button type="button" class="btn btn-outline-primary" id="showNewestEpisodes">Mới nhất</button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($episodes) > 0): ?>
        <div class="row episode-list">
            <?php foreach ($episodes as $episode): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3 episode-item">
                    <a href="watch.php?episode_id=<?php echo $episode['id']; ?>" class="episode-card">
                        <div class="position-relative">
                            <img src="<?php echo get_thumbnail($episode['thumbnail'] ?? $anime['thumbnail']); ?>" class="img-fluid rounded" alt="Tập <?php echo $episode['episode_number']; ?>">
                            <div class="episode-number">
                                Tập <?php echo $episode['episode_number']; ?>
                            </div>
                            <div class="episode-play-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            
                            <?php if (isset($episode['release_date']) && strtotime($episode['release_date']) > strtotime('-3 days')): ?>
                                <div class="new-badge">Mới</div>
                            <?php endif; ?>
                        </div>
                        <div class="episode-info p-2">
                            <h5 class="episode-title text-truncate"><?php echo $episode['title']; ?></h5>
                            <div class="d-flex justify-content-between">
                                <span><i class="far fa-clock"></i> <?php echo format_time($episode['release_date']); ?></span>
                                <span><i class="far fa-eye"></i> <?php echo number_format($episode['views']); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Chưa có tập phim nào được phát hành. Vui lòng quay lại sau.
        </div>
    <?php endif; ?>
</div>

<!-- Bình luận -->
<div class="comments-section mb-5">
    <h2 class="section-title mb-4">
        <i class="fas fa-comments me-2"></i> Bình luận
    </h2>
    
    <?php if (is_logged_in()): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form id="commentForm">
                    <input type="hidden" id="animeId" value="<?php echo $anime_id; ?>">
                    <div class="mb-3">
                        <textarea class="form-control" id="commentContent" rows="3" placeholder="Nhập bình luận của bạn..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Gửi bình luận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i> Vui lòng <a href="login.php" class="alert-link">đăng nhập</a> để bình luận.
        </div>
    <?php endif; ?>
    
    <div id="commentsList">
        <!-- Danh sách bình luận sẽ được hiển thị ở đây -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-3">Đang tải bình luận...</p>
        </div>
    </div>
</div>

<!-- Anime liên quan -->
<?php if (count($related_anime) > 0): ?>
    <div class="related-anime mb-5">
        <h2 class="section-title mb-4">
            <i class="fas fa-link me-2"></i> Anime liên quan
        </h2>
        
        <div class="row g-4">
            <?php foreach ($related_anime as $related): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="anime-card">
                        <a href="anime-detail.php?id=<?php echo $related['id']; ?>">
                            <div class="position-relative">
                                <img src="<?php echo get_thumbnail($related['thumbnail']); ?>" class="card-img-top" alt="<?php echo $related['title']; ?>">
                                <div class="episode-count">
                                    <?php echo $related['episode_count']; ?> tập
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-truncate"><?php echo $related['title']; ?></h5>
                                <div class="anime-stats">
                                    <span><i class="far fa-eye"></i> <?php echo number_format($related['views']); ?></span>
                                    <span><i class="far fa-star"></i> <?php echo number_format($related['rating'], 1); ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php
// JavaScript cho trang chi tiết anime
$extra_js = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Xử lý yêu thích
        const favoriteBtn = document.getElementById("favoriteBtn");
        if (favoriteBtn) {
            favoriteBtn.addEventListener("click", function() {
                const animeId = this.getAttribute("data-anime-id");
                
                // Gửi yêu cầu AJAX
                fetch("api/favorite.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        anime_id: animeId
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const favoriteText = document.getElementById("favoriteText");
                        
                        if (data.is_favorite) {
                            // Đã thêm vào yêu thích
                            favoriteBtn.classList.remove("btn-outline-danger");
                            favoriteBtn.classList.add("btn-danger");
                            favoriteBtn.querySelector("i").classList.remove("far");
                            favoriteBtn.querySelector("i").classList.add("fas");
                            favoriteText.textContent = "Đã yêu thích";
                        } else {
                            // Đã xóa khỏi yêu thích
                            favoriteBtn.classList.remove("btn-danger");
                            favoriteBtn.classList.add("btn-outline-danger");
                            favoriteBtn.querySelector("i").classList.remove("fas");
                            favoriteBtn.querySelector("i").classList.add("far");
                            favoriteText.textContent = "Thêm vào yêu thích";
                        }
                    } else {
                        alert("Đã xảy ra lỗi. Vui lòng thử lại sau.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Đã xảy ra lỗi. Vui lòng thử lại sau.");
                });
            });
        }
        
        // Xử lý hiển thị danh sách tập
        const showAllEpisodes = document.getElementById("showAllEpisodes");
        const showNewestEpisodes = document.getElementById("showNewestEpisodes");
        const episodeItems = document.querySelectorAll(".episode-item");
        
        if (showAllEpisodes && showNewestEpisodes) {
            showAllEpisodes.addEventListener("click", function() {
                episodeItems.forEach(item => {
                    item.style.display = "block";
                });
                
                showAllEpisodes.classList.add("active");
                showNewestEpisodes.classList.remove("active");
            });
            
            showNewestEpisodes.addEventListener("click", function() {
                episodeItems.forEach((item, index) => {
                    if (index < 20) {
                        item.style.display = "block";
                    } else {
                        item.style.display = "none";
                    }
                });
                
                showAllEpisodes.classList.remove("active");
                showNewestEpisodes.classList.add("active");
            });
        }
        
        // Tải bình luận
        loadComments();
        
        // Xử lý form bình luận
        const commentForm = document.getElementById("commentForm");
        if (commentForm) {
            commentForm.addEventListener("submit", function(e) {
                e.preventDefault();
                
                const animeId = document.getElementById("animeId").value;
                const content = document.getElementById("commentContent").value;
                
                if (content.trim() === "") {
                    alert("Vui lòng nhập nội dung bình luận.");
                    return;
                }
                
                // Gửi bình luận
                fetch("api/comment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        anime_id: animeId,
                        content: content
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Đặt lại form và tải lại bình luận
                        document.getElementById("commentContent").value = "";
                        loadComments();
                    } else {
                        alert(data.message || "Đã xảy ra lỗi khi gửi bình luận.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Đã xảy ra lỗi. Vui lòng thử lại sau.");
                });
            });
        }
        
        // Hàm tải bình luận
        function loadComments() {
            const animeId = document.getElementById("animeId").value;
            const commentsList = document.getElementById("commentsList");
            
            fetch(`api/comment.php?anime_id=${animeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.comments.length > 0) {
                            let html = "";
                            data.comments.forEach(comment => {
                                html += `
                                    <div class="comment-item card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <img src="${comment.avatar || "assets/images/default-avatar.jpg"}" class="rounded-circle comment-avatar" alt="${comment.username}">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-1">${comment.username}</h5>
                                                        <small class="text-muted">${comment.formatted_time}</small>
                                                    </div>
                                                    <p class="mb-1">${comment.content}</p>
                                                    <div class="comment-actions">
                                                        <button class="btn btn-sm btn-link reply-btn" data-comment-id="${comment.id}">
                                                            <i class="fas fa-reply"></i> Trả lời
                                                        </button>
                                                        <button class="btn btn-sm btn-link like-btn" data-comment-id="${comment.id}">
                                                            <i class="far fa-thumbs-up"></i> Thích (${comment.likes})
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            commentsList.innerHTML = html;
                        } else {
                            commentsList.innerHTML = `
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="lead">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                                </div>
                            `;
                        }
                    } else {
                        commentsList.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> Đã xảy ra lỗi khi tải bình luận.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    commentsList.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> Đã xảy ra lỗi khi tải bình luận.
                        </div>
                    `;
                });
        }
    });
</script>
';

// Include footer
include 'footer.php';
?>
