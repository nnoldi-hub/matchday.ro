<?php
/**
 * Debug/Diagnostic Script
 * MatchDay.ro - Use to find 500 errors
 * DELETE THIS FILE AFTER USE!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace;'>\n";
echo "=== MatchDay.ro Diagnostic ===\n\n";

// Check PHP version
echo "1. PHP Version: " . PHP_VERSION . "\n";

// Check if files exist
$files = [
    'config/config.php',
    'config/database.php',
    'includes/Post.php',
    'includes/Comment.php',
    'includes/seo.php',
    'includes/header.php',
    'includes/footer.php'
];

echo "\n2. File Check:\n";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $status = file_exists($path) ? '✓' : '✗ MISSING';
    echo "   $file: $status\n";
}

// Try to load config
echo "\n3. Loading Config...\n";
try {
    require_once(__DIR__ . '/config/config.php');
    echo "   ✓ config.php loaded\n";
    echo "   SITE_NAME: " . (defined('SITE_NAME') ? SITE_NAME : 'NOT DEFINED') . "\n";
    echo "   BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

// Try to connect to database
echo "\n4. Database Connection...\n";
try {
    require_once(__DIR__ . '/config/database.php');
    $db = Database::getInstance();
    echo "   ✓ Database connected\n";
    echo "   Type: " . (Database::isMySQL() ? 'MySQL' : 'SQLite') . "\n";
    
    // Test simple query
    $count = Database::fetchValue("SELECT COUNT(*) FROM posts");
    echo "   Total posts: $count\n";
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test Comment check
echo "\n5. Testing Comment columns...\n";
try {
    // Check if parent_id column exists
    $sql = "SHOW COLUMNS FROM comments LIKE 'parent_id'";
    $result = Database::fetch($sql);
    echo "   parent_id column: " . ($result ? '✓ EXISTS' : '✗ MISSING') . "\n";
    
    $sql = "SHOW COLUMNS FROM comments LIKE 'likes'";
    $result = Database::fetch($sql);
    echo "   likes column: " . ($result ? '✓ EXISTS' : '✗ MISSING') . "\n";
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    
    // Try SQLite equivalent
    try {
        $cols = Database::fetchAll("PRAGMA table_info(comments)");
        echo "   Comment columns (SQLite):\n";
        foreach ($cols as $col) {
            echo "     - " . $col['name'] . "\n";
        }
    } catch (Throwable $e2) {
        echo "   Can't read columns: " . $e2->getMessage() . "\n";
    }
}

// Test loading Post.php
echo "\n6. Loading Post class...\n";
try {
    require_once(__DIR__ . '/includes/Post.php');
    echo "   ✓ Post.php loaded\n";
    
    // Get latest post
    $posts = Post::getLatest(1);
    if ($posts) {
        echo "   Latest post: " . $posts[0]['title'] . "\n";
    }
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test loading Comment.php
echo "\n7. Loading Comment class...\n";
try {
    require_once(__DIR__ . '/includes/Comment.php');
    echo "   ✓ Comment.php loaded\n";
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test getting comments
echo "\n8. Testing Comment::getByPost...\n";
try {
    $posts = Post::getLatest(1);
    if ($posts) {
        $slug = $posts[0]['slug'];
        echo "   Testing with slug: $slug\n";
        $comments = Comment::getByPost($slug);
        echo "   ✓ Got " . count($comments) . " comments\n";
    }
} catch (Throwable $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// Check for required tables
echo "\n9. Required tables check...\n";
$tables = ['posts', 'users', 'categories', 'comments', 'polls', 'poll_options', 'poll_votes'];
foreach ($tables as $table) {
    try {
        $count = Database::fetchValue("SELECT COUNT(*) FROM $table");
        echo "   $table: ✓ ($count rows)\n";
    } catch (Throwable $e) {
        echo "   $table: ✗ MISSING or ERROR\n";
    }
}

// Check Phase 4 tables
echo "\n10. Phase 4 tables (optional)...\n";
$phase4Tables = ['user_badges', 'comment_likes', 'newsletter_logs', 'push_subscriptions'];
foreach ($phase4Tables as $table) {
    try {
        $count = Database::fetchValue("SELECT COUNT(*) FROM $table");
        echo "   $table: ✓ ($count rows)\n";
    } catch (Throwable $e) {
        echo "   $table: ✗ NOT CREATED (run migrate-phase4.php)\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n";
echo "</pre>";

// Show recommendation
echo "<div style='background:#ffe;padding:20px;margin:20px;border:2px solid #f90;'>";
echo "<h3>⚠️ IMPORTANT: Șterge acest fișier după debugging!</h3>";
echo "<p>Rulează: <code>rm debug-500.php</code></p>";
echo "</div>";
