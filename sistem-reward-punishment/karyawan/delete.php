<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hanya admin yang bisa menghapus
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin dan HRD admin yang dapat menghapus karyawan.';
    redirect('../index.php');
}

// Cek apakah ID tersedia
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error'] = 'ID karyawan tidak valid!';
    redirect('../index.php');
}

$id = intval($_POST['id']);

// Cek apakah karyawan ada
$karyawan = getSingleRow("SELECT k.*, d.nama as departemen_nama, j.nama as jabatan_nama 
                         FROM karyawan k 
                         LEFT JOIN departemen d ON k.departemen_id = d.id 
                         LEFT JOIN jabatan j ON k.jabatan_id = j.id 
                         WHERE k.id = ?", [$id]);

if (!$karyawan) {
    $_SESSION['error'] = 'Data karyawan tidak ditemukan!';
    redirect('../index.php');
}

// Cek apakah karyawan memiliki data penilaian
$has_penilaian = getSingleRow("SELECT COUNT(*) as count FROM penilaian WHERE karyawan_id = ?", [$id]);
if ($has_penilaian['count'] > 0) {
    $_SESSION['error'] = 'Karyawan tidak dapat dihapus karena memiliki data penilaian!';
    redirect('../index.php');
}

$conn = connectDB();

try {
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    // Hapus foto jika ada
    if (!empty($karyawan['foto']) && file_exists("../../uploads/karyawan/{$karyawan['foto']}")) {
        unlink("../../uploads/karyawan/{$karyawan['foto']}");
    }
    
    // Hapus data karyawan
    $sql = "DELETE FROM karyawan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_commit($conn);
        
        // Log activity
        logActivity('delete_karyawan', "Menghapus karyawan: {$karyawan['nama']} ({$karyawan['nik']})");
        
        $_SESSION['success'] = "Karyawan {$karyawan['nama']} berhasil dihapus!";
    } else {
        throw new Exception('Gagal menghapus data karyawan: ' . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Gagal menghapus karyawan: " . $e->getMessage();
}

// Redirect kembali ke halaman karyawan
redirect('../index.php');
?>