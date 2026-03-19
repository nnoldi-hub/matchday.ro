<?php
/**
 * Database Migration Script
 * MatchDay.ro - Migrate from file-based to MySQL/SQLite
 * 
 * Run this script ONCE to migrate existing data
 * Usage: php migrate.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==============================================\n";
echo "  MatchDay.ro - Database Migration Script\n";
echo "==============================================\n\n";

// Load configuration
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

// Initialize database connection first (sets isMySQL flag)
Database::getInstance();

// Detect database type
$isMySQL = Database::isMySQL();
$dbType = $isMySQL ? 'MySQL' : 'SQLite';
echo "Database Type: $dbType\n\n";

// Helper for INSERT IGNORE (different syntax for MySQL vs SQLite)
function insertIgnore($columns, $table) {
    global $isMySQL;
    $cols = implode(', ', $columns);
    $placeholders = ':' . implode(', :', $columns);
    
    if ($isMySQL) {
        return "INSERT IGNORE INTO $table ($cols) VALUES ($placeholders)";
    } else {
        return "INSERT OR IGNORE INTO $table ($cols) VALUES ($placeholders)";
    }
}

// Stats
$stats = [
    'categories' => 0,
    'posts' => 0,
    'polls' => 0,
    'poll_options' => 0,
    'comments' => 0,
    'users' => 0
];

// Step 1: Initialize database schema
echo "[1/6] Initializing database schema...\n";
if (Database::initSchema()) {
    echo "      ✓ Schema created successfully\n";
} else {
    die("      ✗ Failed to create schema\n");
}

// Step 2: Migrate categories
echo "\n[2/6] Migrating categories...\n";
$categoriesFile = __DIR__ . '/config/categories.php';
if (file_exists($categoriesFile)) {
    $categories = require($categoriesFile);
    
    if (is_array($categories)) {
        $sql = insertIgnore(['slug', 'name', 'color', 'icon', 'sort_order'], 'categories');
        $stmt = Database::getInstance()->prepare($sql);
        
        $order = 0;
        foreach ($categories as $slug => $data) {
            $name = is_array($data) ? ($data['name'] ?? ucfirst($slug)) : ucfirst($slug);
            $color = is_array($data) ? ($data['color'] ?? '#007bff') : '#007bff';
            $icon = is_array($data) ? ($data['icon'] ?? 'fas fa-folder') : 'fas fa-folder';
            
            $stmt->execute([
                'slug' => $slug,
                'name' => $name,
                'color' => $color,
                'icon' => $icon,
                'sort_order' => $order++
            ]);
            $stats['categories']++;
            echo "      ✓ Category: $slug\n";
        }
    }
}
echo "      Total: {$stats['categories']} categories\n";

// Step 3: Migrate posts from HTML files
echo "\n[3/6] Migrating posts from HTML files...\n";
$postsDir = __DIR__ . '/posts';
if (is_dir($postsDir)) {
    $files = array_filter(scandir($postsDir), fn($f) => substr($f, -5) === '.html' && $f !== 'index.html');
    
    $sql = insertIgnore(['title', 'slug', 'content', 'excerpt', 'category_slug', 'cover_image', 'tags', 'status', 'author', 'published_at', 'created_at'], 'posts');
    $stmt = Database::getInstance()->prepare($sql);
    
    foreach ($files as $file) {
        $path = $postsDir . '/' . $file;
        $html = file_get_contents($path);
        
        // Parse david-meta JSON from HTML comment
        $meta = [
            'title' => pathinfo($file, PATHINFO_FILENAME),
            'slug' => pathinfo($file, PATHINFO_FILENAME),
            'date' => date('Y-m-d H:i:s', filemtime($path)),
            'excerpt' => '',
            'cover' => '',
            'category' => '',
            'tags' => [],
            'author' => 'Admin'
        ];
        
        if (preg_match('/<!--\s*david-meta:(.*?)-->/', $html, $matches)) {
            $jsonMeta = json_decode(trim($matches[1]), true);
            if (is_array($jsonMeta)) {
                $meta = array_merge($meta, $jsonMeta);
            }
        }
        
        // Extract content (remove the meta comment)
        $content = preg_replace('/<!--\s*david-meta:.*?-->/', '', $html);
        $content = trim($content);
        
        // Parse date from filename if possible (YYYY-MM-DD-slug.html)
        if (preg_match('/^(\d{4}-\d{2}-\d{2})-/', $file, $dateMatch)) {
            $meta['date'] = $dateMatch[1] . ' 12:00:00';
        }
        
        try {
            $stmt->execute([
                'title' => $meta['title'],
                'slug' => $meta['slug'],
                'content' => $content,
                'excerpt' => $meta['excerpt'] ?? '',
                'category_slug' => $meta['category'] ?? null,
                'cover_image' => $meta['cover'] ?? '',
                'tags' => is_array($meta['tags']) ? implode(',', $meta['tags']) : ($meta['tags'] ?? ''),
                'status' => 'published',
                'author' => $meta['author'] ?? 'Admin',
                'published_at' => $meta['date'],
                'created_at' => $meta['date']
            ]);
            $stats['posts']++;
            echo "      ✓ Post: {$meta['title']}\n";
        } catch (Exception $e) {
            echo "      ✗ Failed: {$meta['title']} - {$e->getMessage()}\n";
        }
    }
}
echo "      Total: {$stats['posts']} posts\n";

// Step 4: Migrate polls from JSON files
echo "\n[4/6] Migrating polls from JSON files...\n";
$pollsDir = __DIR__ . '/data/polls';
if (is_dir($pollsDir)) {
    $pollFiles = array_filter(scandir($pollsDir), fn($f) => substr($f, -5) === '.json');
    
    $pollSql = insertIgnore(['slug', 'question', 'description', 'active', 'created_at'], 'polls');
    
    foreach ($pollFiles as $pf) {
        $pollData = json_decode(file_get_contents($pollsDir . '/' . $pf), true);
        if (!$pollData) continue;
        
        try {
            // Insert poll
            $pollId = Database::insert($pollSql, [
                'slug' => $pollData['id'] ?? pathinfo($pf, PATHINFO_FILENAME),
                'question' => $pollData['question'] ?? 'Sondaj',
                'description' => $pollData['description'] ?? '',
                'active' => $pollData['active'] ?? 1,
                'created_at' => $pollData['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            if ($pollId && !empty($pollData['options'])) {
                // Insert options
                $optStmt = Database::getInstance()->prepare(
                    "INSERT INTO poll_options (poll_id, option_text, votes, sort_order) 
                     VALUES (:poll_id, :option_text, :votes, :sort_order)"
                );
                
                foreach ($pollData['options'] as $index => $option) {
                    $text = is_array($option) ? ($option['text'] ?? '') : $option;
                    $votes = is_array($option) ? ($option['votes'] ?? 0) : 0;
                    
                    $optStmt->execute([
                        'poll_id' => $pollId,
                        'option_text' => $text,
                        'votes' => $votes,
                        'sort_order' => $index
                    ]);
                    $stats['poll_options']++;
                }
                
                $stats['polls']++;
                echo "      ✓ Poll: {$pollData['question']}\n";
            }
        } catch (Exception $e) {
            echo "      ✗ Failed poll: {$pf} - {$e->getMessage()}\n";
        }
    }
}
echo "      Total: {$stats['polls']} polls, {$stats['poll_options']} options\n";

// Step 5: Migrate comments from JSON files
echo "\n[5/6] Migrating comments from JSON files...\n";
$commentsDir = __DIR__ . '/data/comments';
if (is_dir($commentsDir)) {
    $commentFiles = array_filter(scandir($commentsDir), fn($f) => substr($f, -5) === '.json' && strpos($f, 'recent_') === false);
    
    $stmt = Database::getInstance()->prepare(
        "INSERT INTO comments (post_slug, author_name, content, approved, created_at) 
         VALUES (:post_slug, :author_name, :content, :approved, :created_at)"
    );
    
    foreach ($commentFiles as $cf) {
        $postSlug = pathinfo($cf, PATHINFO_FILENAME);
        $comments = json_decode(file_get_contents($commentsDir . '/' . $cf), true);
        
        if (is_array($comments)) {
            foreach ($comments as $comment) {
                try {
                    $stmt->execute([
                        'post_slug' => $postSlug,
                        'author_name' => $comment['author'] ?? $comment['name'] ?? 'Anonim',
                        'content' => $comment['content'] ?? $comment['text'] ?? $comment['message'] ?? '',
                        'approved' => $comment['approved'] ?? 1,
                        'created_at' => $comment['date'] ?? $comment['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $stats['comments']++;
                } catch (Exception $e) {
                    // Skip duplicates
                }
            }
        }
    }
}
echo "      Total: {$stats['comments']} comments\n";

// Step 6: Create default admin user
echo "\n[6/6] Creating admin user...\n";
$adminPassword = 'matchday2024';
$adminHash = password_hash($adminPassword, PASSWORD_ARGON2ID);

$userSql = insertIgnore(['username', 'email', 'password_hash', 'role'], 'users');

try {
    Database::insert($userSql, [
        'username' => 'admin',
        'email' => 'admin@matchday.ro',
        'password_hash' => $adminHash,
        'role' => 'admin'
    ]);
    $stats['users']++;
    echo "      ✓ Admin user created (username: admin, password: $adminPassword)\n";
} catch (Exception $e) {
    echo "      ✓ Admin user already exists\n";
}

// Summary
echo "\n==============================================\n";
echo "  Migration Complete!\n";
echo "==============================================\n";
echo "  Database:      $dbType\n";
echo "  Categories:    {$stats['categories']}\n";
echo "  Posts:         {$stats['posts']}\n";
echo "  Polls:         {$stats['polls']} ({$stats['poll_options']} options)\n";
echo "  Comments:      {$stats['comments']}\n";
echo "  Users:         {$stats['users']}\n";
echo "==============================================\n";

if ($isMySQL) {
    echo "  Host: " . MYSQL_HOST . "\n";
    echo "  Database: " . MYSQL_DB . "\n";
} else {
    echo "  Database: " . SQLITE_PATH . "\n";
}
echo "==============================================\n\n";

// Verify database
$postCount = Database::fetchValue("SELECT COUNT(*) FROM posts");
$pollCount = Database::fetchValue("SELECT COUNT(*) FROM polls");
echo "Verification:\n";
echo "  - Posts in DB: $postCount\n";
echo "  - Polls in DB: $pollCount\n";

echo "\n✓ Migration script finished successfully!\n";
echo "  You can now update index.php to use the database.\n\n";
