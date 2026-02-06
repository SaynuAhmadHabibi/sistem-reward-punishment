<?php
/**
 * Script Instalasi Database Sistem Reward & Punishment
 * File ini akan mengimpor database.sql ke MySQL secara otomatis
 */

// Konfigurasi database - sesuaikan dengan setting MySQL Anda
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_reward_punishment');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Instalasi Database Sistem Reward & Punishment</h1>";
echo "<pre>";

// Cek koneksi ke MySQL
echo "1. Mengecek koneksi ke MySQL...\n";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error . "\n");
}
echo "✅ Koneksi ke MySQL berhasil\n\n";

// Buat database jika belum ada
echo "2. Membuat database...\n";
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database '" . DB_NAME . "' berhasil dibuat atau sudah ada\n";
} else {
    die("❌ Error membuat database: " . $conn->error . "\n");
}

// Pilih database
$conn->select_db(DB_NAME);

// Baca file SQL
echo "\n3. Membaca file database.sql...\n";
$sql_file = __DIR__ . '/database.sql';

if (!file_exists($sql_file)) {
    die("❌ File database.sql tidak ditemukan di: " . $sql_file . "\n");
}

$sql_content = file_get_contents($sql_file);
echo "✅ File database.sql berhasil dibaca (" . strlen($sql_content) . " karakter)\n";

// Split SQL commands
echo "\n4. Memproses perintah SQL...\n";
$sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
$success_count = 0;
$error_count = 0;

foreach ($sql_commands as $command) {
    if (empty($command) || strpos($command, '--') === 0) {
        continue; // Skip empty lines and comments
    }

    // Skip USE database command
    if (stripos($command, 'USE ') === 0) {
        continue;
    }

    if ($conn->query($command) === TRUE) {
        $success_count++;
    } else {
        echo "❌ Error: " . $conn->error . "\n";
        echo "   Query: " . substr($command, 0, 100) . "...\n";
        $error_count++;
    }
}

echo "\n5. Ringkasan instalasi:\n";
echo "✅ Perintah SQL berhasil: " . $success_count . "\n";
if ($error_count > 0) {
    echo "❌ Perintah SQL gagal: " . $error_count . "\n";
}

// Verifikasi instalasi
echo "\n6. Verifikasi instalasi...\n";
$tables = ['users', 'departemen', 'jabatan', 'karyawan', 'penilaian', 'topsis_results', 'reward', 'punishment'];
$tables_exist = 0;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $tables_exist++;
        echo "✅ Tabel '$table' ada\n";
    } else {
        echo "❌ Tabel '$table' tidak ditemukan\n";
    }
}

echo "\n7. Mengecek data sample...\n";
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$row = $result->fetch_assoc();
echo "✅ Total users: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM karyawan");
$row = $result->fetch_assoc();
echo "✅ Total karyawan: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM departemen");
$row = $result->fetch_assoc();
echo "✅ Total departemen: " . $row['total'] . "\n";

$conn->close();

echo "\n" . str_repeat("=", 50) . "\n";
if ($error_count == 0 && $tables_exist == count($tables)) {
    echo "🎉 INSTALASI BERHASIL!\n";
    echo "Database sistem reward & punishment telah terinstall dengan lengkap.\n\n";
    echo "Langkah selanjutnya:\n";
    echo "1. Akses sistem di: http://localhost/sistem-reward-punishment\n";
    echo "2. Login dengan:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";
    echo "3. Hapus file install.php untuk keamanan\n";
} else {
    echo "⚠️  INSTALASI SELESAI DENGAN PERINGATAN\n";
    echo "Periksa pesan error di atas dan perbaiki jika diperlukan.\n";
}
echo str_repeat("=", 50) . "\n";

echo "</pre>";
?>