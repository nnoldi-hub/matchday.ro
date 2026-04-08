<?php
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Category.php');

echo "=== DEBUG SYNC ===\n\n";

// Config categories
$cats = require(__DIR__ . '/config/categories.php');
echo "Config keys: " . implode(', ', array_keys($cats)) . "\n\n";

// DB categories slugs
$dbCats = Category::getAll();
$dbSlugs = array_column($dbCats, 'slug');
echo "DB slugs: " . implode(', ', $dbSlugs) . "\n\n";

// Missing
$missing = array_diff(array_keys($cats), $dbSlugs);
echo "Missing: " . implode(', ', $missing) . "\n\n";

// Try to add them manually
echo "Adding missing categories...\n";
$order = count($dbSlugs);
foreach ($missing as $slug) {
    $data = $cats[$slug];
    echo "  Adding: $slug ({$data['name']})... ";
    try {
        $existing = Category::getBySlug($slug);
        if ($existing) {
            echo "already exists\n";
            continue;
        }
        
        Database::insert(
            "INSERT INTO categories (slug, name, description, color, icon, sort_order, parent_slug) 
             VALUES (:slug, :name, :description, :color, :icon, :sort_order, :parent_slug)",
            [
                'slug' => $slug,
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#007bff',
                'icon' => $data['icon'] ?? 'fas fa-folder',
                'sort_order' => $order++,
                'parent_slug' => $data['parent'] ?? null
            ]
        );
        echo "OK\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Done ===\n";
echo "Total categories in DB now: " . count(Category::getAll()) . "\n";
