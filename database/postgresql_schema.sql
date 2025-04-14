-- Lọc Phim - Cấu trúc cơ sở dữ liệu PostgreSQL
-- Phiên bản 1.0

-- Bảng Người dùng
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  full_name VARCHAR(100) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  remember_token VARCHAR(100) DEFAULT NULL,
  reset_token VARCHAR(100) DEFAULT NULL,
  reset_token_expires TIMESTAMP DEFAULT NULL,
  email_verified BOOLEAN NOT NULL DEFAULT FALSE,
  email_verification_token VARCHAR(100) DEFAULT NULL,
  last_login TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL
);

-- Bảng Danh mục
CREATE TABLE IF NOT EXISTS categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  parent_id INTEGER DEFAULT NULL,
  "order" INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Bảng Video
CREATE TABLE IF NOT EXISTS videos (
  id SERIAL PRIMARY KEY,
  video_id VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  thumbnail VARCHAR(255) DEFAULT NULL,
  banner VARCHAR(255) DEFAULT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'anime',
  duration INTEGER DEFAULT NULL,
  release_year INTEGER DEFAULT NULL,
  release_date DATE DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ongoing',
  country VARCHAR(100) DEFAULT NULL,
  rating NUMERIC(3,1) DEFAULT 0,
  views INTEGER NOT NULL DEFAULT 0,
  episode_count INTEGER DEFAULT NULL,
  featured BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL
);

-- Bảng Tập phim
CREATE TABLE IF NOT EXISTS episodes (
  id SERIAL PRIMARY KEY,
  video_id VARCHAR(100) NOT NULL,
  episode_number INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  thumbnail VARCHAR(255) DEFAULT NULL,
  duration INTEGER DEFAULT NULL,
  views INTEGER NOT NULL DEFAULT 0,
  release_date DATE DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_episode_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT uq_video_episode UNIQUE (video_id, episode_number)
);

-- Bảng Nguồn video
CREATE TABLE IF NOT EXISTS video_sources (
  id SERIAL PRIMARY KEY,
  video_id VARCHAR(100) DEFAULT NULL,
  episode_id INTEGER DEFAULT NULL,
  quality VARCHAR(20) NOT NULL,
  source_url VARCHAR(255) NOT NULL,
  source_type VARCHAR(50) NOT NULL DEFAULT 'mp4',
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_source_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_source_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Bảng Thể loại video
CREATE TABLE IF NOT EXISTS video_categories (
  id SERIAL PRIMARY KEY,
  video_id VARCHAR(100) NOT NULL,
  category_id INTEGER NOT NULL,
  created_at TIMESTAMP NOT NULL,
  CONSTRAINT fk_vidcat_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_vidcat_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  CONSTRAINT uq_video_category UNIQUE (video_id, category_id)
);

-- Bảng Phụ đề
CREATE TABLE IF NOT EXISTS subtitles (
  id SERIAL PRIMARY KEY,
  video_id VARCHAR(100) DEFAULT NULL,
  episode_id INTEGER DEFAULT NULL,
  language VARCHAR(50) NOT NULL,
  subtitle_url VARCHAR(255) NOT NULL,
  subtitle_type VARCHAR(20) NOT NULL DEFAULT 'vtt',
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_subtitle_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_subtitle_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Bảng Bình luận
CREATE TABLE IF NOT EXISTS comments (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  video_id VARCHAR(100) DEFAULT NULL,
  episode_id INTEGER DEFAULT NULL,
  parent_id INTEGER DEFAULT NULL,
  content TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'approved',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- Bảng Thích bình luận
CREATE TABLE IF NOT EXISTS comment_likes (
  id SERIAL PRIMARY KEY,
  comment_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  created_at TIMESTAMP NOT NULL,
  CONSTRAINT fk_like_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
  CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uq_comment_user UNIQUE (comment_id, user_id)
);

-- Bảng Đánh giá video
CREATE TABLE IF NOT EXISTS ratings (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  video_id VARCHAR(100) NOT NULL,
  rating SMALLINT NOT NULL,
  review TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rating_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT uq_user_video UNIQUE (user_id, video_id)
);

-- Bảng Yêu thích
CREATE TABLE IF NOT EXISTS favorites (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  video_id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorite_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT uq_user_video_fav UNIQUE (user_id, video_id)
);

-- Bảng Lịch sử xem
CREATE TABLE IF NOT EXISTS watch_history (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  video_id VARCHAR(100) NOT NULL,
  episode_id INTEGER DEFAULT NULL,
  playback_time NUMERIC(10, 2) NOT NULL DEFAULT 0,
  duration NUMERIC(10, 2) NOT NULL DEFAULT 0,
  percentage_watched NUMERIC(5, 2) NOT NULL DEFAULT 0,
  last_watched TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL,
  CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_history_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_history_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
  CONSTRAINT uq_user_video_episode UNIQUE (user_id, video_id, episode_id)
);

-- Bảng Thành viên VIP
CREATE TABLE IF NOT EXISTS vip_members (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  level SMALLINT NOT NULL DEFAULT 1,
  start_date TIMESTAMP NOT NULL,
  expire_date TIMESTAMP NOT NULL,
  payment_id VARCHAR(100) DEFAULT NULL,
  amount NUMERIC(10, 2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  ads BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_vip_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng Giao dịch thanh toán
CREATE TABLE IF NOT EXISTS payment_transactions (
  id SERIAL PRIMARY KEY,
  transaction_id VARCHAR(100) NOT NULL UNIQUE,
  user_id INTEGER NOT NULL,
  amount NUMERIC(10, 2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  payment_gateway_code VARCHAR(100) DEFAULT NULL,
  bank_code VARCHAR(50) DEFAULT NULL,
  card_type VARCHAR(50) DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  vip_level SMALLINT NOT NULL DEFAULT 1,
  vip_duration INTEGER NOT NULL DEFAULT 30,
  order_info TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng Thông báo
CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  type VARCHAR(50) NOT NULL,
  read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL,
  read_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng Báo cáo lỗi
CREATE TABLE IF NOT EXISTS reports (
  id SERIAL PRIMARY KEY,
  user_id INTEGER DEFAULT NULL,
  video_id VARCHAR(100) DEFAULT NULL,
  episode_id INTEGER DEFAULT NULL,
  report_type VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  admin_note TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_report_video FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
  CONSTRAINT fk_report_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
);

-- Bảng Cache API anime
CREATE TABLE IF NOT EXISTS anime_api_cache (
  id SERIAL PRIMARY KEY,
  source_id VARCHAR(100) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  alt_title VARCHAR(255) DEFAULT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  thumbnail VARCHAR(255) DEFAULT NULL,
  banner VARCHAR(255) DEFAULT NULL,
  release_year INTEGER DEFAULT NULL,
  release_date DATE DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'unknown',
  episode_count INTEGER DEFAULT 0,
  rating NUMERIC(3,1) DEFAULT 0,
  details_json TEXT DEFAULT NULL,
  trailer_json TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE INDEX anime_api_cache_source_id_idx ON anime_api_cache (source_id);
CREATE INDEX anime_api_cache_title_idx ON anime_api_cache (title);
CREATE INDEX anime_api_cache_slug_idx ON anime_api_cache (slug);

-- Bảng Cài đặt trang web
CREATE TABLE IF NOT EXISTS settings (
  id SERIAL PRIMARY KEY,
  key VARCHAR(100) NOT NULL UNIQUE,
  value TEXT DEFAULT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'text',
  "group" VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT NULL
);

-- Chèn dữ liệu mặc định
INSERT INTO settings (key, value, type, "group", created_at) VALUES
('site_name', 'Lọc Phim', 'text', 'general', NOW()),
('site_description', 'Trang xem phim và anime trực tuyến', 'textarea', 'general', NOW()),
('site_logo', '', 'image', 'general', NOW()),
('site_favicon', '', 'image', 'general', NOW()),
('maintenance_mode', '0', 'boolean', 'general', NOW()),
('maintenance_message', 'Trang web đang được bảo trì. Vui lòng quay lại sau.', 'textarea', 'general', NOW()),
('theme_color', '#007bff', 'color', 'appearance', NOW()),
('dark_mode', '0', 'boolean', 'appearance', NOW()),
('allow_comments', '1', 'boolean', 'comments', NOW()),
('comment_moderation', '0', 'boolean', 'comments', NOW()),
('show_related_videos', '1', 'boolean', 'video', NOW()),
('auto_play_next', '1', 'boolean', 'video', NOW()),
('default_video_quality', '720p', 'select', 'video', NOW()),
('homepage_featured_limit', '5', 'number', 'homepage', NOW()),
('homepage_latest_limit', '12', 'number', 'homepage', NOW()),
('homepage_popular_limit', '12', 'number', 'homepage', NOW()),
('items_per_page', '20', 'number', 'pagination', NOW()),
('admin_email', 'admin@locphim.com', 'email', 'contact', NOW()),
('contact_email', 'contact@locphim.com', 'email', 'contact', NOW()),
('social_facebook', '', 'url', 'social', NOW()),
('social_twitter', '', 'url', 'social', NOW()),
('social_instagram', '', 'url', 'social', NOW()),
('social_youtube', '', 'url', 'social', NOW()),
('analytics_code', '', 'textarea', 'seo', NOW()),
('meta_keywords', 'anime, phim, phim hoạt hình, xem phim, xem anime', 'textarea', 'seo', NOW()),
('footer_text', '© 2025 Lọc Phim. Tất cả quyền được bảo lưu.', 'textarea', 'general', NOW());