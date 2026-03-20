<?php
/**
 * Category Sync Script
 * Sincronizează categoriile din config/categories.php în baza de date
 * Rulează acest script o singură dată după deploy
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Category.php');

// Security check - doar admin sau CLI
if (php_sapi_name() !== 'cli' && empty($_SESSION['david_logged'])) {
    // Permite cu token secret
    if (($_GET['token'] ?? '') !== 'matchday2026sync') {
        http_response_code(403);
        die('Access denied');
    }
}

echo "<pre>\n";
echo "=== Category Sync Tool ===\n\n";

// Load categories from config
$configFile = __DIR__ . '/config/categories.php';
$categories = include $configFile;

echo "Categories in config: " . count($categories) . "\n";
foreach ($categories as $slug => $data) {
    echo "  - {$slug}: {$data['name']}\n";
}

echo "\n";

// Check database connection
try {
    $db = Database::getInstance();
    echo "Database connection: OK\n";
    echo "Database type: " . (Database::isMySQL() ? 'MySQL' : 'SQLite') . "\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check what's in database
echo "Categories in database:\n";
$dbCategories = Category::getAll();
if (empty($dbCategories)) {
    echo "  (none)\n";
} else {
    foreach ($dbCategories as $cat) {
        echo "  - {$cat['slug']}: {$cat['name']}\n";
    }
}

echo "\n";

// Sync
echo "Syncing...\n";
$count = Category::syncFromConfig();
echo "New categories added: {$count}\n\n";

// Verify
echo "Categories after sync:\n";
$dbCategories = Category::getAll();
foreach ($dbCategories as $cat) {
    echo "  - {$cat['slug']}: {$cat['name']}\n";
}

echo "\n=== Done! ===\n";
echo "</pre>";
