<?php
// Site configuration
define('SITE_NAME', 'MatchDay.ro');
define('SITE_TAGLINE', 'Fiecare meci are o poveste. Noi o scriem.');
define('BASE_URL', 'https://matchday.ro'); // Actualizează cu https://matchday.ro pentru producție
date_default_timezone_set('Europe/Bucharest');
define('CONTACT_TO_EMAIL', 'contact@matchday.ro'); // OBLIGATORIU: Schimbă cu email-ul tău real

// Security configuration
define('ADMIN_PASSWORD_HASH', '$argon2id$v=19$m=65536,t=4,p=1$V2FIZWVXS3djTUlnZGx5ag$L9ABXoNngrYhbzzsmh88MtbOtdIvFyy/BvIK/eD8yS0');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

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