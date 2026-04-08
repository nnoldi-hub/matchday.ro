<?php
/**
 * Badges API
 * MatchDay.ro - Handle badge checking and awarding
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Badge.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $userId = Security::sanitizeInput($_GET['id'] ?? '');
    
    if (empty($userId)) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    switch ($action) {
        case 'stats':
            $badges = Badge::getUserBadges($userId);
            $points = Badge::getUserPoints($userId);
            $rank = Badge::getUserRank($userId);
            
            echo json_encode([
                'badges' => $badges,
                'points' => $points,
                'rank' => $rank,
                'badge_count' => count($badges)
            ]);
            break;
            
        case 'leaderboard':
            $limit = min(50, max(5, (int)($_GET['limit'] ?? 10)));
            $leaderboard = Badge::getLeaderboard($limit);
            echo json_encode(['leaderboard' => $leaderboard]);
            break;
            
        case 'all':
            echo json_encode(['badges' => Badge::getAllBadges()]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    $userId = Security::sanitizeInput($input['id'] ?? '');
    
    if (empty($userId)) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    switch ($action) {
        case 'check':
            $activity = $input['activity'] ?? [];
            $newBadges = Badge::checkAndAward($userId, $activity);
            
            echo json_encode([
                'new_badges' => $newBadges,
                'total_points' => Badge::getUserPoints($userId)
            ]);
            break;
            
        case 'award':
            // Admin-only function
            session_start();
            if (!isset($_SESSION['admin_logged_in'])) {
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            
            $badgeId = Security::sanitizeInput($input['badge_id'] ?? '');
            $success = Badge::award($userId, $badgeId);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Badge awarded' : 'Badge already earned or invalid'
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
