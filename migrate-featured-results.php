<?php
/**
 * Migration: Create featured_results table for homepage
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "===========================================\n";
echo "  Migration: Featured Results Table\n";
echo "===========================================\n\n";

$db = Database::getInstance();
$isMySQL = Database::isMySQL();

try {
    if ($isMySQL) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS featured_results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                home_team VARCHAR(100) NOT NULL,
                away_team VARCHAR(100) NOT NULL,
                home_score INT NOT NULL DEFAULT 0,
                away_score INT NOT NULL DEFAULT 0,
                competition VARCHAR(100),
                post_id INT,
                sort_order INT DEFAULT 0,
                active TINYINT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,
                INDEX idx_active (active),
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS featured_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                home_team TEXT NOT NULL,
                away_team TEXT NOT NULL,
                home_score INTEGER NOT NULL DEFAULT 0,
                away_score INTEGER NOT NULL DEFAULT 0,
                competition TEXT,
                post_id INTEGER,
                sort_order INTEGER DEFAULT 0,
                active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                FOREIGN KEY (post_id) REFERENCES posts(id)
            )
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_featured_active ON featured_results(active)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_featured_sort ON featured_results(sort_order)");
    }
    
    echo "✓ Table 'featured_results' created successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
