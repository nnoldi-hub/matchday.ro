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

echo "<h2>Debug Champions League</h2>";

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
