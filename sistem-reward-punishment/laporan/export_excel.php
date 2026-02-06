<?php
/**
 * Export Excel - Laporan Bulanan Reward & Punishment
 * File: laporan/export_excel.php
 * ✅ FIXED: Hapus kolom 'ranking' yang tidak ada di database
 */

require_once '../includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
    exit;
}

// Cek akses - Admin, HRD Admin, atau Direktur bisa export
if (!isAdmin() && !isHRDAdmin() && !isDirektur()) {
    $_SESSION['error'] = 'Akses ditolak! Anda tidak memiliki permission untuk export excel.';
    redirect('index.php');
    exit;
}

$conn = connectDB();

// Ambil parameter periode dengan validasi
$periode = isset($_GET['periode']) ? sanitize($_GET['periode']) : date('Y-m');

// Validasi format periode (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
    $periode = date('Y-m');
}

$tahun = date('Y', strtotime($periode . '-01'));
$nama_bulan = date('F Y', strtotime($periode . '-01'));

/* =======================
   DATA REWARD BULANAN
======================= */
// ✅ FIXED: Hapus r.ranking dari SELECT karena kolom tidak ada
$sql_reward = "SELECT 
    r.id,
    r.tanggal,
    k.nik,
    k.nama AS nama_karyawan,
    d.nama AS departemen,
    j.nama AS jabatan,
    r.jenis_reward,
    r.keterangan,
    r.topsis_score,
    r.level
FROM reward r
LEFT JOIN karyawan k ON r.karyawan_id = k.id
LEFT JOIN departemen d ON k.departemen_id = d.id
LEFT JOIN jabatan j ON k.jabatan_id = j.id
WHERE DATE_FORMAT(r.tanggal, '%Y-%m') = ?
ORDER BY r.topsis_score DESC, r.tanggal DESC";

$stmt = mysqli_prepare($conn, $sql_reward);
mysqli_stmt_bind_param($stmt, 's', $periode);
mysqli_stmt_execute($stmt);
$result_reward = mysqli_stmt_get_result($stmt);

$rewards = [];
$no_reward = 1;
while ($row = mysqli_fetch_assoc($result_reward)) {
    $row['ranking'] = $no_reward++; // Generate ranking on-the-fly
    $rewards[] = $row;
}
mysqli_stmt_close($stmt);

/* =======================
   DATA PUNISHMENT BULANAN
======================= */
// ✅ FIXED: Hapus p.ranking dari SELECT karena kolom tidak ada
$sql_punishment = "SELECT 
    p.id,
    p.tanggal,
    k.nik,
    k.nama AS nama_karyawan,
    d.nama AS departemen,
    j.nama AS jabatan,
    p.alasan AS jenis_punishment,
    p.sanksi AS keterangan,
    p.topsis_score,
    p.level
FROM punishment p
LEFT JOIN karyawan k ON p.karyawan_id = k.id
LEFT JOIN departemen d ON k.departemen_id = d.id
LEFT JOIN jabatan j ON k.jabatan_id = j.id
WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ?
ORDER BY p.topsis_score DESC, p.tanggal DESC";

$stmt = mysqli_prepare($conn, $sql_punishment);
mysqli_stmt_bind_param($stmt, 's', $periode);
mysqli_stmt_execute($stmt);
$result_punishment = mysqli_stmt_get_result($stmt);

$punishments = [];
$no_punishment = 1;
while ($row = mysqli_fetch_assoc($result_punishment)) {
    $row['ranking'] = $no_punishment++; // Generate ranking on-the-fly
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
mysqli_stmt_bind_param($stmt, 's', $periode);
mysqli_stmt_execute($stmt);
$result_avg_reward = mysqli_stmt_get_result($stmt);
$avg_reward = mysqli_fetch_assoc($result_avg_reward)['avg_score'] ?? 0;
mysqli_stmt_close($stmt);

/* Rata-rata TOPSIS Punishment */
$sql_avg_punishment = "SELECT AVG(topsis_score) AS avg_score 
                       FROM punishment 
                       WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
$stmt = mysqli_prepare($conn, $sql_avg_punishment);
mysqli_stmt_bind_param($stmt, 's', $periode);
mysqli_stmt_execute($stmt);
$result_avg_punishment = mysqli_stmt_get_result($stmt);
$avg_punishment = mysqli_fetch_assoc($result_avg_punishment)['avg_score'] ?? 0;
mysqli_stmt_close($stmt);

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_' . $periode . '_' . date('YmdHis') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan <?php echo $nama_bulan; ?></title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary td {
            padding: 5px;
        }
        .section-title {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h2>LAPORAN BULANAN REWARD & PUNISHMENT</h2>
    <h3>Periode: <?php echo $nama_bulan; ?></h3>
</div>

<!-- RINGKASAN -->
<div class="summary">
    <h3>Ringkasan</h3>
    <table>
        <tr>
            <td width="200"><strong>Total Reward</strong></td>
            <td><?php echo $total_reward; ?> karyawan</td>
        </tr>
        <tr>
            <td><strong>Total Punishment</strong></td>
            <td><?php echo $total_punishment; ?> karyawan</td>
        </tr>
        <tr>
            <td><strong>Rata-rata TOPSIS Reward</strong></td>
            <td><?php echo number_format($avg_reward, 4); ?></td>
        </tr>
        <tr>
            <td><strong>Rata-rata TOPSIS Punishment</strong></td>
            <td><?php echo number_format($avg_punishment, 4); ?></td>
        </tr>
    </table>
</div>

<!-- TABEL REWARD -->
<div class="section-title">DATA REWARD</div>
<table>
    <thead>
        <tr>
            <th>Ranking</th>
            <th>Tanggal</th>
            <th>NIK</th>
            <th>Nama Karyawan</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Level</th>
            <th>Jenis Reward</th>
            <th>Keterangan</th>
            <th>TOPSIS Score</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($rewards) > 0): ?>
            <?php foreach ($rewards as $reward): ?>
                <tr>
                    <td><?php echo $reward['ranking']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($reward['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($reward['nik'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($reward['nama_karyawan'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($reward['departemen'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($reward['jabatan'] ?? '-'); ?></td>
                    <td><?php echo strtoupper(str_replace('_', ' ', $reward['level'] ?? '-')); ?></td>
                    <td><?php echo htmlspecialchars($reward['jenis_reward'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($reward['keterangan'] ?? '-'); ?></td>
                    <td><?php echo number_format($reward['topsis_score'] ?? 0, 4); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="text-align: center;">Tidak ada data reward untuk periode ini</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- TABEL PUNISHMENT -->
<div class="section-title">DATA PUNISHMENT</div>
<table>
    <thead>
        <tr>
            <th>Ranking</th>
            <th>Tanggal</th>
            <th>NIK</th>
            <th>Nama Karyawan</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Level</th>
            <th>Alasan</th>
            <th>Sanksi</th>
            <th>TOPSIS Score</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($punishments) > 0): ?>
            <?php foreach ($punishments as $punishment): ?>
                <tr>
                    <td><?php echo $punishment['ranking']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($punishment['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($punishment['nik'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($punishment['nama_karyawan'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($punishment['departemen'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($punishment['jabatan'] ?? '-'); ?></td>
                    <td><?php echo strtoupper($punishment['level'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($punishment['jenis_punishment'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($punishment['keterangan'] ?? '-'); ?></td>
                    <td><?php echo number_format($punishment['topsis_score'] ?? 0, 4); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="text-align: center;">Tidak ada data punishment untuk periode ini</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- FOOTER -->
<div style="margin-top: 30px; font-size: 10px;">
    <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
    <p>Sistem Reward & Punishment</p>
</div>

</body>
</html>