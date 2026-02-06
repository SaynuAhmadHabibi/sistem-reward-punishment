// Sistem Reward & Punishment - JavaScript Functions

// Konstanta
const BASE_URL = window.location.origin + '/sistem-reward-punishment/';

/* =========================================================
   INITIALIZATION
========================================================= */
document.addEventListener('DOMContentLoaded', function () {
    try {
        initTooltips();
        initDataTables();
        initFormValidation();
        initCharts();
        initScrollAnimations();
        initRealTimeUpdates();
        initSidebar();
    } catch (err) {
        console.error('Init error:', err);
    }
});

/* =========================================================
   TOOLTIP & POPOVER
========================================================= */
function initTooltips() {
    if (typeof bootstrap === 'undefined') return;

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        new bootstrap.Popover(el);
    });
}

/* =========================================================
   DATATABLE
========================================================= */
function initDataTables() {
    if (typeof $.fn.DataTable === 'undefined') return;
    if ($('.datatable').length === 0) return;

    $('.datatable').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
        },
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25
    });
}

/* =========================================================
   FORM VALIDATION
========================================================= */
function initFormValidation() {
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

/* =========================================================
   TOAST NOTIFICATION
========================================================= */
function showToast(title, message, type = 'info') {
    $('.toast-container').remove();

    const toastContainer = $(`
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div class="toast align-items-center text-white bg-${type} border-0">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `);

    $('body').append(toastContainer);
    new bootstrap.Toast(toastContainer.find('.toast')[0]).show();
}

/* =========================================================
   TOPSIS REAL TIME (AMAN)
========================================================= */
function calculateTopsisRealTime() {
    const kinerja = parseFloat($('#kinerja').val()) || 0;
    const kedisiplinan = parseFloat($('#kedisiplinan').val()) || 0;
    const kerjasama = parseFloat($('#kerjasama').val()) || 0;
    const absensi = parseFloat($('#absensi').val()) || 0;

    const total = Math.sqrt(
        kinerja ** 2 +
        kedisiplinan ** 2 +
        kerjasama ** 2 +
        absensi ** 2
    );

    if (total === 0) return;

    const score = (
        (kinerja / total * 0.35) +
        (kedisiplinan / total * 0.25) +
        (kerjasama / total * 0.20) +
        (absensi / total * 0.20)
    );

    updateTopsisVisualization(score);
}

/* =========================================================
   TOPSIS VISUAL
========================================================= */
function updateTopsisVisualization(score) {
    const percentage = Math.min(Math.max(score * 100, 0), 100);
    $('.topsis-progress').css('width', percentage + '%');
    $('.topsis-score-text').text(score.toFixed(3));
}

/* =========================================================
   CHART (SAFE)
========================================================= */
function initCharts() {
    if (typeof Chart === 'undefined') return;

    const ctx = document.getElementById('topsisChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Reward', 'Punishment', 'Normal'],
            datasets: [{
                label: 'Distribusi Nilai',
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(39,174,96,0.7)',
                    'rgba(231,76,60,0.7)',
                    'rgba(243,156,18,0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
}

/* =========================================================
   SIDEBAR
========================================================= */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

/* =========================================================
   REAL TIME CLOCK
========================================================= */
function initRealTimeUpdates() {
    function updateTime() {
        $('.current-datetime').text(new Date().toLocaleString('id-ID'));
    }
    updateTime();
    setInterval(updateTime, 1000);
}

/* =========================================================
   GLOBAL ERROR HANDLER (FIXED ❗)
========================================================= */
window.onerror = function (msg, url, lineNo, columnNo, error) {
    console.error('JS Error:', msg, 'Line:', lineNo);

    showToast(
        'Error',
        'Terjadi kesalahan. Silakan hubungi admin.',
        'danger'
    );

    // ⛔ STOP refresh loop
    return true;
};
