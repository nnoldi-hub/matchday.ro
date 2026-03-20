<?php
/**
 * Stats Model - MatchDay.ro
 * Real visitor tracking and analytics
 */

require_once(__DIR__ . '/../config/database.php');

class Stats {
    
    /**
     * Ensure stats tables exist
     */
    public static function ensureTables(): void {
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS stats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    post_id INT NULL,
                    date DATE NOT NULL,
                    views INT DEFAULT 0,
                    unique_visitors INT DEFAULT 0,
                    UNIQUE KEY unique_post_date (post_id, date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS visitors (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_hash VARCHAR(64) NOT NULL,
                    post_id INT NULL,
                    page_type VARCHAR(50) DEFAULT 'post',
                    user_agent TEXT,
                    referer TEXT,
                    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ip_hash (ip_hash),
                    INDEX idx_post_id (post_id),
                    INDEX idx_visited_at (visited_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS stats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    post_id INTEGER,
                    date TEXT NOT NULL,
                    views INTEGER DEFAULT 0,
                    unique_visitors INTEGER DEFAULT 0,
                    UNIQUE(post_id, date)
                )
            ");
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS visitors (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip_hash TEXT NOT NULL,
                    post_id INTEGER,
                    page_type TEXT DEFAULT 'post',
                    user_agent TEXT,
                    referer TEXT,
                    visited_at TEXT DEFAULT (datetime('now'))
                )
            ");
        }
    }
    
    /**
     * Track page view - unique per IP per day
     */
    public static function trackView(?int $postId = null, string $pageType = 'post'): void {
        self::ensureTables();
        
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $ipHash = self::hashIP();
        
        // Log visitor (for detailed analytics)
        if (Database::isMySQL()) {
            $db->prepare("
                INSERT INTO visitors (ip_hash, post_id, page_type, user_agent, referer, visited_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([
                $ipHash,
                $postId,
                $pageType,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? ''
            ]);
        } else {
            $db->prepare("
                INSERT INTO visitors (ip_hash, post_id, page_type, user_agent, referer)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $ipHash,
                $postId,
                $pageType,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? ''
            ]);
        }
        
        // Check if this IP already visited today
        $isUnique = self::isUniqueVisit($ipHash, $postId, $today);
        
        // Update daily stats
        if (Database::isMySQL()) {
            if ($isUnique) {
                $db->prepare("
                    INSERT INTO stats (post_id, date, views, unique_visitors)
                    VALUES (?, ?, 1, 1)
                    ON DUPLICATE KEY UPDATE 
                        views = views + 1,
                        unique_visitors = unique_visitors + 1
                ")->execute([$postId, $today]);
            } else {
                $db->prepare("
                    INSERT INTO stats (post_id, date, views, unique_visitors)
                    VALUES (?, ?, 1, 0)
                    ON DUPLICATE KEY UPDATE views = views + 1
                ")->execute([$postId, $today]);
            }
        } else {
            if ($isUnique) {
                $db->prepare("
                    INSERT INTO stats (post_id, date, views, unique_visitors)
                    VALUES (?, ?, 1, 1)
                    ON CONFLICT(post_id, date) DO UPDATE SET 
                        views = views + 1,
                        unique_visitors = unique_visitors + 1
                ")->execute([$postId, $today]);
            } else {
                $db->prepare("
                    INSERT INTO stats (post_id, date, views, unique_visitors)
                    VALUES (?, ?, 1, 0)
                    ON CONFLICT(post_id, date) DO UPDATE SET views = views + 1
                ")->execute([$postId, $today]);
            }
        }
    }
    
    /**
     * Check if this is a unique visit for today
     */
    private static function isUniqueVisit(string $ipHash, ?int $postId, string $date): bool {
        $db = Database::getInstance();
        
        if ($postId === null) {
            // Homepage visit
            if (Database::isMySQL()) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM visitors 
                    WHERE ip_hash = ? AND post_id IS NULL AND DATE(visited_at) = ?
                    AND id != LAST_INSERT_ID()
                ");
            } else {
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM visitors 
                    WHERE ip_hash = ? AND post_id IS NULL AND DATE(visited_at) = ?
                ");
            }
            $stmt->execute([$ipHash, $date]);
        } else {
            if (Database::isMySQL()) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM visitors 
                    WHERE ip_hash = ? AND post_id = ? AND DATE(visited_at) = ?
                    AND id != LAST_INSERT_ID()
                ");
            } else {
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM visitors 
                    WHERE ip_hash = ? AND post_id = ? AND DATE(visited_at) = ?
                ");
            }
            $stmt->execute([$ipHash, $postId, $date]);
        }
        
        return $stmt->fetchColumn() <= 1; // First visit or only this one
    }
    
    /**
     * Hash IP address for privacy
     */
    private static function hashIP(): string {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Extract first IP if multiple
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return hash('sha256', $ip . date('Y-m'));
    }
    
    /**
     * Get total views for today
     */
    public static function getTodayViews(): int {
        self::ensureTables();
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(views), 0) FROM stats WHERE date = ?",
            [date('Y-m-d')]
        );
    }
    
    /**
     * Get unique visitors for today
     */
    public static function getTodayUniqueVisitors(): int {
        self::ensureTables();
        return (int) Database::fetchValue(
            "SELECT COALESCE(SUM(unique_visitors), 0) FROM stats WHERE date = ?",
            [date('Y-m-d')]
        );
    }
    
    /**
     * Get views for last N days
     */
    public static function getViewsLastDays(int $days = 7): array {
        self::ensureTables();
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare("
                SELECT date, SUM(views) as views, SUM(unique_visitors) as unique_visitors
                FROM stats 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY date
                ORDER BY date ASC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT date, SUM(views) as views, SUM(unique_visitors) as unique_visitors
                FROM stats 
                WHERE date >= DATE('now', '-' || ? || ' days')
                GROUP BY date
                ORDER BY date ASC
            ");
        }
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get most viewed posts
     */
    public static function getMostViewedPosts(int $limit = 10, int $days = 30): array {
        self::ensureTables();
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare("
                SELECT p.id, p.title, p.slug, SUM(s.views) as total_views
                FROM stats s
                JOIN posts p ON p.id = s.post_id
                WHERE s.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY p.id, p.title, p.slug
                ORDER BY total_views DESC
                LIMIT ?
            ");
        } else {
            $stmt = $db->prepare("
                SELECT p.id, p.title, p.slug, SUM(s.views) as total_views
                FROM stats s
                JOIN posts p ON p.id = s.post_id
                WHERE s.date >= DATE('now', '-' || ? || ' days')
                GROUP BY p.id, p.title, p.slug
                ORDER BY total_views DESC
                LIMIT ?
            ");
        }
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total stats summary
     */
    public static function getSummary(): array {
        self::ensureTables();
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
        $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
        $thisMonthStart = date('Y-m-01');
        
        return [
            'today_views' => self::getTodayViews(),
            'today_unique' => self::getTodayUniqueVisitors(),
            'yesterday_views' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(views), 0) FROM stats WHERE date = ?", [$yesterday]
            ),
            'this_week_views' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(views), 0) FROM stats WHERE date >= ?", [$thisWeekStart]
            ),
            'last_week_views' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(views), 0) FROM stats WHERE date >= ? AND date <= ?", 
                [$lastWeekStart, $lastWeekEnd]
            ),
            'this_month_views' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(views), 0) FROM stats WHERE date >= ?", [$thisMonthStart]
            ),
            'total_views' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(views), 0) FROM stats"
            ),
            'total_unique' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(unique_visitors), 0) FROM stats"
            ),
        ];
    }
    
    /**
     * Get hourly distribution for today
     */
    public static function getTodayHourlyStats(): array {
        self::ensureTables();
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->query("
                SELECT HOUR(visited_at) as hour, COUNT(*) as visits
                FROM visitors 
                WHERE DATE(visited_at) = CURDATE()
                GROUP BY HOUR(visited_at)
                ORDER BY hour ASC
            ");
        } else {
            $stmt = $db->query("
                SELECT CAST(strftime('%H', visited_at) AS INTEGER) as hour, COUNT(*) as visits
                FROM visitors 
                WHERE DATE(visited_at) = DATE('now')
                GROUP BY hour
                ORDER BY hour ASC
            ");
        }
        
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * Get referer stats
     */
    public static function getTopReferers(int $limit = 10, int $days = 7): array {
        self::ensureTables();
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN referer = '' OR referer IS NULL THEN 'Direct'
                        ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '/', 3), '://', -1)
                    END as source,
                    COUNT(*) as visits
                FROM visitors 
                WHERE DATE(visited_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY source
                ORDER BY visits DESC
                LIMIT ?
            ");
        } else {
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN referer = '' OR referer IS NULL THEN 'Direct'
                        ELSE referer
                    END as source,
                    COUNT(*) as visits
                FROM visitors 
                WHERE DATE(visited_at) >= DATE('now', '-' || ? || ' days')
                GROUP BY source
                ORDER BY visits DESC
                LIMIT ?
            ");
        }
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clean old visitor logs (keep last 90 days for GDPR compliance)
     */
    public static function cleanOldLogs(int $daysToKeep = 90): int {
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare("
                DELETE FROM visitors WHERE visited_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
        } else {
            $stmt = $db->prepare("
                DELETE FROM visitors WHERE visited_at < DATE('now', '-' || ? || ' days')
            ");
        }
        $stmt->execute([$daysToKeep]);
        
        return $stmt->rowCount();
    }
}
