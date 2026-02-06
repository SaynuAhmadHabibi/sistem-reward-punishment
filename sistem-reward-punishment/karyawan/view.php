<?php
require_once '../includes/auth_helper.php';
require_once '../includes/functions.php';

/* ===============================
   AUTH & AKSES
================================ */
requireLogin();

if (
    !hasPermission('karyawan_view') &&
    !isHRDAdmin() &&
    !isAdmin() &&
    !isDirektur()
) {
    redirect('../dashboard.php');
}

$page_title = "Detail Karyawan";

/* ===============================
   VALIDASI ID
================================ */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID karyawan tidak valid!';
    redirect('../karyawan/');
}

$id = (int) $_GET['id'];

/* ===============================
   AMBIL DATA
================================ */
$karyawan = getSingleRow(
    "SELECT k.*, d.nama AS departemen_nama, d.kode AS departemen_kode,
            j.nama AS jabatan_nama, j.kode AS jabatan_kode
     FROM karyawan k
     LEFT JOIN departemen d ON k.departemen_id = d.id
     LEFT JOIN jabatan j ON k.jabatan_id = j.id
     WHERE k.id = ?",
    [$id]
);

if (!$karyawan) {
    $_SESSION['error'] = 'Data karyawan tidak ditemukan!';
    redirect('../karyawan/');
}

$penilaian_terbaru = getSingleRow(
    "SELECT * FROM penilaian 
     WHERE karyawan_id = ? 
     ORDER BY tanggal_penilaian DESC LIMIT 1",
    [$id]
);

$total_penilaian = getSingleRow(
    "SELECT COUNT(*) AS total FROM penilaian WHERE karyawan_id = ?",
    [$id]
)['total'];

$total_reward = getSingleRow(
    "SELECT COUNT(*) AS total FROM reward WHERE karyawan_id = ?",
    [$id]
)['total'];

$total_punishment = getSingleRow(
    "SELECT COUNT(*) AS total FROM punishment WHERE karyawan_id = ?",
    [$id]
)['total'];

$riwayat_penilaian = getMultipleRows(
    "SELECT * FROM penilaian 
     WHERE karyawan_id = ? 
     ORDER BY tanggal_penilaian DESC LIMIT 5",
    [$id]
);

$page_subtitle = "Detail Data Karyawan";
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-4">

        <!-- Profil -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Profil Karyawan</h5>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($karyawan['foto'])): ?>
                    <img src="../../uploads/karyawan/<?php echo $karyawan['foto']; ?>"
                         class="img-thumbnail rounded-circle mb-3"
                         style="width:150px;height:150px;">
                <?php else: ?>
                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto"
                         style="width:150px;height:150px;">
                        <i class="fas fa-user text-white fs-1"></i>
                    </div>
                <?php endif; ?>

                <h4><?php echo htmlspecialchars($karyawan['nama']); ?></h4>
                <p class="text-muted"><?php echo $karyawan['nik']; ?></p>

                <span class="badge bg-<?php echo $karyawan['status']=='aktif'?'success':'danger'; ?>">
                    <?php echo ucfirst($karyawan['status']); ?>
                </span>

                <div class="row mt-3">
                    <div class="col-6">
                        <strong class="text-primary"><?php echo $total_penilaian; ?></strong>
                        <div class="small text-muted">Penilaian</div>
                    </div>
                    <div class="col-6">
                        <strong class="text-success"><?php echo $total_reward; ?></strong>
                        <div class="small text-muted">Reward</div>
                    </div>
                </div>
            </div>

            <?php if (isHRDAdmin() || isAdmin()): ?>
            <div class="card-footer bg-light d-flex justify-content-between">
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button class="btn btn-danger btn-sm"
                        onclick="confirmDelete(<?php echo $id; ?>,'<?php echo htmlspecialchars($karyawan['nama']); ?>')">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kontak -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-address-book me-2"></i>Kontak</h6>
            </div>
            <div class="card-body">
                <p><i class="fas fa-envelope me-2"></i><?php echo $karyawan['email']; ?></p>
                <p><i class="fas fa-phone me-2"></i><?php echo $karyawan['telepon']; ?></p>
                <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($karyawan['alamat']); ?></p>
            </div>
        </div>
    </div>

    <!-- KANAN -->
    <div class="col-md-8">

        <!-- Statistik -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistik Performa</h5>
            </div>
            <div class="card-body row text-center">
                <div class="col-md-3">
                    <h3><?php echo $total_penilaian; ?></h3>
                    <small>Total Penilaian</small>
                </div>
                <div class="col-md-3">
                    <h3><?php echo $total_reward; ?></h3>
                    <small>Reward</small>
                </div>
                <div class="col-md-3">
                    <h3><?php echo $total_punishment; ?></h3>
                    <small>Punishment</small>
                </div>
                <div class="col-md-3">
                    <h3><?php echo calculateAge($karyawan['tanggal_lahir']); ?></h3>
                    <small>Usia</small>
                </div>
            </div>
        </div>

        <!-- Riwayat Penilaian -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Penilaian</h5>
            </div>
            <div class="card-body">
                <?php if ($riwayat_penilaian): ?>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kinerja</th>
                            <th>Kedisiplinan</th>
                            <th>Kerjasama</th>
                            <th>Absensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat_penilaian as $p): ?>
                        <tr>
                            <td><?php echo formatDate($p['tanggal_penilaian']); ?></td>
                            <td><?php echo $p['kinerja']; ?></td>
                            <td><?php echo $p['kedisiplinan']; ?></td>
                            <td><?php echo $p['kerjasama']; ?></td>
                            <td><?php echo $p['absensi']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info">Belum ada penilaian.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="delete.php" style="display:none;">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
</form>

<script>
function confirmDelete(id, nama) {
    if (confirm(`Hapus karyawan "${nama}"?`)) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
