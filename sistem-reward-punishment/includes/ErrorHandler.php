<?php
/**
 * Error Handling & Logging Helper
 * Menyediakan fungsi untuk error handling dan logging yang aman
 */

class ErrorHandler {
    private static $logFile = null;
    private static $DEBUG_MODE = null;
    
    /**
     * Initialize error handler
     * 
     * @param string $logDir Directory untuk menyimpan log files
     * @param bool $debugMode Debug mode flag
     */
    public static function init($logDir = null, $debugMode = false) {
        if ($logDir === null) {
            $logDir = __DIR__ . '/../logs';
        }
        
        // Create logs directory jika belum ada
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        self::$logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        self::$DEBUG_MODE = $debugMode;
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Set shutdown handler untuk fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Suppress errors yang di-prefix dengan @
        if (error_reporting() === 0) {
            return true;
        }
        
        $errorType = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $type = $errorType[$errno] ?? 'Unknown';
        
        $message = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $type,
            $errstr,
            $errfile,
            $errline
        );
        
        // Log error
        self::log($message);
        
        // Display error
        if (self::$DEBUG_MODE) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<strong>' . htmlspecialchars($type) . ':</strong> ';
            echo htmlspecialchars($errstr) . '<br>';
            echo '<small>' . htmlspecialchars($errfile) . ':' . $errline . '</small>';
            echo '</div>';
        } else {
            // Show generic message di production
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo 'An error occurred. Please try again later.';
            echo '</div>';
        }
        
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        $message = sprintf(
            "[%s] Exception: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        // Log exception
        self::log($message);
        
        // Log stack trace jika debug
        if (self::$DEBUG_MODE) {
            self::log("Stack trace:\n" . $exception->getTraceAsString());
        }
        
        // Display error
        if (self::$DEBUG_MODE) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<strong>Exception:</strong> ';
            echo htmlspecialchars($exception->getMessage()) . '<br>';
            echo '<small>' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</small><br>';
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo 'An error occurred. Please try again later.';
            echo '</div>';
        }
    }
    
    /**
     * Handle shutdown untuk fatal errors
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
    
    /**
     * Log message ke file
     * 
     * @param string $message Message untuk di-log
     * @param string $level Log level (INFO, WARNING, ERROR)
     */
    public static function log($message, $level = 'ERROR') {
        if (self::$logFile === null) {
            return;
        }
        
        $logEntry = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        @error_log($logEntry, 3, self::$logFile);
    }
    
    /**
     * Log info message
     * 
     * @param string $message Message untuk di-log
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    /**
     * Log warning message
     * 
     * @param string $message Message untuk di-log
     */
    public static function warning($message) {
        self::log($message, 'WARNING');
    }
    
    /**
     * Log error message
     * 
     * @param string $message Message untuk di-log
     */
    public static function error($message) {
        self::log($message, 'ERROR');
    }
}

?>
