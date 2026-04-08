<?php
/**
 * Migration: Add Match-Article Link
 * MatchDay.ro
 * 
 * Adds:
 * - match result columns to posts table (if missing)
 * - article_id column to live_matches table
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "🔧 MatchDay.ro - Migrare Match-Article Link\n";
echo "=============================================\n\n";

$isMySQL = defined('USE_MYSQL') && USE_MYSQL;
$success = [];
$errors = [];

// ============================================
// 1. Add match result columns to posts
// ============================================
echo "📦 1. Verificare/Adăugare coloane match result în posts...\n";

$postsColumns = [
    'is_match_result' => 'TINYINT DEFAULT 0',
    'home_team' => 'VARCHAR(100)',
    'away_team' => 'VARCHAR(100)',
    'home_score' => 'INT DEFAULT NULL',
    'away_score' => 'INT DEFAULT NULL',
    'match_competition' => 'VARCHAR(200)',
    'match_id' => 'INT DEFAULT NULL'  // Link to live_matches
];

foreach ($postsColumns as $column => $type) {
    try {
        // Check if column exists
        if ($isMySQL) {
            $exists = Database::fetchOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = :col",
                ['col' => $column]
            );
        } else {
            $tableInfo = Database::fetchAll("PRAGMA table_info(posts)");
            $exists = false;
            foreach ($tableInfo as $col) {
                if ($col['name'] === $column) {
                    $exists = true;
                    break;
                }
            }
        }
        
        if (!$exists) {
            $sqlType = $isMySQL ? $type : str_replace('TINYINT', 'INTEGER', $type);
            Database::execute("ALTER TABLE posts ADD COLUMN $column $sqlType");
            $success[] = "posts.$column added";
            echo "   ✅ Coloană adăugată: posts.$column\n";
        } else {
            echo "   ⏭️ Coloană existentă: posts.$column\n";
        }
    } catch (Exception $e) {
        $errors[] = "posts.$column: " . $e->getMessage();
        echo "   ❌ Eroare posts.$column: " . $e->getMessage() . "\n";
    }
}

// ============================================
// 2. Check and create live_matches table
// ============================================
echo "\n📦 2. Verificare tabel live_matches...\n";

try {
    if ($isMySQL) {
        $tables = Database::fetchAll("SHOW TABLES LIKE 'live_matches'");
        $tableExists = !empty($tables);
    } else {
        $result = Database::fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='live_matches'");
        $tableExists = !empty($result);
    }
    
    if (!$tableExists) {
        echo "   📊 Creare tabel live_matches...\n";
        
        if ($isMySQL) {
            Database::execute("
                CREATE TABLE live_matches (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    competition VARCHAR(100) NOT NULL DEFAULT '',
                    home_team VARCHAR(100) NOT NULL,
                    away_team VARCHAR(100) NOT NULL,
                    home_score INT DEFAULT 0,
                    away_score INT DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'scheduled',
                    minute INT DEFAULT NULL,
                    kickoff DATETIME NOT NULL,
                    home_scorers TEXT DEFAULT NULL,
                    away_scorers TEXT DEFAULT NULL,
                    venue VARCHAR(100) DEFAULT NULL,
                    external_id VARCHAR(50) DEFAULT NULL,
                    article_id INT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_kickoff (kickoff),
                    INDEX idx_article (article_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            Database::execute("
                CREATE TABLE live_matches (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    competition VARCHAR(100) NOT NULL DEFAULT '',
                    home_team VARCHAR(100) NOT NULL,
                    away_team VARCHAR(100) NOT NULL,
                    home_score INTEGER DEFAULT 0,
                    away_score INTEGER DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'scheduled',
                    minute INTEGER DEFAULT NULL,
                    kickoff DATETIME NOT NULL,
                    home_scorers TEXT DEFAULT NULL,
                    away_scorers TEXT DEFAULT NULL,
                    venue VARCHAR(100) DEFAULT NULL,
                    external_id VARCHAR(50) DEFAULT NULL,
                    article_id INTEGER DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_live_matches_kickoff ON live_matches(kickoff)");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_live_matches_article ON live_matches(article_id)");
        }
        
        $success[] = "live_matches table created";
        echo "   ✅ Tabel creat: live_matches\n";
    } else {
        echo "   ⏭️ Tabel existent: live_matches\n";
        
        // Add article_id column if missing
        try {
            if ($isMySQL) {
                $exists = Database::fetchOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'live_matches' AND COLUMN_NAME = 'article_id'"
                );
            } else {
                $tableInfo = Database::fetchAll("PRAGMA table_info(live_matches)");
                $exists = false;
                foreach ($tableInfo as $col) {
                    if ($col['name'] === 'article_id') {
                        $exists = true;
                        break;
                    }
                }
            }
            
            if (!$exists) {
                $type = $isMySQL ? 'INT DEFAULT NULL' : 'INTEGER DEFAULT NULL';
                Database::execute("ALTER TABLE live_matches ADD COLUMN article_id $type");
                $success[] = "live_matches.article_id added";
                echo "   ✅ Coloană adăugată: live_matches.article_id\n";
            } else {
                echo "   ⏭️ Coloană existentă: live_matches.article_id\n";
            }
        } catch (Exception $e) {
            $errors[] = "live_matches.article_id: " . $e->getMessage();
            echo "   ❌ Eroare: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    $errors[] = "live_matches table: " . $e->getMessage();
    echo "   ❌ Eroare: " . $e->getMessage() . "\n";
}

// ============================================
// Summary
// ============================================
echo "\n=============================================\n";
echo "📊 Rezultat Migrare\n";
echo "=============================================\n";
echo "✅ Succes: " . count($success) . "\n";
foreach ($success as $s) {
    echo "   - $s\n";
}
echo "❌ Erori: " . count($errors) . "\n";
foreach ($errors as $e) {
    echo "   - $e\n";
}

if (empty($errors)) {
    echo "\n🎉 Migrare completă cu succes!\n";
} else {
    echo "\n⚠️ Migrare completă cu avertismente.\n";
}
