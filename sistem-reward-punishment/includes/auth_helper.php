<?php

/**
 * Helper functions untuk autentikasi dan autorisasi
 * Menggunakan bcrypt password hashing (lebih aman)
 * 
 * FIXED:
 * - Hapus nested function definition di dalam hasPermission()
 * - Fix session variable consistency: pakai $_SESSION['user_role']
 * - Pisahkan function definition di luar
 */

/**
 * Hash password menggunakan bcrypt
 *
 * @param string $password Password plain text
 * @return string Password yang sudah di-hash
 */
function hashPassword($password)
{
    // Menggunakan PASSWORD_BCRYPT dengan cost 12
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifikasi password dengan hash yang tersimpan
 *
 * @param string $inputPassword Password yang dimasukkan user
 * @param string $storedPassword Password hash yang tersimpan di database
 * @return bool True jika password cocok
 */
function verifyPassword($inputPassword, $storedPassword)
{
    // Gunakan password_verify untuk perbandingan yang aman
    return password_verify($inputPassword, $storedPassword);
}

/**
 * Check apakah password perlu untuk di-hash ulang (rehash)
 * Berguna untuk upgrade cost factor di masa depan
 *
 * @param string $password Password hash
 * @return bool True jika perlu rehash
 */
function needsRehash($password)
{
    return password_needs_rehash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}



/**
 * Cek apakah user adalah HRD Admin (full access)
 *
 * @return bool True jika user adalah HRD Admin
 */
function isHRDAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'hrd_admin';
}

/**
 * Cek apakah user adalah Direktur (read only)
 *
 * @return bool True jika user adalah Direktur
 */
function isDirektur()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'direktur';
}


/**
 * Cek apakah user adalah Admin atau HRD Admin
 *
 * @return bool True jika user adalah Admin atau HRD Admin
 */
function isAdminOrHRD()
{
    return isAdmin() || isHRDAdmin();
}


/**
 * Get user permissions dari database berdasarkan role
 *
 * @param string $role Role user
 * @return array Array permissions
 */
function getRolePermissions($role)
{
    $permissions = [
        'hrd_admin' => [
            'read' => true,
            'write' => true,
            'delete' => true,
            'manage_users' => true,
            'manage_departments' => true,
            'manage_positions' => true,
            'manage_employees' => true,
            'manage_evaluations' => true,
            'manage_rewards' => true,
            'manage_punishments' => true,
            'generate_reports' => true,
            'backup_data' => true,
            'karyawan_view' => true,
            'karyawan_create' => true,
            'karyawan_edit' => true,
            'karyawan_delete' => true,
            'penilaian_view' => true,
            'penilaian_create' => true,
            'penilaian_edit' => true,
            'penilaian_delete' => true,
            'reward_view' => true,
            'reward_create' => true,
            'reward_edit' => true,
            'reward_delete' => true,
            'punishment_view' => true,
            'punishment_create' => true,
            'punishment_edit' => true,
            'punishment_delete' => true,
            'laporan_view' => true,
            'laporan_export' => true,
            'laporan_cetak' => true,
        ],
        'admin' => [
            'read' => true,
            'write' => true,
            'delete' => false,
            'manage_users' => true,
            'manage_departments' => true,
            'manage_positions' => true,
            'manage_employees' => true,
            'manage_evaluations' => true,
            'manage_rewards' => true,
            'manage_punishments' => true,
            'generate_reports' => true,
            'backup_data' => true,
            'karyawan_view' => true,
            'karyawan_create' => true,
            'karyawan_edit' => true,
            'karyawan_delete' => true,
            'penilaian_view' => true,
            'penilaian_create' => true,
            'penilaian_edit' => true,
            'penilaian_delete' => true,
            'reward_view' => true,
            'reward_create' => true,
            'reward_edit' => true,
            'reward_delete' => true,
            'punishment_view' => true,
            'punishment_create' => true,
            'punishment_edit' => true,
            'punishment_delete' => true,
            'laporan_view' => true,
            'laporan_export' => true,
            'laporan_cetak' => true,
        ],
        'direktur' => [
            'read' => true,
            'write' => false,
            'delete' => false,
            'manage_users' => false,
            'manage_departments' => false,
            'manage_positions' => false,
            'manage_employees' => false,
            'manage_evaluations' => false,
            'manage_rewards' => false,
            'manage_punishments' => false,
            'generate_reports' => true,
            'backup_data' => false,
            'karyawan_view' => true,
            'karyawan_create' => false,
            'karyawan_edit' => false,
            'karyawan_delete' => false,
            'penilaian_view' => true,
            'penilaian_create' => false,
            'penilaian_edit' => false,
            'penilaian_delete' => false,
            'reward_view' => true,
            'reward_create' => false,
            'reward_edit' => false,
            'reward_delete' => false,
            'punishment_view' => true,
            'punishment_create' => false,
            'punishment_edit' => false,
            'punishment_delete' => false,
            'laporan_view' => true,
            'laporan_export' => true,
            'laporan_cetak' => true,
        ],
        'manager' => [
            'read' => true,
            'write' => true,
            'delete' => false,
            'manage_users' => false,
            'manage_departments' => false,
            'manage_positions' => false,
            'manage_employees' => true,
            'manage_evaluations' => true,
            'manage_rewards' => false,
            'manage_punishments' => false,
            'generate_reports' => true,
            'backup_data' => false,
            'karyawan_view' => true,
            'karyawan_create' => false,
            'karyawan_edit' => false,
            'karyawan_delete' => false,
            'penilaian_view' => true,
            'penilaian_create' => true,
            'penilaian_edit' => true,
            'penilaian_delete' => false,
            'reward_view' => true,
            'reward_create' => false,
            'reward_edit' => false,
            'reward_delete' => false,
            'punishment_view' => true,
            'punishment_create' => false,
            'punishment_edit' => false,
            'punishment_delete' => false,
            'laporan_view' => true,
            'laporan_export' => false,
            'laporan_cetak' => false,
        ],
        'user' => [
            'read' => true,
            'write' => false,
            'delete' => false,
            'manage_users' => false,
            'manage_departments' => false,
            'manage_positions' => false,
            'manage_employees' => false,
            'manage_evaluations' => false,
            'manage_rewards' => false,
            'manage_punishments' => false,
            'generate_reports' => false,
            'backup_data' => false,
            'karyawan_view' => false,
            'karyawan_create' => false,
            'karyawan_edit' => false,
            'karyawan_delete' => false,
            'penilaian_view' => false,
            'penilaian_create' => false,
            'penilaian_edit' => false,
            'penilaian_delete' => false,
            'reward_view' => false,
            'reward_create' => false,
            'reward_edit' => false,
            'reward_delete' => false,
            'punishment_view' => false,
            'punishment_create' => false,
            'punishment_edit' => false,
            'punishment_delete' => false,
            'laporan_view' => false,
            'laporan_export' => false,
            'laporan_cetak' => false,
        ]
    ];

    return isset($permissions[$role]) ? $permissions[$role] : $permissions['user'];
}

/**
 * Login user dengan verifikasi role dan permissions
 * Menggunakan bcrypt password verification
 *
 * @param string $username Username
 * @param string $password Password plain text
 * @return array|false Array user data jika login berhasil, false jika gagal
 */

function loginUser($username, $password)
{
    $conn = connectDB();

    $sql = "SELECT id, username, password, nama, email, role, permissions
            FROM users
            WHERE username = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log('Prepare failed: ' . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $username);

    if (!mysqli_stmt_execute($stmt)) {
        error_log('Execute failed: ' . mysqli_error($conn));
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password - support both bcrypt and base64 for backward compatibility
        $storedPassword = $user['password'];
        $passwordValid = false;

        // Check if password is bcrypt hash (starts with $2y$)
        if (password_verify($password, $storedPassword)) {
            $passwordValid = true;
        } elseif (base64_decode($storedPassword) === $password) {
            // Fallback to base64 for backward compatibility
            $passwordValid = true;
        }

        if (!$passwordValid) {
            mysqli_stmt_close($stmt);
            return false;
        }

        // =========================
        // SET SESSION - FIX: CONSISTENCY USER_ROLE
        // =========================
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_nama']     = $user['nama'];
        $_SESSION['user_email']    = $user['email'];
        $_SESSION['user_role']     = $user['role']; // ✅ FIX: USE user_role NOT role


        // Permissions
        if (!empty($user['permissions'])) {
            $_SESSION['user_permissions'] = json_decode($user['permissions'], true);
        } else {
            $_SESSION['user_permissions'] = getRolePermissions($user['role']);
        }

        // Update last login
        $updateLoginSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $updateLoginStmt = mysqli_prepare($conn, $updateLoginSql);
        mysqli_stmt_bind_param($updateLoginStmt, 'i', $user['id']);
        mysqli_stmt_execute($updateLoginStmt);
        mysqli_stmt_close($updateLoginStmt);

        mysqli_stmt_close($stmt);
        return $user;
    }

    mysqli_stmt_close($stmt);
    return false;
}


/**
 * Logout user
 */
function logoutUser()
{
    // Hapus semua session data
    session_unset();
    session_destroy();

    // Redirect ke halaman login
    header("Location: " . BASE_URL . "login.php");
    exit();
}


/**
 * Require login - redirect ke login jika belum login
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Require permission - redirect jika tidak punya permission
 *
 * @param string $permission Permission yang diperlukan
 * @param string $redirectUrl URL untuk redirect jika tidak punya permission
 */
function requirePermission($permission, $redirectUrl = null)
{
    if (!hasPermission($permission)) {
        if ($redirectUrl === null) {
            $redirectUrl = BASE_URL . "index.php";
        }
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Get role display name
 *
 * @param string $role Role code
 * @return string Role display name
 */
function getRoleDisplayName($role)
{
    $roles = [
        'hrd_admin' => 'HRD Administrator',
        'direktur' => 'Direktur',
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'user' => 'User'
    ];

    return isset($roles[$role]) ? $roles[$role] : 'Unknown';
}

/**
 * Get permission display name
 *
 * @param string $permission Permission code
 * @return string Permission display name
 */
function getPermissionDisplayName($permission)
{
    $permissions = [
        'read' => 'Baca Data',
        'write' => 'Tulis Data',
        'delete' => 'Hapus Data',
        'manage_users' => 'Kelola User',
        'manage_departments' => 'Kelola Departemen',
        'manage_positions' => 'Kelola Jabatan',
        'manage_employees' => 'Kelola Karyawan',
        'manage_evaluations' => 'Kelola Penilaian',
        'manage_rewards' => 'Kelola Reward',
        'manage_punishments' => 'Kelola Punishment',
        'generate_reports' => 'Generate Laporan',
        'backup_data' => 'Backup Data',
        'karyawan_view' => 'Lihat Karyawan',
        'karyawan_create' => 'Tambah Karyawan',
        'karyawan_edit' => 'Edit Karyawan',
        'karyawan_delete' => 'Hapus Karyawan',
        'penilaian_view' => 'Lihat Penilaian',
        'penilaian_create' => 'Tambah Penilaian',
        'penilaian_edit' => 'Edit Penilaian',
        'penilaian_delete' => 'Hapus Penilaian',
        'reward_view' => 'Lihat Reward',
        'reward_create' => 'Tambah Reward',
        'reward_edit' => 'Edit Reward',
        'reward_delete' => 'Hapus Reward',
        'punishment_view' => 'Lihat Punishment',
        'punishment_create' => 'Tambah Punishment',
        'punishment_edit' => 'Edit Punishment',
        'punishment_delete' => 'Hapus Punishment',
        'laporan_view' => 'Lihat Laporan',
        'laporan_export' => 'Export Laporan',
        'laporan_cetak' => 'Cetak Laporan',
    ];

    return isset($permissions[$permission]) ? $permissions[$permission] : ucfirst(str_replace('_', ' ', $permission));
}

/**
 * Check apakah user bisa CREATE (write permission)
 * Direktur tidak bisa create
 *
 * @return bool True jika user memiliki permission create
 */
function canCreate() {
    return hasPermission('write') || isAdminOrHRD();
}

/**
 * Check apakah user bisa READ
 */
function canRead() {
    return isLoggedIn() && hasPermission('read');
}

/**
 * Check apakah user bisa UPDATE
 */
function canUpdate() {
    return hasPermission('write') || isAdminOrHRD();
}

/**
 * Check apakah user bisa DELETE
 */
function canDelete() {
    return hasPermission('delete') || isAdmin();
}

/**
 * Restrict write access (CREATE, UPDATE, DELETE)
 * Untuk direktur atau user yang tidak memiliki permission
 * 
 * @return void Redirect ke dashboard jika tidak punya akses
 */
function restrictWriteAccess()
{
    if (isDirektur() || !canCreate()) {
        $_SESSION['error'] = 'Anda tidak memiliki akses untuk melakukan operasi ini. Akses hanya READ untuk role Anda.';
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/index.php');
        exit();
    }
}

/**
 * Restrict direktur access - direktur hanya read-only
 * Gunakan ini di halaman create, edit, delete
 * 
 * @return void Redirect ke index jika user adalah direktur
 */
function restrictDirekturAccess()
{
    if (isDirektur()) {
        $_SESSION['error'] = 'Direktur hanya memiliki akses READ-ONLY. Anda tidak dapat melakukan operasi ini.';
        header('Location: ./index.php');
        exit();
    }
}

/**
 * Restrict admin-only access
 *
 * @return void Redirect jika user bukan admin
 */
function restrictAdminAccess() {
    if (!isAdminOrHRD()) {
        $_SESSION['error'] = 'Akses ditolak. Hanya Admin atau HRD yang dapat mengakses fitur ini.';
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}