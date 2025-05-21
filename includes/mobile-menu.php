<?php
/**
 * Lọc Phim - Mobile Menu
 * 
 * Menu mobile được tối ưu cho UI theo mẫu
 */
?>
<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <h2 class="mobile-menu-title">Lọc Phim</h2>
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="mobile-menu-content">
        <?php if (isLoggedIn()): ?>
            <?php $currentUser = getCurrentUser(); ?>
            <div class="mobile-user-section">
                <div class="mobile-user-avatar">
                    <img src="<?php echo url($currentUser['avatar'] ? $currentUser['avatar'] : 'assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($currentUser['username']); ?>">
                </div>
                <div class="mobile-user-info">
                    <div class="mobile-user-name"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="mobile-auth-section">
                <a href="<?php echo url('/dang-nhap'); ?>" class="btn btn-outline-secondary w-100 mb-2">Đăng nhập</a>
                <a href="<?php echo url('/dang-ky'); ?>" class="btn btn-outline-danger w-100">Đăng ký</a>
            </div>
        <?php endif; ?>
        
        <div class="mobile-section-title">DANH MỤC</div>
        
        <ul class="mobile-nav-list">
            <li class="mobile-nav-item <?php echo isPath('/') ? 'active' : ''; ?>">
                <a href="<?php echo url('/'); ?>" class="mobile-nav-link">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
            </li>
            <li class="mobile-nav-item <?php echo isPath('/phim-le') ? 'active' : ''; ?>">
                <a href="<?php echo url('/phim-le'); ?>" class="mobile-nav-link">
                    <i class="fas fa-film"></i> Phim lẻ
                </a>
            </li>
            <li class="mobile-nav-item <?php echo isPath('/phim-bo') ? 'active' : ''; ?>">
                <a href="<?php echo url('/phim-bo'); ?>" class="mobile-nav-link">
                    <i class="fas fa-tv"></i> Phim bộ
                </a>
            </li>
            <li class="mobile-nav-item <?php echo isPath('/phim-chieu-rap') ? 'active' : ''; ?>">
                <a href="<?php echo url('/phim-chieu-rap'); ?>" class="mobile-nav-link">
                    <i class="fas fa-ticket-alt"></i> Phim chiếu rạp
                </a>
            </li>
            <li class="mobile-nav-item <?php echo isPath('/phim-sap-chieu') ? 'active' : ''; ?>">
                <a href="<?php echo url('/phim-sap-chieu'); ?>" class="mobile-nav-link">
                    <i class="fas fa-calendar-alt"></i> Phim sắp chiếu
                </a>
            </li>
        </ul>
        
        <?php if (isLoggedIn()): ?>
            <div class="mobile-section-title">TÀI KHOẢN</div>
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="<?php echo url('/tai-khoan'); ?>" class="mobile-nav-link">
                        <i class="fas fa-user"></i> Tài khoản của tôi
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('/tai-khoan/yeu-thich'); ?>" class="mobile-nav-link">
                        <i class="fas fa-heart"></i> Phim yêu thích
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('/tai-khoan/lich-su'); ?>" class="mobile-nav-link">
                        <i class="fas fa-history"></i> Lịch sử xem
                    </a>
                </li>
                <?php if (isVip()): ?>
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('/tai-khoan/vip'); ?>" class="mobile-nav-link">
                            <i class="fas fa-crown"></i> VIP của tôi
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mobile-nav-item">
                        <a href="<?php echo url('/vip'); ?>" class="mobile-nav-link">
                            <i class="fas fa-crown"></i> Nâng cấp VIP
                        </a>
                    </li>
                <?php endif; ?>
                <li class="mobile-nav-item">
                    <a href="<?php echo url('/dang-xuat'); ?>" class="mobile-nav-link">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        <?php endif; ?>
        
        <div class="mobile-dark-mode">
            <span>Dark Mode</span>
            <label class="dark-mode-switch">
                <input type="checkbox" id="darkModeToggleMobile">
                <span class="dark-mode-slider"></span>
            </label>
        </div>
    </div>
</div>