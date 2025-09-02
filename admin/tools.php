<?php
// Admin tools
session_start();
require_once(__DIR__ . '/../config/config.php');

if (empty($_SESSION['david_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'clear_cache':
        try {
            Cache::clear();
            echo json_encode(['success' => true, 'message' => 'Cache cleared successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_stats':
        try {
            $postsDir = __DIR__ . '/../posts';
            $files = array_filter(scandir($postsDir), fn($f) => substr($f, -5) === '.html');
            
            $stats = [
                'total_posts' => count($files),
                'cache_enabled' => CACHE_ENABLED,
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'upload_max' => ini_get('upload_max_filesize')
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
