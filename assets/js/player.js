/**
 * Lọc Phim - Custom Video Player
 * Hỗ trợ nhiều độ phân giải, quảng cáo, theo dõi thời gian xem
 */

// Khởi tạo trình phát video
function initializePlayer(videoId, options = {}) {
    // Cấu hình mặc định
    const defaultOptions = {
        autoplay: false,
        muted: false,
        loop: false,
        controls: true,
        preload: 'auto',
        poster: '',
        sources: [],
        defaultQuality: '720p',
        currentTime: 0,
        showAds: true,
        adInterval: 15 * 60, // 15 phút
        saveProgress: true,
        saveInterval: 30, // 30 giây
        autoNext: true,
        nextEpisodeUrl: ''
    };
    
    // Merge các tùy chọn
    const playerOptions = Object.assign({}, defaultOptions, options);
    
    // Lấy phần tử video
    const player = document.getElementById(videoId);
    if (!player) {
        console.error('Không tìm thấy phần tử video với ID: ' + videoId);
        return null;
    }
    
    // Thiết lập các thuộc tính cơ bản
    player.autoplay = playerOptions.autoplay;
    player.muted = playerOptions.muted;
    player.loop = playerOptions.loop;
    player.controls = playerOptions.controls;
    player.preload = playerOptions.preload;
    player.poster = playerOptions.poster;
    
    // Thêm các nguồn video
    if (playerOptions.sources && playerOptions.sources.length > 0) {
        addSources(player, playerOptions.sources, playerOptions.defaultQuality);
    }
    
    // Thiết lập thời gian bắt đầu nếu có
    if (playerOptions.currentTime > 0) {
        player.currentTime = playerOptions.currentTime;
    }
    
    // Khởi tạo trình điều khiển video
    initializeVideoController(player, playerOptions);
    
    return player;
}

// Thêm các nguồn video với nhiều độ phân giải
function addSources(player, sources, defaultQuality) {
    // Xóa tất cả các nguồn hiện tại
    while (player.firstChild) {
        player.removeChild(player.firstChild);
    }
    
    // Sắp xếp các nguồn theo chất lượng (từ cao đến thấp)
    const sortedSources = sources.sort((a, b) => {
        const qualityA = parseInt(a.label.replace('p', ''));
        const qualityB = parseInt(b.label.replace('p', ''));
        return qualityB - qualityA;
    });
    
    // Tìm nguồn mặc định
    let defaultSource = sortedSources.find(source => source.label === defaultQuality);
    if (!defaultSource) {
        defaultSource = sortedSources[0];
    }
    
    // Thêm nguồn mặc định
    const source = document.createElement('source');
    source.src = defaultSource.src;
    source.type = defaultSource.type || 'video/mp4';
    source.dataset.quality = defaultSource.label;
    player.appendChild(source);
    
    // Lưu tất cả các nguồn vào dataset của player
    player.dataset.sources = JSON.stringify(sortedSources);
    player.dataset.currentQuality = defaultSource.label;
}

// Khởi tạo trình điều khiển video
function initializeVideoController(player, options) {
    // Tạo container cho trình điều khiển
    const videoContainer = player.parentElement;
    if (!videoContainer.classList.contains('video-container')) {
        videoContainer.classList.add('video-container');
    }
    
    // Biến theo dõi trạng thái
    let isPlaying = false;
    let isMuted = player.muted;
    let currentVolume = player.volume;
    let isFullscreen = false;
    let showingControls = false;
    let controlsTimeout = null;
    let saveProgressInterval = null;
    let lastAdTime = 0;
    
    // Thêm overlay điều khiển
    const controlsOverlay = document.createElement('div');
    controlsOverlay.className = 'video-controls-overlay';
    videoContainer.appendChild(controlsOverlay);
    
    // Tạo các nút điều khiển
    const controlsHTML = `
        <div class="video-progress-container">
            <div class="video-progress-bar">
                <div class="video-progress-fill"></div>
                <div class="video-progress-handler"></div>
            </div>
            <div class="video-time">
                <span class="video-current-time">00:00</span> / <span class="video-duration">00:00</span>
            </div>
        </div>
        <div class="video-controls">
            <button class="video-play-pause">
                <i class="fas fa-play"></i>
            </button>
            <div class="video-volume-container">
                <button class="video-mute">
                    <i class="fas fa-volume-up"></i>
                </button>
                <div class="video-volume-slider">
                    <div class="video-volume-fill"></div>
                    <div class="video-volume-handler"></div>
                </div>
            </div>
            <div class="video-quality-selector">
                <button class="video-quality-button">
                    <span class="video-current-quality">720p</span>
                    <i class="fas fa-caret-down"></i>
                </button>
                <div class="video-quality-menu"></div>
            </div>
            <button class="video-fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    `;
    controlsOverlay.innerHTML = controlsHTML;
    
    // Lấy các phần tử điều khiển
    const playPauseButton = controlsOverlay.querySelector('.video-play-pause');
    const muteButton = controlsOverlay.querySelector('.video-mute');
    const volumeSlider = controlsOverlay.querySelector('.video-volume-slider');
    const volumeFill = controlsOverlay.querySelector('.video-volume-fill');
    const volumeHandler = controlsOverlay.querySelector('.video-volume-handler');
    const progressBar = controlsOverlay.querySelector('.video-progress-bar');
    const progressFill = controlsOverlay.querySelector('.video-progress-fill');
    const progressHandler = controlsOverlay.querySelector('.video-progress-handler');
    const currentTimeDisplay = controlsOverlay.querySelector('.video-current-time');
    const durationDisplay = controlsOverlay.querySelector('.video-duration');
    const fullscreenButton = controlsOverlay.querySelector('.video-fullscreen');
    const qualityButton = controlsOverlay.querySelector('.video-quality-button');
    const qualityMenu = controlsOverlay.querySelector('.video-quality-menu');
    const currentQualityDisplay = controlsOverlay.querySelector('.video-current-quality');
    
    // Cập nhật hiển thị chất lượng hiện tại
    currentQualityDisplay.textContent = player.dataset.currentQuality || options.defaultQuality;
    
    // Thêm danh sách chất lượng
    if (options.sources && options.sources.length > 0) {
        options.sources.forEach(source => {
            const qualityItem = document.createElement('div');
            qualityItem.className = 'video-quality-item';
            qualityItem.textContent = source.label;
            qualityItem.dataset.quality = source.label;
            qualityItem.dataset.src = source.src;
            qualityItem.dataset.type = source.type || 'video/mp4';
            qualityMenu.appendChild(qualityItem);
            
            // Đánh dấu chất lượng hiện tại
            if (source.label === player.dataset.currentQuality) {
                qualityItem.classList.add('active');
            }
            
            // Sự kiện chọn chất lượng
            qualityItem.addEventListener('click', () => {
                changeQuality(source.label);
                qualityMenu.classList.remove('show');
            });
        });
    }
    
    // Sự kiện hiển thị menu chất lượng
    qualityButton.addEventListener('click', () => {
        qualityMenu.classList.toggle('show');
    });
    
    // Ẩn menu chất lượng khi nhấp ra ngoài
    document.addEventListener('click', (e) => {
        if (!qualityButton.contains(e.target) && !qualityMenu.contains(e.target)) {
            qualityMenu.classList.remove('show');
        }
    });
    
    // Hàm thay đổi chất lượng video
    function changeQuality(quality) {
        const currentTime = player.currentTime;
        const isPaused = player.paused;
        const sources = JSON.parse(player.dataset.sources);
        const selectedSource = sources.find(source => source.label === quality);
        
        if (selectedSource) {
            // Cập nhật nguồn video
            const sourceElement = player.querySelector('source');
            sourceElement.src = selectedSource.src;
            sourceElement.type = selectedSource.type || 'video/mp4';
            sourceElement.dataset.quality = selectedSource.label;
            
            // Lưu chất lượng hiện tại
            player.dataset.currentQuality = selectedSource.label;
            currentQualityDisplay.textContent = selectedSource.label;
            
            // Cập nhật UI
            qualityMenu.querySelectorAll('.video-quality-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.quality === selectedSource.label) {
                    item.classList.add('active');
                }
            });
            
            // Tải lại video và khôi phục trạng thái
            player.load();
            player.currentTime = currentTime;
            
            if (!isPaused) {
                player.play().catch(error => {
                    console.error('Lỗi khi phát video sau khi thay đổi chất lượng:', error);
                });
            }
        }
    }
    
    // Hàm kiểm tra giới hạn độ phân giải dựa trên cấp VIP
    function limitResolution() {
        if (options.vipLevel && options.sources && options.sources.length > 0) {
            let maxAllowedQuality = '480p'; // Mặc định cho người dùng thường
            
            // Lấy giới hạn chất lượng dựa trên cấp VIP
            if (options.vipLevel > 0) {
                const vipConfig = {
                    1: '720p',
                    2: '1080p',
                    3: '4K'
                };
                maxAllowedQuality = vipConfig[options.vipLevel] || '1080p';
            }
            
            // Lọc các nguồn cho phép dựa trên cấp VIP
            const maxQualityValue = parseInt(maxAllowedQuality.replace('p', '').replace('K', '000'));
            const allowedSources = options.sources.filter(source => {
                const qualityValue = parseInt(source.label.replace('p', '').replace('K', '000'));
                return qualityValue <= maxQualityValue;
            });
            
            // Cập nhật danh sách chất lượng
            qualityMenu.innerHTML = '';
            allowedSources.forEach(source => {
                const qualityItem = document.createElement('div');
                qualityItem.className = 'video-quality-item';
                qualityItem.textContent = source.label;
                qualityItem.dataset.quality = source.label;
                qualityItem.dataset.src = source.src;
                qualityItem.dataset.type = source.type || 'video/mp4';
                qualityMenu.appendChild(qualityItem);
                
                // Đánh dấu chất lượng hiện tại
                if (source.label === player.dataset.currentQuality) {
                    qualityItem.classList.add('active');
                }
                
                // Sự kiện chọn chất lượng
                qualityItem.addEventListener('click', () => {
                    changeQuality(source.label);
                    qualityMenu.classList.remove('show');
                });
            });
            
            // Cập nhật player.dataset.sources
            player.dataset.sources = JSON.stringify(allowedSources);
            
            // Kiểm tra nếu chất lượng hiện tại vượt quá giới hạn
            const currentQuality = player.dataset.currentQuality;
            const currentQualityValue = parseInt(currentQuality.replace('p', '').replace('K', '000'));
            
            if (currentQualityValue > maxQualityValue) {
                // Thay đổi xuống chất lượng cho phép cao nhất
                changeQuality(maxAllowedQuality);
            }
        }
    }
    
    // Giới hạn độ phân giải dựa trên cấp VIP
    limitResolution();
    
    // Sự kiện Play/Pause
    playPauseButton.addEventListener('click', () => {
        togglePlayPause();
    });
    
    // Nhấp vào video để play/pause
    player.addEventListener('click', () => {
        togglePlayPause();
    });
    
    // Hàm toggle play/pause
    function togglePlayPause() {
        if (player.paused) {
            player.play().catch(error => {
                console.error('Lỗi khi phát video:', error);
            });
        } else {
            player.pause();
        }
    }
    
    // Sự kiện khi video đang phát
    player.addEventListener('play', () => {
        isPlaying = true;
        playPauseButton.innerHTML = '<i class="fas fa-pause"></i>';
        
        // Bắt đầu lưu tiến trình xem
        if (options.saveProgress) {
            startSavingProgress();
        }
    });
    
    // Sự kiện khi video tạm dừng
    player.addEventListener('pause', () => {
        isPlaying = false;
        playPauseButton.innerHTML = '<i class="fas fa-play"></i>';
    });
    
    // Sự kiện Volume Mute/Unmute
    muteButton.addEventListener('click', () => {
        toggleMute();
    });
    
    // Hàm toggle mute
    function toggleMute() {
        player.muted = !player.muted;
        updateVolumeUI();
    }
    
    // Cập nhật UI âm lượng
    function updateVolumeUI() {
        isMuted = player.muted;
        
        if (isMuted) {
            muteButton.innerHTML = '<i class="fas fa-volume-mute"></i>';
            volumeFill.style.width = '0%';
            volumeHandler.style.left = '0%';
        } else {
            const volumeLevel = player.volume;
            volumeFill.style.width = (volumeLevel * 100) + '%';
            volumeHandler.style.left = (volumeLevel * 100) + '%';
            
            if (volumeLevel > 0.5) {
                muteButton.innerHTML = '<i class="fas fa-volume-up"></i>';
            } else if (volumeLevel > 0) {
                muteButton.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                muteButton.innerHTML = '<i class="fas fa-volume-off"></i>';
            }
        }
    }
    
    // Sự kiện điều chỉnh âm lượng
    volumeSlider.addEventListener('click', (e) => {
        const rect = volumeSlider.getBoundingClientRect();
        const position = (e.clientX - rect.left) / rect.width;
        setVolume(position);
    });
    
    // Kéo thanh âm lượng
    volumeHandler.addEventListener('mousedown', (e) => {
        const startDrag = (e) => {
            const rect = volumeSlider.getBoundingClientRect();
            let position = (e.clientX - rect.left) / rect.width;
            
            // Giới hạn trong khoảng 0-1
            position = Math.max(0, Math.min(1, position));
            
            setVolume(position);
        };
        
        const stopDrag = () => {
            document.removeEventListener('mousemove', startDrag);
            document.removeEventListener('mouseup', stopDrag);
        };
        
        document.addEventListener('mousemove', startDrag);
        document.addEventListener('mouseup', stopDrag);
    });
    
    // Hàm điều chỉnh âm lượng
    function setVolume(level) {
        level = Math.max(0, Math.min(1, level));
        player.volume = level;
        currentVolume = level;
        
        if (level === 0) {
            player.muted = true;
        } else if (player.muted) {
            player.muted = false;
        }
        
        updateVolumeUI();
    }
    
    // Cập nhật thời gian và thanh tiến trình
    player.addEventListener('timeupdate', () => {
        if (!isNaN(player.duration)) {
            const currentTime = player.currentTime;
            const duration = player.duration;
            const percentage = (currentTime / duration) * 100;
            
            // Cập nhật thanh tiến trình
            progressFill.style.width = percentage + '%';
            progressHandler.style.left = percentage + '%';
            
            // Cập nhật hiển thị thời gian
            currentTimeDisplay.textContent = formatTime(currentTime);
            durationDisplay.textContent = formatTime(duration);
            
            // Kiểm tra xem có cần hiển thị quảng cáo không
            if (options.showAds && currentTime - lastAdTime >= options.adInterval) {
                showAdvertisement();
                lastAdTime = currentTime;
            }
        }
    });
    
    // Sự kiện khi video tải metadata
    player.addEventListener('loadedmetadata', () => {
        durationDisplay.textContent = formatTime(player.duration);
        
        // Hiển thị overlay tiếp tục xem nếu có thời gian đã lưu
        if (options.currentTime > 0 && options.currentTime < player.duration - 30) {
            createResumeOverlay(options.currentTime);
        }
        
        // Thêm nút chuyển tập tiếp theo nếu có
        if (options.autoNext && options.nextEpisodeUrl) {
            addAutoNextButton();
        }
    });
    
    // Sự kiện khi video kết thúc
    player.addEventListener('ended', () => {
        playPauseButton.innerHTML = '<i class="fas fa-play"></i>';
        
        // Chuyển tự động sang tập tiếp theo nếu có
        if (options.autoNext && options.nextEpisodeUrl) {
            setTimeout(() => {
                window.location.href = options.nextEpisodeUrl;
            }, 3000);
        }
    });
    
    // Sự kiện nhấp vào thanh tiến trình
    progressBar.addEventListener('click', (e) => {
        const rect = progressBar.getBoundingClientRect();
        const position = (e.clientX - rect.left) / rect.width;
        seekVideo(position);
    });
    
    // Kéo thanh tiến trình
    progressHandler.addEventListener('mousedown', (e) => {
        const startDrag = (e) => {
            const rect = progressBar.getBoundingClientRect();
            let position = (e.clientX - rect.left) / rect.width;
            
            // Giới hạn trong khoảng 0-1
            position = Math.max(0, Math.min(1, position));
            
            seekVideo(position);
        };
        
        const stopDrag = () => {
            document.removeEventListener('mousemove', startDrag);
            document.removeEventListener('mouseup', stopDrag);
        };
        
        document.addEventListener('mousemove', startDrag);
        document.addEventListener('mouseup', stopDrag);
    });
    
    // Hàm tìm kiếm video
    function seekVideo(position) {
        const seekTime = player.duration * position;
        player.currentTime = seekTime;
    }
    
    // Sự kiện toàn màn hình
    fullscreenButton.addEventListener('click', () => {
        toggleFullscreen();
    });
    
    // Hàm toggle toàn màn hình
    function toggleFullscreen() {
        if (!isFullscreen) {
            if (videoContainer.requestFullscreen) {
                videoContainer.requestFullscreen();
            } else if (videoContainer.mozRequestFullScreen) {
                videoContainer.mozRequestFullScreen();
            } else if (videoContainer.webkitRequestFullscreen) {
                videoContainer.webkitRequestFullscreen();
            } else if (videoContainer.msRequestFullscreen) {
                videoContainer.msRequestFullscreen();
            }
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
        }
    }
    
    // Sự kiện thay đổi trạng thái toàn màn hình
    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('mozfullscreenchange', updateFullscreenButton);
    document.addEventListener('MSFullscreenChange', updateFullscreenButton);
    
    // Cập nhật nút toàn màn hình
    function updateFullscreenButton() {
        isFullscreen = !!document.fullscreenElement || !!document.mozFullScreenElement || !!document.webkitFullscreenElement || !!document.msFullscreenElement;
        
        if (isFullscreen) {
            fullscreenButton.innerHTML = '<i class="fas fa-compress"></i>';
            videoContainer.classList.add('fullscreen');
        } else {
            fullscreenButton.innerHTML = '<i class="fas fa-expand"></i>';
            videoContainer.classList.remove('fullscreen');
        }
    }
    
    // Hiển thị/ẩn điều khiển khi di chuột
    videoContainer.addEventListener('mousemove', () => {
        showControls();
    });
    
    videoContainer.addEventListener('mouseleave', () => {
        hideControls();
    });
    
    // Hiển thị điều khiển
    function showControls() {
        if (!showingControls) {
            controlsOverlay.classList.add('show');
            showingControls = true;
        }
        
        // Reset timeout
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(() => {
            hideControls();
        }, 3000);
    }
    
    // Ẩn điều khiển
    function hideControls() {
        if (showingControls && !player.paused) {
            controlsOverlay.classList.remove('show');
            showingControls = false;
        }
    }
    
    // Bắt đầu lưu tiến trình xem
    function startSavingProgress() {
        if (saveProgressInterval) {
            clearInterval(saveProgressInterval);
        }
        
        saveProgressInterval = setInterval(() => {
            saveWatchHistory(player.currentTime);
        }, options.saveInterval * 1000);
    }
    
    // Dừng lưu tiến trình xem
    function stopSavingProgress() {
        if (saveProgressInterval) {
            clearInterval(saveProgressInterval);
            saveProgressInterval = null;
        }
    }
    
    // Lưu lịch sử xem
    function saveWatchHistory(currentTime) {
        // Không lưu nếu đã xem gần hết video
        if (currentTime >= player.duration - 10) {
            return;
        }
        
        // Lưu vào database thông qua API
        const data = {
            video_id: options.videoId,
            current_time: currentTime,
            duration: player.duration,
            episode_id: options.episodeId || null
        };
        
        fetch('/api/watch-history', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Đã lưu tiến trình xem:', data);
        })
        .catch(error => {
            console.error('Lỗi khi lưu tiến trình xem:', error);
        });
    }
    
    // Hiển thị quảng cáo
    function showAdvertisement() {
        if (!options.showAds) {
            return;
        }
        
        // Lưu thời gian hiện tại
        const currentTime = player.currentTime;
        
        // Tạm dừng video
        player.pause();
        
        // Tạo overlay quảng cáo
        const adOverlay = document.createElement('div');
        adOverlay.className = 'video-ad-overlay';
        adOverlay.innerHTML = `
            <div class="video-ad-container">
                <div class="video-ad-header">
                    <span>Quảng cáo</span>
                    <span class="video-ad-timer">15</span>
                </div>
                <div class="video-ad-content">
                    <img src="/assets/images/ads/ad1.jpg" alt="Quảng cáo">
                </div>
                <div class="video-ad-footer">
                    <span>Bạn có thể bỏ qua quảng cáo sau <span class="video-ad-skip-timer">5</span> giây</span>
                    <button class="video-ad-skip-button" disabled>Bỏ qua</button>
                </div>
            </div>
        `;
        videoContainer.appendChild(adOverlay);
        
        // Đếm ngược thời gian quảng cáo
        let adTime = 15;
        let skipTime = 5;
        const adTimer = adOverlay.querySelector('.video-ad-timer');
        const skipTimer = adOverlay.querySelector('.video-ad-skip-timer');
        const skipButton = adOverlay.querySelector('.video-ad-skip-button');
        
        const adInterval = setInterval(() => {
            adTime--;
            adTimer.textContent = adTime;
            
            if (skipTime > 0) {
                skipTime--;
                skipTimer.textContent = skipTime;
                
                if (skipTime === 0) {
                    skipButton.disabled = false;
                    skipButton.textContent = 'Bỏ qua quảng cáo';
                }
            }
            
            if (adTime <= 0) {
                clearInterval(adInterval);
                endAdvertisement(currentTime);
            }
        }, 1000);
        
        // Sự kiện bỏ qua quảng cáo
        skipButton.addEventListener('click', () => {
            if (!skipButton.disabled) {
                clearInterval(adInterval);
                endAdvertisement(currentTime);
            }
        });
    }
    
    // Kết thúc quảng cáo
    function endAdvertisement(resumeTime) {
        const adOverlay = videoContainer.querySelector('.video-ad-overlay');
        if (adOverlay) {
            adOverlay.remove();
        }
        
        // Tiếp tục phát video
        player.currentTime = resumeTime;
        player.play().catch(error => {
            console.error('Lỗi khi tiếp tục phát video sau quảng cáo:', error);
        });
    }
    
    // Tạo overlay tiếp tục xem
    function createResumeOverlay(savedTime) {
        const resumeOverlay = document.createElement('div');
        resumeOverlay.className = 'video-resume-overlay';
        resumeOverlay.innerHTML = `
            <div class="video-resume-container">
                <span>Bạn đã xem đến ${formatTime(savedTime)}</span>
                <div class="video-resume-buttons">
                    <button class="video-resume-continue">Tiếp tục xem</button>
                    <button class="video-resume-restart">Xem từ đầu</button>
                </div>
            </div>
        `;
        videoContainer.appendChild(resumeOverlay);
        
        // Sự kiện nút tiếp tục xem
        resumeOverlay.querySelector('.video-resume-continue').addEventListener('click', () => {
            player.currentTime = savedTime;
            resumeOverlay.remove();
            player.play().catch(error => {
                console.error('Lỗi khi tiếp tục phát video:', error);
            });
        });
        
        // Sự kiện nút xem từ đầu
        resumeOverlay.querySelector('.video-resume-restart').addEventListener('click', () => {
            player.currentTime = 0;
            resumeOverlay.remove();
            player.play().catch(error => {
                console.error('Lỗi khi phát video từ đầu:', error);
            });
        });
        
        // Tự động đóng sau 10 giây
        setTimeout(() => {
            if (videoContainer.contains(resumeOverlay)) {
                resumeOverlay.remove();
            }
        }, 10000);
    }
    
    // Thêm nút chuyển tập tiếp theo
    function addAutoNextButton() {
        const autoNextButton = document.createElement('button');
        autoNextButton.className = 'video-auto-next-button';
        autoNextButton.innerHTML = 'Tự động phát tập tiếp theo: <span>BẬT</span>';
        videoContainer.appendChild(autoNextButton);
        
        // Trạng thái ban đầu
        let autoNextEnabled = options.autoNext;
        updateAutoNextButtonState();
        
        // Sự kiện nhấp vào nút
        autoNextButton.addEventListener('click', () => {
            autoNextEnabled = !autoNextEnabled;
            options.autoNext = autoNextEnabled;
            updateAutoNextButtonState();
        });
        
        // Cập nhật trạng thái nút
        function updateAutoNextButtonState() {
            const stateText = autoNextButton.querySelector('span');
            if (autoNextEnabled) {
                stateText.textContent = 'BẬT';
                stateText.style.color = '#4CAF50';
            } else {
                stateText.textContent = 'TẮT';
                stateText.style.color = '#F44336';
            }
        }
    }
    
    // Định dạng thời gian
    function formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        
        if (h > 0) {
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        } else {
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }
    }
    
    // Sự kiện khi người dùng rời khỏi trang
    window.addEventListener('beforeunload', () => {
        // Lưu tiến trình xem lần cuối
        if (options.saveProgress && player.currentTime > 0) {
            saveWatchHistory(player.currentTime);
        }
        
        // Dừng interval
        stopSavingProgress();
    });
    
    // Khởi tạo UI ban đầu
    updateVolumeUI();
}

// Xuất các hàm để sử dụng bên ngoài
window.LocPhimPlayer = {
    initializePlayer: initializePlayer
};