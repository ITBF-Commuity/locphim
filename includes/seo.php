<?php
/**
 * Tối ưu hóa SEO
 * Lọc Phim - SEO Optimization
 */

// Định nghĩa hằng số bảo vệ tệp
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Lớp SEO Optimizer
 * Quản lý và tối ưu hóa các yếu tố SEO của trang web
 */
class SEOOptimizer {
    /**
     * Cấu hình SEO
     */
    private $config = [
        'enable_seo' => true,
        'site_name' => 'Lọc Phim',
        'site_title_format' => '{page_title} | {site_name}',
        'title_separator' => '|',
        'meta_description' => 'Trang web xem phim và anime trực tuyến với chất lượng cao, phụ đề Tiếng Việt.',
        'meta_keywords' => 'anime, phim, phim hoạt hình, xem phim, xem anime',
        'anime_keywords' => 'anime, phim hoạt hình Nhật Bản, anime vietsub, xem anime, otaku, manga, anime mùa, anime mới nhất, anime hay',
        'canonical_url' => '',
        'enable_og_meta' => true,
        'enable_twitter_cards' => true,
        'twitter_card_type' => 'summary_large_image',
        'twitter_username' => '',
        'enable_meta_author' => true,
        'og_image' => ''
    ];
    
    /**
     * Biến lưu trữ dữ liệu SEO
     */
    private $data = [
        'title' => '',
        'description' => '',
        'keywords' => '',
        'image' => '',
        'type' => 'website',
        'url' => '',
        'author' => '',
        'published_time' => '',
        'modified_time' => '',
        'tags' => [],
        'section' => '',
        'noindex' => false,
        'nofollow' => false,
        'extra_tags' => []
    ];

    /**
     * Hàm khởi tạo
     */
    public function __construct() {
        // Tải cấu hình từ DB
        $this->loadConfig();
        
        // Đặt URL hiện tại làm canonical URL nếu không được đặt
        if (empty($this->data['url'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $this->data['url'] = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    }
    
    /**
     * Tải cấu hình từ bảng settings
     */
    private function loadConfig() {
        $settings = [
            'enable_seo' => get_setting('enable_seo', '1'),
            'site_name' => get_setting('site_name', 'Lọc Phim'),
            'site_title_format' => get_setting('site_title_format', '{page_title} | {site_name}'),
            'title_separator' => get_setting('title_separator', '|'),
            'meta_description' => get_setting('meta_description', $this->config['meta_description']),
            'meta_keywords' => get_setting('meta_keywords', $this->config['meta_keywords']),
            'anime_keywords' => get_setting('anime_keywords', $this->config['anime_keywords']),
            'canonical_url' => get_setting('canonical_url', ''),
            'enable_og_meta' => get_setting('enable_og_meta', '1'),
            'enable_twitter_cards' => get_setting('enable_twitter_cards', '1'),
            'twitter_card_type' => get_setting('twitter_card_type', 'summary_large_image'),
            'twitter_username' => get_setting('twitter_username', ''),
            'enable_meta_author' => get_setting('enable_meta_author', '1'),
            'og_image' => get_setting('og_image', '')
        ];
        
        // Cập nhật cấu hình
        foreach ($settings as $key => $value) {
            $this->config[$key] = $value;
        }
    }
    
    /**
     * Đặt tiêu đề trang
     */
    public function setTitle($title) {
        $this->data['title'] = $title;
        return $this;
    }
    
    /**
     * Đặt mô tả meta
     */
    public function setDescription($description) {
        $this->data['description'] = $description;
        return $this;
    }
    
    /**
     * Đặt từ khóa meta
     */
    public function setKeywords($keywords) {
        $this->data['keywords'] = $keywords;
        return $this;
    }
    
    /**
     * Đặt từ khóa anime mặc định
     */
    public function useAnimeKeywords() {
        $this->data['keywords'] = $this->config['anime_keywords'];
        return $this;
    }
    
    /**
     * Đặt hình ảnh cho OpenGraph
     */
    public function setImage($image) {
        $this->data['image'] = $image;
        return $this;
    }
    
    /**
     * Đặt loại nội dung (website, article, video, etc.)
     */
    public function setType($type) {
        $this->data['type'] = $type;
        return $this;
    }
    
    /**
     * Đặt URL chính thức
     */
    public function setURL($url) {
        $this->data['url'] = $url;
        return $this;
    }
    
    /**
     * Đặt tác giả
     */
    public function setAuthor($author) {
        $this->data['author'] = $author;
        return $this;
    }
    
    /**
     * Đặt thời gian xuất bản
     */
    public function setPublishedTime($time) {
        $this->data['published_time'] = $time;
        return $this;
    }
    
    /**
     * Đặt thời gian sửa đổi
     */
    public function setModifiedTime($time) {
        $this->data['modified_time'] = $time;
        return $this;
    }
    
    /**
     * Đặt danh sách thẻ
     */
    public function setTags($tags) {
        $this->data['tags'] = $tags;
        return $this;
    }
    
    /**
     * Đặt loại mục
     */
    public function setSection($section) {
        $this->data['section'] = $section;
        return $this;
    }
    
    /**
     * Bật/tắt noindex
     */
    public function setNoIndex($noindex = true) {
        $this->data['noindex'] = $noindex;
        return $this;
    }
    
    /**
     * Bật/tắt nofollow
     */
    public function setNoFollow($nofollow = true) {
        $this->data['nofollow'] = $nofollow;
        return $this;
    }
    
    /**
     * Thêm thẻ meta tùy chỉnh
     */
    public function addMetaTag($name, $content, $property = false) {
        $this->data['extra_tags'][] = [
            'name' => $name,
            'content' => $content,
            'property' => $property
        ];
        return $this;
    }
    
    /**
     * Tạo thẻ meta
     */
    private function createMetaTag($name, $content, $property = false) {
        if (empty($content)) {
            return '';
        }
        
        $attribute = $property ? 'property' : 'name';
        return "<meta {$attribute}=\"{$name}\" content=\"" . htmlspecialchars($content, ENT_QUOTES) . "\" />\n";
    }
    
    /**
     * Tạo tiêu đề trang
     */
    private function generateTitle() {
        $title = $this->data['title'];
        
        if (empty($title)) {
            return $this->config['site_name'];
        }
        
        // Định dạng tiêu đề theo cấu hình
        $formatted_title = str_replace(
            ['{page_title}', '{site_name}', '{separator}'],
            [$title, $this->config['site_name'], $this->config['title_separator']],
            $this->config['site_title_format']
        );
        
        return $formatted_title;
    }
    
    /**
     * Tạo thẻ title
     */
    private function generateTitleTag() {
        return "<title>" . htmlspecialchars($this->generateTitle(), ENT_QUOTES) . "</title>\n";
    }
    
    /**
     * Tạo thẻ meta description
     */
    private function generateDescriptionTag() {
        $description = $this->data['description'];
        
        if (empty($description)) {
            $description = $this->config['meta_description'];
        }
        
        return $this->createMetaTag('description', $description);
    }
    
    /**
     * Tạo thẻ meta keywords
     */
    private function generateKeywordsTag() {
        $keywords = $this->data['keywords'];
        
        if (empty($keywords)) {
            $keywords = $this->config['meta_keywords'];
        }
        
        return $this->createMetaTag('keywords', $keywords);
    }
    
    /**
     * Tạo thẻ canonical
     */
    private function generateCanonicalTag() {
        $url = $this->data['url'];
        
        if (empty($url)) {
            // Sử dụng canonical URL từ cấu hình nếu có
            if (!empty($this->config['canonical_url'])) {
                $url = rtrim($this->config['canonical_url'], '/') . $_SERVER['REQUEST_URI'];
            } else {
                // Tạo URL từ $_SERVER
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }
        }
        
        // Loại bỏ các tham số URL không cần thiết
        $url = preg_replace('/([?&])(_=[^&]+)(&|$)/', '$1', $url);
        $url = rtrim($url, '?&');
        
        return "<link rel=\"canonical\" href=\"" . htmlspecialchars($url, ENT_QUOTES) . "\" />\n";
    }
    
    /**
     * Tạo thẻ meta robots
     */
    private function generateRobotsTag() {
        $directives = [];
        
        if ($this->data['noindex']) {
            $directives[] = 'noindex';
        } else {
            $directives[] = 'index';
        }
        
        if ($this->data['nofollow']) {
            $directives[] = 'nofollow';
        } else {
            $directives[] = 'follow';
        }
        
        return $this->createMetaTag('robots', implode(', ', $directives));
    }
    
    /**
     * Tạo thẻ meta OpenGraph
     */
    private function generateOpenGraphTags() {
        if ($this->config['enable_og_meta'] !== '1') {
            return '';
        }
        
        $tags = '';
        
        // Thông tin cơ bản
        $tags .= $this->createMetaTag('og:title', $this->data['title'] ?: $this->generateTitle(), true);
        $tags .= $this->createMetaTag('og:description', $this->data['description'] ?: $this->config['meta_description'], true);
        $tags .= $this->createMetaTag('og:type', $this->data['type'], true);
        $tags .= $this->createMetaTag('og:url', $this->data['url'], true);
        
        // Hình ảnh
        $image = $this->data['image'] ?: $this->config['og_image'];
        if (!empty($image)) {
            // Nếu là đường dẫn tương đối, thêm domain
            if (strpos($image, 'http') !== 0) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domain = $protocol . $_SERVER['HTTP_HOST'];
                $image = $domain . '/' . ltrim($image, '/');
            }
            
            $tags .= $this->createMetaTag('og:image', $image, true);
        }
        
        // Tên trang web
        $tags .= $this->createMetaTag('og:site_name', $this->config['site_name'], true);
        
        // Thông tin bổ sung cho article
        if ($this->data['type'] === 'article') {
            if (!empty($this->data['published_time'])) {
                $tags .= $this->createMetaTag('article:published_time', $this->data['published_time'], true);
            }
            
            if (!empty($this->data['modified_time'])) {
                $tags .= $this->createMetaTag('article:modified_time', $this->data['modified_time'], true);
            }
            
            if (!empty($this->data['author'])) {
                $tags .= $this->createMetaTag('article:author', $this->data['author'], true);
            }
            
            if (!empty($this->data['section'])) {
                $tags .= $this->createMetaTag('article:section', $this->data['section'], true);
            }
            
            // Thẻ
            foreach ($this->data['tags'] as $tag) {
                $tags .= $this->createMetaTag('article:tag', $tag, true);
            }
        }
        
        return $tags;
    }
    
    /**
     * Tạo thẻ Twitter Card
     */
    private function generateTwitterCardTags() {
        if ($this->config['enable_twitter_cards'] !== '1') {
            return '';
        }
        
        $tags = '';
        
        // Loại thẻ
        $tags .= $this->createMetaTag('twitter:card', $this->config['twitter_card_type']);
        
        // Tên người dùng Twitter nếu có
        if (!empty($this->config['twitter_username'])) {
            $tags .= $this->createMetaTag('twitter:site', '@' . ltrim($this->config['twitter_username'], '@'));
        }
        
        // Tiêu đề và mô tả
        $tags .= $this->createMetaTag('twitter:title', $this->data['title'] ?: $this->generateTitle());
        $tags .= $this->createMetaTag('twitter:description', $this->data['description'] ?: $this->config['meta_description']);
        
        // Hình ảnh
        $image = $this->data['image'] ?: $this->config['og_image'];
        if (!empty($image)) {
            // Nếu là đường dẫn tương đối, thêm domain
            if (strpos($image, 'http') !== 0) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domain = $protocol . $_SERVER['HTTP_HOST'];
                $image = $domain . '/' . ltrim($image, '/');
            }
            
            $tags .= $this->createMetaTag('twitter:image', $image);
        }
        
        return $tags;
    }
    
    /**
     * Tạo thẻ tác giả
     */
    private function generateAuthorTag() {
        if ($this->config['enable_meta_author'] !== '1' || empty($this->data['author'])) {
            return '';
        }
        
        return $this->createMetaTag('author', $this->data['author']);
    }
    
    /**
     * Tạo thẻ meta tùy chỉnh
     */
    private function generateExtraTags() {
        $tags = '';
        
        foreach ($this->data['extra_tags'] as $tag) {
            $tags .= $this->createMetaTag($tag['name'], $tag['content'], $tag['property']);
        }
        
        return $tags;
    }
    
    /**
     * Xuất HTML cho các thẻ SEO
     */
    public function generateTags() {
        if ($this->config['enable_seo'] !== '1') {
            return "<title>" . htmlspecialchars($this->generateTitle(), ENT_QUOTES) . "</title>\n";
        }
        
        $tags = '';
        
        // Tiêu đề trang
        $tags .= $this->generateTitleTag();
        
        // Meta mô tả và từ khóa
        $tags .= $this->generateDescriptionTag();
        $tags .= $this->generateKeywordsTag();
        
        // Canonical URL
        $tags .= $this->generateCanonicalTag();
        
        // Meta robots
        $tags .= $this->generateRobotsTag();
        
        // Meta tác giả
        $tags .= $this->generateAuthorTag();
        
        // OpenGraph tags
        $tags .= $this->generateOpenGraphTags();
        
        // Twitter Card tags
        $tags .= $this->generateTwitterCardTags();
        
        // Thẻ meta tùy chỉnh
        $tags .= $this->generateExtraTags();
        
        return $tags;
    }
    
    /**
     * Xuất HTML
     */
    public function __toString() {
        return $this->generateTags();
    }
    
    /**
     * Tối ưu hóa tên tệp cho SEO
     */
    public static function optimizeFilename($filename) {
        // Chuyển đổi tiếng Việt sang không dấu
        $filename = self::convertVietnamese($filename);
        
        // Loại bỏ các ký tự đặc biệt
        $filename = preg_replace('/[^a-zA-Z0-9\s-]/', '', $filename);
        
        // Chuyển khoảng trắng thành dấu gạch ngang
        $filename = preg_replace('/\s+/', '-', $filename);
        
        // Chuyển về chữ thường
        $filename = strtolower($filename);
        
        // Loại bỏ dấu gạch ngang liên tiếp
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Cắt bớt nếu quá dài
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 50);
        }
        
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $filename = trim($filename, '-');
        
        return $filename;
    }
    
    /**
     * Tạo slug từ chuỗi
     */
    public static function createSlug($string) {
        // Chuyển đổi tiếng Việt sang không dấu
        $string = self::convertVietnamese($string);
        
        // Loại bỏ các ký tự đặc biệt
        $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', $string);
        
        // Chuyển khoảng trắng thành dấu gạch ngang
        $string = preg_replace('/\s+/', '-', $string);
        
        // Chuyển về chữ thường
        $string = strtolower($string);
        
        // Loại bỏ dấu gạch ngang liên tiếp
        $string = preg_replace('/-+/', '-', $string);
        
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $string = trim($string, '-');
        
        return $string;
    }
    
    /**
     * Chuyển đổi tiếng Việt sang không dấu
     */
    public static function convertVietnamese($string) {
        $search = array(
            '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#u',
            '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#u',
            '#(ì|í|ị|ỉ|ĩ)#u',
            '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#u',
            '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#u',
            '#(ỳ|ý|ỵ|ỷ|ỹ)#u',
            '#(đ)#u',
        );
        
        $replace = array(
            'a',
            'e',
            'i',
            'o',
            'u',
            'y',
            'd',
        );
        
        return preg_replace($search, $replace, $string);
    }
    
    /**
     * Rút gọn văn bản và thêm dấu ba chấm
     */
    public static function truncateText($text, $length = 160, $append = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
    
    /**
     * Tạo mô tả meta từ nội dung
     */
    public static function generateMetaDescription($content, $length = 160) {
        // Loại bỏ các thẻ HTML
        $content = strip_tags($content);
        
        // Loại bỏ khoảng trắng dư thừa
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Rút gọn văn bản
        return self::truncateText($content, $length);
    }
    
    /**
     * Tạo từ khóa meta từ nội dung
     */
    public static function generateMetaKeywords($content, $maxKeywords = 10) {
        // Loại bỏ các thẻ HTML
        $content = strip_tags($content);
        
        // Loại bỏ ký tự đặc biệt
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content);
        
        // Loại bỏ khoảng trắng dư thừa
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Chuyển thành chữ thường
        $content = mb_strtolower($content);
        
        // Tách thành các từ
        $words = explode(' ', $content);
        
        // Loại bỏ các từ ngắn
        $words = array_filter($words, function($word) {
            return mb_strlen($word) > 3;
        });
        
        // Đếm tần suất
        $wordCount = array_count_values($words);
        
        // Sắp xếp theo tần suất
        arsort($wordCount);
        
        // Lấy các từ khóa phổ biến nhất
        $keywords = array_slice(array_keys($wordCount), 0, $maxKeywords);
        
        return implode(', ', $keywords);
    }
}

/**
 * Hàm toàn cục để tạo SEO Optimizer
 */
function seo() {
    static $seo = null;
    
    if ($seo === null) {
        $seo = new SEOOptimizer();
    }
    
    return $seo;
}

/**
 * Hiển thị schema markup cho JSON-LD
 */
function generate_schema_markup($schema_type, $data) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $schema_type
    ];
    
    // Kết hợp dữ liệu
    $schema = array_merge($schema, $data);
    
    // Xuất dưới dạng JSON-LD
    $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    return '<script type="application/ld+json">' . $json . '</script>';
}

/**
 * Tạo schema markup cho video
 */
function generate_video_schema($video) {
    $data = [
        'name' => $video['title'],
        'description' => $video['description'],
        'thumbnailUrl' => $video['thumbnail'],
        'uploadDate' => date('c', strtotime($video['created_at'])),
        'duration' => 'PT' . $video['duration'] . 'M',
        'contentUrl' => get_site_url() . '/watch.php?slug=' . $video['slug']
    ];
    
    if (!empty($video['rating'])) {
        $data['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $video['rating'],
            'bestRating' => '10',
            'worstRating' => '1',
            'ratingCount' => $video['rating_count'] ?? 0
        ];
    }
    
    return generate_schema_markup('VideoObject', $data);
}

/**
 * Tạo schema markup cho trang web
 */
function generate_website_schema() {
    $site_name = get_setting('site_name', 'Lọc Phim');
    $site_description = get_setting('site_description', 'Trang xem phim và anime trực tuyến');
    
    $data = [
        'name' => $site_name,
        'description' => $site_description,
        'url' => get_site_url(),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => get_site_url() . '/search.php?q={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ]
    ];
    
    return generate_schema_markup('WebSite', $data);
}

/**
 * Hàm lấy URL của trang web
 */
function get_site_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'];
}

/**
 * Tạo sitemap
 */
function generate_sitemap() {
    // Chuẩn bị file XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    // Lấy domain
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $protocol . $_SERVER['HTTP_HOST'];
    
    // Trang chủ
    $xml .= "\t<url>\n";
    $xml .= "\t\t<loc>" . $domain . "/</loc>\n";
    $xml .= "\t\t<priority>1.0</priority>\n";
    $xml .= "\t\t<changefreq>daily</changefreq>\n";
    $xml .= "\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $xml .= "\t</url>\n";
    
    // Danh sách anime
    $xml .= "\t<url>\n";
    $xml .= "\t\t<loc>" . $domain . "/anime.php</loc>\n";
    $xml .= "\t\t<priority>0.9</priority>\n";
    $xml .= "\t\t<changefreq>daily</changefreq>\n";
    $xml .= "\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $xml .= "\t</url>\n";
    
    // Trang xếp hạng
    $xml .= "\t<url>\n";
    $xml .= "\t\t<loc>" . $domain . "/ranking.php</loc>\n";
    $xml .= "\t\t<priority>0.8</priority>\n";
    $xml .= "\t\t<changefreq>daily</changefreq>\n";
    $xml .= "\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $xml .= "\t</url>\n";
    
    // Trang VIP
    $xml .= "\t<url>\n";
    $xml .= "\t\t<loc>" . $domain . "/vip.php</loc>\n";
    $xml .= "\t\t<priority>0.7</priority>\n";
    $xml .= "\t\t<changefreq>weekly</changefreq>\n";
    $xml .= "\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $xml .= "\t</url>\n";
    
    // Tất cả anime
    $anime_list = db_query("SELECT id, title, slug, updated_at FROM anime", [], true);
    
    foreach ($anime_list as $anime) {
        $xml .= "\t<url>\n";
        $xml .= "\t\t<loc>" . $domain . "/anime-detail.php?id=" . $anime['id'] . "</loc>\n";
        $xml .= "\t\t<priority>0.7</priority>\n";
        $xml .= "\t\t<changefreq>weekly</changefreq>\n";
        $xml .= "\t\t<lastmod>" . date('Y-m-d', strtotime($anime['updated_at'] ?? 'now')) . "</lastmod>\n";
        $xml .= "\t</url>\n";
    }
    
    // Tất cả tập phim
    $episodes = db_query("
        SELECT e.id, e.episode_number, e.anime_id, e.updated_at, a.title as anime_title 
        FROM episodes e
        JOIN anime a ON e.anime_id = a.id
    ", [], true);
    
    foreach ($episodes as $episode) {
        $xml .= "\t<url>\n";
        $xml .= "\t\t<loc>" . $domain . "/watch.php?anime_id=" . $episode['anime_id'] . "&episode_id=" . $episode['id'] . "</loc>\n";
        $xml .= "\t\t<priority>0.6</priority>\n";
        $xml .= "\t\t<changefreq>monthly</changefreq>\n";
        $xml .= "\t\t<lastmod>" . date('Y-m-d', strtotime($episode['updated_at'] ?? 'now')) . "</lastmod>\n";
        $xml .= "\t</url>\n";
    }
    
    // Đóng file XML
    $xml .= '</urlset>';
    
    // Lưu file
    $sitemap_path = dirname(dirname(__FILE__)) . '/sitemap.xml';
    file_put_contents($sitemap_path, $xml);
    
    return [
        'success' => true,
        'path' => $sitemap_path,
        'url' => $domain . '/sitemap.xml',
        'count' => count($anime_list) + count($episodes) + 4 // 4 trang tĩnh
    ];
}

/**
 * Đếm số URL trong sitemap
 */
function count_sitemap_urls() {
    $sitemap_path = dirname(dirname(__FILE__)) . '/sitemap.xml';
    
    if (!file_exists($sitemap_path)) {
        return 0;
    }
    
    $content = file_get_contents($sitemap_path);
    return substr_count($content, '<url>');
}

/**
 * Format kích thước file
 */
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}