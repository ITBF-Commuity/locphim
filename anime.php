<?php
// Trang danh sách anime
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Anime - Lọc Phim</title>
    <link rel="stylesheet" href="https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .anime-card {
            transition: transform 0.3s;
        }
        .anime-card:hover {
            transform: translateY(-5px);
        }
        .anime-image {
            height: 250px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lọc Phim</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="anime.php">Anime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">Phim</a>
                    </li>
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Tìm kiếm..." aria-label="Search">
                    <button class="btn btn-primary" type="submit">Tìm</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="mb-4">Danh sách Anime</h1>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,1" class="card-img-top anime-image" alt="One Piece">
                    <div class="card-body">
                        <h5 class="card-title">One Piece</h5>
                        <p class="card-text small text-muted">1999 | 1000+ Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-info">Phiêu Lưu</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.5
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,2" class="card-img-top anime-image" alt="Naruto Shippuden">
                    <div class="card-body">
                        <h5 class="card-title">Naruto Shippuden</h5>
                        <p class="card-text small text-muted">2007 | 500 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-secondary">Siêu Nhiên</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.0
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,3" class="card-img-top anime-image" alt="Attack on Titan">
                    <div class="card-body">
                        <h5 class="card-title">Attack on Titan</h5>
                        <p class="card-text small text-muted">2013 | 75 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-danger">Kinh Dị</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.4
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,4" class="card-img-top anime-image" alt="My Hero Academia">
                    <div class="card-body">
                        <h5 class="card-title">My Hero Academia</h5>
                        <p class="card-text small text-muted">2016 | 113 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-success">Siêu Anh Hùng</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.9
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,5" class="card-img-top anime-image" alt="Demon Slayer">
                    <div class="card-body">
                        <h5 class="card-title">Demon Slayer</h5>
                        <p class="card-text small text-muted">2019 | 44 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-danger">Siêu Nhiên</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.2
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,6" class="card-img-top anime-image" alt="Jujutsu Kaisen">
                    <div class="card-body">
                        <h5 class="card-title">Jujutsu Kaisen</h5>
                        <p class="card-text small text-muted">2020 | 38 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-danger">Siêu Nhiên</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.1
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,7" class="card-img-top anime-image" alt="Tokyo Revengers">
                    <div class="card-body">
                        <h5 class="card-title">Tokyo Revengers</h5>
                        <p class="card-text small text-muted">2021 | 24 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-info">Du hành thời gian</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.7
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card anime-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?japan,animation,8" class="card-img-top anime-image" alt="Chainsaw Man">
                    <div class="card-body">
                        <h5 class="card-title">Chainsaw Man</h5>
                        <p class="card-text small text-muted">2022 | 12 Tập</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-danger">Kinh Dị</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 9.0
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
        </div>
        
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
    
    <footer class="bg-dark text-center text-white py-4">
        <div class="container">
            <p class="mb-0">© 2025 Lọc Phim - Tất cả các quyền được bảo lưu</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>