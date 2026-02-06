<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/pdf_helper.php';

// Pastikan user terautentikasi
requireLogin();

// Cek permission
if (!hasPermission('view_punishment')) {
    redirect('dashboard.php');
}

// Get and normalize parameters
$bulan = isset($_GET['bulan']) ? str_pad((int)$_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$karyawan_id = isset($_GET['karyawan_id']) ? intval($_GET['karyawan_id']) : 0;

// Bulan list
$bulanList = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Build query using project's mysqli helper
$sql = "
    SELECT p.*, k.nik, k.nama, k.jabatan, u.nama as diberikan_oleh_nama
    FROM punishment p
    JOIN karyawan k ON p.karyawan_id = k.id
    LEFT JOIN users u ON p.diberikan_oleh = u.id
    WHERE MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?";

$params = [$bulan, $tahun];
if ($karyawan_id > 0) {
    $sql .= " AND p.karyawan_id = ?";
    $params[] = $karyawan_id;
}

$sql .= " ORDER BY p.tanggal DESC";

$result = executeQuery($sql, $params);
$punishments = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $punishments[] = $row;
    }
}

// Get total statistics
$total_nilai = 0;
$total_karyawan = count($punishments);
foreach ($punishments as $punishment) {
    $total_nilai += floatval($punishment['nilai_akhir'] ?? 0);
}
$rata_nilai = $total_karyawan > 0 ? $total_nilai / $total_karyawan : 0;

// Get karyawan specific data if needed
$karyawan_detail = null;
if ($karyawan_id > 0 && count($punishments) > 0) {
    $karyawan_detail = $punishments[0];
}

// Create PDF
$pdf = new PDFHelper('P', 'mm', 'A4');
$pdf->setTitle('LAPORAN REKOMENDASI PUNISHMENT KARYAWAN');
$pdf->setSubtitle('Periode: ' . $bulanList[$bulan] . ' ' . $tahun);
$pdf->setFooterText('Sistem Reward & Punishment Karyawan - PT. Perusahaan Contoh');
$pdf->AliasNbPages();

$pdf->AddPage();

// Add company info
$pdf->SetFont('Arial', '', 10);
$pdf->addCompanyInfo();
$pdf->Ln(10);

// Warning Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(0, 10, 'DOKUMEN RAHASIA - HANYA UNTUK KALANGAN INTERNAL', 0, 1, 'C');
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 10);

// Summary
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, 'RINGKASAN', 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(40, 6, 'Periode', 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, $bulanList[$bulan] . ' ' . $tahun, 0, 1);

$pdf->Cell(40, 6, 'Total Karyawan', 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, $total_karyawan . ' orang', 0, 1);

$pdf->Cell(40, 6, 'Rata-rata Nilai', 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, number_format($rata_nilai, 2), 0, 1);

$pdf->Cell(40, 6, 'Tanggal Cetak', 0, 0);
$pdf->Cell(5, 6, ':', 0, 0);
$pdf->Cell(0, 6, date('d/m/Y H:i'), 0, 1);

$pdf->Ln(10);

// Table Header
$header = array('NO', 'NIK', 'NAMA KARYAWAN', 'JABATAN', 'NILAI', 'LEVEL', 'TINDAKAN');
$colWidths = array(10, 25, 50, 35, 20, 30, 30);

$pdf->tableHeader($header, $colWidths);

// Table Data
$no = 1;
$fill = false;

foreach($punishments as $punishment) {
    // Determine punishment level
    $level = '';
    $tindakan = '';
    
    if($punishment['nilai_akhir'] < 40) {
        $level = 'SP3';
        $tindakan = 'Peringatan Ketiga';
    } elseif($punishment['nilai_akhir'] < 50) {
        $level = 'SP2';
        $tindakan = 'Peringatan Kedua';
    } elseif($punishment['nilai_akhir'] < 60) {
        $level = 'SP1';
        $tindakan = 'Peringatan Pertama';
    } else {
        $level = 'PERINGATAN';
        $tindakan = 'Pembinaan';
    }
    
    $data = array(
        $no,
        $punishment['nik'],
        $punishment['nama'],
        $punishment['jabatan'],
        number_format($punishment['nilai_akhir'], 2),
        $level,
        $tindakan
    );
    
    $pdf->tableRow($data, $colWidths, $fill);
    $fill = !$fill;
    $no++;
}

$pdf->Ln(10);

// Detail Kriteria Penilaian
if($karyawan_detail) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'DETAIL PENILAIAN INDIVIDU', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(40, 6, 'Nama Karyawan', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['nama'], 0, 1);
    
    $pdf->Cell(40, 6, 'NIK', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['nik'], 0, 1);
    
    $pdf->Cell(40, 6, 'Jabatan', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(0, 6, $karyawan_detail['jabatan'], 0, 1);
    
    $pdf->Ln(5);
    
    // Kriteria Table
    $header_kriteria = array('NO', 'KRITERIA', 'NILAI', 'STANDAR', 'STATUS');
    $colWidths_kriteria = array(10, 50, 25, 35, 60);
    
    $pdf->tableHeader($header_kriteria, $colWidths_kriteria);
    
    $kriteria_data = array(
        array('1', 'Absensi', $karyawan_detail['absensi'] . '%', 'Min. 80%', 
              $karyawan_detail['absensi'] >= 80 ? '✓ Memenuhi' : '✗ Tidak Memenuhi'),
        array('2', 'Jumlah Telat', $karyawan_detail['jumlah_telat'] . ' kali', 'Maks. 3 kali', 
              $karyawan_detail['jumlah_telat'] <= 3 ? '✓ Memenuhi' : '✗ Tidak Memenuhi'),
        array('3', 'Jumlah Tidak Hadir', $karyawan_detail['jumlah_tidak_hadir'] . ' hari', 'Maks. 2 hari', 
              $karyawan_detail['jumlah_tidak_hadir'] <= 2 ? '✓ Memenuhi' : '✗ Tidak Memenuhi'),
        array('4', 'Kecepatan Kinerja', $karyawan_detail['kecepatan_kinerja'], 'Min. 70', 
              $karyawan_detail['kecepatan_kinerja'] >= 70 ? '✓ Memenuhi' : '✗ Tidak Memenuhi'),
        array('5', 'Kualitas Hasil Kerja', $karyawan_detail['kualitas_hasil_kerja'], 'Min. 75', 
              $karyawan_detail['kualitas_hasil_kerja'] >= 75 ? '✓ Memenuhi' : '✗ Tidak Memenuhi')
    );
    
    $fill_kriteria = false;
    foreach($kriteria_data as $row) {
        $pdf->tableRow($row, $colWidths_kriteria, $fill_kriteria);
        $fill_kriteria = !$fill_kriteria;
    }
    
    $pdf->Ln(10);
    
    // Analisis Penyebab
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'ANALISIS PENYEBAB', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $analisis = [];
    
    if($karyawan_detail['absensi'] < 80) {
        $analisis[] = '• Absensi rendah (' . $karyawan_detail['absensi'] . '%) dari standar 80%';
    }
    if($karyawan_detail['jumlah_telat'] > 3) {
        $analisis[] = '• Sering terlambat (' . $karyawan_detail['jumlah_telat'] . 'x) dari maksimal 3x';
    }
    if($karyawan_detail['jumlah_tidak_hadir'] > 2) {
        $analisis[] = '• Tidak hadir tanpa keterangan (' . $karyawan_detail['jumlah_tidak_hadir'] . ' hari)';
    }
    if($karyawan_detail['kecepatan_kinerja'] < 70) {
        $analisis[] = '• Kecepatan kerja rendah (' . $karyawan_detail['kecepatan_kinerja'] . ') dari standar 70';
    }
    if($karyawan_detail['kualitas_hasil_kerja'] < 75) {
        $analisis[] = '• Kualitas kerja rendah (' . $karyawan_detail['kualitas_hasil_kerja'] . ') dari standar 75';
    }
    
    if(count($analisis) > 0) {
        foreach($analisis as $item) {
            $pdf->MultiCell(0, 6, $item);
        }
    } else {
        $pdf->MultiCell(0, 6, 'Tidak ada analisis spesifik. Nilai keseluruhan di bawah standar.');
    }
    
    $pdf->Ln(10);
    
    // Rekomendasi Detail
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'REKOMENDASI TINDAKAN', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $rekomendasi = '';
    
    if($karyawan_detail['nilai_akhir'] < 40) {
        $rekomendasi = "1. Surat Peringatan 3 (SP3) dikeluarkan\n" .
                       "2. Evaluasi kinerja 30 hari ke depan\n" .
                       "3. Kemungkinan pemutusan hubungan kerja jika tidak ada perbaikan\n" .
                       "4. Konseling dengan psikolog perusahaan\n" .
                       "5. Monitoring ketat oleh atasan langsung";
    } elseif($karyawan_detail['nilai_akhir'] < 50) {
        $rekomendasi = "1. Surat Peringatan 2 (SP2) dikeluarkan\n" .
                       "2. Program pembinaan intensif 60 hari\n" .
                       "3. Pelatihan ulang bidang pekerjaan\n" .
                       "4. Evaluasi mingguan oleh supervisor\n" .
                       "5. Pemotongan bonus bulanan 50%";
    } elseif($karyawan_detail['nilai_akhir'] < 60) {
        $rekomendasi = "1. Surat Peringatan 1 (SP1) dikeluarkan\n" .
                       "2. Program perbaikan kinerja 90 hari\n" .
                       "3. Coaching oleh senior staff\n" .
                       "4. Evaluasi bulanan\n" .
                       "5. Pemotongan bonus bulanan 25%";
    } else {
        $rekomendasi = "1. Peringatan lisan resmi\n" .
                       "2. Program pembinaan ringan\n" .
                       "3. Bimbingan oleh atasan langsung\n" .
                       "4. Evaluasi dalam 3 bulan";
    }
    
    $pdf->MultiCell(0, 6, $rekomendasi);
    
    $pdf->Ln(5);
    
    // Timeline Perbaikan
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'TIMELINE PERBAIKAN', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $timeline = "1. Tanggal mulai: " . date('d/m/Y') . "\n" .
                "2. Review pertama: " . date('d/m/Y', strtotime('+30 days')) . "\n" .
                "3. Evaluasi tengah: " . date('d/m/Y', strtotime('+60 days')) . "\n" .
                "4. Evaluasi akhir: " . date('d/m/Y', strtotime('+90 days')) . "\n" .
                "5. Keputusan final: " . date('d/m/Y', strtotime('+95 days'));
    
    $pdf->MultiCell(0, 6, $timeline);
}

$pdf->Ln(15);

// Important Notes
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 5, 'PENTING:', 0, 1);
$pdf->SetFont('Arial', 'I', 8);
$pdf->MultiCell(0, 4, "1. Dokumen ini bersifat RAHASIA dan hanya untuk keperluan internal perusahaan.\n2. Proses punishment harus mengikuti prosedur ketenagakerjaan yang berlaku.\n3. Karyawan berhak mendapatkan klarifikasi dan pembelaan.\n4. Semua tindakan harus didokumentasikan dengan baik.\n5. Untuk pertanyaan hukum, hubungi departemen Legal.");

$pdf->Ln(10);

// Multiple Signatures
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 5, 'Disetujui oleh:', 0, 0);
$pdf->Cell(95, 5, 'Diketahui oleh:', 0, 1);

$pdf->Ln(20);

// HRD Signature
$pdf->Cell(95, 5, '________________________', 0, 0);
$pdf->Cell(95, 5, '________________________', 0, 1);

$pdf->Cell(95, 5, 'Manager HRD', 0, 0);
$pdf->Cell(95, 5, 'Atasan Langsung', 0, 1);

$pdf->Ln(15);

// Director Signature
$pdf->Cell(0, 5, 'Disahkan oleh:', 0, 1);
$pdf->Ln(20);
$pdf->Cell(0, 5, '________________________', 0, 1);
$pdf->Cell(0, 5, 'Direktur', 0, 1);

// Output PDF
$filename = 'Laporan_Punishment_' . $bulanList[$bulan] . '_' . $tahun . ($karyawan_id > 0 ? '_' . $karyawan_detail['nama'] : '') . '.pdf';
$pdf->Output('I', $filename);
?>