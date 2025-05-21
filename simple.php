<?php
// Simple.php - Một trang đơn giản để kiểm tra CSS và giao diện
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Đơn Giản - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/simple.css?v=<?php echo time(); ?>">
    
    <style>
        /* Inline CSS để đảm bảo hiển thị cơ bản */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .header {
            background-color: #e50914;
            color: white;
            padding: 10px 0;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div>
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.svg" alt="<?php echo SITE_NAME; ?>" class="logo">
                </div>
                <div>
                    <p><?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="container">
            <div class="card">
                <h1 class="card-title">Trang Đơn Giản - Kiểm Tra Giao Diện</h1>
                <p>Đây là trang kiểm tra giao diện đơn giản để xác minh rằng CSS và JavaScript đang hoạt động đúng.</p>
                <p>Thông tin server:</p>
                <ul>
                    <li><strong>SITE_URL:</strong> <?php echo SITE_URL; ?></li>
                    <li><strong>SITE_PATH:</strong> <?php echo SITE_PATH; ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2 class="card-title">Kiểm Tra Font Awesome</h2>
                <p>
                    <i class="fas fa-check text-success"></i> Nếu bạn thấy biểu tượng check màu xanh, Font Awesome đã hoạt động.
                </p>
                <p>
                    <i class="fas fa-film"></i> Phim
                    <i class="fas fa-tv"></i> TV Show
                    <i class="fas fa-crown"></i> VIP
                    <i class="fas fa-star"></i> Đánh giá
                </p>
            </div>
            
            <div class="card">
                <h2 class="card-title">Kiểm Tra Bootstrap</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            Nếu bạn thấy thông báo này được định dạng đẹp, Bootstrap đã hoạt động.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-primary">Nút Bootstrap</button>
                        <button class="btn btn-danger">Nút Nguy Hiểm</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Các Phim Mẫu</h2>
                <div class="movie-grid">
                    <div class="movie-card">
                        <img src="<?php echo SITE_URL; ?>/assets/images/default-poster.svg" alt="Phim mẫu 1" class="movie-poster">
                        <div class="movie-info">
                            <h3 class="movie-title">Phim Mẫu 1</h3>
                            <p><i class="fas fa-star text-warning"></i> 9.5/10</p>
                        </div>
                    </div>
                    
                    <div class="movie-card">
                        <img src="<?php echo SITE_URL; ?>/assets/images/default-poster.svg" alt="Phim mẫu 2" class="movie-poster">
                        <div class="movie-info">
                            <h3 class="movie-title">Phim Mẫu 2</h3>
                            <p><i class="fas fa-star text-warning"></i> 8.7/10</p>
                        </div>
                    </div>
                    
                    <div class="movie-card">
                        <img src="<?php echo SITE_URL; ?>/assets/images/default-poster.svg" alt="Phim mẫu 3" class="movie-poster">
                        <div class="movie-info">
                            <h3 class="movie-title">Phim Mẫu 3</h3>
                            <p><i class="fas fa-star text-warning"></i> 7.9/10</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Giới thiệu</h3>
                    <p>Lọc Phim là nền tảng xem phim và anime trực tuyến hàng đầu tại Việt Nam.</p>
                </div>
                <div class="col-md-6">
                    <h3>Tải xuống ứng dụng</h3>
                    <div>
                        <img src="<?php echo SITE_URL; ?>/assets/images/app-store.svg" alt="App Store" style="height: 40px; margin-right: 10px;">
                        <img src="<?php echo SITE_URL; ?>/assets/images/google-play.svg" alt="Google Play" style="height: 40px;">
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Lọc Phim. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kiểm tra JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('JavaScript đã hoạt động!');
        });
    </script>
</body>
</html>