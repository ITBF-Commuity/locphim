    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <div class="footer-logo">
                        <?php if (isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1'): ?>
                            <img src="<?php echo url('assets/images/logo-dark.png'); ?>" alt="<?php echo SITE_NAME; ?>" style="height: 40px;">
                        <?php else: ?>
                            <img src="<?php echo url('assets/images/logo.svg'); ?>" alt="<?php echo SITE_NAME; ?>" style="height: 40px;">
                        <?php endif; ?>
                    </div>
                    
                    <p class="footer-description">
                        <?php echo SITE_DESCRIPTION; ?>
                    </p>
                    
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="Telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Thể loại phim</h3>
                    
                    <ul class="footer-links">
                        <?php
                        try {
                            $footerCategories = $db->getAll("SELECT * FROM categories ORDER BY name ASC LIMIT 10");
                            foreach ($footerCategories as $category):
                        ?>
                            <li>
                                <a href="<?php echo url('the-loai/' . $category['slug']); ?>" class="footer-link">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php
                            endforeach;
                        } catch (Exception $e) {
                            // Do nothing on error, just don't show categories
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Liên kết nhanh</h3>
                    
                    <ul class="footer-links">
                        <li>
                            <a href="<?php echo url(''); ?>" class="footer-link">Trang chủ</a>
                        </li>
                        <li>
                            <a href="<?php echo url('danh-sach/phim-le'); ?>" class="footer-link">Phim lẻ</a>
                        </li>
                        <li>
                            <a href="<?php echo url('danh-sach/phim-bo'); ?>" class="footer-link">Phim bộ</a>
                        </li>
                        <li>
                            <a href="<?php echo url('danh-sach/anime'); ?>" class="footer-link">Anime</a>
                        </li>
                        <li>
                            <a href="<?php echo url('vip'); ?>" class="footer-link">VIP</a>
                        </li>
                        <?php if (isset($user) && $user): ?>
                            <li>
                                <a href="<?php echo url('tai-khoan'); ?>" class="footer-link">Tài khoản</a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="<?php echo url('dang-nhap'); ?>" class="footer-link">Đăng nhập</a>
                            </li>
                            <li>
                                <a href="<?php echo url('dang-ky'); ?>" class="footer-link">Đăng ký</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Liên hệ</h3>
                    
                    <ul class="contact-info">
                        <li>
                            <span class="contact-icon"><i class="fas fa-map-marker-alt"></i></span>
                            <span class="contact-text">Hà Nội, Việt Nam</span>
                        </li>
                        <li>
                            <span class="contact-icon"><i class="fas fa-envelope"></i></span>
                            <span class="contact-text">support@locphim.vn</span>
                        </li>
                        <li>
                            <span class="contact-icon"><i class="fas fa-phone-alt"></i></span>
                            <span class="contact-text">+84 123 456 789</span>
                        </li>
                    </ul>
                    
                    <div class="download-apps">
                        <h4 class="download-heading">Tải ứng dụng</h4>
                        <div class="app-buttons">
                            <a href="#" class="app-button">
                                <i class="fab fa-google-play"></i> Google Play
                            </a>
                            <a href="#" class="app-button">
                                <i class="fab fa-apple"></i> App Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.
                </div>
                
                <div class="footer-legal">
                    <a href="#" class="legal-link">Điều khoản sử dụng</a>
                    <a href="#" class="legal-link">Chính sách bảo mật</a>
                    <a href="#" class="legal-link">Giới thiệu</a>
                    <a href="#" class="legal-link">Liên hệ</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <a href="#" class="back-to-top" id="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <!-- JavaScript Files -->
    <script src="<?php echo url('assets/js/locphim.js?v=' . CACHE_VERSION); ?>"></script>
    
    <!-- Script for UI Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search toggle
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
            
            // User dropdown
            const userAvatar = document.getElementById('user-avatar');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userAvatar && userDropdown) {
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && e.target !== userAvatar) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Mobile menu
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            
            if (mobileMenuToggle && mobileMenu && mobileMenuClose) {
                mobileMenuToggle.addEventListener('click', function() {
                    mobileMenu.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
                
                mobileMenuClose.addEventListener('click', function() {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Dark mode toggle
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            
            if (darkModeToggle) {
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
                        link.href = '<?php echo url('assets/css/dark-mode.css?v=' . CACHE_VERSION); ?>';
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
            
            // Toast close button
            const toastCloseButtons = document.querySelectorAll('.toast-close');
            toastCloseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });
            
            // Back to top button
            const backToTopButton = document.getElementById('back-to-top');
            
            if (backToTopButton) {
                // Show/hide button based on scroll position
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTopButton.classList.add('active');
                    } else {
                        backToTopButton.classList.remove('active');
                    }
                });
                
                // Scroll to top when clicked
                backToTopButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
    
    <style>
        /* Footer Styling */
        .footer {
            background-color: var(--footer-bg);
            color: var(--text-color);
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-column {
            flex: 1;
        }
        
        .footer-logo {
            margin-bottom: 1rem;
        }
        
        .footer-description {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .social-links {
            display: flex;
            gap: 0.75rem;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--bg-light);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .footer-heading {
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .footer-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            display: block;
            padding: 0.5rem 0;
            color: var(--text-muted);
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .contact-info {
            list-style: none;
            margin-bottom: 1.5rem;
        }
        
        .contact-info li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .contact-icon {
            width: 16px;
            color: var(--primary-color);
        }
        
        .contact-text {
            flex: 1;
            color: var(--text-muted);
        }
        
        .download-heading {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .app-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .app-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius);
            background-color: var(--bg-light);
            color: var(--text-color);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .app-button:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .footer-bottom {
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .copyright {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .footer-legal {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .legal-link {
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: var(--transition);
        }
        
        .legal-link:hover {
            color: var(--primary-color);
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: -50px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 99;
            opacity: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .back-to-top.active {
            bottom: 20px;
            opacity: 1;
        }
        
        .back-to-top:hover {
            background-color: var(--primary-hover);
            transform: translateY(-3px);
        }
        
        /* Responsive Footer */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-legal {
                justify-content: center;
            }
        }
    </style>
</body>
</html>