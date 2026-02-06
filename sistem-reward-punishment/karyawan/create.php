<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan manager yang bisa akses
if (!isAdmin() && !isManager() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin, manager, dan HRD admin yang dapat menambah karyawan.';
    redirect('../dashboard.php');
}

$page_title = "Tambah Karyawan";

// Inisialisasi variabel
$errors = [];
$success = '';
$data = [
    'nik' => '',
    'nama' => '',
    'jenis_kelamin' => 'L',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'alamat' => '',
    'email' => '',
    'telepon' => '',
    'departemen_id' => '',
    'jabatan_id' => '',
    'tanggal_masuk' => date('Y-m-d'),
    'status' => 'aktif'
];

// Get data departemen dan jabatan untuk dropdown
$conn = connectDB();
$departemen_list = getMultipleRows("SELECT id, kode, nama FROM departemen ORDER BY nama ASC");
$jabatan_list = getMultipleRows("SELECT id, kode, nama FROM jabatan ORDER BY nama ASC");

// Generate NIK otomatis
if (empty($_POST)) {
    $data['nik'] = generateNIK('EMP');
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    foreach ($_POST as $key => $value) {
        $data[$key] = sanitize($value);
    }
    
    // Validasi
    if (empty($data['nik'])) {
        $errors['nik'] = 'NIK wajib diisi';
    } else {
        // Cek duplikasi NIK
        $check_nik = getSingleRow("SELECT id FROM karyawan WHERE nik = ?", [$data['nik']]);
        if ($check_nik) {
            $errors['nik'] = 'NIK sudah terdaftar';
        }
    }
    
    if (empty($data['nama'])) {
        $errors['nama'] = 'Nama wajib diisi';
    } elseif (strlen($data['nama']) < 3) {
        $errors['nama'] = 'Nama minimal 3 karakter';
    }
    
    if (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
        $errors['jenis_kelamin'] = 'Jenis kelamin tidak valid';
    }
    
    if (empty($data['tempat_lahir'])) {
        $errors['tempat_lahir'] = 'Tempat lahir wajib diisi';
    }
    
    if (empty($data['tanggal_lahir'])) {
        $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi';
    } elseif (strtotime($data['tanggal_lahir']) > strtotime('-17 years')) {
        $errors['tanggal_lahir'] = 'Minimal usia 17 tahun';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    } else {
        // Cek duplikasi email
        $check_email = getSingleRow("SELECT id FROM karyawan WHERE email = ?", [$data['email']]);
        if ($check_email) {
            $errors['email'] = 'Email sudah terdaftar';
        }
    }
    
    if (empty($data['telepon'])) {
        $errors['telepon'] = 'Telepon wajib diisi';
    }
    
    if (empty($data['departemen_id'])) {
        $errors['departemen_id'] = 'Departemen wajib dipilih';
    }
    
    if (empty($data['jabatan_id'])) {
        $errors['jabatan_id'] = 'Jabatan wajib dipilih';
    }
    
    if (empty($data['tanggal_masuk'])) {
        $errors['tanggal_masuk'] = 'Tanggal masuk wajib diisi';
    }
    
    // Handle foto upload
    $foto_filename = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['foto'], '../../uploads/karyawan/', ['jpg', 'jpeg', 'png'], 2097152);
        
        if ($upload_result['success']) {
            $foto_filename = $upload_result['filename'];
        } else {
            $errors['foto'] = $upload_result['message'];
        }
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $conn = connectDB();
        
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            $sql = "INSERT INTO karyawan (
                nik, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, 
                alamat, email, telepon, departemen_id, jabatan_id, 
                tanggal_masuk, status, foto, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($conn, $sql);
            
            $params = [
                $data['nik'],
                $data['nama'],
                $data['jenis_kelamin'],
                $data['tempat_lahir'],
                $data['tanggal_lahir'],
                $data['alamat'],
                $data['email'],
                $data['telepon'],
                $data['departemen_id'],
                $data['jabatan_id'],
                $data['tanggal_masuk'],
                $data['status'],
                $foto_filename
            ];
            
            mysqli_stmt_bind_param($stmt, 'ssssssssiisss', ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($conn);
                $karyawan_id = mysqli_insert_id($conn);
                
                // Log activity
                logActivity('create_karyawan', "Menambah karyawan baru: {$data['nama']} ({$data['nik']})");
                
                $_SESSION['success'] = "Karyawan {$data['nama']} berhasil ditambahkan!";
                redirect('../index.php');
            } else {
                throw new Exception('Gagal menyimpan data karyawan: ' . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors['database'] = $e->getMessage();
            
            // Hapus foto jika sudah terupload tapi gagal simpan
            if ($foto_filename && file_exists("../../uploads/karyawan/{$foto_filename}")) {
                unlink("../../uploads/karyawan/{$foto_filename}");
            }
        }
    }
}

$page_subtitle = "Tambah Data Karyawan Baru";
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Form Tambah Karyawan
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($errors['database'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="karyawanForm">
                    <!-- Informasi Pribadi -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user me-2"></i>Informasi Pribadi
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nik']) ? 'is-invalid' : ''; ?>" 
                                       name="nik" value="<?php echo htmlspecialchars($data['nik']); ?>" 
                                       placeholder="Masukkan NIK" required>
                                <?php if (isset($errors['nik'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['nik']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">NIK akan digenerate otomatis</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" 
                                       name="nama" value="<?php echo htmlspecialchars($data['nama']); ?>" 
                                       placeholder="Masukkan nama lengkap" required>
                                <?php if (isset($errors['nama'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['nama']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin" 
                                               id="jk_l" value="L" <?php echo $data['jenis_kelamin'] == 'L' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="jk_l">Laki-laki</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin" 
                                               id="jk_p" value="P" <?php echo $data['jenis_kelamin'] == 'P' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="jk_p">Perempuan</label>
                                    </div>
                                </div>
                                <?php if (isset($errors['jenis_kelamin'])): ?>
                                    <div class="text-danger small"><?php echo $errors['jenis_kelamin']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto</label>
                                <input type="file" class="form-control <?php echo isset($errors['foto']) ? 'is-invalid' : ''; ?>" 
                                       name="foto" accept="image/*" id="fotoInput">
                                <?php if (isset($errors['foto'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['foto']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Format: JPG, PNG (max 2MB)</small>
                                
                                <!-- Foto Preview -->
                                <div class="mt-2 text-center" id="fotoPreview">
                                    <img src="../../uploads/default-avatar.png" alt="Preview" 
                                         class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['tempat_lahir']) ? 'is-invalid' : ''; ?>" 
                                       name="tempat_lahir" value="<?php echo htmlspecialchars($data['tempat_lahir']); ?>" 
                                       placeholder="Masukkan tempat lahir" required>
                                <?php if (isset($errors['tempat_lahir'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['tempat_lahir']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['tanggal_lahir']) ? 'is-invalid' : ''; ?>" 
                                       name="tanggal_lahir" value="<?php echo $data['tanggal_lahir']; ?>" 
                                       max="<?php echo date('Y-m-d', strtotime('-17 years')); ?>" required>
                                <?php if (isset($errors['tanggal_lahir'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['tanggal_lahir']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Minimal usia 17 tahun</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control" name="alamat" rows="3" 
                                      placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Informasi Kontak -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-address-book me-2"></i>Informasi Kontak
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       name="email" value="<?php echo htmlspecialchars($data['email']); ?>" 
                                       placeholder="nama@contoh.com" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telepon/HP <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?php echo isset($errors['telepon']) ? 'is-invalid' : ''; ?>" 
                                       name="telepon" value="<?php echo htmlspecialchars($data['telepon']); ?>" 
                                       placeholder="0812xxxxxxx" required>
                                <?php if (isset($errors['telepon'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['telepon']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Pekerjaan -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-briefcase me-2"></i>Informasi Pekerjaan
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departemen <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['departemen_id']) ? 'is-invalid' : ''; ?>" 
                                        name="departemen_id" required>
                                    <option value="">Pilih Departemen</option>
                                    <?php foreach ($departemen_list as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo $data['departemen_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['nama']); ?> (<?php echo $dept['kode']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['departemen_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['departemen_id']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['jabatan_id']) ? 'is-invalid' : ''; ?>" 
                                        name="jabatan_id" required>
                                    <option value="">Pilih Jabatan</option>
                                    <?php foreach ($jabatan_list as $jab): ?>
                                        <option value="<?php echo $jab['id']; ?>" 
                                                <?php echo $data['jabatan_id'] == $jab['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($jab['nama']); ?> (<?php echo $jab['kode']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['jabatan_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['jabatan_id']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['tanggal_masuk']) ? 'is-invalid' : ''; ?>" 
                                       name="tanggal_masuk" value="<?php echo $data['tanggal_masuk']; ?>" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                                <?php if (isset($errors['tanggal_masuk'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['tanggal_masuk']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" required>
                                    <option value="aktif" <?php echo $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="non-aktif" <?php echo $data['status'] == 'non-aktif' ? 'selected' : ''; ?>>Non-Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="../index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Panduan Form -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Panduan Pengisian
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>NIK:</strong> Akan digenerate otomatis
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Nama:</strong> Minimal 3 karakter
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Tanggal Lahir:</strong> Minimal usia 17 tahun
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Email:</strong> Format email yang valid
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Foto:</strong> Format JPG/PNG, maksimal 2MB
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Statistik Karyawan -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Statistik Karyawan
                </h6>
            </div>
            <div class="card-body">
                <?php
                $stats = getSingleRow("SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                    SUM(CASE WHEN status = 'non-aktif' THEN 1 ELSE 0 END) as non_aktif,
                    COUNT(DISTINCT departemen_id) as total_departemen,
                    COUNT(DISTINCT jabatan_id) as total_jabatan
                    FROM karyawan");
                ?>
                <div class="text-center mb-3">
                    <div class="display-6 text-primary"><?php echo $stats['total']; ?></div>
                    <small class="text-muted">Total Karyawan</small>
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-success fw-bold"><?php echo $stats['aktif']; ?></div>
                        <small>Aktif</small>
                    </div>
                    <div class="col-6">
                        <div class="text-danger fw-bold"><?php echo $stats['non_aktif']; ?></div>
                        <small>Non-Aktif</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="small text-muted">
                    <div><i class="fas fa-building me-2"></i> Departemen: <?php echo $stats['total_departemen']; ?></div>
                    <div><i class="fas fa-user-tie me-2"></i> Jabatan: <?php echo $stats['total_jabatan']; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Preview foto sebelum upload
    $('#fotoInput').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#fotoPreview img').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Generate NIK otomatis jika kosong
    $('input[name="nik"]').on('blur', function() {
        if (!$(this).val().trim()) {
            $.ajax({
                url: 'generate_nik.php',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('input[name="nik"]').val(response.nik);
                    }
                }
            });
        }
    });
    
    // Hitung usia otomatis
    $('input[name="tanggal_lahir"]').on('change', function() {
        const birthDate = new Date($(this).val());
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age < 17) {
            alert('Minimal usia 17 tahun!');
            $(this).val('');
        }
    });
    
    // Form validation
    $('#karyawanForm').on('submit', function(e) {
        let valid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                valid = false;
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback">Field ini wajib diisi</div>');
            }
        });
        
        // Check email format
        const email = $('input[name="email"]').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            valid = false;
            $('input[name="email"]').addClass('is-invalid');
            $('input[name="email"]').after('<div class="invalid-feedback">Format email tidak valid</div>');
        }
        
        // Check file size if any
        const fotoInput = $('#fotoInput')[0];
        if (fotoInput.files.length > 0) {
            const fileSize = fotoInput.files[0].size;
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (fileSize > maxSize) {
                valid = false;
                $('#fotoInput').addClass('is-invalid');
                $('#fotoInput').after('<div class="invalid-feedback">Ukuran file maksimal 2MB</div>');
            }
        }
        
        if (!valid) {
            e.preventDefault();
            showToast('error', 'Harap perbaiki error terlebih dahulu!');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>