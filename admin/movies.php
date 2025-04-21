<?php
// Trang quản lý phim và anime
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
    
    // Xử lý thêm phim mới
    if (isset($_POST['add_movie'])) {
        $title = trim($_POST['title']);
        $original_title = trim($_POST['original_title']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $release_year = (int)$_POST['release_year'];
        $duration = (int)$_POST['duration'];
        $quality = trim($_POST['quality']);
        $trailer_url = trim($_POST['trailer_url']);
        $poster = trim($_POST['poster']);
        $thumbnail = trim($_POST['thumbnail']);
        $status = isset($_POST['status']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        
        // Tạo slug nếu trống
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }
        
        // Kiểm tra slug đã tồn tại chưa
        $stmt = $db->prepare("SELECT COUNT(*) FROM movies WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Slug đã tồn tại!";
        } else {
            // Thêm phim mới
            $stmt = $db->prepare("INSERT INTO movies (title, original_title, slug, description, type, release_year, 
                                 duration, quality, trailer_url, poster, thumbnail, status, featured, created_at, updated_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
            $stmt->execute([$title, $original_title, $slug, $description, $type, $release_year, 
                          $duration, $quality, $trailer_url, $poster, $thumbnail, $status, $featured]);
            
            $movie_id = $db->lastInsertId();
            
            // Thêm thể loại cho phim
            if (!empty($categories)) {
                $insert_category_stmt = $db->prepare("INSERT INTO movie_categories (movie_id, category_id, created_at) VALUES (?, ?, datetime('now'))");
                foreach ($categories as $category_id) {
                    $insert_category_stmt->execute([$movie_id, $category_id]);
                }
            }
            
            $success_message = "Đã thêm phim mới thành công!";
        }
    }
    
    // Xử lý cập nhật phim
    if (isset($_POST['update_movie'])) {
        $movie_id = (int)$_POST['movie_id'];
        $title = trim($_POST['title']);
        $original_title = trim($_POST['original_title']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $release_year = (int)$_POST['release_year'];
        $duration = (int)$_POST['duration'];
        $quality = trim($_POST['quality']);
        $trailer_url = trim($_POST['trailer_url']);
        $poster = trim($_POST['poster']);
        $thumbnail = trim($_POST['thumbnail']);
        $status = isset($_POST['status']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        
        // Tạo slug nếu trống
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }
        
        // Kiểm tra slug đã tồn tại chưa (trừ phim hiện tại)
        $stmt = $db->prepare("SELECT COUNT(*) FROM movies WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $movie_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Slug đã tồn tại!";
        } else {
            // Cập nhật phim
            $stmt = $db->prepare("UPDATE movies SET title = ?, original_title = ?, slug = ?, description = ?, 
                                type = ?, release_year = ?, duration = ?, quality = ?, trailer_url = ?, 
                                poster = ?, thumbnail = ?, status = ?, featured = ?, updated_at = datetime('now') 
                                WHERE id = ?");
            $stmt->execute([$title, $original_title, $slug, $description, $type, $release_year, 
                          $duration, $quality, $trailer_url, $poster, $thumbnail, $status, $featured, $movie_id]);
            
            // Xóa thể loại hiện tại
            $db->prepare("DELETE FROM movie_categories WHERE movie_id = ?")->execute([$movie_id]);
            
            // Thêm thể loại mới
            if (!empty($categories)) {
                $insert_category_stmt = $db->prepare("INSERT INTO movie_categories (movie_id, category_id, created_at) VALUES (?, ?, datetime('now'))");
                foreach ($categories as $category_id) {
                    $insert_category_stmt->execute([$movie_id, $category_id]);
                }
            }
            
            $success_message = "Đã cập nhật phim thành công!";
        }
    }
    
    // Xử lý xóa phim
    if (isset($_POST['delete_movie'])) {
        $movie_id = (int)$_POST['movie_id'];
        
        // Xóa thể loại liên kết
        $db->prepare("DELETE FROM movie_categories WHERE movie_id = ?")->execute([$movie_id]);
        
        // Xóa tập phim
        $db->prepare("DELETE FROM episodes WHERE movie_id = ?")->execute([$movie_id]);
        
        // Xóa bình luận
        $db->prepare("DELETE FROM comments WHERE movie_id = ?")->execute([$movie_id]);
        
        // Xóa yêu thích
        $db->prepare("DELETE FROM favorites WHERE movie_id = ?")->execute([$movie_id]);
        
        // Xóa lịch sử xem
        $db->prepare("DELETE FROM watch_history WHERE movie_id = ?")->execute([$movie_id]);
        
        // Xóa phim
        $db->prepare("DELETE FROM movies WHERE id = ?")->execute([$movie_id]);
        
        $success_message = "Đã xóa phim thành công!";
    }
    
    // Xử lý thêm tập phim
    if (isset($_POST['add_episode'])) {
        $movie_id = (int)$_POST['movie_id'];
        $episode_number = (int)$_POST['episode_number'];
        $title = trim($_POST['episode_title']);
        $video_url = trim($_POST['video_url']);
        $duration = (int)$_POST['episode_duration'];
        $status = isset($_POST['episode_status']) ? 1 : 0;
        
        // Kiểm tra tập đã tồn tại chưa
        $stmt = $db->prepare("SELECT COUNT(*) FROM episodes WHERE movie_id = ? AND episode_number = ?");
        $stmt->execute([$movie_id, $episode_number]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tập phim đã tồn tại!";
        } else {
            // Thêm tập phim mới
            $stmt = $db->prepare("INSERT INTO episodes (movie_id, episode_number, title, video_url, duration, status, created_at, updated_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
            $stmt->execute([$movie_id, $episode_number, $title, $video_url, $duration, $status]);
            
            // Cập nhật số tập cho phim
            $db->prepare("UPDATE movies SET episodes_count = (SELECT COUNT(*) FROM episodes WHERE movie_id = ?) WHERE id = ?")
               ->execute([$movie_id, $movie_id]);
            
            $success_message = "Đã thêm tập phim mới thành công!";
        }
    }
    
    // Lấy danh sách thể loại
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Phân trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Tìm kiếm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = "WHERE m.title LIKE ? OR m.original_title LIKE ? OR m.description LIKE ?";
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param];
    }
    
    // Lọc theo loại
    $type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
    if ($type_filter != 'all') {
        if (empty($where_clause)) {
            $where_clause = "WHERE m.type = ?";
        } else {
            $where_clause .= " AND m.type = ?";
        }
        $params[] = $type_filter;
    }
    
    // Lọc theo thể loại
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    if ($category_filter > 0) {
        if (empty($where_clause)) {
            $where_clause = "WHERE EXISTS (SELECT 1 FROM movie_categories mc WHERE mc.movie_id = m.id AND mc.category_id = ?)";
        } else {
            $where_clause .= " AND EXISTS (SELECT 1 FROM movie_categories mc WHERE mc.movie_id = m.id AND mc.category_id = ?)";
        }
        $params[] = $category_filter;
    }
    
    // Lọc theo trạng thái
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    if ($status_filter != 'all') {
        $status_value = ($status_filter == 'active') ? 1 : 0;
        if (empty($where_clause)) {
            $where_clause = "WHERE m.status = ?";
        } else {
            $where_clause .= " AND m.status = ?";
        }
        $params[] = $status_value;
    }
    
    // Đếm tổng số phim
    $count_sql = "SELECT COUNT(*) FROM movies m $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_movies = $stmt->fetchColumn();
    $total_pages = ceil($total_movies / $limit);
    
    // Lấy danh sách phim
    $sql = "SELECT m.* FROM movies m $where_clause ORDER BY m.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy số tập và thể loại cho từng phim
    $movie_details = [];
    foreach ($movies as $movie) {
        $movie_id = $movie['id'];
        
        // Lấy thể loại
        $cat_stmt = $db->prepare("SELECT c.id, c.name 
                                FROM categories c 
                                JOIN movie_categories mc ON c.id = mc.category_id 
                                WHERE mc.movie_id = ? 
                                ORDER BY c.name");
        $cat_stmt->execute([$movie_id]);
        $movie_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy số tập
        $ep_stmt = $db->prepare("SELECT COUNT(*) FROM episodes WHERE movie_id = ?");
        $ep_stmt->execute([$movie_id]);
        $episode_count = $ep_stmt->fetchColumn();
        
        $movie_details[$movie_id] = [
            'categories' => $movie_categories,
            'episode_count' => $episode_count
        ];
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Quản lý phim - Quản trị Lọc Phim';

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
                        <a class="nav-link active" href="movies.php">
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
                <h1 class="h2">Quản lý phim</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMovieModal">
                        <i class="fas fa-plus"></i> Thêm phim mới
                    </button>
                </div>
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
                        <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm phim..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <a href="?type=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $type_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                        <a href="?type=movie<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $type_filter == 'movie' ? 'active' : ''; ?>">Phim</a>
                        <a href="?type=anime<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $type_filter == 'anime' ? 'active' : ''; ?>">Anime</a>
                    </div>
                    
                    <div class="btn-group me-2">
                        <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                        <a href="?status=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'active' ? 'active' : ''; ?>">Công khai</a>
                        <a href="?status=inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>" class="btn btn-outline-secondary <?php echo $status_filter == 'inactive' ? 'active' : ''; ?>">Ẩn</a>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Thể loại
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item <?php echo $category_filter == 0 ? 'active' : ''; ?>" href="?category=0<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>">Tất cả thể loại</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($categories as $category): ?>
                            <li><a class="dropdown-item <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>" href="?category=<?php echo $category['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Bảng danh sách phim -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Loại</th>
                            <th>Năm</th>
                            <th>Thể loại</th>
                            <th>Tập</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movies)): ?>
                            <?php foreach ($movies as $movie): ?>
                                <tr>
                                    <td><?php echo $movie['id']; ?></td>
                                    <td>
                                        <?php if (!empty($movie['thumbnail'])): ?>
                                            <img src="<?php echo $movie['thumbnail']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="img-thumbnail" width="80">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 45px;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                        <?php if (!empty($movie['original_title'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($movie['original_title']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($movie['type'] == 'movie'): ?>
                                            <span class="badge bg-primary">Phim</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Anime</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $movie['release_year']; ?></td>
                                    <td>
                                        <?php if (!empty($movie_details[$movie['id']]['categories'])): ?>
                                            <?php foreach ($movie_details[$movie['id']]['categories'] as $index => $cat): ?>
                                                <?php if ($index < 3): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($cat['name']); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($movie_details[$movie['id']]['categories']) > 3): ?>
                                                <span class="badge bg-light text-dark">+<?php echo count($movie_details[$movie['id']]['categories']) - 3; ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa phân loại</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($movie['type'] == 'movie'): ?>
                                            <span class="badge bg-primary">Phim lẻ</span>
                                        <?php else: ?>
                                            <?php if ($movie_details[$movie['id']]['episode_count'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $movie_details[$movie['id']]['episode_count']; ?> tập</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Chưa có tập</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($movie['status'] == 1): ?>
                                            <span class="badge bg-success">Công khai</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Ẩn</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($movie['featured'] == 1): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-star"></i> Nổi bật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../detail.php?slug=<?php echo $movie['slug']; ?>" class="btn btn-sm btn-outline-info" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-movie" 
                                                    data-bs-toggle="modal" data-bs-target="#editMovieModal"
                                                    data-id="<?php echo $movie['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($movie['type'] == 'anime' || $movie['type'] == 'series'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success add-episode" 
                                                    data-bs-toggle="modal" data-bs-target="#addEpisodeModal"
                                                    data-id="<?php echo $movie['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($movie['title']); ?>">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-movie"
                                                    data-bs-toggle="modal" data-bs-target="#deleteMovieModal"
                                                    data-id="<?php echo $movie['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($movie['title']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Không tìm thấy phim nào</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $type_filter != 'all' ? '&type=' . $type_filter : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Next">
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

<!-- Modal thêm phim mới -->
<div class="modal fade" id="addMovieModal" tabindex="-1" aria-labelledby="addMovieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMovieModalLabel">Thêm phim mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="original_title" class="form-label">Tiêu đề gốc</label>
                                <input type="text" class="form-control" id="original_title" name="original_title">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" placeholder="tự động tạo nếu để trống">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Loại <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="movie">Phim lẻ</option>
                                    <option value="anime">Anime</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="release_year" class="form-label">Năm phát hành</label>
                                <input type="number" class="form-control" id="release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="duration" class="form-label">Thời lượng (phút)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quality" class="form-label">Chất lượng</label>
                                <select class="form-select" id="quality" name="quality">
                                    <option value="">Chọn chất lượng</option>
                                    <option value="HD">HD</option>
                                    <option value="Full HD">Full HD</option>
                                    <option value="4K">4K</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="trailer_url" class="form-label">URL Trailer</label>
                        <input type="url" class="form-control" id="trailer_url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="poster" class="form-label">URL Poster</label>
                                <input type="url" class="form-control" id="poster" name="poster" placeholder="https://example.com/posters/movie.jpg">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="thumbnail" class="form-label">URL Thumbnail</label>
                                <input type="url" class="form-control" id="thumbnail" name="thumbnail" placeholder="https://example.com/thumbnails/movie.jpg">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thể loại</label>
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="category_<?php echo $category['id']; ?>" name="categories[]" value="<?php echo $category['id']; ?>">
                                    <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">Công khai</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured">
                                <label class="form-check-label" for="featured">Nổi bật</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_movie" class="btn btn-primary">Thêm phim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa phim -->
<div class="modal fade" id="editMovieModal" tabindex="-1" aria-labelledby="editMovieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMovieModalLabel">Chỉnh sửa phim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post" id="editMovieForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_movie_id" name="movie_id">
                    
                    <!-- Các trường giống như form thêm phim mới -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_original_title" class="form-label">Tiêu đề gốc</label>
                                <input type="text" class="form-control" id="edit_original_title" name="original_title">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="edit_slug" name="slug">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Loại <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="movie">Phim lẻ</option>
                                    <option value="anime">Anime</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_release_year" class="form-label">Năm phát hành</label>
                                <input type="number" class="form-control" id="edit_release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_duration" class="form-label">Thời lượng (phút)</label>
                                <input type="number" class="form-control" id="edit_duration" name="duration" min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_quality" class="form-label">Chất lượng</label>
                                <select class="form-select" id="edit_quality" name="quality">
                                    <option value="">Chọn chất lượng</option>
                                    <option value="HD">HD</option>
                                    <option value="Full HD">Full HD</option>
                                    <option value="4K">4K</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_trailer_url" class="form-label">URL Trailer</label>
                        <input type="url" class="form-control" id="edit_trailer_url" name="trailer_url">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_poster" class="form-label">URL Poster</label>
                                <input type="url" class="form-control" id="edit_poster" name="poster">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_thumbnail" class="form-label">URL Thumbnail</label>
                                <input type="url" class="form-control" id="edit_thumbnail" name="thumbnail">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thể loại</label>
                        <div class="row" id="edit_categories_container">
                            <?php foreach ($categories as $category): ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input edit-category" type="checkbox" id="edit_category_<?php echo $category['id']; ?>" name="categories[]" value="<?php echo $category['id']; ?>">
                                    <label class="form-check-label" for="edit_category_<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_status" name="status">
                                <label class="form-check-label" for="edit_status">Công khai</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_featured" name="featured">
                                <label class="form-check-label" for="edit_featured">Nổi bật</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="update_movie" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal thêm tập phim -->
<div class="modal fade" id="addEpisodeModal" tabindex="-1" aria-labelledby="addEpisodeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEpisodeModalLabel">Thêm tập phim mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" id="episode_movie_id" name="movie_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Phim</label>
                        <input type="text" class="form-control" id="episode_movie_title" readonly>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="episode_number" class="form-label">Số tập <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="episode_number" name="episode_number" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="episode_title" class="form-label">Tiêu đề tập</label>
                                <input type="text" class="form-control" id="episode_title" name="episode_title">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="video_url" class="form-label">URL Video <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="video_url" name="video_url" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="episode_duration" class="form-label">Thời lượng (phút)</label>
                        <input type="number" class="form-control" id="episode_duration" name="episode_duration" min="1">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="episode_status" name="episode_status" checked>
                        <label class="form-check-label" for="episode_status">Công khai</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_episode" class="btn btn-primary">Thêm tập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xóa phim -->
<div class="modal fade" id="deleteMovieModal" tabindex="-1" aria-labelledby="deleteMovieModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMovieModalLabel">Xác nhận xóa phim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" id="delete_movie_id" name="movie_id">
                    <p>Bạn có chắc chắn muốn xóa phim <strong id="delete_movie_title"></strong>?</p>
                    <p class="text-danger">Cảnh báo: Hành động này không thể hoàn tác và sẽ xóa tất cả dữ liệu liên quan đến phim này, bao gồm tập phim, bình luận và đánh giá.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_movie" class="btn btn-danger">Xóa phim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý sự kiện cho nút Chỉnh sửa phim
    const editButtons = document.querySelectorAll('.edit-movie');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            
            // Gọi API để lấy thông tin phim theo ID
            fetch(`../api/movies.php?id=${movieId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const movie = data.data;
                    
                    // Điền thông tin vào form
                    document.getElementById('edit_movie_id').value = movie.id;
                    document.getElementById('edit_title').value = movie.title;
                    document.getElementById('edit_original_title').value = movie.original_title || '';
                    document.getElementById('edit_slug').value = movie.slug;
                    document.getElementById('edit_type').value = movie.type;
                    document.getElementById('edit_description').value = movie.description || '';
                    document.getElementById('edit_release_year').value = movie.release_year;
                    document.getElementById('edit_duration').value = movie.duration || '';
                    document.getElementById('edit_quality').value = movie.quality || '';
                    document.getElementById('edit_trailer_url').value = movie.trailer_url || '';
                    document.getElementById('edit_poster').value = movie.poster || '';
                    document.getElementById('edit_thumbnail').value = movie.thumbnail || '';
                    document.getElementById('edit_status').checked = movie.status == 1;
                    document.getElementById('edit_featured').checked = movie.featured == 1;
                    
                    // Reset tất cả checkbox thể loại
                    document.querySelectorAll('.edit-category').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Chọn các thể loại của phim
                    if (movie.categories && movie.categories.length > 0) {
                        movie.categories.forEach(categoryId => {
                            const checkbox = document.getElementById(`edit_category_${categoryId}`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                } else {
                    alert('Không thể lấy thông tin phim. Vui lòng thử lại sau.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi lấy thông tin phim.');
            });
        });
    });
    
    // Xử lý sự kiện cho nút Thêm tập phim
    const addEpisodeButtons = document.querySelectorAll('.add-episode');
    
    addEpisodeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            const movieTitle = this.getAttribute('data-title');
            
            document.getElementById('episode_movie_id').value = movieId;
            document.getElementById('episode_movie_title').value = movieTitle;
            
            // Lấy số tập tiếp theo
            fetch(`../api/episodes.php?movie_id=${movieId}&count=true`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('episode_number').value = data.count + 1;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    // Xử lý sự kiện cho nút Xóa phim
    const deleteButtons = document.querySelectorAll('.delete-movie');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-id');
            const movieTitle = this.getAttribute('data-title');
            
            document.getElementById('delete_movie_id').value = movieId;
            document.getElementById('delete_movie_title').textContent = movieTitle;
        });
    });
    
    // Tự động tạo slug từ tiêu đề
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            slugInput.value = slug;
        });
    }
});
</script>

<?php include 'admin_footer.php'; ?>