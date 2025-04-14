/**
 * Lọc Phim - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Xử lý thông báo (toast)
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });

    // Auto show toast
    toastList.forEach(toast => toast.show());

    // Đóng thông báo flash message sau 5 giây
    setTimeout(function() {
        const flashAlert = document.querySelector('.alert-dismissible');
        if (flashAlert) {
            bootstrap.Alert.getInstance(flashAlert).close();
        }
    }, 5000);

    // Xử lý tìm kiếm
    const searchForm = document.querySelector('form[action="search.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Xử lý chuyển theme (Dark/Light mode)
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            if (isDarkMode) {
                // Switch to light mode
                document.body.classList.remove('dark-mode');
                document.cookie = "dark_mode=disabled; path=/; max-age=31536000";
                this.innerHTML = '<i class="fas fa-moon"></i> Chế độ tối';
            } else {
                // Switch to dark mode
                document.body.classList.add('dark-mode');
                document.cookie = "dark_mode=enabled; path=/; max-age=31536000";
                this.innerHTML = '<i class="fas fa-sun"></i> Chế độ sáng';
            }
        });
    }

    // Hiển thị/ẩn mật khẩu trên các form đăng nhập/đăng ký
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            
            // Thay đổi loại input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Thay đổi icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Lazy loading hình ảnh
    const lazyImages = document.querySelectorAll('[data-src]');
    
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
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver support
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }

    // Hiệu ứng Scroll to Top
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });
        
        scrollTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Xử lý form đánh giá
    const ratingForm = document.getElementById('ratingForm');
    
    if (ratingForm) {
        const ratingStars = ratingForm.querySelectorAll('.rating-star');
        const ratingValueInput = document.getElementById('ratingValue');
        
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.dataset.value;
                ratingValueInput.value = value;
                
                // Reset all stars
                ratingStars.forEach(s => s.classList.remove('active'));
                
                // Set active stars
                ratingStars.forEach(s => {
                    if (s.dataset.value <= value) {
                        s.classList.add('active');
                    }
                });
            });
        });
    }

    // Xử lý thêm/xóa khỏi danh sách yêu thích
    const favoriteBtn = document.getElementById('favoriteBtn');
    
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const animeId = this.getAttribute('data-anime-id');
            
            // Gửi yêu cầu AJAX
            fetch('api/favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anime_id: animeId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const favoriteText = document.getElementById('favoriteText');
                    
                    if (data.is_favorite) {
                        // Đã thêm vào yêu thích
                        favoriteBtn.classList.remove('btn-outline-danger');
                        favoriteBtn.classList.add('btn-danger');
                        favoriteBtn.querySelector('i').classList.remove('far');
                        favoriteBtn.querySelector('i').classList.add('fas');
                        favoriteText.textContent = 'Đã yêu thích';
                    } else {
                        // Đã xóa khỏi yêu thích
                        favoriteBtn.classList.remove('btn-danger');
                        favoriteBtn.classList.add('btn-outline-danger');
                        favoriteBtn.querySelector('i').classList.remove('fas');
                        favoriteBtn.querySelector('i').classList.add('far');
                        favoriteText.textContent = 'Thêm vào yêu thích';
                    }
                } else {
                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
            });
        });
    }

    // Xử lý dropdown tự động đóng khi click ra ngoài
    document.addEventListener('click', function(e) {
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        if (dropdowns.length > 0) {
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target) && !dropdown.previousElementSibling.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
    });

    // Xử lý form tìm kiếm nâng cao
    const advancedSearchForm = document.getElementById('advancedSearchForm');
    
    if (advancedSearchForm) {
        advancedSearchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (searchInput.value.trim() === '' && !this.querySelector('select[name="category"]').value) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Xử lý giao diện tương tác cho tabs
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Xử lý giao diện tương tác cho accordion
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const isCollapsed = this.classList.contains('collapsed');
            if (!isCollapsed) {
                this.classList.add('active');
            } else {
                this.classList.remove('active');
            }
        });
    });

    // Check hợp lệ form contact
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            const nameInput = document.getElementById('contactName');
            const emailInput = document.getElementById('contactEmail');
            const messageInput = document.getElementById('contactMessage');
            
            if (nameInput.value.trim() === '') {
                markInvalid(nameInput, 'Vui lòng nhập họ tên của bạn');
                isValid = false;
            } else {
                markValid(nameInput);
            }
            
            if (emailInput.value.trim() === '') {
                markInvalid(emailInput, 'Vui lòng nhập email của bạn');
                isValid = false;
            } else if (!isValidEmail(emailInput.value)) {
                markInvalid(emailInput, 'Vui lòng nhập email hợp lệ');
                isValid = false;
            } else {
                markValid(emailInput);
            }
            
            if (messageInput.value.trim() === '') {
                markInvalid(messageInput, 'Vui lòng nhập nội dung tin nhắn');
                isValid = false;
            } else {
                markValid(messageInput);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Hàm kiểm tra email hợp lệ
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    // Hàm đánh dấu trường không hợp lệ
    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        
        const feedbackElement = input.nextElementSibling;
        if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
            feedbackElement.textContent = message;
        } else {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }
    }

    // Hàm đánh dấu trường hợp lệ
    function markValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        
        const feedbackElement = input.nextElementSibling;
        if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
            feedbackElement.textContent = '';
        }
    }
});
