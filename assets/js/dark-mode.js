/**
 * Xử lý Dark Mode cho Lọc Phim
 */

document.addEventListener('DOMContentLoaded', function() {
    // Lấy nút chuyển đổi theme
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        // Khởi tạo theme dựa trên preference đã lưu
        const savedTheme = localStorage.getItem('theme') || 
                          (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        // Áp dụng theme
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateToggleButton(savedTheme);
        
        // Xử lý sự kiện click
        themeToggle.addEventListener('click', function() {
            // Lấy theme hiện tại
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            // Đổi theme
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Cập nhật theme
            document.documentElement.setAttribute('data-theme', newTheme);
            updateToggleButton(newTheme);
            
            // Lưu preference
            localStorage.setItem('theme', newTheme);
            saveUserPreference(newTheme);
        });
    }
    
    /**
     * Cập nhật UI của nút toggle dựa trên theme
     * 
     * @param {string} isDarkMode Chế độ hiện tại ('dark' hoặc 'light')
     */
    function updateToggleButton(isDarkMode) {
        if (themeToggle) {
            if (isDarkMode === 'dark') {
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                themeToggle.setAttribute('title', 'Chuyển sang chế độ sáng');
            } else {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                themeToggle.setAttribute('title', 'Chuyển sang chế độ tối');
            }
        }
    }
    
    /**
     * Lưu preference của người dùng lên server (nếu đã đăng nhập)
     * 
     * @param {string} theme Theme preference ('dark' hoặc 'light')
     */
    function saveUserPreference(theme) {
        // Nếu người dùng đã đăng nhập, gửi preference lên server
        const userId = document.body.dataset.userId;
        if (userId) {
            fetch('ajax/save_theme_preference.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `theme=${theme}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Theme preference saved', data);
            })
            .catch(error => {
                console.error('Error saving theme preference:', error);
            });
        }
    }
});