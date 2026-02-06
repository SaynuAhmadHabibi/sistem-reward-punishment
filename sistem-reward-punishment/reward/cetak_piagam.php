<?php
require_once '../includes/functions.php';
require_once '../includes/pdf_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

$conn = connectDB();

// Ambil data reward berdasarkan ID dengan validasi
$id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid reward ID!");
}

$sql = "SELECT r.*, k.nama as nama_karyawan, k.nik, k.jabatan_id, j.nama as nama_jabatan
        FROM reward r 
        LEFT JOIN karyawan k ON r.karyawan_id = k.id 
        LEFT JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE r.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Data reward tidak ditemukan!");
}

$reward = mysqli_fetch_assoc($result);

// Buat PDF sertifikat
$pdf = new PDFHelper('P', 'mm', 'A4');
$pdf->SetTitle('Sertifikat Reward - ' . $reward['nama_karyawan']);
$pdf->SetAuthor('Sistem Reward & Punishment');

// Data untuk sertifikat
$certificate_data = [
    'nama_karyawan' => $reward['nama_karyawan'],
    'nik' => $reward['nik'],
    'jabatan' => $reward['nama_jabatan'],
    'level' => strtoupper(str_replace('_', ' ', $reward['level'])),
    'topsis_score' => $reward['topsis_score'],
    'tanggal' => $reward['tanggal']
];

// Buat sertifikat
$pdf->CreateRewardCertificate($certificate_data);

// Output PDF
$pdf->Output('I', 'Sertifikat_Reward_' . $reward['nama_karyawan'] . '.pdf');
?>