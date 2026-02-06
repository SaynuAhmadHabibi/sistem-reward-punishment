<?php
/**
 * Dashboard dengan Role-based Access Control - FIXED
 * Menampilkan informasi user dan permissions berdasarkan role
 * 
 * FIXED:
 * - Dashboard menampilkan konten berbeda untuk setiap role
 * - Admin & HRD: Lihat semua statistik
 * - Direktur: Lihat ringkasan laporan saja (bukan statistik raw)
 * - Statistik hanya tampil untuk role yang berhak
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_helper.php';
require_once 'includes/header.php';

// Cek login
requireLogin();

// Get user info - with safety checks
$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? 'user';
$userPermissions = $_SESSION['user_permissions'] ?? [];
$userNama = $_SESSION['user_nama'] ?? 'User';

// Get role display name
$userRole = $_SESSION['user_role'] ?? 'user';
$roleDisplayName = getRoleDisplayName($userRole);

// Statistik untuk dashboard - hanya query jika user berhak
$totalKaryawan = 0;
$totalPenilaian = 0;
$totalReward = 0;
$totalPunishment = 0;

// FIX: ONLY ADMIN & HRD dapat lihat statistik raw
if (isAdminOrHRD()) {
    $totalKaryawan = getSingleRow("SELECT COUNT(*) as total FROM karyawan WHERE status = 'aktif'")['total'];
    $totalPenilaian = getSingleRow("SELECT COUNT(*) as total FROM penilaian")['total'];
    $totalReward = getSingleRow("SELECT COUNT(*) as total FROM reward")['total'];
    $totalPunishment = getSingleRow("SELECT COUNT(*) as total FROM punishment")['total'];
}
?>

<!-- Sidebar menu ditampilkan dari header.php -->
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">
                        <?php 
                        if (isAdminOrHRD()) {
                            echo "Ringkasan cepat sistem dan aktivitas terakhir";
                        } elseif (isDirektur()) {
                            echo "Ringkasan eksekutif dan laporan kinerja";
                        } else {
                            echo "Selamat datang di sistem";
                        }
                        ?>
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- FIX: ROLE-BASED CONTENT - ADMIN & HRD LIHAT STATISTIK -->
            <?php if (isAdminOrHRD()): ?>
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <a href="<?php echo BASE_URL; ?>karyawan/" class="clickable-card">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($totalKaryawan); ?></h4>
                                    <small class="text-muted">Total Karyawan Aktif</small>
                                </div>
                                <div class="text-primary fs-3">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?php echo BASE_URL; ?>penilaian/" class="clickable-card">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($totalPenilaian); ?></h4>
                                    <small class="text-muted">Total Penilaian</small>
                                </div>
                                <div class="text-success fs-3">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?php echo BASE_URL; ?>reward/" class="clickable-card">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($totalReward); ?></h4>
                                    <small class="text-muted">Total Reward</small>
                                </div>
                                <div class="text-warning fs-3">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <a href="<?php echo BASE_URL; ?>punishment/" class="clickable-card">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($totalPunishment); ?></h4>
                                    <small class="text-muted">Total Punishment</small>
                                </div>
                                <div class="text-danger fs-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- FIX: DIREKTUR LIHAT RINGKASAN LAPORAN SAJA -->
            <?php if (isDirektur()): ?>
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-file-alt text-primary"></i> Laporan Terbaru
                            </h5>
                            <p class="text-muted small">
                                Akses laporan kinerja dan analisis karyawan
                            </p>
                            <a href="<?php echo BASE_URL; ?>laporan/" class="btn btn-sm btn-primary">
                                <i class="fas fa-arrow-right"></i> Buka Laporan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-success">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-chart-bar text-success"></i> Analisis Kinerja
                            </h5>
                            <p class="text-muted small">
                                Lihat grafik kinerja dan trend karyawan
                            </p>
                            <a href="<?php echo BASE_URL; ?>laporan/" class="btn btn-sm btn-success">
                                <i class="fas fa-arrow-right"></i> Lihat Analisis
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-info">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-info-circle text-info"></i> Informasi Umum
                            </h5>
                            <p class="text-muted small">
                                Akses informasi profil dan permission test
                            </p>
                            <a href="<?php echo BASE_URL; ?>profile.php" class="btn btn-sm btn-info">
                                <i class="fas fa-arrow-right"></i> Lihat Profil
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info: Direktur Read-Only -->
            <div class="alert alert-info" role="alert">
                <i class="fas fa-shield-alt"></i>
                <strong>Akses Anda:</strong> Sebagai Direktur, Anda memiliki akses READ-ONLY ke semua data. 
                Anda dapat melihat laporan, analisis kinerja, dan informasi karyawan, tetapi tidak dapat membuat, 
                mengedit, atau menghapus data.
            </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <div class="timeline-item mb-3">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <p class="mb-0"><strong>Login Sistem</strong></p>
                                <small class="text-muted">Anda baru saja login ke sistem</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: User Info & Quick Links -->
        <div class="col-lg-4">
            <!-- User Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-body text-center">
                    <div class="avatar mb-3">
                        <i class="fas fa-user-circle" style="font-size: 3rem; color: #3498db;"></i>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($userNama); ?></h5>
                    <p class="text-muted mb-3">
                        <span class="badge 
                            <?php 
                            if (isAdmin()) {
                                echo 'bg-warning text-dark';
                            } elseif (isHRDAdmin()) {
                                echo 'bg-danger';
                            } elseif (isDirektur()) {
                                echo 'bg-primary';
                            } else {
                                echo 'bg-secondary';
                            }
                            ?>">
                            <?php echo htmlspecialchars($roleDisplayName); ?>
                        </span>
                    </p>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>profile.php" class="btn btn-sm btn-outline-primary w-100 mb-2">
                        <i class="fas fa-user"></i> Lihat Profil
                    </a>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Permissions Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">Akses Anda</h5>
                </div>
                <div class="card-body">
                    <div class="permission-list">
                        <?php 
                        // For Admin and HRD Admin, show all permissions as green
                        $isFullAccess = isAdmin() || isHRDAdmin();
                        
                        $permissions = $userPermissions;
                        $activePermissions = [];
                        
                        if (is_array($permissions)) {
                            foreach ($permissions as $perm => $value) {
                                if ($value === true) {
                                    $activePermissions[] = $perm;
                                }
                            }
                        }
                        
                        // Show key permissions
                        $keyPermissions = [
                            'manage_employees' => 'Kelola Karyawan',
                            'manage_evaluations' => 'Kelola Penilaian',
                            'manage_rewards' => 'Kelola Reward',
                            'manage_punishments' => 'Kelola Punishment',
                            'generate_reports' => 'Generate Laporan',
                            'manage_users' => 'Kelola User',
                            'backup_data' => 'Backup Data',
                            'write' => 'Edit Data',
                            'delete' => 'Hapus Data'
                        ];
                        
                        foreach ($keyPermissions as $permCode => $permLabel) {
                            // If Admin or HRD Admin, always show as green (allowed)
                            $hasIt = $isFullAccess ? true : in_array($permCode, $activePermissions);
                            ?>
                            <div class="permission-item mb-2">
                                <small>
                                    <i class="fas <?php echo $hasIt ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                                    <?php echo htmlspecialchars($permLabel); ?>
                                </small>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php if (isAdminOrHRD()): ?>
                        <a href="<?php echo BASE_URL; ?>karyawan/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-users text-primary"></i> Data Karyawan
                        </a>
                        <a href="<?php echo BASE_URL; ?>penilaian/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-chart-line text-success"></i> Penilaian
                        </a>
                        <a href="<?php echo BASE_URL; ?>reward/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-trophy text-warning"></i> Reward
                        </a>
                        <a href="<?php echo BASE_URL; ?>punishment/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-exclamation-triangle text-danger"></i> Punishment
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isAdminOrHRD() || isDirektur()): ?>
                        <a href="<?php echo BASE_URL; ?>laporan/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-file-alt text-info"></i> Laporan
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isAdminOrHRD()): ?>
                        <a href="<?php echo BASE_URL; ?>users/" class="list-group-item list-group-item-action small">
                            <i class="fas fa-user-cog text-secondary"></i> Manajemen User
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>permission_test.php" class="list-group-item list-group-item-action small">
                            <i class="fas fa-shield-alt text-muted"></i> Permission Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
require_once 'includes/footer.php';
?>