<?php
// Trang thống kê và báo cáo
session_start();

// Kết nối database và các hàm tiện ích
$db_file = '../loc_phim.db';

// Kiểm tra đăng nhập và phân quyền
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Kết nối database
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin người dùng
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy khoảng thời gian từ tham số GET hoặc mặc định 30 ngày
    $time_range = isset($_GET['range']) ? $_GET['range'] : '30days';
    $start_date = '';
    $end_date = date('Y-m-d');
    
    switch ($time_range) {
        case '7days':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $label = '7 ngày qua';
            break;
        case '30days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $label = '30 ngày qua';
            break;
        case '90days':
            $start_date = date('Y-m-d', strtotime('-90 days'));
            $label = '90 ngày qua';
            break;
        case 'year':
            $start_date = date('Y-m-d', strtotime('-1 year'));
            $label = '1 năm qua';
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $label = '30 ngày qua';
    }
    
    // Lấy dữ liệu xem phim theo ngày
    $view_data_sql = "SELECT 
                        strftime('%Y-%m-%d', watched_at) as date,
                        COUNT(*) as count
                      FROM watch_history
                      WHERE watched_at BETWEEN ? AND ? 
                      GROUP BY date
                      ORDER BY date";
    $stmt = $db->prepare($view_data_sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $view_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy dữ liệu đăng ký tài khoản theo ngày
    $signup_data_sql = "SELECT 
                        strftime('%Y-%m-%d', created_at) as date,
                        COUNT(*) as count
                      FROM users
                      WHERE created_at BETWEEN ? AND ? 
                      GROUP BY date
                      ORDER BY date";
    $stmt = $db->prepare($signup_data_sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $signup_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy top 10 phim xem nhiều nhất
    $top_movies_sql = "SELECT 
                        m.id, m.title, m.slug, m.type,
                        COUNT(w.id) as view_count
                      FROM movies m
                      JOIN watch_history w ON m.id = w.movie_id
                      WHERE w.watched_at BETWEEN ? AND ?
                      GROUP BY m.id
                      ORDER BY view_count DESC
                      LIMIT 10";
    $stmt = $db->prepare($top_movies_sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $top_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy thống kê theo thiết bị - Tạm bỏ qua vì không có trường device_type
    $device_stats = [];
    /*
    $device_stats_sql = "SELECT 
                        'Web' as device_type,
                        COUNT(*) as count
                      FROM watch_history
                      WHERE watched_at BETWEEN ? AND ?";
    $stmt = $db->prepare($device_stats_sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $device_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */
    
    // Lấy thống kê theo thể loại
    $category_stats_sql = "SELECT 
                          c.name as category_name,
                          COUNT(w.id) as view_count
                        FROM categories c
                        JOIN movie_categories mc ON c.id = mc.category_id
                        JOIN movies m ON mc.movie_id = m.id
                        JOIN watch_history w ON m.id = w.movie_id
                        WHERE w.watched_at BETWEEN ? AND ?
                        GROUP BY c.id
                        ORDER BY view_count DESC
                        LIMIT 10";
    $stmt = $db->prepare($category_stats_sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
}

// Convert data for charts
$dates = [];
$view_counts = [];
$signup_counts = [];

// Initialize arrays with all dates in range with 0 values
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$end_date_obj->modify('+1 day');
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($current_date, $interval, $end_date_obj);

foreach ($date_range as $date) {
    $date_str = $date->format('Y-m-d');
    $dates[] = $date_str;
    $view_counts[$date_str] = 0;
    $signup_counts[$date_str] = 0;
}

// Fill in actual values
foreach ($view_data as $item) {
    $view_counts[$item['date']] = (int)$item['count'];
}

foreach ($signup_data as $item) {
    $signup_counts[$item['date']] = (int)$item['count'];
}

// Prepare device stats for chart
$device_labels = [];
$device_data = [];
foreach ($device_stats as $item) {
    $device_labels[] = !empty($item['device_type']) ? $item['device_type'] : 'Unknown';
    $device_data[] = (int)$item['count'];
}

// Prepare category stats for chart
$category_labels = [];
$category_data = [];
foreach ($category_stats as $item) {
    $category_labels[] = $item['category_name'];
    $category_data[] = (int)$item['view_count'];
}

// Tiêu đề trang
$page_title = 'Thống kê & Báo cáo - Quản trị Lọc Phim';

// Bao gồm header quản trị
include 'admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">
                            <i class="fas fa-film"></i> Quản lý phim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-list"></i> Thể loại
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Người dùng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments"></i> Bình luận
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Cài đặt
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="google_drive.php">
                            <i class="fab fa-google-drive"></i> Google Drive
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Báo cáo</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Thống kê
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history"></i> Nhật ký
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Thống kê và Báo cáo</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReport()">
                            <i class="fas fa-file-export"></i> Xuất báo cáo
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> In
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar"></i> <?php echo $label; ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="timeRangeDropdown">
                            <li><a class="dropdown-item <?php echo $time_range == '7days' ? 'active' : ''; ?>" href="?range=7days">7 ngày qua</a></li>
                            <li><a class="dropdown-item <?php echo $time_range == '30days' ? 'active' : ''; ?>" href="?range=30days">30 ngày qua</a></li>
                            <li><a class="dropdown-item <?php echo $time_range == '90days' ? 'active' : ''; ?>" href="?range=90days">90 ngày qua</a></li>
                            <li><a class="dropdown-item <?php echo $time_range == 'year' ? 'active' : ''; ?>" href="?range=year">1 năm qua</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Biểu đồ tổng quan -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Thống kê lượt xem và đăng ký
                </div>
                <div class="card-body">
                    <canvas id="overviewChart" height="300"></canvas>
                </div>
            </div>
            
            <div class="row">
                <!-- Top phim xem nhiều -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i> Top phim xem nhiều
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên phim</th>
                                        <th>Loại</th>
                                        <th>Lượt xem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_movies)): ?>
                                        <?php foreach ($top_movies as $index => $movie): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <a href="../detail.php?slug=<?php echo $movie['slug']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($movie['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $movie['type'] == 'movie' ? 'Phim' : 'Anime'; ?></td>
                                                <td><?php echo number_format($movie['view_count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê theo thiết bị -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-mobile-alt"></i> Thống kê theo thiết bị
                        </div>
                        <div class="card-body">
                            <canvas id="deviceChart" height="260"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Thống kê theo thể loại -->
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-list"></i> Thống kê theo thể loại
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dữ liệu cho biểu đồ tổng quan
        const dates = <?php echo json_encode($dates); ?>;
        const viewCounts = <?php echo json_encode(array_values($view_counts)); ?>;
        const signupCounts = <?php echo json_encode(array_values($signup_counts)); ?>;
        
        // Dữ liệu cho biểu đồ thiết bị
        const deviceLabels = <?php echo json_encode($device_labels); ?>;
        const deviceData = <?php echo json_encode($device_data); ?>;
        
        // Dữ liệu cho biểu đồ thể loại
        const categoryLabels = <?php echo json_encode($category_labels); ?>;
        const categoryData = <?php echo json_encode($category_data); ?>;
        
        // Biểu đồ tổng quan
        const overviewCtx = document.getElementById('overviewChart').getContext('2d');
        const overviewChart = new Chart(overviewCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Lượt xem',
                        data: viewCounts,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Đăng ký mới',
                        data: signupCounts,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Thống kê lượt xem và đăng ký'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Biểu đồ thiết bị
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        const deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceLabels,
                datasets: [{
                    data: deviceData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Thiết bị truy cập'
                    }
                }
            }
        });
        
        // Biểu đồ thể loại
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Lượt xem',
                    data: categoryData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Thống kê theo thể loại'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
    
    // Hàm xuất báo cáo
    function exportReport() {
        // Tạo tên file báo cáo với timestamp
        const now = new Date();
        const timestamp = now.toISOString().replace(/[:.]/g, '-');
        const filename = `loc-phim-report-${timestamp}.csv`;
        
        // Giả lập tải xuống
        alert('Đã xuất báo cáo: ' + filename);
    }
</script>

<?php include 'admin_footer.php'; ?>