<?php
/**
 * Lọc Phim - Tạo cơ sở dữ liệu
 */

// Nếu đã cài đặt rồi thì chặn truy cập
if (file_exists('install.lock')) {
    die('Hệ thống đã được cài đặt. Vui lòng xóa file install.lock để cài đặt lại.');
}

// Kiểm tra xem có yêu cầu cài đặt không
if (!isset($_POST['install']) || $_POST['install'] !== 'true') {
    die('Truy cập trái phép.');
}

// Load file cấu hình
require_once 'config.php';

try {
    // Tạo schema cho database
    switch (DB_TYPE) {
        case 'pgsql':
            create_postgresql_schema($db);
            break;
            
        case 'mysql':
            create_mysql_schema($db);
            break;
            
        case 'sqlite':
            create_sqlite_schema($db);
            break;
            
        default:
            die('Loại cơ sở dữ liệu không hỗ trợ: ' . DB_TYPE);
    }
    
    // Tạo tài khoản admin
    if (isset($_POST['admin_username']) && isset($_POST['admin_password']) && isset($_POST['admin_email'])) {
        $admin_username = trim($_POST['admin_username']);
        $admin_password = trim($_POST['admin_password']);
        $admin_email = trim($_POST['admin_email']);
        
        // Kiểm tra dữ liệu hợp lệ
        if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
            die('Vui lòng nhập đầy đủ thông tin tài khoản admin.');
        }
        
        // Kiểm tra email hợp lệ
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            die('Email không hợp lệ.');
        }
        
        // Tạo tài khoản admin
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        
        $admin_data = [
            'username' => $admin_username,
            'password' => $password_hash,
            'email' => $admin_email,
            'is_admin' => true,
            'is_vip' => true,
            'vip_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => $now,
            'updated_at' => $now
        ];
        
        $db->insert('users', $admin_data);
        
        // Thêm một số dữ liệu mẫu
        add_sample_data($db);
        
        // Tạo file lock để đánh dấu đã cài đặt
        file_put_contents('install.lock', '1');
        
        echo 'success';
    } else {
        die('Thiếu thông tin tài khoản admin.');
    }
} catch (Exception $e) {
    die('Lỗi khi cài đặt: ' . $e->getMessage());
}

// Hàm tạo schema cho PostgreSQL
function create_postgresql_schema($db) {
    // Tạo bảng users
    $db->createTable('users', [
        'id SERIAL PRIMARY KEY',
        'username VARCHAR(50) NOT NULL UNIQUE',
        'email VARCHAR(100) NOT NULL UNIQUE',
        'password VARCHAR(255) NOT NULL',
        'fullname VARCHAR(100)',
        'avatar VARCHAR(255)',
        'phone VARCHAR(20)',
        'is_admin BOOLEAN NOT NULL DEFAULT FALSE',
        'is_vip BOOLEAN NOT NULL DEFAULT FALSE',
        'vip_expires_at TIMESTAMP',
        'remember_token VARCHAR(100)',
        'reset_token VARCHAR(100)',
        'reset_token_expires_at TIMESTAMP',
        'last_login TIMESTAMP',
        'status VARCHAR(20) NOT NULL DEFAULT \'active\'',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng countries
    $db->createTable('countries', [
        'id SERIAL PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng categories
    $db->createTable('categories', [
        'id SERIAL PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'description TEXT',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng actors
    $db->createTable('actors', [
        'id SERIAL PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'bio TEXT',
        'avatar VARCHAR(255)',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng movies
    $db->createTable('movies', [
        'id SERIAL PRIMARY KEY',
        'title VARCHAR(255) NOT NULL',
        'original_title VARCHAR(255)',
        'slug VARCHAR(255) NOT NULL UNIQUE',
        'description TEXT',
        'poster VARCHAR(255)',
        'backdrop VARCHAR(255)',
        'trailer VARCHAR(255)',
        'type VARCHAR(20) NOT NULL DEFAULT \'movie\'', // movie, series, anime
        'status VARCHAR(20) NOT NULL DEFAULT \'published\'', // published, coming_soon, hidden
        'is_featured BOOLEAN NOT NULL DEFAULT FALSE',
        'is_vip BOOLEAN NOT NULL DEFAULT FALSE',
        'release_date DATE',
        'release_year INT',
        'country_id INT REFERENCES countries(id) ON DELETE SET NULL',
        'duration INT', // Thời lượng (phút)
        'total_episodes INT', // Tổng số tập (đối với phim bộ)
        'quality VARCHAR(20)', // 480p, 720p, 1080p, 4K
        'language VARCHAR(50)',
        'director VARCHAR(100)',
        'imdb_rating DECIMAL(3,1)',
        'views INT NOT NULL DEFAULT 0',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng episodes
    $db->createTable('episodes', [
        'id SERIAL PRIMARY KEY',
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'title VARCHAR(255)',
        'description TEXT',
        'thumbnail VARCHAR(255)',
        'duration INT', // Thời lượng (giây)
        'season_number INT NOT NULL DEFAULT 1',
        'episode_number INT NOT NULL',
        'status VARCHAR(20) NOT NULL DEFAULT \'published\'',
        'views INT NOT NULL DEFAULT 0',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'UNIQUE (movie_id, season_number, episode_number)'
    ]);
    
    // Tạo bảng servers
    $db->createTable('servers', [
        'id SERIAL PRIMARY KEY',
        'episode_id INT REFERENCES episodes(id) ON DELETE CASCADE',
        'name VARCHAR(100) NOT NULL',
        'url TEXT NOT NULL',
        'quality VARCHAR(20)',
        'is_default BOOLEAN NOT NULL DEFAULT FALSE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng subtitles
    $db->createTable('subtitles', [
        'id SERIAL PRIMARY KEY',
        'episode_id INT NOT NULL REFERENCES episodes(id) ON DELETE CASCADE',
        'language VARCHAR(50) NOT NULL',
        'url TEXT NOT NULL',
        'is_default BOOLEAN NOT NULL DEFAULT FALSE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng movie_categories
    $db->createTable('movie_categories', [
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE',
        'PRIMARY KEY (movie_id, category_id)'
    ]);
    
    // Tạo bảng movie_actors
    $db->createTable('movie_actors', [
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'actor_id INT NOT NULL REFERENCES actors(id) ON DELETE CASCADE',
        'PRIMARY KEY (movie_id, actor_id)'
    ]);
    
    // Tạo bảng favorites
    $db->createTable('favorites', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'UNIQUE (user_id, movie_id)'
    ]);
    
    // Tạo bảng watch_history
    $db->createTable('watch_history', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'episode_id INT REFERENCES episodes(id) ON DELETE CASCADE',
        'current_time INT NOT NULL DEFAULT 0',
        'duration INT NOT NULL DEFAULT 0',
        'completed BOOLEAN NOT NULL DEFAULT FALSE',
        'last_watched TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'UNIQUE (user_id, episode_id)'
    ]);
    
    // Tạo bảng comments
    $db->createTable('comments', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE',
        'episode_id INT REFERENCES episodes(id) ON DELETE CASCADE',
        'parent_id INT REFERENCES comments(id) ON DELETE CASCADE',
        'content TEXT NOT NULL',
        'likes INT NOT NULL DEFAULT 0',
        'status VARCHAR(20) NOT NULL DEFAULT \'approved\'',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng comment_likes
    $db->createTable('comment_likes', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'comment_id INT NOT NULL REFERENCES comments(id) ON DELETE CASCADE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'UNIQUE (user_id, comment_id)'
    ]);
    
    // Tạo bảng reports
    $db->createTable('reports', [
        'id SERIAL PRIMARY KEY',
        'user_id INT REFERENCES users(id) ON DELETE SET NULL',
        'movie_id INT REFERENCES movies(id) ON DELETE CASCADE',
        'episode_id INT REFERENCES episodes(id) ON DELETE CASCADE',
        'report_type VARCHAR(50) NOT NULL',
        'details TEXT',
        'status VARCHAR(20) NOT NULL DEFAULT \'pending\'',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng notifications
    $db->createTable('notifications', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'title VARCHAR(255) NOT NULL',
        'content TEXT NOT NULL',
        'link VARCHAR(255)',
        'read BOOLEAN NOT NULL DEFAULT FALSE',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng subscriptions
    $db->createTable('subscriptions', [
        'id SERIAL PRIMARY KEY',
        'user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE',
        'plan VARCHAR(20) NOT NULL', // monthly, quarterly, yearly
        'amount DECIMAL(10,2) NOT NULL',
        'payment_method VARCHAR(20) NOT NULL', // vnpay, momo, stripe
        'payment_id VARCHAR(100)',
        'status VARCHAR(20) NOT NULL DEFAULT \'pending\'', // pending, completed, failed, refunded
        'starts_at TIMESTAMP',
        'expires_at TIMESTAMP',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Tạo index để tối ưu truy vấn
    $db->createIndex('movies', 'idx_movies_slug', 'slug');
    $db->createIndex('movies', 'idx_movies_type_status', ['type', 'status']);
    $db->createIndex('movies', 'idx_movies_country', 'country_id');
    $db->createIndex('episodes', 'idx_episodes_movie', 'movie_id');
    $db->createIndex('watch_history', 'idx_history_user', 'user_id');
    $db->createIndex('comments', 'idx_comments_movie', 'movie_id');
    $db->createIndex('comments', 'idx_comments_episode', 'episode_id');
    $db->createIndex('comments', 'idx_comments_parent', 'parent_id');
}

// Hàm tạo schema cho MySQL
function create_mysql_schema($db) {
    // Tạo bảng users
    $db->createTable('users', [
        'id INT AUTO_INCREMENT PRIMARY KEY',
        'username VARCHAR(50) NOT NULL UNIQUE',
        'email VARCHAR(100) NOT NULL UNIQUE',
        'password VARCHAR(255) NOT NULL',
        'fullname VARCHAR(100)',
        'avatar VARCHAR(255)',
        'phone VARCHAR(20)',
        'is_admin TINYINT(1) NOT NULL DEFAULT 0',
        'is_vip TINYINT(1) NOT NULL DEFAULT 0',
        'vip_expires_at DATETIME',
        'remember_token VARCHAR(100)',
        'reset_token VARCHAR(100)',
        'reset_token_expires_at DATETIME',
        'last_login DATETIME',
        'status VARCHAR(20) NOT NULL DEFAULT \'active\'',
        'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng countries
    $db->createTable('countries', [
        'id INT AUTO_INCREMENT PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng categories
    $db->createTable('categories', [
        'id INT AUTO_INCREMENT PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'description TEXT',
        'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng actors
    $db->createTable('actors', [
        'id INT AUTO_INCREMENT PRIMARY KEY',
        'name VARCHAR(100) NOT NULL',
        'slug VARCHAR(100) NOT NULL UNIQUE',
        'bio TEXT',
        'avatar VARCHAR(255)',
        'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]);
    
    // Tạo bảng movies
    $db->createTable('movies', [
        'id INT AUTO_INCREMENT PRIMARY KEY',
        'title VARCHAR(255) NOT NULL',
        'original_title VARCHAR(255)',
        'slug VARCHAR(255) NOT NULL UNIQUE',
        'description TEXT',
        'poster VARCHAR(255)',
        'backdrop VARCHAR(255)',
        'trailer VARCHAR(255)',
        'type VARCHAR(20) NOT NULL DEFAULT \'movie\'',
        'status VARCHAR(20) NOT NULL DEFAULT \'published\'',
        'is_featured TINYINT(1) NOT NULL DEFAULT 0',
        'is_vip TINYINT(1) NOT NULL DEFAULT 0',
        'release_date DATE',
        'release_year INT',
        'country_id INT',
        'duration INT',
        'total_episodes INT',
        'quality VARCHAR(20)',
        'language VARCHAR(50)',
        'director VARCHAR(100)',
        'imdb_rating DECIMAL(3,1)',
        'views INT NOT NULL DEFAULT 0',
        'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL'
    ]);
    
    // Các bảng còn lại tương tự như PostgreSQL
    // ...
}

// Hàm tạo schema cho SQLite
function create_sqlite_schema($db) {
    // Tạo bảng users
    $db->createTable('users', [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'username TEXT NOT NULL UNIQUE',
        'email TEXT NOT NULL UNIQUE',
        'password TEXT NOT NULL',
        'fullname TEXT',
        'avatar TEXT',
        'phone TEXT',
        'is_admin INTEGER NOT NULL DEFAULT 0',
        'is_vip INTEGER NOT NULL DEFAULT 0',
        'vip_expires_at TIMESTAMP',
        'remember_token TEXT',
        'reset_token TEXT',
        'reset_token_expires_at TIMESTAMP',
        'last_login TIMESTAMP',
        'status TEXT NOT NULL DEFAULT \'active\'',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
    
    // Các bảng còn lại tương tự như PostgreSQL
    // ...
}

// Hàm thêm dữ liệu mẫu
function add_sample_data($db) {
    // Thêm các quốc gia
    $countries = [
        ['name' => 'Việt Nam', 'slug' => 'viet-nam'],
        ['name' => 'Mỹ', 'slug' => 'my'],
        ['name' => 'Hàn Quốc', 'slug' => 'han-quoc'],
        ['name' => 'Trung Quốc', 'slug' => 'trung-quoc'],
        ['name' => 'Nhật Bản', 'slug' => 'nhat-ban'],
        ['name' => 'Thái Lan', 'slug' => 'thai-lan'],
        ['name' => 'Pháp', 'slug' => 'phap'],
        ['name' => 'Anh', 'slug' => 'anh'],
        ['name' => 'Đài Loan', 'slug' => 'dai-loan'],
        ['name' => 'Hồng Kông', 'slug' => 'hong-kong'],
        ['name' => 'Ấn Độ', 'slug' => 'an-do']
    ];
    
    foreach ($countries as $country) {
        $country['created_at'] = date('Y-m-d H:i:s');
        $country['updated_at'] = date('Y-m-d H:i:s');
        $db->insert('countries', $country);
    }
    
    // Thêm các thể loại
    $categories = [
        ['name' => 'Hành Động', 'slug' => 'hanh-dong', 'description' => 'Phim hành động'],
        ['name' => 'Tình Cảm', 'slug' => 'tinh-cam', 'description' => 'Phim tình cảm'],
        ['name' => 'Hài Hước', 'slug' => 'hai-huoc', 'description' => 'Phim hài hước'],
        ['name' => 'Kinh Dị', 'slug' => 'kinh-di', 'description' => 'Phim kinh dị'],
        ['name' => 'Viễn Tưởng', 'slug' => 'vien-tuong', 'description' => 'Phim viễn tưởng'],
        ['name' => 'Hoạt Hình', 'slug' => 'hoat-hinh', 'description' => 'Phim hoạt hình'],
        ['name' => 'Phiêu Lưu', 'slug' => 'phieu-luu', 'description' => 'Phim phiêu lưu'],
        ['name' => 'Tâm Lý', 'slug' => 'tam-ly', 'description' => 'Phim tâm lý'],
        ['name' => 'Chiến Tranh', 'slug' => 'chien-tranh', 'description' => 'Phim chiến tranh'],
        ['name' => 'Hình Sự', 'slug' => 'hinh-su', 'description' => 'Phim hình sự'],
        ['name' => 'Võ Thuật', 'slug' => 'vo-thuat', 'description' => 'Phim võ thuật'],
        ['name' => 'Cổ Trang', 'slug' => 'co-trang', 'description' => 'Phim cổ trang'],
        ['name' => 'Thần Thoại', 'slug' => 'than-thoai', 'description' => 'Phim thần thoại'],
        ['name' => 'TV Show', 'slug' => 'tv-show', 'description' => 'TV Show'],
        ['name' => 'Anime', 'slug' => 'anime', 'description' => 'Phim Anime Nhật Bản']
    ];
    
    foreach ($categories as $category) {
        $category['created_at'] = date('Y-m-d H:i:s');
        $category['updated_at'] = date('Y-m-d H:i:s');
        $db->insert('categories', $category);
    }
    
    // Có thể thêm các dữ liệu khác như phim, tập phim, diễn viên, ...
}