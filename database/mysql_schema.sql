-- Lọc Phim - Cấu trúc cơ sở dữ liệu MySQL
-- Phiên bản 1.0

-- Bảng Người dùng
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin', 'moderator') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `reset_token` VARCHAR(100) DEFAULT NULL,
  `reset_token_expires` DATETIME DEFAULT NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(100) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Danh mục
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `parent_id` INT DEFAULT NULL,
  `order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Video
CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(100) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `banner` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('movie', 'anime', 'tv_show') NOT NULL DEFAULT 'anime',
  `duration` INT DEFAULT NULL,
  `release_year` INT DEFAULT NULL,
  `release_date` DATE DEFAULT NULL,
  `status` ENUM('ongoing', 'completed', 'upcoming', 'cancelled') NOT NULL DEFAULT 'ongoing',
  `country` VARCHAR(100) DEFAULT NULL,
  `rating` DECIMAL(3,1) DEFAULT 0,
  `views` INT NOT NULL DEFAULT 0,
  `episode_count` INT DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Tập phim
CREATE TABLE IF NOT EXISTS `episodes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(100) NOT NULL,
  `episode_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `duration` INT DEFAULT NULL,
  `views` INT NOT NULL DEFAULT 0,
  `release_date` DATE DEFAULT NULL,
  `status` ENUM('published', 'draft', 'private') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  UNIQUE KEY `video_episode` (`video_id`, `episode_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Nguồn video
CREATE TABLE IF NOT EXISTS `video_sources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(100) DEFAULT NULL,
  `episode_id` INT DEFAULT NULL,
  `quality` VARCHAR(20) NOT NULL,
  `source_url` VARCHAR(255) NOT NULL,
  `source_type` VARCHAR(50) NOT NULL DEFAULT 'mp4',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Thể loại video
CREATE TABLE IF NOT EXISTS `video_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(100) NOT NULL,
  `category_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `video_category` (`video_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Phụ đề
CREATE TABLE IF NOT EXISTS `subtitles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` VARCHAR(100) DEFAULT NULL,
  `episode_id` INT DEFAULT NULL,
  `language` VARCHAR(50) NOT NULL,
  `subtitle_url` VARCHAR(255) NOT NULL,
  `subtitle_type` VARCHAR(20) NOT NULL DEFAULT 'vtt',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Bình luận
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `video_id` VARCHAR(100) DEFAULT NULL,
  `episode_id` INT DEFAULT NULL,
  `parent_id` INT DEFAULT NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('approved', 'pending', 'spam') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Thích bình luận
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `comment_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `comment_user` (`comment_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Đánh giá video
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `video_id` VARCHAR(100) NOT NULL,
  `rating` TINYINT NOT NULL,
  `review` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  UNIQUE KEY `user_video` (`user_id`, `video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Yêu thích
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `video_id` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  UNIQUE KEY `user_video` (`user_id`, `video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Lịch sử xem
CREATE TABLE IF NOT EXISTS `watch_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `video_id` VARCHAR(100) NOT NULL,
  `episode_id` INT DEFAULT NULL,
  `playback_time` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `duration` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `percentage_watched` DECIMAL(5, 2) NOT NULL DEFAULT 0,
  `last_watched` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_video_episode` (`user_id`, `video_id`, `episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Thành viên VIP
CREATE TABLE IF NOT EXISTS `vip_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `level` TINYINT NOT NULL DEFAULT 1,
  `start_date` DATETIME NOT NULL,
  `expire_date` DATETIME NOT NULL,
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
  `ads` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Giao dịch thanh toán
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_gateway_code` VARCHAR(100) DEFAULT NULL,
  `bank_code` VARCHAR(50) DEFAULT NULL,
  `card_type` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `vip_level` TINYINT NOT NULL DEFAULT 1,
  `vip_duration` INT NOT NULL DEFAULT 30,
  `order_info` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Thông báo
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `read_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Báo cáo lỗi
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `video_id` VARCHAR(100) DEFAULT NULL,
  `episode_id` INT DEFAULT NULL,
  `report_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
  `admin_note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`video_id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Cache API anime
CREATE TABLE IF NOT EXISTS `anime_api_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `source_id` VARCHAR(100) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `alt_title` VARCHAR(255) DEFAULT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `banner` VARCHAR(255) DEFAULT NULL,
  `release_year` INT DEFAULT NULL,
  `release_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'unknown',
  `episode_count` INT DEFAULT 0,
  `rating` DECIMAL(3,1) DEFAULT 0,
  `details_json` TEXT DEFAULT NULL,
  `trailer_json` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  INDEX (`source_id`),
  INDEX (`title`),
  INDEX (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Cài đặt trang web
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'text',
  `group` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chèn dữ liệu mặc định
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `created_at`) VALUES
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