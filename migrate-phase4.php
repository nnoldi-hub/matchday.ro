<?php
/**
 * Migration: Phase 4 Tables
 * Creates tables for badges, comment likes, and newsletter logs
 * 
 * Run: php migrate-phase4.php
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');

echo "=== MatchDay.ro Phase 4 Migration ===\n\n";

$db = Database::getInstance();
$isMySQL = Database::isMySQL();

try {
    // 1. User Badges Table
    echo "Creating user_badges table...\n";
    
    if ($isMySQL) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_badges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_identifier VARCHAR(100) NOT NULL,
                badge_id VARCHAR(50) NOT NULL,
                earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_badge (user_identifier, badge_id),
                INDEX idx_user (user_identifier),
                INDEX idx_badge (badge_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_badges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_identifier TEXT NOT NULL,
                badge_id TEXT NOT NULL,
                earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_identifier, badge_id)
            )
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_user ON user_badges(user_identifier)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_badge ON user_badges(badge_id)");
    }
    echo "✓ user_badges table created\n";
    
    // 2. Comment Likes Table
    echo "Creating comment_likes table...\n";
    
    if ($isMySQL) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS comment_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                comment_id INT NOT NULL,
                ip_hash VARCHAR(64) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_like (comment_id, ip_hash),
                INDEX idx_comment (comment_id),
                FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS comment_likes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comment_id INTEGER NOT NULL,
                ip_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(comment_id, ip_hash)
            )
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_comment_likes_comment ON comment_likes(comment_id)");
    }
    echo "✓ comment_likes table created\n";
    
    // 3. Newsletter Logs Table
    echo "Creating newsletter_logs table...\n";
    
    if ($isMySQL) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS newsletter_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sent_count INT DEFAULT 0,
                error_count INT DEFAULT 0,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS newsletter_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sent_count INTEGER DEFAULT 0,
                error_count INTEGER DEFAULT 0,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "✓ newsletter_logs table created\n";
    
    // 4. Add columns to comments table if missing
    echo "Adding columns to comments table...\n";
    
    // Check if parent_id exists
    try {
        if ($isMySQL) {
            $stmt = $db->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
            if ($stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE comments ADD COLUMN parent_id INT DEFAULT NULL");
                $db->exec("ALTER TABLE comments ADD INDEX idx_parent (parent_id)");
            }
        } else {
            $db->exec("ALTER TABLE comments ADD COLUMN parent_id INTEGER DEFAULT NULL");
        }
        echo "✓ parent_id column added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
        echo "  parent_id already exists\n";
    }
    
    // Check if likes exists
    try {
        if ($isMySQL) {
            $stmt = $db->query("SHOW COLUMNS FROM comments LIKE 'likes'");
            if ($stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE comments ADD COLUMN likes INT DEFAULT 0");
            }
        } else {
            $db->exec("ALTER TABLE comments ADD COLUMN likes INTEGER DEFAULT 0");
        }
        echo "✓ likes column added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
        echo "  likes column already exists\n";
    }
    
    // 5. Push notification subscriptions table
    echo "Creating push_subscriptions table...\n";
    
    if ($isMySQL) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS push_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                endpoint TEXT NOT NULL,
                p256dh TEXT,
                auth TEXT,
                user_identifier VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_identifier)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS push_subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                endpoint TEXT NOT NULL,
                p256dh TEXT,
                auth TEXT,
                user_identifier TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "✓ push_subscriptions table created\n";
    
    echo "\n=== Migration Complete! ===\n";
    echo "All Phase 4 tables have been created successfully.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
