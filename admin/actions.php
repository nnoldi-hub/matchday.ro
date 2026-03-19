<?php
require_once(__DIR__ . '/../config/config.php');

// Verifică autentificarea admin
session_start();
if (!isset($_SESSION['david_logged']) || !$_SESSION['david_logged']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Neautorizat']);
    exit;
}

// Verifică metoda și acțiunea
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă nepermisă']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'delete_post':
            deletePost();
            break;
        case 'clear_cache':
            clearCache();
            break;
        default:
            throw new Exception('Acțiune necunoscută');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function deletePost() {
    $filename = $_POST['filename'] ?? '';
    
    if (empty($filename)) {
        throw new Exception('Numele fișierului este necesar');
    }
    
    // Sanitizare și validare
    $filename = Security::sanitizeInput($filename);
    if (!preg_match('/^[\w\-\.]+\.html$/', $filename)) {
        throw new Exception('Nume de fișier invalid');
    }
    
    $postsDir = __DIR__ . '/../posts';
    $filePath = $postsDir . '/' . $filename;
    
    // Verifică dacă fișierul există
    if (!file_exists($filePath)) {
        throw new Exception('Fișierul nu există');
    }
    
    // Verifică că este în directorul posts (securitate)
    $realPath = realpath($filePath);
    $realPostsDir = realpath($postsDir);
    if (strpos($realPath, $realPostsDir) !== 0) {
        throw new Exception('Cale de fișier invalidă');
    }
    
    // Șterge fișierul
    if (!unlink($filePath)) {
        throw new Exception('Nu s-a putut șterge fișierul');
    }
    
    // Șterge și comentariile asociate dacă există
    $slug = pathinfo($filename, PATHINFO_FILENAME);
    $commentsFile = __DIR__ . '/../data/comments/' . $slug . '.json';
    if (file_exists($commentsFile)) {
        @unlink($commentsFile);
    }
    
    // Golește cache-ul pentru refresh
    clearCache();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Articol șters cu succes',
        'filename' => $filename
    ]);
}

function clearCache() {
    $cacheDir = __DIR__ . '/../data/cache';
    
    if (!is_dir($cacheDir)) {
        echo json_encode(['success' => true, 'message' => 'Cache-ul este deja gol']);
        return;
    }
    
    $files = glob($cacheDir . '/*.cache');
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && unlink($file)) {
            $deleted++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Cache golit cu succes. $deleted fișiere șterse."
    ]);
}
?>
