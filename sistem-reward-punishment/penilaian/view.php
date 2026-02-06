<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Admin dan HRD Admin bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak!';
    redirect('../dashboard.php');
}

$page_title = "Detail Penilaian";

// Cek apakah ID tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID penilaian tidak valid!';
    redirect('index.php');
}

$id = intval($_GET['id']);

// Get data penilaian
$penilaian = getSingleRow("SELECT p.*, k.nama as karyawan_nama, k.nik, j.nama as jabatan, d.nama as departemen,
                                  u.nama as penilai_nama
                           FROM penilaian p
                           LEFT JOIN karyawan k ON p.karyawan_id = k.id
                           LEFT JOIN jabatan j ON k.jabatan_id = j.id
                           LEFT JOIN departemen d ON k.departemen_id = d.id
                           LEFT JOIN users u ON p.penilai_id = u.id
                           WHERE p.id = ?", [$id]);

if (!$penilaian) {
    $_SESSION['error'] = 'Data penilaian tidak ditemukan!';
    redirect('index.php');
}

// Get detail kriteria penilaian jika ada tabel terpisah
$detail_kriteria = [];
if (tableExists('penilaian_detail')) {
    $detail_kriteria = getMultipleRows("SELECT * FROM penilaian_detail WHERE penilaian_id = ? ORDER BY kriteria", [$id]);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i>Detail Penilaian
                    </h5>
                    <div>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm me-2">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="?action=delete&id=<?php echo $id; ?>" class="btn btn-danger btn-sm me-2"
                           onclick="return confirm('Hapus penilaian ini secara permanen?')">
                            <i class="fas fa-trash me-1"></i>Hapus
                        </a>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informasi Utama -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi Penilaian
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="140"><strong>ID Penilaian:</strong></td>
                                    <td><?php echo $penilaian['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Karyawan:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['karyawan_nama']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>NIK:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['nik']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Jabatan:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['jabatan']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Departemen:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['departemen']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="140"><strong>Penilai:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['penilai_nama']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal:</strong></td>
                                    <td><?php echo formatDate($penilaian['tanggal_penilaian']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status TOPSIS:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo $penilaian['topsis_status'] == 'calculated' ? 'success' :
                                                 ($penilaian['topsis_status'] == 'calculating' ? 'warning' : 'secondary');
                                        ?>">
                                            <?php echo ucfirst($penilaian['topsis_status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Nilai Preferensi:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo ($penilaian['topsis_preference'] ?? 0) >= 0.7 ? 'success' :
                                                 (($penilaian['topsis_preference'] ?? 0) >= 0.3 ? 'warning' : 'danger');
                                        ?> fs-6">
                                            <?php echo number_format($penilaian['topsis_preference'] ?? 0, 4); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <?php if (!empty($penilaian['keterangan'])): ?>
                        <div class="mt-3">
                            <strong>Keterangan:</strong><br>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($penilaian['keterangan'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Ringkasan Penilaian
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h2 text-<?php
                            $pref = $penilaian['topsis_preference'] ?? 0;
                            echo $pref >= 0.7 ? 'success' :
                                 ($pref >= 0.3 ? 'warning' : 'danger');
                        ?>">
                            <?php echo number_format($pref, 4); ?>
                        </div>
                        <small class="text-muted">Nilai Preferensi TOPSIS</small>
                    </div>

                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-<?php
                            echo $pref >= 0.7 ? 'success' :
                                 ($pref >= 0.3 ? 'warning' : 'danger');
                        ?>" role="progressbar" style="width: <?php echo ($pref * 100); ?>%"
                             aria-valuenow="<?php echo ($pref * 100); ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo number_format($pref * 100, 1); ?>%
                        </div>
                    </div>

                    <div class="text-center">
                        <small class="text-muted">
                            <?php
                            $category = $penilaian['topsis_category'] ?? '';
                            if ($category == 'reward') {
                                echo "Berhak Mendapat Reward";
                            } elseif ($category == 'punishment') {
                                echo "Perlu Punishment";
                            } else {
                                echo "Kinerja Normal";
                            }
                            ?>
                        </small>
                    </div>

                    <!-- Nilai Input -->
                    <div class="mt-3">
                        <h6 class="text-center mb-2">Nilai Input</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?php echo $penilaian['kinerja']; ?></div>
                                    <small class="text-muted">Kinerja</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?php echo $penilaian['kedisiplinan']; ?></div>
                                    <small class="text-muted">Disiplin</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?php echo $penilaian['kerjasama']; ?></div>
                                    <small class="text-muted">Kerjasama</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?php echo $penilaian['absensi']; ?></div>
                                    <small class="text-muted">Absensi</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Tambahan -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-calendar me-2"></i>Informasi Tambahan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-calendar-plus me-2"></i>
                        <span>Dibuat: <?php echo formatDate($penilaian['created_at']); ?></span>
                    </div>
                    <?php if (isset($penilaian['updated_at']) && $penilaian['updated_at']): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-calendar-check me-2"></i>
                            <span>Diupdate: <?php echo formatDate($penilaian['updated_at']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-edit me-2"></i>
                        <span>Penilai: <?php echo htmlspecialchars($penilaian['penilai_nama']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Kriteria Penilaian -->
    <?php if (!empty($detail_kriteria)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-check me-2"></i>Detail Kriteria Penilaian
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <th>Bobot</th>
                                    <th>Nilai</th>
                                    <th>Skor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_kriteria as $detail): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detail['kriteria']); ?></td>
                                        <td><?php echo $detail['bobot']; ?>%</td>
                                        <td><?php echo $detail['nilai']; ?>/100</td>
                                        <td><?php echo number_format(($detail['nilai'] * $detail['bobot'] / 100), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Riwayat Penilaian Karyawan -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Riwayat Penilaian Karyawan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Penilai</th>
                                    <th>Nilai Akhir</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $riwayat = getMultipleRows("SELECT p.*, u.nama as penilai_nama
                                                           FROM penilaian p
                                                           LEFT JOIN users u ON p.penilai_id = u.id
                                                           WHERE p.karyawan_id = ?
                                                           ORDER BY p.tanggal_penilaian DESC
                                                           LIMIT 10", [$penilaian['karyawan_id']]);

                                if (!empty($riwayat)):
                                    foreach ($riwayat as $item):
                                ?>
                                    <tr class="<?php echo $item['id'] == $id ? 'table-active' : ''; ?>">
                                        <td><?php echo formatDate($item['tanggal_penilaian']); ?></td>
                                        <td><?php echo htmlspecialchars($item['penilai_nama']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                $pref = $item['topsis_preference'] ?? 0;
                                                echo $pref >= 0.7 ? 'success' :
                                                     ($pref >= 0.3 ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo number_format($pref, 4); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $item['topsis_status'] == 'calculated' ? 'success' :
                                                     ($item['topsis_status'] == 'calculating' ? 'warning' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst($item['topsis_status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($item['id'] != $id): ?>
                                                <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Penilaian Saat Ini</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Tidak ada riwayat penilaian</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>