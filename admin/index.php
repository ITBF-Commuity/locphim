<?php
/**
 * Trang chủ quản trị
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Bảng Điều Khiển';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền xem dashboard
$admin = require_admin_permission('view_dashboard');

// Lấy thống kê
$db_type = get_config('db.type');

// Thống kê người dùng
$users_sql = "SELECT COUNT(*) AS total FROM users";
$users_result = db_query($users_sql);
$total_users = 0;

// Thống kê video
$videos_sql = "SELECT COUNT(*) AS total FROM videos";
$videos_result = db_query($videos_sql);
$total_videos = 0;

// Thống kê bình luận
$comments_sql = "SELECT COUNT(*) AS total FROM comments";
$comments_result = db_query($comments_sql);
$total_comments = 0;

// Thống kê VIP
$vip_sql = "SELECT COUNT(*) AS total FROM vip_members WHERE status = 'active'";
$vip_result = db_query($vip_sql);
$total_vip = 0;

// Lấy kết quả tùy thuộc loại database
if ($db_type === 'postgresql') {
    $total_users = pg_fetch_assoc($users_result)['total'] ?? 0;
    $total_videos = pg_fetch_assoc($videos_result)['total'] ?? 0;
    $total_comments = pg_fetch_assoc($comments_result)['total'] ?? 0;
    $total_vip = pg_fetch_assoc($vip_result)['total'] ?? 0;
} else {
    $total_users = $users_result->fetch_assoc()['total'] ?? 0;
    $total_videos = $videos_result->fetch_assoc()['total'] ?? 0;
    $total_comments = $comments_result->fetch_assoc()['total'] ?? 0;
    $total_vip = $vip_result->fetch_assoc()['total'] ?? 0;
}

// Lấy thống kê gần đây
$recent_users_sql = "SELECT COUNT(*) AS total FROM users WHERE created_at >= NOW() - INTERVAL '7 days'";
$recent_videos_sql = "SELECT COUNT(*) AS total FROM videos WHERE created_at >= NOW() - INTERVAL '7 days'";
$recent_comments_sql = "SELECT COUNT(*) AS total FROM comments WHERE created_at >= NOW() - INTERVAL '7 days'";
$recent_vip_sql = "SELECT COUNT(*) AS total FROM vip_members WHERE created_at >= NOW() - INTERVAL '7 days'";

// Điều chỉnh cú pháp SQL cho MySQL
if ($db_type !== 'postgresql') {
    $recent_users_sql = "SELECT COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $recent_videos_sql = "SELECT COUNT(*) AS total FROM videos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $recent_comments_sql = "SELECT COUNT(*) AS total FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $recent_vip_sql = "SELECT COUNT(*) AS total FROM vip_members WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$recent_users_result = db_query($recent_users_sql);
$recent_videos_result = db_query($recent_videos_sql);
$recent_comments_result = db_query($recent_comments_sql);
$recent_vip_result = db_query($recent_vip_sql);

$recent_users = 0;
$recent_videos = 0;
$recent_comments = 0;
$recent_vip = 0;

if ($db_type === 'postgresql') {
    $recent_users = pg_fetch_assoc($recent_users_result)['total'] ?? 0;
    $recent_videos = pg_fetch_assoc($recent_videos_result)['total'] ?? 0;
    $recent_comments = pg_fetch_assoc($recent_comments_result)['total'] ?? 0;
    $recent_vip = pg_fetch_assoc($recent_vip_result)['total'] ?? 0;
} else {
    $recent_users = $recent_users_result->fetch_assoc()['total'] ?? 0;
    $recent_videos = $recent_videos_result->fetch_assoc()['total'] ?? 0;
    $recent_comments = $recent_comments_result->fetch_assoc()['total'] ?? 0;
    $recent_vip = $recent_vip_result->fetch_assoc()['total'] ?? 0;
}

// Top 5 phim được xem nhiều nhất
$top_videos_sql = "SELECT title, slug, views FROM videos ORDER BY views DESC LIMIT 5";
$top_videos_result = db_query($top_videos_sql);
$top_videos = [];

if ($db_type === 'postgresql') {
    while ($row = pg_fetch_assoc($top_videos_result)) {
        $top_videos[] = $row;
    }
} else {
    while ($row = $top_videos_result->fetch_assoc()) {
        $top_videos[] = $row;
    }
}

// Hoạt động mới nhất
$latest_activities_sql = "SELECT a.action, a.created_at, u.username, u.avatar 
                         FROM admin_logs a 
                         LEFT JOIN users u ON a.user_id = u.id 
                         ORDER BY a.created_at DESC 
                         LIMIT 10";
$latest_activities_result = db_query($latest_activities_sql);
$latest_activities = [];

if ($db_type === 'postgresql') {
    while ($row = pg_fetch_assoc($latest_activities_result)) {
        $latest_activities[] = $row;
    }
} else {
    while ($row = $latest_activities_result->fetch_assoc()) {
        $latest_activities[] = $row;
    }
}

// Báo cáo lỗi mới nhất
$latest_reports_sql = "SELECT r.id, r.report_type, r.description, r.created_at, r.status,
                      v.title as video_title, v.slug as video_slug 
                      FROM reports r 
                      LEFT JOIN videos v ON r.video_id = v.video_id 
                      ORDER BY r.created_at DESC 
                      LIMIT 5";
$latest_reports_result = db_query($latest_reports_sql);
$latest_reports = [];

if ($db_type === 'postgresql') {
    while ($row = pg_fetch_assoc($latest_reports_result)) {
        $latest_reports[] = $row;
    }
} else {
    while ($row = $latest_reports_result->fetch_assoc()) {
        $latest_reports[] = $row;
    }
}

// JS cho biểu đồ
$extra_js = '';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Bảng Điều Khiển</h1>
        <p class="admin-page-subtitle">Chào mừng trở lại, <?php echo $admin['full_name'] ?? $admin['username']; ?>!</p>
    </div>
    
    <div class="admin-page-actions">
        <a href="videos-add.php" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Thêm Phim Mới
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fas fa-flag mr-1"></i> Xem Báo Cáo
        </a>
    </div>
</div>

<div class="admin-stat-cards">
    <div class="admin-stat-card">
        <div class="admin-stat-icon bg-primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="admin-stat-info">
            <h2 class="admin-stat-value"><?php echo number_format($total_users); ?></h2>
            <p class="admin-stat-label">Người Dùng <span class="text-success ml-1">(+<?php echo number_format($recent_users); ?>)</span></p>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon bg-info">
            <i class="fas fa-film"></i>
        </div>
        <div class="admin-stat-info">
            <h2 class="admin-stat-value"><?php echo number_format($total_videos); ?></h2>
            <p class="admin-stat-label">Phim & Anime <span class="text-success ml-1">(+<?php echo number_format($recent_videos); ?>)</span></p>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon bg-warning">
            <i class="fas fa-comments"></i>
        </div>
        <div class="admin-stat-info">
            <h2 class="admin-stat-value"><?php echo number_format($total_comments); ?></h2>
            <p class="admin-stat-label">Bình Luận <span class="text-success ml-1">(+<?php echo number_format($recent_comments); ?>)</span></p>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon bg-success">
            <i class="fas fa-crown"></i>
        </div>
        <div class="admin-stat-info">
            <h2 class="admin-stat-value"><?php echo number_format($total_vip); ?></h2>
            <p class="admin-stat-label">Thành Viên VIP <span class="text-success ml-1">(+<?php echo number_format($recent_vip); ?>)</span></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-chart-bar mr-2"></i> Số Lượng Người Xem Trong 30 Ngày Qua
                </h2>
            </div>
            <div class="admin-card-body">
                <canvas id="viewsChart" height="250"></canvas>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="fas fa-trophy mr-2"></i> Top Phim Xem Nhiều
                        </h2>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên Phim</th>
                                        <th class="text-right">Lượt Xem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_videos)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_videos as $video): ?>
                                            <tr>
                                                <td>
                                                    <a href="../watch.php?slug=<?php echo $video['slug']; ?>" target="_blank">
                                                        <?php echo $video['title']; ?>
                                                    </a>
                                                </td>
                                                <td class="text-right"><?php echo number_format($video['views']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="admin-card-footer text-center">
                        <a href="videos.php?sort=views" class="btn btn-sm btn-outline-primary">Xem Tất Cả</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="fas fa-flag mr-2"></i> Báo Cáo Lỗi Mới Nhất
                        </h2>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Loại</th>
                                        <th>Phim</th>
                                        <th class="text-center">Trạng Thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($latest_reports)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Không có báo cáo lỗi nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($latest_reports as $report): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    switch ($report['report_type']) {
                                                        case 'video_not_working':
                                                            echo 'Video lỗi';
                                                            break;
                                                        case 'subtitle_issue':
                                                            echo 'Lỗi phụ đề';
                                                            break;
                                                        case 'content_issue':
                                                            echo 'Vấn đề nội dung';
                                                            break;
                                                        default:
                                                            echo $report['report_type'];
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($report['video_title'])): ?>
                                                        <a href="../watch.php?slug=<?php echo $report['video_slug']; ?>" target="_blank">
                                                            <?php echo $report['video_title']; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Không xác định</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    switch ($report['status']) {
                                                        case 'pending':
                                                            echo '<span class="badge badge-warning">Chờ xử lý</span>';
                                                            break;
                                                        case 'in_progress':
                                                            echo '<span class="badge badge-info">Đang xử lý</span>';
                                                            break;
                                                        case 'resolved':
                                                            echo '<span class="badge badge-success">Đã xử lý</span>';
                                                            break;
                                                        case 'rejected':
                                                            echo '<span class="badge badge-danger">Từ chối</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">' . ucfirst($report['status']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="admin-card-footer text-center">
                        <a href="reports.php" class="btn btn-sm btn-outline-primary">Xem Tất Cả</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-bell mr-2"></i> Tin Tức & Cập Nhật
                </h2>
            </div>
            <div class="admin-card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-success mr-2">Mới</span>
                                <strong>Tính năng mới: Quản lý API anime</strong>
                            </div>
                            <small class="text-muted">Hôm nay</small>
                        </div>
                        <p class="mb-0 mt-1">Hệ thống API anime đã được cập nhật với nhiều tính năng hơn.</p>
                    </li>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-info mr-2">Cập nhật</span>
                                <strong>Cập nhật trình phát video</strong>
                            </div>
                            <small class="text-muted">Hôm qua</small>
                        </div>
                        <p class="mb-0 mt-1">Trình phát video đã được cải thiện với tính năng tự động phát tập tiếp theo.</p>
                    </li>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-warning mr-2">Quan trọng</span>
                                <strong>Bảo mật hệ thống</strong>
                            </div>
                            <small class="text-muted">3 ngày trước</small>
                        </div>
                        <p class="mb-0 mt-1">Cập nhật bảo mật quan trọng đã được triển khai. Vui lòng kiểm tra cài đặt.</p>
                    </li>
                </ul>
            </div>
            <div class="admin-card-footer text-center">
                <a href="#" class="btn btn-sm btn-outline-primary">Xem Tất Cả Tin Tức</a>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-history mr-2"></i> Hoạt Động Gần Đây
                </h2>
            </div>
            <div class="admin-card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($latest_activities)): ?>
                        <div class="list-group-item">Không có hoạt động nào gần đây</div>
                    <?php else: ?>
                        <?php foreach ($latest_activities as $activity): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex">
                                    <div class="mr-3">
                                        <?php if (!empty($activity['avatar'])): ?>
                                            <img src="<?php echo $activity['avatar']; ?>" alt="<?php echo $activity['username']; ?>" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="admin-header-avatar-text" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($activity['username'] ?? 'A', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <span class="font-weight-bold"><?php echo $activity['username'] ?? 'Admin'; ?></span>
                                            <small class="text-muted ml-auto"><?php echo time_elapsed_string($activity['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo $activity['action']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-card-footer text-center">
                <a href="logs.php" class="btn btn-sm btn-outline-primary">Xem Tất Cả Hoạt Động</a>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-tasks mr-2"></i> Trạng Thái Hệ Thống
                </h2>
            </div>
            <div class="admin-card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-database mr-2 text-primary"></i> Cơ sở dữ liệu
                        </div>
                        <span class="badge badge-success">Hoạt động</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-server mr-2 text-primary"></i> Máy chủ
                        </div>
                        <span class="badge badge-success">Hoạt động</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-exchange-alt mr-2 text-primary"></i> API
                        </div>
                        <span class="badge badge-success">Hoạt động</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-credit-card mr-2 text-primary"></i> Thanh toán
                        </div>
                        <span class="badge badge-success">Hoạt động</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-shield-alt mr-2 text-primary"></i> Bảo mật
                        </div>
                        <span class="badge badge-success">Bảo vệ</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
// Hàm tính thời gian đã trôi qua
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    
    $string = [
        'y' => 'năm',
        'm' => 'tháng',
        'w' => 'tuần',
        'd' => 'ngày',
        'h' => 'giờ',
        'i' => 'phút',
        's' => 'giây',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }
    
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
}

// JS cho biểu đồ
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dữ liệu mẫu cho biểu đồ lượt xem
    const viewsCtx = document.getElementById("viewsChart").getContext("2d");
    const viewsChart = new Chart(viewsCtx, {
        type: "line",
        data: {
            labels: ' . json_encode(array_map(function($i) { return date('d/m', strtotime("-$i days")); }, range(30, 0))) . ',
            datasets: [{
                label: "Lượt xem",
                data: ' . json_encode(array_map(function() { return rand(100, 1000); }, range(0, 31))) . ',
                backgroundColor: "rgba(52, 152, 219, 0.1)",
                borderColor: "rgba(52, 152, 219, 1)",
                borderWidth: 2,
                pointBackgroundColor: "rgba(52, 152, 219, 1)",
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: "index",
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return "Lượt xem: " + context.raw.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 10
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "rgba(0, 0, 0, 0.05)"
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>