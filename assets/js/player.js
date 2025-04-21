/**
 * Lọc Phim - Player JavaScript
 * 
 * File JavaScript để xử lý player video và các tính năng liên quan
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== Các biến toàn cục =====
    const videoPlayer = document.getElementById('video-player');
    const videoContainer = document.querySelector('.video-player-container');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const muteBtn = document.getElementById('mute-btn');
    const volumeSlider = document.getElementById('volume-slider');
    const currentTimeElement = document.getElementById('current-time');
    const durationElement = document.getElementById('duration');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.querySelector('.progress-container');
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const settingsBtn = document.getElementById('settings-btn');
    const settingsMenu = document.getElementById('settings-menu');
    const qualityOptions = document.getElementById('quality-options');
    const subtitleOptions = document.getElementById('subtitle-options');
    const playbackSpeedOptions = document.getElementById('playback-speed-options');
    const loadingIndicator = document.querySelector('.loading-indicator');
    
    // Thông tin video
    const videoId = videoPlayer ? videoPlayer.getAttribute('data-video-id') : null;
    const episodeId = videoPlayer ? videoPlayer.getAttribute('data-episode-id') : null;
    const movieId = videoPlayer ? videoPlayer.getAttribute('data-movie-id') : null;
    const saveProgressInterval = 10; // Lưu tiến độ mỗi 10 giây
    let currentQuality = 'auto';
    let currentSubtitle = 'off';
    let currentPlaybackSpeed = 1;
    let isUserSeeking = false;
    let progressUpdateInterval;
    let lastSavedTime = 0;
    
    // Kiểm tra xem có phần tử player không
    if (!videoPlayer) return;
    
    // ===== Các hàm chính =====
    
    // Khởi tạo player video
    function initializePlayer() {
        // Đặt âm lượng mặc định
        videoPlayer.volume = 0.8;
        volumeSlider.value = videoPlayer.volume * 100;
        
        // Thêm các sự kiện cho player
        addPlayerEvents();
        
        // Khôi phục tiến độ xem trước đó
        restoreVideoProgress();
        
        // Khởi tạo menu cài đặt
        initializeSettingsMenu();
        
        // Cập nhật trạng thái player
        updatePlayerState();
    }
    
    // Thêm các sự kiện cho player
    function addPlayerEvents() {
        // Sự kiện phát/tạm dừng
        playPauseBtn.addEventListener('click', togglePlayPause);
        videoPlayer.addEventListener('click', togglePlayPause);
        
        // Sự kiện tắt/bật âm thanh
        muteBtn.addEventListener('click', toggleMute);
        
        // Sự kiện thay đổi âm lượng
        volumeSlider.addEventListener('input', changeVolume);
        
        // Sự kiện tiến độ video
        videoPlayer.addEventListener('timeupdate', updateProgress);
        progressContainer.addEventListener('click', setProgress);
        
        // Sự kiện toàn màn hình
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        
        // Sự kiện cài đặt
        settingsBtn.addEventListener('click', toggleSettings);
        
        // Sự kiện loading
        videoPlayer.addEventListener('waiting', showLoading);
        videoPlayer.addEventListener('canplay', hideLoading);
        
        // Sự kiện kết thúc video
        videoPlayer.addEventListener('ended', onVideoEnded);
        
        // Sự kiện tải metadata
        videoPlayer.addEventListener('loadedmetadata', function() {
            updateDuration();
            hideLoading();
        });
        
        // Sự kiện cho thanh tiến trình
        progressBar.addEventListener('mousedown', function() {
            isUserSeeking = true;
            videoPlayer.pause();
        });
        
        document.addEventListener('mouseup', function() {
            if (isUserSeeking) {
                isUserSeeking = false;
                videoPlayer.play();
            }
        });
        
        progressBar.addEventListener('input', function() {
            const seekTime = calculateSeekTime(progressBar.value);
            currentTimeElement.textContent = formatTime(seekTime);
            
            // Hiển thị preview nếu có
            showSeekPreview(seekTime);
        });
        
        progressBar.addEventListener('change', function() {
            const seekTime = calculateSeekTime(progressBar.value);
            videoPlayer.currentTime = seekTime;
        });
        
        // Lưu tiến độ định kỳ
        progressUpdateInterval = setInterval(saveVideoProgress, saveProgressInterval * 1000);
        
        // Sự kiện khi người dùng rời khỏi trang
        window.addEventListener('beforeunload', function() {
            saveVideoProgress();
        });
        
        // Sự kiện phím tắt
        document.addEventListener('keydown', handleKeyPress);
    }
    
    // Phát/tạm dừng video
    function togglePlayPause() {
        if (videoPlayer.paused) {
            videoPlayer.play();
        } else {
            videoPlayer.pause();
        }
        updatePlayerState();
    }
    
    // Cập nhật trạng thái player
    function updatePlayerState() {
        // Cập nhật nút phát/tạm dừng
        if (videoPlayer.paused) {
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            videoContainer.classList.add('paused');
        } else {
            playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            videoContainer.classList.remove('paused');
        }
        
        // Cập nhật nút tắt/bật âm thanh
        if (videoPlayer.muted || videoPlayer.volume === 0) {
            muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        } else if (videoPlayer.volume < 0.5) {
            muteBtn.innerHTML = '<i class="fas fa-volume-down"></i>';
        } else {
            muteBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    }
    
    // Tắt/bật âm thanh
    function toggleMute() {
        videoPlayer.muted = !videoPlayer.muted;
        
        if (videoPlayer.muted) {
            volumeSlider.value = 0;
        } else {
            volumeSlider.value = videoPlayer.volume * 100;
        }
        
        updatePlayerState();
    }
    
    // Thay đổi âm lượng
    function changeVolume() {
        videoPlayer.volume = volumeSlider.value / 100;
        
        if (videoPlayer.volume === 0) {
            videoPlayer.muted = true;
        } else {
            videoPlayer.muted = false;
        }
        
        updatePlayerState();
        saveVolumePreference(videoPlayer.volume);
    }
    
    // Lưu tùy chọn âm lượng
    function saveVolumePreference(volume) {
        localStorage.setItem('preferred_volume', volume);
    }
    
    // Cập nhật tiến độ video
    function updateProgress() {
        if (isUserSeeking) return;
        
        const currentTime = videoPlayer.currentTime;
        const duration = videoPlayer.duration;
        
        if (duration) {
            // Cập nhật thanh tiến trình
            const progressPercent = (currentTime / duration) * 100;
            progressBar.value = progressPercent;
            progressBar.style.background = `linear-gradient(to right, var(--primary-color) ${progressPercent}%, #ccc ${progressPercent}%)`;
            
            // Cập nhật thời gian hiện tại
            currentTimeElement.textContent = formatTime(currentTime);
        }
    }
    
    // Cập nhật thời lượng video
    function updateDuration() {
        durationElement.textContent = formatTime(videoPlayer.duration);
    }
    
    // Đặt tiến độ video khi nhấp vào thanh tiến trình
    function setProgress(e) {
        const progressBarRect = progressContainer.getBoundingClientRect();
        const clickX = e.clientX - progressBarRect.left;
        const progressBarWidth = progressBarRect.width;
        const progressPercent = (clickX / progressBarWidth) * 100;
        
        progressBar.value = progressPercent;
        videoPlayer.currentTime = (progressPercent / 100) * videoPlayer.duration;
    }
    
    // Tính toán thời gian tìm kiếm dựa trên giá trị phần trăm
    function calculateSeekTime(percent) {
        return (percent / 100) * videoPlayer.duration;
    }
    
    // Hiển thị preview khi tìm kiếm (nếu có)
    function showSeekPreview(seekTime) {
        // Hiện tại chỉ hiển thị thời gian, có thể mở rộng để hiển thị thumbnail
        // (Yêu cầu API hoặc trích xuất hình ảnh từ video)
    }
    
    // Chuyển đổi giây sang định dạng thời gian (mm:ss hoặc hh:mm:ss)
    function formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) {
            return '00:00';
        }
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    }
    
    // Chuyển đổi chế độ toàn màn hình
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            if (videoContainer.requestFullscreen) {
                videoContainer.requestFullscreen();
            } else if (videoContainer.mozRequestFullScreen) {
                videoContainer.mozRequestFullScreen();
            } else if (videoContainer.webkitRequestFullscreen) {
                videoContainer.webkitRequestFullscreen();
            } else if (videoContainer.msRequestFullscreen) {
                videoContainer.msRequestFullscreen();
            }
            fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        }
    }
    
    // Sự kiện khi thoát khỏi chế độ toàn màn hình
    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('mozfullscreenchange', updateFullscreenButton);
    document.addEventListener('MSFullscreenChange', updateFullscreenButton);
    
    function updateFullscreenButton() {
        if (document.fullscreenElement || 
            document.webkitFullscreenElement || 
            document.mozFullScreenElement || 
            document.msFullscreenElement) {
            fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        } else {
            fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        }
    }
    
    // Hiển thị/ẩn menu cài đặt
    function toggleSettings() {
        settingsMenu.classList.toggle('active');
    }
    
    // Khởi tạo menu cài đặt
    function initializeSettingsMenu() {
        // Tạo tùy chọn chất lượng video
        if (qualityOptions) {
            const qualities = videoPlayer.getAttribute('data-qualities')?.split(',') || ['360p', '480p', '720p', '1080p'];
            
            // Tạo các phần tử tùy chọn
            qualityOptions.innerHTML = '';
            qualities.forEach(quality => {
                const option = document.createElement('div');
                option.className = 'setting-option';
                option.textContent = quality;
                
                if (quality === currentQuality) {
                    option.classList.add('active');
                }
                
                option.addEventListener('click', function() {
                    changeQuality(quality);
                    
                    // Cập nhật trạng thái active
                    document.querySelectorAll('#quality-options .setting-option').forEach(el => {
                        el.classList.remove('active');
                    });
                    option.classList.add('active');
                    
                    // Đóng menu
                    settingsMenu.classList.remove('active');
                });
                
                qualityOptions.appendChild(option);
            });
        }
        
        // Tạo tùy chọn phụ đề
        if (subtitleOptions) {
            const subtitles = videoPlayer.getAttribute('data-subtitles')?.split(',') || ['off', 'vi', 'en'];
            
            // Tạo các phần tử tùy chọn
            subtitleOptions.innerHTML = '';
            subtitles.forEach(subtitle => {
                const option = document.createElement('div');
                option.className = 'setting-option';
                
                // Hiển thị tên ngôn ngữ
                let subtitleText;
                switch (subtitle) {
                    case 'off':
                        subtitleText = 'Tắt phụ đề';
                        break;
                    case 'vi':
                        subtitleText = 'Tiếng Việt';
                        break;
                    case 'en':
                        subtitleText = 'Tiếng Anh';
                        break;
                    default:
                        subtitleText = subtitle;
                }
                
                option.textContent = subtitleText;
                
                if (subtitle === currentSubtitle) {
                    option.classList.add('active');
                }
                
                option.addEventListener('click', function() {
                    changeSubtitle(subtitle);
                    
                    // Cập nhật trạng thái active
                    document.querySelectorAll('#subtitle-options .setting-option').forEach(el => {
                        el.classList.remove('active');
                    });
                    option.classList.add('active');
                    
                    // Đóng menu
                    settingsMenu.classList.remove('active');
                });
                
                subtitleOptions.appendChild(option);
            });
        }
        
        // Tạo tùy chọn tốc độ phát
        if (playbackSpeedOptions) {
            const speeds = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];
            
            // Tạo các phần tử tùy chọn
            playbackSpeedOptions.innerHTML = '';
            speeds.forEach(speed => {
                const option = document.createElement('div');
                option.className = 'setting-option';
                option.textContent = speed === 1 ? 'Bình thường' : `${speed}x`;
                
                if (speed === currentPlaybackSpeed) {
                    option.classList.add('active');
                }
                
                option.addEventListener('click', function() {
                    changePlaybackSpeed(speed);
                    
                    // Cập nhật trạng thái active
                    document.querySelectorAll('#playback-speed-options .setting-option').forEach(el => {
                        el.classList.remove('active');
                    });
                    option.classList.add('active');
                    
                    // Đóng menu
                    settingsMenu.classList.remove('active');
                });
                
                playbackSpeedOptions.appendChild(option);
            });
        }
    }
    
    // Thay đổi chất lượng video
    function changeQuality(quality) {
        const currentTime = videoPlayer.currentTime;
        const isPaused = videoPlayer.paused;
        
        // Nếu đang xem video từ Google Drive hoặc có nhiều nguồn khác nhau
        // Thay đổi nguồn video
        const qualitySources = {
            '360p': videoPlayer.getAttribute('data-source-360'),
            '480p': videoPlayer.getAttribute('data-source-480'),
            '720p': videoPlayer.getAttribute('data-source-720'),
            '1080p': videoPlayer.getAttribute('data-source-1080'),
            '4k': videoPlayer.getAttribute('data-source-4k')
        };
        
        if (qualitySources[quality]) {
            videoPlayer.src = qualitySources[quality];
            
            // Khôi phục thời gian và trạng thái phát
            videoPlayer.addEventListener('loadedmetadata', function onLoaded() {
                videoPlayer.currentTime = currentTime;
                
                if (!isPaused) {
                    videoPlayer.play();
                }
                
                videoPlayer.removeEventListener('loadedmetadata', onLoaded);
            });
            
            currentQuality = quality;
            
            // Lưu tùy chọn chất lượng
            saveQualityPreference(quality);
        }
        
        // Hiển thị thông báo
        if (window.showToast) {
            window.showToast(`Đã chuyển sang chất lượng ${quality}`, 'info');
        }
    }
    
    // Thay đổi phụ đề
    function changeSubtitle(subtitle) {
        // Tắt tất cả các phụ đề
        const tracks = videoPlayer.textTracks;
        for (let i = 0; i < tracks.length; i++) {
            tracks[i].mode = 'disabled';
        }
        
        // Bật phụ đề được chọn
        if (subtitle !== 'off') {
            for (let i = 0; i < tracks.length; i++) {
                if (tracks[i].language === subtitle) {
                    tracks[i].mode = 'showing';
                    break;
                }
            }
        }
        
        currentSubtitle = subtitle;
        
        // Lưu tùy chọn phụ đề
        saveSubtitlePreference(subtitle);
        
        // Hiển thị thông báo
        if (window.showToast) {
            let subtitleText;
            switch (subtitle) {
                case 'off':
                    subtitleText = 'Đã tắt phụ đề';
                    break;
                case 'vi':
                    subtitleText = 'Phụ đề Tiếng Việt';
                    break;
                case 'en':
                    subtitleText = 'Phụ đề Tiếng Anh';
                    break;
                default:
                    subtitleText = `Phụ đề ${subtitle}`;
            }
            window.showToast(subtitleText, 'info');
        }
    }
    
    // Thay đổi tốc độ phát
    function changePlaybackSpeed(speed) {
        videoPlayer.playbackRate = speed;
        currentPlaybackSpeed = speed;
        
        // Lưu tùy chọn tốc độ phát
        savePlaybackSpeedPreference(speed);
        
        // Hiển thị thông báo
        if (window.showToast) {
            window.showToast(`Tốc độ phát: ${speed === 1 ? 'Bình thường' : speed + 'x'}`, 'info');
        }
    }
    
    // Lưu tùy chọn chất lượng
    function saveQualityPreference(quality) {
        localStorage.setItem('preferred_quality', quality);
        
        // Nếu đã đăng nhập, lưu vào cơ sở dữ liệu
        saveUserPreference('quality', quality);
    }
    
    // Lưu tùy chọn phụ đề
    function saveSubtitlePreference(subtitle) {
        localStorage.setItem('preferred_subtitle', subtitle);
        
        // Nếu đã đăng nhập, lưu vào cơ sở dữ liệu
        saveUserPreference('subtitle', subtitle);
    }
    
    // Lưu tùy chọn tốc độ phát
    function savePlaybackSpeedPreference(speed) {
        localStorage.setItem('preferred_playback_speed', speed);
    }
    
    // Lưu tùy chọn người dùng vào cơ sở dữ liệu
    function saveUserPreference(type, value) {
        // Kiểm tra xem người dùng đã đăng nhập hay chưa
        const isLoggedIn = document.querySelector('.user-menu') !== null;
        
        if (isLoggedIn) {
            let endpoint = '';
            
            switch (type) {
                case 'subtitle':
                    endpoint = 'ajax/save_subtitle_preference.php';
                    break;
                case 'quality':
                    endpoint = 'ajax/save_quality_preference.php';
                    break;
                case 'audio':
                    endpoint = 'ajax/save_audio_preference.php';
                    break;
            }
            
            if (endpoint) {
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `value=${value}`
                })
                .catch(error => {
                    console.error('Error saving preference:', error);
                });
            }
        }
    }
    
    // Hiển thị biểu tượng loading
    function showLoading() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'flex';
        }
    }
    
    // Ẩn biểu tượng loading
    function hideLoading() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }
    
    // Xử lý khi video kết thúc
    function onVideoEnded() {
        // Hiển thị overlay kết thúc video
        const videoEndedOverlay = document.querySelector('.video-ended-overlay');
        
        if (videoEndedOverlay) {
            videoEndedOverlay.style.display = 'flex';
        }
        
        // Đánh dấu video đã xem
        markVideoAsWatched();
        
        // Kiểm tra và tự động phát tập tiếp theo (nếu có)
        playNextEpisode();
    }
    
    // Đánh dấu video đã xem
    function markVideoAsWatched() {
        if (!episodeId) return;
        
        fetch('ajax/mark_as_watched.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `episode_id=${episodeId}&movie_id=${movieId}`
        })
        .catch(error => {
            console.error('Error marking video as watched:', error);
        });
    }
    
    // Tự động phát tập tiếp theo
    function playNextEpisode() {
        const nextEpisodeBtn = document.querySelector('.next-episode-btn');
        
        if (nextEpisodeBtn) {
            const nextEpisodeUrl = nextEpisodeBtn.getAttribute('href');
            
            if (nextEpisodeUrl) {
                const autoplayCheckbox = document.getElementById('autoplay-checkbox');
                
                // Nếu chức năng autoplay được bật
                if (autoplayCheckbox && autoplayCheckbox.checked) {
                    // Hiển thị đếm ngược
                    const countdownElement = document.querySelector('.next-episode-countdown');
                    if (countdownElement) {
                        let countdown = 10;
                        countdownElement.textContent = countdown;
                        countdownElement.style.display = 'inline';
                        
                        const countdownInterval = setInterval(() => {
                            countdown--;
                            countdownElement.textContent = countdown;
                            
                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                window.location.href = nextEpisodeUrl;
                            }
                        }, 1000);
                        
                        // Cho phép người dùng hủy tự động phát
                        const cancelAutoplayBtn = document.querySelector('.cancel-autoplay-btn');
                        if (cancelAutoplayBtn) {
                            cancelAutoplayBtn.addEventListener('click', () => {
                                clearInterval(countdownInterval);
                                countdownElement.style.display = 'none';
                            });
                        }
                    } else {
                        // Nếu không có phần tử đếm ngược, chuyển hướng ngay lập tức sau 3 giây
                        setTimeout(() => {
                            window.location.href = nextEpisodeUrl;
                        }, 3000);
                    }
                }
            }
        }
    }
    
    // Lưu tiến độ xem video
    function saveVideoProgress() {
        if (!videoPlayer || !episodeId || !movieId) return;
        
        const currentTime = videoPlayer.currentTime;
        const duration = videoPlayer.duration;
        
        // Chỉ lưu nếu thời gian hiện tại và thời lượng hợp lệ
        if (isNaN(currentTime) || !isFinite(currentTime) || isNaN(duration) || !isFinite(duration)) {
            return;
        }
        
        // Chỉ lưu nếu đã xem được ít nhất 5 giây
        if (currentTime < 5) {
            return;
        }
        
        // Chỉ lưu nếu thời gian đã thay đổi đáng kể so với lần lưu trước
        if (Math.abs(currentTime - lastSavedTime) < 5) {
            return;
        }
        
        // Tính toán phần trăm tiến độ
        const progress = Math.round((currentTime / duration) * 100);
        
        // Lưu vào localStorage cho người dùng chưa đăng nhập
        localStorage.setItem(`progress_${episodeId}`, currentTime);
        
        // Gửi yêu cầu lưu tiến độ
        fetch('ajax/save_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `episode_id=${episodeId}&movie_id=${movieId}&progress=${currentTime}&duration=${duration}`
        })
        .then(response => {
            if (response.ok) {
                lastSavedTime = currentTime;
            }
        })
        .catch(error => {
            console.error('Error saving progress:', error);
        });
    }
    
    // Khôi phục tiến độ xem video
    function restoreVideoProgress() {
        if (!videoPlayer || !episodeId) return;
        
        // Hiển thị loading
        showLoading();
        
        // Kiểm tra xem người dùng đã đăng nhập hay chưa
        const isLoggedIn = document.querySelector('.user-menu') !== null;
        
        if (isLoggedIn) {
            // Nếu đã đăng nhập, lấy tiến độ từ cơ sở dữ liệu
            fetch(`ajax/get_progress.php?episode_id=${episodeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.progress > 0) {
                    // Nếu đã xem hơn 95%, bắt đầu từ đầu
                    if (data.progress / data.duration > 0.95) {
                        videoPlayer.currentTime = 0;
                    } else {
                        videoPlayer.currentTime = data.progress;
                        
                        // Hiển thị thông báo
                        showResumePopup(data.progress);
                    }
                } else {
                    // Nếu không có tiến độ, kiểm tra localStorage
                    checkLocalStorageProgress();
                }
            })
            .catch(error => {
                console.error('Error getting progress:', error);
                checkLocalStorageProgress();
            });
        } else {
            // Nếu chưa đăng nhập, lấy tiến độ từ localStorage
            checkLocalStorageProgress();
        }
    }
    
    // Kiểm tra tiến độ trong localStorage
    function checkLocalStorageProgress() {
        const savedProgress = localStorage.getItem(`progress_${episodeId}`);
        
        if (savedProgress) {
            const progress = parseFloat(savedProgress);
            
            // Chỉ khôi phục nếu tiến độ lớn hơn 5 giây
            if (progress > 5) {
                videoPlayer.currentTime = progress;
                
                // Hiển thị thông báo
                showResumePopup(progress);
            }
        }
    }
    
    // Hiển thị popup tiếp tục xem
    function showResumePopup(progress) {
        const resumePopup = document.querySelector('.resume-popup');
        
        if (resumePopup) {
            // Hiển thị thời gian đã xem
            const resumeTime = resumePopup.querySelector('.resume-time');
            if (resumeTime) {
                resumeTime.textContent = formatTime(progress);
            }
            
            // Hiển thị popup
            resumePopup.style.display = 'flex';
            
            // Thiết lập thời gian ẩn tự động
            setTimeout(() => {
                resumePopup.style.display = 'none';
            }, 5000);
            
            // Xử lý nút tiếp tục
            const resumeBtn = resumePopup.querySelector('.resume-btn');
            if (resumeBtn) {
                resumeBtn.addEventListener('click', () => {
                    videoPlayer.currentTime = progress;
                    videoPlayer.play();
                    resumePopup.style.display = 'none';
                });
            }
            
            // Xử lý nút bắt đầu lại
            const restartBtn = resumePopup.querySelector('.restart-btn');
            if (restartBtn) {
                restartBtn.addEventListener('click', () => {
                    videoPlayer.currentTime = 0;
                    videoPlayer.play();
                    resumePopup.style.display = 'none';
                });
            }
        }
    }
    
    // Xử lý phím tắt
    function handleKeyPress(e) {
        // Chỉ xử lý nếu không đang nhập vào input/textarea
        if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA')) {
            return;
        }
        
        switch (e.key) {
            case ' ':
            case 'k':
                // Phát/tạm dừng video
                e.preventDefault();
                togglePlayPause();
                break;
            case 'f':
                // Toàn màn hình
                e.preventDefault();
                toggleFullscreen();
                break;
            case 'm':
                // Tắt/bật âm thanh
                e.preventDefault();
                toggleMute();
                break;
            case 'ArrowRight':
                // Tua nhanh 10 giây
                e.preventDefault();
                videoPlayer.currentTime += 10;
                break;
            case 'ArrowLeft':
                // Tua lùi 10 giây
                e.preventDefault();
                videoPlayer.currentTime -= 10;
                break;
            case 'ArrowUp':
                // Tăng âm lượng
                e.preventDefault();
                if (videoPlayer.volume + 0.1 > 1) {
                    videoPlayer.volume = 1;
                } else {
                    videoPlayer.volume += 0.1;
                }
                volumeSlider.value = videoPlayer.volume * 100;
                updatePlayerState();
                break;
            case 'ArrowDown':
                // Giảm âm lượng
                e.preventDefault();
                if (videoPlayer.volume - 0.1 < 0) {
                    videoPlayer.volume = 0;
                } else {
                    videoPlayer.volume -= 0.1;
                }
                volumeSlider.value = videoPlayer.volume * 100;
                updatePlayerState();
                break;
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                // Nhảy đến phần trăm của video
                e.preventDefault();
                const percent = parseInt(e.key) * 10;
                videoPlayer.currentTime = videoPlayer.duration * (percent / 100);
                break;
        }
    }
    
    // Khởi tạo player khi tải xong trang
    initializePlayer();
});