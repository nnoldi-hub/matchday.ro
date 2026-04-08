<?php
/**
 * Save Post - Database version
 * MatchDay.ro
 */
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Category.php');
require_once(__DIR__ . '/../includes/Logger.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

try {
    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        throw new Exception('Token de securitate invalid.');
    }
    
    // Rate limiting for post creation
    $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::rateLimitCheck("post_$userIP", 10, 300)) {
        throw new Exception('Prea multe articole create. Încearcă din nou în 5 minute.');
    }
    
    // Input validation
    $title = Validator::required($_POST['title'] ?? '', 'Titlul');
    $title = Validator::maxLength($title, 200, 'Titlul');
    
    $date = Validator::required($_POST['date'] ?? '', 'Data');
    $date = Validator::date($date, 'Data');
    
    $tags = trim($_POST['tags'] ?? '');
    $tags = Validator::maxLength($tags, 500, 'Tagurile');
    
    $category = Validator::required($_POST['category'] ?? '', 'Categoria');
    // Validate category exists in database
    $categoryData = Category::getBySlug($category);
    if (!$categoryData) {
        throw new Exception('Categoria selectată nu este validă.');
    }
    
    $cover = trim($_POST['cover'] ?? '');
    if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
        $cover = '';
    }
    
    $content = Validator::required($_POST['content'] ?? '', 'Conținutul');
    $content = Validator::maxLength($content, 100000, 'Conținutul');
    
    // Sanitize content (allow safe HTML)
    $allowedTags = '<p><br><strong><em><b><i><u><a><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><img><div><span><table><tr><td><th><thead><tbody>';
    $content = strip_tags($content, $allowedTags);
    
    // Handle file upload
    if ($cover === '' && isset($_FILES['cover_upload']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])) {
        $uploadFile = $_FILES['cover_upload'];
        
        if ($uploadFile['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('Imaginea este prea mare. Maximum ' . round(MAX_UPLOAD_SIZE/1024/1024, 1) . 'MB.');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Tip de fișier neacceptat. Folosește JPG, PNG, WebP sau GIF.');
        }
        
        $ext = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExts)) {
            $ext = 'jpg';
        }
        
        $destName = date('Y-m-d') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../assets/uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $destPath = $uploadDir . $destName;
        
        if (move_uploaded_file($uploadFile['tmp_name'], $destPath)) {
            $cover = '/assets/uploads/' . $destName;
        } else {
            throw new Exception('Eroare la încărcarea imaginii.');
        }
    }
    
    // Generate excerpt
    $plain = preg_replace('/\s+/', ' ', strip_tags($content));
    $excerpt = mb_substr($plain, 0, 200) . (mb_strlen($plain) > 200 ? '…' : '');
    
    // Process tags
    $tagsArr = array_filter(array_map('trim', explode(',', $tags)));
    $tagsArr = array_slice($tagsArr, 0, 10);
    
    // Match result data
    $isMatchResult = isset($_POST['is_match_result']) ? 1 : 0;
    $homeTeam = trim($_POST['home_team'] ?? '');
    $awayTeam = trim($_POST['away_team'] ?? '');
    $homeScore = is_numeric($_POST['home_score'] ?? '') ? (int)$_POST['home_score'] : null;
    $awayScore = is_numeric($_POST['away_score'] ?? '') ? (int)$_POST['away_score'] : null;
    $matchCompetition = trim($_POST['match_competition'] ?? '');
    
    // Create post in database
    $postId = Post::create([
        'title' => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'category' => $category,
        'cover_image' => $cover,
        'tags' => $tagsArr,
        'status' => 'published',
        'author' => 'Admin',
        'is_match_result' => $isMatchResult,
        'home_team' => $homeTeam,
        'away_team' => $awayTeam,
        'home_score' => $homeScore,
        'away_score' => $awayScore,
        'match_competition' => $matchCompetition
    ]);
    
    if (!$postId) {
        throw new Exception('Eroare la salvarea articolului în baza de date.');
    }
    
    // Clear cache
    if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
        Cache::clear();
    }
    
    // Audit log the action
    $userId = $_SESSION['user_id'] ?? 0;
    Logger::audit('POST_CREATE', $userId, [
        'post_id' => $postId,
        'title' => $title,
        'category' => $category
    ]);
    
    $_SESSION['flash_success'] = 'Articolul a fost creat cu succes!';
    header('Location: posts.php');
    exit;
    
} catch (Exception $e) {
    error_log("Post creation error: " . $e->getMessage());
    $errorMsg = urlencode($e->getMessage());
    header("Location: new-post.php?error=$errorMsg");
    exit;
}
