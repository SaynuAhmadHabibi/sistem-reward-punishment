<?php
/**
 * Environment Configuration Loader
 * Memuat variabel dari file .env
 */

class EnvLoader {
    private static $loaded = false;
    
    /**
     * Load environment variables dari file .env
     * 
     * @param string $path Path ke file .env
     * @return void
     */
    public static function load($path = __DIR__ . '/../.env') {
        if (self::$loaded) {
            return;
        }
        
        if (!file_exists($path)) {
            // Fallback ke .env.example jika .env tidak ada
            $path = __DIR__ . '/../.env.example';
            
            if (!file_exists($path)) {
                throw new Exception('.env file not found');
            }
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes jika ada
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Set sebagai environment variable dan constant
                putenv("$key=$value");
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     * 
     * @param string $key Key dari environment variable
     * @param mixed $default Default value jika tidak ada
     * @return mixed
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            if (defined($key)) {
                return constant($key);
            }
            return $default;
        }
        
        return $value;
    }
}
?>
