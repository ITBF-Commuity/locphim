/**
 * Lọc Phim - Admin Panel JavaScript
 * Version: 1.0
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Hiệu ứng dropdown
    setupDropdowns();
    
    // Toggle sidebar
    setupSidebar();
    
    // Hiệu ứng submenu
    setupSubmenus();
    
    // Thiết lập Select2
    setupSelect2();
    
    // Thiết lập Datepicker
    setupDatepicker();
    
    // Thiết lập Summernote
    setupSummernote();
    
    // Thiết lập dark mode
    setupDarkMode();
    
    // Thiết lập xác nhận xóa
    setupDeleteConfirmation();
    
    // Khởi tạo tooltips và popovers
    initTooltipsAndPopovers();
    
    // Hiển thị ảnh xem trước khi upload
    setupImagePreviews();
    
    // Thiết lập checkbox chọn tất cả
    setupSelectAll();
    
    // Thiết lập slug generator
    setupSlugGenerator();
    
    // Setup form validation
    setupFormValidation();
});

/**
 * Thiết lập các dropdown menu
 */
function setupDropdowns() {
    // Hiệu ứng dropdown header
    const headerDropdownToggles = document.querySelectorAll('.admin-header-link[id]');
    
    headerDropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = this.getAttribute('id');
            
            if (target === 'adminNotificationsToggle') {
                document.getElementById('adminNotificationsDropdown').classList.toggle('show');
                document.getElementById('adminUserDropdown').classList.remove('show');
            } else if (target === 'adminUserToggle') {
                document.getElementById('adminUserDropdown').classList.toggle('show');
                document.getElementById('adminNotificationsDropdown').classList.remove('show');
            } else if (target === 'adminThemeToggle') {
                document.body.classList.toggle('dark-mode');
                
                // Save theme preference
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('admin_theme', 'dark');
                    this.querySelector('i').classList.replace('fa-moon', 'fa-sun');
                } else {
                    localStorage.setItem('admin_theme', 'light');
                    this.querySelector('i').classList.replace('fa-sun', 'fa-moon');
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.admin-header-item, .admin-dropdown')) {
            document.querySelectorAll('.admin-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

/**
 * Thiết lập sidebar
 */
function setupSidebar() {
    const sidebarToggles = document.querySelectorAll('#sidebarToggle, #sidebarToggleMobile');
    
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
            
            // Save sidebar state
            const isCollapsed = document.querySelector('.admin-container').classList.contains('sidebar-collapsed');
            localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        });
    });
    
    // Restore sidebar state
    if (localStorage.getItem('admin_sidebar_collapsed') === 'true') {
        document.querySelector('.admin-container').classList.add('sidebar-collapsed');
    }
}

/**
 * Thiết lập submenus
 */
function setupSubmenus() {
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement;
            const submenu = this.nextElementSibling;
            
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                parent.classList.toggle('expanded');
            }
        });
    });
}

/**
 * Thiết lập Select2
 */
function setupSelect2() {
    if (jQuery && jQuery.fn.select2) {
        jQuery('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
        
        // Thiết lập select2 tags
        jQuery('.select2-tags').select2({
            theme: 'bootstrap4',
            width: '100%',
            tags: true,
            tokenSeparators: [',']
        });
        
        // Thiết lập select2 với AJAX
        jQuery('.select2-ajax').each(function() {
            const $this = jQuery(this);
            const url = $this.data('url');
            const placeholder = $this.data('placeholder') || 'Tìm kiếm...';
            
            $this.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.items,
                            pagination: {
                                more: data.pagination && (params.page < data.pagination.total_pages)
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        });
    }
}

/**
 * Thiết lập Datepicker
 */
function setupDatepicker() {
    if (jQuery && jQuery.fn.datepicker) {
        jQuery('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            language: 'vi',
            autoclose: true
        });
        
        // Thiết lập date range picker
        if (jQuery.fn.daterangepicker) {
            jQuery('.daterangepicker').daterangepicker({
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
                    fromLabel: 'Từ',
                    toLabel: 'Đến',
                    customRangeLabel: 'Tùy chỉnh',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    firstDay: 1
                }
            });
        }
    }
}

/**
 * Thiết lập Summernote
 */
function setupSummernote() {
    if (jQuery && jQuery.fn.summernote) {
        jQuery('.summernote').summernote({
            height: 250,
            lang: 'vi-VN',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    // Upload image through AJAX
                    for (let i = 0; i < files.length; i++) {
                        uploadSummernoteImage(files[i], this);
                    }
                }
            }
        });
    }
}

/**
 * Upload Summernote image
 */
function uploadSummernoteImage(file, editor) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('ajax/upload-image.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            jQuery(editor).summernote('insertImage', data.url, data.filename);
        } else {
            alert('Lỗi upload ảnh: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error uploading image:', error);
        alert('Lỗi upload ảnh. Vui lòng thử lại sau.');
    });
}

/**
 * Thiết lập dark mode
 */
function setupDarkMode() {
    // Restore dark mode state
    if (localStorage.getItem('admin_theme') === 'dark') {
        document.body.classList.add('dark-mode');
        const themeToggle = document.getElementById('adminThemeToggle');
        if (themeToggle && themeToggle.querySelector('i')) {
            themeToggle.querySelector('i').classList.replace('fa-moon', 'fa-sun');
        }
    }
}

/**
 * Thiết lập xác nhận xóa
 */
function setupDeleteConfirmation() {
    document.querySelectorAll('.delete-confirm').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.')) {
                e.preventDefault();
            }
        });
    });
    
    // Multiple delete confirmation
    const deleteForm = document.getElementById('multipleDeleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]:checked');
            
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất một mục để xóa.');
                return false;
            }
            
            if (!confirm(`Bạn có chắc chắn muốn xóa ${checkboxes.length} mục đã chọn không? Hành động này không thể hoàn tác.`)) {
                e.preventDefault();
                return false;
            }
        });
    }
}

/**
 * Khởi tạo tooltips và popovers
 */
function initTooltipsAndPopovers() {
    // Tooltips
    if (jQuery && jQuery.fn.tooltip) {
        jQuery('[data-toggle="tooltip"]').tooltip();
    }
    
    // Popovers
    if (jQuery && jQuery.fn.popover) {
        jQuery('[data-toggle="popover"]').popover();
    }
}

/**
 * Hiển thị ảnh xem trước khi upload
 */
function setupImagePreviews() {
    document.querySelectorAll('.image-upload').forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.getElementById(this.dataset.preview);
            
            if (preview) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            }
        });
    });
}

/**
 * Thiết lập checkbox chọn tất cả
 */
function setupSelectAll() {
    const selectAll = document.getElementById('selectAll');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Khi tất cả checkbox được chọn, select all cũng được chọn
        const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('input[name="delete_ids[]"]:checked').length === checkboxes.length;
                selectAll.checked = allChecked;
            });
        });
    }
}

/**
 * Thiết lập slug generator
 */
function setupSlugGenerator() {
    document.querySelectorAll('.slug-source').forEach(input => {
        input.addEventListener('input', function() {
            const target = document.getElementById(this.dataset.target);
            
            if (target) {
                const slug = generateSlug(this.value);
                target.value = slug;
            }
        });
    });
}

/**
 * Tạo slug từ chuỗi
 */
function generateSlug(text) {
    let slug = text.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[đĐ]/g, 'd')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    return slug;
}

/**
 * Thiết lập form validation
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    if (forms) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
}

/**
 * Hiển thị thông báo
 */
function showNotification(message, type = 'success', timeout = 3000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show admin-notification-float`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, timeout);
    }, 100);
}

/**
 * Thiết lập form AJAX
 */
function setupAjaxForms() {
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            const submitButton = form.querySelector('[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            
            // Collect form data
            const formData = new FormData(form);
            
            // Make AJAX request
            fetch(form.action, {
                method: form.method,
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                // Show notification
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Reset form if needed
                    if (form.dataset.reset === 'true') {
                        form.reset();
                    }
                    
                    // Redirect if needed
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                    
                    // Reload if needed
                    if (form.dataset.reload === 'true') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                    
                    // Callback if exists
                    if (window[form.dataset.callback]) {
                        window[form.dataset.callback](data);
                    }
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                // Show error
                showNotification('Đã xảy ra lỗi. Vui lòng thử lại sau.', 'danger');
            });
        });
    });
}