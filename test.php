<?php
// Kiểm tra hoạt động của CSS/JS
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra CSS/JS - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/test.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="header mb-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1><?php echo SITE_NAME; ?> - Kiểm tra CSS/JS</h1>
                </div>
                <div class="col-md-6 text-end">
                    <p>Thời gian hiện tại: <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4>Thông tin hệ thống</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>URL cơ sở:</strong> <?php echo SITE_URL; ?></p>
                            <p><strong>Đường dẫn cơ sở:</strong> <?php echo SITE_PATH; ?></p>
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4>Kiểm tra tệp CSS</h4>
                        </div>
                        <div class="card-body">
                            <p>Nếu bạn thấy trang này có định dạng đẹp, CSS đã được nạp thành công.</p>
                            <p>File CSS being tested: <code><?php echo SITE_URL; ?>/assets/css/test.css</code></p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Kiểm tra xem biểu tượng Font Awesome này có hiển thị không.
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning">
                            <h4>Kiểm tra JavaScript</h4>
                        </div>
                        <div class="card-body">
                            <p>Nhấn nút bên dưới để kiểm tra JavaScript:</p>
                            <button id="testButton" class="btn btn-primary">Kiểm tra JavaScript</button>
                            <div id="jsResult" class="mt-3"></div>
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
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>Version: <?php echo SITE_VERSION; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testButton = document.getElementById('testButton');
            const jsResult = document.getElementById('jsResult');
            
            testButton.addEventListener('click', function() {
                jsResult.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> JavaScript đang hoạt động tốt!
                        <br>Thời gian: ${new Date().toLocaleString()}
                    </div>
                `;
            });
        });
    </script>
</body>
</html>