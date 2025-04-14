<?php
// Tạo cơ sở dữ liệu PostgreSQL cho "Lọc Phim"
define('SECURE_ACCESS', true);
require_once 'config.php';

// Kết nối đến cơ sở dữ liệu
$conn = db_connect();

// 1. Tạo bảng users (người dùng)
$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'inactive',
    activation_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT NULL
);
";

// 2. Tạo bảng categories (thể loại)
$sql_categories = "
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// 3. Tạo bảng anime (phim/anime)
$sql_anime = "
CREATE TABLE IF NOT EXISTS anime (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    alt_title VARCHAR(255) DEFAULT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    banner VARCHAR(255) DEFAULT NULL,
    category_id INTEGER REFERENCES categories(id),
    release_year INTEGER DEFAULT NULL,
    release_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'ongoing',
    episode_count INTEGER DEFAULT 0,
    rating NUMERIC(3,1) DEFAULT 0.0,
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// 4. Tạo bảng episodes (tập phim)
$sql_episodes = "
CREATE TABLE IF NOT EXISTS episodes (
    id SERIAL PRIMARY KEY,
    anime_id INTEGER NOT NULL REFERENCES anime(id) ON DELETE CASCADE,
    title VARCHAR(255) DEFAULT NULL,
    episode_number INTEGER NOT NULL,
    duration INTEGER DEFAULT 0,
    thumbnail VARCHAR(255) DEFAULT NULL,
    video_url VARCHAR(255) NOT NULL,
    video_720p VARCHAR(255) DEFAULT NULL,
    video_1080p VARCHAR(255) DEFAULT NULL,
    video_4k VARCHAR(255) DEFAULT NULL,
    max_resolution VARCHAR(10) DEFAULT '480p',
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(anime_id, episode_number)
);
";

// 5. Tạo bảng comments (bình luận)
$sql_comments = "
CREATE TABLE IF NOT EXISTS comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    anime_id INTEGER NOT NULL REFERENCES anime(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    parent_id INTEGER DEFAULT NULL REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT NULL
);
";

// 6. Tạo bảng ratings (đánh giá)
$sql_ratings = "
CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    anime_id INTEGER NOT NULL REFERENCES anime(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, anime_id)
);
";

// 7. Tạo bảng favorites (yêu thích)
$sql_favorites = "
CREATE TABLE IF NOT EXISTS favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    anime_id INTEGER NOT NULL REFERENCES anime(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, anime_id)
);
";

// 8. Tạo bảng watch_history (lịch sử xem) - Đã sửa current_time thành playback_time vì đó là từ khóa dành riêng trong PostgreSQL
$sql_watch_history = "
CREATE TABLE IF NOT EXISTS watch_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    anime_id INTEGER NOT NULL,
    episode_id INTEGER NOT NULL,
    playback_time NUMERIC(10,2) DEFAULT 0,
    watched_percent INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, anime_id, episode_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);
";

// 9. Tạo bảng vip_members (thành viên VIP)
$sql_vip_members = "
CREATE TABLE IF NOT EXISTS vip_members (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    level INTEGER NOT NULL DEFAULT 1,
    start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expire_date TIMESTAMP NOT NULL,
    payment_id VARCHAR(255) DEFAULT NULL,
    amount NUMERIC(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'active',
    ads BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// 10. Tạo bảng auth_tokens (token xác thực)
$sql_auth_tokens = "
CREATE TABLE IF NOT EXISTS auth_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    selector VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// 11. Tạo bảng password_resets (đặt lại mật khẩu)
$sql_password_resets = "
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL,
    expires TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// 12. Tạo bảng notifications (thông báo)
$sql_notifications = "
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    type VARCHAR(50) DEFAULT 'system',
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
";

// Tạo các bảng dữ liệu
$tables = [
    'users' => $sql_users,
    'categories' => $sql_categories,
    'anime' => $sql_anime,
    'episodes' => $sql_episodes,
    'comments' => $sql_comments,
    'ratings' => $sql_ratings,
    'favorites' => $sql_favorites,
    'watch_history' => $sql_watch_history,
    'vip_members' => $sql_vip_members,
    'auth_tokens' => $sql_auth_tokens,
    'password_resets' => $sql_password_resets,
    'notifications' => $sql_notifications
];

// Thực thi các lệnh SQL để tạo bảng
$success = true;
$errors = [];

foreach ($tables as $table_name => $sql) {
    if (get_config('db.type') === 'postgresql') {
        $result = pg_query($conn, $sql);
        if (!$result) {
            $success = false;
            $errors[] = "Lỗi khi tạo bảng $table_name: " . pg_last_error($conn);
        }
    } else {
        if (!$conn->query($sql)) {
            $success = false;
            $errors[] = "Lỗi khi tạo bảng $table_name: " . $conn->error;
        }
    }
}

// Tạo dữ liệu mẫu - admin user
$admin_username = 'admin';
$admin_email = 'admin@locphim.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);

$sql_admin = "
INSERT INTO users (username, email, password, role, status, created_at) 
VALUES ('$admin_username', '$admin_email', '$admin_password', 'admin', 'active', NOW())
ON CONFLICT (username) DO NOTHING;
";

// Tạo dữ liệu mẫu - thể loại
$categories = [
    ['Hành động', 'hanh-dong', 'Anime thể loại Hành động'],
    ['Hài hước', 'hai-huoc', 'Anime thể loại Hài hước'],
    ['Lãng mạn', 'lang-man', 'Anime thể loại Lãng mạn'],
    ['Phiêu lưu', 'phieu-luu', 'Anime thể loại Phiêu lưu'],
    ['Kinh dị', 'kinh-di', 'Anime thể loại Kinh dị'],
    ['Giả tưởng', 'gia-tuong', 'Anime thể loại Giả tưởng'],
    ['Thể thao', 'the-thao', 'Anime thể loại Thể thao'],
    ['Học đường', 'hoc-duong', 'Anime thể loại Học đường'],
    ['Đời thường', 'doi-thuong', 'Anime thể loại Đời thường'],
    ['Viễn tưởng', 'vien-tuong', 'Anime thể loại Viễn tưởng']
];

// Thêm admin user
if (get_config('db.type') === 'postgresql') {
    $result = pg_query($conn, $sql_admin);
    if (!$result) {
        $errors[] = "Lỗi khi tạo admin user: " . pg_last_error($conn);
    }
} else {
    if (!$conn->query($sql_admin)) {
        $errors[] = "Lỗi khi tạo admin user: " . $conn->error;
    }
}

// Thêm thể loại
foreach ($categories as $category) {
    $name = $category[0];
    $slug = $category[1];
    $description = $category[2];
    
    $sql_category = "
    INSERT INTO categories (name, slug, description, created_at)
    VALUES ('$name', '$slug', '$description', NOW())
    ON CONFLICT (slug) DO NOTHING;
    ";
    
    if (get_config('db.type') === 'postgresql') {
        $result = pg_query($conn, $sql_category);
        if (!$result) {
            $errors[] = "Lỗi khi tạo thể loại $name: " . pg_last_error($conn);
        }
    } else {
        try {
            $conn->query($sql_category);
        } catch (Exception $e) {
            $errors[] = "Lỗi khi tạo thể loại $name: " . $e->getMessage();
        }
    }
}

// Hiển thị kết quả
if ($success && empty($errors)) {
    echo '<h1 style="color: green;">Thiết lập cơ sở dữ liệu thành công!</h1>';
    echo '<p>Đã tạo các bảng dữ liệu cần thiết cho trang web Lọc Phim.</p>';
    echo '<p>Tài khoản admin mặc định:</p>';
    echo '<ul>';
    echo '<li>Tên đăng nhập: admin</li>';
    echo '<li>Mật khẩu: admin123</li>';
    echo '</ul>';
    echo '<p><a href="index.php">Quay lại trang chủ</a></p>';
} else {
    echo '<h1 style="color: red;">Thiết lập cơ sở dữ liệu thất bại!</h1>';
    echo '<p>Đã xảy ra lỗi khi thiết lập cơ sở dữ liệu:</p>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . $error . '</li>';
    }
    echo '</ul>';
    echo '<p><a href="setup-database.php">Thử lại</a></p>';
}
?>