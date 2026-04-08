<?php
/**
 * Polls Public API
 * MatchDay.ro - Vote on polls
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Poll.php');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        // Support both slug and numeric ID in 'poll' parameter
        $pollIdentifier = Security::sanitizeInput($_GET['poll'] ?? $_GET['poll_id'] ?? '');
        $pollIdentifier = preg_replace('/[^a-z0-9\-_]/', '', $pollIdentifier);
        
        // Also support separate 'id' parameter
        $pollId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        if ($pollIdentifier === '' && $pollId === 0) {
            // Return all active polls
            $polls = Poll::getActive(10);
            
            // Format for JS compatibility
            foreach ($polls as &$poll) {
                foreach ($poll['options'] as &$opt) {
                    $opt['text'] = $opt['option_text'];
                }
            }
            
            echo json_encode($polls);
            exit;
        }
        
        // Get poll by slug or ID
        if ($pollId > 0) {
            $poll = Poll::getById($pollId);
        } elseif (ctype_digit($pollIdentifier)) {
            // Numeric string in poll parameter - treat as ID
            $poll = Poll::getById((int)$pollIdentifier);
        } else {
            $poll = Poll::getBySlug($pollIdentifier);
        }
        
        if (!$poll) {
            http_response_code(404);
            echo json_encode(['error' => 'Sondaj nu a fost găsit']);
            exit;
        }
        
        // Format for JS compatibility
        foreach ($poll['options'] as &$opt) {
            $opt['text'] = $opt['option_text'];
        }
        
        // Check if user has voted
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $poll['hasVoted'] = Poll::hasVoted($poll['id'], $clientIP);
        
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
        
        $pollIdentifier = Security::sanitizeInput($_POST['poll'] ?? '');
        $pollIdentifier = preg_replace('/[^a-z0-9\-_]/', '', $pollIdentifier);
        
        $optionId = Security::sanitizeInput($_POST['option'] ?? '');
        // Extract numeric ID from "option_X" format
        $optionId = preg_replace('/[^0-9]/', '', $optionId);
        
        if ($pollIdentifier === '' || $optionId === '') {
            throw new Exception('Date invalide');
        }
        
        // Support both numeric ID and slug
        if (ctype_digit($pollIdentifier)) {
            $poll = Poll::getById((int)$pollIdentifier);
        } else {
            $poll = Poll::getBySlug($pollIdentifier);
        }
        
        if (!$poll) {
            throw new Exception('Sondaj nu a fost găsit');
        }
        
        if (!$poll['active']) {
            throw new Exception('Sondajul nu este activ');
        }
        
        // Vote using the database
        $result = Poll::vote($poll['id'], (int)$optionId, $clientIP);
        
        if ($result['success']) {
            // Format response for JS
            $updatedPoll = $result['poll'];
            $updatedPoll['id'] = $updatedPoll['slug'];
            foreach ($updatedPoll['options'] as &$opt) {
                $opt['id'] = 'option_' . $opt['id'];
                $opt['text'] = $opt['option_text'];
            }
            
            echo json_encode([
                'ok' => true, 
                'message' => 'Vot înregistrat cu succes!',
                'poll' => $updatedPoll
            ]);
        } else {
            throw new Exception($result['error']);
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
?>
