<?php
/**
 * Migration: Add goal scorers columns to featured_results table
 * Allows storing player names and minutes for each goal
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "===========================================\n";
echo "  Migration: Add Scorers to Featured Results\n";
echo "===========================================\n\n";

$db = Database::getInstance();
$isMySQL = Database::isMySQL();

try {
    // Check if columns already exist
    if ($isMySQL) {
        $columns = $db->query("SHOW COLUMNS FROM featured_results LIKE 'home_scorers'")->fetch();
    } else {
        $columns = $db->query("PRAGMA table_info(featured_results)")->fetchAll();
        $columns = array_filter($columns, fn($col) => $col['name'] === 'home_scorers');
        $columns = !empty($columns);
    }
    
    if ($columns) {
        echo "✓ Scorers columns already exist. Nothing to do.\n";
        exit(0);
    }
    
    // Add home_scorers and away_scorers columns
    // Format: JSON array of objects [{name: "Player", minute: 45}, ...]
    if ($isMySQL) {
        $db->exec("ALTER TABLE featured_results ADD COLUMN home_scorers JSON DEFAULT NULL");
        $db->exec("ALTER TABLE featured_results ADD COLUMN away_scorers JSON DEFAULT NULL");
    } else {
        $db->exec("ALTER TABLE featured_results ADD COLUMN home_scorers TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE featured_results ADD COLUMN away_scorers TEXT DEFAULT NULL");
    }
    
    echo "✓ Added 'home_scorers' column to featured_results\n";
    echo "✓ Added 'away_scorers' column to featured_results\n";
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
