<?php
/**
 * Migration: Add substitutions to live_matches
 * Run this on live: https://matchday.ro/migrate-match-subs.php
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "<h2>Migrare: Adăugare schimburi la meciuri</h2><pre>";

try {
    $db = Database::getInstance();
    $isMySQL = Database::isMySQL();
    
    // Add substitutions columns to live_matches
    $columns = [
        'substitutions_home' => 'TEXT',
        'substitutions_away' => 'TEXT'
    ];
    
    foreach ($columns as $col => $type) {
        try {
            if ($isMySQL) {
                $db->exec("ALTER TABLE live_matches ADD COLUMN {$col} {$type} NULL");
            } else {
                $db->exec("ALTER TABLE live_matches ADD COLUMN {$col} {$type}");
            }
            echo "✅ Coloana '{$col}' adăugată în live_matches\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️ Coloana '{$col}' există deja\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Create match_comments table
    $matchCommentsSQL = $isMySQL ? "
        CREATE TABLE IF NOT EXISTS match_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            match_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(255),
            content TEXT NOT NULL,
            status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_match_id (match_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    " : "
        CREATE TABLE IF NOT EXISTS match_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            match_id INTEGER NOT NULL,
            author_name TEXT NOT NULL,
            author_email TEXT,
            content TEXT NOT NULL,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'spam')),
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $db->exec($matchCommentsSQL);
    echo "✅ Tabela 'match_comments' creată/verificată\n";
    
    // Add indexes for SQLite
    if (!$isMySQL) {
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_mc_match ON match_comments(match_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_mc_status ON match_comments(status)");
            echo "✅ Indexuri adăugate pentru match_comments\n";
        } catch (PDOException $e) {
            echo "⚠️ Indexuri: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Migrare completă!\n";
    echo "\nAcum poți adăuga schimburi la meciuri și vizitatorii pot comenta.\n";
    
} catch (PDOException $e) {
    echo "❌ Eroare: " . $e->getMessage() . "\n";
}

echo "</pre>";
