        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-columns">
                    <div class="footer-column">
                        <h3>Giới thiệu</h3>
                        <p>Lọc Phim là nền tảng xem phim và anime trực tuyến hàng đầu tại Việt Nam, cung cấp nội dung chất lượng cao với phụ đề Việt ngữ. Chúng tôi luôn cập nhật nhanh chóng các phim mới và anime mới nhất.</p>
                        <div class="footer-social">
                            <a href="#" class="footer-social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="footer-social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="footer-social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="footer-social-link" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="#" class="footer-social-link" title="TikTok">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Điều hướng</h3>
                        <div class="footer-links">
                            <a href="<?php echo url('/'); ?>" class="footer-link">Trang chủ</a>
                            <a href="<?php echo url('/phim-le'); ?>" class="footer-link">Phim lẻ</a>
                            <a href="<?php echo url('/phim-bo'); ?>" class="footer-link">Phim bộ</a>
                            <a href="<?php echo url('/anime'); ?>" class="footer-link">Anime</a>
                            <a href="<?php echo url('/the-loai'); ?>" class="footer-link">Thể loại</a>
                            <a href="<?php echo url('/bang-xep-hang'); ?>" class="footer-link">Xếp hạng</a>
                        </div>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Thông tin</h3>
                        <div class="footer-links">
                            <a href="<?php echo url('/gioi-thieu'); ?>" class="footer-link">Giới thiệu</a>
                            <a href="<?php echo url('/lien-he'); ?>" class="footer-link">Liên hệ</a>
                            <a href="<?php echo url('/dieu-khoan-su-dung'); ?>" class="footer-link">Điều khoản sử dụng</a>
                            <a href="<?php echo url('/chinh-sach-bao-mat'); ?>" class="footer-link">Chính sách bảo mật</a>
                            <a href="<?php echo url('/faq'); ?>" class="footer-link">FAQ</a>
                            <a href="<?php echo url('/dmca'); ?>" class="footer-link">DMCA</a>
                        </div>
                    </div>
                    
                    <div class="footer-column">
                        <h3>Tải xuống ứng dụng</h3>
                        <p>Tải xuống ứng dụng Lọc Phim để xem phim mọi lúc mọi nơi trên điện thoại của bạn.</p>
                        <div class="d-flex mt-3">
                            <a href="#" class="mr-2">
                                <img src="<?php echo SITE_URL; ?>/assets/images/app-store.svg" alt="App Store" style="height: 40px;">
                            </a>
                            <a href="#">
                                <img src="<?php echo SITE_URL; ?>/assets/images/google-play.svg" alt="Google Play" style="height: 40px;">
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> Lọc Phim. Tất cả quyền được bảo lưu.</p>
                </div>
            </div>
        </footer>
        
        <!-- Back to Top -->
        <a href="#" class="back-to-top" id="backToTop">
            <i class="fas fa-arrow-up"></i>
        </a>
        
        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>
    
    <!-- Custom JS -->
    <?php if (isset($customJs)): ?>
    <script src="<?php echo SITE_URL; ?><?php echo $customJs; ?>?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    
    <!-- Modern JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark Mode Toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');
            
            function toggleDarkMode(isLightMode) {
                if (isLightMode) {
                    document.documentElement.classList.add('light-mode');
                } else {
                    document.documentElement.classList.remove('light-mode');
                }
                localStorage.setItem('darkMode', isLightMode);
                
                if (darkModeToggleMobile) {
                    darkModeToggleMobile.checked = isLightMode;
                }
            }
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    const isLightMode = !document.documentElement.classList.contains('light-mode');
                    toggleDarkMode(isLightMode);
                });
            }
            
            if (darkModeToggleMobile) {
                darkModeToggleMobile.addEventListener('change', function() {
                    toggleDarkMode(this.checked);
                });
            }
            
            // Mobile Menu Toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileMenuClose = document.getElementById('mobileMenuClose');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            
            function openMobileMenu() {
                if (mobileMenu && mobileMenuOverlay) {
                    mobileMenu.classList.add('active');
                    mobileMenuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            function closeMobileMenu() {
                if (mobileMenu && mobileMenuOverlay) {
                    mobileMenu.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', openMobileMenu);
            }
            
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', closeMobileMenu);
            }
            
            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', closeMobileMenu);
            }
            
            // Back to Top
            const backToTop = document.getElementById('backToTop');
            
            if (backToTop) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTop.classList.add('active');
                    } else {
                        backToTop.classList.remove('active');
                    }
                });
                
                backToTop.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
            
            // User Dropdown Toggle
            const userDropdownToggle = document.querySelector('.user-avatar');
            const userDropdown = document.querySelector('.user-dropdown');
            
            if (userDropdownToggle && userDropdown) {
                userDropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdownToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Init Tooltips and Popovers
            if (typeof bootstrap !== 'undefined') {
                const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                if (tooltips.length) {
                    Array.from(tooltips).map(tooltip => new bootstrap.Tooltip(tooltip));
                }
                
                const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
                if (popovers.length) {
                    Array.from(popovers).map(popover => new bootstrap.Popover(popover));
                }
            }
            
            console.log('Lọc Phim - JavaScript đã được khởi tạo');
        });
        
        // Toast Function
        function showToast(message, type = 'success', duration = 3000) {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'check-circle' :
                         type === 'error' ? 'times-circle' :
                         type === 'warning' ? 'exclamation-circle' : 'info-circle';
            
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${icon}"></i>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close"><i class="fas fa-times"></i></button>
            `;
            
            toastContainer.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.classList.add('active');
            }, 10);
            
            const closeButton = toast.querySelector('.toast-close');
            closeButton.addEventListener('click', () => {
                removeToast(toast);
            });
            
            if (duration) {
                setTimeout(() => {
                    removeToast(toast);
                }, duration);
            }
            
            function removeToast(toast) {
                toast.classList.remove('active');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        // Helper function to check if element is in viewport
        function isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
        
        // Animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .slide-in-up');
            
            elements.forEach(element => {
                if (isInViewport(element) && !element.classList.contains('animated')) {
                    element.classList.add('animated');
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('resize', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
    </script>
    
    <!-- Inline JS -->
    <?php if (isset($inlineJs)): ?>
    <script>
    <?php echo $inlineJs; ?>
    </script>
    <?php endif; ?>
</body>
</html>