<?php
require_once(__DIR__ . '/config/config.php');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$dir = __DIR__ . '/data/polls';
if (!is_dir($dir)) { 
    mkdir($dir, 0755, true); 
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        $pollId = Security::sanitizeInput($_GET['poll'] ?? '');
        $pollId = preg_replace('/[^a-z0-9\-_]/', '', $pollId);
        
        if ($pollId === '') {
            // Return all active polls
            $polls = [];
            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['active']) && $data['active']) {
                    $polls[] = $data;
                }
            }
            echo json_encode($polls);
            exit;
        }
        
        $file = $dir . '/' . $pollId . '.json';
        if (!file_exists($file)) {
            http_response_code(404);
            echo json_encode(['error' => 'Sondaj nu a fost găsit']);
            exit;
        }
        
        $poll = json_decode(file_get_contents($file), true);
        echo json_encode($poll);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la încărcarea sondajului']);
    }
    exit;
}

if ($method === 'POST') {
    try {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $hashedIP = md5($clientIP . 'poll_salt');
        
        $pollId = Security::sanitizeInput($_POST['poll'] ?? '');
        $pollId = preg_replace('/[^a-z0-9\-_]/', '', $pollId);
        
        $optionId = Security::sanitizeInput($_POST['option'] ?? '');
        $optionId = preg_replace('/[^a-z0-9\-_]/', '', $optionId);
        
        if ($pollId === '' || $optionId === '') {
            throw new Exception('Date invalide');
        }
        
        $file = $dir . '/' . $pollId . '.json';
        if (!file_exists($file)) {
            throw new Exception('Sondaj nu a fost găsit');
        }
        
        $poll = json_decode(file_get_contents($file), true);
        if (!$poll || !$poll['active']) {
            throw new Exception('Sondajul nu este activ');
        }
        
        // Check if IP already voted
        if (!isset($poll['votes'])) {
            $poll['votes'] = [];
        }
        
        if (in_array($hashedIP, $poll['voted_ips'] ?? [])) {
            throw new Exception('Ai votat deja în acest sondaj');
        }
        
        // Validate option exists
        $validOption = false;
        foreach ($poll['options'] as &$option) {
            if ($option['id'] === $optionId) {
                $option['votes'] = ($option['votes'] ?? 0) + 1;
                $validOption = true;
                break;
            }
        }
        
        if (!$validOption) {
            throw new Exception('Opțiune invalidă');
        }
        
        // Record vote
        $poll['total_votes'] = ($poll['total_votes'] ?? 0) + 1;
        $poll['voted_ips'] = $poll['voted_ips'] ?? [];
        $poll['voted_ips'][] = $hashedIP;
        
        // Limit stored IPs to prevent file from growing too large
        if (count($poll['voted_ips']) > 10000) {
            $poll['voted_ips'] = array_slice($poll['voted_ips'], -5000);
        }
        
        if (file_put_contents($file, json_encode($poll, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new Exception('Eroare la salvarea votului');
        }
        
        echo json_encode([
            'ok' => true, 
            'message' => 'Vot înregistrat cu succes!',
            'poll' => $poll
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
