<?php
/**
 * Trang tối ưu hóa hình ảnh
 * Lọc Phim - Admin Panel
 */

// Tiêu đề trang
$page_title = 'Tối Ưu Hình Ảnh';

// Kết nối header
require_once __DIR__ . '/partials/header.php';

// Yêu cầu quyền quản lý cài đặt
$admin = require_admin_permission('manage_settings');

// Kiểm tra xem GD extension có được cài đặt không
if (!extension_loaded('gd')) {
    set_flash_message('error', 'GD Extension chưa được cài đặt. Vui lòng cài đặt GD Extension để sử dụng tính năng tối ưu hình ảnh.');
    header('Location: performance.php');
    exit;
}

// Xử lý tối ưu hình ảnh
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    verify_csrf_token();
    
    // Lấy cài đặt
    $compression_level = $_POST['compression_level'] ?? 'medium';
    $convert_webp = isset($_POST['convert_webp']) ? true : false;
    $resize_large_images = isset($_POST['resize_large_images']) ? true : false;
    $generate_thumbnails = isset($_POST['generate_thumbnails']) ? true : false;
    
    // Xác định thư mục hình ảnh
    $images_dir = dirname(dirname(__FILE__)) . '/uploads/images';
    
    // Kiểm tra thư mục tồn tại
    if (!file_exists($images_dir)) {
        mkdir($images_dir, 0755, true);
    }
    
    // Tạo thư mục thumbnails nếu cần
    if ($generate_thumbnails) {
        $thumbnails_dir = dirname(dirname(__FILE__)) . '/uploads/images/thumbnails';
        if (!file_exists($thumbnails_dir)) {
            mkdir($thumbnails_dir, 0755, true);
        }
    }
    
    // Tạo thư mục webp nếu cần
    if ($convert_webp) {
        $webp_dir = dirname(dirname(__FILE__)) . '/uploads/images/webp';
        if (!file_exists($webp_dir)) {
            mkdir($webp_dir, 0755, true);
        }
    }
    
    // Tìm tất cả hình ảnh
    $images = find_images($images_dir);
    
    // Thiết lập các thông số tối ưu
    $quality = 80; // Chất lượng mặc định
    
    switch ($compression_level) {
        case 'low':
            $quality = 90;
            break;
        case 'medium':
            $quality = 75;
            break;
        case 'high':
            $quality = 60;
            break;
    }
    
    // Kích thước tối đa
    $max_width = 1920;
    $max_height = 1080;
    
    // Kích thước thumbnail
    $thumb_width = 300;
    $thumb_height = 170;
    
    // Biến đếm
    $total_images = count($images);
    $optimized_count = 0;
    $thumbnails_count = 0;
    $webp_count = 0;
    $resized_count = 0;
    $total_size_before = 0;
    $total_size_after = 0;
    $errors = [];
    
    // Tối ưu từng hình ảnh
    foreach ($images as $image_path) {
        // Bỏ qua các hình ảnh trong thư mục thumbnails và webp
        if (strpos($image_path, '/thumbnails/') !== false || strpos($image_path, '/webp/') !== false) {
            continue;
        }
        
        // Lấy kích thước ban đầu
        $original_size = filesize($image_path);
        $total_size_before += $original_size;
        
        // Lấy thông tin về hình ảnh
        $image_info = pathinfo($image_path);
        $extension = strtolower($image_info['extension']);
        
        // Chỉ xử lý các định dạng hình ảnh phổ biến
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            continue;
        }
        
        try {
            // Tạo đối tượng hình ảnh
            $image = null;
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($image_path);
                    break;
                case 'png':
                    $image = imagecreatefrompng($image_path);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($image_path);
                    break;
            }
            
            if (!$image) {
                $errors[] = "Không thể tạo đối tượng hình ảnh từ {$image_path}";
                continue;
            }
            
            // Lấy kích thước hình ảnh
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Thay đổi kích thước nếu cần
            if ($resize_large_images && ($width > $max_width || $height > $max_height)) {
                // Tính toán tỷ lệ
                $ratio = min($max_width / $width, $max_height / $height);
                $new_width = round($width * $ratio);
                $new_height = round($height * $ratio);
                
                // Tạo hình ảnh mới
                $resized = imagecreatetruecolor($new_width, $new_height);
                
                // Bảo toàn kênh alpha cho png
                if ($extension === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                
                // Thay đổi kích thước
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                
                // Giải phóng bộ nhớ của hình ảnh cũ
                imagedestroy($image);
                
                // Sử dụng hình ảnh mới
                $image = $resized;
                $width = $new_width;
                $height = $new_height;
                
                $resized_count++;
            }
            
            // Lưu hình ảnh đã tối ưu
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $image_path, $quality);
                    break;
                case 'png':
                    // PNG cần xử lý đặc biệt cho chất lượng
                    $png_quality = floor(($quality - 50) * 9 / 50);
                    $png_quality = max(0, min(9, $png_quality));
                    imagepng($image, $image_path, $png_quality);
                    break;
                case 'gif':
                    imagegif($image, $image_path);
                    break;
            }
            
            $optimized_count++;
            
            // Tạo thumbnail nếu cần
            if ($generate_thumbnails) {
                $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
                
                // Bảo toàn kênh alpha cho png
                if ($extension === 'png') {
                    imagealphablending($thumb_image, false);
                    imagesavealpha($thumb_image, true);
                }
                
                // Tính toán và cắt hình ảnh để vừa với kích thước thumbnail
                $source_ratio = $width / $height;
                $thumb_ratio = $thumb_width / $thumb_height;
                
                if ($source_ratio > $thumb_ratio) {
                    // Hình ảnh rộng hơn so với thumbnail
                    $source_x = round(($width - $height * $thumb_ratio) / 2);
                    $source_y = 0;
                    $source_w = round($height * $thumb_ratio);
                    $source_h = $height;
                } else {
                    // Hình ảnh cao hơn so với thumbnail
                    $source_x = 0;
                    $source_y = round(($height - $width / $thumb_ratio) / 2);
                    $source_w = $width;
                    $source_h = round($width / $thumb_ratio);
                }
                
                imagecopyresampled($thumb_image, $image, 0, 0, $source_x, $source_y, $thumb_width, $thumb_height, $source_w, $source_h);
                
                // Lưu thumbnail
                $thumb_path = $thumbnails_dir . '/' . $image_info['basename'];
                
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($thumb_image, $thumb_path, $quality);
                        break;
                    case 'png':
                        imagepng($thumb_image, $thumb_path, $png_quality);
                        break;
                    case 'gif':
                        imagegif($thumb_image, $thumb_path);
                        break;
                }
                
                imagedestroy($thumb_image);
                $thumbnails_count++;
            }
            
            // Chuyển đổi sang WebP nếu cần
            if ($convert_webp) {
                $webp_path = $webp_dir . '/' . $image_info['filename'] . '.webp';
                imagewebp($image, $webp_path, $quality);
                $webp_count++;
            }
            
            // Giải phóng bộ nhớ
            imagedestroy($image);
            
            // Lấy kích thước sau khi tối ưu
            $optimized_size = filesize($image_path);
            $total_size_after += $optimized_size;
        } catch (Exception $e) {
            $errors[] = "Lỗi xử lý hình ảnh {$image_path}: " . $e->getMessage();
        }
    }
    
    // Tính toán thống kê
    $size_saved = $total_size_before - $total_size_after;
    $percent_saved = ($total_size_before > 0) ? round(($size_saved / $total_size_before) * 100, 2) : 0;
    
    // Thông báo kết quả
    if ($optimized_count > 0) {
        $message = "Đã tối ưu $optimized_count/$total_images hình ảnh thành công. ";
        $message .= "Đã giảm " . format_size($size_saved) . " (" . $percent_saved . "%). ";
        
        if ($resized_count > 0) {
            $message .= "Đã thay đổi kích thước $resized_count hình ảnh. ";
        }
        
        if ($thumbnails_count > 0) {
            $message .= "Đã tạo $thumbnails_count hình ảnh thu nhỏ. ";
        }
        
        if ($webp_count > 0) {
            $message .= "Đã chuyển đổi $webp_count hình ảnh sang WebP.";
        }
        
        set_flash_message('success', $message);
    } else {
        set_flash_message('warning', "Không có hình ảnh nào được tối ưu.");
    }
    
    // Lưu thông báo lỗi nếu có
    if (!empty($errors)) {
        $_SESSION['optimization_errors'] = $errors;
    }
    
    // Ghi log
    log_admin_action('optimize_images', "Đã tối ưu $optimized_count hình ảnh, giảm " . format_size($size_saved) . " (" . $percent_saved . "%)");
    
    // Chuyển hướng về trang hiệu suất
    header('Location: optimize-images.php?done=1');
    exit;
}

// Kiểm tra xem có phải vừa tối ưu xong không
$is_optimized = isset($_GET['done']) && $_GET['done'] == 1;
$optimization_errors = isset($_SESSION['optimization_errors']) ? $_SESSION['optimization_errors'] : [];

// Xóa thông báo lỗi sau khi đã hiển thị
if ($is_optimized && isset($_SESSION['optimization_errors'])) {
    unset($_SESSION['optimization_errors']);
}

// CSRF token
$csrf_token = generate_csrf_token();

/**
 * Tìm tất cả hình ảnh trong thư mục
 */
function find_images($dir) {
    $images = [];
    
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = $file->getPathname();
                }
            }
        }
    }
    
    return $images;
}

/**
 * Định dạng kích thước
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

// Lấy thông tin về hình ảnh
$images_dir = dirname(dirname(__FILE__)) . '/uploads/images';
$images = file_exists($images_dir) ? find_images($images_dir) : [];
$total_images = count($images);
$total_size = 0;

// Lọc hình ảnh chính (không bao gồm thumbnails và webp)
$main_images = [];
foreach ($images as $image) {
    if (strpos($image, '/thumbnails/') === false && strpos($image, '/webp/') === false) {
        $main_images[] = $image;
        $total_size += filesize($image);
    }
}

$total_main_images = count($main_images);

// Phân tích kích thước hình ảnh
$size_categories = [
    'small' => 0,    // < 100KB
    'medium' => 0,   // 100KB - 500KB
    'large' => 0,    // 500KB - 1MB
    'xlarge' => 0    // > 1MB
];

foreach ($main_images as $image) {
    $size = filesize($image);
    
    if ($size < 100 * 1024) {
        $size_categories['small']++;
    } elseif ($size < 500 * 1024) {
        $size_categories['medium']++;
    } elseif ($size < 1024 * 1024) {
        $size_categories['large']++;
    } else {
        $size_categories['xlarge']++;
    }
}

// Xác định các định dạng hình ảnh
$formats = [
    'jpg' => 0,
    'png' => 0,
    'gif' => 0,
    'webp' => 0,
    'other' => 0
];

foreach ($images as $image) {
    $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    
    if (isset($formats[$extension])) {
        $formats[$extension]++;
    } else {
        $formats['other']++;
    }
}

// Lấy hình ảnh lớn nhất
$largest_images = $main_images;
usort($largest_images, function($a, $b) {
    return filesize($b) - filesize($a);
});
$largest_images = array_slice($largest_images, 0, 5);
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Tối Ưu Hình Ảnh</h1>
        <p class="admin-page-subtitle">Tối ưu hóa hình ảnh để tăng tốc độ tải trang</p>
    </div>
    
    <div class="admin-page-actions">
        <a href="performance.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left mr-1"></i> Quay Lại
        </a>
    </div>
</div>

<?php if (!extension_loaded('gd')): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle mr-2"></i> GD Extension chưa được cài đặt. Vui lòng cài đặt GD Extension để sử dụng tính năng tối ưu hình ảnh.
    </div>
<?php else: ?>

<?php if (!empty($optimization_errors)): ?>
    <div class="alert alert-warning">
        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle mr-2"></i> Có một số lỗi xảy ra trong quá trình tối ưu:</h5>
        <ul class="mb-0">
            <?php foreach ($optimization_errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-info-circle mr-2"></i> Thông Tin Hình Ảnh
                </h2>
            </div>
            <div class="admin-card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Tổng số hình ảnh:</span>
                        <span class="font-weight-bold"><?php echo number_format($total_main_images); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Tổng kích thước:</span>
                        <span class="font-weight-bold"><?php echo format_size($total_size); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Kích thước trung bình:</span>
                        <span class="font-weight-bold"><?php echo $total_main_images > 0 ? format_size($total_size / $total_main_images) : '0 B'; ?></span>
                    </li>
                </ul>
                
                <?php if ($total_main_images > 0): ?>
                    <div class="mt-4">
                        <h5>Phân bố kích thước:</h5>
                        <div class="progress mb-3" style="height: 24px;">
                            <?php if ($total_main_images > 0): ?>
                                <div class="progress-bar bg-success" style="width: <?php echo ($size_categories['small'] / $total_main_images) * 100; ?>%" title="< 100 KB (<?php echo $size_categories['small']; ?>)">
                                    <?php echo round(($size_categories['small'] / $total_main_images) * 100); ?>%
                                </div>
                                <div class="progress-bar bg-info" style="width: <?php echo ($size_categories['medium'] / $total_main_images) * 100; ?>%" title="100KB - 500KB (<?php echo $size_categories['medium']; ?>)">
                                    <?php echo round(($size_categories['medium'] / $total_main_images) * 100); ?>%
                                </div>
                                <div class="progress-bar bg-warning" style="width: <?php echo ($size_categories['large'] / $total_main_images) * 100; ?>%" title="500KB - 1MB (<?php echo $size_categories['large']; ?>)">
                                    <?php echo round(($size_categories['large'] / $total_main_images) * 100); ?>%
                                </div>
                                <div class="progress-bar bg-danger" style="width: <?php echo ($size_categories['xlarge'] / $total_main_images) * 100; ?>%" title="> 1MB (<?php echo $size_categories['xlarge']; ?>)">
                                    <?php echo round(($size_categories['xlarge'] / $total_main_images) * 100); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="legend small text-muted">
                            <span class="badge badge-success mr-2">< 100KB</span>
                            <span class="badge badge-info mr-2">100KB - 500KB</span>
                            <span class="badge badge-warning mr-2">500KB - 1MB</span>
                            <span class="badge badge-danger">1MB+</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($largest_images)): ?>
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">
                        <i class="fas fa-weight mr-2"></i> Hình Ảnh Lớn Nhất
                    </h2>
                </div>
                <div class="admin-card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($largest_images as $image): ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3" style="width: 50px; height: 50px; overflow: hidden; border-radius: 4px;">
                                        <img src="<?php echo str_replace(dirname(dirname(__FILE__)), '', $image); ?>" class="img-fluid" alt="Thumbnail">
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <p class="mb-1 text-truncate"><?php echo basename($image); ?></p>
                                        <small class="text-danger"><?php echo format_size(filesize($image)); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-compress mr-2"></i> Tối Ưu Hình Ảnh
                </h2>
            </div>
            <div class="admin-card-body">
                <?php if ($total_main_images === 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> Không tìm thấy hình ảnh nào trong thư mục uploads/images. Vui lòng tải lên hình ảnh trước khi sử dụng tính năng này.
                    </div>
                <?php else: ?>
                    <p>Tối ưu hóa hình ảnh sẽ giúp giảm kích thước hình ảnh, tạo hình ảnh thu nhỏ, và chuyển đổi sang định dạng hiện đại hơn.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> Quá trình tối ưu có thể mất nhiều thời gian tùy thuộc vào số lượng và kích thước hình ảnh. Vui lòng không đóng trình duyệt trong quá trình tối ưu.
                    </div>
                    
                    <form method="post" action="optimize-images.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label for="compression_level">Mức độ nén</label>
                            <select class="form-control" id="compression_level" name="compression_level">
                                <option value="low">Thấp (giảm 20-30% kích thước, chất lượng cao)</option>
                                <option value="medium" selected>Trung bình (giảm 40-60% kích thước, chất lượng tốt)</option>
                                <option value="high">Cao (giảm 70-80% kích thước, chất lượng thấp hơn)</option>
                            </select>
                            <small class="form-text text-muted">Mức độ nén cao sẽ giảm kích thước hình ảnh nhưng cũng làm giảm chất lượng.</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="convert_webp" name="convert_webp" checked>
                                <label class="custom-control-label" for="convert_webp">Chuyển đổi sang định dạng WebP</label>
                            </div>
                            <small class="form-text text-muted">WebP là định dạng hình ảnh hiện đại với kích thước nhỏ hơn và chất lượng tốt hơn.</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="resize_large_images" name="resize_large_images" checked>
                                <label class="custom-control-label" for="resize_large_images">Thay đổi kích thước hình ảnh lớn</label>
                            </div>
                            <small class="form-text text-muted">Thay đổi kích thước hình ảnh quá lớn để phù hợp với kích thước hiển thị tối đa (1920x1080).</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="generate_thumbnails" name="generate_thumbnails" checked>
                                <label class="custom-control-label" for="generate_thumbnails">Tạo hình ảnh thu nhỏ</label>
                            </div>
                            <small class="form-text text-muted">Tạo các phiên bản thu nhỏ (300x170) của hình ảnh để sử dụng trong danh sách và trang chủ.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-compress mr-1"></i> Tối ưu <?php echo number_format($total_main_images); ?> hình ảnh
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="fas fa-lightbulb mr-2"></i> Mẹo Tối Ưu Hình Ảnh
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-file-image text-primary mr-2"></i> Định dạng hình ảnh</h5>
                        <ul>
                            <li>Sử dụng WebP thay vì JPEG hoặc PNG khi có thể</li>
                            <li>Sử dụng JPEG cho hình ảnh có nhiều màu sắc và chi tiết</li>
                            <li>Sử dụng PNG cho hình ảnh cần độ trong suốt</li>
                            <li>Tránh sử dụng GIF nếu không cần hình ảnh động</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-compress-arrows-alt text-primary mr-2"></i> Kích thước hình ảnh</h5>
                        <ul>
                            <li>Luôn thay đổi kích thước hình ảnh để phù hợp với nơi hiển thị</li>
                            <li>Không tải lên hình ảnh lớn hơn 1920x1080 pixels</li>
                            <li>Sử dụng hình ảnh thu nhỏ cho danh sách và xem trước</li>
                            <li>Chỉ định kích thước hình ảnh trong HTML</li>
                        </ul>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5><i class="fas fa-stopwatch text-primary mr-2"></i> Lazy Loading</h5>
                        <ul>
                            <li>Sử dụng tính năng lazy loading để chỉ tải hình ảnh khi cần</li>
                            <li>Thêm thuộc tính loading="lazy" vào thẻ img</li>
                            <li>Sử dụng placeholder cho hình ảnh chưa tải</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-cloud text-primary mr-2"></i> CDN và Cache</h5>
                        <ul>
                            <li>Sử dụng CDN (Mạng phân phối nội dung) cho hình ảnh</li>
                            <li>Bật bộ nhớ đệm trình duyệt cho hình ảnh</li>
                            <li>Sử dụng thuộc tính srcset cho responsive images</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
// JavaScript để xử lý hiển thị trang
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Hiển thị ảnh xem trước khi hover
    const imgElements = document.querySelectorAll(".list-group-item img");
    imgElements.forEach(img => {
        const originalSrc = img.src;
        img.addEventListener("error", function() {
            this.src = "/assets/images/image-placeholder.png";
        });
    });
});
</script>
';

// Kết nối footer
require_once __DIR__ . '/partials/footer.php';
?>