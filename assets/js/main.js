/**
 * Lọc Phim - Main JavaScript
 * 
 * File JavaScript chính cho trang web Lọc Phim
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo theme
    initTheme();
    
    // Khởi tạo swiper slider nếu có trong trang
    if (document.querySelector('.swiper')) {
        initSwipers();
    }
    
    // Khởi tạo menu mobile
    initMobileMenu();
    
    // Khởi tạo các nút yêu thích
    initFavoriteButtons();
    
    // Khởi tạo thông báo Toast
    initToasts();
    
    // Theo dõi sự kiện cuộn để thêm hiệu ứng cho header
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.site-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // Lazy loading cho các hình ảnh
    const lazyImages = document.querySelectorAll('img[data-src]');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback cho các trình duyệt không hỗ trợ IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
});

/**
 * Khởi tạo và xử lý theme
 */
function initTheme() {
    const themeToggleBtn = document.getElementById('theme-toggle');
    
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            // Lấy theme hiện tại
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            
            // Chuyển đổi theme
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Cập nhật theme mới
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // Cập nhật icon
            updateThemeIcon(newTheme);
            
            // Lưu preference của người dùng
            saveUserThemePreference(newTheme);
        });
    }
}

/**
 * Cập nhật icon của nút chuyển đổi theme
 * 
 * @param {string} theme Theme hiện tại ('light' hoặc 'dark')
 */
function updateThemeIcon(theme) {
    const themeToggleBtn = document.getElementById('theme-toggle');
    
    if (themeToggleBtn) {
        if (theme === 'dark') {
            themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }
}

/**
 * Lưu theme preference của người dùng vào cookie hoặc server
 * 
 * @param {string} theme Theme đã chọn ('light' hoặc 'dark')
 */
function saveUserThemePreference(theme) {
    // Lưu vào cookie cho người dùng chưa đăng nhập
    document.cookie = `theme=${theme}; path=/; max-age=31536000`; // 1 năm
    
    // Nếu người dùng đã đăng nhập, gửi AJAX để lưu vào server
    if (document.querySelector('body').dataset.userId) {
        fetch('ajax/save_theme_preference.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `theme=${theme}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Đã lưu theme preference của bạn');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lưu theme preference:', error);
        });
    }
}

/**
 * Khởi tạo các swiper slider
 */
function initSwipers() {
    // Hero swiper
    if (document.querySelector('.hero-swiper')) {
        new Swiper('.hero-swiper', {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    }
    
    // Movie/Anime swiper
    const movieSwipers = document.querySelectorAll('.movie-swiper');
    if (movieSwipers.length > 0) {
        movieSwipers.forEach(swiper => {
            new Swiper(swiper, {
                slidesPerView: 'auto',
                spaceBetween: 15,
                freeMode: true,
                navigation: {
                    nextEl: swiper.nextElementSibling,
                    prevEl: swiper.nextElementSibling.nextElementSibling,
                },
                breakpoints: {
                    320: {
                        slidesPerView: 2,
                        spaceBetween: 10
                    },
                    576: {
                        slidesPerView: 3,
                        spaceBetween: 15
                    },
                    768: {
                        slidesPerView: 4,
                        spaceBetween: 15
                    },
                    992: {
                        slidesPerView: 5,
                        spaceBetween: 20
                    },
                    1200: {
                        slidesPerView: 6,
                        spaceBetween: 20
                    }
                }
            });
        });
    }
}

/**
 * Khởi tạo menu mobile
 */
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const closeMenuBtn = document.querySelector('.close-menu-btn');
    
    if (mobileMenuToggle && mobileMenu && closeMenuBtn) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
}

/**
 * Khởi tạo các nút yêu thích
 */
function initFavoriteButtons() {
    const favButtons = document.querySelectorAll('.btn-favorite, .add-to-favorites');
    
    favButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const movieId = this.dataset.id;
            
            // Kiểm tra đăng nhập
            if (!document.querySelector('body').dataset.userId) {
                showToast('Vui lòng đăng nhập để sử dụng tính năng này', 'warning');
                return;
            }
            
            // Gửi AJAX để toggle yêu thích
            fetch('ajax/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `movie_id=${movieId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật UI
                    if (data.action === 'add') {
                        this.classList.add('active');
                        showToast('Đã thêm vào danh sách yêu thích', 'success');
                    } else {
                        this.classList.remove('active');
                        showToast('Đã xóa khỏi danh sách yêu thích', 'info');
                    }
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Lỗi khi toggle yêu thích:', error);
                showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
            });
        });
    });
}

/**
 * Khởi tạo toast notification
 */
function initToasts() {
    const toastContainer = document.querySelector('.toast-container');
    
    // Tạo container nếu chưa có
    if (!toastContainer) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

/**
 * Hiển thị toast notification
 * 
 * @param {string} message Nội dung thông báo
 * @param {string} type Loại thông báo: success, info, warning, error
 * @param {number} duration Thời gian hiển thị (ms)
 */
function showToast(message, type = 'info', duration = 3000) {
    const container = document.querySelector('.toast-container');
    
    // Tạo toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="toast-icon fas ${getToastIcon(type)}"></i>
            <div class="toast-message">${message}</div>
        </div>
        <div class="toast-progress"></div>
    `;
    
    // Thêm vào container
    container.appendChild(toast);
    
    // Hiệu ứng hiển thị
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Set progress animation
    const progress = toast.querySelector('.toast-progress');
    progress.style.transition = `width ${duration}ms linear`;
    
    // Trigger reflow (để animation hoạt động)
    progress.getBoundingClientRect();
    
    // Bắt đầu animation
    progress.style.width = '0%';
    
    // Tự động đóng sau thời gian duration
    const timeout = setTimeout(() => {
        closeToast(toast);
    }, duration);
    
    // Xử lý click để đóng toast
    toast.addEventListener('click', function() {
        clearTimeout(timeout);
        closeToast(toast);
    });
}

/**
 * Lấy icon cho từng loại toast
 * 
 * @param {string} type Loại toast
 * @return {string} Class của icon
 */
function getToastIcon(type) {
    switch (type) {
        case 'success':
            return 'fa-check-circle';
        case 'warning':
            return 'fa-exclamation-triangle';
        case 'error':
            return 'fa-times-circle';
        case 'info':
        default:
            return 'fa-info-circle';
    }
}

/**
 * Đóng toast notification
 * 
 * @param {HTMLElement} toast Toast element cần đóng
 */
function closeToast(toast) {
    toast.classList.remove('show');
    
    // Xóa khỏi DOM sau khi animation kết thúc
    toast.addEventListener('transitionend', function() {
        toast.remove();
    });
}

/**
 * Kiểm tra xem element có nằm trong viewport không
 * 
 * @param {HTMLElement} element Element cần kiểm tra
 * @return {boolean} True nếu element nằm trong viewport
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}