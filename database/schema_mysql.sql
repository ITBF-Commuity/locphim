--
-- Lọc Phim - MySQL Database Schema
-- Version 1.0.0
--

-- Disable foreign key checks temporarily for easier data loading
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255),
    `is_admin` BOOLEAN DEFAULT FALSE,
    `is_vip` BOOLEAN DEFAULT FALSE,
    `vip_expires_at` DATETIME,
    `verification_token` VARCHAR(255),
    `is_verified` BOOLEAN DEFAULT FALSE,
    `reset_token` VARCHAR(255),
    `reset_token_expires_at` DATETIME,
    `remember_token` VARCHAR(255),
    `last_login` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table (thể loại)
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Countries table (quốc gia)
CREATE TABLE `countries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `code` CHAR(2) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movies table (phim)
CREATE TABLE `movies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `original_title` VARCHAR(255),
    `description` TEXT,
    `poster` VARCHAR(255),
    `backdrop` VARCHAR(255),
    `trailer` VARCHAR(255),
    `type` VARCHAR(20) NOT NULL COMMENT 'movie, series, anime',
    `status` VARCHAR(20) NOT NULL COMMENT 'ongoing, completed',
    `release_year` SMALLINT,
    `country_id` INT,
    `duration` INT COMMENT 'in minutes',
    `quality` VARCHAR(20) COMMENT '4K, 1080p, etc.',
    `age_rating` VARCHAR(10) COMMENT 'G, PG, PG-13, R, etc.',
    `is_featured` BOOLEAN DEFAULT FALSE,
    `is_premium` BOOLEAN DEFAULT FALSE COMMENT 'Only for VIP members',
    `views` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movie-Category relationship
CREATE TABLE `movie_categories` (
    `movie_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY (`movie_id`, `category_id`),
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Episodes table (tập phim)
CREATE TABLE `episodes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `movie_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `episode_number` INT NOT NULL,
    `season_number` INT DEFAULT 1,
    `description` TEXT,
    `thumbnail` VARCHAR(255),
    `duration` INT COMMENT 'in seconds',
    `video_url` VARCHAR(255) COMMENT 'main video source',
    `views` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_episode` (`movie_id`, `season_number`, `episode_number`),
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video sources table (các nguồn video khác nhau cho mỗi tập)
CREATE TABLE `video_sources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `episode_id` INT NOT NULL,
    `quality` VARCHAR(20) NOT NULL COMMENT '360p, 480p, 720p, 1080p, 4K',
    `source_url` VARCHAR(255) NOT NULL,
    `source_type` VARCHAR(20) NOT NULL COMMENT 'direct, embed, gdrive, m3u8',
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subtitles table (phụ đề)
CREATE TABLE `subtitles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `episode_id` INT NOT NULL,
    `language` VARCHAR(50) NOT NULL,
    `language_code` CHAR(2) NOT NULL,
    `file_url` VARCHAR(255) NOT NULL,
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites table (phim yêu thích)
CREATE TABLE `favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `movie_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_favorite` (`user_id`, `movie_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watch history table (lịch sử xem)
CREATE TABLE `watch_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `episode_id` INT NOT NULL,
    `watched_seconds` INT DEFAULT 0,
    `completed` BOOLEAN DEFAULT FALSE,
    `last_watched_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_history` (`user_id`, `episode_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlists table (danh sách phát)
CREATE TABLE `playlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlist-Movie relationship
CREATE TABLE `playlist_movies` (
    `playlist_id` INT NOT NULL,
    `movie_id` INT NOT NULL,
    `position` INT DEFAULT 0,
    `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`playlist_id`, `movie_id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table (bình luận)
CREATE TABLE `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `movie_id` INT NULL,
    `episode_id` INT NULL,
    `parent_id` INT NULL,
    `content` TEXT NOT NULL,
    `likes` INT DEFAULT 0,
    `dislikes` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    CHECK (`movie_id` IS NOT NULL OR `episode_id` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ratings table (đánh giá)
CREATE TABLE `ratings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `movie_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 10),
    `review` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_rating` (`user_id`, `movie_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports table (báo cáo lỗi)
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `movie_id` INT,
    `episode_id` INT,
    `type` VARCHAR(50) NOT NULL COMMENT 'video_broken, subtitle_issue, wrong_content, etc.',
    `content` TEXT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, resolved, rejected',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- VIP packages table (gói VIP)
CREATE TABLE `vip_packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `duration` INT NOT NULL COMMENT 'in days',
    `features` TEXT COMMENT 'JSON or comma-separated list of features',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table (giao dịch)
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `package_id` INT,
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL COMMENT 'momo, vnpay, stripe, etc.',
    `transaction_code` VARCHAR(100) UNIQUE,
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, completed, failed, refunded',
    `paid_at` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `vip_packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table (thông báo)
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'system, new_episode, account, etc.',
    `is_read` BOOLEAN DEFAULT FALSE,
    `link` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ads table (quảng cáo)
CREATE TABLE `ads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'banner, video, popup',
    `location` VARCHAR(50) NOT NULL COMMENT 'header, sidebar, player_before, player_during, player_after',
    `content` TEXT NOT NULL COMMENT 'HTML or embed code or URL',
    `starts_at` DATETIME,
    `ends_at` DATETIME,
    `is_active` BOOLEAN DEFAULT TRUE,
    `impressions` INT DEFAULT 0,
    `clicks` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table (cài đặt hệ thống)
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `group_name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'text, number, boolean, array, json',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `group_name`, `type`) VALUES
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
INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
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
INSERT INTO `countries` (`name`, `slug`, `code`) VALUES
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
INSERT INTO `vip_packages` (`name`, `description`, `price`, `duration`, `features`) VALUES
('VIP Tuần', 'Truy cập nội dung VIP trong 7 ngày', 29000, 7, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim'),
('VIP Tháng', 'Truy cập nội dung VIP trong 30 ngày', 79000, 30, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim, Sớm 2 ngày'),
('VIP Năm', 'Truy cập nội dung VIP trong 365 ngày', 599000, 365, 'Xem phim chất lượng 4K, Không quảng cáo, Tải phim, Sớm 7 ngày, Hỗ trợ ưu tiên');

-- Create admin user
INSERT INTO `users` (`username`, `email`, `phone`, `password`, `is_admin`, `is_verified`) VALUES
('admin', 'admin@locphim.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create indexes for better performance
CREATE INDEX `idx_movies_slug` ON `movies`(`slug`);
CREATE INDEX `idx_movies_type` ON `movies`(`type`);
CREATE INDEX `idx_movies_views` ON `movies`(`views`);
CREATE INDEX `idx_episodes_movie_id` ON `episodes`(`movie_id`);
CREATE INDEX `idx_episodes_views` ON `episodes`(`views`);
CREATE INDEX `idx_watch_history_user_id` ON `watch_history`(`user_id`);
CREATE INDEX `idx_favorites_user_id` ON `favorites`(`user_id`);
CREATE INDEX `idx_comments_movie_id` ON `comments`(`movie_id`);
CREATE INDEX `idx_comments_episode_id` ON `comments`(`episode_id`);