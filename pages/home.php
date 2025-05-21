<?php
/**
 * Lọc Phim - Trang chủ
 */

// Thiết lập tiêu đề trang
$pageTitle = SITE_NAME . ' - ' . SITE_DESCRIPTION;

// Lấy danh sách phim nổi bật
try {
    $featuredMovies = $db->getAll("SELECT m.* 
                               FROM movies m 
                               WHERE m.is_featured = true AND m.status = 'published' 
                               ORDER BY m.release_date DESC 
                               LIMIT 6");
} catch (Exception $e) {
    // Trong trường hợp có lỗi (ví dụ: bảng chưa được tạo), khởi tạo mảng rỗng
    $featuredMovies = [];
}

// Lấy phim mới cập nhật
try {
    $latestMovies = $db->getAll("SELECT m.* 
                             FROM movies m 
                             WHERE m.status = 'published' 
                             ORDER BY m.created_at DESC 
                             LIMIT 12");
} catch (Exception $e) {
    $latestMovies = [];
}

// Lấy phim xem nhiều
try {
    $popularMovies = $db->getAll("SELECT m.* 
                              FROM movies m 
                              WHERE m.status = 'published' 
                              ORDER BY m.views DESC 
                              LIMIT 12");
} catch (Exception $e) {
    $popularMovies = [];
}

// Lấy phim lẻ mới
try {
    $singleMovies = $db->getAll("SELECT m.* 
                             FROM movies m 
                             WHERE m.type = 'movie' AND m.status = 'published' 
                             ORDER BY m.release_date DESC 
                             LIMIT 12");
} catch (Exception $e) {
    $singleMovies = [];
}

// Lấy phim bộ mới
try {
    $seriesMovies = $db->getAll("SELECT m.* 
                             FROM movies m 
                             WHERE m.type = 'series' AND m.status = 'published' 
                             ORDER BY m.release_date DESC 
                             LIMIT 12");
} catch (Exception $e) {
    $seriesMovies = [];
}

// Lấy anime mới
try {
    $animeMovies = $db->getAll("SELECT m.* 
                            FROM movies m 
                            WHERE m.type = 'anime' AND m.status = 'published' 
                            ORDER BY m.release_date DESC 
                            LIMIT 12");
} catch (Exception $e) {
    $animeMovies = [];
}

// Lấy thể loại phim
try {
    $categories = $db->getAll("SELECT * FROM categories ORDER BY name ASC");
} catch (Exception $e) {
    $categories = [];
}

// Bắt đầu output buffering
ob_start();
?>

<div class="home-page">
    <?php if (!empty($featuredMovies)): ?>
    <!-- Hero Section with Featured Movies -->
    <section class="hero-section">
        <div class="hero-slider" id="hero-slider">
            <?php foreach ($featuredMovies as $index => $movie): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo !empty($movie['backdrop']) ? image_url($movie['backdrop']) : image_url($movie['poster']); ?>');">
                    <div class="hero-overlay"></div>
                    <div class="container">
                        <div class="hero-content">
                            <div class="hero-poster">
                                <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                            </div>
                            <div class="hero-info">
                                <h2 class="hero-title"><?php echo htmlspecialchars($movie['title']); ?></h2>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <h3 class="hero-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></h3>
                                <?php endif; ?>
                                
                                <div class="hero-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($movie['quality'])): ?>
                                        <span class="meta-item quality"><?php echo htmlspecialchars($movie['quality']); ?></span>
                                    <?php endif; ?>
                                    
                                    <span class="meta-item type">
                                        <?php 
                                        switch ($movie['type']) {
                                            case 'movie':
                                                echo 'Phim Lẻ';
                                                break;
                                            case 'series':
                                                echo 'Phim Bộ';
                                                break;
                                            case 'anime':
                                                echo 'Anime';
                                                break;
                                            default:
                                                echo ucfirst($movie['type']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="hero-description">
                                    <?php 
                                    if (!empty($movie['description'])) {
                                        echo truncate_string(strip_tags($movie['description']), 200);
                                    }
                                    ?>
                                </div>
                                
                                <div class="hero-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Xem ngay
                                    </a>
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn btn-outline">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Hero Controls -->
            <div class="hero-controls">
                <button class="hero-control prev" id="hero-prev"><i class="fas fa-chevron-left"></i></button>
                <div class="hero-dots">
                    <?php for ($i = 0; $i < count($featuredMovies); $i++): ?>
                        <span class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                </div>
                <button class="hero-control next" id="hero-next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <?php if (!empty($latestMovies)): ?>
            <!-- Latest Movies -->
            <div class="section latest-movies">
                <div class="section-header">
                    <h2 class="section-title">Phim mới cập nhật</h2>
                    <a href="<?php echo url('danh-sach/phim-moi'); ?>" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($latestMovies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                </a>
                                
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                                
                                <div class="movie-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                                
                                <?php if ($movie['type'] === 'series' || $movie['type'] === 'anime'): ?>
                                    <div class="movie-episode-count">
                                        <?php
                                        try {
                                            $episodeCount = $db->getValue("SELECT COUNT(*) FROM episodes WHERE movie_id = ?", [$movie['id']]);
                                            echo !empty($movie['total_episodes']) ? $episodeCount . '/' . $movie['total_episodes'] : $episodeCount . ' tập';
                                        } catch (Exception $e) {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="movie-info">
                                <h3 class="movie-title">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="movie-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                    
                                    <span class="meta-item type">
                                        <?php 
                                        switch ($movie['type']) {
                                            case 'movie':
                                                echo 'Phim Lẻ';
                                                break;
                                            case 'series':
                                                echo 'Phim Bộ';
                                                break;
                                            case 'anime':
                                                echo 'Anime';
                                                break;
                                            default:
                                                echo ucfirst($movie['type']);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($popularMovies)): ?>
            <!-- Popular Movies -->
            <div class="section popular-movies">
                <div class="section-header">
                    <h2 class="section-title">Phim xem nhiều</h2>
                    <a href="<?php echo url('danh-sach/phim-xem-nhieu'); ?>" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($popularMovies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                </a>
                                
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                                
                                <div class="movie-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                                
                                <?php if ($movie['views'] > 0): ?>
                                    <div class="movie-view-count">
                                        <i class="fas fa-eye"></i> <?php echo format_views($movie['views']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="movie-info">
                                <h3 class="movie-title">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="movie-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                    
                                    <span class="meta-item type">
                                        <?php 
                                        switch ($movie['type']) {
                                            case 'movie':
                                                echo 'Phim Lẻ';
                                                break;
                                            case 'series':
                                                echo 'Phim Bộ';
                                                break;
                                            case 'anime':
                                                echo 'Anime';
                                                break;
                                            default:
                                                echo ucfirst($movie['type']);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($categories)): ?>
            <!-- Categories -->
            <div class="section categories">
                <div class="section-header">
                    <h2 class="section-title">Thể loại phim</h2>
                </div>
                
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <a href="<?php echo url('the-loai/' . $category['slug']); ?>" class="category-item">
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($singleMovies)): ?>
            <!-- Single Movies -->
            <div class="section single-movies">
                <div class="section-header">
                    <h2 class="section-title">Phim lẻ mới</h2>
                    <a href="<?php echo url('danh-sach/phim-le'); ?>" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($singleMovies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                </a>
                                
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                                
                                <div class="movie-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                                
                                <?php if (!empty($movie['quality'])): ?>
                                    <div class="movie-quality"><?php echo htmlspecialchars($movie['quality']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="movie-info">
                                <h3 class="movie-title">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="movie-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($movie['duration'])): ?>
                                        <span class="meta-item duration"><?php echo format_duration($movie['duration']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($seriesMovies)): ?>
            <!-- Series Movies -->
            <div class="section series-movies">
                <div class="section-header">
                    <h2 class="section-title">Phim bộ mới</h2>
                    <a href="<?php echo url('danh-sach/phim-bo'); ?>" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($seriesMovies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                </a>
                                
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                                
                                <div class="movie-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                                
                                <?php
                                try {
                                    $episodeCount = $db->getValue("SELECT COUNT(*) FROM episodes WHERE movie_id = ?", [$movie['id']]);
                                    echo '<div class="movie-episode-count">';
                                    echo !empty($movie['total_episodes']) ? $episodeCount . '/' . $movie['total_episodes'] : $episodeCount . ' tập';
                                    echo '</div>';
                                } catch (Exception $e) {
                                    // Skip if there's an error
                                }
                                ?>
                            </div>
                            
                            <div class="movie-info">
                                <h3 class="movie-title">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="movie-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($animeMovies)): ?>
            <!-- Anime -->
            <div class="section anime-movies">
                <div class="section-header">
                    <h2 class="section-title">Anime mới</h2>
                    <a href="<?php echo url('danh-sach/anime'); ?>" class="view-all">Xem tất cả <i class="fas fa-angle-right"></i></a>
                </div>
                
                <div class="movie-grid">
                    <?php foreach ($animeMovies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                    <img src="<?php echo image_url($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                                </a>
                                
                                <?php if ($movie['is_vip']): ?>
                                    <div class="vip-badge">VIP</div>
                                <?php endif; ?>
                                
                                <div class="movie-actions">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>" class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                                
                                <?php
                                try {
                                    $episodeCount = $db->getValue("SELECT COUNT(*) FROM episodes WHERE movie_id = ?", [$movie['id']]);
                                    echo '<div class="movie-episode-count">';
                                    echo !empty($movie['total_episodes']) ? $episodeCount . '/' . $movie['total_episodes'] : $episodeCount . ' tập';
                                    echo '</div>';
                                } catch (Exception $e) {
                                    // Skip if there's an error
                                }
                                ?>
                            </div>
                            
                            <div class="movie-info">
                                <h3 class="movie-title">
                                    <a href="<?php echo url('phim/' . $movie['slug'] . '/' . $movie['id']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($movie['original_title'])): ?>
                                    <div class="movie-original-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="movie-meta">
                                    <?php if (!empty($movie['release_year'])): ?>
                                        <span class="meta-item year"><?php echo $movie['release_year']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
    /* Hero Section */
    .hero-section {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .hero-slider {
        position: relative;
        height: 550px;
        overflow: hidden;
    }
    
    .hero-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transition: opacity 0.5s ease;
        z-index: 1;
    }
    
    .hero-slide.active {
        opacity: 1;
        z-index: 2;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.5) 50%, rgba(0, 0, 0, 0.7) 100%);
    }
    
    .hero-content {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
        padding: 2rem 0;
        color: white;
    }
    
    .hero-poster {
        flex-shrink: 0;
        width: 250px;
        margin-right: 2rem;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        position: relative;
    }
    
    .hero-poster img {
        width: 100%;
        height: auto;
    }
    
    .hero-poster .vip-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--vip-color);
        color: var(--bg-dark);
        font-size: 0.875rem;
        font-weight: bold;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
    }
    
    .hero-info {
        flex: 1;
        max-width: 600px;
    }
    
    .hero-title {
        font-size: 3rem;
        margin-bottom: 0.5rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }
    
    .hero-original-title {
        font-size: 1.5rem;
        font-weight: normal;
        margin-bottom: 1rem;
        opacity: 0.8;
    }
    
    .hero-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .meta-item {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        background-color: rgba(255, 255, 255, 0.1);
        font-size: 0.875rem;
    }
    
    .hero-description {
        margin-bottom: 2rem;
        font-size: 1.1rem;
        line-height: 1.6;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }
    
    .hero-actions {
        display: flex;
        gap: 1rem;
    }
    
    .hero-controls {
        position: absolute;
        bottom: 20px;
        left: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        z-index: 3;
    }
    
    .hero-control {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .hero-control:hover {
        background-color: var(--primary-color);
    }
    
    .hero-dots {
        display: flex;
        gap: 0.5rem;
    }
    
    .hero-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .hero-dot.active {
        background-color: var(--primary-color);
    }
    
    /* Main Content Sections */
    .section {
        margin-bottom: 3rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        font-size: 1.75rem;
        margin: 0;
        position: relative;
        padding-left: 15px;
    }
    
    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 10%;
        height: 80%;
        width: 4px;
        background-color: var(--primary-color);
        border-radius: 2px;
    }
    
    .view-all {
        color: var(--primary-color);
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: var(--font-weight-medium);
        transition: var(--transition);
    }
    
    .view-all:hover {
        color: var(--primary-hover);
    }
    
    /* Movie Grid */
    .movie-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1.5rem;
    }
    
    .movie-card {
        display: flex;
        flex-direction: column;
    }
    
    .movie-poster {
        position: relative;
        margin-bottom: 0.75rem;
        border-radius: var(--border-radius);
        overflow: hidden;
        aspect-ratio: 2/3;
    }
    
    .movie-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .movie-poster:hover img {
        transform: scale(1.05);
    }
    
    .movie-poster .vip-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--vip-color);
        color: var(--bg-dark);
        font-size: 0.75rem;
        font-weight: bold;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        z-index: 2;
    }
    
    .movie-actions {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .movie-poster:hover .movie-actions {
        opacity: 1;
    }
    
    .btn-play {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    
    .btn-play:hover {
        background-color: var(--primary-hover);
        transform: scale(1.1);
    }
    
    .movie-episode-count,
    .movie-view-count,
    .movie-quality {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        z-index: th;
    }
    
    .movie-info {
        flex: 1;
    }
    
    .movie-title {
        font-size: 1rem;
        margin-bottom: 0.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .movie-title a {
        color: var(--text-color);
        transition: var(--transition);
    }
    
    .movie-title a:hover {
        color: var(--primary-color);
    }
    
    .movie-original-title {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }
    
    .movie-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    /* Category Grid */
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .category-item {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 60px;
        background-color: var(--bg-light);
        border-radius: var(--border-radius);
        text-align: center;
        transition: var(--transition);
        border: 1px solid var(--border-color);
        padding: 0.5rem;
    }
    
    .category-item:hover {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .category-name {
        font-weight: var(--font-weight-medium);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .hero-slider {
            height: 450px;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-poster {
            width: 200px;
        }
    }
    
    @media (max-width: 768px) {
        .hero-slider {
            height: auto;
            aspect-ratio: 16/10;
        }
        
        .hero-content {
            flex-direction: column;
            text-align: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .hero-poster {
            margin-right: 0;
            margin-bottom: 1.5rem;
            width: 180px;
        }
        
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-meta {
            justify-content: center;
        }
        
        .hero-actions {
            justify-content: center;
        }
        
        .movie-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .hero-description {
            display: none;
        }
        
        .movie-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
        }
        
        .category-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero slider
    const heroSlider = document.getElementById('hero-slider');
    if (heroSlider) {
        const slides = heroSlider.querySelectorAll('.hero-slide');
        const dots = heroSlider.querySelectorAll('.hero-dot');
        const prevBtn = document.getElementById('hero-prev');
        const nextBtn = document.getElementById('hero-next');
        let currentIndex = 0;
        let intervalId = null;
        
        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            currentIndex = index;
        }
        
        function nextSlide() {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= slides.length) {
                nextIndex = 0;
            }
            showSlide(nextIndex);
        }
        
        function prevSlide() {
            let prevIndex = currentIndex - 1;
            if (prevIndex < 0) {
                prevIndex = slides.length - 1;
            }
            showSlide(prevIndex);
        }
        
        function startSlideInterval() {
            if (intervalId) {
                clearInterval(intervalId);
            }
            intervalId = setInterval(nextSlide, 5000);
        }
        
        // Initialize slider
        if (slides.length > 0) {
            startSlideInterval();
            
            // Attach event listeners
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    prevSlide();
                    startSlideInterval();
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    nextSlide();
                    startSlideInterval();
                });
            }
            
            dots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    showSlide(index);
                    startSlideInterval();
                });
            });
            
            // Pause slider on hover
            heroSlider.addEventListener('mouseenter', function() {
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            });
            
            heroSlider.addEventListener('mouseleave', function() {
                startSlideInterval();
            });
        }
    }
});
</script>

<?php
// Lấy nội dung trang từ buffer
$pageContent = ob_get_clean();
?>