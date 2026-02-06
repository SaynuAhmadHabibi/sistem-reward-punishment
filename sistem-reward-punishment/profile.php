<?php
require_once 'includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$user = getSingleRow("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($nama)) {
        $error = "Nama tidak boleh kosong!";
    } elseif (empty($email)) {
        $error = "Email tidak boleh kosong!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Cek email sudah ada (kecuali untuk user ini sendiri)
        $existing = getSingleRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
        if ($existing) {
            $error = "Email sudah digunakan!";
        }
    }

    if (empty($error)) {
        $conn = connectDB();

        // Update basic info
        $sql = "UPDATE users SET nama = ?, email = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $nama, $email, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Update session
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;

            // Handle password change
            if (!empty($current_password) && !empty($new_password)) {
                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 6) {
                            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_pass = "UPDATE users SET password = ? WHERE id = ?";
                            $stmt_pass = mysqli_prepare($conn, $update_pass);
                            mysqli_stmt_bind_param($stmt_pass, 'si', $password_hash, $user_id);

                            if (mysqli_stmt_execute($stmt_pass)) {
                                $success = "Profil dan password berhasil diupdate!";
                                logActivity('UPDATE_PROFILE', 'User mengupdate profil dan password');
                            } else {
                                $error = "Gagal mengupdate password!";
                            }
                        } else {
                            $error = "Password baru minimal 6 karakter!";
                        }
                    } else {
                        $error = "Password baru dan konfirmasi tidak cocok!";
                    }
                } else {
                    $error = "Password saat ini salah!";
                }
            } else {
                $success = "Profil berhasil diupdate!";
                logActivity('UPDATE_PROFILE', 'User mengupdate profil');
            }
        } else {
            $error = "Gagal mengupdate profil: " . mysqli_error($conn);
        }

        // Refresh user data
        $user = getSingleRow("SELECT * FROM users WHERE id = ?", [$user_id]);
    }
}
?>

<?php
// Page title for header
$page_title = 'Profil Saya';
include 'includes/header.php';

// Prepare safe display variables to avoid undefined index / null warnings
$display_name = '';
if (!empty($user['nama'])) {
    $display_name = $user['nama'];
} elseif (!empty($user['nama_lengkap'])) {
    $display_name = $user['nama_lengkap'];
} elseif (!empty($user['username'])) {
    $display_name = $user['username'];
} else {
    $display_name = 'User';
}

$display_initial = mb_strtoupper(mb_substr($display_name, 0, 1));
$role = !empty($user['role']) ? $user['role'] : 'user';
$username = !empty($user['username']) ? $user['username'] : '';
$email = !empty($user['email']) ? $user['email'] : '';
$created_at_display = !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-';
$last_login_display = !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-';
?>

        <main class="main-content">
            <div class="container-fluid p-4">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Profil Saya</h5>
                                <small class="text-muted">Kelola informasi akun Anda</small>
                            </div>
                            <div class="card-body">
                                <?php if($error): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>

                                <?php if($success): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input class="form-control" type="text" value="<?php echo htmlspecialchars($username); ?>" disabled>
                                            <small class="text-muted">Tidak dapat diubah</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Role</label>
                                            <input class="form-control" type="text" value="<?php echo ucfirst(htmlspecialchars($role)); ?>" disabled>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input class="form-control" type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama'] ?? $display_name); ?>" required>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input class="form-control" type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Terakhir Login</label>
                                            <input class="form-control" type="text" value="<?php echo $last_login_display; ?>" disabled>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Terdaftar Sejak</label>
                                        <input class="form-control" type="text" value="<?php echo $created_at_display; ?>" disabled>
                                    </div>

                                    <h6>Ubah Password</h6>
                                    <div class="row mb-3">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label">Password Saat Ini</label>
                                            <input class="form-control" type="password" id="current_password" name="current_password">
                                            <small class="text-muted">Kosongkan jika tidak ingin mengubah</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru</label>
                                            <input class="form-control" type="password" id="new_password" name="new_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Konfirmasi Password Baru</label>
                                            <input class="form-control" type="password" id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profil</button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">Kembali</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mb-3 text-center">
                            <div class="card-body">
                                <div class="profile-avatar" style="width:90px;height:90px;border-radius:50%;background:#f1f5f9;color:#1f2937;display:inline-flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:0.5rem;">
                                    <?php echo $display_initial; ?>
                                </div>
                                <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($display_name); ?></h5>
                                <p class="text-muted">@<?php echo htmlspecialchars($username); ?></p>
                                <p><span class="badge bg-primary me-1"><?php echo strtoupper(htmlspecialchars($role)); ?></span></p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Statistik Aktivitas</h6>
                                <?php
                                $stats = ['total_activities' => 0, 'last_activity' => null];
                                if (tableExists('activity_log')) {
                                    $result = getSingleRow("SELECT COUNT(*) as total_activities, MAX(created_at) as last_activity FROM activity_log WHERE user_id = ?", [$user_id]);
                                    if ($result) {
                                        $stats = $result;
                                    }
                                }
                                ?>
                                <p class="mb-2"><i class="fas fa-clock text-info me-2"></i>Total Aktivitas: <strong><?php echo htmlspecialchars($stats['total_activities'] ?? 0); ?></strong></p>
                                <p class="mb-0"><i class="fas fa-history text-warning me-2"></i>Terakhir: <strong><?php echo !empty($stats['last_activity']) ? date('d/m/Y H:i', strtotime($stats['last_activity'])) : '-'; ?></strong></p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Aksi Cepat</h6>
                                <a href="users/edit.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary mb-2 w-100">Edit Profil</a>
                                <a href="logout.php" class="btn btn-sm btn-outline-danger w-100">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>