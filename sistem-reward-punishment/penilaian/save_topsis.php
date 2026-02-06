<?php
require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $save_results = $_POST['save_result'] ?? [];

    if (empty($save_results)) {
        redirect('topsis.php', null, 'Pilih minimal satu hasil untuk disimpan');
    }

    $success_count = 0;
    $errors = [];

    foreach ($save_results as $penilaian_id) {
        // Ambil data penilaian
        $sql = "SELECT * FROM penilaian WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $penilaian_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $penilaian = mysqli_fetch_assoc($result);

        if ($penilaian) {
            // Hitung TOPSIS untuk penilaian ini
            $topsis_result = calculateTopsisForKaryawan($penilaian['karyawan_id']);

            if ($topsis_result['success']) {
                // Simpan hasil TOPSIS
                $save_result = saveTopsisResult($penilaian_id, $topsis_result['result']);

                if ($save_result['success']) {
                    $success_count++;
                } else {
                    $errors[] = "Gagal menyimpan hasil untuk karyawan ID {$penilaian['karyawan_id']}: " . $save_result['message'];
                }
            } else {
                $errors[] = "Gagal menghitung TOPSIS untuk karyawan ID {$penilaian['karyawan_id']}: " . $topsis_result['message'];
            }
        } else {
            $errors[] = "Data penilaian dengan ID {$penilaian_id} tidak ditemukan";
        }
    }

    if ($success_count > 0) {
        $message = "Berhasil menyimpan {$success_count} hasil perhitungan TOPSIS";
        if (!empty($errors)) {
            $message .= ". Namun ada " . count($errors) . " error: " . implode('; ', $errors);
        }
        redirect('topsis.php', $message);
    } else {
        redirect('topsis.php', null, 'Gagal menyimpan hasil: ' . implode('; ', $errors));
    }
} else {
    redirect('topsis.php');
}
?>