/**
 * Xử lý thông báo cho Lọc Phim
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo xử lý thông báo
    initNotifications();
});

/**
 * Khởi tạo xử lý thông báo
 */
function initNotifications() {
    // Kiểm tra các phần tử thông báo có tồn tại không
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationCount = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');
    const markAllReadBtn = document.getElementById('mark-all-read');
    
    if (!notificationBtn || !notificationList) {
        return;
    }
    
    // Hiển thị/ẩn dropdown thông báo
    notificationBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Hiển thị dropdown
        notificationDropdown.classList.toggle('show');
        
        // Tải thông báo nếu dropdown đang hiển thị
        if (notificationDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Đánh dấu tất cả thông báo đã đọc
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-container') && notificationDropdown.classList.contains('show')) {
            notificationDropdown.classList.remove('show');
        }
    });
    
    // Kiểm tra thông báo mới định kỳ (mỗi 1 phút)
    setInterval(checkNewNotifications, 60000);
    
    // Kiểm tra thông báo mới ngay khi trang tải xong
    checkNewNotifications();
}

/**
 * Tải danh sách thông báo
 * 
 * @param {number} limit Số lượng thông báo tối đa
 * @param {number} offset Vị trí bắt đầu
 */
function loadNotifications(limit = 10, offset = 0) {
    const notificationList = document.getElementById('notification-list');
    const loadingElement = document.getElementById('notification-loading');
    
    if (!notificationList) return;
    
    // Hiển thị loading
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
    
    // Tải danh sách thông báo
    fetch(`/ajax/notifications.php?limit=${limit}&offset=${offset}`)
        .then(response => response.json())
        .then(data => {
            // Ẩn loading
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            if (data.success) {
                renderNotifications(data.notifications);
                updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông báo:', error);
            
            // Ẩn loading
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            // Hiển thị thông báo lỗi
            notificationList.innerHTML = `
                <div class="notification-error">
                    <p>Đã xảy ra lỗi khi tải thông báo. Vui lòng thử lại sau.</p>
                </div>
            `;
        });
}

/**
 * Hiển thị danh sách thông báo
 * 
 * @param {Array} notifications Danh sách thông báo
 */
function renderNotifications(notifications) {
    const notificationList = document.getElementById('notification-list');
    const noNotificationElement = document.getElementById('no-notification');
    
    if (!notificationList) return;
    
    if (!notifications || notifications.length === 0) {
        // Hiển thị thông báo không có thông báo
        if (noNotificationElement) {
            noNotificationElement.style.display = 'block';
        } else {
            notificationList.innerHTML = `
                <div class="no-notification" id="no-notification">
                    <p>Bạn chưa có thông báo nào.</p>
                </div>
            `;
        }
        return;
    }
    
    // Ẩn thông báo không có thông báo
    if (noNotificationElement) {
        noNotificationElement.style.display = 'none';
    }
    
    // Hiển thị danh sách thông báo
    let notificationHTML = '';
    
    notifications.forEach(notification => {
        const isRead = notification.is_read == 1 ? 'read' : 'unread';
        const notificationIcon = getNotificationIcon(notification.type);
        
        notificationHTML += `
            <div class="notification-item ${isRead}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="${notificationIcon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.content}</div>
                    <div class="notification-time">${notification.formatted_time}</div>
                </div>
                ${notification.is_read == 0 ? '<button class="mark-read-btn" title="Đánh dấu đã đọc"><i class="fa fa-check"></i></button>' : ''}
            </div>
        `;
    });
    
    notificationList.innerHTML = notificationHTML;
    
    // Thêm sự kiện click cho các thông báo
    const notificationItems = notificationList.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        // Xử lý click vào thông báo
        item.addEventListener('click', function(e) {
            const markReadBtn = e.target.closest('.mark-read-btn');
            
            if (markReadBtn) {
                // Ngăn chặn sự kiện bubble
                e.stopPropagation();
                
                // Đánh dấu thông báo đã đọc
                const notificationId = this.dataset.id;
                markNotificationAsRead(notificationId);
            } else {
                // Xử lý click vào thông báo
                const notificationId = this.dataset.id;
                const notificationLink = getNotificationLink(notificationId, notifications);
                
                // Đánh dấu thông báo đã đọc
                if (this.classList.contains('unread')) {
                    markNotificationAsRead(notificationId);
                }
                
                // Chuyển hướng nếu có link
                if (notificationLink) {
                    window.location.href = notificationLink;
                }
            }
        });
    });
}

/**
 * Lấy icon cho loại thông báo
 * 
 * @param {string} type Loại thông báo
 * @return {string} Class CSS của icon
 */
function getNotificationIcon(type) {
    switch (type) {
        case 'system':
            return 'fa fa-bullhorn';
        case 'movie':
            return 'fa fa-film';
        case 'comment':
            return 'fa fa-comment';
        case 'payment':
            return 'fa fa-money-bill';
        case 'user':
            return 'fa fa-user';
        default:
            return 'fa fa-bell';
    }
}

/**
 * Lấy link của thông báo
 * 
 * @param {number} notificationId ID của thông báo
 * @param {Array} notifications Danh sách thông báo
 * @return {string|null} Link của thông báo hoặc null nếu không có
 */
function getNotificationLink(notificationId, notifications) {
    const notification = notifications.find(n => n.id == notificationId);
    return notification ? notification.link : null;
}

/**
 * Đánh dấu thông báo đã đọc
 * 
 * @param {number} notificationId ID của thông báo
 */
function markNotificationAsRead(notificationId) {
    const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
    
    if (!notificationItem) return;
    
    // Gửi yêu cầu đánh dấu đã đọc
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('notification_id', notificationId);
    
    fetch('/ajax/notifications.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật giao diện
                notificationItem.classList.remove('unread');
                notificationItem.classList.add('read');
                
                // Xóa nút đánh dấu đã đọc
                const markReadBtn = notificationItem.querySelector('.mark-read-btn');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
                
                // Cập nhật số lượng thông báo chưa đọc
                updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Lỗi khi đánh dấu thông báo đã đọc:', error);
        });
}

/**
 * Đánh dấu tất cả thông báo đã đọc
 */
function markAllNotificationsAsRead() {
    // Gửi yêu cầu đánh dấu tất cả đã đọc
    const formData = new FormData();
    formData.append('action', 'mark_all_read');
    
    fetch('/ajax/notifications.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật giao diện
                const unreadItems = document.querySelectorAll('.notification-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                    item.classList.add('read');
                    
                    // Xóa nút đánh dấu đã đọc
                    const markReadBtn = item.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                });
                
                // Cập nhật số lượng thông báo chưa đọc
                updateNotificationCount(0);
            }
        })
        .catch(error => {
            console.error('Lỗi khi đánh dấu tất cả thông báo đã đọc:', error);
        });
}

/**
 * Kiểm tra thông báo mới
 */
function checkNewNotifications() {
    fetch('/ajax/notifications.php?limit=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Lỗi khi kiểm tra thông báo mới:', error);
        });
}

/**
 * Cập nhật số lượng thông báo chưa đọc
 * 
 * @param {number} count Số lượng thông báo chưa đọc
 */
function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notification-count');
    
    if (!notificationCount) return;
    
    if (count > 0) {
        notificationCount.textContent = count > 99 ? '99+' : count;
        notificationCount.style.display = 'flex';
    } else {
        notificationCount.style.display = 'none';
    }
}