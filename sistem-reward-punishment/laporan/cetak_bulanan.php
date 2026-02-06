<?php
// Disable all error reporting to prevent output
error_reporting(0);
ini_set('display_errors', 0);

// Clean all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Set locale for FPDF
setlocale(LC_NUMERIC, 'C');

require_once '../includes/functions.php';
require_once '../includes/pdf_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../../login.php');
    exit;
}

// ✅ FIXED: Admin, HRD Admin, atau Direktur bisa akses laporan
if (!isAdmin() && !isHRDAdmin() && !isDirektur()) {
    $_SESSION['error'] = 'Akses ditolak! Anda tidak memiliki permission untuk melihat laporan.';
    redirect('index.php');
    exit;
}

$conn = connectDB();

// Ambil parameter periode dengan validasi
$bulan = isset($_GET['bulan']) ? sanitize($_GET['bulan']) : date('Y-m');

// Validasi format bulan (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $bulan = date('Y-m');
}

$tahun = date('Y', strtotime($bulan));
$nama_bulan = date('F Y', strtotime($bulan));

/* =======================
   DATA REWARD BULANAN
======================= */
$sql_reward = "SELECT r.*, k.nama AS nama_karyawan, k.nik 
               FROM reward r 
               LEFT JOIN karyawan k ON r.karyawan_id = k.id 
               WHERE DATE_FORMAT(r.tanggal, '%Y-%m') = ?
               ORDER BY r.tanggal DESC";

$stmt = mysqli_prepare($conn, $sql_reward);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$result_reward = mysqli_stmt_get_result($stmt);

$rewards = [];
while ($row = mysqli_fetch_assoc($result_reward)) {
    $rewards[] = $row;
}
mysqli_stmt_close($stmt);

/* =======================
   DATA PUNISHMENT BULANAN
======================= */
$sql_punishment = "SELECT p.*, k.nama AS nama_karyawan, k.nik 
                   FROM punishment p 
                   LEFT JOIN karyawan k ON p.karyawan_id = k.id 
                   WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ?
                   ORDER BY p.tanggal DESC";

$stmt = mysqli_prepare($conn, $sql_punishment);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$result_punishment = mysqli_stmt_get_result($stmt);

$punishments = [];
while ($row = mysqli_fetch_assoc($result_punishment)) {
    $punishments[] = $row;
}
mysqli_stmt_close($stmt);

/* =======================
   STATISTIK RINGKASAN
======================= */
$total_reward = count($rewards);
$total_punishment = count($punishments);

/* Rata-rata TOPSIS Reward */
$sql_avg_reward = "SELECT AVG(topsis_score) AS avg_score 
                   FROM reward 
                   WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
$stmt = mysqli_prepare($conn, $sql_avg_reward);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$result_avg_reward = mysqli_stmt_get_result($stmt);
$avg_reward = mysqli_fetch_assoc($result_avg_reward)['avg_score'] ?? 0;
mysqli_stmt_close($stmt);

/* Rata-rata TOPSIS Punishment */
$sql_avg_punishment = "SELECT AVG(topsis_score) AS avg_score 
                       FROM punishment 
                       WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
$stmt = mysqli_prepare($conn, $sql_avg_punishment);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$result_avg_punishment = mysqli_stmt_get_result($stmt);
$avg_punishment = mysqli_fetch_assoc($result_avg_punishment)['avg_score'] ?? 0;
mysqli_stmt_close($stmt);

$avg_topsis = ($avg_reward + $avg_punishment) / 2;

/* =======================
   DATA LAPORAN
======================= */
$report_data = [
    'summary' => [
        'bulan' => $nama_bulan,
        'total_reward' => $total_reward,
        'total_punishment' => $total_punishment,
        'avg_topsis' => round($avg_topsis, 2)
    ],
    'rewards' => $rewards,
    'punishments' => $punishments
];

/* =======================
   GENERATE PDF
======================= */
// Clean all output buffers completely
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Create PDF instance and generate content in one go
try {
    $pdf = new PDFHelper('P', 'mm', 'A4');
    $pdf->SetTitle('Laporan Bulanan Reward & Punishment - ' . $nama_bulan);
    $pdf->SetAuthor('Sistem Reward & Punishment');

    // Generate report content
    $pdf->CreateMonthlyReport($report_data);

    // Clean output buffer and send PDF to browser
    ob_end_clean();

    // Set headers for PDF display
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Laporan_' . $bulan . '_' . date('YmdHis') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');

    // Output PDF directly to browser
    $pdf->Output();

} catch (Exception $e) {
    // If PDF generation fails, show error
    ob_end_clean();
    die('Error generating PDF: ' . $e->getMessage());
}
exit;
