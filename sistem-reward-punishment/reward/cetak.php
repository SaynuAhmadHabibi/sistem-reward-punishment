<?php
require_once '../includes/functions.php';
require_once '../includes/pdf_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin dan manager yang bisa akses
if (!isAdmin() && !isManager()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin dan manager yang dapat mencetak laporan reward.';
    redirect('../dashboard.php');
}

// Get parameters
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
$karyawan_id = intval($_GET['karyawan_id'] ?? 0);

// Validasi parameter
if (!is_numeric($bulan) || $bulan < 1 || $bulan > 12) {
    $bulan = date('m');
}
if (!is_numeric($tahun) || $tahun < 2020 || $tahun > date('Y') + 1) {
    $tahun = date('Y');
}

// Bulan list
$bulanList = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Get data reward
$sql = "SELECT r.*, k.nik, k.nama, j.nama as jabatan, d.nama as departemen,
               p.tanggal_penilaian
        FROM reward r
        LEFT JOIN karyawan k ON r.karyawan_id = k.id
        LEFT JOIN jabatan j ON k.jabatan_id = j.id
        LEFT JOIN departemen d ON k.departemen_id = d.id
        LEFT JOIN penilaian p ON r.penilaian_id = p.id
        WHERE MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";

$params = [$bulan, $tahun];

if ($karyawan_id > 0) {
    $sql .= " AND r.karyawan_id = ?";
    $params[] = $karyawan_id;
}

$sql .= " ORDER BY r.tanggal DESC, k.nama ASC";

$rewards = getMultipleRows($sql, $params);

// Hitung statistik
$total_karyawan = count($rewards);
$total_nilai = 0;
$rata_nilai = 0;

if ($total_karyawan > 0) {
    foreach ($rewards as $reward) {
        $total_nilai += $reward['nilai_akhir'] ?? 0;
    }
    $rata_nilai = $total_nilai / $total_karyawan;
}

// Get karyawan detail jika spesifik
$karyawan_detail = null;
if ($karyawan_id > 0 && !empty($rewards)) {
    $karyawan_detail = $rewards[0];
}

// Create PDF
$pdf = new PDFHelper('P', 'mm', 'A4');
$pdf->AliasNbPages();

$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, $pdf->safeText('LAPORAN REWARD KARYAWAN'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 5, $pdf->safeText('Periode: ' . $bulanList[$bulan] . ' ' . $tahun), 0, 1, 'C');
$pdf->Ln(10);

// Summary
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, $pdf->safeText('RINGKASAN'), 0, 1);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(50, 6, $pdf->safeText('Periode'), 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, $pdf->safeText($bulanList[$bulan] . ' ' . $tahun), 0, 1);

$pdf->Cell(50, 6, $pdf->safeText('Total Karyawan'), 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, $pdf->safeText($total_karyawan . ' orang'), 0, 1);

$pdf->Cell(50, 6, $pdf->safeText('Rata-rata Nilai'), 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, number_format($rata_nilai, 2), 0, 1);

$pdf->Cell(50, 6, $pdf->safeText('Tanggal Cetak'), 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, date('d/m/Y H:i'), 0, 1);

$pdf->Ln(10);

// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);

// Header cells
$header = array('NO', 'NIK', 'NAMA KARYAWAN', 'JABATAN', 'NILAI', 'JENIS REWARD');
$w = array(15, 25, 60, 40, 25, 35);

for($i=0; $i<count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table Data
$pdf->SetFont('Arial', '', 9);
$fill = false;
$no = 1;

foreach($rewards as $reward) {
    // Determine reward type based on nilai
    $jenisReward = '';
    if (($reward['nilai_akhir'] ?? 0) >= 90) {
        $jenisReward = 'Bonus Besar';
    } elseif (($reward['nilai_akhir'] ?? 0) >= 80) {
        $jenisReward = 'Bonus Sedang';
    } elseif (($reward['nilai_akhir'] ?? 0) >= 70) {
        $jenisReward = 'Bonus Kecil';
    } else {
        $jenisReward = 'Apresiasi';
    }

    $pdf->Cell($w[0], 6, $no, 1, 0, 'C', $fill);
    $pdf->Cell($w[1], 6, $pdf->safeText($reward['nik'] ?? '-'), 1, 0, 'L', $fill);
    $pdf->Cell($w[2], 6, $pdf->safeText(substr($reward['nama'] ?? '-', 0, 25)), 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 6, $pdf->safeText(substr($reward['jabatan'] ?? '-', 0, 18)), 1, 0, 'L', $fill);
    $pdf->Cell($w[4], 6, number_format($reward['nilai_akhir'] ?? 0, 1), 1, 0, 'C', $fill);
    $pdf->Cell($w[5], 6, $pdf->safeText($jenisReward), 1, 0, 'L', $fill);
    $pdf->Ln();

    $fill = !$fill;
    $no++;
}

$pdf->Ln(10);

// Detail Kriteria Penilaian
if($karyawan_detail) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'DETAIL PENILAIAN INDIVIDU', 0, 1);
    $pdf->SetFont('Arial', '', 11);

    $pdf->Cell(50, 6, 'Nama Karyawan', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['nama'] ?? '-', 0, 1);

    $pdf->Cell(50, 6, 'NIK', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['nik'] ?? '-', 0, 1);

    $pdf->Cell(50, 6, 'Jabatan', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['jabatan'] ?? '-', 0, 1);

    $pdf->Cell(50, 6, 'Departemen', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['departemen'] ?? '-', 0, 1);

    $pdf->Cell(50, 6, 'Nilai Akhir', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, number_format($karyawan_detail['nilai_akhir'] ?? 0, 2), 0, 1);

    $pdf->Ln(10);

    // Rekomendasi Detail
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'REKOMENDASI DETAIL', 0, 1);
    $pdf->SetFont('Arial', '', 11);

    $jenisReward = '';
    $detailReward = '';

    $nilai = $karyawan_detail['nilai_akhir'] ?? 0;
    if($nilai >= 90) {
        $jenisReward = 'BONUS BESAR';
        $detailReward = "1. Bonus bulanan 2x gaji pokok\n2. Voucher belanja Rp 1.000.000\n3. Program pengembangan karir prioritas\n4. Cuti tambahan 3 hari\n5. Piagam penghargaan";
    } elseif($nilai >= 80) {
        $jenisReward = 'BONUS SEDANG';
        $detailReward = "1. Bonus bulanan 1.5x gaji pokok\n2. Voucher belanja Rp 500.000\n3. Pelatihan khusus\n4. Cuti tambahan 2 hari";
    } elseif($nilai >= 70) {
        $jenisReward = 'BONUS KECIL';
        $detailReward = "1. Bonus bulanan 1x gaji pokok\n2. Voucher belanja Rp 250.000\n3. Apresiasi publik di meeting bulanan";
    } else {
        $jenisReward = 'APRESIASI';
        $detailReward = "1. Bonus insentif Rp 500.000\n2. Sertifikat penghargaan\n3. Pengumuman apresiasi internal";
    }

    $pdf->Cell(50, 6, 'Jenis Reward', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 6, $jenisReward, 0, 1);
    $pdf->SetFont('Arial', '', 11);

    $pdf->Ln(5);
    $pdf->MultiCell(0, 6, $detailReward);
}

$pdf->Ln(15);

// Notes
$pdf->SetFont('Arial', 'I', 9);
$pdf->MultiCell(0, 5, $pdf->safeText("Catatan:\n1. Dokumen ini bersifat resmi dan dapat digunakan sebagai referensi pemberian reward.\n2. Reward akan diberikan maksimal 30 hari setelah dokumen ini diterbitkan.\n3. Untuk pertanyaan lebih lanjut, hubungi departemen HRD."));

$pdf->Ln(10);

// Signature
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, $pdf->safeText('Dikeluarkan di: ' . 'Jakarta'), 0, 1);
$pdf->Cell(0, 8, $pdf->safeText('Pada tanggal: ' . date('d F Y')), 0, 1);

$pdf->Ln(20);
$pdf->Cell(120);
$pdf->Cell(0, 8, '______________________________', 0, 1);
$pdf->Cell(120);
$pdf->Cell(0, 8, $pdf->safeText($_SESSION['nama'] ?? 'Administrator'), 0, 1);
$pdf->Cell(120);
$pdf->Cell(0, 8, strtoupper($_SESSION['role'] ?? 'ADMIN'), 0, 1);

// Output PDF
$filename = 'Laporan_Reward_' . $bulanList[$bulan] . '_' . $tahun . ($karyawan_id > 0 ? '_' . ($karyawan_detail['nama'] ?? 'Unknown') : '') . '.pdf';
$pdf->Output('I', $filename);
?>