<?php
/**
 * Comments Public API
 * MatchDay.ro - Comment system
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Comment.php');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $slug = Security::sanitizeInput($_GET['slug'] ?? '');
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        if ($slug === '') { 
            echo json_encode([]);
            exit; 
        }
        
        $comments = Comment::getByPost($slug);
        
        // Format for JS compatibility
        $formattedComments = [];
        foreach ($comments as $comment) {
            $formattedComments[] = [
                'name' => $comment['author_name'],
                'message' => $comment['content'],
                'date' => date('Y-m-d H:i', strtotime($comment['created_at']))
            ];
        }
        
        echo json_encode($formattedComments);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la încărcarea comentariilor']);
    }
    exit;
}

if ($method === 'POST') {
    try {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Rate limiting for comments using database
        if (!Comment::checkRateLimit($clientIP, 3, 5)) {
            throw new Exception('Prea multe comentarii. Încearcă din nou în 5 minute.');
        }
        
        // Input validation
        $slug = Security::sanitizeInput($_POST['slug'] ?? '');
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        $name = Validator::required($_POST['name'] ?? '', 'Numele');
        $name = Validator::maxLength(trim($name), 50, 'Numele');
        
        $message = Validator::required($_POST['message'] ?? '', 'Mesajul');
        $message = Validator::maxLength(trim($message), 500, 'Mesajul');
        
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
        
        // Create comment in database
        $commentId = Comment::create([
            'post_slug' => $slug,
            'author_name' => $name,
            'content' => $message,
            'ip' => $clientIP,
            'approved' => 0 // Requires moderation
        ]);
        
        if ($commentId) {
            // Log comment for moderation
            error_log("New comment on $slug by $name (IP: $clientIP): " . substr($message, 0, 100));
            
            echo json_encode(['ok' => true, 'message' => 'Comentariu salvat cu succes! Va fi vizibil după aprobare.']);
        } else {
            throw new Exception('Eroare la salvarea comentariului');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
