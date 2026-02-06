<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection Helper
 * Menyediakan token generation dan validation
 */

class CSRFProtection {
    private static $tokenName = '_csrf_token';
    private static $tokenLength = 32;
    
    /**
     * Initialize CSRF protection
     * Harus dipanggil di awal session
     */
    public static function init() {
        if (!isset($_SESSION[self::$tokenName])) {
            self::generateToken();
        }
    }
    
    /**
     * Generate CSRF token baru
     * 
     * @return string CSRF token
     */
    public static function generateToken() {
        $token = bin2hex(random_bytes(self::$tokenLength));
        $_SESSION[self::$tokenName] = $token;
        return $token;
    }
    
    /**
     * Get current CSRF token
     * 
     * @return string|null CSRF token
     */
    public static function getToken() {
        return $_SESSION[self::$tokenName] ?? null;
    }
    
    /**
     * Get hidden input field untuk form
     * 
     * @return string HTML hidden input
     */
    public static function getField() {
        $token = self::getToken();
        return '<input type="hidden" name="' . htmlspecialchars(self::$tokenName) . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate CSRF token dari request
     * 
     * @param string $token Token untuk divalidasi
     * @return bool True jika token valid
     */
    public static function validate($token) {
        // Validasi token ada di session
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        // Gunakan hash_equals untuk timing-safe comparison
        return hash_equals($_SESSION[self::$tokenName], $token);
    }
    
    /**
     * Validasi CSRF token dari POST request
     * 
     * @return bool True jika token valid
     */
    public static function validateFromRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }
        
        $token = $_POST[self::$tokenName] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return self::validate($token);
    }
    
    /**
     * Regenerate token setelah login
     */
    public static function regenerate() {
        unset($_SESSION[self::$tokenName]);
        self::generateToken();
    }
}

// Initialize CSRF protection ketika file diinclude
if (session_status() === PHP_SESSION_ACTIVE) {
    CSRFProtection::init();
}
?>
