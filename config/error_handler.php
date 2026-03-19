<?php
// Error and exception handling
class ErrorHandler {
    public static function init() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        error_log("PHP Error [$severity]: $message in $file on line $line");
        
        if ($severity === E_ERROR || $severity === E_USER_ERROR) {
            self::showErrorPage('A apărut o eroare', 'Vă rugăm să reîncercați mai târziu.');
        }
        
        return true;
    }
    
    public static function handleException($exception) {
        error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
        self::showErrorPage('A apărut o problemă', 'Ceva nu a mers bine. Vă rugăm să reîncercați.');
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    }
    
    private static function showErrorPage($title, $message) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><title>' . $title . '</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
        echo '<body class="bg-light"><div class="container mt-5"><div class="row justify-content-center">';
        echo '<div class="col-md-6"><div class="card"><div class="card-body text-center">';
        echo '<h1 class="h4 text-danger">' . $title . '</h1>';
        echo '<p>' . $message . '</p>';
        echo '<a href="/" class="btn btn-primary">Înapoi la pagina principală</a>';
        echo '</div></div></div></div></div></body></html>';
        exit;
    }
}

// Initialize error handler
ErrorHandler::init();
