<?php
/**
 * Error and Exception Handler
 * MatchDay.ro - Integrated with Logger system
 */

require_once(__DIR__ . '/../includes/Logger.php');

class ErrorHandler {
    
    // Error severity mapping
    private static $severityMap = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED'
    ];
    
    // Is production environment?
    private static $isProduction = true;
    
    /**
     * Initialize error handling
     */
    public static function init(): void {
        // Check environment
        self::$isProduction = !defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE;
        
        // Set error reporting based on environment
        if (self::$isProduction) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
        
        // Register handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool {
        // Respect error_reporting settings
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Get severity name
        $severityName = self::$severityMap[$severity] ?? 'UNKNOWN';
        
        // Determine log level
        $logLevel = Logger::LEVEL_WARNING;
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            $logLevel = Logger::LEVEL_ERROR;
        } elseif (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED])) {
            $logLevel = Logger::LEVEL_INFO;
        }
        
        // Log the error
        Logger::log($logLevel, $message, [
            'severity' => $severityName,
            'severity_code' => $severity,
            'file' => $file,
            'line' => $line
        ], Logger::CHANNEL_ERROR);
        
        // For fatal errors, show error page
        if (in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            self::showErrorPage(
                'A apărut o eroare', 
                'Vă rugăm să reîncercați mai târziu.',
                self::$isProduction ? null : "$severityName: $message in $file:$line"
            );
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void {
        // Log exception with full context
        Logger::exception($exception);
        
        // Log to security channel if it's a security-related exception
        $securityExceptions = ['InvalidCSRFTokenException', 'UnauthorizedException', 'RateLimitException'];
        if (in_array(get_class($exception), $securityExceptions)) {
            Logger::security($exception->getMessage(), [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
        }
        
        // Send alert for critical exceptions (not security ones - those are expected)
        if (!in_array(get_class($exception), $securityExceptions)) {
            Logger::alert('Excepție Neprinsă', $exception->getMessage(), [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => substr($exception->getTraceAsString(), 0, 1000)
            ]);
        }
        
        // Show error page
        $detail = null;
        if (!self::$isProduction) {
            $detail = get_class($exception) . ": " . $exception->getMessage() . 
                      "\n\nFile: " . $exception->getFile() . ":" . $exception->getLine() .
                      "\n\nStack trace:\n" . $exception->getTraceAsString();
        }
        
        self::showErrorPage(
            'A apărut o problemă', 
            'Ceva nu a mers bine. Vă rugăm să reîncercați.',
            $detail
        );
    }
    
    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $severityName = self::$severityMap[$error['type']] ?? 'FATAL';
            
            // Log fatal error
            Logger::critical($error['message'], [
                'severity' => $severityName,
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => 'shutdown_error'
            ]);
            
            // Send alert for fatal errors
            Logger::alert('Eroare Fatală PHP', $error['message'], [
                'severity' => $severityName,
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => 'shutdown_error'
            ]);
            
            // Don't show error page if output already started
            if (!headers_sent()) {
                self::showErrorPage(
                    'Eroare critică',
                    'Site-ul a întâmpinat o problemă. Încercați din nou.',
                    self::$isProduction ? null : "{$severityName}: {$error['message']} in {$error['file']}:{$error['line']}"
                );
            }
        }
    }
    
    /**
     * Display error page
     */
    private static function showErrorPage(string $title, string $message, ?string $detail = null): void {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        // Check if it's an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'error' => true,
                'message' => $message,
                'detail' => $detail
            ]);
            exit;
        }
        
        // Check if it's an API request
        if (isset($_SERVER['REQUEST_URI']) && 
            (strpos($_SERVER['REQUEST_URI'], '_api.php') !== false || 
             strpos($_SERVER['REQUEST_URI'], '/api/') !== false)) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => $message
            ]);
            exit;
        }
        
        ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - MatchDay.ro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
        }
        .error-card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .error-icon i { font-size: 2rem; color: white; }
        pre.debug-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            max-height: 200px;
            overflow: auto;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="error-card p-5 text-center">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h1 class="h3 text-dark mb-3"><?= htmlspecialchars($title) ?></h1>
                    <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
                    
                    <?php if ($detail): ?>
                    <details class="mb-4">
                        <summary class="btn btn-sm btn-outline-secondary">Detalii tehnice</summary>
                        <pre class="debug-info mt-3"><?= htmlspecialchars($detail) ?></pre>
                    </details>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="/" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i>Pagina principală
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-1"></i>Reîncearcă
                        </button>
                    </div>
                    
                    <p class="text-muted small mt-4 mb-0">
                        Dacă problema persistă, contactează-ne la 
                        <a href="mailto:contact@matchday.ro">contact@matchday.ro</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }
    
    /**
     * Manually trigger an error report (for soft errors)
     */
    public static function report(string $message, array $context = [], string $level = Logger::LEVEL_ERROR): void {
        Logger::log($level, $message, $context, Logger::CHANNEL_ERROR);
    }
}

// Initialize error handler
ErrorHandler::init();
