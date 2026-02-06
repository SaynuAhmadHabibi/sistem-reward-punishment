<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan manager yang bisa edit
if (!isAdmin() && !isManager() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Hanya admin, manager, dan HRD admin yang dapat mengedit karyawan. Hanya admin dan manager yang dapat mengedit karyawan.';
    redirect('../dashboard.php');
}

$page_title = "Edit Karyawan";

// Cek apakah ID tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID karyawan tidak valid!';
    redirect('../index.php');
}

$id = intval($_GET['id']);

// Get data karyawan
$karyawan = getSingleRow("SELECT * FROM karyawan WHERE id = ?", [$id]);

if (!$karyawan) {
    $_SESSION['error'] = 'Data karyawan tidak ditemukan!';
    redirect('../index.php');
}

// Get data departemen dan jabatan untuk dropdown
$departemen_list = getMultipleRows("SELECT id, kode, nama FROM departemen ORDER BY nama ASC");
$jabatan_list = getMultipleRows("SELECT id, kode, nama FROM jabatan ORDER BY nama ASC");

// Inisialisasi variabel
$errors = [];
$data = $karyawan;

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
        // Cek duplikasi NIK (kecuali diri sendiri)
        $check_nik = getSingleRow("SELECT id FROM karyawan WHERE nik = ? AND id != ?", [$data['nik'], $id]);
        if ($check_nik) {
            $errors['nik'] = 'NIK sudah terdaftar oleh karyawan lain';
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
        // Cek duplikasi email (kecuali diri sendiri)
        $check_email = getSingleRow("SELECT id FROM karyawan WHERE email = ? AND id != ?", [$data['email'], $id]);
        if ($check_email) {
            $errors['email'] = 'Email sudah terdaftar oleh karyawan lain';
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
    $foto_filename = $karyawan['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['foto'], '../../uploads/karyawan/', ['jpg', 'jpeg', 'png'], 2097152);
        
        if ($upload_result['success']) {
            // Hapus foto lama jika ada
            if ($foto_filename && file_exists("../../uploads/karyawan/{$foto_filename}")) {
                unlink("../../uploads/karyawan/{$foto_filename}");
            }
            $foto_filename = $upload_result['filename'];
        } else {
            $errors['foto'] = $upload_result['message'];
        }
    }
    
    // Handle hapus foto
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1') {
        if ($foto_filename && file_exists("../../uploads/karyawan/{$foto_filename}")) {
            unlink("../../uploads/karyawan/{$foto_filename}");
        }
        $foto_filename = '';
    }
    
    // Jika tidak ada error, update ke database
    if (empty($errors)) {
        $conn = connectDB();
        
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            $sql = "UPDATE karyawan SET
                nik = ?, nama = ?, jenis_kelamin = ?, tempat_lahir = ?, tanggal_lahir = ?,
                alamat = ?, email = ?, telepon = ?, departemen_id = ?, jabatan_id = ?,
                tanggal_masuk = ?, status = ?, foto = ?, updated_at = NOW()
                WHERE id = ?";
            
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
                $foto_filename,
                $id
            ];
            
            mysqli_stmt_bind_param($stmt, 'ssssssssiisssi', ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($conn);
                
                // Log activity
                logActivity('update_karyawan', "Mengupdate karyawan: {$data['nama']} ({$data['nik']})");
                
                $_SESSION['success'] = "Data karyawan {$data['nama']} berhasil diperbarui!";
                redirect('view.php?id=' . $id);
            } else {
                throw new Exception('Gagal update data karyawan: ' . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors['database'] = $e->getMessage();
            
            // Hapus foto baru jika sudah terupload tapi gagal update
            if ($foto_filename != $karyawan['foto'] && $foto_filename && file_exists("../../uploads/karyawan/{$foto_filename}")) {
                unlink("../../uploads/karyawan/{$foto_filename}");
            }
        }
    }
}

$page_subtitle = "Edit Data Karyawan";
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>Form Edit Karyawan
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
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($data['foto'])): ?>
                                        <div class="me-3">
                                            <img src="../../uploads/karyawan/<?php echo $data['foto']; ?>" 
                                                 alt="Foto Karyawan" class="img-thumbnail" style="width: 80px; height: 80px;">
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFoto">
                                            <label class="form-check-label text-danger" for="hapusFoto">
                                                Hapus Foto
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" class="form-control mt-2 <?php echo isset($errors['foto']) ? 'is-invalid' : ''; ?>" 
                                       name="foto" accept="image/*" id="fotoInput">
                                <?php if (isset($errors['foto'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['foto']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Format: JPG, PNG (max 2MB)</small>
                                
                                <!-- Foto Preview -->
                                <div class="mt-2 text-center" id="fotoPreview">
                                    <?php if (empty($data['foto'])): ?>
                                        <img src="../../uploads/default-avatar.png" alt="Preview" 
                                             class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                    <?php endif; ?>
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
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Info Karyawan -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Info Karyawan
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <?php if (!empty($karyawan['foto'])): ?>
                        <img src="../../uploads/karyawan/<?php echo $karyawan['foto']; ?>" 
                             alt="Foto Karyawan" class="img-thumbnail rounded-circle mb-3" style="width: 120px; height: 120px;">
                    <?php else: ?>
                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" 
                             style="width: 120px; height: 120px;">
                            <i class="fas fa-user text-white" style="font-size: 48px;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?php echo htmlspecialchars($karyawan['nama']); ?></h5>
                    <p class="text-muted mb-1"><?php echo $karyawan['nik']; ?></p>
                    <span class="badge bg-<?php echo $karyawan['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                        <?php echo $karyawan['status'] == 'aktif' ? 'Aktif' : 'Non-Aktif'; ?>
                    </span>
                </div>
                
                <hr>
                
                <div class="small">
                    <div class="mb-2">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <strong>Bergabung:</strong> <?php echo formatDate($karyawan['tanggal_masuk']); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-history me-2"></i>
                        <strong>Terakhir Update:</strong> <?php echo formatDate($karyawan['updated_at']); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-user-clock me-2"></i>
                        <strong>Usia:</strong> <?php echo calculateAge($karyawan['tanggal_lahir']); ?> tahun
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Data -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Status Data
                </h6>
            </div>
            <div class="card-body">
                <?php
                // Get data penilaian karyawan
                $penilaian_count = getSingleRow("SELECT COUNT(*) as count FROM penilaian WHERE karyawan_id = ?", [$id])['count'];
                $reward_count = getSingleRow("SELECT COUNT(*) as count FROM reward WHERE karyawan_id = ?", [$id])['count'];
                $punishment_count = getSingleRow("SELECT COUNT(*) as count FROM punishment WHERE karyawan_id = ?", [$id])['count'];
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Data Penilaian</span>
                        <span class="badge bg-primary"><?php echo $penilaian_count; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo min($penilaian_count * 20, 100); ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Reward Diterima</span>
                        <span class="badge bg-success"><?php echo $reward_count; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?php echo min($reward_count * 20, 100); ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Punishment Diberikan</span>
                        <span class="badge bg-danger"><?php echo $punishment_count; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?php echo min($punishment_count * 20, 100); ?>%"></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="alert alert-info small">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Perubahan data karyawan akan mempengaruhi data penilaian dan laporan terkait.
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
                $('#fotoPreview').html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">');
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Toggle hapus foto
    $('#hapusFoto').on('change', function() {
        if ($(this).is(':checked')) {
            $('#fotoInput').prop('disabled', true);
        } else {
            $('#fotoInput').prop('disabled', false);
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
        if (fotoInput.files.length > 0 && !$('#fotoInput').prop('disabled')) {
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