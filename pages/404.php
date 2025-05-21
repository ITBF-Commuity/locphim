<?php
/**
 * Lọc Phim - Trang lỗi 404
 */

// Thiết lập header HTTP 404
if (!headers_sent()) {
    header('HTTP/1.0 404 Not Found');
}

// Thiết lập tiêu đề trang
$pageTitle = 'Không tìm thấy trang - ' . SITE_NAME;
$pageDescription = 'Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.';

// Bắt đầu output buffering
ob_start();
?>

<div class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Không tìm thấy trang</h1>
            <p class="error-message">Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
            
            <div class="error-actions">
                <a href="<?php echo url(''); ?>" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
            
            <div class="error-search">
                <form action="<?php echo url('tim-kiem'); ?>" method="GET">
                    <div class="search-form">
                        <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
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
        color: var(--primary-color);
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
    
    .error-search {
        max-width: 500px;
        margin: 0 auto;
    }
    
    .search-form {
        display: flex;
        border: 1px solid var(--border-color);
        border-radius: 50px;
        overflow: hidden;
    }
    
    .search-input {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border: none;
        background-color: var(--bg-light);
    }
    
    .search-input:focus {
        outline: none;
    }
    
    .search-button {
        padding: 0.75rem 1.5rem;
        background-color: var(--primary-color);
        color: white;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .search-button:hover {
        background-color: var(--primary-hover);
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