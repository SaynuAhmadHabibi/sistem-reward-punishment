<?php
/**
 * PERUBAHAN PADA FILE: penilaian/create.php
 * 
 * Yang berubah:
 * 1. Line 88: Ubah redirect('index.php', ...) menjadi redirect('calculate_topsis.php?id=' . $penilaian_id)
 * 2. Line 86: Dapatkan inserted ID untuk diteruskan ke calculate_topsis
 * 
 * Berikut adalah kode lengkap yang sudah diperbaiki:
 */
?>
<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Admin dan HRD Admin bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin dan HRD admin yang dapat menambah penilaian.';
    redirect('../dashboard.php');
}

$conn = connectDB();
$page_title = "Tambah Penilaian";

// Ambil data karyawan yang aktif
$sql_karyawan = "SELECT id, nama, nik, jabatan_id FROM karyawan WHERE status = ? ORDER BY nama ASC";
$stmt_karyawan = mysqli_prepare($conn, $sql_karyawan);
mysqli_stmt_bind_param($stmt_karyawan, 's', $status);
$status = 'aktif';
mysqli_stmt_execute($stmt_karyawan);
$result_karyawan = mysqli_stmt_get_result($stmt_karyawan);

// Ambil data kriteria TOPSIS
require_once '../config/topsis.php';
$criteria = TOPSIS_CRITERIA;

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $karyawan_id = sanitize($_POST['karyawan_id']);
    $tanggal_penilaian = sanitize($_POST['tanggal_penilaian']);
    $kinerja = (float) sanitize($_POST['kinerja']);
    $kedisiplinan = (float) sanitize($_POST['kedisiplinan']);
    $kerjasama = (float) sanitize($_POST['kerjasama']);
    $absensi = (float) sanitize($_POST['absensi']);
    $penilai_id = $_SESSION['user_id'];

    // Validasi input
    $errors = [];

    if (empty($karyawan_id)) {
        $errors['karyawan_id'] = 'Karyawan harus dipilih';
    }

    if (empty($tanggal_penilaian)) {
        $errors['tanggal_penilaian'] = 'Tanggal penilaian harus diisi';
    }

    // Validasi nilai kriteria
    if ($kinerja < 0 || $kinerja > 100) {
        $errors['kinerja'] = 'Nilai kinerja harus antara 0-100';
    }

    if ($kedisiplinan < 0 || $kedisiplinan > 100) {
        $errors['kedisiplinan'] = 'Nilai kedisiplinan harus antara 0-100';
    }

    if ($kerjasama < 0 || $kerjasama > 100) {
        $errors['kerjasama'] = 'Nilai kerjasama harus antara 0-100';
    }

    if ($absensi < 0 || $absensi > 30) {
        $errors['absensi'] = 'Nilai absensi harus antara 0-30 hari';
    }

    // Cek apakah karyawan sudah dinilai bulan ini
    if (empty($errors)) {
        $current_month = date('Y-m', strtotime($tanggal_penilaian));
        $check_sql = "SELECT id FROM penilaian WHERE karyawan_id = ? AND DATE_FORMAT(tanggal_penilaian, '%Y-%m') = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'is', $karyawan_id, $current_month);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $errors['duplicate'] = 'Karyawan ini sudah dinilai bulan ini';
        }
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        // Tambah field topsis_status untuk tracking status perhitungan TOPSIS
        $sql = "INSERT INTO penilaian (karyawan_id, penilai_id, tanggal_penilaian, kinerja, kedisiplinan, kerjasama, absensi, topsis_status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iisdddd', $karyawan_id, $penilai_id, $tanggal_penilaian, $kinerja, $kedisiplinan, $kerjasama, $absensi);

        if (mysqli_stmt_execute($stmt)) {
            // ===== PENTING: Ambil ID penilaian yang baru dibuat =====
            $penilaian_id = mysqli_insert_id($conn);
            
            // Log activity
            logActivity('create_penilaian', "Menambah penilaian untuk karyawan ID: $karyawan_id");
            
            // ===== PERUBAHAN: REDIRECT KE CALCULATE_TOPSIS BUKAN INDEX =====
            // SEBELUMNYA: redirect('index.php', 'Penilaian berhasil ditambahkan');
            // SESUDAHNYA: redirect('calculate_topsis.php?id=' . $penilaian_id);
            
            redirect('calculate_topsis.php?id=' . $penilaian_id);
            
        } else {
            $errors['database'] = 'Gagal menyimpan data: ' . mysqli_error($conn);
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
                        <i class="fas fa-plus me-2"></i>Tambah Penilaian Karyawan
                    </h5>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="penilaianForm">
                        <!-- Karyawan -->
                        <div class="mb-3">
                            <label for="karyawan_id" class="form-label">
                                <i class="fas fa-user me-1"></i>Karyawan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2" id="karyawan_id" name="karyawan_id" required>
                                <option value="">Pilih Karyawan</option>
                                <?php while ($karyawan = mysqli_fetch_assoc($result_karyawan)): ?>
                                    <option value="<?php echo $karyawan['id']; ?>"
                                            data-nik="<?php echo $karyawan['nik']; ?>"
                                            <?php echo (isset($_POST['karyawan_id']) && $_POST['karyawan_id'] == $karyawan['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($karyawan['nama']); ?> (<?php echo $karyawan['nik']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (isset($errors['karyawan_id'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['karyawan_id']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Tanggal Penilaian -->
                        <div class="mb-3">
                            <label for="tanggal_penilaian" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Tanggal Penilaian <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="tanggal_penilaian" name="tanggal_penilaian"
                                   value="<?php echo $_POST['tanggal_penilaian'] ?? date('Y-m-d'); ?>" required>
                            <?php if (isset($errors['tanggal_penilaian'])): ?>
                                <div class="text-danger mt-1"><?php echo $errors['tanggal_penilaian']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Kriteria Penilaian -->
                        <div class="row">
                            <?php foreach ($criteria as $key => $criterion): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <label for="<?php echo $key; ?>" class="form-label">
                                                <i class="fas fa-star me-1"></i><?php echo ucfirst($key); ?>
                                                <small class="text-muted">(<?php echo isset($criterion['unit']) ? $criterion['unit'] : 'point'; ?>)</small>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                                                   min="<?php echo $criterion['min']; ?>" max="<?php echo $criterion['max']; ?>"
                                                   step="0.01" value="<?php echo $_POST[$key] ?? ''; ?>" required>
                                            <small class="text-muted"><?php echo $criterion['description']; ?></small>
                                            <div class="mt-1">
                                                <small class="text-info">
                                                    Range: <?php echo $criterion['min']; ?> - <?php echo $criterion['max']; ?>
                                                    | Bobot: <?php echo ($criterion['weight'] * 100); ?>%
                                                    | Jenis: <span class="badge bg-<?php echo $criterion['type'] == 'benefit' ? 'success' : 'danger'; ?>">
                                                        <?php echo $criterion['type'] == 'benefit' ? 'Benefit' : 'Cost'; ?>
                                                    </span>
                                                </small>
                                            </div>
                                            <?php if (isset($errors[$key])): ?>
                                                <div class="text-danger mt-1"><?php echo $errors[$key]; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Error Messages -->
                        <?php if (isset($errors['duplicate'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errors['duplicate']; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($errors['database'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $errors['database']; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Info Box -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Setelah form disubmit, sistem akan otomatis menghitung nilai TOPSIS 
                            dan menampilkan proses perhitungan secara bertahap.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan & Hitung TOPSIS
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
document.getElementById('penilaianForm').addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = ['karyawan_id', 'tanggal_penilaian', 'kinerja', 'kedisiplinan', 'kerjasama', 'absensi'];

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        e.preventDefault();
        showToast('error', 'Harap lengkapi semua field yang wajib diisi!');
        return false;
    }

    // Additional validation for numeric fields
    const numericFields = ['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'];
    numericFields.forEach(field => {
        const element = document.getElementById(field);
        const value = parseFloat(element.value);
        const min = parseFloat(element.min);
        const max = parseFloat(element.max);

        if (value < min || value > max) {
            element.classList.add('is-invalid');
            isValid = false;
        }
    });

    if (!isValid) {
        e.preventDefault();
        showToast('error', 'Nilai kriteria tidak dalam range yang valid!');
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

// Auto-fill tanggal dengan bulan berjalan jika belum diisi
document.getElementById('tanggal_penilaian').addEventListener('focus', function() {
    if (!this.value) {
        this.value = new Date().toISOString().split('T')[0];
    }
});

// Update NIK display when karyawan selected
document.getElementById('karyawan_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const nik = selectedOption.getAttribute('data-nik');

    // You can add additional logic here if needed
    console.log('Selected karyawan NIK:', nik);
});
</script>

<?php include '../includes/footer.php'; ?>