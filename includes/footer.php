<?php
/**
 * Footer cho tất cả các trang
 */
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h2 class="logo-text">Lọc Phim</h2>
                    <p>
                        Lọc Phim - Website xem phim và anime trực tuyến hàng đầu Việt Nam với nhiều thể loại đa dạng và chất lượng cao.
                    </p>
                    <div class="contact">
                        <span><i class="fas fa-phone"></i> &nbsp; 0123-456-789</span>
                        <span><i class="fas fa-envelope"></i> &nbsp; info@locphim.com</span>
                    </div>
                    <div class="socials">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-section links">
                    <h2>Liên kết nhanh</h2>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/">Trang chủ</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/anime.php">Anime</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/movie.php">Phim</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/premium.php">Thành viên VIP</a></li>
                    </ul>
                </div>

                <div class="footer-section categories">
                    <h2>Thể loại</h2>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/pages/anime.php?genre=action">Hành động</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/anime.php?genre=comedy">Hài hước</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/anime.php?genre=romance">Tình cảm</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/anime.php?genre=fantasy">Viễn tưởng</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/movie.php?genre=horror">Kinh dị</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/movie.php?genre=adventure">Phiêu lưu</a></li>
                    </ul>
                </div>

                <div class="footer-section contact-form">
                    <h2>Liên hệ với chúng tôi</h2>
                    <form action="<?php echo BASE_URL; ?>/api/contact.php" method="post" id="contact-form">
                        <input type="email" name="email" class="text-input contact-input" placeholder="Email của bạn...">
                        <textarea name="message" class="text-input contact-input" placeholder="Lời nhắn của bạn..."></textarea>
                        <button type="submit" class="btn btn-primary btn-block">Gửi</button>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Lọc Phim - Website xem phim và anime trực tuyến hàng đầu</p>
                <div class="footer-links">
                    <a href="<?php echo BASE_URL; ?>/pages/terms.php">Điều khoản sử dụng</a>
                    <a href="<?php echo BASE_URL; ?>/pages/privacy.php">Chính sách bảo mật</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Xử lý form liên hệ AJAX
        document.getElementById('contact-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.reset();
                    alert('Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể!');
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi gửi liên hệ. Vui lòng thử lại sau!');
            });
        });
    </script>
    
    <?php if ($current_user): ?>
    <!-- Thêm CSS và JS cho thông báo -->
    <link rel="stylesheet" href="assets/css/notifications.css">
    <script src="assets/js/notifications.js"></script>
    <?php endif; ?>
</body>
</html>
