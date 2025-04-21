/**
 * Lọc Phim - Seasonal Themes JavaScript
 * File này xử lý tính năng giao diện theo mùa (Giáng sinh, Tết, Halloween, v.v)
 * Phiên bản nâng cấp với nhiều giao diện ngày lễ Việt Nam hơn
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo giao diện theo mùa
    initSeasonalThemes();
});

/**
 * Khởi tạo tính năng giao diện theo mùa
 */
function initSeasonalThemes() {
    const currentTheme = getCurrentSeasonalTheme();
    
    if (currentTheme) {
        applySeasonalTheme(currentTheme);
        
        // Thêm selector cho việc thay đổi theme
        const themeSelector = document.getElementById('seasonal-theme-selector');
        if (themeSelector) {
            themeSelector.value = currentTheme;
            themeSelector.addEventListener('change', function() {
                const newTheme = this.value;
                if (newTheme === 'none') {
                    removeAllSeasonalThemes();
                    saveSeasonalTheme('none');
                } else {
                    removeAllSeasonalThemes();
                    applySeasonalTheme(newTheme);
                    saveSeasonalTheme(newTheme);
                }
            });
        }
    }
}

/**
 * Lấy theme theo mùa hiện tại
 * 
 * @returns {string|null} Theme hiện tại hoặc null nếu không có
 */
function getCurrentSeasonalTheme() {
    // Lấy theme từ data attribute trong HTML
    const htmlTag = document.documentElement;
    return htmlTag.getAttribute('data-seasonal-theme');
}

/**
 * Áp dụng theme theo mùa cho trang web
 * 
 * @param {string} theme Tên theme cần áp dụng
 */
function applySeasonalTheme(theme) {
    if (!theme || theme === 'none') return;
    
    // Đánh dấu theme đang áp dụng
    document.documentElement.setAttribute('data-seasonal-theme', theme);

    // Thêm class vào body để CSS có thể nhận dạng và styling
    document.body.classList.add(getThemeClass(theme));
    
    // Áp dụng hiệu ứng tương ứng với từng theme
    switch(theme) {
        case 'christmas':
            addSnowflakes();
            break;
        case 'tet':
            addFirecrackers();
            break;
        case 'halloween':
            addHalloweenEffects();
            break;
        case 'trung-thu':
            addMidAutumnEffects();
            break;
        case 'quoc-khanh':
            addNationalDayEffects();
            break;
        case '30-4':
            addLiberationDayEffects();
            break;
    }
}

/**
 * Lấy tên class tương ứng với theme
 * 
 * @param {string} theme Tên theme
 * @returns {string} Tên class
 */
function getThemeClass(theme) {
    switch(theme) {
        case 'christmas':
            return 'christmas';
        case 'tet':
            return 'tet';
        case 'halloween':
            return 'halloween';
        case 'trung-thu':
            return 'mid-autumn';
        case 'quoc-khanh':
            return 'national-day';
        case '30-4':
            return 'liberation';
        default:
            return '';
    }
}

/**
 * Xóa tất cả theme theo mùa
 */
function removeAllSeasonalThemes() {
    // Xóa data attribute
    document.documentElement.removeAttribute('data-seasonal-theme');
    
    // Xóa tất cả các class theme
    document.body.classList.remove(
        'christmas', 
        'tet', 
        'halloween', 
        'mid-autumn', 
        'national-day', 
        'liberation'
    );
    
    // Xóa các hiệu ứng
    removeSnowflakes();
    removeFirecrackers();
    removeHalloweenEffects();
    removeMidAutumnEffects();
    removeNationalDayEffects();
    removeLiberationDayEffects();
}

/**
 * Lưu tùy chọn theme theo mùa
 * 
 * @param {string} theme Theme cần lưu
 */
function saveSeasonalTheme(theme) {
    // Lưu vào cookie
    setCookie('seasonal_theme', theme, 30);
    
    // Nếu người dùng đã đăng nhập, lưu lên server
    fetch('ajax/save_seasonal_theme.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'theme=' + theme
    }).catch(error => {
        console.error('Error saving seasonal theme:', error);
    });
}

// =================== CHRISTMAS EFFECTS ===================

/**
 * Thêm hiệu ứng tuyết rơi cho giao diện Giáng sinh
 */
function addSnowflakes() {
    const snowflakesCount = 50; // Số lượng bông tuyết
    const body = document.body;
    
    // Tạo container cho bông tuyết
    const snowContainer = document.createElement('div');
    snowContainer.className = 'snowflakes-container';
    snowContainer.style.position = 'fixed';
    snowContainer.style.top = '0';
    snowContainer.style.left = '0';
    snowContainer.style.width = '100%';
    snowContainer.style.height = '100%';
    snowContainer.style.overflow = 'hidden';
    snowContainer.style.pointerEvents = 'none';
    snowContainer.style.zIndex = '1000';
    
    // Thêm các bông tuyết
    for (let i = 0; i < snowflakesCount; i++) {
        const snowflake = document.createElement('div');
        snowflake.className = 'snowflake';
        snowflake.textContent = '❆';
        snowflake.style.position = 'absolute';
        snowflake.style.color = 'white';
        snowflake.style.textShadow = '0 0 8px rgba(255, 255, 255, 0.8)';
        snowflake.style.userSelect = 'none';
        snowflake.style.pointerEvents = 'none';
        snowflake.style.fontSize = Math.random() * 15 + 10 + 'px';
        snowflake.style.opacity = Math.random() * 0.5 + 0.5;
        
        // Vị trí ban đầu
        snowflake.style.left = Math.random() * 100 + 'vw';
        snowflake.style.top = '-10px';
        
        // Đặt animation
        const animationDuration = Math.random() * 5 + 10;
        const swayAmount = Math.random() * 50 + 'px';
        snowflake.style.setProperty('--sway-amount', swayAmount);
        snowflake.style.animation = `fall ${animationDuration}s linear infinite, sway 3s ease-in-out infinite alternate`;
        
        // Thêm vào container
        snowContainer.appendChild(snowflake);
    }
    
    body.appendChild(snowContainer);
}

/**
 * Xóa hiệu ứng tuyết rơi
 */
function removeSnowflakes() {
    const snowContainer = document.querySelector('.snowflakes-container');
    if (snowContainer) {
        snowContainer.remove();
    }
}

// ====================== TET EFFECTS ======================

/**
 * Thêm hiệu ứng pháo hoa cho giao diện Tết
 */
function addFirecrackers() {
    const body = document.body;
    
    // Tạo container cho pháo hoa
    const firecrackersContainer = document.createElement('div');
    firecrackersContainer.className = 'firecrackers-container';
    firecrackersContainer.style.position = 'fixed';
    firecrackersContainer.style.top = '0';
    firecrackersContainer.style.left = '0';
    firecrackersContainer.style.width = '100%';
    firecrackersContainer.style.height = '100%';
    firecrackersContainer.style.overflow = 'hidden';
    firecrackersContainer.style.pointerEvents = 'none';
    firecrackersContainer.style.zIndex = '999';
    
    body.appendChild(firecrackersContainer);
    
    // Tạo hiệu ứng pháo hoa ngẫu nhiên
    function createRandomFirecracker() {
        const x = Math.random() * window.innerWidth;
        const y = Math.random() * (window.innerHeight / 2);
        
        const firecracker = document.createElement('div');
        firecracker.className = 'firecracker';
        firecracker.style.left = `${x}px`;
        firecracker.style.top = `${y}px`;
        
        firecrackersContainer.appendChild(firecracker);
        
        // Tạo các hạt pháo hoa
        for (let i = 0; i < 30; i++) {
            setTimeout(() => {
                const particle = document.createElement('div');
                particle.className = 'firecracker-particle';
                
                // Màu sắc ngẫu nhiên
                const colors = ['#ffd700', '#ff0000', '#ffff00', '#fe7c08', '#ff4e90', '#00ff00'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                particle.style.backgroundColor = color;
                
                // Vị trí
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;
                
                // Hướng bay ngẫu nhiên
                const angle = Math.random() * Math.PI * 2;
                const distance = Math.random() * 150 + 50;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance;
                
                particle.style.setProperty('--tx', `${tx}px`);
                particle.style.setProperty('--ty', `${ty}px`);
                
                firecrackersContainer.appendChild(particle);
                
                // Xóa particle sau khi animation kết thúc
                setTimeout(() => {
                    particle.remove();
                }, 1000);
            }, i * 30);
        }
        
        // Xóa firecracker sau khi animation kết thúc
        setTimeout(() => {
            firecracker.remove();
        }, 1200);
    }
    
    // Tạo pháo hoa ngẫu nhiên mỗi 3-5 giây
    const firecrackersInterval = setInterval(() => {
        createRandomFirecracker();
    }, Math.random() * 2000 + 3000);
    
    // Lưu interval để có thể clear sau này
    window.firecrackersInterval = firecrackersInterval;
    
    // Tạo pháo hoa ban đầu
    for (let i = 0; i < 5; i++) {
        setTimeout(() => {
            createRandomFirecracker();
        }, i * 500);
    }
}

/**
 * Xóa hiệu ứng pháo hoa
 */
function removeFirecrackers() {
    const firecrackersContainer = document.querySelector('.firecrackers-container');
    if (firecrackersContainer) {
        firecrackersContainer.remove();
    }
    
    // Xóa interval
    if (window.firecrackersInterval) {
        clearInterval(window.firecrackersInterval);
        window.firecrackersInterval = null;
    }
}

// =================== HALLOWEEN EFFECTS ===================

/**
 * Thêm hiệu ứng Halloween
 */
function addHalloweenEffects() {
    const body = document.body;
    
    // Tạo container cho hiệu ứng Halloween
    const halloweenContainer = document.createElement('div');
    halloweenContainer.className = 'halloween-effects-container';
    halloweenContainer.style.position = 'fixed';
    halloweenContainer.style.top = '0';
    halloweenContainer.style.left = '0';
    halloweenContainer.style.width = '100%';
    halloweenContainer.style.height = '100%';
    halloweenContainer.style.overflow = 'hidden';
    halloweenContainer.style.pointerEvents = 'none';
    halloweenContainer.style.zIndex = '999';
    
    body.appendChild(halloweenContainer);
    
    // Thêm nhện rơi
    function addSpider() {
        const spider = document.createElement('div');
        spider.className = 'halloween-spider';
        
        // Vị trí ban đầu
        const randomX = Math.random() * window.innerWidth;
        spider.style.left = `${randomX}px`;
        
        // Animation
        const swingAmount = Math.random() * 100 - 50;
        spider.style.setProperty('--swing-amount', `${swingAmount}px`);
        
        const animationDuration = Math.random() * 3 + 2;
        spider.style.animation = `spider-swing ${animationDuration}s ease-in-out infinite`;
        
        halloweenContainer.appendChild(spider);
        
        // Xóa con nhện sau một khoảng thời gian
        setTimeout(() => {
            spider.remove();
        }, 15000);
    }
    
    // Thêm các con nhện ngẫu nhiên
    const spiderInterval = setInterval(() => {
        if (Math.random() > 0.6) { // 40% cơ hội xuất hiện
            addSpider();
        }
    }, 3000);
    
    // Lưu interval để có thể clear sau này
    window.spiderInterval = spiderInterval;
    
    // Thêm một vài con nhện ban đầu
    for (let i = 0; i < 3; i++) {
        setTimeout(() => {
            addSpider();
        }, i * 1000);
    }
}

/**
 * Xóa hiệu ứng Halloween
 */
function removeHalloweenEffects() {
    const halloweenContainer = document.querySelector('.halloween-effects-container');
    if (halloweenContainer) {
        halloweenContainer.remove();
    }
    
    // Xóa interval
    if (window.spiderInterval) {
        clearInterval(window.spiderInterval);
        window.spiderInterval = null;
    }
}

// =================== MID-AUTUMN EFFECTS ===================

/**
 * Thêm hiệu ứng cho giao diện Trung Thu
 */
function addMidAutumnEffects() {
    const body = document.body;
    
    // Tạo container cho hiệu ứng Trung Thu
    const midAutumnContainer = document.createElement('div');
    midAutumnContainer.className = 'mid-autumn-effects-container';
    midAutumnContainer.style.position = 'fixed';
    midAutumnContainer.style.top = '0';
    midAutumnContainer.style.left = '0';
    midAutumnContainer.style.width = '100%';
    midAutumnContainer.style.height = '100%';
    midAutumnContainer.style.overflow = 'hidden';
    midAutumnContainer.style.pointerEvents = 'none';
    midAutumnContainer.style.zIndex = '999';
    
    body.appendChild(midAutumnContainer);
    
    // Thêm đèn lồng
    const lantern1 = document.createElement('div');
    lantern1.className = 'mid-autumn-lantern';
    
    const lantern2 = document.createElement('div');
    lantern2.className = 'mid-autumn-lantern';
    
    midAutumnContainer.appendChild(lantern1);
    midAutumnContainer.appendChild(lantern2);
    
    // Thêm con thỏ ngọc nhảy ngẫu nhiên
    function addRabbit() {
        const rabbit = document.createElement('div');
        rabbit.className = 'rabbit-element';
        
        // Vị trí ngẫu nhiên ở phía dưới màn hình
        const randomX = Math.random() * window.innerWidth;
        rabbit.style.left = `${randomX}px`;
        rabbit.style.bottom = '50px';
        
        midAutumnContainer.appendChild(rabbit);
        
        // Xóa con thỏ sau một khoảng thời gian
        setTimeout(() => {
            rabbit.remove();
        }, 12000);
    }
    
    // Thêm các con thỏ ngẫu nhiên
    const rabbitInterval = setInterval(() => {
        if (Math.random() > 0.7) { // 30% cơ hội xuất hiện
            addRabbit();
        }
    }, 6000);
    
    // Lưu interval để có thể clear sau này
    window.rabbitInterval = rabbitInterval;
    
    // Thêm một vài con thỏ ban đầu
    setTimeout(() => {
        addRabbit();
    }, 2000);
}

/**
 * Xóa hiệu ứng Trung Thu
 */
function removeMidAutumnEffects() {
    const midAutumnContainer = document.querySelector('.mid-autumn-effects-container');
    if (midAutumnContainer) {
        midAutumnContainer.remove();
    }
    
    // Xóa interval
    if (window.rabbitInterval) {
        clearInterval(window.rabbitInterval);
        window.rabbitInterval = null;
    }
}

// =================== NATIONAL DAY EFFECTS ===================

/**
 * Thêm hiệu ứng cho giao diện Quốc Khánh
 */
function addNationalDayEffects() {
    const body = document.body;
    
    // Tạo container cho hiệu ứng Quốc Khánh
    const nationalDayContainer = document.createElement('div');
    nationalDayContainer.className = 'national-day-effects-container';
    nationalDayContainer.style.position = 'fixed';
    nationalDayContainer.style.top = '0';
    nationalDayContainer.style.left = '0';
    nationalDayContainer.style.width = '100%';
    nationalDayContainer.style.height = '100%';
    nationalDayContainer.style.overflow = 'hidden';
    nationalDayContainer.style.pointerEvents = 'none';
    nationalDayContainer.style.zIndex = '999';
    
    body.appendChild(nationalDayContainer);
    
    // Thêm lá cờ
    const flag = document.createElement('div');
    flag.className = 'national-day-flag';
    nationalDayContainer.appendChild(flag);
}

/**
 * Xóa hiệu ứng Quốc Khánh
 */
function removeNationalDayEffects() {
    const nationalDayContainer = document.querySelector('.national-day-effects-container');
    if (nationalDayContainer) {
        nationalDayContainer.remove();
    }
}

// =================== LIBERATION DAY EFFECTS ===================

/**
 * Thêm hiệu ứng cho giao diện Giải Phóng Miền Nam
 */
function addLiberationDayEffects() {
    const body = document.body;
    
    // Tạo container cho hiệu ứng Giải Phóng
    const liberationContainer = document.createElement('div');
    liberationContainer.className = 'liberation-effects-container';
    liberationContainer.style.position = 'fixed';
    liberationContainer.style.top = '0';
    liberationContainer.style.left = '0';
    liberationContainer.style.width = '100%';
    liberationContainer.style.height = '100%';
    liberationContainer.style.overflow = 'hidden';
    liberationContainer.style.pointerEvents = 'none';
    liberationContainer.style.zIndex = '999';
    
    body.appendChild(liberationContainer);
    
    // Thêm lá cờ
    const flag = document.createElement('div');
    flag.className = 'liberation-flag';
    liberationContainer.appendChild(flag);
    
    // Thêm hiệu ứng pháo hoa
    function createFirework() {
        const x = Math.random() * window.innerWidth;
        const y = Math.random() * (window.innerHeight / 2);
        
        const firework = document.createElement('div');
        firework.className = 'firework';
        firework.style.left = `${x}px`;
        firework.style.top = `${y}px`;
        
        // Màu sắc ngẫu nhiên
        const colors = ['#ffcd00', '#e30a17', '#0033a0'];
        const colorIndex = Math.floor(Math.random() * colors.length);
        firework.style.boxShadow = `0 0 10px 5px ${colors[colorIndex]}`;
        
        liberationContainer.appendChild(firework);
        
        // Xóa pháo hoa sau animation
        setTimeout(() => {
            firework.remove();
        }, 1000);
    }
    
    // Tạo pháo hoa ngẫu nhiên mỗi 2-4 giây
    const fireworkInterval = setInterval(() => {
        createFirework();
    }, Math.random() * 2000 + 2000);
    
    // Lưu interval để có thể clear sau này
    window.fireworkInterval = fireworkInterval;
    
    // Tạo pháo hoa ban đầu
    for (let i = 0; i < 3; i++) {
        setTimeout(() => {
            createFirework();
        }, i * 500);
    }
}

/**
 * Xóa hiệu ứng Giải Phóng
 */
function removeLiberationDayEffects() {
    const liberationContainer = document.querySelector('.liberation-effects-container');
    if (liberationContainer) {
        liberationContainer.remove();
    }
    
    // Xóa interval
    if (window.fireworkInterval) {
        clearInterval(window.fireworkInterval);
        window.fireworkInterval = null;
    }
}

// ===================== COOKIE UTILS ======================

/**
 * Lấy giá trị cookie
 * 
 * @param {string} name Tên cookie
 * @returns {string|null} Giá trị cookie hoặc null nếu không tồn tại
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

/**
 * Đặt cookie
 * 
 * @param {string} name Tên cookie
 * @param {string} value Giá trị cookie
 * @param {number} days Số ngày tồn tại
 */
function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = `; expires=${date.toUTCString()}`;
    }
    document.cookie = `${name}=${value}${expires}; path=/`;
}