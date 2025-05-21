--
-- Lọc Phim - PostgreSQL Database Schema
-- Version 1.0.0
--

-- Disable foreign key checks temporarily for easier data loading
-- SET session_replication_role = 'replica';

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    is_vip BOOLEAN DEFAULT FALSE,
    vip_expires_at TIMESTAMP,
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255),
    reset_token_expires_at TIMESTAMP,
    remember_token VARCHAR(255),
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table (thể loại)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Countries table (quốc gia)
CREATE TABLE countries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    code CHAR(2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table (phim)
CREATE TABLE movies (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    original_title VARCHAR(255),
    description TEXT,
    poster VARCHAR(255),
    backdrop VARCHAR(255),
    trailer VARCHAR(255),
    type VARCHAR(20) NOT NULL, -- 'movie', 'series', 'anime'
    status VARCHAR(20) NOT NULL, -- 'ongoing', 'completed'
    release_year SMALLINT,
    country_id INTEGER REFERENCES countries(id),
    duration INTEGER, -- in minutes
    quality VARCHAR(20), -- '4K', '1080p', etc.
    age_rating VARCHAR(10), -- 'G', 'PG', 'PG-13', 'R', etc.
    is_featured BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE, -- Only for VIP members
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movie-Category relationship
CREATE TABLE movie_categories (
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (movie_id, category_id)
);

-- Episodes table (tập phim)
CREATE TABLE episodes (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    episode_number INTEGER NOT NULL,
    season_number INTEGER DEFAULT 1,
    description TEXT,
    thumbnail VARCHAR(255),
    duration INTEGER, -- in seconds
    video_url VARCHAR(255), -- main video source
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, season_number, episode_number)
);

-- Video sources table (các nguồn video khác nhau cho mỗi tập)
CREATE TABLE video_sources (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    quality VARCHAR(20) NOT NULL, -- '360p', '480p', '720p', '1080p', '4K'
    source_url VARCHAR(255) NOT NULL,
    source_type VARCHAR(20) NOT NULL, -- 'direct', 'embed', 'gdrive', 'm3u8'
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subtitles table (phụ đề)
CREATE TABLE subtitles (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    language VARCHAR(50) NOT NULL,
    language_code CHAR(2) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Favorites table (phim yêu thích)
CREATE TABLE favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id)
);

-- Watch history table (lịch sử xem)
CREATE TABLE watch_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    watched_seconds INTEGER DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    last_watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, episode_id)
);

-- Playlists table (danh sách phát)
CREATE TABLE playlists (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Playlist-Movie relationship
CREATE TABLE playlist_movies (
    playlist_id INTEGER NOT NULL REFERENCES playlists(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    position INTEGER DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, movie_id)
);

-- Comments table (bình luận)
CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER REFERENCES movies(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    parent_id INTEGER REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    likes INTEGER DEFAULT 0,
    dislikes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (movie_id IS NOT NULL OR episode_id IS NOT NULL)
);

-- Ratings table (đánh giá)
CREATE TABLE ratings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 10),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id)
);

-- Reports table (báo cáo lỗi)
CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    movie_id INTEGER REFERENCES movies(id) ON DELETE SET NULL,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE SET NULL,
    type VARCHAR(50) NOT NULL, -- 'video_broken', 'subtitle_issue', 'wrong_content', etc.
    content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'resolved', 'rejected'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- VIP packages table (gói VIP)
CREATE TABLE vip_packages (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration INTEGER NOT NULL, -- in days
    features TEXT, -- JSON or comma-separated list of features
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table (giao dịch)
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    package_id INTEGER REFERENCES vip_packages(id) ON DELETE SET NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- 'momo', 'vnpay', 'stripe', etc.
    transaction_code VARCHAR(100) UNIQUE,
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'completed', 'failed', 'refunded'
    paid_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table (thông báo)
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'system', 'new_episode', 'account', etc.
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ads table (quảng cáo)
CREATE TABLE ads (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'banner', 'video', 'popup'
    location VARCHAR(50) NOT NULL, -- 'header', 'sidebar', 'player_before', 'player_during', 'player_after'
    content TEXT NOT NULL, -- HTML or embed code or URL
    starts_at TIMESTAMP,
    ends_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table (cài đặt hệ thống)
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    group_name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'text', 'number', 'boolean', 'array', 'json'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (key, value, group_name, type) VALUES
('site_name', 'Lọc Phim', 'general', 'text'),
('site_description', 'Trải nghiệm xem phim thông minh', 'general', 'text'),
('site_keywords', 'phim, phim hay, phim mới, phim chiếu rạp, phim vietsub, phim thuyết minh, anime', 'general', 'text'),
('contact_email', 'contact@locphim.com', 'general', 'text'),
('items_per_page', '24', 'general', 'number'),
('max_recently_viewed', '20', 'general', 'number'),
('enable_registration', 'true', 'users', 'boolean'),
('require_email_verification', 'true', 'users', 'boolean'),
('allow_comments', 'true', 'content', 'boolean'),
('default_player_quality', '720p', 'player', 'text'),
('allow_downloads', 'false', 'player', 'boolean'),
('maintenance_mode', 'false', 'system', 'boolean'),
('social_links', '{"facebook":"https://facebook.com/locphim","twitter":"https://twitter.com/locphim","instagram":"https://instagram.com/locphim"}', 'general', 'json'),
('footer_text', '© 2025 Lọc Phim. Tất cả quyền được bảo lưu.', 'general', 'text');

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES
('Hành Động', 'hanh-dong', 'Phim hành động đặc sắc'),
('Tình Cảm', 'tinh-cam', 'Phim tình cảm lãng mạn'),
('Hài Hước', 'hai-huoc', 'Phim hài hước vui nhộn'),
('Kinh Dị', 'kinh-di', 'Phim kinh dị rùng rợn'),
('Viễn Tưởng', 'vien-tuong', 'Phim viễn tưởng'),
('Hoạt Hình', 'hoat-hinh', 'Phim hoạt hình'),
('Phiêu Lưu', 'phieu-luu', 'Phim phiêu lưu'),
('Tâm Lý', 'tam-ly', 'Phim tâm lý'),
('Võ Thuật', 'vo-thuat', 'Phim võ thuật'),
('Thần Thoại', 'than-thoai', 'Phim thần thoại'),
('Cổ Trang', 'co-trang', 'Phim cổ trang'),
('Chiến Tranh', 'chien-tranh', 'Phim chiến tranh'),
('Hình Sự', 'hinh-su', 'Phim hình sự'),
('Âm Nhạc', 'am-nhac', 'Phim âm nhạc'),
('Gia Đình', 'gia-dinh', 'Phim gia đình'),
('Thể Thao', 'the-thao', 'Phim thể thao'),
('Anime', 'anime', 'Phim hoạt hình Nhật Bản'),
('TV Shows', 'tv-shows', 'Chương trình truyền hình thực tế');

-- Insert default countries
INSERT INTO countries (name, slug, code) VALUES
('Việt Nam', 'viet-nam', 'VN'),
('Mỹ', 'my', 'US'),
('Hàn Quốc', 'han-quoc', 'KR'),
('Nhật Bản', 'nhat-ban', 'JP'),
('Trung Quốc', 'trung-quoc', 'CN'),
('Thái Lan', 'thai-lan', 'TH'),
('Đài Loan', 'dai-loan', 'TW'),
('Hồng Kông', 'hong-kong', 'HK'),
('Ấn Độ', 'an-do', 'IN'),
('Anh', 'anh', 'GB'),
('Pháp', 'phap', 'FR'),
('Đức', 'duc', 'DE'),
('Tây Ban Nha', 'tay-ban-nha', 'ES'),
('Úc', 'uc', 'AU'),
('Canada', 'canada', 'CA');

-- Insert default VIP packages
INSERT INTO vip_packages (name, description, price, duration, features) VALUES
('VIP Tuần', 'Truy cập nội dung VIP trong 7 ngày', 29000, 7, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim'),
('VIP Tháng', 'Truy cập nội dung VIP trong 30 ngày', 79000, 30, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim, Sớm 2 ngày'),
('VIP Năm', 'Truy cập nội dung VIP trong 365 ngày', 599000, 365, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim, Sớm 7 ngày, Hỗ trợ ưu tiên');

-- Create admin user
INSERT INTO users (username, email, phone, password, is_admin, is_verified) VALUES
('admin', 'admin@locphim.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE);

-- Re-enable foreign key checks
-- SET session_replication_role = 'origin';

-- Create indexes for better performance
CREATE INDEX idx_movies_slug ON movies(slug);
CREATE INDEX idx_movies_type ON movies(type);
CREATE INDEX idx_movies_views ON movies(views);
CREATE INDEX idx_episodes_movie_id ON episodes(movie_id);
CREATE INDEX idx_episodes_views ON episodes(views);
CREATE INDEX idx_watch_history_user_id ON watch_history(user_id);
CREATE INDEX idx_favorites_user_id ON favorites(user_id);
CREATE INDEX idx_comments_movie_id ON comments(movie_id);
CREATE INDEX idx_comments_episode_id ON comments(episode_id);