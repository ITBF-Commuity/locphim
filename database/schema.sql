-- Schema cho Lọc Phim
-- Hỗ trợ các loại database: MySQL, PostgreSQL, SQLite, MariaDB

-- Bảng người dùng (users)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    name VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_vip BOOLEAN DEFAULT FALSE,
    vip_expiry TIMESTAMP,
    auth_token VARCHAR(255),
    token_expired_at TIMESTAMP,
    reset_token VARCHAR(255),
    reset_token_expired_at TIMESTAMP,
    activation_token VARCHAR(255),
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng danh mục (categories)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng quốc gia (countries)
CREATE TABLE countries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phim (movies)
CREATE TABLE movies (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    poster VARCHAR(255),
    banner VARCHAR(255),
    trailer VARCHAR(255),
    release_year INTEGER,
    duration INTEGER, -- Tính bằng phút
    rating FLOAT DEFAULT 0,
    views INTEGER DEFAULT 0,
    likes INTEGER DEFAULT 0,
    is_series BOOLEAN DEFAULT FALSE, -- True nếu là phim bộ, False nếu là phim lẻ
    is_anime BOOLEAN DEFAULT FALSE, -- True nếu là anime
    is_featured BOOLEAN DEFAULT FALSE, -- True nếu là phim nổi bật
    is_published BOOLEAN DEFAULT TRUE, -- True nếu phim đã công khai
    is_vip BOOLEAN DEFAULT FALSE, -- True nếu chỉ dành cho VIP
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phim thuộc danh mục (movie_categories)
CREATE TABLE movie_categories (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, category_id)
);

-- Bảng phim thuộc quốc gia (movie_countries)
CREATE TABLE movie_countries (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    country_id INTEGER NOT NULL REFERENCES countries(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, country_id)
);

-- Bảng tập phim (episodes)
CREATE TABLE episodes (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    episode_number INTEGER NOT NULL,
    description TEXT,
    duration INTEGER, -- Tính bằng giây
    thumbnail VARCHAR(255),
    views INTEGER DEFAULT 0,
    likes INTEGER DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    is_vip BOOLEAN DEFAULT FALSE, -- True nếu chỉ dành cho VIP
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, episode_number)
);

-- Bảng nguồn video (video_sources)
CREATE TABLE video_sources (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    source_name VARCHAR(50) NOT NULL, -- VD: default, backup, google_drive
    resolution VARCHAR(20) NOT NULL, -- VD: 360p, 480p, 720p, 1080p, 4k
    url VARCHAR(500) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phụ đề (subtitles)
CREATE TABLE subtitles (
    id SERIAL PRIMARY KEY,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    language VARCHAR(50) NOT NULL, -- VD: vi, en, jp
    label VARCHAR(100) NOT NULL, -- VD: Tiếng Việt, English, 日本語
    url VARCHAR(500) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng diễn viên (actors)
CREATE TABLE actors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    biography TEXT,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phim có diễn viên (movie_actors)
CREATE TABLE movie_actors (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    actor_id INTEGER NOT NULL REFERENCES actors(id) ON DELETE CASCADE,
    character_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, actor_id)
);

-- Bảng đạo diễn (directors)
CREATE TABLE directors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    biography TEXT,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phim có đạo diễn (movie_directors)
CREATE TABLE movie_directors (
    id SERIAL PRIMARY KEY,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    director_id INTEGER NOT NULL REFERENCES directors(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (movie_id, director_id)
);

-- Bảng bình luận (comments)
CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    parent_id INTEGER REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    likes INTEGER DEFAULT 0,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng yêu thích (favorites)
CREATE TABLE favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id)
);

-- Bảng đánh giá (ratings)
CREATE TABLE ratings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 10),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, movie_id)
);

-- Bảng tiến trình xem (watch_progress)
CREATE TABLE watch_progress (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
    watch_time INTEGER DEFAULT 0, -- Tính bằng giây
    duration INTEGER DEFAULT 0, -- Tổng thời lượng tập phim (giây)
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, episode_id)
);

-- Bảng lịch sử xem (watch_history)
CREATE TABLE watch_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    movie_id INTEGER NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE CASCADE,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng báo cáo lỗi (reports)
CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    movie_id INTEGER REFERENCES movies(id) ON DELETE SET NULL,
    episode_id INTEGER REFERENCES episodes(id) ON DELETE SET NULL,
    report_type VARCHAR(50) NOT NULL, -- VD: broken_video, wrong_subtitle, copyright
    content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, in_progress, resolved, rejected
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng log hoạt động (activity_logs)
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng thông báo (notifications)
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    type VARCHAR(20) DEFAULT 'info', -- info, success, warning, error
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng thanh toán (payments)
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'VND',
    payment_method VARCHAR(50) NOT NULL, -- VD: vnpay, momo, stripe
    payment_id VARCHAR(100), -- ID từ cổng thanh toán
    status VARCHAR(20) DEFAULT 'pending', -- pending, completed, failed, refunded
    package_type VARCHAR(20) NOT NULL, -- VD: month, 3months, 6months, year
    package_duration INTEGER NOT NULL, -- Số tháng
    order_info TEXT,
    transaction_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

-- Bảng cài đặt hệ thống (settings)
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng quảng cáo (ads)
CREATE TABLE ads (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(50) NOT NULL, -- VD: header, sidebar, video_start, video_middle, video_end
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dữ liệu mẫu cho bảng settings
INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES
('site_name', 'Lọc Phim', 'general', 'Tên trang web'),
('site_description', 'Website xem phim và anime trực tuyến hàng đầu Việt Nam', 'general', 'Mô tả trang web'),
('site_keywords', 'phim, xem phim, anime, phim bộ, phim lẻ, phim vietsub, phim thuyết minh', 'general', 'Từ khóa SEO'),
('site_logo', '/assets/images/logo.png', 'general', 'Logo trang web'),
('site_favicon', '/assets/images/favicon.ico', 'general', 'Favicon trang web'),
('admin_email', 'admin@locphim.com', 'general', 'Email quản trị'),
('mail_driver', 'smtp', 'mail', 'Phương thức gửi mail'),
('mail_host', 'smtp.gmail.com', 'mail', 'Host mail'),
('mail_port', '587', 'mail', 'Cổng mail'),
('mail_username', '', 'mail', 'Tài khoản mail'),
('mail_password', '', 'mail', 'Mật khẩu mail'),
('mail_encryption', 'tls', 'mail', 'Mã hóa mail'),
('mail_from_address', 'no-reply@locphim.com', 'mail', 'Địa chỉ gửi mail'),
('mail_from_name', 'Lọc Phim', 'mail', 'Tên người gửi mail'),
('social_facebook', 'https://facebook.com/locphim', 'social', 'Trang Facebook'),
('social_twitter', 'https://twitter.com/locphim', 'social', 'Trang Twitter'),
('social_instagram', 'https://instagram.com/locphim', 'social', 'Trang Instagram'),
('social_youtube', 'https://youtube.com/locphim', 'social', 'Kênh YouTube'),
('vip_price_month', '50000', 'vip', 'Giá VIP 1 tháng (VND)'),
('vip_price_3months', '135000', 'vip', 'Giá VIP 3 tháng (VND)'),
('vip_price_6months', '250000', 'vip', 'Giá VIP 6 tháng (VND)'),
('vip_price_year', '450000', 'vip', 'Giá VIP 1 năm (VND)'),
('video_max_resolution', '1080p', 'video', 'Độ phân giải tối đa cho người dùng thường'),
('video_vip_max_resolution', '4k', 'video', 'Độ phân giải tối đa cho VIP'),
('video_watermark', '/assets/images/watermark.png', 'video', 'Watermark video'),
('video_watermark_position', 'bottom-right', 'video', 'Vị trí watermark'),
('maintenance_mode', 'false', 'system', 'Chế độ bảo trì'),
('maintenance_message', 'Trang web đang được bảo trì, vui lòng quay lại sau.', 'system', 'Thông báo bảo trì'),
('enable_registration', 'true', 'users', 'Cho phép đăng ký'),
('auto_approve_comments', 'true', 'comments', 'Tự động duyệt bình luận'),
('google_drive_api_key', '', 'api', 'API key Google Drive');

-- Tạo tài khoản admin mặc định
INSERT INTO users (username, email, password, name, is_admin, is_active) VALUES
('admin', 'admin@locphim.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', TRUE, TRUE);

-- Thêm một số danh mục phim cơ bản
INSERT INTO categories (name, slug, description) VALUES
('Hành động', 'hanh-dong', 'Các phim thuộc thể loại hành động'),
('Tình cảm', 'tinh-cam', 'Các phim thuộc thể loại tình cảm'),
('Hài hước', 'hai-huoc', 'Các phim thuộc thể loại hài hước'),
('Kinh dị', 'kinh-di', 'Các phim thuộc thể loại kinh dị'),
('Viễn tưởng', 'vien-tuong', 'Các phim thuộc thể loại viễn tưởng'),
('Hoạt hình', 'hoat-hinh', 'Các phim thuộc thể loại hoạt hình'),
('Võ thuật', 'vo-thuat', 'Các phim thuộc thể loại võ thuật'),
('Phiêu lưu', 'phieu-luu', 'Các phim thuộc thể loại phiêu lưu'),
('Tâm lý', 'tam-ly', 'Các phim thuộc thể loại tâm lý'),
('Hình sự', 'hinh-su', 'Các phim thuộc thể loại hình sự');

-- Thêm một số quốc gia cơ bản
INSERT INTO countries (name, slug, code) VALUES
('Việt Nam', 'viet-nam', 'VN'),
('Mỹ', 'my', 'US'),
('Hàn Quốc', 'han-quoc', 'KR'),
('Trung Quốc', 'trung-quoc', 'CN'),
('Nhật Bản', 'nhat-ban', 'JP'),
('Thái Lan', 'thai-lan', 'TH'),
('Pháp', 'phap', 'FR'),
('Anh', 'anh', 'GB'),
('Đài Loan', 'dai-loan', 'TW'),
('Ấn Độ', 'an-do', 'IN');

-- Chỉ tạo dữ liệu mẫu trong môi trường development
-- Thêm dữ liệu mẫu cho phim, tập phim, v.v. ở đây nếu cần