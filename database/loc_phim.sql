-- Lọc Phim - Cơ sở dữ liệu
-- Compatibles with PostgreSQL, MySQL/MariaDB

-- Xóa các bảng nếu tồn tại
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS video_ads;
DROP TABLE IF EXISTS advertisements;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS watch_history;
DROP TABLE IF EXISTS movie_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS subtitles;
DROP TABLE IF EXISTS video_sources;
DROP TABLE IF EXISTS episodes;
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;

-- Tạo bảng users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    avatar VARCHAR(255) DEFAULT '/assets/images/avatars/default.png',
    is_admin BOOLEAN DEFAULT FALSE,
    is_vip BOOLEAN DEFAULT FALSE,
    vip_expired_at TIMESTAMP,
    total_minutes_watched INTEGER DEFAULT 0,
    remember_token VARCHAR(255),
    reset_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng categories
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng movies
CREATE TABLE movies (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    poster VARCHAR(255),
    banner VARCHAR(255),
    trailer_url VARCHAR(255),
    release_year INTEGER,
    duration INTEGER,
    country VARCHAR(100),
    status VARCHAR(50) DEFAULT 'ongoing',
    is_featured BOOLEAN DEFAULT FALSE,
    is_vip BOOLEAN DEFAULT FALSE,
    is_anime BOOLEAN DEFAULT FALSE,
    views INTEGER DEFAULT 0,
    rating FLOAT DEFAULT 0,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng episodes
CREATE TABLE episodes (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    episode_number INTEGER NOT NULL,
    title VARCHAR(255),
    description TEXT,
    duration INTEGER,
    thumbnail VARCHAR(255),
    views INTEGER DEFAULT 0,
    is_vip BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(movie_id, episode_number)
);

-- Tạo bảng video_sources
CREATE TABLE video_sources (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    quality VARCHAR(20),
    source_type VARCHAR(50),
    source_url TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng subtitles
CREATE TABLE subtitles (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    language VARCHAR(20) NOT NULL,
    subtitle_url TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng tags
CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng movie_tags
CREATE TABLE movie_tags (
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    tag_id INTEGER REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (movie_id, tag_id)
);

-- Tạo bảng watch_history
CREATE TABLE watch_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    watched_time INTEGER DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, episode_id)
);

-- Tạo bảng favorites
CREATE TABLE favorites (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, movie_id)
);

-- Tạo bảng ratings
CREATE TABLE ratings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, movie_id)
);

-- Tạo bảng comments
CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE SET NULL,
    parent_id INTEGER REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng payments
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    payment_code VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(15, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    vip_plan VARCHAR(20) NOT NULL,
    transaction_id VARCHAR(255),
    transaction_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng advertisements
CREATE TABLE advertisements (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    ad_type VARCHAR(50) NOT NULL,
    position VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng video_ads
CREATE TABLE video_ads (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    ad_url TEXT NOT NULL,
    ad_type VARCHAR(50) NOT NULL,
    skip_after INTEGER DEFAULT 5,
    duration INTEGER,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng notifications
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng settings
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chèn dữ liệu mẫu
-- Admin user
INSERT INTO users (username, email, password, is_admin, full_name, created_at, updated_at)
VALUES ('admin', 'admin@locphim.vn', '$2y$10$7rLSvRVyTQORapkDOqmkhetjF6H9lJHngr4hJMSM2lHObJbW5EQh6', TRUE, 'Quản trị viên', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- Thể loại
INSERT INTO categories (name, slug, description, created_at, updated_at)
VALUES 
('Phim Lẻ', 'phim-le', 'Tất cả các bộ phim lẻ', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Phim Bộ', 'phim-bo', 'Tất cả các bộ phim nhiều tập', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Anime', 'anime', 'Tất cả các bộ anime', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Phim Chiếu Rạp', 'phim-chieu-rap', 'Phim đang chiếu tại rạp', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- Tags
INSERT INTO tags (name, slug, created_at, updated_at)
VALUES 
('Hành Động', 'hanh-dong', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Tình Cảm', 'tinh-cam', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Hài Hước', 'hai-huoc', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Kinh Dị', 'kinh-di', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Viễn Tưởng', 'vien-tuong', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Hoạt Hình', 'hoat-hinh', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Võ Thuật', 'vo-thuat', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Phiêu Lưu', 'phieu-luu', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Tâm Lý', 'tam-ly', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Khoa Học', 'khoa-hoc', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- Settings
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
VALUES
('site_title', 'Lọc Phim - Xem phim và anime trực tuyến', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('site_description', 'Nền tảng xem phim và anime trực tuyến cung cấp những nội dung chất lượng cao', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('site_keywords', 'phim, anime, xem phim, phim hay, phim mới, anime hot', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('site_logo', '/assets/images/logo.png', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('site_favicon', '/assets/images/favicon.ico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('footer_text', '© 2023 Lọc Phim. Tất cả quyền được bảo lưu.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('player_watermark', 'Lọc Phim', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('maintenance_mode', '0', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('default_theme', 'light', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('allow_registration', '1', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('email_verification', '0', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('social_login', '0', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('facebook_url', '', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('twitter_url', '', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('youtube_url', '', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('instagram_url', '', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);