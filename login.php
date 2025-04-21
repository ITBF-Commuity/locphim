<?php
// Trang đăng nhập
session_start();

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Lấy URL chuyển hướng (nếu có)
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // Kiểm tra dữ liệu
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        try {
            // Kết nối database
            $db_file = './loc_phim.db';
            $db = new PDO('sqlite:' . $db_file);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Kiểm tra người dùng
            $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                
                // Lưu thông tin vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role_id'];
                $_SESSION['user_role_name'] = $user['role_name'];
                
                // Cập nhật lần đăng nhập cuối
                $stmt = $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Lưu cookie nếu chọn "Ghi nhớ đăng nhập"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 ngày
                    
                    $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                    
                    setcookie('remember_token', $token, $expires, '/');
                }
                
                // Chuyển hướng
                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                } else if ($user['role_id'] == 1) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Lọc Phim</title>
    <link rel="stylesheet" href="https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .auth-banner {
            background-color: rgba(0,0,0,0.7);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-bg {
            background-color: rgba(33, 37, 41, 0.8);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        body {
            background-image: url('https://source.unsplash.com/random/1920x1080/?cinema,movie,dark');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lọc Phim</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="anime.php">Anime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">Phim</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-primary active me-2">Đăng nhập</a>
                    <a href="register.php" class="btn btn-outline-light">Đăng ký</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="login-container">
            <div class="auth-banner text-center text-white">
                <h1 class="mb-3">Lọc Phim</h1>
                <p class="lead">Đăng nhập để trải nghiệm những bộ phim và anime chất lượng cao</p>
            </div>
            
            <div class="form-bg">
                <h2 class="text-center mb-4 text-white">Đăng nhập</h2>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label text-white">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label text-white" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Đăng nhập</button>
                    </div>
                </form>
                
                <hr class="text-white-50">
                
                <div class="text-center text-white">
                    <p>Chưa có tài khoản? <a href="register.php" class="text-info">Đăng ký</a></p>
                    <p><a href="forgot_password.php" class="text-info">Quên mật khẩu?</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-center text-white py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Lọc Phim - Tất cả các quyền được bảo lưu</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>