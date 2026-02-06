<?php
/**
 * Halaman Hasil TOPSIS Step-by-Step
 * 
 * File ini menampilkan semua tahap perhitungan TOPSIS dengan detail
 * Membantu user memahami bagaimana hasil final diperoleh
 */

require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';
require_once '../config/topsis.php';

// Cek login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan HRD admin
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak!';
    redirect('../dashboard.php');
}

$page_title = "Hasil Perhitungan TOPSIS";

// Validasi ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID penilaian tidak valid!';
    redirect('index.php');
}

$penilaian_id = intval($_GET['id']);

// Ambil data dari session atau database
if (isset($_SESSION['topsis_results']) && $_SESSION['topsis_results']['penilaian_id'] == $penilaian_id) {
    $results = $_SESSION['topsis_results'];
    unset($_SESSION['topsis_results']); // Hapus session setelah diambil
} else {
    // Ambil dari database jika tidak ada di session
    $conn = connectDB();
    $sql = "SELECT p.*, k.nama as karyawan_nama, k.nik,
                   u.nama as penilai_nama
            FROM penilaian p
            LEFT JOIN karyawan k ON p.karyawan_id = k.id
            LEFT JOIN users u ON p.penilai_id = u.id
            WHERE p.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $penilaian_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penilaian = mysqli_fetch_assoc($result);
    
    if (!$penilaian) {
        $_SESSION['error'] = 'Data penilaian tidak ditemukan!';
        redirect('index.php');
    }
    
    // Jika TOPSIS belum dihitung, arahkan ke calculate
    if ($penilaian['topsis_status'] != 'calculated') {
        redirect('calculate_topsis.php?id=' . $penilaian_id);
    }
    
    // Siapkan results dari database
    $results = [
        'penilaian' => $penilaian,
        'step5' => [
            'preference' => $penilaian['topsis_preference'],
            'd_plus' => $penilaian['topis_d_plus'],
            'd_minus' => $penilaian['topsis_d_minus'],
            'category' => $penilaian['topsis_category'],
            'level' => $penilaian['topsis_level']
        ]
    ];
}

$penilaian = $results['penilaian'];
$criteria = TOPSIS_CRITERIA;

?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator me-2"></i>Hasil Perhitungan TOPSIS
                    </h5>
                    <div>
                        <button class="btn btn-info me-2" onclick="printResult()">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                        <a href="view.php?id=<?php echo $penilaian_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Penilaian -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Data Karyawan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="130"><strong>Nama:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['karyawan_nama'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>NIK:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['nik'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Jabatan:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['jabatan'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Departemen:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['departemen'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="130"><strong>Tanggal Penilaian:</strong></td>
                                    <td><?php echo formatDate($penilaian['tanggal_penilaian']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Penilai:</strong></td>
                                    <td><?php echo htmlspecialchars($penilaian['penilai_nama'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Dihitung pada:</strong></td>
                                    <td><?php echo (isset($penilaian['calculated_at']) && $penilaian['calculated_at']) ? formatDate($penilaian['calculated_at']) : 'Baru saja'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hasil Akhir -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-3">NILAI PREFERENSI AKHIR</h6>
                    <div class="display-4 font-weight-bold mb-3" 
                         style="color: <?php 
                            $pref = $results['step5']['preference'];
                            if ($pref >= 0.7) echo '#28a745';
                            elseif ($pref < 0.3) echo '#dc3545';
                            else echo '#ffc107';
                         ?>">
                        <?php echo number_format($results['step5']['preference'], 4); ?>
                    </div>
                    
                    <?php 
                    $category = $results['step5']['category'];
                    $level = $results['step5']['level'];
                    $badge_class = ($category == 'reward') ? 'success' : (($category == 'punishment') ? 'danger' : 'warning');
                    ?>
                    
                    <div class="mb-3">
                        <span class="badge bg-<?php echo $badge_class; ?> px-3 py-2 fs-6">
                            <?php echo strtoupper($category); ?>
                        </span>
                    </div>
                    
                    <p class="text-muted small mb-0">
                        Level: <strong><?php echo str_replace('_', ' ', strtoupper($level)); ?></strong>
                    </p>
                </div>
            </div>

            <!-- Nilai Input -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Nilai Input</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Kinerja</strong></td>
                            <td class="text-end"><strong><?php echo $penilaian['kinerja']; ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Kedisiplinan</strong></td>
                            <td class="text-end"><strong><?php echo $penilaian['kedisiplinan']; ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Kerjasama</strong></td>
                            <td class="text-end"><strong><?php echo $penilaian['kerjasama']; ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Absensi</strong></td>
                            <td class="text-end"><strong><?php echo $penilaian['absensi']; ?> hari</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Langkah-Langkah Perhitungan TOPSIS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-check me-2"></i>Langkah-Langkah Perhitungan TOPSIS
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills mb-4 flex-wrap" id="stepTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="step1-tab" data-bs-toggle="tab" 
                                    data-bs-target="#step1" type="button" role="tab">
                                <i class="fas fa-layer-group me-2"></i>Step 1: Normalisasi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step2-tab" data-bs-toggle="tab" 
                                    data-bs-target="#step2" type="button" role="tab">
                                <i class="fas fa-weight me-2"></i>Step 2: Bobot
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step3-tab" data-bs-toggle="tab" 
                                    data-bs-target="#step3" type="button" role="tab">
                                <i class="fas fa-arrows-alt me-2"></i>Step 3: Solusi Ideal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step4-tab" data-bs-toggle="tab" 
                                    data-bs-target="#step4" type="button" role="tab">
                                <i class="fas fa-ruler me-2"></i>Step 4: Jarak Ideal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step5-tab" data-bs-toggle="tab" 
                                    data-bs-target="#step5" type="button" role="tab">
                                <i class="fas fa-certificate me-2"></i>Step 5: Preferensi
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="stepTabContent">
                        <!-- Step 1: Normalisasi -->
                        <div class="tab-pane fade show active" id="step1" role="tabpanel">
                            <h6 class="mb-3"><strong>STEP 1: Normalisasi Matriks Keputusan</strong></h6>
                            
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Normalisasi dilakukan dengan membagi setiap nilai dengan akar kuadrat dari jumlah kuadrat kolom (Vector Normalization).
                                <br>Rumus: X'<sub>ij</sub> = X<sub>ij</sub> / √(Σ X<sub>ij</sub>²)
                            </p>

                            <?php if (isset($results['step1'])): ?>
                                <!-- Nilai Input -->
                                <div class="mb-4">
                                    <h6><i class="fas fa-database me-2"></i>Nilai Input Asli</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo $results['step1']['original'][$c]; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Sum of Squares -->
                                <div class="mb-4">
                                    <h6><i class="fas fa-square me-2"></i>Perhitungan √Σ(X<sub>ij</sub>²)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Σ X²</th>
                                                    <th>√ Σ X²</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo number_format($results['step1']['sum_squares'][$c], 4); ?></td>
                                                        <td><?php echo number_format(sqrt($results['step1']['sum_squares'][$c]), 4); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Hasil Normalisasi -->
                                <div class="mb-4">
                                    <h6><i class="fas fa-check-circle me-2"></i>Hasil Matriks Ternormalisasi</h6>
                                    <div class="alert alert-info">
                                        <strong>Rumus:</strong> Nilai Input / √ Σ X²
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Nilai Input</th>
                                                    <th>÷</th>
                                                    <th>√ Σ X²</th>
                                                    <th>=</th>
                                                    <th>Nilai Normalisasi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo $results['step1']['original'][$c]; ?></td>
                                                        <td>÷</td>
                                                        <td><?php echo number_format(sqrt($results['step1']['sum_squares'][$c]), 4); ?></td>
                                                        <td>=</td>
                                                        <td><strong><?php echo number_format($results['step1']['normalized'][$c], 4); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">Step 1 data tidak tersedia</div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 2: Bobot -->
                        <div class="tab-pane fade" id="step2" role="tabpanel">
                            <h6 class="mb-3"><strong>STEP 2: Perhitungan Bobot Ternormalisasi</strong></h6>
                            
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Kalikan matriks ternormalisasi dengan bobot kriteria masing-masing.
                                <br>Rumus: Y<sub>ij</sub> = W<sub>j</sub> × X'<sub>ij</sub>
                            </p>

                            <?php if (isset($results['step2'])): ?>
                                <div class="mb-4">
                                    <h6><i class="fas fa-info-circle me-2"></i>Bobot Kriteria</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Bobot</th>
                                                    <th>Persentase</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo $results['step2']['weights'][$c]; ?></td>
                                                        <td><?php echo ($results['step2']['weights'][$c] * 100) . '%'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6><i class="fas fa-calculator me-2"></i>Perhitungan Bobot Ternormalisasi</h6>
                                    <div class="alert alert-info">
                                        <strong>Rumus:</strong> Nilai Normalisasi × Bobot
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Nilai Normalisasi</th>
                                                    <th>×</th>
                                                    <th>Bobot</th>
                                                    <th>=</th>
                                                    <th>Bobot Ternormalisasi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo number_format($results['step2']['normalized'][$c], 4); ?></td>
                                                        <td>×</td>
                                                        <td><?php echo $results['step2']['weights'][$c]; ?></td>
                                                        <td>=</td>
                                                        <td><strong><?php echo number_format($results['step2']['weighted'][$c], 4); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">Step 2 data tidak tersedia</div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 3: Solusi Ideal -->
                        <div class="tab-pane fade" id="step3" role="tabpanel">
                            <h6 class="mb-3"><strong>STEP 3: Menentukan Solusi Ideal Positif (A+) dan Negatif (A-)</strong></h6>
                            
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Untuk kriteria benefit: A+ = max, A- = min
                                <br>Untuk kriteria cost: A+ = min, A- = max
                            </p>

                            <?php if (isset($results['step3'])): ?>
                                <div class="mb-4">
                                    <h6><i class="fas fa-arrow-up me-2"></i>Solusi Ideal Positif (A+)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Tipe</th>
                                                    <th>Nilai A+</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($results['step3']['criteria_types'][$c] == 'benefit') ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($results['step3']['criteria_types'][$c]); ?>
                                                            </span>
                                                        </td>
                                                        <td><strong><?php echo number_format($results['step3']['ideal_positive'][$c], 4); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6><i class="fas fa-arrow-down me-2"></i>Solusi Ideal Negatif (A-)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Tipe</th>
                                                    <th>Nilai A-</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($results['step3']['criteria_types'][$c] == 'benefit') ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($results['step3']['criteria_types'][$c]); ?>
                                                            </span>
                                                        </td>
                                                        <td><strong><?php echo number_format($results['step3']['ideal_negative'][$c], 4); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">Step 3 data tidak tersedia</div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 4: Jarak Ideal -->
                        <div class="tab-pane fade" id="step4" role="tabpanel">
                            <h6 class="mb-3"><strong>STEP 4: Menghitung Jarak ke Solusi Ideal</strong></h6>
                            
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Hitung jarak Euclidean dari alternatif ke solusi ideal positif (D+) dan negatif (D-).
                                <br>Rumus: D = √[Σ(Y<sub>ij</sub> - A<sub>j</sub>)²]
                            </p>

                            <?php if (isset($results['step4'])): ?>
                                <div class="mb-4">
                                    <h6><i class="fas fa-arrow-up me-2"></i>Perhitungan Jarak ke Ideal Positif (D+)</h6>
                                    <div class="alert alert-info">
                                        <strong>Rumus:</strong> D+ = √[Σ(Bobot Ternormalisasi - A+)²]
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Bobot Ternormalisasi</th>
                                                    <th>A+</th>
                                                    <th>Selisih</th>
                                                    <th>Selisih²</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $sum_d_plus = 0;
                                                foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): 
                                                    $diff = $results['step4']['weighted'][$c] - $results['step4']['ideal_positive'][$c];
                                                    $sum_d_plus += pow($diff, 2);
                                                ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo number_format($results['step4']['weighted'][$c], 4); ?></td>
                                                        <td><?php echo number_format($results['step4']['ideal_positive'][$c], 4); ?></td>
                                                        <td><?php echo number_format($diff, 4); ?></td>
                                                        <td><?php echo number_format(pow($diff, 2), 4); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-light">
                                                    <td colspan="4"><strong>∑ Selisih² =</strong></td>
                                                    <td><strong><?php echo number_format($sum_d_plus, 4); ?></strong></td>
                                                </tr>
                                                <tr class="bg-light">
                                                    <td colspan="4"><strong>D+ = √ ∑ Selisih² =</strong></td>
                                                    <td><strong><?php echo number_format($results['step4']['d_plus'], 4); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6><i class="fas fa-arrow-down me-2"></i>Perhitungan Jarak ke Ideal Negatif (D-)</h6>
                                    <div class="alert alert-info">
                                        <strong>Rumus:</strong> D- = √[Σ(Bobot Ternormalisasi - A-)²]
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Bobot Ternormalisasi</th>
                                                    <th>A-</th>
                                                    <th>Selisih</th>
                                                    <th>Selisih²</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $sum_d_minus = 0;
                                                foreach (['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'] as $c): 
                                                    $diff = $results['step4']['weighted'][$c] - $results['step4']['ideal_negative'][$c];
                                                    $sum_d_minus += pow($diff, 2);
                                                ?>
                                                    <tr>
                                                        <td><strong><?php echo ucfirst($c); ?></strong></td>
                                                        <td><?php echo number_format($results['step4']['weighted'][$c], 4); ?></td>
                                                        <td><?php echo number_format($results['step4']['ideal_negative'][$c], 4); ?></td>
                                                        <td><?php echo number_format($diff, 4); ?></td>
                                                        <td><?php echo number_format(pow($diff, 2), 4); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-light">
                                                    <td colspan="4"><strong>∑ Selisih² =</strong></td>
                                                    <td><strong><?php echo number_format($sum_d_minus, 4); ?></strong></td>
                                                </tr>
                                                <tr class="bg-light">
                                                    <td colspan="4"><strong>D- = √ ∑ Selisih² =</strong></td>
                                                    <td><strong><?php echo number_format($results['step4']['d_minus'], 4); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">Step 4 data tidak tersedia</div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 5: Preferensi -->
                        <div class="tab-pane fade" id="step5" role="tabpanel">
                            <h6 class="mb-3"><strong>STEP 5: Menghitung Nilai Preferensi dan Ranking</strong></h6>
                            
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Nilai preferensi menunjukkan seberapa dekat alternatif dengan solusi ideal.
                                <br>Rumus: V<sub>i</sub> = D<sub>i</sub>- / (D<sub>i</sub>+ + D<sub>i</sub>-)
                                <br>Range: 0 sampai 1 (semakin tinggi semakin baik)
                            </p>

                            <div class="mb-4">
                                <h6><i class="fas fa-calculator me-2"></i>Perhitungan Nilai Preferensi</h6>
                                <div class="alert alert-info">
                                    <strong>Rumus:</strong> V = D- / (D+ + D-)
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Komponen</th>
                                                <th>Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>D+ (Jarak ke Ideal Positif)</strong></td>
                                                <td><?php echo number_format($results['step5']['d_plus'], 6); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>D- (Jarak ke Ideal Negatif)</strong></td>
                                                <td><?php echo number_format($results['step5']['d_minus'], 6); ?></td>
                                            </tr>
                                            <tr class="table-light">
                                                <td><strong>D+ + D- =</strong></td>
                                                <td><?php echo number_format($results['step5']['d_plus'] + $results['step5']['d_minus'], 6); ?></td>
                                            </tr>
                                            <tr class="bg-success text-white">
                                                <td><strong>NILAI PREFERENSI (V) = D- / (D+ + D-) =</strong></td>
                                                <td><strong><?php echo number_format($results['step5']['preference'], 6); ?></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6><i class="fas fa-tag me-2"></i>Kategori Berdasarkan Nilai Preferensi</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <h6 class="text-success">REWARD</h6>
                                                <p class="mb-0">Nilai ≥ 0.7</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-warning">
                                            <div class="card-body text-center">
                                                <h6 class="text-warning">NORMAL</h6>
                                                <p class="mb-0">0.3 ≤ Nilai < 0.7</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-danger">
                                            <div class="card-body text-center">
                                                <h6 class="text-danger">PUNISHMENT</h6>
                                                <p class="mb-0">Nilai < 0.3</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6><i class="fas fa-check-circle me-2"></i>Hasil Akhir</h6>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <table class="table table-borderless mb-0">
                                            <tr>
                                                <td width="200"><strong>Nilai Preferensi:</strong></td>
                                                <td><?php echo number_format($results['step5']['preference'], 4); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Kategori:</strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($results['step5']['category'] == 'reward') ? 'success' : (($results['step5']['category'] == 'punishment') ? 'danger' : 'warning'); ?> px-3 py-2">
                                                        <?php echo strtoupper($results['step5']['category']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Level:</strong></td>
                                                <td><?php echo strtoupper(str_replace('_', ' ', $results['step5']['level'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekomendasi -->
    <div class="row mb-4">
        <div class="col-12">
            <?php 
            $category = $results['step5']['category'];
            $level = $results['step5']['level'];
            
            if ($category == 'reward'):
                $alert_class = 'alert-success';
                $icon = 'fas fa-trophy';
                $title = 'Rekomendasi Reward';
            elseif ($category == 'punishment'):
                $alert_class = 'alert-danger';
                $icon = 'fas fa-exclamation-triangle';
                $title = 'Rekomendasi Punishment';
            else:
                $alert_class = 'alert-warning';
                $icon = 'fas fa-info-circle';
                $title = 'Status Normal';
            endif;
            ?>
            
            <div class="alert <?php echo $alert_class; ?>" role="alert">
                <h5 class="alert-heading">
                    <i class="<?php echo $icon; ?> me-2"></i><?php echo $title; ?>
                </h5>
                
                <?php if ($category == 'reward'): ?>
                    <p>Karyawan ini menunjukkan performa yang sangat baik dan berhak mendapatkan reward.</p>
                    <hr>
                    <p><strong>Level Reward:</strong> <?php echo strtoupper(str_replace('_', ' ', $level)); ?></p>
                    <p><strong>Tindakan yang Direkomendasikan:</strong></p>
                    <ul class="mb-0">
                        <li>Berikan apresiasi dan pengakuan atas performa terbaik</li>
                        <li>Pertimbangkan bonus atau insentif tambahan</li>
                        <li>Kirim ke program pengembangan karir</li>
                        <li>Pertahankan motivasi dan komitmen karyawan</li>
                    </ul>
                <?php elseif ($category == 'punishment'): ?>
                    <p>Karyawan ini menunjukkan performa yang kurang memuaskan dan memerlukan perbaikan.</p>
                    <hr>
                    <p><strong>Level Punishment:</strong> <?php echo strtoupper(str_replace('_', ' ', $level)); ?></p>
                    <p><strong>Tindakan yang Direkomendasikan:</strong></p>
                    <ul class="mb-0">
                        <li>Adakan sesi pembinaan dan coaching</li>
                        <li>Berikan peringatan sesuai dengan tingkat pelanggaran</li>
                        <li>Buat rencana perbaikan (performance improvement plan)</li>
                        <li>Monitor progress secara berkala</li>
                    </ul>
                <?php else: ?>
                    <p>Karyawan berada dalam kategori normal dengan performa yang sesuai standar.</p>
                    <hr>
                    <p><strong>Rekomendasi:</strong></p>
                    <ul class="mb-0">
                        <li>Pertahankan performa saat ini</li>
                        <li>Berikan penghargaan rutin (bonus normal)</li>
                        <li>Tawarkan program pengembangan untuk meningkatkan performa</li>
                        <li>Pantau perkembangan secara berkala</li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-list me-2"></i>Kembali ke Daftar
                    </a>
                </div>
                <div>
                    <a href="view.php?id=<?php echo $penilaian_id; ?>" class="btn btn-info me-2">
                        <i class="fas fa-eye me-2"></i>Lihat Detail
                    </a>
                    <button class="btn btn-success" onclick="printResult()">
                        <i class="fas fa-print me-2"></i>Cetak Hasil
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi print hasil TOPSIS
function printResult() {
    const printContent = document.querySelector('.container-fluid').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Hasil Perhitungan TOPSIS</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .no-print { display: none; }
                @media print {
                    @page { size: A4; margin: 1cm; }
                    body { margin: 0; padding: 0; }
                }
            </style>
        </head>
        <body>
            ${printContent}
            <script>
                window.addEventListener('load', function() {
                    window.print();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                });
            <\/script>
        </body>
        </html>
    `;
}
</script>

<?php include '../includes/footer.php'; ?>