/**
 * Lọc Phim - Admin Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar trên mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }

    // DataTables initialization
    const dataTables = document.querySelectorAll('.datatable');
    
    dataTables.forEach(table => {
        new DataTable(table, {
            responsive: true,
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ mục",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                infoFiltered: "(lọc từ _MAX_ mục)",
                zeroRecords: "Không tìm thấy dữ liệu phù hợp",
                emptyTable: "Không có dữ liệu",
                paginate: {
                    first: "Đầu",
                    previous: "Trước",
                    next: "Tiếp",
                    last: "Cuối"
                }
            }
        });
    });

    // Thêm xác nhận khi xóa
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này?')) {
                e.preventDefault();
            }
        });
    });

    // Xử lý multiselect
    const multiSelects = document.querySelectorAll('.multiselect');
    
    multiSelects.forEach(select => {
        new MultiSelectTag(select.id, {
            rounded: true,
            shadow: true,
            placeholder: select.getAttribute('placeholder') || 'Chọn...',
            tagColor: {
                textColor: '#FFFFFF',
                background: '#0d6efd'
            }
        });
    });

    // Xử lý form tìm kiếm nâng cao
    const advancedSearchToggle = document.getElementById('advancedSearchToggle');
    const advancedSearchForm = document.getElementById('advancedSearchForm');
    
    if (advancedSearchToggle && advancedSearchForm) {
        advancedSearchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            advancedSearchForm.classList.toggle('d-none');
        });
    }

    // Khởi tạo tooltip
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Khởi tạo chart nếu có
    const analyticsChart = document.getElementById('analyticsChart');
    
    if (analyticsChart) {
        const ctx = analyticsChart.getContext('2d');
        
        // Lấy dữ liệu từ data attribute
        const viewsData = JSON.parse(analyticsChart.getAttribute('data-views'));
        const usersData = JSON.parse(analyticsChart.getAttribute('data-users'));
        const labels = JSON.parse(analyticsChart.getAttribute('data-labels'));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Lượt xem',
                        data: viewsData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Người dùng mới',
                        data: usersData,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Phân tích hoạt động'
                    }
                }
            }
        });
    }

    // Khởi tạo datetime picker
    const datetimePickers = document.querySelectorAll('.datetimepicker');
    
    datetimePickers.forEach(input => {
        new Tempus({
            target: input,
            dateFormat: 'YYYY-MM-DD HH:mm:ss'
        });
    });

    // Khởi tạo trình soạn thảo cho textarea
    const richTextEditors = document.querySelectorAll('.richtext-editor');
    
    richTextEditors.forEach(textarea => {
        CKEDITOR.replace(textarea.id, {
            height: 300,
            toolbar: [
                { name: 'document', items: ['Source'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                { name: 'editing', items: ['Find', 'Replace', 'SelectAll'] },
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                { name: 'insert', items: ['Table', 'HorizontalRule', 'SpecialChar'] },
                { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] }
            ]
        });
    });

    // Xử lý upload thumbnail
    const thumbnailUpload = document.getElementById('thumbnailUpload');
    const thumbnailPreview = document.getElementById('thumbnailPreview');
    const thumbnailInput = document.getElementById('thumbnail');
    
    if (thumbnailUpload && thumbnailPreview && thumbnailInput) {
        thumbnailUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Kiểm tra kích thước và loại file
                if (file.size > 5 * 1024 * 1024) {
                    alert('Kích thước file không được vượt quá 5MB');
                    this.value = '';
                    return;
                }
                
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WEBP)');
                    this.value = '';
                    return;
                }
                
                // Hiển thị preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    thumbnailPreview.src = e.target.result;
                    thumbnailPreview.classList.remove('d-none');
                    thumbnailInput.value = file.name; // Update hidden input
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Xử lý tải lên nhiều tập phim
    const episodeUploadForm = document.getElementById('episodeUploadForm');
    const addEpisodeBtn = document.getElementById('addEpisodeBtn');
    const episodeContainer = document.getElementById('episodeContainer');
    
    if (episodeUploadForm && addEpisodeBtn && episodeContainer) {
        let episodeCount = document.querySelectorAll('.episode-item').length;
        
        addEpisodeBtn.addEventListener('click', function() {
            episodeCount++;
            
            const episodeItem = document.createElement('div');
            episodeItem.className = 'episode-item card mb-3';
            episodeItem.innerHTML = `
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tập ${episodeCount}</h5>
                    <button type="button" class="btn btn-sm btn-danger remove-episode">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" name="episodes[${episodeCount}][title]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Số tập</label>
                            <input type="number" class="form-control" name="episodes[${episodeCount}][episode_number]" value="${episodeCount}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Thời lượng (phút)</label>
                            <input type="number" class="form-control" name="episodes[${episodeCount}][duration]" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Link video (480p)</label>
                            <input type="text" class="form-control" name="episodes[${episodeCount}][video_url]" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Link video (720p)</label>
                            <input type="text" class="form-control" name="episodes[${episodeCount}][video_720p]">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Link video (1080p)</label>
                            <input type="text" class="form-control" name="episodes[${episodeCount}][video_1080p]">
                        </div>
                    </div>
                </div>
            `;
            
            episodeContainer.appendChild(episodeItem);
            
            // Xử lý nút xóa tập
            const removeBtn = episodeItem.querySelector('.remove-episode');
            removeBtn.addEventListener('click', function() {
                episodeItem.remove();
            });
        });
        
        // Xử lý nút xóa tập trên các tập đã có
        document.querySelectorAll('.remove-episode').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.episode-item').remove();
            });
        });
    }

    // Xử lý xem trước mật khẩu
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Thay đổi icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Khởi tạo sortable cho danh sách có thể sắp xếp
    const sortableLists = document.querySelectorAll('.sortable-list');
    
    sortableLists.forEach(list => {
        new Sortable(list, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onSort: function(evt) {
                // Cập nhật thứ tự sau khi sắp xếp
                const items = list.querySelectorAll('.sortable-item');
                items.forEach((item, index) => {
                    const orderInput = item.querySelector('.item-order');
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                });
            }
        });
    });

    // Xử lý filter nhanh trong danh sách
    const quickFilter = document.getElementById('quickFilter');
    
    if (quickFilter) {
        quickFilter.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });
    }

    // Xử lý batch actions
    const batchActionForm = document.getElementById('batchActionForm');
    const batchActionSelect = document.getElementById('batchAction');
    const batchActionBtn = document.getElementById('batchActionBtn');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (batchActionForm && batchActionSelect && batchActionBtn && selectAllCheckbox) {
        // Xử lý chọn tất cả
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            
            // Enable/disable nút thực hiện
            batchActionBtn.disabled = !this.checked;
        });
        
        // Xử lý khi chọn/bỏ chọn từng item
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
                batchActionBtn.disabled = checkedCount === 0;
                
                // Cập nhật trạng thái selectAll
                selectAllCheckbox.checked = checkedCount === document.querySelectorAll('.item-checkbox').length;
            });
        });
        
        // Xử lý submit form
        batchActionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            if (checkedItems.length === 0) {
                alert('Vui lòng chọn ít nhất một mục');
                return;
            }
            
            const action = batchActionSelect.value;
            if (!action) {
                alert('Vui lòng chọn hành động');
                return;
            }
            
            // Xác nhận trước khi thực hiện
            if (action === 'delete' && !confirm('Bạn có chắc chắn muốn xóa các mục đã chọn?')) {
                return;
            }
            
            // Submit form
            this.submit();
        });
    }

    // Xử lý tabs trong trang chi tiết
    const tabLinks = document.querySelectorAll('.admin-tab-link');
    const tabContents = document.querySelectorAll('.admin-tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Bỏ active tất cả tabs
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.add('d-none'));
            
            // Active tab được chọn
            this.classList.add('active');
            const target = this.getAttribute('data-target');
            document.getElementById(target).classList.remove('d-none');
            
            // Lưu tab active vào localStorage
            localStorage.setItem('activeAdminTab', target);
        });
    });
    
    // Khôi phục tab active từ localStorage
    const activeTab = localStorage.getItem('activeAdminTab');
    if (activeTab) {
        const tabLink = document.querySelector(`.admin-tab-link[data-target="${activeTab}"]`);
        if (tabLink) {
            tabLink.click();
        }
    }
});
