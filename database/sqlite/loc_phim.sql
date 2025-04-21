-- Tạo bảng người dùng
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INTEGER NOT NULL DEFAULT 3,
    avatar VARCHAR(255),
    phone VARCHAR(20),
    full_name VARCHAR(100),
    birthday DATE,
    gender VARCHAR(10),
    address TEXT,
    status TINYINT NOT NULL DEFAULT 1,
    remember_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expire DATETIME,
    vip_expiry DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng role
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng thể loại
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INTEGER DEFAULT NULL,
    status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tạo bảng phim
CREATE TABLE IF NOT EXISTS movies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    release_year INTEGER,
    duration VARCHAR(20),
    quality VARCHAR(20) DEFAULT '720p',
    thumbnail VARCHAR(255),
    poster VARCHAR(255),
    trailer VARCHAR(255),
    country VARCHAR(100),
    language VARCHAR(100),
    director VARCHAR(100),
    actors TEXT,
    views INTEGER DEFAULT 0,
    rating FLOAT DEFAULT 0,
    rating_count INTEGER DEFAULT 0,
    type VARCHAR(20) NOT NULL DEFAULT 'movie',
    episodes_count INTEGER DEFAULT 1,
    status TINYINT NOT NULL DEFAULT 1,
    featured TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng phim_thể loại
CREATE TABLE IF NOT EXISTS movie_categories (
    movie_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY (movie_id, category_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tạo bảng tập phim
CREATE TABLE IF NOT EXISTS episodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movie_id INTEGER NOT NULL,
    episode_number INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INTEGER DEFAULT 0,
    thumbnail VARCHAR(255),
    source_360 TEXT,
    source_480 TEXT,
    source_720 TEXT,
    source_1080 TEXT,
    source_4k TEXT,
    source_360_type TEXT CHECK (source_360_type IN ('direct', 'google_drive', 'iframe', 'youtube')) DEFAULT 'direct',
    source_480_type TEXT CHECK (source_480_type IN ('direct', 'google_drive', 'iframe', 'youtube')) DEFAULT 'direct',
    source_720_type TEXT CHECK (source_720_type IN ('direct', 'google_drive', 'iframe', 'youtube')) DEFAULT 'direct',
    source_1080_type TEXT CHECK (source_1080_type IN ('direct', 'google_drive', 'iframe', 'youtube')) DEFAULT 'direct',
    source_4k_type TEXT CHECK (source_4k_type IN ('direct', 'google_drive', 'iframe', 'youtube')) DEFAULT 'direct',
    views INTEGER DEFAULT 0,
    status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Tạo bảng bình luận
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    episode_id INTEGER,
    content TEXT NOT NULL,
    parent_id INTEGER DEFAULT NULL,
    status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- Tạo bảng đánh giá
CREATE TABLE IF NOT EXISTS ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Tạo bảng lịch sử xem phim
CREATE TABLE IF NOT EXISTS watch_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    episode_id INTEGER NOT NULL,
    progress INTEGER DEFAULT 0,
    watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Tạo bảng danh sách yêu thích
CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Tạo bảng phụ đề
CREATE TABLE IF NOT EXISTS subtitles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    episode_id INTEGER NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    language_name VARCHAR(50) NOT NULL,
    subtitle_file VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Tạo bảng âm thanh
CREATE TABLE IF NOT EXISTS audio_tracks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    episode_id INTEGER NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    language_name VARCHAR(50) NOT NULL,
    audio_url VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Tạo bảng thanh toán
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    start_date DATETIME,
    end_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

-- Tạo bảng gói VIP
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INTEGER NOT NULL, -- Số ngày
    features TEXT,
    status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng thông báo
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'general',
    link VARCHAR(255),
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tạo bảng cài đặt người dùng
CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    subtitle_language VARCHAR(10) DEFAULT 'vi',
    audio_language VARCHAR(10) DEFAULT 'vi',
    theme_preference VARCHAR(10) DEFAULT 'light',
    email_notifications TINYINT DEFAULT 1,
    browser_notifications TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Chèn dữ liệu mẫu cho roles
INSERT INTO roles (id, name, description) 
VALUES 
(1, 'admin', 'Quản trị viên'),
(2, 'vip', 'Thành viên VIP'),
(3, 'user', 'Thành viên thường');

-- Chèn dữ liệu mẫu cho admin
INSERT INTO users (username, email, password, role_id, status)
VALUES ('admin', 'admin@locphim.com', '$2y$10$8KMJVXo1GnAx3i.4MB5t/.Wj00MqG6cDZvmVpyuI1yF/s4YI.1Fx.', 1, 1);

-- Chèn dữ liệu mẫu cho thể loại
INSERT INTO categories (name, slug, description)
VALUES 
('Hành Động', 'hanh-dong', 'Phim thuộc thể loại hành động'),
('Tình Cảm', 'tinh-cam', 'Phim thuộc thể loại tình cảm'),
('Hài Hước', 'hai-huoc', 'Phim thuộc thể loại hài hước'),
('Kinh Dị', 'kinh-di', 'Phim thuộc thể loại kinh dị'),
('Viễn Tưởng', 'vien-tuong', 'Phim thuộc thể loại viễn tưởng'),
('Hoạt Hình', 'hoat-hinh', 'Phim thuộc thể loại hoạt hình'),
('Anime', 'anime', 'Phim thuộc thể loại anime Nhật Bản');

-- Chèn dữ liệu mẫu cho phim
INSERT INTO movies (title, original_title, slug, description, release_year, duration, quality, thumbnail, type, episodes_count, status)
VALUES 
('One Piece', 'One Piece', 'one-piece', 'One Piece là câu chuyện về Monkey D. Luffy, một cậu bé có ước mơ trở thành Vua Hải Tặc và tìm được kho báu vĩ đại nhất thế giới mang tên "One Piece".', 1999, '24 phút/tập', '1080p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 1000, 1),
('Naruto Shippuden', 'Naruto Shippuden', 'naruto-shippuden', 'Naruto Shippuden là một bộ anime nổi tiếng, kể về hành trình của Naruto Uzumaki sau 2 năm rèn luyện.', 2007, '23 phút/tập', '720p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 500, 1),
('Attack on Titan', 'Shingeki no Kyojin', 'attack-on-titan', 'Attack on Titan là một bộ anime nổi tiếng về cuộc chiến sinh tồn của loài người trước các Titan.', 2013, '24 phút/tập', '1080p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 75, 1);

-- Chèn dữ liệu mẫu cho phim_thể loại
INSERT INTO movie_categories (movie_id, category_id)
VALUES 
(1, 1), (1, 6), (1, 7),
(2, 1), (2, 6), (2, 7),
(3, 1), (3, 4), (3, 7);

-- Chèn dữ liệu mẫu cho tập phim
INSERT INTO episodes (movie_id, episode_number, title, description, duration, thumbnail)
VALUES 
(1, 1, 'Ta là Luffy! Người sẽ trở thành Vua Hải Tặc!', 'Tập đầu tiên của series One Piece.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(1, 2, 'Xuất hiện! Thợ săn hải tặc Zoro!', 'Luffy gặp Zoro - kiếm sĩ tài ba nhưng đang bị bắt giữ.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(2, 1, 'Trở về', 'Naruto trở về làng Lá sau 2 năm tu luyện.', 23, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(3, 1, 'Với bạn sau 2000 năm', 'Tập đầu tiên của Attack on Titan.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f');

-- Chèn dữ liệu mẫu cho gói VIP
INSERT INTO plans (name, description, price, duration, features)
VALUES 
('VIP Tháng', 'Gói VIP hàng tháng với đầy đủ tính năng', 50000, 30, 'Xem phim chất lượng cao|Không quảng cáo|Tải phim về máy'),
('VIP Năm', 'Gói VIP hàng năm với đầy đủ tính năng và giá ưu đãi', 500000, 365, 'Xem phim chất lượng cao|Không quảng cáo|Tải phim về máy|Xem sớm phim mới');