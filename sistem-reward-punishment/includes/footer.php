<?php
/**
 * Footer Template
 * Template footer untuk semua halaman
 */

// Cek jika functions.php sudah diinclude
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Get base URL
$base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/sistem-reward-punishment/';

// Get current year and server time
$current_year = date('Y');
$server_time = date('d/m/Y H:i:s');

// Get user name from session
$user_name = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guest';
?>
                <?php if (isLoggedIn()): ?>
                </div><!-- End .main-content-area -->
            </div><!-- End .content-container -->
            
            <!-- Footer -->
            <footer class="footer mt-auto py-3" style="background: linear-gradient(135deg, var(--bg-primary, #0f172a) 0%, var(--bg-secondary, #1a2332) 100%); border-top: 1px solid var(--border-color, #2e3e4f); color: var(--text-secondary, #cbd5e1);">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span style="color: var(--text-muted, #94a3b8);">
                                <i class="fas fa-copyright me-1" style="color: var(--accent-green, #10b981);"></i>
                                <?php echo $current_year; ?> Sistem Reward & Punishment. All rights reserved.
                            </span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span style="color: var(--text-muted, #94a3b8);">
                                <i class="fas fa-clock me-1" style="color: var(--accent-green, #10b981);"></i>
                                Server Time: <?php echo $server_time; ?>
                                <span class="mx-2" style="color: var(--border-color, #2e3e4f);">|</span>
                                <i class="fas fa-user me-1" style="color: var(--accent-green, #10b981);"></i>
                                User: <?php echo $user_name; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </footer>
        </div><!-- End .main-content -->
    </div><!-- End .wrapper -->
    <?php else: ?>
    </div><!-- End .auth-wrapper -->
    <?php endif; ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo $base_url; ?>assets/js/ui-enhancement.js"></script>

    
    <script>
    $(document).ready(function() {
        // CSRF Token Setup
        const csrfToken = document.querySelector('#csrf_token') ? document.querySelector('#csrf_token').value : '';
        if (csrfToken) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            });
        }
        
        // AJAX Error Handling
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            console.error('AJAX Error:', jqxhr.status, thrownError);
            
            if (jqxhr.status === 401) {
                toastr.error('Session expired. Redirecting to login...');
                setTimeout(() => window.location.href = '<?php echo $base_url; ?>login.php', 2000);
            } else if (jqxhr.status === 403) {
                toastr.error('Access denied!');
            } else if (jqxhr.status === 419) {
                toastr.error('CSRF token mismatch. Refreshing page...');
                setTimeout(() => window.location.reload(), 2000);
            } else if (jqxhr.status === 422) {
                try {
                    const errors = JSON.parse(jqxhr.responseText).errors;
                    Object.values(errors).forEach(error => toastr.error(error));
                } catch (e) {
                    toastr.error('Validation error occurred.');
                }
            } else if (jqxhr.status === 500) {
                toastr.error('Server error. Please try again.');
            }
        });
        
        // Initialize components
        initializeComponents();
        
        // Utility functions
        setupUtilityFunctions();
    });
    
    function initializeComponents() {
        // Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Bootstrap popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
        
        // Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        // DataTables
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            responsive: true,
            pageLength: 25,
            order: [],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
        });
        
        // Toastr configuration
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 5000
        };
        
        // Event listeners
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Mobile sidebar toggle
        $('.sidebar-toggle').on('click', function() {
            $('.sidebar').toggleClass('active');
        });
        
        // Auto-hide alerts
        setTimeout(() => $('.alert-auto-hide').alert('close'), 5000);
        
        // Prevent form double submission
        $('form').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            if (submitBtn.length) {
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                // Restore after 10 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.prop('disabled', false).html(originalText);
                }, 10000);
            }
        });
        
        // Confirm delete
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            const message = $(this).data('confirm') || 'Are you sure you want to delete?';
            if (confirm(message)) {
                $(this).closest('form').submit();
            }
        });
        
        // Number input formatting
        $('.number-input').on('input', function() {
            $(this).val($(this).val().replace(/[^\d]/g, ''));
        });
        
        // Print
        $('.btn-print').on('click', function() {
            window.print();
        });
        
        // Image preview
        $('.image-preview-input').on('change', function() {
            const input = this;
            const preview = $(this).data('preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(preview).attr('src', e.target.result).show();
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
        
        // Real-time clock
        function updateClock() {
            const now = new Date();
            $('.current-datetime').text(now.toLocaleString('id-ID'));
        }
        setInterval(updateClock, 1000);
        updateClock();
    }
    
    function setupUtilityFunctions() {
        // Format number
        window.formatNumber = function(number, decimals = 0) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        };
        
        // Format currency
        window.formatCurrency = function(amount) {
            return 'Rp ' + window.formatNumber(amount, 0);
        };
        
        // Format date
        window.formatDate = function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID');
        };
        
        // Loading overlay
        window.showLoading = function(message = 'Loading...') {
            $('body').append(`
                <div id="loading-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    flex-direction: column;
                ">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-3">${message}</p>
                </div>
            `);
        };
        
        window.hideLoading = function() {
            $('#loading-overlay').remove();
        };
        
        // Toast notification
        window.showToast = function(type, message, title = '') {
            toastr[type](message, title);
        };
        
        // Confirm dialog
        window.showConfirm = function(message, callback) {
            if (confirm(message) && typeof callback === 'function') {
                callback();
            }
        };
        
        // Simple TOPSIS calculation
        window.calculateTopsisRealTime = function() {
            const criteria = ['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'];
            let total = 0;
            
            criteria.forEach(criterion => {
                const value = parseFloat($(`#${criterion}`).val()) || 0;
                total += Math.pow(value, 2);
            });
            
            if (total === 0) return 0;
            
            const normalized = {};
            criteria.forEach(criterion => {
                const value = parseFloat($(`#${criterion}`).val()) || 0;
                normalized[criterion] = value / Math.sqrt(total);
            });
            
            const preference = (
                normalized.kinerja * 0.35 +
                normalized.kedisiplinan * 0.25 +
                normalized.kerjasama * 0.20 +
                (1 - normalized.absensi) * 0.20
            );
            
            window.updateTopsisVisualization(preference);
            return preference;
        };
        
        window.updateTopsisVisualization = function(score) {
            const percentage = Math.min(Math.max(score * 100, 0), 100);
            
            $('.topsis-progress').css('width', percentage + '%');
            $('.topsis-score-text').text(score.toFixed(4));
            
            let status = 'normal';
            let statusClass = 'warning';
            
            if (score >= 0.7) {
                status = 'REWARD';
                statusClass = 'success';
            } else if (score <= 0.3) {
                status = 'PUNISHMENT';
                statusClass = 'danger';
            }
            
            $('.topsis-status').text(status)
                .removeClass('badge-success badge-danger badge-warning')
                .addClass('badge-' + statusClass);
            
            $('.topsis-marker').css('left', percentage + '%');
        };
        
        // Form validation
        window.validateForm = function(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            let isValid = true;
            
            // Clear previous errors
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').remove();
            
            // Validate required fields
            $(form).find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid').after(
                        '<div class="invalid-feedback">This field is required</div>'
                    );
                }
            });
            
            // Validate email
            $(form).find('input[type="email"]').each(function() {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email && !emailRegex.test(email)) {
                    isValid = false;
                    $(this).addClass('is-invalid').after(
                        '<div class="invalid-feedback">Invalid email format</div>'
                    );
                }
            });
            
            return isValid;
        };
    }
    </script>
    
    <?php if (isset($page_js)): ?>
    <script><?php echo $page_js; ?></script>
    <?php endif; ?>
    
</body>
</html>