<?php
// Trang cài đặt ban đầu cho Lọc Phim
session_start();

// Thiết lập hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đường dẫn tới file database
$db_file = './loc_phim.db';
$is_installed = false;

// Kiểm tra đã cài đặt chưa
if (file_exists($db_file)) {
    try {
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Kiểm tra bảng users đã tồn tại chưa
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($result->fetchColumn()) {
            $is_installed = true;
        }
    } catch (PDOException $e) {
        $error = 'Lỗi kiểm tra cơ sở dữ liệu: ' . $e->getMessage();
    }
}

// Xử lý cài đặt
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // Tạo database mới
    try {
        // Xóa file database cũ nếu có
        if (file_exists($db_file)) {
            unlink($db_file);
        }
        
        // Tạo kết nối mới
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Tạo bảng roles (vai trò người dùng)
        $db->exec("CREATE TABLE roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tạo bảng users (người dùng)
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role_id INTEGER DEFAULT 3, 
            status INTEGER DEFAULT 1,
            remember_token TEXT,
            vip_expiry DATETIME,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        )");
        
        // Tạo bảng categories (thể loại)
        $db->exec("CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            parent_id INTEGER,
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id)
        )");
        
        // Tạo bảng movies (phim và anime)
        $db->exec("CREATE TABLE movies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            original_title TEXT,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            poster TEXT,
            banner TEXT,
            type TEXT NOT NULL,
            release_year INTEGER,
            status TEXT DEFAULT 'ongoing',
            rating REAL DEFAULT 0,
            views INTEGER DEFAULT 0,
            featured INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tạo bảng movie_category (liên kết phim với thể loại)
        $db->exec("CREATE TABLE movie_category (
            movie_id INTEGER,
            category_id INTEGER,
            PRIMARY KEY (movie_id, category_id),
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng episodes (tập phim)
        $db->exec("CREATE TABLE episodes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            movie_id INTEGER,
            title TEXT NOT NULL,
            episode_number INTEGER NOT NULL,
            source TEXT,
            duration INTEGER,
            views INTEGER DEFAULT 0,
            is_vip INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng gdrive_sources (nguồn Google Drive)
        $db->exec("CREATE TABLE gdrive_sources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            episode_id INTEGER,
            drive_id TEXT NOT NULL,
            file_name TEXT,
            quality TEXT,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng subtitles (phụ đề)
        $db->exec("CREATE TABLE subtitles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            episode_id INTEGER,
            language TEXT NOT NULL,
            file_path TEXT NOT NULL,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng comments (bình luận)
        $db->exec("CREATE TABLE comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            movie_id INTEGER,
            episode_id INTEGER,
            parent_id INTEGER,
            content TEXT NOT NULL,
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng ratings (đánh giá)
        $db->exec("CREATE TABLE ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            movie_id INTEGER,
            rating INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng watch_history (lịch sử xem)
        $db->exec("CREATE TABLE watch_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            episode_id INTEGER,
            progress INTEGER DEFAULT 0,
            completed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng favorites (yêu thích)
        $db->exec("CREATE TABLE favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            movie_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng notifications (thông báo)
        $db->exec("CREATE TABLE notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            type TEXT DEFAULT 'info',
            is_read INTEGER DEFAULT 0,
            link TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng payments (thanh toán)
        $db->exec("CREATE TABLE payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            amount REAL NOT NULL,
            package TEXT NOT NULL,
            duration INTEGER NOT NULL,
            payment_method TEXT NOT NULL,
            transaction_id TEXT,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng user_preferences (cài đặt người dùng)
        $db->exec("CREATE TABLE user_preferences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            theme TEXT DEFAULT 'dark',
            subtitle_language TEXT DEFAULT 'vi',
            audio_language TEXT DEFAULT 'jp',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Tạo bảng settings (cài đặt hệ thống)
        $db->exec("CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT NOT NULL UNIQUE,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Chèn dữ liệu mẫu
        
        // Vai trò người dùng
        $db->exec("INSERT INTO roles (id, name, description) VALUES 
            (1, 'admin', 'Quản trị viên'),
            (2, 'moderator', 'Người kiểm duyệt'),
            (3, 'user', 'Thành viên thường'),
            (4, 'vip', 'Thành viên VIP')");
        
        // Tài khoản quản trị viên mặc định
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, email, password, role_id, status) VALUES 
            ('admin', 'admin@locphim.com', '$admin_password', 1, 1)");
        
        // Thể loại phim
        $db->exec("INSERT INTO categories (name, slug, description) VALUES 
            ('Hành động', 'hanh-dong', 'Phim hành động'),
            ('Tình cảm', 'tinh-cam', 'Phim tình cảm lãng mạn'),
            ('Hài hước', 'hai-huoc', 'Phim hài'),
            ('Kinh dị', 'kinh-di', 'Phim kinh dị'),
            ('Viễn tưởng', 'vien-tuong', 'Phim viễn tưởng'),
            ('Phiêu lưu', 'phieu-luu', 'Phim phiêu lưu'),
            ('Hoạt hình', 'hoat-hinh', 'Phim hoạt hình'),
            ('Tâm lý', 'tam-ly', 'Phim tâm lý'),
            ('Chiến tranh', 'chien-tranh', 'Phim chiến tranh'),
            ('Âm nhạc', 'am-nhac', 'Phim âm nhạc')");
        
        // Một số phim mẫu
        $db->exec("INSERT INTO movies (title, original_title, slug, description, type, release_year, status, rating, views, featured) VALUES 
            ('Cuộc Chiến Vô Cực', 'Avengers: Infinity War', 'cuoc-chien-vo-cuc', 'Avengers và đồng minh của họ phải sẵn sàng hy sinh tất cả để đánh bại Thanos.', 'movie', 2018, 'completed', 8.5, 1500, 1),
            ('Người Nhện: Vũ Trụ Mới', 'Spider-Man: Into the Spider-Verse', 'nguoi-nhen-vu-tru-moi', 'Miles Morales trở thành Người Nhện trong vũ trụ của riêng mình.', 'movie', 2018, 'completed', 8.7, 1200, 1),
            ('Naruto Shippuden', 'ナルト 疾風伝', 'naruto-shippuden', 'Naruto trở về làng Lá và tiếp tục hành trình trở thành Hokage.', 'anime', 2007, 'completed', 8.6, 5000, 1),
            ('Attack on Titan', '進撃の巨人', 'attack-on-titan', 'Eren và bạn bè chiến đấu chống lại những người khổng lồ.', 'anime', 2013, 'completed', 9.0, 4800, 1),
            ('One Piece', 'ワンピース', 'one-piece', 'Luffy và băng hải tặc mũ rơm tìm kiếm kho báu huyền thoại One Piece.', 'anime', 1999, 'ongoing', 8.9, 7500, 1)");
        
        // Liên kết phim với thể loại
        $db->exec("INSERT INTO movie_category (movie_id, category_id) VALUES 
            (1, 1), (1, 5), (1, 6),
            (2, 1), (2, 5), (2, 7),
            (3, 1), (3, 6), (3, 7),
            (4, 1), (4, 5), (4, 7),
            (5, 1), (5, 6), (5, 7)");
        
        // Tạo một số tập phim mẫu
        $db->exec("INSERT INTO episodes (movie_id, title, episode_number, duration, views) VALUES 
            (3, 'Trở Về', 1, 1320, 1200),
            (3, 'Akatsuki Hành Động', 2, 1320, 1150),
            (3, 'Kết Quả Của Sự Luyện Tập', 3, 1320, 1100),
            (4, 'Với Tôi, Trong 2000 Năm', 1, 1320, 1500),
            (4, 'Ngày Đó', 2, 1320, 1450),
            (4, 'Ánh Sáng Mờ Nhạt Giữa Sự Tuyệt Vọng', 3, 1320, 1400),
            (5, 'Ta Là Luffy! Người Sẽ Trở Thành Vua Hải Tặc!', 1, 1320, 1800),
            (5, 'Xuất Hiện! Thợ Săn Hải Tặc Roronoa Zoro', 2, 1320, 1750),
            (5, 'Morgan vs Luffy! Người Bí Ẩn Là Cô Gái Nami!', 3, 1320, 1700)");
        
        // Thêm một số nguồn Google Drive mẫu
        $db->exec("INSERT INTO gdrive_sources (episode_id, drive_id, quality, is_default) VALUES 
            (1, '1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (2, '2aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (3, '3aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (4, '4aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (5, '5aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (6, '6aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (7, '7aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (8, '8aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1),
            (9, '9aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', '720p', 1)");
        
        // Thêm cài đặt hệ thống
        $db->exec("INSERT INTO settings (key, value) VALUES 
            ('site_name', 'Lọc Phim'),
            ('site_description', 'Website xem phim và anime trực tuyến hàng đầu Việt Nam'),
            ('site_logo', 'assets/images/logo.png'),
            ('admin_email', 'admin@locphim.com'),
            ('items_per_page', '12'),
            ('maintenance_mode', '0')");
        
        // Thiết lập quyền cho file database
        chmod($db_file, 0666);
        
        $success = 'Cài đặt thành công! Bây giờ bạn có thể <a href="index.php">truy cập trang chủ</a> hoặc <a href="login.php">đăng nhập</a> với tài khoản: admin / admin123';
        $is_installed = true;
    } catch (PDOException $e) {
        $error = 'Lỗi cài đặt: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Lọc Phim</title>
    <link rel="stylesheet" href="https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .setup-header p {
            color: #aaa;
            font-size: 1.1rem;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #252525;
            border-radius: 8px;
        }
        .step h3 {
            margin-top: 0;
            color: #fff;
        }
        .step-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: #0d6efd;
        }
        .completed-icon {
            color: #198754;
        }
        .error-icon {
            color: #dc3545;
        }
        .btn-install {
            background-color: #0d6efd;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-install:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }
        .already-installed {
            text-align: center;
            padding: 20px;
            background-color: #252525;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .database-details {
            background-color: #252525;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .database-details h4 {
            margin-top: 0;
            color: #fff;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .database-details ul {
            list-style-type: none;
            padding-left: 0;
        }
        .database-details li {
            padding: 5px 0;
            border-bottom: 1px solid #333;
        }
        .database-details li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Cài đặt Lọc Phim</h1>
            <p>Tiến trình cài đặt và khởi tạo cơ sở dữ liệu ban đầu</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($is_installed): ?>
        <div class="already-installed">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h2>Đã cài đặt thành công!</h2>
            <p>Cơ sở dữ liệu đã được khởi tạo và sẵn sàng sử dụng.</p>
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary me-2">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="login.php" class="btn btn-success me-2">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
                <a href="admin/index.php" class="btn btn-info">
                    <i class="fas fa-cog"></i> Quản trị
                </a>
            </div>
            
            <div class="database-details mt-4">
                <h4><i class="fas fa-info-circle"></i> Thông tin cài đặt</h4>
                <ul>
                    <li><strong>Tài khoản quản trị:</strong> admin / admin123</li>
                    <li><strong>Cơ sở dữ liệu:</strong> SQLite (<?php echo $db_file; ?>)</li>
                    <li><strong>Số bảng đã tạo:</strong> 16</li>
                    <li><strong>Dữ liệu mẫu:</strong> Đã thêm</li>
                </ul>
                
                <p class="mt-3 mb-0 text-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Lưu ý: Vì lý do bảo mật, hãy thay đổi mật khẩu quản trị sau khi đăng nhập.
                </p>
            </div>
            
            <form method="post" class="mt-4">
                <input type="hidden" name="install" value="1">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Cài đặt lại sẽ xóa tất cả dữ liệu hiện tại. Bạn có chắc chắn muốn tiếp tục?');">
                    <i class="fas fa-redo-alt"></i> Cài đặt lại
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="steps">
            <div class="step">
                <h3><i class="fas fa-database step-icon"></i> Bước 1: Khởi tạo cơ sở dữ liệu</h3>
                <p>Tạo file cơ sở dữ liệu SQLite và các bảng cần thiết.</p>
            </div>
            
            <div class="step">
                <h3><i class="fas fa-table step-icon"></i> Bước 2: Tạo cấu trúc bảng</h3>
                <p>Tạo 16 bảng dữ liệu cần thiết cho hệ thống.</p>
            </div>
            
            <div class="step">
                <h3><i class="fas fa-user-plus step-icon"></i> Bước 3: Thêm dữ liệu ban đầu</h3>
                <p>Thêm tài khoản quản trị và dữ liệu mẫu để bắt đầu sử dụng.</p>
            </div>
            
            <form method="post" class="text-center mt-4">
                <input type="hidden" name="install" value="1">
                <button type="submit" class="btn btn-primary btn-install">
                    <i class="fas fa-rocket me-2"></i> Bắt đầu cài đặt
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>