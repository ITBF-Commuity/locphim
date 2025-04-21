<?php
// Trang quản trị - Dashboard
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Lấy thông tin người dùng
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Thống kê
    $user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $movie_count = $db->query("SELECT COUNT(*) FROM movies")->fetchColumn();
    $category_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $comment_count = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    
    // Người dùng mới nhất
    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $new_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Phim mới nhất
    $stmt = $db->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 5");
    $new_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Bình luận mới nhất
    $stmt = $db->query("SELECT c.*, u.username, m.title as movie_title FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      JOIN movies m ON c.movie_id = m.id 
                      ORDER BY c.created_at DESC LIMIT 5");
    $new_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Bảng điều khiển - Quản trị Lọc Phim';

// Bao gồm header quản trị
include 'admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">
                            <i class="fas fa-film"></i> Quản lý phim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-list"></i> Thể loại
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Người dùng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments"></i> Bình luận
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Cài đặt
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="google_drive.php">
                            <i class="fab fa-google-drive"></i> Google Drive
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Báo cáo</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history"></i> Nhật ký
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bảng điều khiển</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Xuất báo cáo</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Chia sẻ</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <i class="fas fa-calendar"></i>
                        Hôm nay
                    </button>
                </div>
            </div>

            <!-- Statistics cards -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-users fa-3x me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">Người dùng</h5>
                                <h2 class="mt-2 mb-0"><?php echo number_format($user_count); ?></h2>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="users.php" class="text-white text-decoration-none">Xem chi tiết</a>
                            <i class="fas fa-angle-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-film fa-3x me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">Phim</h5>
                                <h2 class="mt-2 mb-0"><?php echo number_format($movie_count); ?></h2>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="movies.php" class="text-white text-decoration-none">Xem chi tiết</a>
                            <i class="fas fa-angle-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-list fa-3x me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">Thể loại</h5>
                                <h2 class="mt-2 mb-0"><?php echo number_format($category_count); ?></h2>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="categories.php" class="text-white text-decoration-none">Xem chi tiết</a>
                            <i class="fas fa-angle-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-comments fa-3x me-3"></i>
                            <div>
                                <h5 class="card-title mb-0">Bình luận</h5>
                                <h2 class="mt-2 mb-0"><?php echo number_format($comment_count); ?></h2>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="comments.php" class="text-white text-decoration-none">Xem chi tiết</a>
                            <i class="fas fa-angle-right"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent data -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-users me-1"></i> Người dùng mới nhất
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên đăng nhập</th>
                                        <th>Email</th>
                                        <th>Ngày đăng ký</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($new_users)): ?>
                                        <?php foreach ($new_users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo $user['created_at']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            <a href="users.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-film me-1"></i> Phim mới nhất
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tiêu đề</th>
                                        <th>Loại</th>
                                        <th>Thêm vào lúc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($new_movies)): ?>
                                        <?php foreach ($new_movies as $movie): ?>
                                            <tr>
                                                <td><?php echo $movie['id']; ?></td>
                                                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                                <td><?php echo htmlspecialchars($movie['type']); ?></td>
                                                <td><?php echo $movie['created_at']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            <a href="movies.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-comments me-1"></i> Bình luận mới nhất
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người dùng</th>
                                        <th>Phim</th>
                                        <th>Nội dung</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($new_comments)): ?>
                                        <?php foreach ($new_comments as $comment): ?>
                                            <tr>
                                                <td><?php echo $comment['id']; ?></td>
                                                <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                                <td><?php echo htmlspecialchars($comment['movie_title']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($comment['content'], 0, 50)) . (strlen($comment['content']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo $comment['created_at']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            <a href="comments.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'admin_footer.php'; ?>