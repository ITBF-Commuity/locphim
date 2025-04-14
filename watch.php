<?php
// Trang xem phim/anime
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

// Lấy thông tin video từ tham số url
$video_id = isset($_GET['id']) ? $_GET['id'] : null;
$episode_id = isset($_GET['episode']) ? intval($_GET['episode']) : null;

// Kiểm tra video tồn tại
if (!$video_id) {
    redirect('index.php');
    exit;
}

// Tải thông tin video từ database
$video = get_video_by_id($video_id);
if (!$video) {
    // Video không tồn tại
    set_flash_message('error', 'Video không tồn tại hoặc đã bị xóa');
    redirect('index.php');
    exit;
}

// Tải thông tin tập phim nếu có
$episode = null;
if ($episode_id) {
    $episode = get_episode_by_id($episode_id);
    if (!$episode || $episode['video_id'] != $video_id) {
        // Tập phim không tồn tại hoặc không thuộc về video này
        set_flash_message('error', 'Tập phim không tồn tại');
        redirect('index.php');
        exit;
    }
}

// Nếu không có episode_id nhưng video có nhiều tập, chuyển hướng tới tập đầu tiên
if (!$episode_id && isset($video['episode_count']) && $video['episode_count'] > 0) {
    $first_episode = get_first_episode($video_id);
    if ($first_episode) {
        redirect('watch.php?id=' . $video_id . '&episode=' . $first_episode['id']);
        exit;
    }
}

// Lấy danh sách tập phim nếu có
$episodes = [];
if (isset($video['episode_count']) && $video['episode_count'] > 0) {
    $episodes = get_episodes_by_video_id($video_id);
}

// Lấy thông tin người dùng hiện tại nếu đã đăng nhập
$current_user = null;
$user_vip = null;
if (is_logged_in()) {
    $current_user = get_current_user();
    
    // Kiểm tra trạng thái VIP
    $user_vip = check_vip_status($current_user['id']);
}

// Xác định các nguồn video và chất lượng có sẵn
$video_sources = [];
$video_qualities = ['360p', '480p', '720p', '1080p'];

// Tạo đường dẫn giả định cho các nguồn video với nhiều độ phân giải
foreach ($video_qualities as $quality) {
    $source_url = '';
    
    if ($episode) {
        // Đường dẫn cho tập phim
        $source_url = '/assets/videos/' . $video_id . '/episode_' . $episode_id . '_' . $quality . '.mp4';
    } else {
        // Đường dẫn cho phim lẻ
        $source_url = '/assets/videos/' . $video_id . '/' . $quality . '.mp4';
    }
    
    $video_sources[] = [
        'src' => $source_url,
        'type' => 'video/mp4',
        'label' => $quality
    ];
}

// Xác định độ phân giải tối đa dựa trên cấp VIP
$max_quality = '480p'; // Mặc định cho người dùng không phải VIP
$show_ads = true;

if ($user_vip) {
    // Lấy cấu hình VIP
    $vip_config = get_config('vip');
    $user_vip_level = $user_vip['level'];
    
    // Xác định độ phân giải tối đa cho cấp VIP
    if (isset($vip_config['levels'][$user_vip_level]['resolution'])) {
        $max_quality = $vip_config['levels'][$user_vip_level]['resolution'];
    }
    
    // Xác định có hiển thị quảng cáo không
    if (isset($vip_config['levels'][$user_vip_level]['ads'])) {
        $show_ads = $vip_config['levels'][$user_vip_level]['ads'];
    } else {
        $show_ads = false; // Mặc định VIP không có quảng cáo
    }
}

// Lấy lịch sử xem của người dùng cho video này
$watch_history = null;
if ($current_user) {
    // Gọi API để lấy thông tin thời gian xem
    $video_time_api_url = '/api/watch-history.php?action=video_time&video_id=' . $video_id;
    if ($episode_id) {
        $video_time_api_url .= '&episode_id=' . $episode_id;
    }
    
    // Thực hiện API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $video_time_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']); // Gửi cookie để duy trì phiên
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $response_data = json_decode($response, true);
        if ($response_data && $response_data['success'] && isset($response_data['data'])) {
            $watch_history = $response_data['data'];
        }
    }
}

// Tăng lượt xem cho video
update_video_views($video_id);

// Thêm vào danh sách đã xem gần đây
if ($current_user) {
    add_to_recently_watched($current_user['id'], $video_id, $episode_id);
}

// Lấy video liên quan
$related_videos = get_related_videos($video_id, 6);

// Lấy tập tiếp theo nếu có
$next_episode = null;
if ($episode_id && count($episodes) > 0) {
    $next_episode = get_next_episode($video_id, $episode_id);
}

// Chuẩn bị dữ liệu cho trình phát video
$player_data = [
    'videoId' => 'videoPlayer',
    'options' => [
        'autoplay' => false,
        'muted' => false,
        'poster' => $video['thumbnail'],
        'sources' => $video_sources,
        'defaultQuality' => '480p',
        'currentTime' => $watch_history ? floatval($watch_history['playback_time']) : 0,
        'showAds' => $show_ads,
        'vipLevel' => $user_vip ? intval($user_vip['level']) : 0,
        'saveProgress' => true,
        'autoNext' => true,
        'nextEpisodeUrl' => $next_episode ? ('watch.php?id=' . $video_id . '&episode=' . $next_episode['id']) : '',
        'videoInfo' => [
            'id' => $video_id,
            'episodeId' => $episode_id,
            'title' => $video['title'],
            'episodeTitle' => $episode ? $episode['title'] : ''
        ]
    ]
];

// Chuyển đổi dữ liệu trình phát thành JSON
$player_data_json = json_encode($player_data);

// Thêm tiêu đề trang
$page_title = $video['title'];
if ($episode) {
    $page_title .= ' - ' . $episode['title'];
}
$page_title .= ' - Lọc Phim';

$page_description = 'Xem ' . $page_title . ' - ' . ($video['description'] ? substr(strip_tags($video['description']), 0, 150) . '...' : '');

// Tải header
include 'header.php';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <div class="col-lg-9">
            <!-- Video Player -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="video-container" style="width: 100%; max-width: 100%;">
                        <video id="videoPlayer" class="w-100" controls>
                            <source src="<?php echo $video_sources[0]['src']; ?>" type="<?php echo $video_sources[0]['type']; ?>" data-quality="<?php echo $video_sources[0]['label']; ?>">
                            Trình duyệt của bạn không hỗ trợ video HTML5.
                        </video>
                    </div>
                </div>
            </div>
            
            <!-- Video Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h4 mb-2"><?php echo htmlspecialchars($video['title']); ?></h1>
                    <?php if ($episode): ?>
                        <h2 class="h6 text-muted mb-3"><?php echo htmlspecialchars($episode['title']); ?></h2>
                    <?php endif; ?>
                    
                    <div class="video-meta d-flex flex-wrap align-items-center mb-3">
                        <span class="mr-3 badge badge-primary"><?php echo $video['type'] ?? 'Anime'; ?></span>
                        <?php if (isset($video['rating']) && $video['rating'] > 0): ?>
                            <span class="mr-3"><i class="fas fa-star text-warning"></i> <?php echo number_format($video['rating'], 1); ?>/10</span>
                        <?php endif; ?>
                        <span class="mr-3"><i class="fas fa-eye"></i> <?php echo number_format($video['views'] ?? 0); ?> lượt xem</span>
                        <?php if (isset($video['release_year']) && $video['release_year'] > 0): ?>
                            <span class="mr-3"><i class="far fa-calendar-alt"></i> <?php echo $video['release_year']; ?></span>
                        <?php endif; ?>
                        <?php if (isset($video['status'])): ?>
                            <span class="mr-3">
                                <i class="fas fa-info-circle"></i> 
                                <?php 
                                    switch($video['status']) {
                                        case 'ongoing': echo 'Đang chiếu'; break;
                                        case 'completed': echo 'Hoàn thành'; break;
                                        case 'upcoming': echo 'Sắp chiếu'; break;
                                        default: echo ucfirst($video['status']); break;
                                    }
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="video-actions mb-4">
                        <?php if (is_logged_in()): ?>
                            <button class="btn btn-outline-primary mr-2 toggle-favorite" data-video-id="<?php echo $video_id; ?>">
                                <i class="far fa-heart"></i> Yêu thích
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('watch.php?id=' . $video_id . ($episode_id ? '&episode=' . $episode_id : '')); ?>" class="btn btn-outline-primary mr-2">
                                <i class="far fa-heart"></i> Yêu thích
                            </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-secondary mr-2" data-toggle="modal" data-target="#shareModal">
                            <i class="fas fa-share-alt"></i> Chia sẻ
                        </button>
                        
                        <button class="btn btn-outline-success mr-2" data-toggle="modal" data-target="#reportModal">
                            <i class="fas fa-flag"></i> Báo cáo
                        </button>
                        
                        <?php if (!$user_vip): ?>
                            <a href="vip.php" class="btn btn-warning">
                                <i class="fas fa-crown"></i> Nâng cấp VIP để xem chất lượng cao
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($episodes && count($episodes) > 0): ?>
                        <h3 class="h5 mb-3">Danh sách tập</h3>
                        <div class="episode-list mb-4">
                            <div class="row">
                                <?php foreach ($episodes as $ep): ?>
                                    <div class="col-md-3 col-6 mb-2">
                                        <a href="watch.php?id=<?php echo $video_id; ?>&episode=<?php echo $ep['id']; ?>" 
                                           class="btn btn-block <?php echo ($episode_id == $ep['id']) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            <?php echo $ep['episode_number']; ?>. <?php echo htmlspecialchars($ep['title']); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="h5 mb-3">Giới thiệu</h3>
                    <div class="video-description mb-4">
                        <?php if ($video['description']): ?>
                            <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Chưa có mô tả chi tiết cho nội dung này.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($video['genres']) && !empty($video['genres'])): ?>
                        <div class="video-genres mb-4">
                            <h3 class="h5 mb-2">Thể loại</h3>
                            <div>
                                <?php foreach ($video['genres'] as $genre): ?>
                                    <a href="browse.php?genre=<?php echo urlencode($genre); ?>" class="badge badge-secondary mr-2 mb-2 p-2"><?php echo htmlspecialchars($genre); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h3 class="h5 mb-0">Bình luận</h3>
                </div>
                <div class="card-body">
                    <?php if (is_logged_in()): ?>
                        <form id="commentForm" class="mb-4">
                            <div class="form-group">
                                <textarea class="form-control" id="commentContent" rows="3" placeholder="Viết bình luận của bạn..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <a href="login.php?redirect=<?php echo urlencode('watch.php?id=' . $video_id . ($episode_id ? '&episode=' . $episode_id : '')); ?>">Đăng nhập</a> để bình luận.
                        </div>
                    <?php endif; ?>
                    
                    <div id="commentsContainer">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Đang tải...</span>
                            </div>
                            <p class="mt-2">Đang tải bình luận...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Related Videos -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h3 class="h5 mb-0">Liên quan</h3>
                </div>
                <div class="card-body p-0">
                    <?php if ($related_videos && count($related_videos) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($related_videos as $related): ?>
                                <a href="watch.php?id=<?php echo $related['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 mr-3" style="width: 100px;">
                                            <img src="<?php echo $related['thumbnail']; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="img-fluid rounded">
                                        </div>
                                        <div>
                                            <h5 class="mb-1 h6"><?php echo htmlspecialchars($related['title']); ?></h5>
                                            <small class="text-muted">
                                                <i class="fas fa-eye"></i> <?php echo number_format($related['views'] ?? 0); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-3">
                            <p class="text-muted">Không có video liên quan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upgrade to VIP -->
            <?php if (!$user_vip): ?>
                <div class="card shadow-sm mb-4 bg-gradient-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="h5">Trải nghiệm xem phim tốt hơn</h3>
                        <p>Nâng cấp lên VIP để xem phim với chất lượng cao nhất, không quảng cáo</p>
                        <a href="vip.php" class="btn btn-light btn-block">
                            <i class="fas fa-crown text-warning"></i> Nâng cấp VIP
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Chia sẻ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="shareLink">Đường dẫn</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" value="<?php echo get_current_url(); ?>" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="copyShareLink">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="share-buttons mt-3">
                    <button class="btn btn-primary mr-2" onclick="shareOnFacebook()">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button class="btn btn-info mr-2" onclick="shareOnTwitter()">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                    <button class="btn btn-success" onclick="shareOnWhatsApp()">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Báo cáo vấn đề</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <div class="form-group">
                        <label for="reportType">Loại vấn đề</label>
                        <select class="form-control" id="reportType" required>
                            <option value="">-- Chọn vấn đề --</option>
                            <option value="video_not_playing">Video không phát</option>
                            <option value="wrong_episode">Tập phim sai</option>
                            <option value="audio_issue">Vấn đề về âm thanh</option>
                            <option value="subtitle_issue">Vấn đề về phụ đề</option>
                            <option value="other">Vấn đề khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reportDescription">Mô tả chi tiết</label>
                        <textarea class="form-control" id="reportDescription" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi báo cáo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Load CSS và JS cho trình phát video tùy chỉnh -->
<link rel="stylesheet" href="/assets/css/player.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
// Dữ liệu trình phát video
const playerData = <?php echo $player_data_json; ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo trình phát video
    const player = LocPhimPlayer.initializePlayer(playerData.videoId, playerData.options);
    
    // Xử lý sao chép đường dẫn chia sẻ
    document.getElementById('copyShareLink').addEventListener('click', function() {
        const shareLink = document.getElementById('shareLink');
        shareLink.select();
        document.execCommand('copy');
        
        // Hiển thị thông báo đã sao chép
        const button = this;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    });
    
    // Xử lý biểu mẫu báo cáo
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const reportType = document.getElementById('reportType').value;
        const reportDescription = document.getElementById('reportDescription').value;
        
        // Kiểm tra dữ liệu hợp lệ
        if (!reportType || !reportDescription) {
            alert('Vui lòng điền đầy đủ thông tin.');
            return;
        }
        
        // Gửi báo cáo
        fetch('/api/report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                video_id: '<?php echo $video_id; ?>',
                episode_id: <?php echo $episode_id ? $episode_id : 'null'; ?>,
                report_type: reportType,
                description: reportDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cảm ơn bạn đã báo cáo vấn đề. Chúng tôi sẽ xem xét sớm nhất có thể.');
                $('#reportModal').modal('hide');
                document.getElementById('reportForm').reset();
            } else {
                alert('Có lỗi xảy ra khi gửi báo cáo. Vui lòng thử lại sau.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi gửi báo cáo. Vui lòng thử lại sau.');
        });
    });
    
    // Tải bình luận
    loadComments();
    
    // Xử lý biểu mẫu bình luận
    document.getElementById('commentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const commentContent = document.getElementById('commentContent').value;
        
        // Kiểm tra nội dung bình luận
        if (!commentContent.trim()) {
            alert('Vui lòng nhập nội dung bình luận.');
            return;
        }
        
        // Gửi bình luận
        fetch('/api/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                video_id: '<?php echo $video_id; ?>',
                episode_id: <?php echo $episode_id ? $episode_id : 'null'; ?>,
                content: commentContent
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('commentContent').value = '';
                loadComments(); // Tải lại bình luận
            } else {
                alert('Có lỗi xảy ra khi gửi bình luận. Vui lòng thử lại sau.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi gửi bình luận. Vui lòng thử lại sau.');
        });
    });
    
    // Xử lý nút yêu thích
    document.querySelector('.toggle-favorite')?.addEventListener('click', function() {
        const videoId = this.getAttribute('data-video-id');
        const button = this;
        
        fetch('/api/favorites', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                video_id: videoId,
                action: 'toggle'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.is_favorite) {
                    button.innerHTML = '<i class="fas fa-heart"></i> Đã yêu thích';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                } else {
                    button.innerHTML = '<i class="far fa-heart"></i> Yêu thích';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-outline-primary');
                }
            } else {
                alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
        });
    });
});

// Hàm tải bình luận
function loadComments() {
    const commentsContainer = document.getElementById('commentsContainer');
    
    fetch('/api/comments?video_id=<?php echo $video_id; ?><?php echo $episode_id ? '&episode_id=' . $episode_id : ''; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const comments = data.data;
                
                let commentsHTML = '';
                
                if (comments.length === 0) {
                    commentsHTML = '<div class="text-center p-4"><p class="text-muted">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p></div>';
                } else {
                    comments.forEach(comment => {
                        commentsHTML += `
                            <div class="comment-item mb-3 pb-3 border-bottom">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 mr-3">
                                        <img src="${comment.user_avatar || '/assets/images/default-avatar.png'}" alt="${comment.username}" class="rounded-circle" width="40" height="40">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="font-weight-bold">${comment.username}</span>
                                                ${comment.is_vip ? '<span class="badge badge-warning ml-1"><i class="fas fa-crown"></i> VIP</span>' : ''}
                                                <small class="text-muted ml-2">${formatDate(comment.created_at)}</small>
                                            </div>
                                            <div class="comment-actions">
                                                <button class="btn btn-sm text-primary like-comment" data-comment-id="${comment.id}">
                                                    <i class="far fa-thumbs-up"></i> ${comment.likes || 0}
                                                </button>
                                                <button class="btn btn-sm text-secondary reply-comment" data-comment-id="${comment.id}">
                                                    <i class="far fa-comment"></i> Trả lời
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mb-1">${comment.content}</p>
                                        
                                        <!-- Hiển thị trả lời bình luận nếu có -->
                                        ${comment.replies && comment.replies.length > 0 ? renderReplies(comment.replies) : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                commentsContainer.innerHTML = commentsHTML;
                
                // Thêm sự kiện cho các nút like và reply
                setupCommentActions();
            } else {
                commentsContainer.innerHTML = '<div class="alert alert-danger">Lỗi khi tải bình luận. Vui lòng làm mới trang.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            commentsContainer.innerHTML = '<div class="alert alert-danger">Lỗi khi tải bình luận. Vui lòng làm mới trang.</div>';
        });
}

// Render phần trả lời bình luận
function renderReplies(replies) {
    let repliesHTML = '<div class="comment-replies mt-3 pl-3 border-left">';
    
    replies.forEach(reply => {
        repliesHTML += `
            <div class="comment-item mb-2 pb-2 border-bottom">
                <div class="d-flex">
                    <div class="flex-shrink-0 mr-2">
                        <img src="${reply.user_avatar || '/assets/images/default-avatar.png'}" alt="${reply.username}" class="rounded-circle" width="30" height="30">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="font-weight-bold">${reply.username}</span>
                                ${reply.is_vip ? '<span class="badge badge-warning ml-1"><i class="fas fa-crown"></i> VIP</span>' : ''}
                                <small class="text-muted ml-2">${formatDate(reply.created_at)}</small>
                            </div>
                            <div class="comment-actions">
                                <button class="btn btn-sm text-primary like-comment" data-comment-id="${reply.id}">
                                    <i class="far fa-thumbs-up"></i> ${reply.likes || 0}
                                </button>
                            </div>
                        </div>
                        <p class="mb-0">${reply.content}</p>
                    </div>
                </div>
            </div>
        `;
    });
    
    repliesHTML += '</div>';
    return repliesHTML;
}

// Thiết lập sự kiện cho các nút like và reply
function setupCommentActions() {
    // Xử lý nút like
    document.querySelectorAll('.like-comment').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            
            fetch('/api/comments/like', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comment_id: commentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật UI
                    this.innerHTML = `<i class="${data.is_liked ? 'fas' : 'far'} fa-thumbs-up"></i> ${data.likes}`;
                    if (data.is_liked) {
                        this.classList.add('text-primary');
                        this.classList.remove('text-secondary');
                    } else {
                        this.classList.add('text-secondary');
                        this.classList.remove('text-primary');
                    }
                } else {
                    if (data.message === 'Not logged in') {
                        alert('Vui lòng đăng nhập để thích bình luận.');
                    } else {
                        alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
            });
        });
    });
    
    // Xử lý nút reply
    document.querySelectorAll('.reply-comment').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const commentItem = this.closest('.comment-item');
            const username = commentItem.querySelector('.font-weight-bold').textContent;
            
            // Scroll đến form bình luận và focus vào textarea
            const commentTextarea = document.getElementById('commentContent');
            commentTextarea.focus();
            commentTextarea.value = `@${username} `;
            
            // Lưu comment id đang trả lời
            document.getElementById('commentForm').setAttribute('data-reply-to', commentId);
        });
    });
}

// Định dạng thời gian
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 1) {
        // Trong ngày hôm nay
        const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
        if (diffHours < 1) {
            // Dưới 1 giờ
            const diffMinutes = Math.floor(diffTime / (1000 * 60));
            if (diffMinutes < 1) {
                return 'Vừa xong';
            }
            return `${diffMinutes} phút trước`;
        }
        return `${diffHours} giờ trước`;
    } else if (diffDays < 7) {
        // Trong tuần này
        return `${diffDays} ngày trước`;
    } else {
        // Hơn 1 tuần
        return date.toLocaleDateString('vi-VN');
    }
}

// Các hàm chia sẻ
function shareOnFacebook() {
    const url = encodeURIComponent(document.getElementById('shareLink').value);
    const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(document.getElementById('shareLink').value);
    const text = encodeURIComponent('Đang xem <?php echo htmlspecialchars($page_title); ?> trên Lọc Phim');
    const shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(document.getElementById('shareLink').value);
    const text = encodeURIComponent('Đang xem <?php echo htmlspecialchars($page_title); ?> trên Lọc Phim');
    const shareUrl = `https://wa.me/?text=${text}%20${url}`;
    window.open(shareUrl, '_blank');
}
</script>

<?php include 'footer.php'; ?>