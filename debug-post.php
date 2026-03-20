<?php
/**
 * Debug Post Display - Afișează erorile detaliate
 * ȘTERGE ACEST FIȘIER DUPĂ REZOLVARE!
 */

// Activează afișarea erorilor
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>\n";
echo "=== DEBUG POST.PHP ===\n\n";

// Test 1: Config
echo "[1] Loading config...\n";
try {
    require_once(__DIR__ . '/config/config.php');
    echo "    ✓ Config loaded OK\n";
    echo "    SITE_NAME: " . SITE_NAME . "\n";
    echo "    BASE_URL: " . BASE_URL . "\n";
} catch (Throwable $e) {
    echo "    ✗ Config ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 2: Database
echo "\n[2] Loading database...\n";
try {
    require_once(__DIR__ . '/config/database.php');
    echo "    ✓ Database file loaded\n";
    
    $db = Database::getInstance();
    echo "    ✓ Database connection OK\n";
    echo "    MySQL: " . (Database::isMySQL() ? 'Yes' : 'No (SQLite)') . "\n";
} catch (Throwable $e) {
    echo "    ✗ Database ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 3: Post model
echo "\n[3] Loading Post model...\n";
try {
    require_once(__DIR__ . '/includes/Post.php');
    echo "    ✓ Post.php loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Post ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 4: Comment model
echo "\n[4] Loading Comment model...\n";
try {
    require_once(__DIR__ . '/includes/Comment.php');
    echo "    ✓ Comment.php loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Comment ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 5: Stats model
echo "\n[5] Loading Stats model...\n";
try {
    require_once(__DIR__ . '/includes/Stats.php');
    echo "    ✓ Stats.php loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Stats ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 6: Get post by slug
$testSlug = $_GET['slug'] ?? 'concluzii-dupa-returul-optimilor-de-finala-din-champions-league';
echo "\n[6] Testing Post::getBySlug('$testSlug')...\n";
try {
    $post = Post::getBySlug($testSlug);
    if ($post) {
        echo "    ✓ Post found!\n";
        echo "    ID: " . $post['id'] . "\n";
        echo "    Title: " . $post['title'] . "\n";
        echo "    Status: " . $post['status'] . "\n";
        echo "    Category: " . ($post['category_slug'] ?? 'N/A') . "\n";
    } else {
        echo "    ✗ Post NOT found\n";
    }
} catch (Throwable $e) {
    echo "    ✗ Post query ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 7: Stats tracking
echo "\n[7] Testing Stats::trackView()...\n";
try {
    if (isset($post) && $post) {
        Stats::trackView($post['id'], 'post');
        echo "    ✓ Stats tracked OK\n";
    } else {
        echo "    - Skipped (no post)\n";
    }
} catch (Throwable $e) {
    echo "    ✗ Stats ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 8: Categories config
echo "\n[8] Loading categories config...\n";
try {
    $categories = require(__DIR__ . '/config/categories.php');
    echo "    ✓ Categories loaded: " . count($categories) . " categories\n";
    echo "    Keys: " . implode(', ', array_keys($categories)) . "\n";
} catch (Throwable $e) {
    echo "    ✗ Categories ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 9: Related posts
echo "\n[9] Testing Post::getSimilar()...\n";
try {
    if (isset($post) && $post) {
        $related = Post::getSimilar($post['id'], $post['category_slug'] ?? '', $post['tags'] ?? '', 4);
        echo "    ✓ Related posts: " . count($related) . " found\n";
    } else {
        echo "    - Skipped (no post)\n";
    }
} catch (Throwable $e) {
    echo "    ✗ Related posts ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 10: Comments
echo "\n[10] Testing Comment::getByPost()...\n";
try {
    if (isset($post) && $post) {
        $comments = Comment::getByPost($post['slug']);
        echo "    ✓ Comments: " . count($comments) . " found\n";
    } else {
        echo "    - Skipped (no post)\n";
    }
} catch (Throwable $e) {
    echo "    ✗ Comments ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 11: Header include
echo "\n[11] Testing header.php include...\n";
try {
    // Set required variables
    $pageTitle = $post['title'] ?? 'Test';
    $pageDescription = $post['excerpt'] ?? 'Test description';
    $pageKeywords = ['test'];
    $pageImage = '/assets/images/logo.png';
    $pageType = 'article';
    $publishedDate = $post['published_at'] ?? date('Y-m-d');
    $modifiedDate = $post['updated_at'] ?? date('Y-m-d');
    $articleAuthor = 'Admin';
    $articleCategory = $post['category_name'] ?? '';
    $articleTags = [];
    $breadcrumbs = [
        ['name' => 'Acasă', 'url' => '/index.php'],
        ['name' => 'Test']
    ];
    
    ob_start();
    include(__DIR__ . '/includes/header.php');
    $headerOutput = ob_get_clean();
    echo "    ✓ Header included OK (output: " . strlen($headerOutput) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "    ✗ Header ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";
