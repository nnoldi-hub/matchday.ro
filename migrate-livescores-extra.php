<?php
/**
 * Migration: Add extra fields to live_matches
 * - venue (stadium)
 * - referee (main referee)  
 * - referee_team (full referee team - JSON)
 * - yellow_cards (JSON array of players)
 * - red_cards (JSON array of players)
 * - article_id (link to post)
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "===========================================\n";
echo "  Migration: Live Matches Extra Fields\n";
echo "===========================================\n\n";

$db = Database::getInstance();
$isMySQL = Database::isMySQL();

$columnsToAdd = [
    'venue' => $isMySQL ? 'VARCHAR(150) DEFAULT NULL' : 'VARCHAR(150) DEFAULT NULL',
    'referee' => $isMySQL ? 'VARCHAR(100) DEFAULT NULL' : 'VARCHAR(100) DEFAULT NULL',
    'referee_team' => $isMySQL ? 'JSON DEFAULT NULL' : 'TEXT DEFAULT NULL',
    'yellow_cards_home' => $isMySQL ? 'JSON DEFAULT NULL' : 'TEXT DEFAULT NULL',
    'yellow_cards_away' => $isMySQL ? 'JSON DEFAULT NULL' : 'TEXT DEFAULT NULL',
    'red_cards_home' => $isMySQL ? 'JSON DEFAULT NULL' : 'TEXT DEFAULT NULL',
    'red_cards_away' => $isMySQL ? 'JSON DEFAULT NULL' : 'TEXT DEFAULT NULL',
    'article_id' => $isMySQL ? 'INT DEFAULT NULL' : 'INTEGER DEFAULT NULL'
];

$added = [];
$existed = [];

foreach ($columnsToAdd as $column => $type) {
    try {
        // Check if column exists
        if ($isMySQL) {
            $exists = $db->query("SHOW COLUMNS FROM live_matches LIKE '$column'")->fetch();
        } else {
            $info = $db->query("PRAGMA table_info(live_matches)")->fetchAll();
            $exists = false;
            foreach ($info as $col) {
                if ($col['name'] === $column) {
                    $exists = true;
                    break;
                }
            }
        }
        
        if ($exists) {
            $existed[] = $column;
            echo "⏭️ Column '$column' already exists\n";
        } else {
            $db->exec("ALTER TABLE live_matches ADD COLUMN $column $type");
            $added[] = $column;
            echo "✅ Added column '$column'\n";
        }
    } catch (PDOException $e) {
        echo "❌ Error adding '$column': " . $e->getMessage() . "\n";
    }
}

echo "\n===========================================\n";
echo "Summary:\n";
echo "  Added: " . count($added) . " columns\n";
echo "  Existed: " . count($existed) . " columns\n";
echo "===========================================\n";
echo "\n✓ Migration completed!\n";
