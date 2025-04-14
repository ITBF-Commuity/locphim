<?php
// Tiêu đề trang
$page_title = 'Trang chủ';

// Include header
include 'header.php';

// Lấy danh sách phim mới cập nhật
$latest_anime = get_anime(null, 12, 0, ['sort' => 'newest']);

// Lấy bảng xếp hạng
$ranking = get_ranking(10);

// Lấy anime phổ biến
$popular_anime = get_anime(null, 6, 0, ['sort' => 'views']);

// Lấy anime theo thể loại
$action_anime = get_anime(null, 4, 0, ['category_id' => 1]); // Hành động
$comedy_anime = get_anime(null, 4, 0, ['category_id' => 3]); // Hài hước
?>

<!-- Main Slider/Banner -->
<section class="main-banner mb-5">
    <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner rounded shadow">
            <div class="carousel-item active">
                <div class="banner-image" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAwIDM1MCIgZmlsbD0ibm9uZSI+PHBhdGggZD0iTTAgMGgxMDAwdjM1MEgwVjB6IiBmaWxsLW9wYWNpdHk9IjAuOCIgZmlsbD0iIzAwMCIvPjxwYXRoIGQ9Ik0zMDAgNTBoNDAwdjI1MEgzMDBWNTB6IiBmaWxsPSIjMTIxMjEyIi8+PHBhdGggZD0iTTUwMCAxMDAiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIyIi8+PHRleHQgeD0iNTAwIiB5PSIxNzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iI2ZmZiI+Q0xPU0VSUzwvdGV4dD48L3N2Zz4=');">
                    <div class="carousel-caption d-md-block">
                        <h2>CLOSERS</h2>
                        <p>Side: Blacklambs</p>
                        <a href="anime-detail.php?id=1" class="btn btn-primary">Xem ngay</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="banner-image" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAwIDM1MCIgZmlsbD0ibm9uZSI+PHBhdGggZD0iTTAgMGgxMDAwdjM1MEgwVjB6IiBmaWxsLW9wYWNpdHk9IjAuOCIgZmlsbD0iIzAwMCIvPjxwYXRoIGQ9Ik0zMDAgNTBoNDAwdjI1MEgzMDBWNTB6IiBmaWxsPSIjMTIxMjEyIi8+PHBhdGggZD0iTTUwMCAxMDAiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIyIi8+PHRleHQgeD0iNTAwIiB5PSIxNzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iI2ZmZiI+QXR0YWNrIG9uIFRpdGFuPC90ZXh0Pjwvc3ZnPg==');">
                    <div class="carousel-caption d-md-block">
                        <h2>Attack on Titan</h2>
                        <p>Final Season Part 2</p>
                        <a href="anime-detail.php?id=2" class="btn btn-primary">Xem ngay</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="banner-image" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAwIDM1MCIgZmlsbD0ibm9uZSI+PHBhdGggZD0iTTAgMGgxMDAwdjM1MEgwVjB6IiBmaWxsLW9wYWNpdHk9IjAuOCIgZmlsbD0iIzAwMCIvPjxwYXRoIGQ9Ik0zMDAgNTBoNDAwdjI1MEgzMDBWNTB6IiBmaWxsPSIjMTIxMjEyIi8+PHBhdGggZD0iTTUwMCAxMDAiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIyIi8+PHRleHQgeD0iNTAwIiB5PSIxNzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iI2ZmZiI+RGVtb24gU2xheWVyPC90ZXh0Pjwvc3ZnPg==');">
                    <div class="carousel-caption d-md-block">
                        <h2>Demon Slayer</h2>
                        <p>Entertainment District Arc</p>
                        <a href="anime-detail.php?id=3" class="btn btn-primary">Xem ngay</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<!-- Mới cập nhật -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-fire text-danger"></i> Mới cập nhật
        </h2>
        <a href="anime.php?sort=newest" class="btn btn-outline-primary btn-sm">
            Xem tất cả <i class="fas fa-angle-right"></i>
        </a>
    </div>
    
    <div class="row g-4">
        <?php foreach ($latest_anime as $anime): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="anime-card">
                    <a href="anime-detail.php?id=<?php echo $anime['id']; ?>">
                        <div class="position-relative">
                            <img src="<?php echo get_thumbnail($anime['thumbnail']); ?>" class="card-img-top" alt="<?php echo $anime['title']; ?>">
                            <div class="episode-count">
                                <?php echo $anime['episode_count']; ?> tập
                            </div>
                            <?php if (isset($anime['release_date']) && strtotime($anime['release_date']) > strtotime('-7 days')): ?>
                                <div class="new-badge">Mới</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-truncate"><?php echo $anime['title']; ?></h5>
                            <div class="anime-stats">
                                <span><i class="far fa-eye"></i> <?php echo number_format($anime['views']); ?></span>
                                <span><i class="far fa-star"></i> <?php echo number_format($anime['rating'], 1); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Bảng xếp hạng và Thể loại -->
<div class="row mb-5">
    <!-- Bảng xếp hạng -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-trophy"></i> Bảng xếp hạng
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($ranking as $index => $anime): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <div class="rank-number <?php echo $index < 3 ? 'top-rank' : ''; ?>"><?php echo $index + 1; ?></div>
                            <a href="anime-detail.php?id=<?php echo $anime['id']; ?>" class="d-flex align-items-center flex-grow-1">
                                <img src="<?php echo get_thumbnail($anime['thumbnail'], 'small'); ?>" class="ranking-thumbnail me-3" alt="<?php echo $anime['title']; ?>">
                                <div>
                                    <h6 class="mb-0"><?php echo $anime['title']; ?></h6>
                                    <small class="text-muted">
                                        <i class="far fa-eye"></i> <?php echo number_format($anime['views']); ?> lượt xem
                                    </small>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="ranking.php" class="btn btn-link">Xem thêm <i class="fas fa-angle-right"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Thể loại -->
    <div class="col-md-8">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="section-title">
                    <i class="fas fa-fist-raised text-danger"></i> Hành động
                </h3>
                <div class="row g-3">
                    <?php foreach ($action_anime as $anime): ?>
                        <div class="col-6 col-lg-3">
                            <div class="anime-card">
                                <a href="anime-detail.php?id=<?php echo $anime['id']; ?>">
                                    <div class="position-relative">
                                        <img src="<?php echo get_thumbnail($anime['thumbnail']); ?>" class="card-img-top" alt="<?php echo $anime['title']; ?>">
                                        <div class="episode-count">
                                            <?php echo $anime['episode_count']; ?> tập
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate"><?php echo $anime['title']; ?></h5>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h3 class="section-title">
                    <i class="fas fa-laugh-beam text-warning"></i> Hài hước
                </h3>
                <div class="row g-3">
                    <?php foreach ($comedy_anime as $anime): ?>
                        <div class="col-6 col-lg-3">
                            <div class="anime-card">
                                <a href="anime-detail.php?id=<?php echo $anime['id']; ?>">
                                    <div class="position-relative">
                                        <img src="<?php echo get_thumbnail($anime['thumbnail']); ?>" class="card-img-top" alt="<?php echo $anime['title']; ?>">
                                        <div class="episode-count">
                                            <?php echo $anime['episode_count']; ?> tập
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate"><?php echo $anime['title']; ?></h5>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Phổ biến -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">
            <i class="fas fa-star text-warning"></i> Phổ biến
        </h2>
        <a href="anime.php?sort=views" class="btn btn-outline-primary btn-sm">
            Xem tất cả <i class="fas fa-angle-right"></i>
        </a>
    </div>
    
    <div class="row g-4">
        <?php foreach ($popular_anime as $anime): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="anime-card popular-card">
                    <a href="anime-detail.php?id=<?php echo $anime['id']; ?>">
                        <div class="position-relative">
                            <img src="<?php echo get_thumbnail($anime['thumbnail']); ?>" class="card-img-top" alt="<?php echo $anime['title']; ?>">
                            <div class="rating-badge">
                                <i class="fas fa-star"></i> <?php echo number_format($anime['rating'], 1); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-truncate"><?php echo $anime['title']; ?></h5>
                            <p class="card-text small text-muted">
                                <?php echo $anime['release_year']; ?> • <?php echo $anime['category_name']; ?>
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Nâng cấp VIP Banner -->
<section class="vip-banner p-4 rounded shadow text-white text-center mb-5">
    <div class="row align-items-center">
        <div class="col-md-8 text-md-start">
            <h2><i class="fas fa-crown text-warning"></i> Nâng cấp lên VIP</h2>
            <p class="mb-0">Trải nghiệm xem phim không quảng cáo, chất lượng lên đến 4K và nhiều tính năng đặc biệt khác.</p>
        </div>
        <div class="col-md-4 mt-3 mt-md-0 text-md-end">
            <a href="vip.php" class="btn btn-warning btn-lg">Nâng cấp ngay</a>
        </div>
    </div>
</section>

<?php
// Include footer
include 'footer.php';
?>
