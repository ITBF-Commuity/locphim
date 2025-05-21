<?php
/**
 * Lọc Phim - Trang lỗi 403
 */

// Thiết lập header HTTP 403
if (!headers_sent()) {
    header('HTTP/1.0 403 Forbidden');
}

// Thiết lập tiêu đề trang
$pageTitle = 'Không có quyền truy cập - ' . SITE_NAME;
$pageDescription = 'Bạn không có quyền truy cập vào trang này.';

// Bắt đầu output buffering
ob_start();
?>

<div class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-code">403</div>
            <h1 class="error-title">Truy cập bị từ chối</h1>
            <p class="error-message">Bạn không có quyền truy cập vào trang này.</p>
            
            <div class="error-actions">
                <a href="<?php echo url(''); ?>" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
            
            <?php if (!is_logged_in()): ?>
                <div class="error-login">
                    <p>Bạn chưa đăng nhập? Đăng nhập để tiếp tục.</p>
                    <a href="<?php echo url('dang-nhap'); ?>" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .error-page {
        padding: 4rem 0;
        text-align: center;
    }
    
    .error-content {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .error-code {
        font-size: 8rem;
        font-weight: bold;
        color: var(--danger-color);
        line-height: 1;
        margin-bottom: 1rem;
        text-shadow: 3px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .error-title {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .error-message {
        font-size: 1.1rem;
        color: var(--text-muted);
        margin-bottom: 2rem;
    }
    
    .error-actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .error-login {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border-color);
    }
    
    @media (max-width: 768px) {
        .error-code {
            font-size: 6rem;
        }
        
        .error-title {
            font-size: 1.5rem;
        }
        
        .error-message {
            font-size: 1rem;
        }
        
        .error-actions {
            flex-direction: column;
        }
    }
</style>

<?php
// Lấy nội dung trang từ buffer
$pageContent = ob_get_clean();
?>