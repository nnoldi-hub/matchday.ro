<?php
/**
 * Debug Champions League category issue
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Category.php');

echo "<h2>Debug Champions League - Articol Raw Content</h2>";

// Check for specific article ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $post = Post::getById($id);
    if ($post) {
        echo "<h3>Articol ID: $id</h3>";
        echo "<h4>Titlu: " . htmlspecialchars($post['title']) . "</h4>";
        echo "<h4>Content RAW (escaped):</h4>";
        echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:500px;'>" . htmlspecialchars($post['content']) . "</pre>";
        echo "<h4>Content LENGTH: " . strlen($post['content']) . " bytes</h4>";
        
        // Check for unclosed tags
        echo "<h4>HTML Tag Analysis:</h4>";
        $content = $post['content'];
        preg_match_all('/<([a-z0-9]+)[^>]*>/i', $content, $openTags);
        preg_match_all('/<\/([a-z0-9]+)>/i', $content, $closeTags);
        
        $openCounts = array_count_values($openTags[1]);
        $closeCounts = array_count_values($closeTags[1]);
        
        echo "<pre>";
        echo "Open tags: " . print_r($openCounts, true);
        echo "Close tags: " . print_r($closeCounts, true);
        
        // Find mismatches
        $allTags = array_unique(array_merge(array_keys($openCounts), array_keys($closeCounts)));
        $selfClosing = ['br', 'img', 'hr', 'input', 'meta', 'link'];
        foreach ($allTags as $tag) {
            if (in_array(strtolower($tag), $selfClosing)) continue;
            $open = $openCounts[$tag] ?? 0;
            $close = $closeCounts[$tag] ?? 0;
            if ($open !== $close) {
                echo "<strong style='color:red;'>MISMATCH: &lt;$tag&gt; open=$open, close=$close</strong>\n";
            }
        }
        echo "</pre>";
        exit;
    }
}

// List all Champions League posts with IDs
echo "<h3>Toate articolele Champions League (click pentru detalii):</h3>";
$posts = Post::getPublished(1, 20, 'champions-league', null);
echo "<ul>";
foreach ($posts as $p) {
    echo "<li><a href='?id={$p['id']}'>{$p['title']}</a> (ID: {$p['id']}, Creat: {$p['created_at']})</li>";
}
echo "</ul>";

echo "<hr><h2>Debug Champions League</h2>";

// Check Champions League category
echo "<h3>1. Category Check</h3>";
$cl = Category::getBySlug('champions-league');
echo "<pre>Category::getBySlug('champions-league'):\n";
print_r($cl);
echo "</pre>";

// Check all categories
echo "<h3>2. All Categories</h3>";
$allCats = Category::getAll();
echo "<pre>Category::getAll():\n";
print_r($allCats);
echo "</pre>";

// Check posts in Champions League
echo "<h3>3. Posts in Champions League</h3>";
$posts = Post::getPublished(1, 10, 'champions-league', null);
echo "<pre>Posts in champions-league:\n";
print_r($posts);
echo "</pre>";

// Check BASE_URL
echo "<h3>4. BASE_URL</h3>";
echo "<pre>BASE_URL: " . BASE_URL . "</pre>";
echo "<pre>Footer JS path: " . BASE_URL . "/assets/js/bootstrap.bundle.min.js</pre>";

// Check if there's any error with Category::getAll in header context
echo "<h3>5. Test Header Categories</h3>";
ob_start();
try {
    $navCategories = Category::getAll();
    if (empty($navCategories)) {
        $configCats = require(__DIR__ . '/config/categories.php');
        foreach ($configCats as $key => $cat) {
            $navCategories[] = array_merge($cat, ['slug' => $key]);
        }
        echo "Using config categories fallback<br>";
    } else {
        echo "Using database categories<br>";
    }
    echo "<pre>Nav Categories:\n";
    print_r($navCategories);
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>ERROR: " . $e->getMessage() . "</pre>";
}

// Test footer.php path construction
echo "<h3>6. Asset Path Debug</h3>";
$base = BASE_URL;
$admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$assetBase = $base . ($admin ? '../' : '');
echo "<pre>base: $base\nassetBase: $assetBase\nadmin: " . ($admin ? 'true' : 'false') . "</pre>";

echo "<h3>7. Check for HTML Static Files</h3>";
$htmlFiles = glob(__DIR__ . '/posts/*.html');
echo "<pre>Static HTML files in posts/:\n";
foreach ($htmlFiles as $file) {
    echo basename($file) . "\n";
}
echo "</pre>";
