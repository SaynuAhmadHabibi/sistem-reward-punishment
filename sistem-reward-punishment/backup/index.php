<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Admin dan HRD Admin bisa akses
if (!isAdmin() && !isHRDAdmin()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin dan HRD admin yang dapat mengakses halaman backup.';
    redirect('../dashboard.php');
}

$conn = connectDB();
$page_title = "Backup Database";

// Variabel untuk pesan
$success = '';
$error = '';

// Proses backup database
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup'])) {
    try {
        $backup_type = sanitize($_POST['backup_type']);
        $tables = isset($_POST['tables']) ? $_POST['tables'] : [];
        
        // Nama file backup
        $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '_' . $backup_type;
        $filename = $backup_name . '.sql';
        $backup_path = __DIR__ . '/backups/' . $filename;
        
        // Buat folder backups jika belum ada
        if (!is_dir(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0777, true);
        }
        
        // Handle backup berdasarkan tipe
        if ($backup_type == 'full') {
            // Backup seluruh database
            $sql_content = backupFullDatabase();
        } elseif ($backup_type == 'partial' && !empty($tables)) {
            // Backup tabel tertentu
            $sql_content = backupPartialDatabase($tables);
        } else {
            throw new Exception('Pilih tabel untuk backup partial!');
        }
        
        // Simpan ke file
        if (file_put_contents($backup_path, $sql_content)) {
            // Log backup ke database
            $filesize = filesize($backup_path);
            $user_id = $_SESSION['user_id'];
            $log_sql = "INSERT INTO backup_log (filename, size, backup_type, created_by) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, 'sisi', $filename, $filesize, $backup_type, $user_id);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            $success = "Backup berhasil dibuat: " . $filename . " (" . formatSize($filesize) . ")";
        } else {
            throw new Exception('Gagal menyimpan file backup!');
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses restore database
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore'])) {
    try {
        $backup_file = sanitize($_POST['backup_file']);
        $backup_path = __DIR__ . '/backups/' . $backup_file;
        
        if (!file_exists($backup_path)) {
            throw new Exception('File backup tidak ditemukan!');
        }
        
        // Ekstrak dan eksekusi SQL dari file
        $sql_content = file_get_contents($backup_path);
        $queries = explode(';', $sql_content);
        
        // Nonaktifkan foreign key checks sementara
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                mysqli_query($conn, $query);
            }
        }
        
        // Aktifkan kembali foreign key checks
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        
        $success = "Restore berhasil dari file: " . $backup_file;
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses download backup
if (isset($_GET['download'])) {
    $filename = sanitize($_GET['download']);
    $filepath = __DIR__ . '/backups/' . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $error = "File tidak ditemukan!";
    }
}

// Proses hapus backup
if (isset($_GET['delete'])) {
    $filename = sanitize($_GET['delete']);
    $filepath = __DIR__ . '/backups/' . $filename;
    
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            // Hapus log dari database
            $delete_sql = "DELETE FROM backup_log WHERE filename = '$filename'";
            mysqli_query($conn, $delete_sql);
            $success = "File backup berhasil dihapus: " . $filename;
        } else {
            $error = "Gagal menghapus file backup!";
        }
    } else {
        $error = "File tidak ditemukan!";
    }
    
    redirect('index.php');
}

// Fungsi untuk backup seluruh database
function backupFullDatabase() {
    global $conn;
    
    $sql_content = "-- Backup Database Sistem Reward & Punishment\n";
    $sql_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Generated by: " . $_SESSION['nama'] . "\n\n";
    
    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    foreach ($tables as $table) {
        // Drop table jika sudah ada
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table structure
        $create_table = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($create_table);
        $sql_content .= $row[1] . ";\n\n";
        
        // Insert data
        $result = mysqli_query($conn, "SELECT * FROM `$table`");
        if (mysqli_num_rows($result) > 0) {
            $sql_content .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $values = [];
                foreach ($row as $value) {
                    // Use addslashes for proper escaping instead of deprecated mysqli_real_escape_string
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            $sql_content .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    return $sql_content;
}

// Fungsi untuk backup sebagian tabel
function backupPartialDatabase($selected_tables) {
    global $conn;
    
    $sql_content = "-- Partial Backup Database Sistem Reward & Punishment\n";
    $sql_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Generated by: " . $_SESSION['nama'] . "\n";
    $sql_content .= "-- Tables: " . implode(', ', $selected_tables) . "\n\n";
    
    foreach ($selected_tables as $table) {
        // Drop table jika sudah ada
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table structure
        $create_table = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($create_table);
        $sql_content .= $row[1] . ";\n\n";
        
        // Insert data
        $result = mysqli_query($conn, "SELECT * FROM `$table`");
        if (mysqli_num_rows($result) > 0) {
            $sql_content .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $values = [];
                foreach ($row as $value) {
                    // Use addslashes for proper escaping instead of deprecated mysqli_real_escape_string
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            $sql_content .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    return $sql_content;
}

// Fungsi untuk format ukuran file
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Ambil daftar tabel database
$tables_list = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables_list[] = $row[0];
}

// Ambil daftar backup dari database
$backup_files = [];
$result = mysqli_query($conn, "SELECT bl.*, u.nama as created_by_name 
                              FROM backup_log bl 
                              LEFT JOIN users u ON bl.created_by = u.id 
                              ORDER BY bl.created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $backup_files[] = $row;
}

// Ambil file backup dari folder
$folder_backups = [];
if (is_dir(__DIR__ . '/backups')) {
    $files = scandir(__DIR__ . '/backups', SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = __DIR__ . '/backups/' . $file;
            $folder_backups[$file] = [
                'size' => formatSize(filesize($filepath)),
                'modified' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
}

// Informasi database
$db_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM karyawan) as total_karyawan,
    (SELECT COUNT(*) FROM penilaian) as total_penilaian,
    (SELECT COUNT(*) FROM reward) as total_reward,
    (SELECT COUNT(*) FROM punishment) as total_punishment,
    (SELECT SUM(size) FROM backup_log) as total_backup_size,
    (SELECT COUNT(*) FROM backup_log) as total_backups"));
// Cek permission folder backup
$backup_folder = __DIR__ . '/backups';
$folder_writable = is_writable($backup_folder) || (!is_dir($backup_folder) && is_writable(__DIR__));
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>Backup & Restore Database
                    </h5>
                    <span class="badge bg-info">
                        <i class="fas fa-hdd me-1"></i> 
                        <?php echo DB_NAME; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pesan Status -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Informasi Database -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi Database
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_users']; ?></h4>
                                    <small>Users</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_karyawan']; ?></h4>
                                    <small>Karyawan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_penilaian']; ?></h4>
                                    <small>Penilaian</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_reward']; ?></h4>
                                    <small>Reward</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_punishment']; ?></h4>
                                    <small>Punishment</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $db_info['total_backups']; ?></h4>
                                    <small>Backups</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert <?php echo $folder_writable ? 'alert-success' : 'alert-danger'; ?>">
                        <i class="fas fa-folder me-2"></i>
                        Status Folder Backup: 
                        <strong><?php echo $folder_writable ? 'WRITABLE' : 'NOT WRITABLE'; ?></strong>
                        <br>
                        <small>
                            <?php echo $backup_folder; ?>
                        </small>
                        <?php if (!$folder_writable): ?>
                            <br><small class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Ubah permission folder backup menjadi writable (chmod 777)
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup dan Restore Section -->
    <div class="row">
        <!-- Backup Section -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-save me-2"></i>Buat Backup Database
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="backupForm">
                        <div class="mb-3">
                            <label class="form-label">Jenis Backup</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="backup_type" 
                                       id="full_backup" value="full" checked 
                                       onchange="toggleTablesSelection()">
                                <label class="form-check-label" for="full_backup">
                                    <strong>Full Backup</strong>
                                    <small class="text-muted d-block">Backup seluruh database</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="backup_type" 
                                       id="partial_backup" value="partial"
                                       onchange="toggleTablesSelection()">
                                <label class="form-check-label" for="partial_backup">
                                    <strong>Partial Backup</strong>
                                    <small class="text-muted d-block">Pilih tabel tertentu</small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="tablesSelection" style="display: none;">
                            <label class="form-label">Pilih Tabel</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($tables_list as $table): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="tables[]" value="<?php echo $table; ?>" 
                                               id="table_<?php echo $table; ?>">
                                        <label class="form-check-label" for="table_<?php echo $table; ?>">
                                            <?php echo $table; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Hold Ctrl untuk memilih multiple tabel</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama File Backup</label>
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       value="backup_<?php echo date('Y-m-d_H-i-s'); ?>"
                                       readonly>
                                <span class="input-group-text">.sql</span>
                            </div>
                            <small class="text-muted">Nama file akan ditambah timestamp otomatis</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Backup akan disimpan di: <code><?php echo $backup_folder; ?></code>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="backup" class="btn btn-success btn-lg">
                                <i class="fas fa-database me-2"></i>Buat Backup Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Restore Database
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="restoreForm" onsubmit="return confirmRestore()">
                        <div class="mb-3">
                            <label class="form-label">Pilih File Backup</label>
                            <select class="form-select" name="backup_file" required>
                                <option value="">-- Pilih File Backup --</option>
                                <?php foreach ($folder_backups as $file => $info): ?>
                                    <option value="<?php echo $file; ?>">
                                        <?php echo $file; ?> 
                                        (<?php echo $info['size']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($folder_backups)): ?>
                                <small class="text-danger">Tidak ada file backup yang tersedia</small>
                            <?php endif; ?>
                        </div>

                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>PERINGATAN!</strong> Restore akan mengganti seluruh data database dengan data dari backup!
                            <ul class="mt-2 mb-0">
                                <li>Pastikan Anda telah membuat backup terbaru</li>
                                <li>Restore tidak dapat dibatalkan</li>
                                <li>Semua data setelah backup akan hilang</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="restore" class="btn btn-warning btn-lg"
                                    <?php echo empty($folder_backups) ? 'disabled' : ''; ?>>
                                <i class="fas fa-redo me-2"></i>Restore Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Backup Files -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-archive me-2"></i>Daftar File Backup
                    </h6>
                    <span class="badge bg-primary">
                        Total: <?php echo count($backup_files); ?> file
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($backup_files) || !empty($folder_backups)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama File</th>
                                        <th>Jenis</th>
                                        <th>Ukuran</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Tanggal Backup</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($backup_files as $backup): ?>
                                        <?php
                                        $file_exists = isset($folder_backups[$backup['filename']]);
                                        $file_info = $file_exists ? $folder_backups[$backup['filename']] : null;
                                        ?>
                                        <tr class="<?php echo !$file_exists ? 'table-danger' : ''; ?>">
                                            <td><?php echo $counter++; ?></td>
                                            <td>
                                                <i class="fas fa-file-code me-2 text-primary"></i>
                                                <strong><?php echo $backup['filename']; ?></strong>
                                                <?php if (!$file_exists): ?>
                                                    <br><small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        File tidak ditemukan di server
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $backup['backup_type'] == 'full' ? 'success' : 'info'; ?>">
                                                    <?php echo strtoupper($backup['backup_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo formatSize($backup['size']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo $backup['created_by_name']; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($backup['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($file_exists): ?>
                                                        <a href="?download=<?php echo urlencode($backup['filename']); ?>" 
                                                           class="btn btn-sm btn-success"
                                                           data-bs-toggle="tooltip" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <a href="#" 
                                                           class="btn btn-sm btn-info"
                                                           onclick="showBackupInfo('<?php echo $backup['filename']; ?>')"
                                                           data-bs-toggle="tooltip" title="Info">
                                                            <i class="fas fa-info-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($file_exists && isAdmin()): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                onclick="confirmDeleteBackup('<?php echo $backup['filename']; ?>')"
                                                                data-bs-toggle="tooltip" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Tampilkan file yang ada di folder tapi tidak ada di database -->
                                    <?php foreach ($folder_backups as $file => $info): ?>
                                        <?php 
                                        $in_database = false;
                                        foreach ($backup_files as $backup) {
                                            if ($backup['filename'] == $file) {
                                                $in_database = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if (!$in_database): ?>
                                            <tr class="table-warning">
                                                <td><?php echo $counter++; ?></td>
                                                <td>
                                                    <i class="fas fa-file-code me-2 text-primary"></i>
                                                    <strong><?php echo $file; ?></strong>
                                                    <br><small class="text-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Tidak tercatat di database
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">UNKNOWN</span>
                                                </td>
                                                <td><?php echo $info['size']; ?></td>
                                                <td>-</td>
                                                <td><?php echo $info['modified']; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="?download=<?php echo urlencode($file); ?>" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php if (isAdmin()): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="confirmDeleteBackup('<?php echo $file; ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                            <h4>Belum ada backup</h4>
                            <p class="text-muted">Buat backup pertama Anda untuk mengamankan data.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Tips Backup & Restore
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <i class="fas fa-calendar-alt me-2"></i>Jadwal Rutin
                                    </h5>
                                    <p class="card-text">
                                        Buat backup secara rutin (minimal seminggu sekali) untuk menjaga keamanan data.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <h5 class="card-title text-success">
                                        <i class="fas fa-cloud me-2"></i>Simpan di Lokasi Berbeda
                                    </h5>
                                    <p class="card-text">
                                        Simpan file backup di lokasi yang berbeda dari server untuk keamanan ekstra.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-body">
                                    <h5 class="card-title text-warning">
                                        <i class="fas fa-test-tube me-2"></i>Test Restore
                                    </h5>
                                    <p class="card-text">
                                        Periodically test restore di lingkungan test untuk memastikan backup berfungsi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Info Backup -->
<div class="modal fade" id="backupInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Backup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="backupInfoContent">
                    Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle tabel selection berdasarkan jenis backup
function toggleTablesSelection() {
    const partialBackup = document.getElementById('partial_backup');
    const tablesSelection = document.getElementById('tablesSelection');
    
    if (partialBackup.checked) {
        tablesSelection.style.display = 'block';
    } else {
        tablesSelection.style.display = 'none';
        // Uncheck semua checkbox
        document.querySelectorAll('input[name="tables[]"]').forEach(cb => {
            cb.checked = false;
        });
    }
}

// Konfirmasi sebelum restore
function confirmRestore() {
    return confirm('⚠️ PERINGATAN!\n\nRestore akan mengganti seluruh data database dengan data dari backup.\nData saat ini akan hilang dan tidak dapat dikembalikan.\n\nLanjutkan?');
}

// Konfirmasi sebelum hapus backup
function confirmDeleteBackup(filename) {
    if (confirm(`Hapus file backup: ${filename}?\n\nFile akan dihapus permanen.`)) {
        window.location.href = `index.php?delete=${encodeURIComponent(filename)}`;
    }
}

// Tampilkan info backup
function showBackupInfo(filename) {
    fetch(`backup_info.php?file=${encodeURIComponent(filename)}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('backupInfoContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('backupInfoModal')).show();
        })
        .catch(error => {
            document.getElementById('backupInfoContent').innerHTML = 
                `<div class="alert alert-danger">Error: ${error.message}</div>`;
            new bootstrap.Modal(document.getElementById('backupInfoModal')).show();
        });
}

// Inisialisasi DataTables
$(document).ready(function() {
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        responsive: true,
        order: [[5, 'desc']],
        columnDefs: [
            {
                targets: [6],
                orderable: false,
                searchable: false
            }
        ]
    });
    
    // Auto-check semua tabel untuk partial backup
    $('#partial_backup').on('change', function() {
        if (this.checked) {
            setTimeout(() => {
                $('input[name="tables[]"]').prop('checked', true);
            }, 100);
        }
    });
    
    // Validasi form backup
    $('#backupForm').on('submit', function(e) {
        const backupType = $('input[name="backup_type"]:checked').val();
        const tablesSelected = $('input[name="tables[]"]:checked').length;
        
        if (backupType === 'partial' && tablesSelected === 0) {
            e.preventDefault();
            showToast('Error', 'Pilih minimal satu tabel untuk partial backup!', 'danger');
            return false;
        }
        
        return true;
    });
});

// Toast notification
function showToast(title, message, type = 'info') {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    $('.toast-container').remove();
    const container = $('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
    container.append(toast);
    $('body').append(container);
    
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
}
</script>

<style>
.card.border-primary { border-left: 4px solid #007bff !important; }
.card.border-success { border-left: 4px solid #28a745 !important; }
.card.border-warning { border-left: 4px solid #ffc107 !important; }

.table-hover tbody tr:hover {
    transform: scale(1.005);
    transition: transform 0.2s;
}

.form-check-input:checked {
    background-color: var(--bs-success);
    border-color: var(--bs-success);
}

#tablesSelection .form-check {
    margin-bottom: 5px;
    padding: 5px;
    border-radius: 4px;
}

#tablesSelection .form-check:hover {
    background-color: rgba(0,123,255,0.1);
}

.toast-container {
    z-index: 9999;
}
</style>

<?php include '../includes/footer.php'; ?>