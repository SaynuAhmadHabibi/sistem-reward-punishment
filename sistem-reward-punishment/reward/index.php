<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// =======================
// CEK LOGIN
// =======================
if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Data Reward";

// =======================
// FILTER PERIODE
// =======================
$periode = isset($_GET['periode']) ? sanitize($_GET['periode']) : date('Y-m');
$jenis_laporan = isset($_GET['jenis_laporan']) ? sanitize($_GET['jenis_laporan']) : 'semua';

// Validasi periode
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
    $periode = date('Y-m');
}

// Format nama bulan
$nama_bulan = date('F Y', strtotime($periode . '-01'));

// =======================
// QUERY DATA REWARD
// =======================
$sql = "SELECT r.*,
               k.nama AS karyawan_nama,
               k.nik,
               u.nama AS diberikan_oleh_nama,
               d.nama AS departemen_nama,
               j.nama AS jabatan_nama
        FROM reward r
        LEFT JOIN karyawan k ON r.karyawan_id = k.id
        LEFT JOIN users u ON r.diberikan_oleh = u.id
        LEFT JOIN departemen d ON k.departemen_id = d.id
        LEFT JOIN jabatan j ON k.jabatan_id = j.id
        WHERE DATE_FORMAT(r.tanggal, '%Y-%m') = ?
        ORDER BY r.tanggal DESC";

$rewards = getMultipleRows($sql, [$periode]);

// =======================
// STATISTIK
// =======================
$stats = getSingleRow("SELECT
    COUNT(*) as total_reward,
    SUM(nilai_reward) as total_nilai,
    AVG(topsis_score) as avg_score,
    COUNT(DISTINCT karyawan_id) as unique_karyawan,
    COUNT(CASE WHEN level = 'sangat_baik' THEN 1 END) as sangat_baik,
    COUNT(CASE WHEN level = 'baik' THEN 1 END) as baik,
    COUNT(CASE WHEN level = 'cukup' THEN 1 END) as cukup
    FROM reward WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?", [$periode]);

// =======================
// LIST BULAN
// =======================
$bulan_list = getMultipleRows("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan
    FROM reward
    ORDER BY bulan DESC");

// =======================
// CHART DATA
// =======================
$chart_data = getMultipleRows("SELECT
    DATE_FORMAT(tanggal, '%Y-%m-%d') as tanggal,
    COUNT(CASE WHEN level = 'sangat_baik' THEN 1 END) as sangat_baik,
    COUNT(CASE WHEN level = 'baik' THEN 1 END) as baik,
    COUNT(CASE WHEN level = 'cukup' THEN 1 END) as cukup
    FROM reward
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m-%d')
    ORDER BY tanggal", [$periode]);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-award me-2"></i>Data Reward - <?php echo $nama_bulan; ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo $stats['total_reward'] ?? 0; ?> Data</span>
                    </h5>
                    <div>
                        <a href="../laporan/cetak_bulanan.php?periode=<?php echo $periode; ?>&type=reward"
                           target="_blank" class="btn btn-light btn-sm">
                            <i class="fas fa-print me-1"></i>Cetak Laporan
                        </a>
                        <a href="../laporan/export_excel.php?periode=<?php echo $periode; ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Periode
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Periode (Bulan-Tahun)</label>
                            <select class="form-select" name="periode">
                                <option value="">Pilih Periode</option>
                                <?php foreach ($bulan_list as $bulan): ?>
                                    <option value="<?php echo $bulan['bulan']; ?>"
                                            <?php echo ($bulan['bulan'] == $periode) ? 'selected' : ''; ?>>
                                        <?php echo date('F Y', strtotime($bulan['bulan'] . '-01')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-search me-2"></i>Tampilkan Data
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Total Reward</small>
                            <h4 class="mb-0 fw-bold text-primary"><?php echo $stats['total_reward'] ?? 0; ?></h4>
                        </div>
                        <div class="text-primary opacity-25">
                            <i class="fas fa-award fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Karyawan Unik</small>
                            <h4 class="mb-0 fw-bold text-info"><?php echo $stats['unique_karyawan'] ?? 0; ?></h4>
                        </div>
                        <div class="text-info opacity-25">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Sangat Baik</small>
                            <h4 class="mb-0 fw-bold text-success"><?php echo $stats['sangat_baik'] ?? 0; ?></h4>
                        </div>
                        <div class="text-success opacity-25">
                            <i class="fas fa-star fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Avg Score</small>
                            <h4 class="mb-0 fw-bold text-danger"><?php echo number_format($stats['avg_score'] ?? 0, 3); ?></h4>
                        </div>
                        <div class="text-danger opacity-25">
                            <i class="fas fa-chart-line fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Reward -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Data Reward (<?php echo $stats['total_reward'] ?? 0; ?> records)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($rewards)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Tanggal</th>
                                        <th>Karyawan</th>
                                        <th>Departemen</th>
                                        <th>Jabatan</th>
                                        <th>Level</th>
                                        <th>Nilai</th>
                                        <th>Score TOPSIS</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rewards as $index => $reward): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo formatDate($reward['tanggal']); ?></td>
                                            <td>
                                                <small class="d-block"><?php echo htmlspecialchars($reward['nik']); ?></small>
                                                <?php echo htmlspecialchars($reward['karyawan_nama']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($reward['departemen_nama'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($reward['jabatan_nama'] ?: '-'); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo strtoupper(str_replace('_', ' ', $reward['level'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($reward['nilai_reward'] ?? 0, 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo number_format($reward['topsis_score'], 3); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($reward['keterangan']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-award fa-3x text-muted mb-3"></i>
                            <h4>Tidak ada data reward</h4>
                            <p class="text-muted">Belum ada reward yang tercatat dalam sistem untuk periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Functions
function resetFilter() {
    window.location.href = 'index.php';
}
</script>

<?php include '../includes/footer.php'; ?>
