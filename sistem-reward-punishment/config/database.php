<?php
/**
 * Konfigurasi Database
 * File ini berisi pengaturan koneksi database dan fungsi helper
 */

// Cek jika sudah ada session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mode debug
define('DEBUG_MODE', true);

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_reward_punishment');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Base URL aplikasi
define('BASE_URL', 'http://localhost/sistem-reward-punishment/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting berdasarkan mode debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Koneksi ke Database dengan error handling yang lebih baik
 * 
 * @return mysqli|false Koneksi database atau false jika gagal
 */
function connectDB() {
    static $conn = null;
    
    // Gunakan koneksi yang sudah ada jika tersedia
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }
    
    try {
        // Buat koneksi baru
        $conn = mysqli_init();
        
        // Set timeout
        mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        mysqli_options($conn, MYSQLI_OPT_READ_TIMEOUT, 30);
        
        // Enable SSL jika diperlukan
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        
        // Koneksi ke database
        if (!@mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, null, null, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT)) {
            throw new Exception('Koneksi database gagal: ' . mysqli_connect_error());
        }
        
        // Set charset
        if (!mysqli_set_charset($conn, DB_CHARSET)) {
            throw new Exception('Error loading character set ' . DB_CHARSET . ': ' . mysqli_error($conn));
        }
        
        // Set timezone untuk koneksi
        mysqli_query($conn, "SET time_zone = '+07:00'");
        
        // Set SQL mode
        mysqli_query($conn, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        mysqli_query($conn, "SET AUTOCOMMIT = 1");
        
        return $conn;
        
    } catch (Exception $e) {
        // Log error
        error_log('Database Connection Error: ' . $e->getMessage());
        
        // Tampilkan error berdasarkan mode debug
        if (DEBUG_MODE) {
            die('<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px;">
                    <h3>Database Connection Error</h3>
                    <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p><strong>Host:</strong> ' . DB_HOST . '</p>
                    <p><strong>Database:</strong> ' . DB_NAME . '</p>
                    <p><small>Check your database configuration in config/database.php</small></p>
                </div>');
        } else {
            die('<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px;">
                    <h3>System Maintenance</h3>
                    <p>The system is currently undergoing maintenance. Please try again later.</p>
                </div>');
        }
    }
}

/**
 * Fungsi untuk mengeksekusi query dengan error handling
 * 
 * @param string $sql Query SQL
 * @param array $params Parameter untuk prepared statement (optional)
 * @return mysqli_result|bool Hasil query
 */
function executeQuery($sql, $params = []) {
    $conn = connectDB();
    
    try {
        // Jika ada parameter, gunakan prepared statement
        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                throw new Exception('Prepare statement failed: ' . mysqli_error($conn));
            }
            
            // Bind parameters
            $types = '';
            $bind_params = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bind_params[] = &$param;  // Pass by reference
            }
            
            array_unshift($bind_params, $types);
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
            
            // Execute query
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Execute statement failed: ' . mysqli_error($conn));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            
            return $result;
            
        } else {
            // Execute regular query
            $result = mysqli_query($conn, $sql);
            
            if (!$result) {
                throw new Exception('Query execution failed: ' . mysqli_error($conn));
            }
            
            return $result;
        }
        
    } catch (Exception $e) {
        error_log('Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
        
        if (DEBUG_MODE) {
            die('<div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 5px; margin: 20px;">
                    <h3>Query Error</h3>
                    <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p><strong>SQL:</strong> <code>' . htmlspecialchars($sql) . '</code></p>
                </div>');
        }
        
        return false;
    }
}

/**
 * Fungsi untuk mendapatkan single row
 * 
 * @param string $sql Query SQL
 * @param array $params Parameter untuk prepared statement (optional)
 * @return array|null Row data atau null jika tidak ditemukan
 */
function getSingleRow($sql, $params = []) {
    $result = executeQuery($sql, $params);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Fungsi untuk mendapatkan multiple rows
 * 
 * @param string $sql Query SQL
 * @param array $params Parameter untuk prepared statement (optional)
 * @return array Array of rows
 */
function getMultipleRows($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $rows = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Fungsi untuk insert data
 * 
 * @param string $table Nama tabel
 * @param array $data Data dalam bentuk associative array
 * @return int|false ID yang diinsert atau false jika gagal
 */
function insertData($table, $data) {
    if (empty($table) || empty($data)) {
        return false;
    }
    
    $columns = [];
    $placeholders = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $columns[] = "`" . mysqli_real_escape_string(connectDB(), $column) . "`";
        $placeholders[] = "?";
        $values[] = $value;
    }
    
    $sql = "INSERT INTO `$table` (" . implode(', ', $columns) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $result = executeQuery($sql, $values);
    
    if ($result) {
        return mysqli_insert_id(connectDB());
    }
    
    return false;
}

/**
 * Fungsi untuk update data
 * 
 * @param string $table Nama tabel
 * @param array $data Data dalam bentuk associative array
 * @param string $where Kondisi WHERE
 * @param array $whereParams Parameter untuk kondisi WHERE
 * @return int|false Jumlah baris yang diupdate atau false jika gagal
 */
function updateData($table, $data, $where, $whereParams = []) {
    if (empty($table) || empty($data) || empty($where)) {
        return false;
    }
    
    $sets = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $sets[] = "`" . mysqli_real_escape_string(connectDB(), $column) . "` = ?";
        $values[] = $value;
    }
    
    // Gabungkan values dengan whereParams
    $params = array_merge($values, $whereParams);
    
    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE $where";
    
    $result = executeQuery($sql, $params);
    
    if ($result) {
        return mysqli_affected_rows(connectDB());
    }
    
    return false;
}

/**
 * Fungsi untuk delete data
 * 
 * @param string $table Nama tabel
 * @param string $where Kondisi WHERE
 * @param array $params Parameter untuk kondisi WHERE
 * @return int|false Jumlah baris yang dihapus atau false jika gagal
 */
function deleteData($table, $where, $params = []) {
    if (empty($table) || empty($where)) {
        return false;
    }
    
    $sql = "DELETE FROM `$table` WHERE $where";
    
    $result = executeQuery($sql, $params);
    
    if ($result) {
        return mysqli_affected_rows(connectDB());
    }
    
    return false;
}

/**
 * Fungsi untuk mengecek apakah tabel ada
 * 
 * @param string $table Nama tabel
 * @return bool True jika tabel ada
 */
function tableExists($table) {
    $conn = connectDB();
    $table = mysqli_real_escape_string($conn, $table);
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    
    return $result && mysqli_num_rows($result) > 0;
}

/**
 * Fungsi untuk memulai transaksi
 * 
 * @return bool True jika berhasil
 */
function beginTransaction() {
    $conn = connectDB();
    return mysqli_begin_transaction($conn);
}

/**
 * Fungsi untuk commit transaksi
 * 
 * @return bool True jika berhasil
 */
function commitTransaction() {
    $conn = connectDB();
    return mysqli_commit($conn);
}

/**
 * Fungsi untuk rollback transaksi
 * 
 * @return bool True jika berhasil
 */
function rollbackTransaction() {
    $conn = connectDB();
    return mysqli_rollback($conn);
}

/**
 * Fungsi untuk mendapatkan jumlah baris
 * 
 * @param string $sql Query SQL
 * @param array $params Parameter untuk prepared statement (optional)
 * @return int Jumlah baris
 */
function getRowCount($sql, $params = []) {
    $result = executeQuery($sql, $params);
    
    if ($result) {
        return mysqli_num_rows($result);
    }
    
    return 0;
}

/**
 * Fungsi untuk validasi email
 *
 * @param string $email Email yang akan divalidasi
 * @return bool True jika email valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fungsi untuk validasi URL
 * 
 * @param string $url URL yang akan divalidasi
 * @return bool True jika URL valid
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Fungsi untuk validasi angka
 * 
 * @param mixed $number Angka yang akan divalidasi
 * @param int|null $min Nilai minimum (optional)
 * @param int|null $max Nilai maksimum (optional)
 * @return bool True jika angka valid
 */
function validateNumber($number, $min = null, $max = null) {
    if (!is_numeric($number)) {
        return false;
    }
    
    $number = floatval($number);
    
    if ($min !== null && $number < $min) {
        return false;
    }
    
    if ($max !== null && $number > $max) {
        return false;
    }
    
    return true;
}

/**
 * Fungsi untuk mendapatkan pesan session
 *
 * @return array Array berisi pesan sukses dan error
 */
function getSessionMessages() {
    $messages = [
        'success' => null,
        'error' => null
    ];
    
    if (isset($_SESSION['success_message'])) {
        $messages['success'] = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $messages['error'] = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    return $messages;
}

// Test koneksi saat file diinclude
try {
    $conn = connectDB();
    if ($conn) {
        // Cek jika database ada
        $result = mysqli_select_db($conn, DB_NAME);
        if (!$result && DEBUG_MODE) {
            error_log("Database '" . DB_NAME . "' tidak ditemukan. Silakan buat database terlebih dahulu.");
        }
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log("Database initialization error: " . $e->getMessage());
    }
}
?>