/**
 * JavaScript cho trang quản trị
 */

// Khi tài liệu đã tải xong
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle cho thiết bị di động
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            document.querySelector('#sidebar').classList.toggle('collapse');
        });
    }
    
    // Khởi tạo tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Xử lý đồ thị thống kê (nếu có)
    if (document.getElementById('visitsChart')) {
        initVisitsChart();
    }
    
    // Xử lý checkbox cho xóa hàng loạt
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateBulkActionButtonState();
        });
        
        // Kiểm tra các checkbox riêng lẻ
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkActionButtonState();
                
                // Nếu bỏ chọn một checkbox, cũng bỏ chọn 'Chọn tất cả'
                if (!checkbox.checked && selectAllCheckbox.checked) {
                    selectAllCheckbox.checked = false;
                }
                
                // Nếu tất cả checkbox được chọn, cũng chọn 'Chọn tất cả'
                if (document.querySelectorAll('.item-checkbox:checked').length === checkboxes.length) {
                    selectAllCheckbox.checked = true;
                }
            });
        });
    }
    
    // Xử lý form lọc và tìm kiếm
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Loại bỏ các trường không có giá trị để URL sạch hơn
            const inputs = filterForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.value === '' || input.value === null) {
                    input.disabled = true;
                }
            });
        });
    }
    
    // Xác nhận xóa
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này không?')) {
                e.preventDefault();
            }
        });
    });
    
    // Xác nhận xóa hàng loạt
    const bulkDeleteForm = document.getElementById('bulkActionForm');
    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const action = document.getElementById('bulkAction').value;
            if (action === 'delete' && !confirm('Bạn có chắc chắn muốn xóa các mục đã chọn không?')) {
                e.preventDefault();
            }
        });
    }
});

// Cập nhật trạng thái nút thao tác hàng loạt
function updateBulkActionButtonState() {
    const bulkActionButton = document.getElementById('applyBulkAction');
    if (bulkActionButton) {
        const hasChecked = document.querySelectorAll('.item-checkbox:checked').length > 0;
        bulkActionButton.disabled = !hasChecked;
    }
}

// Khởi tạo biểu đồ lượt truy cập
function initVisitsChart() {
    const ctx = document.getElementById('visitsChart').getContext('2d');
    
    // Dữ liệu mẫu - trong thực tế, dữ liệu này sẽ được tải từ API hoặc từ backend
    const data = {
        labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
        datasets: [{
            label: 'Lượt xem',
            data: [1500, 2000, 1700, 2100, 2300, 2800, 3000, 3200, 3500, 3700, 4000, 4200],
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 2,
            tension: 0.4
        }]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Hàm mở form chỉnh sửa trong modal
function openEditModal(id, title, data = {}) {
    const modal = document.getElementById('editModal');
    if (modal) {
        const modalTitle = modal.querySelector('.modal-title');
        const form = modal.querySelector('form');
        const idInput = form.querySelector('input[name="id"]');
        
        modalTitle.textContent = title;
        idInput.value = id;
        
        // Điền dữ liệu vào form
        for (const key in data) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        }
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }
}

// Hàm xem trước hình ảnh khi upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (preview && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hàm tìm kiếm Ajax
function searchAjax(inputId, resultsContainerId, searchUrl) {
    const input = document.getElementById(inputId);
    const resultsContainer = document.getElementById(resultsContainerId);
    
    if (input && resultsContainer) {
        input.addEventListener('input', debounce(function() {
            const query = input.value.trim();
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                return;
            }
            
            fetch(`${searchUrl}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    
                    if (data.length === 0) {
                        resultsContainer.innerHTML = '<p class="text-muted p-3">Không tìm thấy kết quả</p>';
                        return;
                    }
                    
                    data.forEach(item => {
                        const element = document.createElement('div');
                        element.className = 'search-result-item';
                        element.innerHTML = `
                            <div class="d-flex align-items-center p-2 border-bottom">
                                <img src="${item.image || 'assets/images/default.jpg'}" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0">${item.title}</h6>
                                    <small class="text-muted">${item.subtitle || ''}</small>
                                </div>
                                <a href="${item.url}" class="btn btn-sm btn-primary ms-auto">Xem</a>
                            </div>
                        `;
                        resultsContainer.appendChild(element);
                    });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    resultsContainer.innerHTML = '<p class="text-danger p-3">Đã xảy ra lỗi khi tìm kiếm</p>';
                });
        }, 300));
    }
}

// Hàm debounce để giảm số lần gọi hàm
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}