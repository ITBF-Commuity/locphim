<?php
// Trang quản lý thể loại
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] > 2) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Xử lý hành động
$success = '';
$error = '';

// Xử lý xóa thể loại
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $category_id = (int)$_GET['delete'];
        
        // Kiểm tra xem thể loại có phim nào không
        $stmt = $db->prepare("SELECT COUNT(*) FROM movie_categories WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = 'Không thể xóa thể loại đang được sử dụng bởi ' . $count . ' phim.';
        } else {
            // Xóa thể loại
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success = 'Đã xóa thể loại thành công!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi xóa thể loại: ' . $e->getMessage();
    }
}

// Xử lý thêm mới/chỉnh sửa thể loại
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $category_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $description = $_POST['description'] ?? '';
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // Tạo slug nếu trống
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    
    try {
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($category_id > 0) {
            // Cập nhật thể loại
            $stmt = $db->prepare("UPDATE categories SET 
                name = ?, 
                slug = ?, 
                description = ?, 
                parent_id = ?,
                updated_at = datetime('now')
                WHERE id = ?");
            $stmt->execute([
                $name, 
                $slug, 
                $description, 
                $parent_id,
                $category_id
            ]);
            
            $success = 'Đã cập nhật thể loại thành công!';
        } else {
            // Thêm thể loại mới
            $stmt = $db->prepare("INSERT INTO categories (
                name, slug, description, parent_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))");
            $stmt->execute([
                $name, 
                $slug, 
                $description, 
                $parent_id
            ]);
            
            $success = 'Đã thêm thể loại mới thành công!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi lưu thể loại: ' . $e->getMessage();
    }
}

// Lấy danh sách thể loại
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tham số tìm kiếm
    $search = $_GET['search'] ?? '';
    
    // Câu lệnh cơ sở
    $query = "SELECT c.*, p.name as parent_name, 
              (SELECT COUNT(*) FROM movie_categories WHERE category_id = c.id) as movie_count 
              FROM categories c
              LEFT JOIN categories p ON c.parent_id = p.id";
    $params = [];
    
    // Thêm điều kiện lọc
    if (!empty($search)) {
        $query .= " WHERE c.name LIKE ? OR c.description LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Thêm sắp xếp
    $query .= " ORDER BY c.name ASC";
    
    // Thực thi truy vấn
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh sách thể loại cho dropdown
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi truy vấn dữ liệu: ' . $e->getMessage();
}

// Tiêu đề trang
$page_title = 'Quản lý thể loại - Quản trị Lọc Phim';

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
                        <a class="nav-link active" href="categories.php">
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
                <h1 class="h2">Quản lý thể loại</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus"></i> Thêm mới
                    </button>
                </div>
            </div>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Bộ lọc -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-filter"></i> Tìm kiếm thể loại
                </div>
                <div class="card-body">
                    <form method="get" id="filterForm" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Tìm kiếm theo tên thể loại...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <a href="categories.php" class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i> Đặt lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danh sách thể loại -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-list me-1"></i> Danh sách thể loại (<?php echo count($categories); ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên thể loại</th>
                                    <th>Slug</th>
                                    <th>Thể loại cha</th>
                                    <th>Mô tả</th>
                                    <th>Số phim</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                            <td><?php echo $category['parent_id'] ? htmlspecialchars($category['parent_name']) : '<span class="text-muted">Không có</span>'; ?></td>
                                            <td>
                                                <?php if (!empty($category['description'])): ?>
                                                    <?php echo mb_strlen($category['description']) > 50 ? htmlspecialchars(mb_substr($category['description'], 0, 50)) . '...' : htmlspecialchars($category['description']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Không có mô tả</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $category['movie_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary edit-category-btn" 
                                                        data-id="<?php echo $category['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                        data-slug="<?php echo htmlspecialchars($category['slug']); ?>"
                                                        data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                        data-parent-id="<?php echo $category['parent_id']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($category['movie_count'] == 0): ?>
                                                        <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Không thể xóa thể loại đang được sử dụng">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-3">Không có thể loại nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal thêm/sửa thể loại -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="categories.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Thêm thể loại mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="category_id" name="id" value="">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên thể loại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="tự-động-tạo-nếu-trống">
                        <div class="form-text">URL thân thiện, để trống để tự động tạo từ tên.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Thể loại cha</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Không có</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="save_category" class="btn btn-primary">Lưu thể loại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật modal khi ấn nút sửa
    const editButtons = document.querySelectorAll('.edit-category-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const slug = this.getAttribute('data-slug');
            const description = this.getAttribute('data-description');
            const parentId = this.getAttribute('data-parent-id');
            
            document.getElementById('categoryModalLabel').textContent = 'Sửa thể loại';
            document.getElementById('category_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('slug').value = slug;
            document.getElementById('description').value = description;
            
            // Đặt giá trị cho select
            const parentSelect = document.getElementById('parent_id');
            if (parentId && parentId != 'null') {
                parentSelect.value = parentId;
            } else {
                parentSelect.value = '';
            }
        });
    });
    
    // Đặt lại form khi mở modal thêm mới
    const addButton = document.querySelector('[data-bs-target="#categoryModal"]:not(.edit-category-btn)');
    if (addButton) {
        addButton.addEventListener('click', function() {
            document.getElementById('categoryModalLabel').textContent = 'Thêm thể loại mới';
            document.getElementById('category_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('description').value = '';
            document.getElementById('parent_id').value = '';
        });
    }
});
</script>

<?php include 'admin_footer.php'; ?>