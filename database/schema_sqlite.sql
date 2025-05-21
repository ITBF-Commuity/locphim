--
-- Lọc Phim - SQLite Database Schema
-- Version 1.0.0
--

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    phone TEXT UNIQUE,
    password TEXT NOT NULL,
    avatar TEXT,
    is_admin INTEGER DEFAULT 0,
    is_vip INTEGER DEFAULT 0,
    vip_expires_at TIMESTAMP,
    verification_token TEXT,
    is_verified INTEGER DEFAULT 0,
    reset_token TEXT,
    reset_token_expires_at TIMESTAMP,
    remember_token TEXT,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table (thể loại)
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Countries table (quốc gia)
CREATE TABLE countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    code TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table (phim)
CREATE TABLE movies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    original_title TEXT,
    description TEXT,
    poster TEXT,
    backdrop TEXT,
    trailer TEXT,
    type TEXT NOT NULL, -- 'movie', 'series', 'anime'
    status TEXT NOT NULL, -- 'ongoing', 'completed'
    release_year INTEGER,
    country_id INTEGER,
    duration INTEGER, -- in minutes
    quality TEXT, -- '4K', '1080p', etc.
    age_rating TEXT, -- 'G', 'PG', 'PG-13', 'R', etc.
    is_featured INTEGER DEFAULT 0,
    is_premium INTEGER DEFAULT 0, -- Only for VIP members
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL
);

-- Movie-Category relationship
CREATE TABLE movie_categories (
    movie_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY (movie_id, category_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Episodes table (tập phim)
CREATE TABLE episodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movie_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    episode_number INTEGER NOT NULL,
    season_number INTEGER DEFAULT 1,
    description TEXT,
    thumbnail TEXT,
    duration INTEGER, -- in seconds
    video_url TEXT, -- main video source
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, season_number, episode_number),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Video sources table (các nguồn video khác nhau cho mỗi tập)
CREATE TABLE video_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    episode_id INTEGER NOT NULL,
    quality TEXT NOT NULL, -- '360p', '480p', '720p', '1080p', '4K'
    source_url TEXT NOT NULL,
    source_type TEXT NOT NULL, -- 'direct', 'embed', 'gdrive', 'm3u8'
    is_default INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Subtitles table (phụ đề)
CREATE TABLE subtitles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    episode_id INTEGER NOT NULL,
    language TEXT NOT NULL,
    language_code TEXT NOT NULL,
    file_url TEXT NOT NULL,
    is_default INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Favorites table (phim yêu thích)
CREATE TABLE favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Watch history table (lịch sử xem)
CREATE TABLE watch_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    episode_id INTEGER NOT NULL,
    watched_seconds INTEGER DEFAULT 0,
    completed INTEGER DEFAULT 0,
    last_watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, episode_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Playlists table (danh sách phát)
CREATE TABLE playlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_public INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Playlist-Movie relationship
CREATE TABLE playlist_movies (
    playlist_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    position INTEGER DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, movie_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Comments table (bình luận)
CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER,
    episode_id INTEGER,
    parent_id INTEGER,
    content TEXT NOT NULL,
    likes INTEGER DEFAULT 0,
    dislikes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    CHECK (movie_id IS NOT NULL OR episode_id IS NOT NULL)
);

-- Ratings table (đánh giá)
CREATE TABLE ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 10),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Reports table (báo cáo lỗi)
CREATE TABLE reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    movie_id INTEGER,
    episode_id INTEGER,
    type TEXT NOT NULL, -- 'video_broken', 'subtitle_issue', 'wrong_content', etc.
    content TEXT NOT NULL,
    status TEXT DEFAULT 'pending', -- 'pending', 'resolved', 'rejected'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE SET NULL
);

-- VIP packages table (gói VIP)
CREATE TABLE vip_packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    duration INTEGER NOT NULL, -- in days
    features TEXT, -- JSON or comma-separated list of features
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table (giao dịch)
CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    package_id INTEGER,
    amount REAL NOT NULL,
    payment_method TEXT NOT NULL, -- 'momo', 'vnpay', 'stripe', etc.
    transaction_code TEXT UNIQUE,
    status TEXT DEFAULT 'pending', -- 'pending', 'completed', 'failed', 'refunded'
    paid_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES vip_packages(id) ON DELETE SET NULL
);

-- Notifications table (thông báo)
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    type TEXT NOT NULL, -- 'system', 'new_episode', 'account', etc.
    is_read INTEGER DEFAULT 0,
    link TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ads table (quảng cáo)
CREATE TABLE ads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'banner', 'video', 'popup'
    location TEXT NOT NULL, -- 'header', 'sidebar', 'player_before', 'player_during', 'player_after'
    content TEXT NOT NULL, -- HTML or embed code or URL
    starts_at TIMESTAMP,
    ends_at TIMESTAMP,
    is_active INTEGER DEFAULT 1,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table (cài đặt hệ thống)
CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    group_name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'text', 'number', 'boolean', 'array', 'json'
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

-- Create admin user (password is "password")
INSERT INTO users (username, email, phone, password, is_admin, is_verified) VALUES
('admin', 'admin@locphim.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

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