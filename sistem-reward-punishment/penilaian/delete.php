<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Admin dan HRD Admin bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin dan HRD admin yang dapat menghapus penilaian.';
    redirect('../dashboard.php');
}

$page_title = "Hapus Penilaian";

// Cek apakah ID tersedia
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    $_SESSION['error'] = 'ID penilaian tidak valid!';
    redirect('index.php');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id']);

// Get data penilaian
$penilaian = getSingleRow("SELECT p.*, k.nama as karyawan_nama, k.nik, u.nama as penilai_nama
                          FROM penilaian p
                          LEFT JOIN karyawan k ON p.karyawan_id = k.id
                          LEFT JOIN users u ON p.penilai_id = u.id
                          WHERE p.id = ?", [$id]);

if (!$penilaian) {
    $_SESSION['error'] = 'Data penilaian tidak ditemukan!';
    redirect('index.php');
}

// Proses penghapusan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $conn = connectDB();

    try {
        // Mulai transaksi
        mysqli_begin_transaction($conn);

        // Hapus hasil TOPSIS jika ada
        $delete_topsis_sql = "DELETE FROM topsis_results WHERE penilaian_id = ?";
        $topsis_stmt = mysqli_prepare($conn, $delete_topsis_sql);
        mysqli_stmt_bind_param($topsis_stmt, 'i', $id);
        mysqli_stmt_execute($topsis_stmt);

        // Hapus data penilaian
        $delete_penilaian_sql = "DELETE FROM penilaian WHERE id = ?";
        $penilaian_stmt = mysqli_prepare($conn, $delete_penilaian_sql);
        mysqli_stmt_bind_param($penilaian_stmt, 'i', $id);

        if (mysqli_stmt_execute($penilaian_stmt)) {
            // Commit transaksi
            mysqli_commit($conn);

            logActivity('delete_penilaian', "Menghapus penilaian untuk karyawan: {$penilaian['karyawan_nama']} (ID: {$penilaian['karyawan_id']})");
            redirect('index.php', 'Penilaian berhasil dihapus');
        } else {
            throw new Exception('Gagal menghapus data penilaian: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        redirect('index.php');
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trash me-2"></i>Konfirmasi Hapus Penilaian
                    </h5>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>PERINGATAN
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <strong>Apakah Anda yakin ingin menghapus penilaian ini?</strong>
                        <p class="mb-0">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait.</p>
                    </div>

                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Detail Penilaian:</h6>
                            <div class="row">
                                <div class="col-sm-4"><strong>Karyawan:</strong></div>
                                <div class="col-sm-8"><?php echo htmlspecialchars($penilaian['karyawan_nama']); ?> (<?php echo $penilaian['nik']; ?>)</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Tanggal:</strong></div>
                                <div class="col-sm-8"><?php echo formatDate($penilaian['tanggal_penilaian']); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Penilai:</strong></div>
                                <div class="col-sm-8"><?php echo htmlspecialchars($penilaian['penilai_nama']); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Kinerja:</strong></div>
                                <div class="col-sm-8"><?php echo $penilaian['kinerja']; ?>/100</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Kedisiplinan:</strong></div>
                                <div class="col-sm-8"><?php echo $penilaian['kedisiplinan']; ?>/100</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Kerjasama:</strong></div>
                                <div class="col-sm-8"><?php echo $penilaian['kerjasama']; ?>/100</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Absensi:</strong></div>
                                <div class="col-sm-8"><?php echo $penilaian['absensi']; ?> hari</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form method="POST" id="deleteForm">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="confirm_delete" value="1">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-danger" onclick="return confirmDelete()">
                                    <i class="fas fa-trash me-2"></i>Hapus Penilaian
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    return confirm('Apakah Anda benar-benar ingin menghapus penilaian ini? Tindakan ini tidak dapat dibatalkan.');
}

// Prevent accidental form submission
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    if (!confirmDelete()) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>