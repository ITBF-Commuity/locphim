<?php
/**
 * Trang hiển thị danh sách thông báo
 */

// Bao gồm các file cần thiết
require_once '../init.php';
require_once '../config.php';
require_once '../db_connect.php';
require_once '../functions.php';
require_once '../auth.php';

// Kiểm tra đăng nhập
$current_user = get_logged_in_user();
if (!$current_user) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Lấy tổng số thông báo
$total_notifications = db_fetch_one(
    "SELECT COUNT(*) as count FROM notifications 
     WHERE user_id = ? OR user_id IS NULL",
    [$current_user['id']]
)['count'];

$total_pages = ceil($total_notifications / $per_page);

// Lấy danh sách thông báo
$notifications = get_user_notifications($current_user['id'], $per_page, $offset);

// Đánh dấu tất cả thông báo đã xem
if (isset($_GET['mark_all_read'])) {
    mark_all_notifications_as_read($current_user['id']);
    header('Location: notifications.php');
    exit;
}

// Đánh dấu một thông báo đã xem
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    mark_notification_as_read($notification_id, $current_user['id']);
    
    // Chuyển hướng đến link của thông báo (nếu có)
    $notification = db_fetch_one("SELECT * FROM notifications WHERE id = ?", [$notification_id]);
    if ($notification && !empty($notification['link'])) {
        header('Location: ' . $notification['link']);
        exit;
    } else {
        header('Location: notifications.php');
        exit;
    }
}

// Thiết lập tiêu đề trang
$page_title = 'Thông báo - Lọc Phim';

// Bao gồm header
require_once '../includes/header.php';
?>

<div class="container">
    <div class="notifications-page">
        <div class="page-header">
            <h1>Thông báo của bạn</h1>
            <div class="page-actions">
                <a href="?mark_all_read=1" class="btn btn-primary">Đánh dấu tất cả đã đọc</a>
            </div>
        </div>
        
        <?php if (empty($notifications)): ?>
        <div class="empty-notifications">
            <div class="empty-icon">
                <i class="fas fa-bell-slash"></i>
            </div>
            <h3>Không có thông báo nào</h3>
            <p>Bạn chưa nhận được thông báo nào.</p>
        </div>
        <?php else: ?>
        <div class="notification-list-container">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification-item-large <?php echo $notification['is_read'] == 0 ? 'unread' : 'read'; ?>">
                <div class="notification-icon">
                    <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                </div>
                <div class="notification-content">
                    <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                    <div class="notification-message"><?php echo htmlspecialchars($notification['content']); ?></div>
                    <div class="notification-meta">
                        <span class="notification-time"><?php echo format_time($notification['created_at']); ?></span>
                        <?php if ($notification['is_read'] == 0): ?>
                        <span class="notification-badge">Mới</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="notification-actions">
                    <?php if (!empty($notification['link'])): ?>
                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">Xem</a>
                    <?php elseif ($notification['is_read'] == 0): ?>
                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">Đánh dấu đã đọc</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="pagination-prev"><i class="fas fa-chevron-left"></i> Trước</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                <a href="?page=1" class="pagination-link">1</a>
                <?php if ($start_page > 2): ?>
                <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?>" class="pagination-link"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="pagination-next">Sau <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Hàm lấy icon cho loại thông báo
function getNotificationIcon($type) {
    switch ($type) {
        case 'system':
            return 'fa fa-bullhorn';
        case 'movie':
            return 'fa fa-film';
        case 'comment':
            return 'fa fa-comment';
        case 'payment':
            return 'fa fa-money-bill';
        case 'user':
            return 'fa fa-user';
        default:
            return 'fa fa-bell';
    }
}

// Bao gồm footer
require_once '../includes/footer.php';
?>