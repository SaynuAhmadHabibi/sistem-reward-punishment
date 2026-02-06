<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// FIX: Tambahkan akses untuk Direktur menggunakan fungsi canViewReport() atau isDirektur()
requireLogin();

if (!hasPermission('generate_reports')) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman laporan.';
    redirect('../dashboard.php');
}


$page_title = "Laporan";

// Default periode (bulan ini)
$periode = isset($_GET['periode']) ? sanitize($_GET['periode']) : date('Y-m');
$jenis_laporan = isset($_GET['jenis_laporan']) ? sanitize($_GET['jenis_laporan']) : 'semua';

// Validasi periode
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
    $periode = date('Y-m');
}

// Format nama bulan
$nama_bulan = date('F Y', strtotime($periode . '-01'));

// Get data untuk laporan
$conn = connectDB();

// Statistik umum
$stats = getSingleRow("SELECT 
    (SELECT COUNT(*) FROM karyawan WHERE status = 'aktif') as total_karyawan,
    (SELECT COUNT(*) FROM penilaian WHERE DATE_FORMAT(tanggal_penilaian, '%Y-%m') = ?) as total_penilaian,
    (SELECT COUNT(*) FROM reward WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?) as total_reward,
    (SELECT COUNT(*) FROM punishment WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?) as total_punishment,
    (SELECT AVG(topsis_score) FROM reward WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?) as avg_reward_score,
    (SELECT AVG(topsis_score) FROM punishment WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?) as avg_punishment_score", 
    [$periode, $periode, $periode, $periode, $periode]);

// Data reward
$rewards = [];
if ($jenis_laporan == 'semua' || $jenis_laporan == 'reward') {
    $rewards = getMultipleRows("SELECT r.*, k.nama as karyawan_nama, k.nik, u.nama as diberikan_oleh_nama
        FROM reward r
        LEFT JOIN karyawan k ON r.karyawan_id = k.id
        LEFT JOIN users u ON r.diberikan_oleh = u.id
        WHERE DATE_FORMAT(r.tanggal, '%Y-%m') = ?
        ORDER BY r.tanggal DESC", [$periode]);
}

// Data punishment
$punishments = [];
if ($jenis_laporan == 'semua' || $jenis_laporan == 'punishment') {
    $punishments = getMultipleRows("SELECT p.*, k.nama as karyawan_nama, k.nik, u.nama as diberikan_oleh_nama
        FROM punishment p
        LEFT JOIN karyawan k ON p.karyawan_id = k.id
        LEFT JOIN users u ON p.diberikan_oleh = u.id
        WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ?
        ORDER BY p.tanggal DESC", [$periode]);
}

// Data penilaian (untuk analisis)
$penilaian_data = getMultipleRows("SELECT p.*, k.nama as karyawan_nama, k.nik, 
    (p.kinerja + p.kedisiplinan + p.kerjasama + (100 - p.absensi * 3.33)) / 4 as nilai_total
    FROM penilaian p
    LEFT JOIN karyawan k ON p.karyawan_id = k.id
    WHERE DATE_FORMAT(p.tanggal_penilaian, '%Y-%m') = ?
    ORDER BY nilai_total DESC", [$periode]);

// Data untuk level terbanyak
$most_reward_level = getSingleRow("SELECT level, COUNT(*) as count FROM reward WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? GROUP BY level ORDER BY count DESC LIMIT 1", [$periode]);
$most_punishment_level = getSingleRow("SELECT level, COUNT(*) as count FROM punishment WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? GROUP BY level ORDER BY count DESC LIMIT 1", [$periode]);

// Data untuk chart bulanan (tahun ini)
$year = date('Y', strtotime($periode . '-01'));
$monthly_data = getMultipleRows("SELECT
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    SUM(CASE WHEN type = 'reward' THEN 1 ELSE 0 END) as reward_count,
    SUM(CASE WHEN type = 'punishment' THEN 1 ELSE 0 END) as punishment_count
FROM (
    SELECT tanggal, 'reward' as type FROM reward WHERE YEAR(tanggal) = ?
    UNION ALL
    SELECT tanggal, 'punishment' as type FROM punishment WHERE YEAR(tanggal) = ?
) as combined
GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
ORDER BY bulan", [$year, $year]);

// Data untuk distribusi kategori
$level_distribution = getMultipleRows("SELECT
    CASE WHEN type = 'reward' THEN CONCAT('Reward ', REPLACE(level, '_', ' '))
         ELSE CONCAT('Punishment ', REPLACE(level, '_', ' ')) END as label,
    COUNT(*) as count,
    type
FROM (
    SELECT level, 'reward' as type FROM reward WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    UNION ALL
    SELECT level, 'punishment' as type FROM punishment WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
) as combined
GROUP BY type, level
ORDER BY type, level", [$periode, $periode]);

// Get list bulan untuk dropdown
$bulan_list = getMultipleRows("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan 
    FROM (
        SELECT tanggal FROM reward 
        UNION 
        SELECT tanggal FROM punishment
        UNION
        SELECT tanggal_penilaian as tanggal FROM penilaian
    ) as semua_data
    ORDER BY bulan DESC");

$page_subtitle = "Laporan Bulanan Reward & Punishment";
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>Laporan Bulanan
                    </h5>
                    <div>
                        <a href="cetak_bulanan.php?periode=<?php echo $periode; ?>" target="_blank" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-print me-1"></i>Cetak Laporan PDF
                        </a>
                        <a href="export_excel.php?periode=<?php echo $periode; ?>" class="btn btn-light btn-sm">
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
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Laporan
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3" id="filterForm">
                    <div class="col-md-4">
                        <label class="form-label">Periode (Bulan-Tahun)</label>
                        <select class="form-select" name="periode" id="periodeSelect">
                            <option value="">Pilih Periode</option>
                            <?php foreach ($bulan_list as $bulan): ?>
                                <option value="<?php echo $bulan['bulan']; ?>" 
                                        <?php echo ($bulan['bulan'] == $periode) ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($bulan['bulan'] . '-01')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Jenis Laporan</label>
                        <select class="form-select" name="jenis_laporan">
                            <option value="semua" <?php echo ($jenis_laporan == 'semua') ? 'selected' : ''; ?>>Semua Data</option>
                            <option value="reward" <?php echo ($jenis_laporan == 'reward') ? 'selected' : ''; ?>>Reward Only</option>
                            <option value="punishment" <?php echo ($jenis_laporan == 'punishment') ? 'selected' : ''; ?>>Punishment Only</option>
                            <option value="analisis" <?php echo ($jenis_laporan == 'analisis') ? 'selected' : ''; ?>>Analisis Penilaian</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-2"></i>Tampilkan Laporan
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Custom Date Range -->
                <div class="row mt-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">Custom Range</span>
                            <input type="date" class="form-control" id="dateFrom" value="<?php echo date('Y-m-01', strtotime($periode . '-01')); ?>">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="dateTo" value="<?php echo date('Y-m-t', strtotime($periode . '-01')); ?>">
                            <button class="btn btn-outline-info" type="button" onclick="applyCustomRange()">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Reward</h6>
                        <h2 class="mt-2 mb-0"><?php echo $stats['total_reward'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-award fa-2x opacity-50"></i>
                </div>
                <small class="opacity-75"><?php echo $nama_bulan; ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Punishment</h6>
                        <h2 class="mt-2 mb-0"><?php echo $stats['total_punishment'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
                <small class="opacity-75"><?php echo $nama_bulan; ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Reward Terbanyak</h6>
                        <h2 class="mt-2 mb-0">
                            <?php
                            if ($most_reward_level) {
                                echo ucwords(str_replace('_', ' ', $most_reward_level['level']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-trophy fa-2x opacity-50"></i>
                </div>
                <?php if ($most_reward_level): ?>
                    <small class="opacity-75"><?php echo $most_reward_level['count']; ?> kasus</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Punishment Terbanyak</h6>
                        <h2 class="mt-2 mb-0">
                            <?php
                            if ($most_punishment_level) {
                                echo ucwords(str_replace('_', ' ', $most_punishment_level['level']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                </div>
                <?php if ($most_punishment_level): ?>
                    <small class="opacity-75"><?php echo $most_punishment_level['count']; ?> kasus</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Laporan Detail -->
<div class="row">
    <?php if ($jenis_laporan == 'semua' || $jenis_laporan == 'reward'): ?>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-award me-2"></i>Data Reward - <?php echo $nama_bulan; ?>
                    <span class="badge bg-light text-dark float-end"><?php echo count($rewards); ?> Data</span>
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($rewards)): ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Karyawan</th>
                                    <th>Level</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rewards as $reward): ?>
                                    <tr>
                                        <td><?php echo formatDate($reward['tanggal']); ?></td>
                                        <td>
                                            <small class="d-block"><?php echo $reward['nik']; ?></small>
                                            <?php echo htmlspecialchars($reward['karyawan_nama']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo strtoupper(str_replace('_', ' ', $reward['level'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($reward['topsis_score'], 3); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="cetak_bulanan.php?periode=<?php echo $periode; ?>&type=reward" 
                           target="_blank" class="btn btn-sm btn-success">
                            <i class="fas fa-print me-1"></i>Cetak Detail
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-award fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Tidak ada data reward untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($jenis_laporan == 'semua' || $jenis_laporan == 'punishment'): ?>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Data Punishment - <?php echo $nama_bulan; ?>
                    <span class="badge bg-light text-dark float-end"><?php echo count($punishments); ?> Data</span>
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($punishments)): ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Karyawan</th>
                                    <th>Level</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($punishments as $punishment): ?>
                                    <tr>
                                        <td><?php echo formatDate($punishment['tanggal']); ?></td>
                                        <td>
                                            <small class="d-block"><?php echo $punishment['nik']; ?></small>
                                            <?php echo htmlspecialchars($punishment['karyawan_nama']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?php echo strtoupper(str_replace('_', ' ', $punishment['level'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo number_format($punishment['topsis_score'], 3); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="cetak_bulanan.php?periode=<?php echo $periode; ?>&type=punishment" 
                           target="_blank" class="btn btn-sm btn-danger">
                            <i class="fas fa-print me-1"></i>Cetak Detail
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Tidak ada data punishment untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($jenis_laporan == 'semua' || $jenis_laporan == 'analisis'): ?>
<!-- Analisis Penilaian -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Analisis Penilaian - <?php echo $nama_bulan; ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($penilaian_data)): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Rank</th>
                                            <th>Karyawan</th>
                                            <th>Kinerja</th>
                                            <th>Kedisiplinan</th>
                                            <th>Kerjasama</th>
                                            <th>Absensi</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($penilaian_data as $index => $penilaian): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo ($index < 3) ? 'warning' : 'secondary'; ?>">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="d-block"><?php echo $penilaian['nik']; ?></small>
                                                    <?php echo htmlspecialchars($penilaian['karyawan_nama']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $penilaian['kinerja'] >= 80 ? 'success' : ($penilaian['kinerja'] >= 60 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $penilaian['kinerja']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $penilaian['kedisiplinan'] >= 80 ? 'success' : ($penilaian['kedisiplinan'] >= 60 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $penilaian['kedisiplinan']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $penilaian['kerjasama'] >= 80 ? 'success' : ($penilaian['kerjasama'] >= 60 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $penilaian['kerjasama']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $penilaian['absensi'] <= 3 ? 'success' : ($penilaian['absensi'] <= 7 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $penilaian['absensi']; ?> hari
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?php echo number_format($penilaian['nilai_total'], 2); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Statistik Kriteria</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Hitung rata-rata setiap kriteria
                                    $avg_kinerja = array_sum(array_column($penilaian_data, 'kinerja')) / count($penilaian_data);
                                    $avg_kedisiplinan = array_sum(array_column($penilaian_data, 'kedisiplinan')) / count($penilaian_data);
                                    $avg_kerjasama = array_sum(array_column($penilaian_data, 'kerjasama')) / count($penilaian_data);
                                    $avg_absensi = array_sum(array_column($penilaian_data, 'absensi')) / count($penilaian_data);
                                    ?>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Kinerja</span>
                                            <span><?php echo number_format($avg_kinerja, 1); ?></span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $avg_kinerja; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Kedisiplinan</span>
                                            <span><?php echo number_format($avg_kedisiplinan, 1); ?></span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-info" style="width: <?php echo $avg_kedisiplinan; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Kerjasama</span>
                                            <span><?php echo number_format($avg_kerjasama, 1); ?></span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-warning" style="width: <?php echo $avg_kerjasama; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Absensi</span>
                                            <span><?php echo number_format($avg_absensi, 1); ?> hari</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-danger" style="width: <?php echo ($avg_absensi / 30) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="text-center">
                                        <h5>Rata-rata Total</h5>
                                        <div class="display-6 text-primary">
                                            <?php echo number_format(array_sum(array_column($penilaian_data, 'nilai_total')) / count($penilaian_data), 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Tidak ada data penilaian untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chart Visualization -->
<div class="row mb-4">
    <!-- Bar Chart: Perbandingan Bulanan Reward vs Punishment -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Perbandingan Bulanan Reward vs Punishment
                </h6>
                <small class="text-light">Tahun <?php echo $year; ?></small>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Pie Chart: Distribusi Kategori -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Distribusi Kategori Reward & Punishment
                </h6>
                <small class="text-light"><?php echo $nama_bulan; ?></small>
            </div>
            <div class="card-body">
                <canvas id="distributionChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Summary Report -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-file-pdf me-2"></i>Summary Report
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-file-pdf fa-3x text-primary mb-3"></i>
                                <h5>Laporan Lengkap</h5>
                                <p class="text-muted">Semua data dalam satu dokumen</p>
                                <a href="cetak_bulanan.php?periode=<?php echo $periode; ?>&type=semua" 
                                   target="_blank" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <h5>Export Excel</h5>
                                <p class="text-muted">Data untuk analisis lebih lanjut</p>
                                <a href="export_excel.php?periode=<?php echo $periode; ?>" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>Download Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-pie fa-3x text-info mb-3"></i>
                                <h5>Dashboard Analytics</h5>
                                <p class="text-muted">Visualisasi data interaktif</p>
                                <a href="analytics.php?periode=<?php echo $periode; ?>" 
                                   class="btn btn-info">
                                    <i class="fas fa-external-link-alt me-2"></i>Lihat Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
// Chart.js initialization
document.addEventListener('DOMContentLoaded', function() {
    // Bar Chart: Monthly Comparison
    <?php if (!empty($monthly_data)): ?>
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyLabels = <?php echo json_encode(array_map(function($item) {
        return date('M Y', strtotime($item['bulan'] . '-01'));
    }, $monthly_data)); ?>;

    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Reward',
                    data: <?php echo json_encode(array_column($monthly_data, 'reward_count')); ?>,
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                },
                {
                    label: 'Punishment',
                    data: <?php echo json_encode(array_column($monthly_data, 'punishment_count')); ?>,
                    backgroundColor: '#dc3545',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Kasus'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Bulan'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' kasus';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Pie Chart: Category Distribution
    <?php if (!empty($level_distribution)): ?>
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    const distributionLabels = <?php echo json_encode(array_column($level_distribution, 'label')); ?>;
    const distributionData = <?php echo json_encode(array_column($level_distribution, 'count')); ?>;
    const distributionTypes = <?php echo json_encode(array_column($level_distribution, 'type')); ?>;

    // Create colors based on type
    const distributionColors = distributionTypes.map(type => type === 'reward' ? '#28a745' : '#dc3545');

    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: distributionLabels,
            datasets: [{
                data: distributionData,
                backgroundColor: distributionColors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});

// Functions
function resetFilter() {
    window.location.href = 'index.php';
}

function applyCustomRange() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    if (!dateFrom || !dateTo) {
        alert('Please select both dates');
        return;
    }
    
    // Format: YYYY-MM
    const fromDate = new Date(dateFrom);
    const toDate = new Date(dateTo);
    const periode = fromDate.getFullYear() + '-' + String(fromDate.getMonth() + 1).padStart(2, '0');
    
    // Update the period select
    document.getElementById('periodeSelect').value = periode;
    
    // Submit the form
    document.getElementById('filterForm').submit();
}
</script>

<style>
.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.table-responsive::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.progress {
    border-radius: 10px;
}

.badge {
    font-weight: 500;
}
</style>

<?php include '../includes/footer.php'; ?>