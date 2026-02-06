<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin yang bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Hanya admin dan HRD admin yang dapat menghapus user. Hanya admin yang dapat menghapus user.';
    redirect('../dashboard.php');
}

// Cek apakah ID tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID user tidak valid!';
    redirect('index.php');
}

$id = intval($_GET['id']);

// Tidak boleh hapus diri sendiri
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Anda tidak dapat menghapus akun sendiri!';
    redirect('index.php');
}

// Get data user untuk konfirmasi
$user = getSingleRow("SELECT username, nama, role FROM users WHERE id = ?", [$id]);

if (!$user) {
    $_SESSION['error'] = 'Data user tidak ditemukan!';
    redirect('index.php');
}

// Proses penghapusan
if (isset($_POST['confirm_delete'])) {
    $conn = connectDB();

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Hapus data terkait di tabel lain jika ada
        // (sesuaikan dengan struktur database Anda)

        // Hapus user
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (mysqli_stmt_execute($stmt)) {
            // Log aktivitas
            logActivity('USER_MANAGEMENT', "Menghapus user: {$user['username']} ({$user['role']})");

            // Commit transaksi
            mysqli_commit($conn);

            redirect('index.php', 'User berhasil dihapus');
        } else {
            throw new Exception('Gagal menghapus user: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        // Rollback jika ada error
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
                        <i class="fas fa-user-times me-2"></i>Hapus User
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
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Penghapusan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                    </div>

                    <div class="mb-4">
                        <h6>Detail User yang akan dihapus:</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td width="120"><strong>Username:</strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama:</strong></td>
                                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Role:</strong></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $user['role'] == 'admin' ? 'danger' :
                                             ($user['role'] == 'manager' ? 'warning' : 'info');
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center">
                        <p class="text-muted mb-4">
                            Apakah Anda yakin ingin menghapus user ini?
                        </p>

                        <form method="POST" class="d-inline">
                            <input type="hidden" name="confirm_delete" value="1">
                            <button type="submit" class="btn btn-danger me-2"
                                    onclick="return confirm('Apakah Anda benar-benar yakin ingin menghapus user ini?')">
                                <i class="fas fa-trash me-2"></i>Ya, Hapus User
                            </button>
                        </form>

                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>