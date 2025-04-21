<?php
// Trang danh sách phim
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Phim - Lọc Phim</title>
    <link rel="stylesheet" href="https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .movie-card {
            transition: transform 0.3s;
        }
        .movie-card:hover {
            transform: translateY(-5px);
        }
        .movie-image {
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
                        <a class="nav-link" href="anime.php">Anime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="movies.php">Phim</a>
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
        <h1 class="mb-4">Danh sách Phim</h1>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,1" class="card-img-top movie-image" alt="The Avengers">
                    <div class="card-body">
                        <h5 class="card-title">The Avengers</h5>
                        <p class="card-text small text-muted">2012 | 143 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-info">Viễn Tưởng</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.0
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,2" class="card-img-top movie-image" alt="Inception">
                    <div class="card-body">
                        <h5 class="card-title">Inception</h5>
                        <p class="card-text small text-muted">2010 | 148 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-secondary">Khoa Học Viễn Tưởng</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.8
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,3" class="card-img-top movie-image" alt="The Dark Knight">
                    <div class="card-body">
                        <h5 class="card-title">The Dark Knight</h5>
                        <p class="card-text small text-muted">2008 | 152 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-danger">Tội Phạm</span>
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
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,4" class="card-img-top movie-image" alt="Interstellar">
                    <div class="card-body">
                        <h5 class="card-title">Interstellar</h5>
                        <p class="card-text small text-muted">2014 | 169 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-secondary">Khoa Học Viễn Tưởng</span>
                                <span class="badge bg-success">Phiêu Lưu</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.6
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,5" class="card-img-top movie-image" alt="The Matrix">
                    <div class="card-body">
                        <h5 class="card-title">The Matrix</h5>
                        <p class="card-text small text-muted">1999 | 136 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-secondary">Khoa Học Viễn Tưởng</span>
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
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,6" class="card-img-top movie-image" alt="Parasite">
                    <div class="card-body">
                        <h5 class="card-title">Parasite</h5>
                        <p class="card-text small text-muted">2019 | 132 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-danger">Tội Phạm</span>
                                <span class="badge bg-info">Hài Đen</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.5
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,7" class="card-img-top movie-image" alt="Joker">
                    <div class="card-body">
                        <h5 class="card-title">Joker</h5>
                        <p class="card-text small text-muted">2019 | 122 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-danger">Tội Phạm</span>
                                <span class="badge bg-secondary">Tâm Lý</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.4
                            </div>
                        </div>
                        <a href="#" class="btn btn-primary w-100 mt-3">Xem ngay</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card movie-card shadow-sm">
                    <img src="https://source.unsplash.com/random/300x450?movie,8" class="card-img-top movie-image" alt="Avengers: Endgame">
                    <div class="card-body">
                        <h5 class="card-title">Avengers: Endgame</h5>
                        <p class="card-text small text-muted">2019 | 181 Phút</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary">Hành Động</span>
                                <span class="badge bg-info">Viễn Tưởng</span>
                            </div>
                            <div>
                                <i class="fas fa-star text-warning"></i> 8.4
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