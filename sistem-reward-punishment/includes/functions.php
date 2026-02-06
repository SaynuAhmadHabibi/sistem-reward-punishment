<?php
/**
 * Functions Helper File
 * Berisi fungsi-fungsi helper untuk sistem reward punishment
 */

// Pastikan session sudah start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include konfigurasi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/topsis.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Redirect ke halaman tertentu
 * 
 * @param string $url URL tujuan
 * @param string|null $message Pesan sukses (opsional)
 * @param string|null $error Pesan error (opsional)
 */
function redirect($url, $message = null, $error = null) {
    if ($message !== null) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'text' => $message
        ];
    }
    
    if ($error !== null) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'text' => $error
        ];
    }
    
    header("Location: " . $url);
    exit();
}

/**
 * Cek apakah user sudah login
 * 
 * @return bool True jika sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek role user
 * 
 * @param string $role Role yang dicek
 * @return bool True jika user memiliki role tersebut
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Cek apakah user adalah admin
 * 
 * @return bool True jika user adalah admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Cek apakah user adalah manager
 * 
 * @return bool True jika user adalah manager
 */
function isManager() {
    return hasRole('manager');
}

/**
 * Cek apakah user adalah user biasa
 *
 * @return bool True jika user adalah user biasa
 */
function isRegularUser() {
    return hasRole('user');
}











/**
 * Cek permission spesifik
 *
 * @param string $permission Nama permission
 * @return bool True jika user memiliki permission tersebut
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }

    // Admin dan HRD Admin memiliki semua permission
    if (isAdmin() || isHRDAdmin()) {
        return true;
    }

    // Direktur hanya bisa read
    if (isDirektur() && $permission === 'read') {
        return true;
    }

    // Cek permission dari session
    if (isset($_SESSION['user_permissions']) && is_array($_SESSION['user_permissions'])) {
        return isset($_SESSION['user_permissions'][$permission]) && $_SESSION['user_permissions'][$permission] === true;
    }

    return false;
}









/**
 * Cek permission untuk akses halaman
 * 
 * @param array|string $allowed_roles Role yang diizinkan
 * @param bool $redirect Redirect jika tidak diizinkan
 * @return bool True jika memiliki permission
 */
function checkPermission($allowed_roles = [], $redirect = true) {
    if (!isLoggedIn()) {
        if ($redirect) {
            redirect('../login.php', null, 'Silakan login terlebih dahulu!');
        }
        return false;
    }
    
    if (empty($allowed_roles)) {
        return true;
    }
    
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    $has_permission = in_array($_SESSION['user_role'], $allowed_roles);
    
    if (!$has_permission && $redirect) {
        redirect('../dashboard.php', null, 'Akses ditolak! Anda tidak memiliki permission untuk mengakses halaman ini.');
    }
    
    return $has_permission;
}

/**
 * Sanitize input untuk keamanan
 * 
 * @param mixed $input Input yang akan disanitasi
 * @param bool $strip_tags Hapus tag HTML
 * @param bool $trim Hapus whitespace
 * @return mixed Input yang sudah disanitasi
 */
function sanitize($input, $strip_tags = true, $trim = true) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    
    if ($input === null) {
        return null;
    }
    
    // Convert ke string
    $input = (string) $input;
    
    // Strip tags jika diperlukan
    if ($strip_tags) {
        $input = strip_tags($input);
    }
    
    // Trim whitespace
    if ($trim) {
        $input = trim($input);
    }
    
    // Escape special characters
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $input = mysqli_real_escape_string($conn, $input);
    }
    
    return $input;
}

/**
 * Format tanggal Indonesia
 * 
 * @param string $date Tanggal yang akan diformat
 * @param string $format Format output
 * @return string Tanggal yang sudah diformat
 */
function formatDate($date, $format = 'd-m-Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

/**
 * Format tanggal lengkap Indonesia
 * 
 * @param string $date Tanggal yang akan diformat
 * @param bool $with_time Tampilkan waktu
 * @return string Tanggal format Indonesia
 */
function formatDateIndonesia($date, $with_time = false) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $hari_index = date('w', $timestamp);
    $tanggal = date('j', $timestamp);
    $bulan_index = date('n', $timestamp) - 1;
    $tahun = date('Y', $timestamp);
    
    $formatted = $hari[$hari_index] . ', ' . $tanggal . ' ' . $bulan[$bulan_index] . ' ' . $tahun;
    
    if ($with_time) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    
    return $formatted;
}

/**
 * Format angka dengan pemisah ribuan
 * 
 * @param mixed $number Angka yang akan diformat
 * @param int $decimals Jumlah desimal
 * @return string Angka yang sudah diformat
 */
function formatNumber($number, $decimals = 0) {
    if (!is_numeric($number)) {
        return '0';
    }
    
    return number_format($number, $decimals, ',', '.');
}

/**
 * Format mata uang Rupiah
 * 
 * @param mixed $amount Jumlah uang
 * @return string Format Rupiah
 */
function formatCurrency($amount) {
    if (!is_numeric($amount)) {
        return 'Rp 0';
    }
    
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Hitung usia berdasarkan tanggal lahir
 * 
 * @param string $birthdate Tanggal lahir
 * @return int Usia dalam tahun
 */
function calculateAge($birthdate) {
    if (empty($birthdate) || $birthdate === '0000-00-00') {
        return 0;
    }
    
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    
    if ($birth > $today) {
        return 0;
    }
    
    $age = $today->diff($birth);
    return $age->y;
}

/**
 * Generate random string
 * 
 * @param int $length Panjang string
 * @param string $charset Kumpulan karakter
 * @return string Random string
 */
function generateRandomString($length = 10, $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $result = '';
    $charset_length = strlen($charset);
    
    for ($i = 0; $i < $length; $i++) {
        $result .= $charset[random_int(0, $charset_length - 1)];
    }
    
    return $result;
}

/**
 * Generate NIK otomatis
 * 
 * @param string $department_code Kode departemen
 * @return string NIK baru
 */
function generateNIK($department_code = 'EMP') {
    $conn = connectDB();
    
    // Get last NIK for this department
    $sql = "SELECT nik FROM karyawan WHERE nik LIKE ? ORDER BY nik DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $search_pattern = $department_code . '%';
    mysqli_stmt_bind_param($stmt, 's', $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $last_nik = '';
    if ($row = mysqli_fetch_assoc($result)) {
        $last_nik = $row['nik'];
    }
    
    // Generate new NIK
    if (empty($last_nik)) {
        $new_number = 1;
    } else {
        $last_number = (int) substr($last_nik, strlen($department_code));
        $new_number = $last_number + 1;
    }
    
    return $department_code . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Upload file dengan validasi
 * 
 * @param array $file File dari $_FILES
 * @param string $target_dir Direktori tujuan
 * @param array $allowed_types Tipe file yang diizinkan
 * @param int $max_size Ukuran maksimal (dalam bytes)
 * @return array|false Hasil upload atau false jika gagal
 */
function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 2097152) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File terlalu besar. Maksimal ' . ($max_size / 1024 / 1024) . ' MB'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan. Hanya ' . implode(', ', $allowed_types)];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_file = rtrim($target_dir, '/') . '/' . $filename;
    
    // Create directory if not exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $target_file,
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $target_file)
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

/**
 * Get flash message dari session
 * 
 * @return string|null Flash message atau null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Display flash message
 * 
 * @return string HTML untuk flash message
 */
function displayFlashMessage() {
    $message = getFlashMessage();
    
    if (!$message) {
        return '';
    }
    
    $type = $message['type'] ?? 'info';
    $text = $message['text'] ?? '';
    
    $icons = [
        'success' => 'check-circle',
        'danger' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    
    return '
    <div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        <i class="fas fa-' . $icon . ' me-2"></i>
        ' . htmlspecialchars($text) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

/**
 * Get data karyawan
 * 
 * @param int|null $id ID karyawan (optional)
 * @param bool $active_only Hanya karyawan aktif
 * @return array Data karyawan
 */
function getKaryawan($id = null, $active_only = true) {
    $conn = connectDB();
    
    $sql = "SELECT k.*, d.nama as departemen_nama, j.nama as jabatan_nama
            FROM karyawan k
            LEFT JOIN departemen d ON k.departemen_id = d.id
            LEFT JOIN jabatan j ON k.jabatan_id = j.id
            WHERE 1=1";
    
    if ($active_only) {
        $sql .= " AND k.status = 'aktif'";
    }
    
    if ($id !== null) {
        $sql .= " AND k.id = ?";
        $result = getSingleRow($sql, [$id]);
    } else {
        $sql .= " ORDER BY k.nama ASC";
        $result = getMultipleRows($sql);
    }
    
    return $result;
}

/**
 * Get data penilaian
 * 
 * @param int|null $id ID penilaian (optional)
 * @param string $period Periode (Y-m)
 * @return array Data penilaian
 */
function getPenilaian($id = null, $period = null) {
    $conn = connectDB();
    
    $sql = "SELECT p.*, k.nama as karyawan_nama, k.nik, u.nama as penilai_nama
            FROM penilaian p
            LEFT JOIN karyawan k ON p.karyawan_id = k.id
            LEFT JOIN users u ON p.penilai_id = u.id
            WHERE 1=1";
    
    if ($period !== null) {
        $sql .= " AND DATE_FORMAT(p.tanggal_penilaian, '%Y-%m') = ?";
        $params = [$period];
    }
    
    if ($id !== null) {
        $sql .= " AND p.id = ?";
        $params = isset($params) ? array_merge($params, [$id]) : [$id];
        $result = getSingleRow($sql, $params ?? []);
    } else {
        $sql .= " ORDER BY p.tanggal_penilaian DESC";
        $result = getMultipleRows($sql, $params ?? []);
    }
    
    return $result;
}

/**
 * Get data reward
 * 
 * @param int|null $id ID reward (optional)
 * @param string $period Periode (Y-m)
 * @return array Data reward
 */
function getReward($id = null, $period = null) {
    $conn = connectDB();
    
    $sql = "SELECT r.*, k.nama as karyawan_nama, k.nik, u.nama as diberikan_oleh_nama
            FROM reward r
            LEFT JOIN karyawan k ON r.karyawan_id = k.id
            LEFT JOIN users u ON r.diberikan_oleh = u.id
            WHERE 1=1";
    
    if ($period !== null) {
        $sql .= " AND DATE_FORMAT(r.tanggal, '%Y-%m') = ?";
        $params = [$period];
    }
    
    if ($id !== null) {
        $sql .= " AND r.id = ?";
        $params = isset($params) ? array_merge($params, [$id]) : [$id];
        $result = getSingleRow($sql, $params ?? []);
    } else {
        $sql .= " ORDER BY r.tanggal DESC";
        $result = getMultipleRows($sql, $params ?? []);
    }
    
    return $result;
}

/**
 * Get data punishment
 * 
 * @param int|null $id ID punishment (optional)
 * @param string $period Periode (Y-m)
 * @return array Data punishment
 */
function getPunishment($id = null, $period = null) {
    $conn = connectDB();
    
    $sql = "SELECT p.*, k.nama as karyawan_nama, k.nik, u.nama as diberikan_oleh_nama
            FROM punishment p
            LEFT JOIN karyawan k ON p.karyawan_id = k.id
            LEFT JOIN users u ON p.diberikan_oleh = u.id
            WHERE 1=1";
    
    if ($period !== null) {
        $sql .= " AND DATE_FORMAT(p.tanggal, '%Y-%m') = ?";
        $params = [$period];
    }
    
    if ($id !== null) {
        $sql .= " AND p.id = ?";
        $params = isset($params) ? array_merge($params, [$id]) : [$id];
        $result = getSingleRow($sql, $params ?? []);
    } else {
        $sql .= " ORDER BY p.tanggal DESC";
        $result = getMultipleRows($sql, $params ?? []);
    }
    
    return $result;
}

/**
 * Get data users
 * 
 * @param int|null $id ID user (optional)
 * @return array Data users
 */
function getUsers($id = null) {
    $sql = "SELECT * FROM users WHERE 1=1";
    
    if ($id !== null) {
        $sql .= " AND id = ?";
        $result = getSingleRow($sql, [$id]);
    } else {
        $sql .= " ORDER BY nama ASC";
        $result = getMultipleRows($sql);
    }
    
    return $result;
}

/**
 * Get data departemen
 * 
 * @param int|null $id ID departemen (optional)
 * @return array Data departemen
 */
function getDepartemen($id = null) {
    $sql = "SELECT * FROM departemen WHERE 1=1";
    
    if ($id !== null) {
        $sql .= " AND id = ?";
        $result = getSingleRow($sql, [$id]);
    } else {
        $sql .= " ORDER BY nama ASC";
        $result = getMultipleRows($sql);
    }
    
    return $result;
}

/**
 * Get data jabatan
 * 
 * @param int|null $id ID jabatan (optional)
 * @return array Data jabatan
 */
function getJabatan($id = null) {
    $sql = "SELECT * FROM jabatan WHERE 1=1";
    
    if ($id !== null) {
        $sql .= " AND id = ?";
        $result = getSingleRow($sql, [$id]);
    } else {
        $sql .= " ORDER BY nama ASC";
        $result = getMultipleRows($sql);
    }
    
    return $result;
}

/**
 * Get dashboard statistics
 * 
 * @return array Dashboard statistics
 */
function getDashboardStats() {
    $conn = connectDB();
    
    $stats = [];
    
    // Total karyawan
    $stats['total_karyawan'] = getRowCount("SELECT id FROM karyawan WHERE status = 'aktif'");
    
    // Total penilaian bulan ini
    $stats['total_penilaian'] = getRowCount("SELECT id FROM penilaian WHERE MONTH(tanggal_penilaian) = MONTH(CURRENT_DATE())");
    
    // Total reward bulan ini
    $stats['total_reward'] = getRowCount("SELECT id FROM reward WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())");
    
    // Total punishment bulan ini
    $stats['total_punishment'] = getRowCount("SELECT id FROM punishment WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())");
    
    // Top performers
    $stats['top_performers'] = getMultipleRows("
        SELECT k.nama, k.nik, AVG(p.kinerja) as avg_kinerja
        FROM penilaian p
        LEFT JOIN karyawan k ON p.karyawan_id = k.id
        WHERE MONTH(p.tanggal_penilaian) = MONTH(CURRENT_DATE())
        GROUP BY p.karyawan_id
        ORDER BY avg_kinerja DESC
        LIMIT 5
    ");
    
    // Recent activities
    $stats['recent_activities'] = getMultipleRows("
        (SELECT 'reward' as type, tanggal as activity_date, 
                CONCAT('Reward untuk karyawan ', k.nama) as description,
                r.topsis_score as score
         FROM reward r
         LEFT JOIN karyawan k ON r.karyawan_id = k.id
         ORDER BY r.tanggal DESC
         LIMIT 5)
        UNION ALL
        (SELECT 'punishment' as type, tanggal as activity_date,
                CONCAT('Punishment untuk karyawan ', k.nama) as description,
                p.topsis_score as score
         FROM punishment p
         LEFT JOIN karyawan k ON p.karyawan_id = k.id
         ORDER BY p.tanggal DESC
         LIMIT 5)
        ORDER BY activity_date DESC
        LIMIT 10
    ");
    
    return $stats;
}

/**
 * Get criteria names from TOPSIS config
 *
 * @return array Array of criteria names
 */
function getCriteriaNames() {
    $criteria = TOPSIS_CRITERIA;
    return array_keys($criteria);
}

/**
 * Get criterion information
 *
 * @param string $criterion Criterion name
 * @return array|null Criterion info or null if not found
 */
function getCriterionInfo($criterion) {
    $criteria = TOPSIS_CRITERIA;
    return isset($criteria[$criterion]) ? $criteria[$criterion] : null;
}

/**
 * Validate penilaian data
 *
 * @param array $data Data penilaian
 * @return array Validation result
 */
function validatePenilaian($data) {
    $errors = [];
    $criteria = getCriteriaNames();

    foreach ($criteria as $criterion) {
        if (!isset($data[$criterion]) || $data[$criterion] === '') {
            $errors[$criterion] = "Nilai $criterion harus diisi";
            continue;
        }

        $value = floatval($data[$criterion]);
        $criterion_info = getCriterionInfo($criterion);

        if (isset($criterion_info['min']) && $value < $criterion_info['min']) {
            $errors[$criterion] = "Nilai $criterion tidak boleh kurang dari " . $criterion_info['min'];
        }

        if (isset($criterion_info['max']) && $value > $criterion_info['max']) {
            $errors[$criterion] = "Nilai $criterion tidak boleh lebih dari " . $criterion_info['max'];
        }
    }

    if (!isset($data['karyawan_id']) || empty($data['karyawan_id'])) {
        $errors['karyawan_id'] = "Karyawan harus dipilih";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get bulan dan tahun untuk filter
 * 
 * @param int $months_back Jumlah bulan ke belakang
 * @return array List bulan-tahun
 */
function getMonthYearOptions($months_back = 12) {
    $options = [];
    $current = new DateTime();
    
    for ($i = 0; $i < $months_back; $i++) {
        $date = clone $current;
        $date->modify("-$i months");
        $value = $date->format('Y-m');
        $label = $date->format('F Y');
        $options[$value] = $label;
    }
    
    return $options;
}

/**
 * Get current user info
 * 
 * @return array User info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'nama' => $_SESSION['nama'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Log activity ke database
 * 
 * @param string $action Aksi yang dilakukan
 * @param string $description Deskripsi aktivitas
 * @param int|null $user_id ID user (optional, default current user)
 * @return bool True jika berhasil
 */
function logActivity($action, $description, $user_id = null) {
    if ($user_id === null && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    $conn = connectDB();
    $sql = "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'issss', $user_id, $action, $description, $ip_address, $user_agent);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Encrypt data sederhana (untuk data non-sensitive)
 * 
 * @param string $data Data yang akan dienkripsi
 * @param string $key Kunci enkripsi
 * @return string Data terenkripsi
 */
function simpleEncrypt($data, $key = 'sistem_reward_punishment') {
    $result = '';
    $key_length = strlen($key);
    
    for ($i = 0; $i < strlen($data); $i++) {
        $char = $data[$i];
        $key_char = $key[$i % $key_length];
        $result .= chr(ord($char) + ord($key_char));
    }
    
    return base64_encode($result);
}

/**
 * Decrypt data
 * 
 * @param string $data Data terenkripsi
 * @param string $key Kunci enkripsi
 * @return string Data terdekripsi
 */
function simpleDecrypt($data, $key = 'sistem_reward_punishment') {
    $data = base64_decode($data);
    $result = '';
    $key_length = strlen($key);
    
    for ($i = 0; $i < strlen($data); $i++) {
        $char = $data[$i];
        $key_char = $key[$i % $key_length];
        $result .= chr(ord($char) - ord($key_char));
    }
    
    return $result;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if request is AJAX
 * 
 * @return bool True jika request AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param array $data Data response
 * @param int $status_code HTTP status code
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token dari form
 * @return bool True jika token valid
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Initialize CSRF token jika belum ada
if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>