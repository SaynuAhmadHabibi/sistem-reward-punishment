<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin yang bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Hanya admin dan HRD admin yang dapat mengedit user. Hanya admin yang dapat mengedit user.';
    redirect('../dashboard.php');
}

$page_title = "Edit User";

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

// Inisialisasi variabel
$errors = [];
$data = $user;

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    foreach ($_POST as $key => $value) {
        $data[$key] = sanitize($value);
    }

    // Validasi
    if (empty($data['username'])) {
        $errors['username'] = 'Username wajib diisi';
    } elseif (strlen($data['username']) < 3) {
        $errors['username'] = 'Username minimal 3 karakter';
    } else {
        // Cek username sudah ada (kecuali untuk user ini sendiri)
        $existing = getSingleRow("SELECT id FROM users WHERE username = ? AND id != ?", [$data['username'], $id]);
        if ($existing) {
            $errors['username'] = 'Username sudah digunakan';
        }
    }

    if (empty($data['nama'])) {
        $errors['nama'] = 'Nama wajib diisi';
    }

    if (empty($data['email'])) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    } else {
        // Cek email sudah ada (kecuali untuk user ini sendiri)
        $existing = getSingleRow("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $id]);
        if ($existing) {
            $errors['email'] = 'Email sudah digunakan';
        }
    }

    if (!in_array($data['role'], ['admin', 'manager', 'user'])) {
        $errors['role'] = 'Role tidak valid';
    }

    // Handle password change
    $update_password = false;
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $errors['new_password'] = 'Password baru minimal 6 karakter';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
        } else {
            $update_password = true;
        }
    }

    // Jika tidak ada error, update data
    if (empty($errors)) {
        $conn = connectDB();

        if ($update_password) {
            $password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, nama = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sssssi', $data['username'], $data['nama'], $data['email'], $data['role'], $password_hash, $id);
        } else {
            $sql = "UPDATE users SET username = ?, nama = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssi', $data['username'], $data['nama'], $data['email'], $data['role'], $id);
        }

        if (mysqli_stmt_execute($stmt)) {
            logActivity('USER_MANAGEMENT', "Mengupdate user: {$data['username']} ({$data['role']})");
            redirect('index.php', 'User berhasil diupdate');
        } else {
            $errors['database'] = 'Gagal mengupdate data: ' . mysqli_error($conn);
        }
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
                        <i class="fas fa-user-edit me-2"></i>Edit User
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
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="userForm">
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?php echo htmlspecialchars($data['username']); ?>" required>
                            <small class="form-text text-muted">Username akan digunakan untuk login</small>
                            <?php if (isset($errors['username'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Nama -->
                        <div class="mb-3">
                            <label for="nama" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama" name="nama"
                                   value="<?php echo htmlspecialchars($data['nama']); ?>" required>
                            <?php if (isset($errors['nama'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['nama']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($data['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo ($data['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="manager" <?php echo ($data['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="admin" <?php echo ($data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <small class="form-text text-muted">
                                Admin: Akses penuh ke semua fitur<br>
                                Manager: Akses ke karyawan dan penilaian<br>
                                User: Akses terbatas
                            </small>
                            <?php if (isset($errors['role'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['role']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Password Change Section -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-key me-1"></i>Ubah Password
                            </label>
                            <div class="border rounded p-3 bg-light">
                                <small class="form-text text-muted mb-2">Kosongkan jika tidak ingin mengubah password</small>

                                <div class="mb-2">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="form-text text-muted">Minimal 6 karakter</small>
                                    <?php if (isset($errors['new_password'])): ?>
                                        <div class="text-danger mt-1"><?php echo $errors['new_password']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-2">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="text-danger mt-1"><?php echo $errors['confirm_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-sm-6">
                                    <small class="text-muted">
                                        <strong>Dibuat:</strong> <?php echo formatDate($user['created_at']); ?>
                                    </small>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted">
                                        <strong>Login Terakhir:</strong> <?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : '-'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <?php if (isset($errors['database'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $errors['database']; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = ['username', 'nama', 'email', 'role'];

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });

    // Check password match if new password is provided
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword && newPassword.length < 6) {
        document.getElementById('new_password').classList.add('is-invalid');
        isValid = false;
    }

    if (newPassword && newPassword !== confirmPassword) {
        document.getElementById('confirm_password').classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        showToast('error', 'Harap lengkapi semua field dengan benar!');
        return false;
    }
});

// Real-time validation
document.querySelectorAll('input, select').forEach(element => {
    element.addEventListener('blur', function() {
        if (this.hasAttribute('required') && !this.value.trim()) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    element.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });
});

// Password confirmation check
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;

    if (confirmPassword && newPassword !== confirmPassword) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>

<?php include '../includes/footer.php'; ?>