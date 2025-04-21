/**
 * Xử lý phụ đề và ngôn ngữ âm thanh cho Lọc Phim
 */
document.addEventListener('DOMContentLoaded', function() {
    // Phần tử video
    const videoElement = document.getElementById('main-player');
    if (!videoElement) return;

    // Biến lưu trữ thông tin phụ đề và âm thanh
    let subtitleVisible = true;
    let currentSubtitleTrack = null;
    
    const movieId = document.querySelector('.player-container')?.dataset.movieId;
    const episodeId = document.querySelector('.player-container')?.dataset.episodeId;
    
    // Xử lý chọn phụ đề
    document.querySelectorAll('.subtitle-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            
            // Xóa active từ tất cả các tùy chọn
            document.querySelectorAll('.subtitle-option').forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Đánh dấu tùy chọn hiện tại là active
            this.classList.add('active');
            
            // Cập nhật phụ đề
            if (value === 'off') {
                // Tắt tất cả các track
                for (let i = 0; i < videoElement.textTracks.length; i++) {
                    videoElement.textTracks[i].mode = 'disabled';
                }
                subtitleVisible = false;
            } else {
                // Bật track tương ứng
                for (let i = 0; i < videoElement.textTracks.length; i++) {
                    if (videoElement.textTracks[i].language === value) {
                        videoElement.textTracks[i].mode = 'showing';
                        currentSubtitleTrack = videoElement.textTracks[i];
                    } else {
                        videoElement.textTracks[i].mode = 'disabled';
                    }
                }
                subtitleVisible = true;
            }
            
            // Lưu cài đặt phụ đề
            if (movieId && episodeId) {
                fetch('ajax/save_subtitle_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `movie_id=${movieId}&episode_id=${episodeId}&subtitle=${value}`
                })
                .catch(error => {
                    console.error('Lỗi khi lưu cài đặt phụ đề:', error);
                });
            }
            
            // Đóng menu
            document.querySelector('.subtitle-options').classList.remove('show');
        });
    });
    
    // Hiển thị/ẩn menu phụ đề
    const subtitleSelector = document.querySelector('.subtitle-selector');
    if (subtitleSelector) {
        subtitleSelector.querySelector('.btn-subtitle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.subtitle-options').classList.toggle('show');
            
            // Đóng các menu khác
            document.querySelector('.audio-options')?.classList.remove('show');
            document.querySelector('.quality-options')?.classList.remove('show');
        });
    }
    
    // Xử lý chọn ngôn ngữ âm thanh
    document.querySelectorAll('.audio-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            const audioUrl = this.dataset.url;
            
            // Xóa active từ tất cả các tùy chọn
            document.querySelectorAll('.audio-option').forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Đánh dấu tùy chọn hiện tại là active
            this.classList.add('active');
            
            // Lưu vị trí hiện tại
            const currentTime = videoElement.currentTime;
            const isPaused = videoElement.paused;
            
            // Cập nhật nguồn âm thanh
            const source = videoElement.querySelector('source');
            source.src = audioUrl;
            
            // Load lại video với âm thanh mới
            videoElement.load();
            
            // Sau khi load xong, khôi phục vị trí và trạng thái
            videoElement.addEventListener('loadedmetadata', function onceLoaded() {
                videoElement.currentTime = currentTime;
                
                if (!isPaused) {
                    videoElement.play();
                }
                
                // Xóa listener này để tránh gọi nhiều lần
                videoElement.removeEventListener('loadedmetadata', onceLoaded);
            });
            
            // Lưu cài đặt ngôn ngữ âm thanh
            if (movieId && episodeId) {
                fetch('ajax/save_audio_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `movie_id=${movieId}&episode_id=${episodeId}&audio=${value}`
                })
                .catch(error => {
                    console.error('Lỗi khi lưu cài đặt âm thanh:', error);
                });
            }
            
            // Đóng menu
            document.querySelector('.audio-options').classList.remove('show');
        });
    });
    
    // Hiển thị/ẩn menu ngôn ngữ âm thanh
    const audioSelector = document.querySelector('.audio-selector');
    if (audioSelector) {
        audioSelector.querySelector('.btn-audio').addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.audio-options').classList.toggle('show');
            
            // Đóng các menu khác
            document.querySelector('.subtitle-options')?.classList.remove('show');
            document.querySelector('.quality-options')?.classList.remove('show');
        });
    }
    
    // Đóng menu khi click ra ngoài
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.subtitle-selector') && !e.target.closest('.audio-selector')) {
            document.querySelector('.subtitle-options')?.classList.remove('show');
            document.querySelector('.audio-options')?.classList.remove('show');
        }
    });
});