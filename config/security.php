<?php
// Security utilities
class Security {
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function rateLimitCheck($key, $limit = 5, $window = 300) {
        $file = __DIR__ . '/../data/rate_limits.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        
        $now = time();
        $windowStart = $now - $window;
        
        // Clean old entries
        foreach ($data as $k => $v) {
            $data[$k] = array_filter($v, fn($t) => $t > $windowStart);
            if (empty($data[$k])) unset($data[$k]);
        }
        
        if (!isset($data[$key])) $data[$key] = [];
        
        if (count($data[$key]) >= $limit) {
            return false;
        }
        
        $data[$key][] = $now;
        file_put_contents($file, json_encode($data));
        return true;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
