<?php
// Trang quản lý bình luận
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Kết nối database
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Xử lý xóa bình luận
    if (isset($_POST['delete']) && isset($_POST['comment_id'])) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$_POST['comment_id']]);
        $success_message = "Đã xóa bình luận thành công!";
    }
    
    // Xử lý phê duyệt bình luận
    if (isset($_POST['approve']) && isset($_POST['comment_id'])) {
        $stmt = $db->prepare("UPDATE comments SET status = 1 WHERE id = ?");
        $stmt->execute([$_POST['comment_id']]);
        $success_message = "Đã phê duyệt bình luận thành công!";
    }
    
    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Tìm kiếm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = "WHERE c.content LIKE ? OR u.username LIKE ? OR m.title LIKE ?";
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param];
    }
    
    // Lọc theo trạng thái
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    if ($status_filter != 'all') {
        $status_value = ($status_filter == 'approved') ? 1 : 0;
        if (empty($where_clause)) {
            $where_clause = "WHERE c.status = ?";
        } else {
            $where_clause .= " AND c.status = ?";
        }
        $params[] = $status_value;
    }
    
    // Đếm tổng số bình luận
    $count_sql = "SELECT COUNT(*) FROM comments c 
                 JOIN users u ON c.user_id = u.id 
                 JOIN movies m ON c.movie_id = m.id 
                 $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_comments = $stmt->fetchColumn();
    $total_pages = ceil($total_comments / $limit);
    
    // Lấy danh sách bình luận
    $sql = "SELECT c.*, u.username, m.title as movie_title, m.slug as movie_slug 
           FROM comments c 
           JOIN users u ON c.user_id = u.id 
           JOIN movies m ON c.movie_id = m.id 
           $where_clause 
           ORDER BY c.created_at DESC 
           LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Quản lý bình luận - Quản trị Lọc Phim';

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
                        <a class="nav-link" href="index.php">
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
                        <a class="nav-link active" href="comments.php">
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
                <h1 class="h2">Quản lý bình luận</h1>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Bộ lọc và tìm kiếm -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="get" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Tìm bình luận..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <a href="?status=all" class="btn btn-outline-secondary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                        <a href="?status=approved" class="btn btn-outline-secondary <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Đã duyệt</a>
                        <a href="?status=pending" class="btn btn-outline-secondary <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Chờ duyệt</a>
                    </div>
                </div>
            </div>
            
            <!-- Bảng bình luận -->
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người dùng</th>
                            <th>Phim</th>
                            <th>Nội dung</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td>
                                        <a href="../detail.php?slug=<?php echo $comment['movie_slug']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($comment['movie_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . (strlen($comment['content']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo $comment['created_at']; ?></td>
                                    <td>
                                        <?php if ($comment['status'] == 1): ?>
                                            <span class="badge bg-success">Đã duyệt</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary view-comment" data-bs-toggle="modal" data-bs-target="#viewCommentModal" data-id="<?php echo $comment['id']; ?>" data-content="<?php echo htmlspecialchars($comment['content']); ?>" data-username="<?php echo htmlspecialchars($comment['username']); ?>" data-movie="<?php echo htmlspecialchars($comment['movie_title']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($comment['status'] == 0): ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="approve" class="btn btn-sm btn-outline-success" onclick="return confirm('Bạn có chắc chắn muốn phê duyệt bình luận này?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa bình luận này?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Không có bình luận nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Xem chi tiết bình luận Modal -->
<div class="modal fade" id="viewCommentModal" tabindex="-1" aria-labelledby="viewCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCommentModalLabel">Chi tiết bình luận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Người dùng:</label>
                    <div id="modal-username"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Phim:</label>
                    <div id="modal-movie"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung:</label>
                    <div id="modal-content" class="p-3 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Xử lý hiển thị modal xem chi tiết bình luận
    document.addEventListener('DOMContentLoaded', function() {
        const viewBtns = document.querySelectorAll('.view-comment');
        
        viewBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const content = this.getAttribute('data-content');
                const username = this.getAttribute('data-username');
                const movie = this.getAttribute('data-movie');
                
                document.getElementById('modal-content').textContent = content;
                document.getElementById('modal-username').textContent = username;
                document.getElementById('modal-movie').textContent = movie;
            });
        });
    });
</script>

<?php include 'admin_footer.php'; ?>