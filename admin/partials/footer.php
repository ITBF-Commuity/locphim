        </div>
        <!-- End Main Content -->
    </div>
    <!-- End Content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <!-- Admin JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const adminContent = document.getElementById('adminContent');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    adminSidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                });
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    adminSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-dismiss alerts
            const alertList = document.querySelectorAll('.alert-auto-dismiss');
            alertList.forEach(function(alert) {
                setTimeout(function() {
                    const dismissButton = alert.querySelector('.btn-close');
                    if (dismissButton) {
                        dismissButton.click();
                    }
                }, 5000);
            });
            
            // Confirm delete
            const confirmForms = document.querySelectorAll('.confirm-delete-form');
            confirmForms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const message = form.dataset.confirmMessage || 'Bạn có chắc chắn muốn xóa mục này không?';
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // File input preview
            const fileInputs = document.querySelectorAll('.custom-file-input');
            fileInputs.forEach(function(input) {
                input.addEventListener('change', function(e) {
                    const fileName = e.target.files[0]?.name || 'Chọn tệp';
                    const label = input.nextElementSibling;
                    
                    if (label) {
                        label.textContent = fileName;
                    }
                    
                    // Image preview
                    const previewId = input.dataset.previewTarget;
                    if (previewId && e.target.files[0]) {
                        const preview = document.getElementById(previewId);
                        
                        if (preview) {
                            preview.src = URL.createObjectURL(e.target.files[0]);
                            preview.style.display = 'block';
                            
                            // Free memory when no longer needed
                            preview.onload = function() {
                                URL.revokeObjectURL(preview.src);
                            };
                        }
                    }
                });
            });
            
            // Select all checkboxes
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
            }
            
            // Slug generator
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
            if (titleInput && slugInput) {
                titleInput.addEventListener('keyup', function() {
                    if (!slugInput.dataset.manual) {
                        slugInput.value = generateSlug(titleInput.value);
                    }
                });
                
                // User changes slug manually
                slugInput.addEventListener('input', function() {
                    slugInput.dataset.manual = 'true';
                });
                
                // Generate slug button
                const generateSlugBtn = document.getElementById('generateSlugBtn');
                if (generateSlugBtn) {
                    generateSlugBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        slugInput.value = generateSlug(titleInput.value);
                        slugInput.dataset.manual = 'false';
                    });
                }
            }
            
            // Generate slug
            function generateSlug(text) {
                return text.toString().toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim()
                    .replace(/^-+|-+$/g, '');
            }
        });
    </script>
    
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>