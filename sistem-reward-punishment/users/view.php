<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan manager yang bisa akses
if (!isAdmin() && !isManager()) {
    $_SESSION['error'] = 'Akses ditolak!';
    redirect('../dashboard.php');
}

$page_title = "Detail User";

// Cek apakah ID tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID user tidak valid!';
    redirect('index.php');
}

$id = intval($_GET['id']);

// Get data user
$user = getSingleRow("SELECT * FROM users WHERE id = ?", [$id]);

if (!$user) {
    $_SESSION['error'] = 'Data user tidak ditemukan!';
    redirect('index.php');
}

// Get statistik aktivitas user (jika ada tabel activity_log)
$activity_stats = [];
if (tableExists('activity_log')) {
    $activity_stats = getSingleRow("
        SELECT
            COUNT(*) as total_activities,
            MAX(created_at) as last_activity
        FROM activity_log
        WHERE user_id = ?
    ", [$id]);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Detail User
                    </h5>
                    <div>
                        <?php if (isAdmin()): ?>
                            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger btn-sm me-2"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                <i class="fas fa-trash me-1"></i>Hapus
                            </a>
                        <?php endif; ?>
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
                        <i class="fas fa-info-circle me-2"></i>Informasi User
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="140"><strong>ID User:</strong></td>
                                    <td><?php echo $user['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Nama Lengkap:</strong></td>
                                    <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="140"><strong>Role:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo $user['role'] == 'admin' ? 'danger' :
                                                 ($user['role'] == 'manager' ? 'warning' : 'info');
                                        ?> fs-6">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Dibuat:</strong></td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Diupdate:</strong></td>
                                    <td><?php echo $user['updated_at'] ? formatDate($user['updated_at']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Login Terakhir:</strong></td>
                                    <td><?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Statistik
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($activity_stats)): ?>
                        <div class="text-center mb-3">
                            <div class="h3 text-primary"><?php echo $activity_stats['total_activities']; ?></div>
                            <small class="text-muted">Total Aktivitas</small>
                        </div>
                        <hr>
                        <div class="text-center">
                            <div class="h6 text-success">
                                <?php echo $activity_stats['last_activity'] ? formatDate($activity_stats['last_activity'], 'd/m/Y H:i') : '-'; ?>
                            </div>
                            <small class="text-muted">Aktivitas Terakhir</small>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <p>Data statistik belum tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Akun -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Status Akun
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : ($user['role'] == 'manager' ? 'user-tie' : 'user'); ?> me-2"></i>
                        <span><?php echo ucfirst($user['role']); ?> Account</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-calendar-check me-2"></i>
                        <span>Aktif sejak <?php echo formatDate($user['created_at'], 'M Y'); ?></span>
                    </div>
                    <?php if ($user['last_login']): ?>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2"></i>
                            <span>Login terakhir <?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : 'Belum pernah'; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Aktivitas (jika ada) -->
    <?php if (!empty($activity_stats) && $activity_stats['total_activities'] > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Riwayat Aktivitas Terbaru
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Aktivitas</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $activities = getMultipleRows("
                                    SELECT action, details, created_at
                                    FROM activity_log
                                    WHERE user_id = ?
                                    ORDER BY created_at DESC
                                    LIMIT 10
                                ", [$id]);

                                if (!empty($activities)):
                                    foreach ($activities as $activity):
                                ?>
                                    <tr>
                                        <td><?php echo formatDate($activity['created_at'], 'd/m/Y H:i'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Tidak ada data aktivitas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>