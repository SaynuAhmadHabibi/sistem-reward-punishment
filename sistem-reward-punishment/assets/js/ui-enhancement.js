/**
 * UI ENHANCEMENT JAVASCRIPT
 * Dark Mode Features & Interactivity
 * 
 * IMPORTANT: Hanya untuk UI enhancement
 * Semua logic backend dan CRUD operations TETAP AMAN
 */

(function() {
    'use strict';
    
    // ================================
    // 1. INITIALIZE UI
    // ================================
    document.addEventListener('DOMContentLoaded', function() {
        initializeDarkUI();
        setupCharts();
        enhanceTableInteractivity();
        setupFormEnhancements();
        setupSidebarInteractivity();
        setupAnimations();
    });
    
    // ================================
    // 2. INITIALIZE DARK UI
    // ================================
    function initializeDarkUI() {
        console.log('Dark UI Initialized');
        
        // Add smooth transitions
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Set dark theme
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        
        // Initialize tooltips
        initializeTooltips();
        
        // Setup active menu
        setActiveMenu();
    }
    
    // ================================
    // 3. CHART SETUP
    // ================================
    function setupCharts() {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded');
            return;
        }
        
        // Set default chart colors for dark theme
        const chartDefaults = {
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderColor: '#10b981',
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#10b981',
            pointHoverBackgroundColor: '#34d399',
            pointHoverBorderColor: '#34d399',
            gridColor: 'rgba(16, 185, 129, 0.1)',
            textColor: '#f1f5f9'
        };
        
        // Update Chart.js defaults
        Chart.defaults.color = chartDefaults.textColor;
        Chart.defaults.borderColor = chartDefaults.gridColor;
        
        // Find and enhance all chart containers
        const chartContainers = document.querySelectorAll('[id*="chart"], [class*="chart"]');
        chartContainers.forEach(container => {
            enhanceChartContainer(container);
        });
    }
    
    function enhanceChartContainer(container) {
        // Add dark mode styling to chart container
        if (container.className.includes('chart-container')) {
            return; // Already styled
        }
        
        container.classList.add('fade-in');
        container.style.borderRadius = '12px';
    }
    
    // ================================
    // 4. TABLE INTERACTIVITY
    // ================================
    function enhanceTableInteractivity() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            // Add striped rows
            if (!table.classList.contains('table-striped')) {
                table.classList.add('table-striped');
            }
            
            // Add hover effect
            if (!table.classList.contains('table-hover')) {
                table.classList.add('table-hover');
            }
            
            // Enhance rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                // Add animation delay
                row.style.animation = `fadeIn 0.3s ease-out ${index * 0.05}s backwards`;
                
                // Add click effect for detail rows
                row.addEventListener('click', function(e) {
                    if (!e.target.closest('a, button')) {
                        const detail = this.querySelector('[data-detail]');
                        if (detail) {
                            this.classList.toggle('highlight-row');
                        }
                    }
                });
            });
            
            // Enhance action buttons in table
            const actionButtons = table.querySelectorAll('a[href*="edit"], a[href*="delete"], a[href*="view"]');
            actionButtons.forEach(btn => {
                if (!btn.classList.contains('btn')) {
                    btn.classList.add('btn', 'btn-sm');
                }
                
                // Add icons if not present
                if (btn.href.includes('edit') && !btn.querySelector('i')) {
                    btn.innerHTML = '<i class="fas fa-edit"></i> Edit';
                } else if (btn.href.includes('delete') && !btn.querySelector('i')) {
                    btn.innerHTML = '<i class="fas fa-trash"></i> Hapus';
                } else if (btn.href.includes('view') && !btn.querySelector('i')) {
                    btn.innerHTML = '<i class="fas fa-eye"></i> Lihat';
                }
            });
        });
        
        // Initialize DataTables if available
        if (typeof $ !== 'undefined' && $.fn.dataTable) {
            $('table').not('.no-datatable').DataTable({
                columnDefs: [{
                    orderable: false,
                    targets: 'no-sort'
                }],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        }
    }
    
    // ================================
    // 5. FORM ENHANCEMENTS
    // ================================
    function setupFormEnhancements() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Add form-floating class for better styling
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (!input.classList.contains('form-control') && !input.classList.contains('form-select')) {
                    if (input.tagName === 'TEXTAREA') {
                        input.classList.add('form-control');
                    } else if (input.tagName === 'SELECT') {
                        input.classList.add('form-select');
                    } else {
                        input.classList.add('form-control');
                    }
                }
                
                // Add validation classes
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
            
            // Form submit handler
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
        
        // Setup Select2 if available
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });
        }
    }
    
    function validateField(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            return false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            return true;
        }
    }
    
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // ================================
    // 6. SIDEBAR INTERACTIVITY
    // ================================
    function setupSidebarInteractivity() {
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        }
        
        // Setup menu active state
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
                
                // Close sidebar on mobile
                if (window.innerWidth <= 768 && sidebar) {
                    sidebar.classList.remove('show');
                }
            });
        });
    }
    
    // ================================
    // 7. SET ACTIVE MENU
    // ================================
    function setActiveMenu() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace(/^.*\//, ''))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    // ================================
    // 8. TOOLTIPS
    // ================================
    function initializeTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
    
    // ================================
    // 9. ANIMATIONS
    // ================================
    function setupAnimations() {
        // Animate stat cards
        const statCards = document.querySelectorAll('.stat-card, .card');
        statCards.forEach((card, index) => {
            card.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s backwards`;
        });
        
        // Animate numbers
        animateNumbers();
        
        // Setup scroll animations
        setupScrollAnimations();
    }
    
    function animateNumbers() {
        const numberElements = document.querySelectorAll('[data-number], .stat-number');
        
        numberElements.forEach(element => {
            const finalValue = parseInt(element.textContent.replace(/\D/g, ''));
            
            if (!isNaN(finalValue)) {
                const startValue = 0;
                const duration = 1000;
                const increment = finalValue / (duration / 16);
                let currentValue = startValue;
                
                const counter = setInterval(() => {
                    currentValue += increment;
                    
                    if (currentValue >= finalValue) {
                        element.textContent = finalValue.toLocaleString('id-ID');
                        clearInterval(counter);
                    } else {
                        element.textContent = Math.floor(currentValue).toLocaleString('id-ID');
                    }
                }, 16);
            }
        });
    }
    
    function setupScrollAnimations() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.card:not(.fade-in)').forEach(element => {
                observer.observe(element);
            });
        }
    }
    
    // ================================
    // 10. TOAST NOTIFICATIONS
    // ================================
    window.showToast = function(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} fade-in`;
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; min-width: 300px; z-index: 9999;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
        return container;
    }
    
    // ================================
    // 11. MODAL ENHANCEMENTS
    // ================================
    window.showModal = function(title, content, footer = '') {
        const modalId = 'dynamicModal';
        let modal = document.getElementById(modalId);
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = modalId;
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body').innerHTML = content;
        modal.querySelector('.modal-footer').innerHTML = footer;
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    };
    
    // ================================
    // 12. LOADING STATE
    // ================================
    window.setLoading = function(element, isLoading = true) {
        const btn = document.querySelector(element);
        if (!btn) return;
        
        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText || 'Simpan';
        }
    };
    
    // ================================
    // 13. UTILITY FUNCTIONS
    // ================================
    window.formatCurrency = function(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR'
        }).format(value);
    };
    
    window.formatDate = function(date) {
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    };
    
    window.formatNumber = function(value) {
        return new Intl.NumberFormat('id-ID').format(value);
    };
    
    // ================================
    // 14. CONFIRMATION DIALOG
    // ================================
    window.confirmDelete = function(message = 'Apakah Anda yakin ingin menghapus data ini?') {
        return confirm(message);
    };
    
    // ================================
    // 15. EXPORT UTILITY
    // ================================
    window.exportTableToExcel = function(tableId, filename = 'export.xlsx') {
        const table = document.getElementById(tableId);
        if (!table) {
            console.error('Table not found');
            return;
        }
        
        const html = table.outerHTML;
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/html;charset=utf-8,' + encodeURIComponent(html));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    };
    
    // ================================
    // 16. RESPONSIVE HANDLERS
    // ================================
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // ================================
    // 17. KEYBOARD SHORTCUTS
    // ================================
    document.addEventListener('keydown', function(e) {
        // Ctrl+S untuk save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const form = document.querySelector('form');
            if (form) {
                form.submit();
            }
        }
        
        // Escape untuk close modal
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                }
            });
        }
    });
    
    // ================================
    // 18. EXPORT VARIABLES & FUNCTIONS
    // ================================
    window.DarkUI = {
        animateNumbers,
        setupCharts,
        enhanceTableInteractivity,
        validateForm,
        validateField,
        formatCurrency,
        formatDate,
        formatNumber,
        showToast,
        showModal,
        setLoading,
        confirmDelete,
        exportTableToExcel
    };
    
})();