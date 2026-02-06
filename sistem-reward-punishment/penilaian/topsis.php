<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

$conn = connectDB();
$page_title = "Perhitungan TOPSIS";

// Ambil data penilaian untuk perhitungan TOPSIS
$sql = "SELECT p.*, k.nama as nama_karyawan, k.nik 
        FROM penilaian p 
        LEFT JOIN karyawan k ON p.karyawan_id = k.id 
        WHERE MONTH(p.tanggal_penilaian) = MONTH(CURDATE())
        ORDER BY p.tanggal_penilaian DESC";
$result = mysqli_query($conn, $sql);

$alternatives = [];
$criteria = ['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'];
$weights = TOPSIS_CRITERIA;

while ($row = mysqli_fetch_assoc($result)) {
    $alternatives[] = [
        'id' => $row['id'],
        'nama' => $row['nama_karyawan'],
        'nik' => $row['nik'],
        'kinerja' => $row['kinerja'],
        'kedisiplinan' => $row['kedisiplinan'],
        'kerjasama' => $row['kerjasama'],
        'absensi' => $row['absensi']
    ];
}

// Hitung TOPSIS
if (!empty($alternatives)) {
    // 1. Normalisasi matriks keputusan
    $normalized = [];
    $sum_squares = array_fill_keys($criteria, 0);
    
    foreach ($alternatives as $alt) {
        foreach ($criteria as $c) {
            $sum_squares[$c] += pow($alt[$c], 2);
        }
    }
    
    foreach ($alternatives as $alt) {
        $norm = [];
        foreach ($criteria as $c) {
            $norm[$c] = $alt[$c] / sqrt($sum_squares[$c]);
        }
        $normalized[] = $norm;
    }
    
    // 2. Bobot ternormalisasi
    $weighted = [];
    foreach ($normalized as $norm) {
        $weight = [];
        foreach ($criteria as $c) {
            $weight[$c] = $norm[$c] * $weights[$c]['weight'];
        }
        $weighted[] = $weight;
    }
    
    // 3. Solusi ideal positif dan negatif
    $ideal_positive = [];
    $ideal_negative = [];
    
    foreach ($criteria as $c) {
        $values = array_column($weighted, $c);
        
        if ($weights[$c]['type'] == 'benefit') {
            $ideal_positive[$c] = max($values);
            $ideal_negative[$c] = min($values);
        } else {
            $ideal_positive[$c] = min($values);
            $ideal_negative[$c] = max($values);
        }
    }
    
    // 4. Hitung jarak ke solusi ideal
    $topsis_results = [];
    
    foreach ($weighted as $index => $alt) {
        $d_plus = 0;
        $d_minus = 0;
        
        foreach ($criteria as $c) {
            $d_plus += pow($alt[$c] - $ideal_positive[$c], 2);
            $d_minus += pow($alt[$c] - $ideal_negative[$c], 2);
        }
        
        $d_plus = sqrt($d_plus);
        $d_minus = sqrt($d_minus);
        
        // 5. Hitung nilai preferensi
        $preference = $d_minus / ($d_plus + $d_minus);
        
        $topsis_results[] = [
            'id' => $alternatives[$index]['id'],
            'nama' => $alternatives[$index]['nama'],
            'nik' => $alternatives[$index]['nik'],
            'kinerja' => $alternatives[$index]['kinerja'],
            'kedisiplinan' => $alternatives[$index]['kedisiplinan'],
            'kerjasama' => $alternatives[$index]['kerjasama'],
            'absensi' => $alternatives[$index]['absensi'],
            'd_plus' => $d_plus,
            'd_minus' => $d_minus,
            'preference' => $preference,
            'status' => determineAction($preference),
            'level' => getLevel($preference, determineAction($preference))
        ];
    }
    
    // Urutkan berdasarkan nilai preferensi tertinggi
    usort($topsis_results, function($a, $b) {
        return $b['preference'] <=> $a['preference'];
    });
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator me-2"></i>Perhitungan TOPSIS
                    </h5>
                    <div>
                        <button class="btn btn-info me-2" onclick="printTOPSISTable()">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Kriteria -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Kriteria dan Bobot TOPSIS
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($weights as $key => $criterion): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h6 class="mb-2"><?php echo ucfirst($key); ?></h6>
                                        <div class="mb-2">
                                            <span class="badge bg-primary">Bobot: <?php echo $criterion['weight']; ?></span>
                                        </div>
                                        <small class="text-muted">
                                            Jenis: 
                                            <span class="badge bg-<?php echo $criterion['type'] == 'benefit' ? 'success' : 'danger'; ?>">
                                                <?php echo $criterion['type'] == 'benefit' ? 'Benefit' : 'Cost'; ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Perhitungan -->
    <?php if (!empty($alternatives)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Hasil Perhitungan TOPSIS
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="topsisTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th rowspan="2">#</th>
                                        <th rowspan="2">Nama Karyawan</th>
                                        <th rowspan="2">NIK</th>
                                        <th colspan="4" class="text-center">Nilai Kriteria</th>
                                        <th colspan="3" class="text-center">Hasil TOPSIS</th>
                                        <th rowspan="2">Status</th>
                                        <th rowspan="2">Level</th>
                                    </tr>
                                    <tr>
                                        <th>Kinerja</th>
                                        <th>Kedisiplinan</th>
                                        <th>Kerjasama</th>
                                        <th>Absensi</th>
                                        <th>D+</th>
                                        <th>D-</th>
                                        <th>Preferensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topsis_results as $index => $result): ?>
                                        <tr class="<?php echo $result['status'] == 'reward' ? 'table-success' : ($result['status'] == 'punishment' ? 'table-danger' : ''); ?>">
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($result['nama']); ?></strong>
                                            </td>
                                            <td><?php echo $result['nik']; ?></td>
                                            <td class="text-center"><?php echo $result['kinerja']; ?></td>
                                            <td class="text-center"><?php echo $result['kedisiplinan']; ?></td>
                                            <td class="text-center"><?php echo $result['kerjasama']; ?></td>
                                            <td class="text-center"><?php echo $result['absensi']; ?></td>
                                            <td class="text-center"><?php echo number_format($result['d_plus'], 4); ?></td>
                                            <td class="text-center"><?php echo number_format($result['d_minus'], 4); ?></td>
                                            <td class="text-center">
                                                <strong><?php echo number_format($result['preference'], 4); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($result['status'] == 'reward'): ?>
                                                    <span class="badge bg-success">REWARD</span>
                                                <?php elseif ($result['status'] == 'punishment'): ?>
                                                    <span class="badge bg-danger">PUNISHMENT</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">NORMAL</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <small class="badge bg-info">
                                                    <?php echo strtoupper(str_replace('_', ' ', $result['level'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <td colspan="9" class="text-end"><strong>Solusi Ideal:</strong></td>
                                        <td colspan="2">
                                            <div class="d-flex justify-content-between">
                                                <span>A+: <?php echo json_encode($ideal_positive); ?></span>
                                                <span>A-: <?php echo json_encode($ideal_negative); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visualisasi -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Visualisasi Nilai Preferensi
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="topsisChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simpan Hasil -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-save me-2"></i>Simpan Hasil Perhitungan
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="save_topsis.php" method="POST" id="saveTopsisForm">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Simpan hasil perhitungan TOPSIS untuk digunakan dalam pemberian reward dan punishment.
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nama Karyawan</th>
                                            <th>Nilai TOPSIS</th>
                                            <th>Status</th>
                                            <th>Level</th>
                                            <th>Simpan ke Database?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topsis_results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['nama']); ?></td>
                                                <td><?php echo number_format($result['preference'], 4); ?></td>
                                                <td><?php echo ucfirst($result['status']); ?></td>
                                                <td><?php echo strtoupper(str_replace('_', ' ', $result['level'])); ?></td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="save_result[]" 
                                                               value="<?php echo $result['id']; ?>"
                                                               id="save_<?php echo $result['id']; ?>"
                                                               checked>
                                                        <label class="form-check-label" for="save_<?php echo $result['id']; ?>"></label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Simpan Semua Hasil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calculator fa-3x text-muted mb-3"></i>
                        <h4>Data penilaian bulan ini tidak ditemukan</h4>
                        <p class="text-muted">Silakan tambah data penilaian terlebih dahulu.</p>
                        <a href="create.php" class="btn btn-primary mt-2">
                            <i class="fas fa-plus me-2"></i>Tambah Penilaian
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Chart untuk visualisasi nilai preferensi
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($topsis_results)): ?>
        var labels = <?php echo json_encode(array_column($topsis_results, 'nama')); ?>;
        var data = <?php echo json_encode(array_column($topsis_results, 'preference')); ?>;
        var statuses = <?php echo json_encode(array_column($topsis_results, 'status')); ?>;
        
        var ctx = document.getElementById('topsisChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nilai Preferensi',
                    data: data,
                    backgroundColor: data.map(function(value, index) {
                        return statuses[index] == 'reward' ? 'rgba(39, 174, 96, 0.8)' : 
                               statuses[index] == 'punishment' ? 'rgba(231, 76, 60, 0.8)' : 
                               'rgba(243, 156, 18, 0.8)';
                    }),
                    borderColor: data.map(function(value, index) {
                        return statuses[index] == 'reward' ? 'rgba(39, 174, 96, 1)' : 
                               statuses[index] == 'punishment' ? 'rgba(231, 76, 60, 1)' : 
                               'rgba(243, 156, 18, 1)';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1,
                        title: {
                            display: true,
                            text: 'Nilai Preferensi'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Nama Karyawan'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var status = statuses[context.dataIndex];
                                var level = '<?php echo json_encode(array_column($topsis_results, "level")); ?>';
                                level = JSON.parse(level)[context.dataIndex];
                                return [
                                    'Nilai: ' + context.parsed.y.toFixed(4),
                                    'Status: ' + status.charAt(0).toUpperCase() + status.slice(1),
                                    'Level: ' + level.replace('_', ' ').toUpperCase()
                                ];
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>
});

// Fungsi untuk print tabel TOPSIS
function printTOPSISTable() {
    var printContents = document.getElementById('topsisTable').outerHTML;
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Laporan Perhitungan TOPSIS</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background-color: #f2f2f2; }
                .table-success { background-color: #d4edda; }
                .table-danger { background-color: #f8d7da; }
                @media print {
                    @page { size: landscape; }
                }
            </style>
        </head>
        <body>
            <h2>Laporan Perhitungan TOPSIS</h2>
            <p>Tanggal: ${new Date().toLocaleDateString('id-ID')}</p>
            ${printContents}
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.location.reload();
                    }
                }
            <\/script>
        </body>
        </html> m
    `;
}

// Validasi form sebelum submit
document.getElementById('saveTopsisForm').addEventListener('submit', function(e) {
    var checkboxes = this.querySelectorAll('input[name="save_result[]"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        showToast('warning', 'Pilih minimal satu hasil untuk disimpan', 'Peringatan');
    }
});
</script>

<?php include '../includes/footer.php'; ?>