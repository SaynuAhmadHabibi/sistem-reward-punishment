<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Data Punishment";

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

// Extract bulan dan tahun untuk filter
$bulan = date('m', strtotime($periode . '-01'));
$tahun = date('Y', strtotime($periode . '-01'));

// =======================
// QUERY DATA PUNISHMENT
// =======================
$sql = "SELECT p.*,
               k.nama as karyawan_nama,
               k.nik,
               u.nama as diberikan_oleh_nama,
               d.nama as departemen_nama,
               j.nama as jabatan_nama
        FROM punishment p
        LEFT JOIN karyawan k ON p.karyawan_id = k.id
        LEFT JOIN users u ON p.diberikan_oleh = u.id
        LEFT JOIN departemen d ON k.departemen_id = d.id
        LEFT JOIN jabatan j ON k.jabatan_id = j.id
        WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ?
        ORDER BY p.tanggal DESC";

$punishments = getMultipleRows($sql, [$periode]);

// =======================
// STATISTIK
// =======================
$stats = getSingleRow("SELECT
    COUNT(*) as total_punishment,
    AVG(topsis_score) as avg_score,
    COUNT(DISTINCT karyawan_id) as unique_karyawan,
    COUNT(CASE WHEN level = 'ringan' THEN 1 END) as ringan,
    COUNT(CASE WHEN level = 'sedang' THEN 1 END) as sedang,
    COUNT(CASE WHEN level = 'berat' THEN 1 END) as berat
    FROM punishment WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?", [$periode]);

// =======================
// ANALISIS PENYEBAB
// =======================
$analisis = [
    'ringan' => 0,
    'sedang' => 0,
    'berat' => 0
];

foreach ($punishments as $p) {
    $level = $p['level'] ?? '';
    if ($level == 'ringan') $analisis['ringan']++;
    elseif ($level == 'sedang') $analisis['sedang']++;
    elseif ($level == 'berat') $analisis['berat']++;
}

// =======================
// LIST BULAN DAN TAHUN
// =======================
$bulan_list = getMultipleRows("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan
    FROM punishment
    ORDER BY bulan DESC");

$tahun_list = getMultipleRows("SELECT DISTINCT YEAR(tanggal) as tahun
    FROM punishment
    ORDER BY tahun DESC");

// Jika tidak ada data, buat default
if (empty($tahun_list)) {
    $tahun_list = [['tahun' => date('Y')]];
}
if (empty($bulan_list)) {
    $bulan_list = [['bulan' => date('Y-m')]];
}

// Buat array nama bulan
$nama_bulan_array = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// =======================
// CHART DATA
// =======================
$chart_data = getMultipleRows("SELECT
    DATE_FORMAT(tanggal, '%Y-%m-%d') as tanggal,
    COUNT(CASE WHEN level = 'ringan' THEN 1 END) as ringan,
    COUNT(CASE WHEN level = 'sedang' THEN 1 END) as sedang,
    COUNT(CASE WHEN level = 'berat' THEN 1 END) as berat
    FROM punishment
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m-%d')
    ORDER BY tanggal", [$periode]);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid p-4">

    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Data Punishment - <?php echo $nama_bulan; ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo $stats['total_punishment'] ?? 0; ?> Data</span>
                    </h5>
                    <div>
                        <a href="../laporan/cetak_bulanan.php?periode=<?php echo $periode; ?>&type=punishment"
                           target="_blank" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-print me-1"></i>Cetak Laporan
                        </a>
                        <a href="../laporan/export_excel.php?periode=<?php echo $periode; ?>" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
                        <a href="cetak_sp.php" class="btn btn-light btn-sm">
                            <i class="fas fa-file-signature me-1"></i>Cetak SP
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Bulan</label>
                            <select class="form-select" name="bulan">
                                <?php foreach ($bulan_list as $item): ?>
                                    <?php
                                    $bulan_value = $item['bulan'];
                                    $bulan_num = date('m', strtotime($bulan_value . '-01'));
                                    $bulan_nama = $nama_bulan_array[$bulan_num] ?? $bulan_num;
                                    $tahun_bulan = date('Y', strtotime($bulan_value . '-01'));
                                    ?>
                                    <option value="<?= $bulan_num ?>" <?= ($bulan === $bulan_num && $tahun == $tahun_bulan) ? 'selected' : '' ?>>
                                        <?= $bulan_nama ?> <?= $tahun_bulan ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select class="form-select" name="tahun">
                                <?php foreach ($tahun_list as $item): ?>
                                    <option value="<?= $item['tahun'] ?>" <?= ($tahun == $item['tahun']) ? 'selected' : '' ?>>
                                        <?= $item['tahun'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>

                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                            <small class="text-muted">
                                Total: <?= $stats['total_punishment'] ?? 0 ?> punishment
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ANALISIS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Analisis Penyebab Punishment
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php foreach ($analisis as $label => $nilai): ?>
                            <div class="col-md-2 mb-3">
                                <div class="fw-bold text-capitalize"><?= str_replace('_', ' ', $label) ?></div>
                                <div class="display-6"><?= $nilai ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL -->
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                Data Punishment <?= $nama_bulan ?> <?= $tahun ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($punishments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Karyawan</th>
                                <th>NIK</th>
                                <th>Departemen</th>
                                <th>Jenis</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($punishments as $i => $p): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= formatDate($p['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($p['karyawan_nama']) ?></td>
                                    <td><?= $p['nik'] ?></td>
                                    <td><?= $p['departemen_nama'] ?? '-' ?></td>
                                    <td><?= $p['jenis_punishment'] ?? '-' ?></td>
                                    <td><?= $p['keterangan'] ?? '-' ?></td>
                                    <td>
                                        <a href="cetak.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>Belum ada data punishment untuk <?= $nama_bulan ?> <?= $tahun ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>