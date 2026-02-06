<?php
require_once '../includes/functions.php';
require_once '../includes/pdf_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

$conn = connectDB();

// Ambil data punishment berdasarkan ID dengan validasi
$id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid punishment ID!");
}

$sql = "SELECT p.*, k.nama as nama_karyawan, k.nik, k.jabatan_id, j.nama as nama_jabatan
        FROM punishment p 
        LEFT JOIN karyawan k ON p.karyawan_id = k.id 
        LEFT JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Data punishment tidak ditemukan!");
}

$punishment = mysqli_fetch_assoc($result);

// Buat PDF surat peringatan
$pdf = new PDFHelper('P', 'mm', 'A4');
$pdf->SetTitle('Surat Peringatan - ' . $punishment['nama_karyawan']);
$pdf->SetAuthor('Sistem Reward & Punishment');

// Data untuk surat peringatan
$letter_data = [
    'id' => $punishment['id'],
    'nama_karyawan' => $punishment['nama_karyawan'],
    'nik' => $punishment['nik'],
    'jabatan' => $punishment['nama_jabatan'],
    'level' => $punishment['level'],
    'alasan' => $punishment['alasan'],
    'topsis_score' => $punishment['topsis_score'],
    'tanggal' => $punishment['tanggal']
];

// Buat surat peringatan
$pdf->CreatePunishmentLetter($letter_data);

// Output PDF
$pdf->Output('I', 'Surat_Peringatan_' . $punishment['nama_karyawan'] . '.pdf');
?>