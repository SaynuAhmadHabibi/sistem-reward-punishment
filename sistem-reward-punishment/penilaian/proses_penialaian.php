<?php
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

/* ===============================
   CEK LOGIN
================================ */
if (!isLoggedIn()) {
    redirect('../login.php');
}

/* ===============================
   PROSES TOPSIS
   (PAKAI FILE ASLI PROJECT)
================================ */
require_once 'topsis.php'; 
// ⬆️ File ini menghasilkan array: $topsis_results

if (!isset($topsis_results) || empty($topsis_results)) {
    $_SESSION['error'] = "Data penilaian belum tersedia.";
    redirect('index.php');
}

/* ===============================
   SINKRON OTOMATIS
   REWARD & PUNISHMENT
================================ */
$tanggal = date('Y-m-d');
$bulan   = date('m');
$tahun   = date('Y');

foreach ($topsis_results as $hasil) {

    $karyawan_id = $hasil['karyawan_id'];
    $nilai       = round($hasil['preference'], 4);

    /* ===========================
       CEK DUPLIKASI BULANAN
    ============================ */
    $cek_sql = "SELECT id FROM reward WHERE karyawan_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
    $cek_stmt = mysqli_prepare($conn, $cek_sql);
    mysqli_stmt_bind_param($cek_stmt, 'iii', $karyawan_id, $bulan, $tahun);
    mysqli_stmt_execute($cek_stmt);
    $cek_result = mysqli_stmt_get_result($cek_stmt);
    $sudahAda = mysqli_num_rows($cek_result) > 0;
    mysqli_stmt_close($cek_stmt);

    /* ===========================
       REWARD OTOMATIS
    ============================ */
    if (!$sudahAda && $nilai >= 0.60) {

        if ($nilai >= 0.80) {
            $jenis_reward = 'Reward Emas';
        } elseif ($nilai >= 0.70) {
            $jenis_reward = 'Reward Perak';
        } else {
            $jenis_reward = 'Reward Perunggu';
        }

        $reward_sql = "INSERT INTO reward (karyawan_id, jenis_reward, nilai_topsis, tanggal, keterangan) VALUES (?, ?, ?, ?, ?)";
        $reward_stmt = mysqli_prepare($conn, $reward_sql);
        $keterangan_reward = 'Reward otomatis berdasarkan hasil penilaian TOPSIS';
        mysqli_stmt_bind_param($reward_stmt, 'isdss', $karyawan_id, $jenis_reward, $nilai, $tanggal, $keterangan_reward);
        mysqli_stmt_execute($reward_stmt);
        mysqli_stmt_close($reward_stmt);
    }

    /* ===========================
       PUNISHMENT OTOMATIS
    ============================ */
    if (!$sudahAda && $nilai < 0.50) {

        if ($nilai < 0.30) {
            $jenis_punishment = 'SP 2';
        } elseif ($nilai < 0.40) {
            $jenis_punishment = 'SP 1';
        } else {
            $jenis_punishment = 'Teguran Lisan';
        }

        $punishment_sql = "INSERT INTO punishment (karyawan_id, jenis_punishment, nilai_topsis, tanggal, keterangan) VALUES (?, ?, ?, ?, ?)";
        $punishment_stmt = mysqli_prepare($conn, $punishment_sql);
        $keterangan_punishment = 'Punishment otomatis berdasarkan hasil penilaian TOPSIS';
        mysqli_stmt_bind_param($punishment_stmt, 'isdss', $karyawan_id, $jenis_punishment, $nilai, $tanggal, $keterangan_punishment);
        mysqli_stmt_execute($punishment_stmt);
        mysqli_stmt_close($punishment_stmt);
    }
}

/* ===============================
   REDIRECT
================================ */
$_SESSION['success'] = "Penilaian berhasil diproses. Reward & Punishment dibuat otomatis.";
redirect('hasil.php');
