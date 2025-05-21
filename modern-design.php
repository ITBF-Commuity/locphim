<?php
/**
 * Lọc Phim - Modern Design
 * 
 * Trang mẫu để kiểm tra thiết kế hiện đại mới cho website
 */

// Nạp file cấu hình
require_once 'config.php';

// Thêm hàm kiểm tra Dark Mode
if (!function_exists('isDarkMode')) {
    function isDarkMode() {
        return !isset($_COOKIE['darkMode']) || $_COOKIE['darkMode'] !== 'true';
    }
}

// Thiết lập tiêu đề và mô tả
$pageTitle = SITE_NAME . ' - Thiết Kế Hiện Đại';
$pageDescription = 'Thiết kế giao diện hiện đại mới cho Lọc Phim';
$bodyClass = 'home-page';

// Bao gồm header
include_once 'includes/modern-header.php';

// Mô phỏng dữ liệu phim nổi bật
$featuredMovies = [
    [
        'id' => 1,
        'title' => 'Avengers: Endgame',
        'slug' => 'avengers-endgame',
        'poster' => '/assets/images/default-poster.svg',
        'backdrop' => '/assets/images/default-backdrop.svg',
        'rating' => 8.4,
        'year' => 2019,
        'duration' => 181,
        'quality' => '4K',
        'type' => 'movie',
        'description' => 'Sau các sự kiện tàn khốc của Avengers: Infinity War, vũ trụ đang dần tàn lụi. Với sự giúp đỡ của các đồng minh còn lại, các Avengers tập hợp một lần nữa để đảo ngược hành động của Thanos và khôi phục sự cân bằng cho vũ trụ.'
    ],
    [
        'id' => 2,
        'title' => 'Squid Game',
        'slug' => 'squid-game',
        'poster' => '/assets/images/default-poster.svg',
        'backdrop' => '/assets/images/default-backdrop.svg',
        'rating' => 8.1,
        'year' => 2021,
        'duration' => 60,
        'quality' => 'HD',
        'type' => 'series',
        'description' => 'Hàng trăm người chơi khánh kiệt chấp nhận một lời mời kỳ lạ để thi đấu trong các trò chơi trẻ em. Đón chờ họ là một giải thưởng hấp dẫn cùng những rủi ro chết người.'
    ],
    [
        'id' => 3,
        'title' => 'Demon Slayer: Mugen Train',
        'slug' => 'demon-slayer-mugen-train',
        'poster' => '/assets/images/default-poster.svg',
        'backdrop' => '/assets/images/default-backdrop.svg',
        'rating' => 8.3,
        'year' => 2020,
        'duration' => 117,
        'quality' => 'Full HD',
        'type' => 'anime',
        'description' => 'Tanjiro và các thành viên của Đội Diệt Quỷ lên một con tàu để điều tra hàng loạt vụ mất tích. Điều mà họ không biết là Enmu, một thành viên của Thập Nhị Quỷ Nguyệt đã đặt bẫy để tiêu diệt họ.'
    ]
];

// Mô phỏng dữ liệu phim mới nhất
$latestMovies = [];
for ($i = 1; $i <= 8; $i++) {
    $latestMovies[] = [
        'id' => $i + 3,
        'title' => 'Phim mới ' . $i,
        'slug' => 'phim-moi-' . $i,
        'poster' => '/assets/images/default-poster.svg',
        'rating' => rand(70, 90) / 10,
        'year' => rand(2020, 2023),
        'quality' => rand(0, 1) ? 'HD' : 'Full HD',
        'type' => ['movie', 'series', 'anime'][rand(0, 2)]
    ];
}

// Mô phỏng dữ liệu phim phổ biến
$popularMovies = [];
for ($i = 1; $i <= 8; $i++) {
    $popularMovies[] = [
        'id' => $i + 11,
        'title' => 'Phim phổ biến ' . $i,
        'slug' => 'phim-pho-bien-' . $i,
        'poster' => '/assets/images/default-poster.svg',
        'rating' => rand(75, 95) / 10,
        'year' => rand(2018, 2023),
        'quality' => rand(0, 1) ? 'HD' : '4K',
        'type' => ['movie', 'series', 'anime'][rand(0, 2)]
    ];
}
?>

<div class="container">
    <!-- Hero Slider -->
    <div class="slider">
        <div class="slider-inner">
            <?php foreach ($featuredMovies as $index => $movie): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <div class="slide-image">
                    <img src="<?php echo SITE_URL . $movie['backdrop']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                </div>
                <div class="slide-overlay">
                    <h2 class="slide-title"><?php echo htmlspecialchars($movie['title']); ?></h2>
                    <div class="slide-info">
                        <div class="slide-info-item">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($movie['rating'], 1); ?></span>
                        </div>
                        <div class="slide-info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo $movie['year']; ?></span>
                        </div>
                        <div class="slide-info-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $movie['duration']; ?> phút</span>
                        </div>
                        <div class="slide-info-item">
                            <i class="fas fa-<?php echo $movie['type'] === 'movie' ? 'film' : ($movie['type'] === 'series' ? 'tv' : 'dragon'); ?>"></i>
                            <span><?php echo $movie['type'] === 'movie' ? 'Phim lẻ' : ($movie['type'] === 'series' ? 'Phim bộ' : 'Anime'); ?></span>
                        </div>
                    </div>
                    <p class="slide-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                    <a href="<?php echo url('/xem/' . $movie['slug'] . '/1'); ?>" class="btn btn-lg slide-button">
                        <i class="fas fa-play"></i> Xem ngay
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="slider-prev" id="sliderPrev">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="slider-next" id="sliderNext">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="slider-dots">
            <?php foreach ($featuredMovies as $index => $movie): ?>
            <span class="slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Latest Movies Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Phim mới cập nhật</h2>
            <a href="<?php echo url('/phim-moi'); ?>" class="section-link">
                Xem tất cả <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="movie-grid">
            <?php foreach ($latestMovies as $movie): ?>
            <div class="movie-card fade-in">
                <div class="movie-poster">
                    <img src="<?php echo SITE_URL . $movie['poster']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <div class="movie-overlay">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <div class="movie-info">
                            <div class="movie-rating">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($movie['rating'], 1); ?>
                            </div>
                            <div class="movie-year">
                                <?php echo $movie['year']; ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo url('/xem/' . $movie['slug'] . '/1'); ?>" class="movie-play">
                        <i class="fas fa-play"></i>
                    </a>
                </div>
                <span class="movie-quality"><?php echo $movie['quality']; ?></span>
                <span class="movie-type">
                    <?php echo $movie['type'] === 'movie' ? 'Phim lẻ' : ($movie['type'] === 'series' ? 'Phim bộ' : 'Anime'); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Popular Movies Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Phim phổ biến</h2>
            <a href="<?php echo url('/bang-xep-hang'); ?>" class="section-link">
                Xem tất cả <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div class="movie-grid">
            <?php foreach ($popularMovies as $movie): ?>
            <div class="movie-card fade-in">
                <div class="movie-poster">
                    <img src="<?php echo SITE_URL . $movie['poster']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <div class="movie-overlay">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <div class="movie-info">
                            <div class="movie-rating">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($movie['rating'], 1); ?>
                            </div>
                            <div class="movie-year">
                                <?php echo $movie['year']; ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo url('/xem/' . $movie['slug'] . '/1'); ?>" class="movie-play">
                        <i class="fas fa-play"></i>
                    </a>
                </div>
                <span class="movie-quality"><?php echo $movie['quality']; ?></span>
                <span class="movie-type">
                    <?php echo $movie['type'] === 'movie' ? 'Phim lẻ' : ($movie['type'] === 'series' ? 'Phim bộ' : 'Anime'); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Slider functionality
        const sliderInner = document.querySelector('.slider-inner');
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        const prevBtn = document.getElementById('sliderPrev');
        const nextBtn = document.getElementById('sliderNext');
        
        let currentSlide = 0;
        const slideCount = slides.length;
        
        function goToSlide(index) {
            if (index < 0) index = slideCount - 1;
            if (index >= slideCount) index = 0;
            
            currentSlide = index;
            
            sliderInner.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Update active class
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === currentSlide);
            });
        }
        
        // Initialize slider
        goToSlide(0);
        
        // Previous and Next buttons
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                goToSlide(currentSlide - 1);
                resetInterval();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                goToSlide(currentSlide + 1);
                resetInterval();
            });
        }
        
        // Dots navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                resetInterval();
            });
        });
        
        // Auto-play
        let slideInterval = setInterval(() => {
            goToSlide(currentSlide + 1);
        }, 5000);
        
        function resetInterval() {
            clearInterval(slideInterval);
            slideInterval = setInterval(() => {
                goToSlide(currentSlide + 1);
            }, 5000);
        }
    });
</script>

<?php
// Bao gồm footer
include_once 'includes/modern-footer.php';
?>