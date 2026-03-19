<?php
require_once(__DIR__ . '/config/config.php');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$dir = __DIR__ . '/data/comments';
if (!is_dir($dir)) { 
    mkdir($dir, 0755, true); 
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $slug = Security::sanitizeInput($_GET['slug'] ?? '');
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        if ($slug === '') { 
            echo json_encode([]);
            exit; 
        }
        
        $file = $dir . '/' . $slug . '.json';
        if (!file_exists($file)) { 
            echo json_encode([]);
            exit; 
        }
        
        $json = file_get_contents($file);
        $comments = json_decode($json, true) ?: [];
        
        // Filtrează doar comentariile aprobate pentru public
        $comments = array_filter($comments, function($comment) {
            return isset($comment['approved']) && $comment['approved'] === true;
        });
        
        // Reindexează array-ul după filtrare
        $comments = array_values($comments);
        
        // Pentru compatibilitate, returnează direct array-ul de comentarii
        // În loc de paginare complexă
        echo json_encode($comments);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la încărcarea comentariilor']);
    }
    exit;
}

if ($method === 'POST') {
    try {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Rate limiting for comments
        if (!Security::rateLimitCheck("comment_$clientIP", 3, 300)) {
            throw new Exception('Prea multe comentarii. Încearcă din nou în 5 minute.');
        }
        
        // Input validation
        $slug = Security::sanitizeInput($_POST['slug'] ?? '');
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        $name = Validator::required($_POST['name'] ?? '', 'Numele');
        $name = Validator::maxLength(trim($name), 50, 'Numele');
        $name = Security::sanitizeInput($name);
        
        $message = Validator::required($_POST['message'] ?? '', 'Mesajul');
        $message = Validator::maxLength(trim($message), 500, 'Mesajul');
        $message = Security::sanitizeInput($message);
        
        // Honeypot check
        $honeypot = trim($_POST['website'] ?? '');
        if ($honeypot !== '') {
            // Silent fail for bots
            echo json_encode(['ok' => true]);
            exit;
        }
        
        if ($slug === '') {
            throw new Exception('Slug invalid');
        }
        
        // Simple spam detection
        $spamWords = ['viagra', 'casino', 'porn', 'xxx', 'cheap', 'free money'];
        $content = strtolower($name . ' ' . $message);
        foreach ($spamWords as $word) {
            if (strpos($content, $word) !== false) {
                throw new Exception('Comentariu detectat ca spam');
            }
        }
        
        // Check for duplicate comments (same IP, same message in last hour)
        $recentFile = $dir . '/recent_' . md5($clientIP) . '.json';
        if (file_exists($recentFile)) {
            $recent = json_decode(file_get_contents($recentFile), true) ?: [];
            $recent = array_filter($recent, function($item) {
                return (time() - $item['time']) < 3600; // 1 hour
            });
            
            foreach ($recent as $item) {
                if ($item['message'] === $message) {
                    throw new Exception('Comentariu duplicat detectat');
                }
            }
        } else {
            $recent = [];
        }
        
        $file = $dir . '/' . $slug . '.json';
        $comments = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        
        $newComment = [
            'name' => $name,
            'message' => $message,
            'date' => date('Y-m-d H:i'),
            'ip' => md5($clientIP), // Store hashed IP for moderation
            'user_agent' => md5($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'timestamp' => time(),
            'approved' => false // Comentariul trebuie aprobat de admin
        ];
        
        $comments[] = $newComment;
        
        // Limit total comments per post
        if (count($comments) > 1000) {
            $comments = array_slice($comments, -1000);
        }
        
        // Save comments
        if (file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new Exception('Eroare la salvarea comentariului');
        }
        
        // Update recent comments for this IP
        $recent[] = [
            'message' => $message,
            'time' => time()
        ];
        file_put_contents($recentFile, json_encode($recent, JSON_UNESCAPED_UNICODE));
        
        // Log comment for moderation
        error_log("New comment on $slug by $name (IP: $clientIP): " . substr($message, 0, 100));
        
        echo json_encode(['ok' => true, 'message' => 'Comentariu salvat cu succes! Va fi vizibil după aprobare.']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
