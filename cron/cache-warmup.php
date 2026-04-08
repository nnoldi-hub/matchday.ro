<?php
/**
 * Cache Warmup Script
 * MatchDay.ro - Pre-cache important pages
 * 
 * Usage: php cron/cache-warmup.php
 * Cron: 0 * * * * php /path/to/cron/cache-warmup.php
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/cache.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Category.php');

// Only run from CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

echo "MatchDay.ro Cache Warmup\n";
echo "========================\n\n";

$startTime = microtime(true);
$warmedCount = 0;
$errors = [];

// 1. Warm homepage data
echo "1. Warming homepage data...\n";
try {
    $recentPosts = Post::getRecent(10);
    Cache::set('homepage_recent_posts', $recentPosts, 1800);
    echo "   ✓ Recent posts cached (" . count($recentPosts) . " articles)\n";
    $warmedCount++;
} catch (Exception $e) {
    $errors[] = "Homepage: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 2. Warm category data
echo "\n2. Warming categories...\n";
try {
    $categories = Category::getWithCounts();
    Cache::set('categories_with_counts', $categories, 3600);
    echo "   ✓ Categories cached (" . count($categories) . " categories)\n";
    $warmedCount++;
    
    // Cache each category's recent posts
    foreach ($categories as $category) {
        $posts = Post::getByCategory($category['id'], 5);
        Cache::set('category_' . $category['slug'] . '_posts', $posts, 1800);
        echo "   ✓ {$category['name']}: " . count($posts) . " posts\n";
        $warmedCount++;
    }
} catch (Exception $e) {
    $errors[] = "Categories: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 3. Warm popular posts
echo "\n3. Warming popular posts...\n";
try {
    $db = Database::getInstance();
    $popularPosts = $db->query(
        "SELECT id, title, slug, views FROM posts 
         WHERE status = 'published' 
         ORDER BY views DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    Cache::set('popular_posts', $popularPosts, 3600);
    echo "   ✓ Popular posts cached (" . count($popularPosts) . " articles)\n";
    $warmedCount++;
} catch (Exception $e) {
    $errors[] = "Popular posts: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 4. Warm sitemap data
echo "\n4. Warming sitemap data...\n";
try {
    $allPosts = $db->query(
        "SELECT slug, updated_at FROM posts 
         WHERE status = 'published' 
         ORDER BY updated_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    Cache::set('sitemap_posts', $allPosts, 7200);
    echo "   ✓ Sitemap data cached (" . count($allPosts) . " URLs)\n";
    $warmedCount++;
} catch (Exception $e) {
    $errors[] = "Sitemap: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 5. Warm stats for dashboard
echo "\n5. Warming stats data...\n";
try {
    $stats = [
        'total_posts' => Post::getCount(),
        'total_views' => $db->query("SELECT SUM(views) as total FROM posts")->fetch()['total'] ?? 0,
        'posts_today' => $db->query(
            "SELECT COUNT(*) as count FROM posts 
             WHERE status = 'published' AND DATE(created_at) = CURDATE()"
        )->fetch()['count'] ?? 0,
    ];
    
    Cache::set('dashboard_stats', $stats, 900);
    echo "   ✓ Dashboard stats cached\n";
    $warmedCount++;
} catch (Exception $e) {
    $errors[] = "Stats: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 6. Clean expired cache files
echo "\n6. Cleaning expired cache...\n";
try {
    $cacheDir = __DIR__ . '/../data/cache/';
    $cleaned = 0;
    
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '*.cache') as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);
            
            if ($data !== false && isset($data['expires']) && time() > $data['expires']) {
                unlink($file);
                $cleaned++;
            }
        }
    }
    
    echo "   ✓ Cleaned $cleaned expired cache files\n";
} catch (Exception $e) {
    $errors[] = "Cache cleanup: " . $e->getMessage();
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Summary
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n========================\n";
echo "Cache Warmup Complete!\n";
echo "========================\n";
echo "Items warmed: $warmedCount\n";
echo "Duration: {$duration}s\n";

if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nDone.\n";
