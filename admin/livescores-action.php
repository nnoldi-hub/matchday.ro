<?php
/**
 * Live Scores Admin Actions
 * MatchDay.ro - AJAX handler for quick score updates
 */

header('Content-Type: application/json');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/../includes/LiveScores.php');

// Check admin access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'quick_score':
            $matchId = (int)($input['match_id'] ?? 0);
            $team = $input['team'] ?? '';
            
            if (!$matchId || !in_array($team, ['home', 'away'])) {
                throw new Exception('Invalid parameters');
            }
            
            // Get current match
            $match = Database::fetch(
                "SELECT * FROM live_matches WHERE id = :id",
                ['id' => $matchId]
            );
            
            if (!$match) {
                throw new Exception('Match not found');
            }
            
            // Increment score
            $column = $team === 'home' ? 'home_score' : 'away_score';
            Database::execute(
                "UPDATE live_matches SET {$column} = {$column} + 1, status = 'live' WHERE id = :id",
                ['id' => $matchId]
            );
            
            LiveScores::clearCache();
            
            echo json_encode([
                'success' => true,
                'message' => 'Score updated'
            ]);
            break;
            
        case 'update_status':
            $matchId = (int)($input['match_id'] ?? 0);
            $status = $input['status'] ?? '';
            $minute = $input['minute'] ?? null;
            
            if (!$matchId || !$status) {
                throw new Exception('Invalid parameters');
            }
            
            Database::execute(
                "UPDATE live_matches SET status = :status, minute = :minute WHERE id = :id",
                ['id' => $matchId, 'status' => $status, 'minute' => $minute]
            );
            
            LiveScores::clearCache();
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
