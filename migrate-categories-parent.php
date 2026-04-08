<?php
/**
 * Migration: Fix Categories Table
 * Adds parent_slug column if missing
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "🔧 Migration: Add parent_slug to categories\n";
echo "=============================================\n\n";

$isMySQL = defined('USE_MYSQL') && USE_MYSQL;

try {
    // Check if column exists
    if ($isMySQL) {
        $exists = Database::fetchOne(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'parent_slug'"
        );
    } else {
        $tableInfo = Database::fetchAll("PRAGMA table_info(categories)");
        $exists = false;
        foreach ($tableInfo as $col) {
            if ($col['name'] === 'parent_slug') {
                $exists = true;
                break;
            }
        }
    }
    
    if (!$exists) {
        $type = $isMySQL ? 'VARCHAR(100) DEFAULT NULL' : 'VARCHAR(100) DEFAULT NULL';
        Database::execute("ALTER TABLE categories ADD COLUMN parent_slug $type");
        echo "✅ Coloana parent_slug adăugată!\n";
    } else {
        echo "⏭️ Coloana parent_slug există deja.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
