<?php
// Site configuration
define('SITE_NAME', 'MatchDay.ro');
define('SITE_TAGLINE', 'Fiecare meci are o poveste. Noi o scriem.');
define('BASE_URL', 'https://matchday.ro'); // Actualizează cu https://matchday.ro pentru producție
define('SITE_URL', BASE_URL); // Alias for compatibility
date_default_timezone_set('Europe/Bucharest');
define('CONTACT_TO_EMAIL', 'contact@matchday.ro'); // OBLIGATORIU: Schimbă cu email-ul tău real

// Email/SMTP Configuration
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'mail.matchday.ro');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl'); // 'ssl' for port 465, 'tls' for port 587
define('SMTP_USERNAME', 'newsletter@matchday.ro');
define('SMTP_FROM_EMAIL', 'newsletter@matchday.ro');
define('SMTP_FROM_NAME', 'MatchDay.ro');
define('SMTP_REPLY_TO', 'contact@matchday.ro');

// Load SMTP password from separate file (not in git)
if (file_exists(__DIR__ . '/email_secret.php')) {
    require_once(__DIR__ . '/email_secret.php');
} else {
    define('SMTP_PASSWORD', ''); // Set in config/email_secret.php on server
}

// Security configuration
define('ADMIN_PASSWORD_HASH', '$argon2id$v=19$m=65536,t=4,p=1$dWhzWHAxcDIzdURHS3ZXYg$UHeFfdLTQBW2YHKywkophXH2stSKrZ2j6q6HH+uPUls');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

// Alert configuration (email notifications for critical errors)
define('ALERT_ENABLED', true);
define('ALERT_EMAIL', 'contact@matchday.ro'); // Email for critical error alerts
define('ALERT_RATE_LIMIT_MINUTES', 15); // Don't send more than one alert per type every X minutes
define('ALERT_MIN_LEVEL', 'ERROR'); // Minimum level to trigger alert (ERROR, CRITICAL)

// Upload configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Performance
define('POSTS_PER_PAGE', 6);
define('COMMENTS_PER_PAGE', 20);

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Load utilities
require_once(__DIR__ . '/security.php');
require_once(__DIR__ . '/cache.php');
require_once(__DIR__ . '/validator.php');

// Session security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Auto-logout on inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();
?>