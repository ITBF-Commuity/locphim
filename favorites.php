<?php
// Tiêu đề trang
$page_title = 'Danh sách yêu thích';

// Include header
include 'header.php';

// Yêu cầu đăng nhập
require_login();

// Lấy thông tin người dùng hiện tại
$user = get_current_user();

// Lấy danh sách anime yêu thích
$favorites = get_favorites($_SESSION['user_id']);

// Xử lý xóa khỏi danh sách yêu thích
if (isset($_POST['remove_favorite']) && isset($_POST['anime_id'])) {
    $anime_id = intval($_POST['anime_id']);
    
    // Gọi hàm toggle_favorite để xóa khỏi danh sách yêu thích
    toggle_favorite($_SESSION['user_id'], $anime_id);
    
    // Chuyển hướng để tránh gửi lại form
    header('Location: favorites.php');
    exit;
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="profile.php">Tài khoản</a></li>
        <li class="breadcrumb-item active" aria-current="page">Danh sách yêu thích</li>
    </ol>
</nav>

<!-- Tiêu đề trang -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title">
        <i class="fas fa-heart text-danger me-2"></i> Anime yêu thích
    </h1>
</div>

<!-- Danh sách anime yêu thích -->
<?php if (count($favorites) > 0): ?>
    <div class="row g-4">
        <?php foreach ($favorites as $favorite): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="anime-card">
                    <div class="card h-100">
                        <div class="position-relative">
                            <a href="anime-detail.php?id=<?php echo $favorite['anime_id']; ?>">
                                <img src="<?php echo get_thumbnail($favorite['thumbnail']); ?>" class="card-img-top" alt="<?php echo $favorite['title']; ?>">
                                <div class="rating-badge">
                                    <i class="fas fa-star"></i> <?php echo number_format($favorite['rating'], 1); ?>
                                </div>
                            </a>
                            <form action="favorites.php" method="POST" class="position-absolute top-0 end-0 m-2">
                                <input type="hidden" name="anime_id" value="<?php echo $favorite['anime_id']; ?>">
                                <button type="submit" name="remove_favorite" class="btn btn-sm btn-danger" title="Xóa khỏi danh sách yêu thích">
                                    <i class="fas fa-heart-broken"></i>
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="anime-detail.php?id=<?php echo $favorite['anime_id']; ?>" class="text-decoration-none text-dark"><?php echo $favorite['title']; ?></a>
                            </h5>
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary"><?php echo $favorite['release_year']; ?></span>
                                <span class="text-muted small">
                                    <i class="fas fa-clock"></i> <?php echo format_time($favorite['created_at']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100">
                                <a href="anime-detail.php?id=<?php echo $favorite['anime_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-info-circle"></i> Chi tiết
                                </a>
                                <?php
                                // Lấy tập mới nhất của anime
                                $sql = "SELECT id FROM episodes WHERE anime_id = ? ORDER BY episode_number ASC LIMIT 1";
                                $result = db_query($sql, [$favorite['anime_id']], false);
                                if ($result->num_rows > 0) {
                                    $episode = $result->fetch_assoc();
                                ?>
                                    <a href="watch.php?episode_id=<?php echo $episode['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Xem ngay
                                    </a>
                                <?php } else { ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-clock"></i> Sắp ra mắt
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Phân trang -->
    <nav class="mt-5">
        <ul class="pagination justify-content-center">
            <li class="page-item disabled">
                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Trang trước</a>
            </li>
            <li class="page-item active" aria-current="page">
                <a class="page-link" href="#">1</a>
            </li>
            <li class="page-item disabled">
                <a class="page-link" href="#">Trang sau</a>
            </li>
        </ul>
    </nav>
<?php else: ?>
    <div class="text-center py-5">
        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNTAgMjUwIiBoZWlnaHQ9IjI1MCIgd2lkdGg9IjI1MCIgZmlsbD0iI2YwZjBmMCI+PHBhdGggZD0iTTEyNSAwQzU2LjEgMCAwIDU2LjEgMCAxMjVzNTYuMSAxMjUgMTI1IDEyNSAxMjUtNTYuMSAxMjUtMTI1UzE5My45IDAgMTI1IDB6bTE3LjkgMjEzLjFjLTM4LjEgMC02OC45LTMwLjktNjguOS02OC45IDAtMzggMzAuOS02OC45IDY4LjktNjguOSAzOC4xIDAgNjguOSAzMC45IDY4LjkgNjguOSAwIDM4LTMwLjggNjguOS02OC45IDY4Ljl6IiBvcGFjaXR5PSIuMiIvPjxwYXRoIGQ9Ik0xMjIgMTIyTDcyIDcybTYwIDBsLTQwIDQwYzUuMSA1LjEgNS4xIDEzLjQgMCAxOC41IiBzdHJva2U9IiNhMGEwYTAiIHN0cm9rZS13aWR0aD0iOCIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+PHBhdGggZD0iTTEyNSA5MmMtMTguMiAwLTMzIDE0LjgtMzMgMzNzMTQuOCAzMyAzMyAzMyAzMy0xNC44IDMzLTMzLTE0LjgtMzMtMzMtMzN6bTAgNTdDMTExLjYgMTQ5IDEwMSAxMzguNCAxMDEgMTI1czEwLjYtMjQgMjQtMjQgMjQgMTAuNiAyNCAyNC0xMC42IDI0LTI0IDI0eiIvPjwvc3ZnPg==" alt="Không có dữ liệu" class="mb-4" width="150">
        <h3 class="mb-3">Danh sách yêu thích trống</h3>
        <p class="text-muted mb-4">Bạn chưa thêm anime nào vào danh sách yêu thích.</p>
        <a href="anime.php" class="btn btn-primary">
            <i class="fas fa-search me-2"></i> Khám phá anime ngay
        </a>
    </div>
<?php endif; ?>

<?php
// Include footer
include 'footer.php';
?>
