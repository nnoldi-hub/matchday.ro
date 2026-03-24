<?php
/**
 * Migration: Add parent_slug to categories table
 * Adds hierarchical support for categories (parent/child relationships)
 * Run this once to update existing databases
 */

require_once(__DIR__ . '/config/database.php');

echo "<h1>Migration: Clasamente Categories</h1>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $isMySQL = Database::isMySQL();
    
    echo "Database type: " . ($isMySQL ? "MySQL" : "SQLite") . "\n\n";
    
    // Step 1: Add parent_slug column if it doesn't exist
    echo "Step 1: Adding parent_slug column...\n";
    
    if ($isMySQL) {
        // MySQL - Check if column exists
        $result = $db->query("SHOW COLUMNS FROM categories LIKE 'parent_slug'");
        if ($result->rowCount() == 0) {
            $db->exec("ALTER TABLE categories ADD COLUMN parent_slug VARCHAR(100) DEFAULT NULL");
            $db->exec("CREATE INDEX idx_parent ON categories(parent_slug)");
            echo "✅ Column parent_slug added to MySQL\n";
        } else {
            echo "ℹ️ Column parent_slug already exists\n";
        }
    } else {
        // SQLite - Check if column exists
        $result = $db->query("PRAGMA table_info(categories)");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
        
        if (!in_array('parent_slug', $columns)) {
            $db->exec("ALTER TABLE categories ADD COLUMN parent_slug TEXT DEFAULT NULL");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_slug)");
            echo "✅ Column parent_slug added to SQLite\n";
        } else {
            echo "ℹ️ Column parent_slug already exists\n";
        }
    }
    
    // Step 2: Import new categories from config
    echo "\nStep 2: Importing new categories from config...\n";
    
    $configFile = __DIR__ . '/config/categories.php';
    $categories = include $configFile;
    $count = 0;
    $order = 100; // Start after existing categories
    
    foreach ($categories as $slug => $data) {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $existing = $stmt->fetch();
        
        $parentSlug = $data['parent'] ?? null;
        
        if (!$existing) {
            // Insert new category
            $stmt = $db->prepare("
                INSERT INTO categories (slug, name, description, color, icon, sort_order, parent_slug) 
                VALUES (:slug, :name, :description, :color, :icon, :sort_order, :parent_slug)
            ");
            $stmt->execute([
                'slug' => $slug,
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#007bff',
                'icon' => $data['icon'] ?? 'fas fa-folder',
                'sort_order' => $order,
                'parent_slug' => $parentSlug
            ]);
            echo "✅ Added: {$data['name']} (slug: $slug)" . ($parentSlug ? " [parent: $parentSlug]" : "") . "\n";
            $count++;
        } else {
            // Update existing category with parent_slug if needed
            if ($parentSlug) {
                $stmt = $db->prepare("UPDATE categories SET parent_slug = :parent_slug WHERE slug = :slug");
                $stmt->execute(['parent_slug' => $parentSlug, 'slug' => $slug]);
                echo "↺ Updated parent for: {$data['name']}\n";
            } else {
                echo "⏭️ Skipped (exists): {$data['name']}\n";
            }
        }
        $order++;
    }
    
    echo "\n✅ Migration complete! $count new categories added.\n";
    
    // Step 3: Show current structure
    echo "\n<h2>Current Category Structure:</h2>\n";
    
    $stmt = $db->query("SELECT slug, name, parent_slug, color FROM categories ORDER BY sort_order, name");
    $allCats = $stmt->fetchAll();
    
    // Group by parent
    $tree = [];
    $children = [];
    
    foreach ($allCats as $cat) {
        if (empty($cat['parent_slug'])) {
            $tree[$cat['slug']] = $cat;
        } else {
            if (!isset($children[$cat['parent_slug']])) {
                $children[$cat['parent_slug']] = [];
            }
            $children[$cat['parent_slug']][] = $cat;
        }
    }
    
    foreach ($tree as $slug => $parent) {
        echo "<span style='color: {$parent['color']}'>●</span> <strong>{$parent['name']}</strong> ({$slug})\n";
        if (isset($children[$slug])) {
            foreach ($children[$slug] as $child) {
                echo "    <span style='color: {$child['color']}'>●</span> {$child['name']} ({$child['slug']})\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='/admin/categories.php'>← Go to Categories Admin</a></p>";
?>
