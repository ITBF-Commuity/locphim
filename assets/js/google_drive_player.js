/**
 * Xử lý trình phát video từ Google Drive cho Lọc Phim
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo trình phát Google Drive
    initGoogleDrivePlayer();
});

/**
 * Khởi tạo trình phát Google Drive
 */
function initGoogleDrivePlayer() {
    const video = document.getElementById('main-player');
    
    if (!video) return;
    
    // Kiểm tra xem video có sử dụng nguồn Google Drive không
    const sourceType = video.dataset.sourceType;
    
    if (sourceType === 'google_drive') {
        // Lấy URL video hiện tại
        const currentSource = video.querySelector('source');
        
        if (currentSource) {
            const driveId = currentSource.getAttribute('src');
            
            // Đảm bảo rằng đây là ID Google Drive
            if (driveId && !driveId.startsWith('http')) {
                // Gắn sự kiện xử lý lỗi
                video.addEventListener('error', handleVideoError);
                
                // Tạo URL proxy
                const proxyUrl = getProxyUrl(driveId);
                
                // Cập nhật nguồn video
                currentSource.setAttribute('src', proxyUrl);
                
                // Tải lại video
                video.load();
                
                console.log('Đã khởi tạo player Google Drive');
            }
        }
    }
}

/**
 * Lấy ID Google Drive từ URL
 * 
 * @param {string} url URL của Google Drive
 * @return {string|null} ID của file Google Drive hoặc null nếu không tìm thấy
 */
function getGoogleDriveId(url) {
    // Xử lý trường hợp url đã là ID
    if (url && !url.includes('/') && !url.includes('?')) {
        return url;
    }
    
    // Xử lý URL Google Drive
    if (url && url.includes('drive.google.com')) {
        // URL kiểu drive.google.com/file/d/{ID}/view
        let matches = url.match(/\/file\/d\/([^\/]+)/);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        // URL kiểu drive.google.com/open?id={ID}
        matches = url.match(/[?&]id=([^&]+)/);
        if (matches && matches[1]) {
            return matches[1];
        }
    }
    
    return null;
}

/**
 * Lấy URL proxy cho ID Google Drive
 * 
 * @param {string} driveId ID của file Google Drive
 * @return {string} URL proxy cho video
 */
function getProxyUrl(driveId) {
    return `/api/gdrive_proxy.php?drive_id=${driveId}`;
}

/**
 * Xử lý lỗi khi phát video từ Google Drive
 * 
 * @param {Event} e Sự kiện lỗi
 */
function handleVideoError(e) {
    console.error('Lỗi khi phát video Google Drive:', e);
    
    const video = e.target;
    const currentSource = video.querySelector('source');
    
    if (currentSource) {
        const driveId = currentSource.getAttribute('data-original-src') || 
                       getGoogleDriveId(currentSource.getAttribute('src'));
        
        if (driveId) {
            // Lưu nguồn gốc nếu chưa có
            if (!currentSource.getAttribute('data-original-src')) {
                currentSource.setAttribute('data-original-src', driveId);
            }
            
            // Thử sử dụng phương thức redirect thay vì proxy
            const newUrl = `/api/gdrive_proxy.php?drive_id=${driveId}&redirect=true`;
            
            // Cập nhật nguồn video
            currentSource.setAttribute('src', newUrl);
            
            // Tải lại video
            video.load();
            console.log('Đã thử phương thức thay thế');
        }
    }
    
    // Hiển thị thông báo lỗi
    const playerContainer = video.closest('.player-container');
    if (playerContainer) {
        let errorMessage = playerContainer.querySelector('.error-message');
        
        if (!errorMessage) {
            errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.innerHTML = `
                <div class="error-content">
                    <i class="fa fa-exclamation-triangle"></i>
                    <h3>Không thể phát video</h3>
                    <p>Đã xảy ra lỗi khi tải video từ Google Drive. Vui lòng thử lại sau hoặc chọn chất lượng khác.</p>
                    <button class="btn btn-primary btn-retry">Thử lại</button>
                </div>
            `;
            playerContainer.appendChild(errorMessage);
            
            // Thêm sự kiện thử lại
            const retryButton = errorMessage.querySelector('.btn-retry');
            if (retryButton) {
                retryButton.addEventListener('click', function() {
                    // Xóa thông báo lỗi
                    errorMessage.remove();
                    
                    // Tải lại video
                    video.load();
                    video.play();
                });
            }
        }
    }
}