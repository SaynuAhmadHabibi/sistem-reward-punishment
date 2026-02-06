<?php
/**
 * Calculate TOPSIS Step-by-Step
 * 
 * File ini menangani proses perhitungan TOPSIS secara bertahap
 * Setelah form penilaian disubmit, data dihitung dan disimpan ke database
 * Kemudian user diredirect ke halaman hasil untuk melihat setiap step perhitungan
 * 
 * VERSI SAFE: Menggunakan explicit column selection tanpa asumsi kolom
 */

require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';
require_once '../config/topsis.php';

// Cek login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan HRD admin yang bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak!';
    redirect('../dashboard.php');
}

$conn = connectDB();
$page_title = "Hasil Perhitungan TOPSIS";

// Validasi parameter ID penilaian
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID penilaian tidak valid!';
    redirect('index.php');
}

$penilaian_id = intval($_GET['id']);

// =============================================
// QUERY YANG AMAN - EXPLICIT COLUMN SELECTION
// Ambil data penilaian yang baru dibuat
// =============================================

$sql_penilaian = "SELECT
                    p.id,
                    p.karyawan_id,
                    p.penilai_id,
                    p.tanggal_penilaian,
                    p.kinerja,
                    p.kedisiplinan,
                    p.kerjasama,
                    p.absensi,
                    p.topsis_status,
                    p.topsis_d_plus,
                    p.topsis_d_minus,
                    p.topsis_preference,
                    p.topsis_category,
                    p.topsis_level,
                    p.calculated_at,
                    p.created_at,
                    k.id as karyawan_id_check,
                    k.nama as karyawan_nama,
                    k.nik,
                    u.id as penilai_id_check,
                    u.nama as penilai_nama
                  FROM penilaian p
                  LEFT JOIN karyawan k ON p.karyawan_id = k.id
                  LEFT JOIN users u ON p.penilai_id = u.id
                  WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $sql_penilaian);

if (!$stmt) {
    $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    error_log("Prepare error: " . mysqli_error($conn));
    redirect('index.php');
}

mysqli_stmt_bind_param($stmt, 'i', $penilaian_id);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    error_log("Execute error: " . mysqli_error($conn));
    redirect('index.php');
}

$result = mysqli_stmt_get_result($stmt);
$penilaian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$penilaian) {
    $_SESSION['error'] = 'Data penilaian tidak ditemukan!';
    redirect('index.php');
}

// =============================================
// PROSES PERHITUNGAN TOPSIS
// =============================================

$criteria = TOPSIS_CRITERIA;
$criteria_list = ['kinerja', 'kedisiplinan', 'kerjasama', 'absensi'];

// =============================================
// STEP 1: Ambil semua data penilaian untuk normalisasi
// =============================================

$sql_all = "SELECT 
              p.id, 
              p.karyawan_id, 
              p.kinerja, 
              p.kedisiplinan, 
              p.kerjasama, 
              p.absensi,
              k.nama as nama_karyawan, 
              k.nik
            FROM penilaian p
            LEFT JOIN karyawan k ON p.karyawan_id = k.id
            WHERE MONTH(p.tanggal_penilaian) = MONTH(p.tanggal_penilaian)
            AND YEAR(p.tanggal_penilaian) = YEAR(p.tanggal_penilaian)
            ORDER BY p.tanggal_penilaian DESC";

$result_all = mysqli_query($conn, $sql_all);

if (!$result_all) {
    $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    error_log("Query error: " . mysqli_error($conn));
    redirect('index.php');
}

$all_data = [];
while ($row = mysqli_fetch_assoc($result_all)) {
    $all_data[] = $row;
}

if (empty($all_data)) {
    $_SESSION['error'] = 'Tidak ada data penilaian untuk perhitungan!';
    redirect('index.php');
}

// =============================================
// STEP 1: NORMALISASI MATRIKS KEPUTUSAN
// =============================================

$step1_data = [];
$sum_squares = [];

// Hitung sum of squares untuk setiap kriteria
foreach ($criteria_list as $c) {
    $sum_squares[$c] = 0;
    foreach ($all_data as $data) {
        $sum_squares[$c] += pow($data[$c], 2);
    }
}

// Normalisasi untuk setiap alternatif
$normalized_matrix = [];
foreach ($all_data as $data) {
    $normalized = [];
    foreach ($criteria_list as $c) {
        if ($sum_squares[$c] > 0) {
            $normalized[$c] = $data[$c] / sqrt($sum_squares[$c]);
        } else {
            $normalized[$c] = 0;
        }
    }
    $normalized_matrix[$data['id']] = $normalized;
    
    // Simpan untuk ditampilkan
    if ($data['id'] == $penilaian_id) {
        $step1_data = [
            'original' => $data,
            'normalized' => $normalized,
            'sum_squares' => $sum_squares
        ];
    }
}

// =============================================
// STEP 2: BOBOT TERNORMALISASI
// =============================================

$step2_data = [];
$weighted_matrix = [];

foreach ($normalized_matrix as $id => $normalized) {
    $weighted = [];
    foreach ($criteria_list as $c) {
        $weighted[$c] = $normalized[$c] * $criteria[$c]['weight'];
    }
    $weighted_matrix[$id] = $weighted;
    
    if ($id == $penilaian_id) {
        $step2_data = [
            'normalized' => $normalized,
            'weights' => [
                'kinerja' => $criteria['kinerja']['weight'],
                'kedisiplinan' => $criteria['kedisiplinan']['weight'],
                'kerjasama' => $criteria['kerjasama']['weight'],
                'absensi' => $criteria['absensi']['weight']
            ],
            'weighted' => $weighted
        ];
    }
}

// =============================================
// STEP 3: SOLUSI IDEAL POSITIF & NEGATIF
// =============================================

$step3_data = [];
$ideal_positive = [];
$ideal_negative = [];

foreach ($criteria_list as $c) {
    $values = [];
    foreach ($weighted_matrix as $weighted) {
        $values[] = $weighted[$c];
    }
    
    if ($criteria[$c]['type'] == 'benefit') {
        $ideal_positive[$c] = max($values);
        $ideal_negative[$c] = min($values);
    } else {
        $ideal_positive[$c] = min($values);
        $ideal_negative[$c] = max($values);
    }
}

$step3_data = [
    'ideal_positive' => $ideal_positive,
    'ideal_negative' => $ideal_negative,
    'criteria_types' => [
        'kinerja' => $criteria['kinerja']['type'],
        'kedisiplinan' => $criteria['kedisiplinan']['type'],
        'kerjasama' => $criteria['kerjasama']['type'],
        'absensi' => $criteria['absensi']['type']
    ]
];

// =============================================
// STEP 4: JARAK KE SOLUSI IDEAL
// =============================================

$step4_data = [];
$distances = [];

foreach ($weighted_matrix as $id => $weighted) {
    $d_plus = 0;
    $d_minus = 0;
    
    foreach ($criteria_list as $c) {
        $d_plus += pow($weighted[$c] - $ideal_positive[$c], 2);
        $d_minus += pow($weighted[$c] - $ideal_negative[$c], 2);
    }
    
    $d_plus = sqrt($d_plus);
    $d_minus = sqrt($d_minus);
    
    $distances[$id] = [
        'd_plus' => $d_plus,
        'd_minus' => $d_minus
    ];
    
    if ($id == $penilaian_id) {
        $step4_data = [
            'weighted' => $weighted,
            'ideal_positive' => $ideal_positive,
            'ideal_negative' => $ideal_negative,
            'd_plus' => $d_plus,
            'd_minus' => $d_minus,
            'calculations' => [
                'd_plus_detail' => calculateDistance($weighted, $ideal_positive),
                'd_minus_detail' => calculateDistance($weighted, $ideal_negative)
            ]
        ];
    }
}

// =============================================
// STEP 5: NILAI PREFERENSI DAN RANKING
// =============================================

$step5_data = [];
$preferences = [];

foreach ($distances as $id => $dist) {
    $denominator = $dist['d_plus'] + $dist['d_minus'];
    
    if ($denominator > 0) {
        $preference = $dist['d_minus'] / $denominator;
    } else {
        $preference = 0;
    }
    
    $preferences[$id] = $preference;
    
    if ($id == $penilaian_id) {
        // Tentukan kategori
        $category = 'normal';
        $level = 'normal';
        
        if ($preference >= 0.7) {
            $category = 'reward';
            // Tentukan level reward
            if ($preference >= 0.8) {
                $level = 'sangat_baik';
            } else {
                $level = 'baik';
            }
        } elseif ($preference < 0.3) {
            $category = 'punishment';
            // Tentukan level punishment
            if ($preference < 0.1) {
                $level = 'berat';
            } elseif ($preference < 0.2) {
                $level = 'sedang';
            } else {
                $level = 'ringan';
            }
        }
        
        $step5_data = [
            'd_plus' => $dist['d_plus'],
            'd_minus' => $dist['d_minus'],
            'preference' => $preference,
            'category' => $category,
            'level' => $level,
            'ranking_position' => 0 // akan dihitung setelah sort
        ];
    }
}

// Sort untuk mendapatkan ranking
$sorted_preferences = $preferences;
arsort($sorted_preferences);

$rank = 1;
foreach ($sorted_preferences as $id => $pref) {
    if ($id == $penilaian_id) {
        $step5_data['ranking_position'] = $rank;
        break;
    }
    $rank++;
}

// =============================================
// SIMPAN HASIL KE DATABASE
// =============================================

$category = $step5_data['category'];
$level = $step5_data['level'];
$preference = $step5_data['preference'];
$d_plus = $step5_data['d_plus'];
$d_minus = $step5_data['d_minus'];

// Update penilaian dengan hasil TOPSIS
$sql_update = "UPDATE penilaian 
               SET topsis_preference = ?, 
                   topsis_d_plus = ?, 
                   topsis_d_minus = ?,
                   topsis_category = ?,
                   topsis_level = ?,
                   topsis_status = 'calculated',
                   calculated_at = NOW()
               WHERE id = ?";

$stmt_update = mysqli_prepare($conn, $sql_update);

if (!$stmt_update) {
    $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    error_log("Prepare update error: " . mysqli_error($conn));
    redirect('index.php');
}

mysqli_stmt_bind_param($stmt_update, 'ddddsi',
    $preference, $d_plus, $d_minus, $category, $level, $penilaian_id);

if (!mysqli_stmt_execute($stmt_update)) {
    $_SESSION['error'] = 'Gagal menyimpan hasil TOPSIS: ' . mysqli_error($conn);
    error_log("Execute update error: " . mysqli_error($conn));
    redirect('index.php');
}

mysqli_stmt_close($stmt_update);

// Log activity
logActivity('calculate_topsis', "Menghitung TOPSIS untuk penilaian ID: $penilaian_id, Nilai Preferensi: $preference");

// Siapkan data untuk ditampilkan
$topsis_results = [
    'penilaian_id' => $penilaian_id,
    'penilaian' => $penilaian,
    'step1' => $step1_data,
    'step2' => $step2_data,
    'step3' => $step3_data,
    'step4' => $step4_data,
    'step5' => $step5_data,
    'all_alternatives' => $all_data,
    'all_preferences' => $preferences
];

// Simpan ke session untuk digunakan di halaman hasil
$_SESSION['topsis_results'] = $topsis_results;

// Redirect ke halaman hasil
redirect('topsis_result.php?id=' . $penilaian_id);

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Hitung jarak Euclidean
 */
function calculateDistance($vector, $ideal) {
    $sum = 0;
    foreach ($vector as $key => $value) {
        $sum += pow($value - $ideal[$key], 2);
    }
    return sqrt($sum);
}

?>