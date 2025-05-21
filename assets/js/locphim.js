/**
 * Lọc Phim - Main JavaScript
 */

/**
 * Khởi tạo chế độ Dark Mode
 */
function initDarkMode() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (!darkModeToggle) return;
    
    darkModeToggle.addEventListener('click', function() {
        const darkModeEnabled = document.body.classList.toggle('dark-mode');
        
        // Update cookie
        document.cookie = `dark_mode=${darkModeEnabled ? '1' : '0'}; path=/; max-age=${60*60*24*365}`;
        
        // Update icon
        this.innerHTML = darkModeEnabled ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        
        // Toggle dark mode stylesheet
        const darkModeCss = document.getElementById('dark-mode-css');
        if (darkModeEnabled && !darkModeCss) {
            const link = document.createElement('link');
            link.id = 'dark-mode-css';
            link.rel = 'stylesheet';
            link.href = '/assets/css/dark-mode.css?v=' + new Date().getTime();
            document.head.appendChild(link);
        } else if (!darkModeEnabled && darkModeCss) {
            darkModeCss.remove();
        }
        
        // Update logo
        const logoImages = document.querySelectorAll('.logo img');
        logoImages.forEach(function(img) {
            if (darkModeEnabled) {
                img.src = img.src.replace('logo.svg', 'logo-dark.svg');
            } else {
                img.src = img.src.replace('logo-dark.svg', 'logo.svg');
            }
        });
    });
}

/**
 * Khởi tạo menu mobile
 */
function initMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    
    if (!mobileMenuToggle || !mobileMenu || !mobileMenuClose) return;
    
    mobileMenuToggle.addEventListener('click', function() {
        mobileMenu.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    mobileMenuClose.addEventListener('click', function() {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
    });
}

/**
 * Khởi tạo dropdown menu cho profile
 */
function initProfileMenu() {
    const userAvatar = document.getElementById('user-avatar');
    const userDropdown = document.getElementById('user-dropdown');
    
    console.log('initProfileMenu called');
    console.log('userAvatar element:', userAvatar);
    console.log('userDropdown element:', userDropdown);
    
    if (!userAvatar || !userDropdown) {
        console.log('Missing elements for profile menu');
        return;
    }
    
    // Thêm hover effect cho desktop
    if (window.innerWidth > 768) {
        userAvatar.addEventListener('mouseenter', function() {
            userDropdown.classList.add('active');
        });
        
        userAvatar.parentElement.addEventListener('mouseleave', function() {
            setTimeout(() => {
                if (!userDropdown.matches(':hover')) {
                    userDropdown.classList.remove('active');
                }
            }, 200);
        });
        
        userDropdown.addEventListener('mouseleave', function() {
            setTimeout(() => {
                userDropdown.classList.remove('active');
            }, 200);
        });
    }
    
    // Click behavior cho cả mobile và desktop
    userAvatar.addEventListener('click', function(e) {
        console.log('Avatar clicked');
        e.stopPropagation();
        userDropdown.classList.toggle('active');
        console.log('Dropdown toggled, is active:', userDropdown.classList.contains('active'));
    });
    
    document.addEventListener('click', function(e) {
        if (!userDropdown.contains(e.target) && e.target !== userAvatar) {
            userDropdown.classList.remove('active');
        }
    });
}

/**
 * Khởi tạo video player
 */
function initVideoPlayer() {
    // Kiểm tra xem có video player không
    const videoPlayer = document.getElementById('video-player');
    if (!videoPlayer) return;
    
    // Khởi tạo video.js nếu có
    if (typeof videojs !== 'undefined') {
        const player = videojs('video-player');
        
        // Lưu thời gian xem
        const movieId = videoPlayer.dataset.movieId;
        const episodeId = videoPlayer.dataset.episodeId;
        
        if (movieId && episodeId) {
            // Cập nhật thời gian xem mỗi 30 giây
            const updateInterval = setInterval(function() {
                if (player.paused()) return;
                
                const currentTime = Math.floor(player.currentTime());
                const duration = Math.floor(player.duration());
                
                if (currentTime > 0 && duration > 0) {
                    trackWatchTime(movieId, episodeId, currentTime, duration);
                }
            }, 30000);
            
            // Lưu lại khi thoát video
            player.on('beforeunload', function() {
                const currentTime = Math.floor(player.currentTime());
                const duration = Math.floor(player.duration());
                
                if (currentTime > 0 && duration > 0) {
                    trackWatchTime(movieId, episodeId, currentTime, duration);
                }
                
                clearInterval(updateInterval);
            });
            
            // Đánh dấu hoàn thành khi xem hết video
            player.on('ended', function() {
                const duration = Math.floor(player.duration());
                trackWatchComplete(movieId, episodeId, duration);
            });
        }
        
        // Xử lý nút tắt đèn
        const lightsToggle = document.getElementById('lights-toggle');
        if (lightsToggle) {
            lightsToggle.addEventListener('click', function() {
                document.body.classList.toggle('lights-off');
            });
        }
        
        // Xử lý lựa chọn độ phân giải từ menu cài đặt
        const resolutionOptions = document.querySelectorAll('.resolution-option');
        if (resolutionOptions.length) {
            resolutionOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const serverUrl = this.dataset.serverUrl;
                    
                    // Cập nhật nguồn video
                    player.src(serverUrl);
                    player.load();
                    
                    // Cập nhật UI
                    resolutionOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Ẩn menu cài đặt
                    document.getElementById('player-settings-menu').style.display = 'none';
                });
            });
        }
        
        // Xử lý nút cài đặt
        const settingsButton = document.getElementById('player-settings-button');
        const settingsMenu = document.getElementById('player-settings-menu');
        
        if (settingsButton && settingsMenu) {
            settingsButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (settingsMenu.style.display === 'none' || !settingsMenu.style.display) {
                    settingsMenu.style.display = 'block';
                } else {
                    settingsMenu.style.display = 'none';
                }
            });
            
            // Ẩn menu cài đặt khi click ngoài menu
            document.addEventListener('click', function(e) {
                if (settingsMenu.style.display === 'block' && !settingsMenu.contains(e.target) && e.target !== settingsButton) {
                    settingsMenu.style.display = 'none';
                }
            });
        }
        
        // Xử lý nút xem phim miễn phí với quảng cáo
        const watchFreeBtn = document.getElementById('watch-free-btn');
        const videoPlayerWrapper = document.getElementById('video-player-wrapper');
        
        if (watchFreeBtn && videoPlayerWrapper) {
            watchFreeBtn.addEventListener('click', function() {
                document.querySelector('.vip-player-notice').style.display = 'none';
                videoPlayerWrapper.style.display = 'block';
                
                // Khởi tạo quảng cáo nếu có
                // ...
            });
        }
    }
}

/**
 * Lưu thời gian xem phim
 */
function trackWatchTime(movieId, episodeId, currentTime, duration) {
    fetch('/ajax/track-watch-time', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            movie_id: movieId,
            episode_id: episodeId,
            current_time: currentTime,
            duration: duration
        })
    }).catch(error => console.error('Error tracking watch time:', error));
}

/**
 * Đánh dấu đã xem xong phim
 */
function trackWatchComplete(movieId, episodeId, duration) {
    fetch('/ajax/track-watch-complete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            movie_id: movieId,
            episode_id: episodeId,
            duration: duration
        })
    }).catch(error => console.error('Error tracking watch complete:', error));
}

/**
 * Khởi tạo tab danh sách tập
 */
function initEpisodeTabs() {
    const episodeTabs = document.querySelectorAll('.episode-tab');
    if (!episodeTabs.length) return;
    
    episodeTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.dataset.target;
            
            // Ẩn tất cả các tab content
            document.querySelectorAll('.episode-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Hiển thị tab được chọn
            document.getElementById(targetId).classList.add('active');
            
            // Cập nhật trạng thái active của tab
            episodeTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

/**
 * Khởi tạo tab profile
 */
function initProfileTabs() {
    const profileTabs = document.querySelectorAll('.profile-tab, .nav-item');
    if (!profileTabs.length) return;
    
    profileTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Đối với nav-item, chỉ xử lý khi không phải là logout
            if (tab.classList.contains('nav-item') && tab.classList.contains('logout')) {
                return;
            }
            
            if (tab.classList.contains('nav-item') && tab.getAttribute('href')) {
                // Đối với nav-item, đừng xử lý click nếu đã có href
                return;
            }
            
            e.preventDefault();
            const targetId = this.dataset.target;
            
            // Ẩn tất cả các tab content
            document.querySelectorAll('.profile-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Hiển thị tab được chọn
            if (targetId && document.getElementById(targetId)) {
                document.getElementById(targetId).classList.add('active');
            }
            
            // Cập nhật trạng thái active của tab
            profileTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Avatar upload
    const avatarFileInput = document.getElementById('avatar');
    const avatarButton = document.querySelector('.avatar-upload .btn-outline');
    const avatarPreview = document.querySelector('.avatar-preview');
    
    if (avatarFileInput && avatarButton && avatarPreview) {
        avatarButton.addEventListener('click', function() {
            avatarFileInput.click();
        });
        
        avatarFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Thêm class form-control cho các trường input nếu chưa có
    const inputs = document.querySelectorAll('input:not([type="file"]:not([type="submit"]):not([type="button"]), textarea, select');
    inputs.forEach(input => {
        if (!input.classList.contains('form-control')) {
            input.classList.add('form-control');
        }
    });
}

/**
 * Khởi tạo chức năng admin
 */
function initAdminPanel() {
    const adminTabs = document.querySelectorAll('.admin-tab');
    if (!adminTabs.length) return;
    
    adminTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.dataset.target;
            
            // Ẩn tất cả các tab content
            document.querySelectorAll('.admin-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Hiển thị tab được chọn
            document.getElementById(targetId).classList.add('active');
            
            // Cập nhật trạng thái active của tab
            adminTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

/**
 * Khởi tạo slider cho hero section
 */
function initHeroSlider() {
    const heroSlider = document.getElementById('hero-slider');
    if (!heroSlider) return;
    
    const slides = heroSlider.querySelectorAll('.hero-slide');
    const dots = heroSlider.querySelectorAll('.hero-dot');
    const prevBtn = document.getElementById('hero-prev');
    const nextBtn = document.getElementById('hero-next');
    let currentIndex = 0;
    let intervalId = null;
    
    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentIndex = index;
    }
    
    function nextSlide() {
        let nextIndex = currentIndex + 1;
        if (nextIndex >= slides.length) {
            nextIndex = 0;
        }
        showSlide(nextIndex);
    }
    
    function prevSlide() {
        let prevIndex = currentIndex - 1;
        if (prevIndex < 0) {
            prevIndex = slides.length - 1;
        }
        showSlide(prevIndex);
    }
    
    function startSlideInterval() {
        if (intervalId) {
            clearInterval(intervalId);
        }
        intervalId = setInterval(nextSlide, 5000);
    }
    
    // Initialize slider
    if (slides.length > 0) {
        startSlideInterval();
        
        // Attach event listeners
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                startSlideInterval();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                startSlideInterval();
            });
        }
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function() {
                showSlide(index);
                startSlideInterval();
            });
        });
        
        // Pause slider on hover
        heroSlider.addEventListener('mouseenter', function() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        });
        
        heroSlider.addEventListener('mouseleave', function() {
            startSlideInterval();
        });
    }
}

/**
 * Khởi tạo form comment
 */
function initCommentForm() {
    const commentForm = document.getElementById('comment-form');
    if (!commentForm) return;
    
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(commentForm);
        
        fetch('/ajax/add-comment', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset form
                commentForm.reset();
                
                // Add new comment to list
                addCommentToList(data.comment);
                
                // Show success message
                showToast('Bình luận đã được gửi thành công!', 'success');
            } else {
                showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Đã có lỗi xảy ra khi gửi bình luận.', 'error');
        });
    });
    
    // Reply buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reply-button')) {
            const commentId = e.target.closest('.reply-button').dataset.commentId;
            showReplyForm(commentId);
        }
    });
}

/**
 * Thêm comment mới vào danh sách
 */
function addCommentToList(comment) {
    const commentsList = document.querySelector('.comments-list');
    if (!commentsList) return;
    
    // Kiểm tra xem danh sách có trống không
    const noComments = commentsList.querySelector('.no-comments');
    if (noComments) {
        noComments.remove();
    }
    
    // Tạo element comment mới
    const commentElement = document.createElement('div');
    commentElement.className = 'comment';
    commentElement.id = 'comment-' + comment.id;
    
    commentElement.innerHTML = `
        <div class="comment-avatar">
            <img src="${comment.user_avatar || '/assets/images/default-avatar.svg'}" alt="${comment.username}">
        </div>
        <div class="comment-content">
            <div class="comment-header">
                <div class="comment-user">${comment.username}</div>
                <div class="comment-date">${comment.created_at_formatted}</div>
            </div>
            <div class="comment-text">${comment.content.replace(/\n/g, '<br>')}</div>
            <div class="comment-actions">
                <button class="like-button" onclick="likeComment(${comment.id})">
                    <i class="far fa-thumbs-up"></i> <span>0</span>
                </button>
                <button class="reply-button" data-comment-id="${comment.id}">
                    <i class="far fa-comment"></i> Trả lời
                </button>
            </div>
            <div class="reply-form-container" id="reply-form-${comment.id}"></div>
            <div class="replies" id="replies-${comment.id}"></div>
        </div>
    `;
    
    // Thêm comment vào đầu danh sách
    commentsList.insertBefore(commentElement, commentsList.firstChild);
}

/**
 * Xử lý thích comment
 */
function likeComment(commentId) {
    fetch('/ajax/like-comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            comment_id: commentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật số lượt thích
            const likeCount = document.querySelector(`#comment-${commentId} .like-button span`);
            if (likeCount) {
                likeCount.textContent = data.likes;
            }
            
            // Thay đổi icon nếu đã thích
            if (data.liked) {
                const likeIcon = document.querySelector(`#comment-${commentId} .like-button i`);
                if (likeIcon) {
                    likeIcon.className = 'fas fa-thumbs-up';
                }
            }
        } else {
            showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Đã có lỗi xảy ra.', 'error');
    });
}

/**
 * Hiển thị form trả lời comment
 */
function showReplyForm(commentId) {
    const replyFormContainer = document.getElementById(`reply-form-${commentId}`);
    if (!replyFormContainer) return;
    
    // Nếu form đã tồn tại, ẩn/hiện nó
    if (replyFormContainer.innerHTML.trim()) {
        replyFormContainer.innerHTML = '';
        return;
    }
    
    // Tạo form trả lời
    replyFormContainer.innerHTML = `
        <form class="reply-form" id="reply-form-${commentId}">
            <input type="hidden" name="parent_id" value="${commentId}">
            <input type="hidden" name="movie_id" value="${document.querySelector('#comment-form input[name="movie_id"]').value}">
            <input type="hidden" name="episode_id" value="${document.querySelector('#comment-form input[name="episode_id"]')?.value || ''}">
            <div class="form-group">
                <textarea name="content" placeholder="Viết trả lời của bạn..." required></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('reply-form-${commentId}').innerHTML = ''">Hủy</button>
                <button type="submit" class="btn-submit">Gửi</button>
            </div>
        </form>
    `;
    
    // Xử lý submit form
    const replyForm = document.getElementById(`reply-form-${commentId}`);
    replyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(replyForm);
        
        fetch('/ajax/add-reply', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset form và ẩn nó
                replyFormContainer.innerHTML = '';
                
                // Thêm reply mới vào list
                addReplyToComment(commentId, data.reply);
                
                // Hiển thị thông báo
                showToast('Trả lời đã được gửi thành công!', 'success');
            } else {
                showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Đã có lỗi xảy ra khi gửi trả lời.', 'error');
        });
    });
}

/**
 * Thêm reply mới vào comment
 */
function addReplyToComment(commentId, reply) {
    const repliesList = document.getElementById(`replies-${commentId}`);
    if (!repliesList) return;
    
    // Tạo element reply mới
    const replyElement = document.createElement('div');
    replyElement.className = 'reply';
    replyElement.id = `reply-${reply.id}`;
    
    replyElement.innerHTML = `
        <div class="reply-avatar">
            <img src="${reply.user_avatar || '/assets/images/default-avatar.svg'}" alt="${reply.username}">
        </div>
        <div class="reply-content">
            <div class="reply-header">
                <div class="reply-user">${reply.username}</div>
                <div class="reply-date">${reply.created_at_formatted}</div>
            </div>
            <div class="reply-text">${reply.content.replace(/\n/g, '<br>')}</div>
            <div class="reply-actions">
                <button class="like-button" onclick="likeReply(${reply.id})">
                    <i class="far fa-thumbs-up"></i> <span>0</span>
                </button>
            </div>
        </div>
    `;
    
    // Thêm reply vào cuối danh sách
    repliesList.appendChild(replyElement);
}

/**
 * Xử lý thích reply
 */
function likeReply(replyId) {
    fetch('/ajax/like-reply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            reply_id: replyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật số lượt thích
            const likeCount = document.querySelector(`#reply-${replyId} .like-button span`);
            if (likeCount) {
                likeCount.textContent = data.likes;
            }
            
            // Thay đổi icon nếu đã thích
            if (data.liked) {
                const likeIcon = document.querySelector(`#reply-${replyId} .like-button i`);
                if (likeIcon) {
                    likeIcon.className = 'fas fa-thumbs-up';
                }
            }
        } else {
            showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Đã có lỗi xảy ra.', 'error');
    });
}

/**
 * Khởi tạo nút yêu thích phim
 */
function initFavoriteButton() {
    const favoriteButtons = document.querySelectorAll('.favorite-button');
    if (!favoriteButtons.length) return;
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            toggleFavorite(this);
        });
    });
}

/**
 * Toggle yêu thích phim
 */
function toggleFavorite(button) {
    const movieId = button.dataset.movieId;
    
    fetch('/ajax/toggle-favorite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            movie_id: movieId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật trạng thái nút
            if (data.is_favorite) {
                button.classList.add('favorited');
                button.querySelector('i').className = 'fas fa-heart';
                button.querySelector('span').textContent = 'Đã yêu thích';
                showToast('Đã thêm vào danh sách yêu thích.', 'success');
            } else {
                button.classList.remove('favorited');
                button.querySelector('i').className = 'far fa-heart';
                button.querySelector('span').textContent = 'Yêu thích';
                showToast('Đã xóa khỏi danh sách yêu thích.', 'success');
            }
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Đã có lỗi xảy ra.', 'error');
    });
}

/**
 * Khởi tạo nút back to top
 */
function initBackToTop() {
    const backToTopButton = document.getElementById('back-to-top');
    if (!backToTopButton) return;
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('active');
        } else {
            backToTopButton.classList.remove('active');
        }
    });
    
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Khởi tạo theo dõi lượt xem
 */
function initViewTracking() {
    // Tự động tăng lượt xem khi vào trang chi tiết phim
    const movieDetail = document.querySelector('.movie-detail-page');
    if (movieDetail) {
        const movieId = document.querySelector('.movie-details').dataset.movieId;
        
        if (movieId) {
            // Gửi request tăng lượt xem sau 5 giây xem trang
            setTimeout(function() {
                fetch('/ajax/increment-view', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        movie_id: movieId
                    })
                }).catch(error => console.error('Error incrementing view:', error));
            }, 5000);
        }
    }
}

/**
 * Hiển thị toast message
 */
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    
    // Tạo container nếu chưa có
    if (!toastContainer) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Tạo toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon dựa vào loại thông báo
    let icon = 'info-circle';
    let title = 'Thông báo';
    
    switch (type) {
        case 'success':
            icon = 'check-circle';
            title = 'Thành công';
            break;
        case 'error':
            icon = 'exclamation-circle';
            title = 'Lỗi';
            break;
        case 'warning':
            icon = 'exclamation-triangle';
            title = 'Cảnh báo';
            break;
    }
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Thêm toast vào container
    document.querySelector('.toast-container').appendChild(toast);
    
    // Xử lý nút đóng
    toast.querySelector('.toast-close').addEventListener('click', function() {
        toast.remove();
    });
    
    // Tự động xóa sau 3 giây
    setTimeout(function() {
        toast.classList.add('fade-out');
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 3000);
}

/**
 * Helper function để set cookie
 */
function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
}

/**
 * Helper function để get cookie
 */
function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

// Khởi tạo tất cả các chức năng khi tài liệu đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initMobileMenu();
    initProfileMenu();
    initVideoPlayer();
    initEpisodeTabs();
    initProfileTabs();
    initAdminPanel();
    initHeroSlider();
    initCommentForm();
    initFavoriteButton();
    initBackToTop();
    initViewTracking();
    
    // Khởi tạo search box
    const searchToggle = document.getElementById('search-toggle');
    const searchForm = document.getElementById('search-form');
    
    if (searchToggle && searchForm) {
        searchToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            searchForm.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target) && e.target !== searchToggle) {
                searchForm.classList.remove('active');
            }
        });
    }
    
    // Xử lý modal báo cáo
    const reportBtn = document.querySelector('.report-button');
    const reportModal = document.getElementById('report-modal');
    
    if (reportBtn && reportModal) {
        reportBtn.addEventListener('click', function() {
            reportModal.classList.add('active');
        });
        
        const modalClose = reportModal.querySelector('.modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', function() {
                reportModal.classList.remove('active');
            });
        }
        
        const cancelReport = document.getElementById('cancel-report');
        if (cancelReport) {
            cancelReport.addEventListener('click', function() {
                reportModal.classList.remove('active');
            });
        }
        
        reportModal.querySelector('.modal-overlay').addEventListener('click', function() {
            reportModal.classList.remove('active');
        });
        
        const reportForm = document.getElementById('report-form');
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(reportForm);
                
                fetch('/ajax/report-issue', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Báo cáo lỗi đã được gửi thành công!', 'success');
                        reportModal.classList.remove('active');
                        reportForm.reset();
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra khi gửi báo cáo.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi gửi báo cáo.', 'error');
                });
            });
        }
    }
});