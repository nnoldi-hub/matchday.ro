<?php
/**
 * Logger Class
 * MatchDay.ro - Centralized logging system
 * 
 * Channels: app, error, security, api, audit, performance
 */

class Logger {
    
    // Log levels
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    // Log channels
    const CHANNEL_APP = 'app';
    const CHANNEL_ERROR = 'error';
    const CHANNEL_SECURITY = 'security';
    const CHANNEL_API = 'api';
    const CHANNEL_AUDIT = 'audit';
    const CHANNEL_PERFORMANCE = 'performance';
    
    // Level priorities (for filtering)
    private static $levelPriority = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3,
        self::LEVEL_CRITICAL => 4
    ];
    
    // Minimum log level (configurable)
    private static $minLevel = self::LEVEL_DEBUG;
    
    // Logs directory
    private static $logsDir = null;
    
    /**
     * Initialize logger
     */
    public static function init(): void {
        if (self::$logsDir === null) {
            self::$logsDir = __DIR__ . '/../data/logs';
        }
        
        // Create logs directory if not exists
        if (!is_dir(self::$logsDir)) {
            mkdir(self::$logsDir, 0755, true);
            
            // Create .htaccess to protect logs
            file_put_contents(self::$logsDir . '/.htaccess', "Deny from all\n");
        }
        
        // Set minimum level from config if defined
        if (defined('LOG_MIN_LEVEL')) {
            self::$minLevel = LOG_MIN_LEVEL;
        }
    }
    
    /**
     * Main log method
     */
    public static function log(
        string $level, 
        string $message, 
        array $context = [], 
        string $channel = self::CHANNEL_APP
    ): bool {
        self::init();
        
        // Check if level meets minimum threshold
        if (self::$levelPriority[$level] < self::$levelPriority[self::$minLevel]) {
            return false;
        }
        
        // Build log entry
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
            'request' => [
                'ip' => self::getClientIP(),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200)
            ]
        ];
        
        // Add user ID if available
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $entry['user_id'] = $_SESSION['user_id'];
        }
        
        // Format log line
        $logLine = self::formatLogLine($entry);
        
        // Write to appropriate file
        $filename = self::getLogFilename($channel);
        
        return file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Format log line for file output
     */
    private static function formatLogLine(array $entry): string {
        $contextStr = !empty($entry['context']) ? json_encode($entry['context'], JSON_UNESCAPED_UNICODE) : '';
        
        return sprintf(
            "[%s] %s.%s: %s %s | IP: %s | URI: %s\n",
            $entry['timestamp'],
            strtoupper($entry['channel']),
            $entry['level'],
            $entry['message'],
            $contextStr,
            $entry['request']['ip'],
            $entry['request']['uri']
        );
    }
    
    /**
     * Get log filename based on channel and date
     */
    private static function getLogFilename(string $channel): string {
        $date = date('Y-m-d');
        return self::$logsDir . "/{$channel}-{$date}.log";
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated list (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'Unknown';
    }
    
    // ====== Convenience Methods ======
    
    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): bool {
        return self::log(self::LEVEL_DEBUG, $message, $context, self::CHANNEL_APP);
    }
    
    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): bool {
        return self::log(self::LEVEL_INFO, $message, $context, self::CHANNEL_APP);
    }
    
    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): bool {
        return self::log(self::LEVEL_WARNING, $message, $context, self::CHANNEL_APP);
    }
    
    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): bool {
        return self::log(self::LEVEL_ERROR, $message, $context, self::CHANNEL_ERROR);
    }
    
    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): bool {
        return self::log(self::LEVEL_CRITICAL, $message, $context, self::CHANNEL_ERROR);
    }
    
    // ====== Specialized Channels ======
    
    /**
     * Log security event (failed logins, CSRF failures, etc.)
     */
    public static function security(string $message, array $context = []): bool {
        return self::log(self::LEVEL_WARNING, $message, $context, self::CHANNEL_SECURITY);
    }
    
    /**
     * Log API call (external APIs)
     */
    public static function api(
        string $provider, 
        string $endpoint, 
        int $responseCode, 
        float $durationMs,
        array $context = []
    ): bool {
        $message = "{$provider} {$endpoint} -> {$responseCode} ({$durationMs}ms)";
        $context = array_merge($context, [
            'provider' => $provider,
            'endpoint' => $endpoint,
            'response_code' => $responseCode,
            'duration_ms' => $durationMs
        ]);
        
        $level = $responseCode >= 400 ? self::LEVEL_ERROR : self::LEVEL_INFO;
        return self::log($level, $message, $context, self::CHANNEL_API);
    }
    
    /**
     * Log admin audit action
     */
    public static function audit(string $action, int $userId, array $details = []): bool {
        $message = "User #{$userId}: {$action}";
        $context = array_merge($details, [
            'action' => $action,
            'user_id' => $userId
        ]);
        
        return self::log(self::LEVEL_INFO, $message, $context, self::CHANNEL_AUDIT);
    }
    
    /**
     * Log performance issue (slow queries, timeouts)
     */
    public static function performance(string $operation, float $durationMs, array $context = []): bool {
        $level = $durationMs > 5000 ? self::LEVEL_WARNING : self::LEVEL_INFO;
        $message = "{$operation}: {$durationMs}ms";
        $context['duration_ms'] = $durationMs;
        
        return self::log($level, $message, $context, self::CHANNEL_PERFORMANCE);
    }
    
    // ====== Exception Logging ======
    
    /**
     * Log exception with full stack trace
     */
    public static function exception(Throwable $e, array $context = []): bool {
        $context = array_merge($context, [
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return self::log(
            self::LEVEL_ERROR, 
            $e->getMessage(), 
            $context, 
            self::CHANNEL_ERROR
        );
    }
    
    // ====== Log Reading ======
    
    /**
     * Get recent log entries
     */
    public static function getRecent(
        string $channel = self::CHANNEL_ERROR, 
        int $lines = 100, 
        ?string $date = null
    ): array {
        self::init();
        
        $date = $date ?? date('Y-m-d');
        $filename = self::$logsDir . "/{$channel}-{$date}.log";
        
        if (!file_exists($filename)) {
            return [];
        }
        
        // Read last N lines efficiently
        $file = new SplFileObject($filename, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $entries = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $entries[] = $line;
            }
        }
        
        return array_reverse($entries);
    }
    
    /**
     * Search logs
     */
    public static function search(
        string $query, 
        string $channel = self::CHANNEL_ERROR,
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 100
    ): array {
        self::init();
        
        $startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $endDate ?? date('Y-m-d');
        
        $results = [];
        $currentDate = $startDate;
        
        while ($currentDate <= $endDate && count($results) < $limit) {
            $filename = self::$logsDir . "/{$channel}-{$currentDate}.log";
            
            if (file_exists($filename)) {
                $handle = fopen($filename, 'r');
                while (($line = fgets($handle)) !== false) {
                    if (stripos($line, $query) !== false) {
                        $results[] = [
                            'date' => $currentDate,
                            'entry' => trim($line)
                        ];
                        
                        if (count($results) >= $limit) {
                            break;
                        }
                    }
                }
                fclose($handle);
            }
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        return $results;
    }
    
    /**
     * Get log statistics
     */
    public static function getStats(?string $date = null): array {
        self::init();
        
        $date = $date ?? date('Y-m-d');
        $stats = [
            'date' => $date,
            'channels' => []
        ];
        
        $channels = [
            self::CHANNEL_APP, 
            self::CHANNEL_ERROR, 
            self::CHANNEL_SECURITY, 
            self::CHANNEL_API, 
            self::CHANNEL_AUDIT,
            self::CHANNEL_PERFORMANCE
        ];
        
        foreach ($channels as $channel) {
            $filename = self::$logsDir . "/{$channel}-{$date}.log";
            
            if (file_exists($filename)) {
                $lineCount = 0;
                $handle = fopen($filename, 'r');
                while (!feof($handle)) {
                    if (fgets($handle) !== false) {
                        $lineCount++;
                    }
                }
                fclose($handle);
                
                $stats['channels'][$channel] = [
                    'entries' => $lineCount,
                    'size_kb' => round(filesize($filename) / 1024, 2)
                ];
            } else {
                $stats['channels'][$channel] = [
                    'entries' => 0,
                    'size_kb' => 0
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean old logs (older than X days)
     */
    public static function cleanup(int $keepDays = 30): int {
        self::init();
        
        $deleted = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$keepDays} days"));
        
        $files = glob(self::$logsDir . '/*.log');
        
        foreach ($files as $file) {
            $filename = basename($file);
            // Extract date from filename (channel-YYYY-MM-DD.log)
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $filename, $matches)) {
                $fileDate = $matches[0];
                if ($fileDate < $cutoffDate) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}
