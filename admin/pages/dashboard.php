<?php
/**
 * Lọc Phim - Trang tổng quan quản trị
 * 
 * Hiển thị thống kê và tóm tắt hoạt động của website
 */

// Lấy số lượng phim
$totalMovies = 0;
try {
    $totalMovies = $db->getOne("SELECT COUNT(*) FROM movies");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy số lượng người dùng
$totalUsers = 0;
try {
    $totalUsers = $db->getOne("SELECT COUNT(*) FROM users");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy số lượng người dùng VIP
$totalVipUsers = 0;
try {
    $totalVipUsers = $db->getOne("SELECT COUNT(*) FROM users WHERE is_vip = true");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy tổng lượt xem
$totalViews = 0;
try {
    $totalViews = $db->getOne("SELECT SUM(views) FROM movies");
} catch (Exception $e) {
    // Không làm gì
}

// Tỷ lệ phần trăm người dùng VIP
$vipPercentage = $totalUsers > 0 ? round(($totalVipUsers / $totalUsers) * 100) : 0;

// Lấy phim mới nhất
$latestMovies = [];
try {
    $latestMovies = $db->getAll("
        SELECT m.*, c.name as category_name 
        FROM movies m 
        LEFT JOIN movie_categories mc ON m.id = mc.movie_id 
        LEFT JOIN categories c ON mc.category_id = c.id 
        GROUP BY m.id 
        ORDER BY m.created_at DESC 
        LIMIT 5
    ");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy người dùng mới nhất
$latestUsers = [];
try {
    $latestUsers = $db->getAll("
        SELECT * FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy bình luận mới nhất
$latestComments = [];
try {
    $latestComments = $db->getAll("
        SELECT c.*, u.username, m.title as movie_title 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN movies m ON c.movie_id = m.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
} catch (Exception $e) {
    // Không làm gì
}

// Lấy thanh toán gần đây
$latestPayments = [];
try {
    $latestPayments = $db->getAll("
        SELECT p.*, u.username 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
} catch (Exception $e) {
    // Không làm gì
}
?>

<div class="page-header">
    <h1 class="page-title">Bảng điều khiển</h1>
    <div class="page-actions">
        <a href="/admin?page=settings" class="btn btn-outline">
            <i class="fas fa-cog"></i> Cài đặt
        </a>
        <a href="/admin?page=movies&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm phim mới
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <i class="fas fa-film"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalMovies); ?></div>
            <div class="stat-label">Phim & Series</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">Người dùng</div>
            <div class="stat-progress">
                <div class="stat-progress-bar stat-progress-success" style="width: <?php echo $vipPercentage; ?>%;"></div>
            </div>
            <div class="stat-label"><?php echo $vipPercentage; ?>% VIP</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-info">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalViews); ?></div>
            <div class="stat-label">Lượt xem</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-warning">
            <i class="fas fa-crown"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalVipUsers); ?></div>
            <div class="stat-label">Thành viên VIP</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- Phim mới nhất -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-film"></i> Phim mới thêm
                </h3>
                <a href="/admin?page=movies" class="btn btn-sm btn-outline">Xem tất cả</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Thể loại</th>
                                <th>Lượt xem</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestMovies as $movie): ?>
                            <tr>
                                <td>
                                    <a href="/admin?page=movies&action=edit&id=<?php echo $movie['id']; ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($movie['category_name'] ?? 'Chưa phân loại'); ?></td>
                                <td><?php echo number_format($movie['views']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($movie['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($latestMovies)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Không có dữ liệu</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Bình luận mới nhất -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-comments"></i> Bình luận gần đây
                </h3>
                <a href="/admin?page=comments" class="btn btn-sm btn-outline">Xem tất cả</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Người dùng</th>
                                <th>Phim</th>
                                <th>Nội dung</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestComments as $comment): ?>
                            <tr>
                                <td>
                                    <a href="/admin?page=users&action=edit&id=<?php echo $comment['user_id']; ?>">
                                        <?php echo htmlspecialchars($comment['username']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="/admin?page=movies&action=edit&id=<?php echo $comment['movie_id']; ?>">
                                        <?php echo htmlspecialchars($comment['movie_title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars(substr($comment['content'], 0, 30)) . (strlen($comment['content']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($comment['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($latestComments)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Không có dữ liệu</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <!-- Người dùng mới nhất -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-plus"></i> Người dùng mới
                </h3>
                <a href="/admin?page=users" class="btn btn-sm btn-outline">Xem tất cả</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>VIP</th>
                                <th>Ngày đăng ký</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestUsers as $user): ?>
                            <tr>
                                <td>
                                    <a href="/admin?page=users&action=edit&id=<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_vip']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> VIP</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Thường</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($latestUsers)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Không có dữ liệu</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Thanh toán gần đây -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-credit-card"></i> Thanh toán gần đây
                </h3>
                <a href="/admin?page=payments" class="btn btn-sm btn-outline">Xem tất cả</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Người dùng</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestPayments as $payment): ?>
                            <tr>
                                <td>
                                    <a href="/admin?page=users&action=edit&id=<?php echo $payment['user_id']; ?>">
                                        <?php echo htmlspecialchars($payment['username']); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($payment['amount']) . ' ' . $payment['currency']; ?></td>
                                <td>
                                    <?php
                                    $method = $payment['payment_method'];
                                    $methodIcon = '';
                                    $methodName = '';
                                    
                                    switch ($method) {
                                        case 'vnpay':
                                            $methodIcon = 'fas fa-credit-card';
                                            $methodName = 'VNPAY';
                                            break;
                                        case 'momo':
                                            $methodIcon = 'fas fa-mobile-alt';
                                            $methodName = 'MoMo';
                                            break;
                                        case 'stripe':
                                            $methodIcon = 'fab fa-cc-stripe';
                                            $methodName = 'Stripe';
                                            break;
                                        default:
                                            $methodIcon = 'fas fa-money-bill';
                                            $methodName = ucfirst($method);
                                            break;
                                    }
                                    ?>
                                    <span><i class="<?php echo $methodIcon; ?>"></i> <?php echo $methodName; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status = $payment['status'];
                                    $statusClass = '';
                                    
                                    switch ($status) {
                                        case 'completed':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'pending':
                                            $statusClass = 'bg-warning';
                                            break;
                                        case 'failed':
                                            $statusClass = 'bg-danger';
                                            break;
                                        case 'refunded':
                                            $statusClass = 'bg-info';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($latestPayments)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Không có dữ liệu</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .col-lg-6 {
        width: 100%;
        padding: 0 10px;
    }
    
    @media (min-width: 992px) {
        .col-lg-6 {
            width: 50%;
        }
    }
    
    .badge {
        display: inline-block;
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        color: white;
    }
    
    .bg-success {
        background-color: var(--success-color);
    }
    
    .bg-warning {
        background-color: var(--warning-color);
    }
    
    .bg-danger {
        background-color: var(--danger-color);
    }
    
    .bg-info {
        background-color: var(--info-color);
    }
    
    .bg-secondary {
        background-color: var(--text-light);
    }
    
    .text-center {
        text-align: center;
    }
</style>