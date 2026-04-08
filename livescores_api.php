<?php
/**
 * Live Scores API
 * MatchDay.ro - Returns live match data
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/LiveScores.php');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'live';
$competition = $_GET['competition'] ?? null;

try {
    switch ($action) {
        case 'live':
            // Get live matches
            $matches = LiveScores::getLiveMatches($competition);
            echo json_encode([
                'success' => true,
                'matches' => $matches,
                'count' => count($matches),
                'timestamp' => date('c')
            ]);
            break;
            
        case 'today':
            // Get today's matches
            $matches = LiveScores::getTodayMatches($competition);
            echo json_encode([
                'success' => true,
                'matches' => $matches,
                'count' => count($matches),
                'date' => date('Y-m-d'),
                'timestamp' => date('c')
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("LiveScores API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
