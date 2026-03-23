<?php
/**
 * Migration: Add match result fields to posts table
 * Run this once to add the new columns
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "===========================================\n";
echo "  Migration: Add Match Result Fields\n";
echo "===========================================\n\n";

$db = Database::getInstance();
$isMySQL = Database::isMySQL();

try {
    // Check if columns already exist
    if ($isMySQL) {
        $check = $db->query("SHOW COLUMNS FROM posts LIKE 'is_match_result'");
        $exists = $check->rowCount() > 0;
    } else {
        $check = $db->query("PRAGMA table_info(posts)");
        $columns = $check->fetchAll(PDO::FETCH_COLUMN, 1);
        $exists = in_array('is_match_result', $columns);
    }
    
    if ($exists) {
        echo "✓ Columns already exist. Migration not needed.\n";
        exit(0);
    }
    
    // Add columns based on database type
    if ($isMySQL) {
        $db->exec("ALTER TABLE posts ADD COLUMN is_match_result TINYINT DEFAULT 0");
        $db->exec("ALTER TABLE posts ADD COLUMN home_team VARCHAR(100)");
        $db->exec("ALTER TABLE posts ADD COLUMN away_team VARCHAR(100)");
        $db->exec("ALTER TABLE posts ADD COLUMN home_score INT");
        $db->exec("ALTER TABLE posts ADD COLUMN away_score INT");
        $db->exec("ALTER TABLE posts ADD COLUMN match_competition VARCHAR(100)");
        $db->exec("ALTER TABLE posts ADD INDEX idx_match_result (is_match_result)");
    } else {
        $db->exec("ALTER TABLE posts ADD COLUMN is_match_result INTEGER DEFAULT 0");
        $db->exec("ALTER TABLE posts ADD COLUMN home_team TEXT");
        $db->exec("ALTER TABLE posts ADD COLUMN away_team TEXT");
        $db->exec("ALTER TABLE posts ADD COLUMN home_score INTEGER");
        $db->exec("ALTER TABLE posts ADD COLUMN away_score INTEGER");
        $db->exec("ALTER TABLE posts ADD COLUMN match_competition TEXT");
    }
    
    echo "✓ Added columns: is_match_result, home_team, away_team, home_score, away_score, match_competition\n";
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
