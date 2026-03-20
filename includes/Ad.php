<?php
/**
 * Ad Model
 * MatchDay.ro - Advertising/Sponsorship Management
 */

require_once(__DIR__ . '/../config/database.php');

class Ad {
    
    /**
     * Get all ads with pagination
     */
    public static function getAll(int $page = 1, int $perPage = 20): array {
        try {
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT * FROM ads ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = Database::getInstance()->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Count all ads
     */
    public static function countAll(): int {
        try {
            return (int) Database::fetchValue("SELECT COUNT(*) FROM ads");
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get single ad by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT * FROM ads WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Get active ads by position
     * Checks if ad is active, within date range, and matches position
     */
    public static function getActive(string $position = 'sidebar', int $limit = 5): array {
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM ads 
                WHERE active = 1 
                AND position = :position
                AND (start_date IS NULL OR start_date <= :today1)
                AND (end_date IS NULL OR end_date >= :today2)
                ORDER BY RAND()
                LIMIT :limit";
        
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':position', $position);
        $stmt->bindValue(':today1', $today);
        $stmt->bindValue(':today2', $today);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get single active ad by position (for display)
     */
    public static function getOneActive(string $position = 'sidebar'): ?array {
        $ads = self::getActive($position, 1);
        return $ads[0] ?? null;
    }
    
    /**
     * Create new ad
     */
    public static function create(array $data): int {
        try {
            $sql = "INSERT INTO ads (name, image, link, position, start_date, end_date, active, code, created_at) 
                    VALUES (:name, :image, :link, :position, :start_date, :end_date, :active, :code, NOW())";
            
            Database::getInstance()->prepare($sql)->execute([
                'name' => $data['name'],
                'image' => $data['image'] ?? null,
                'link' => $data['link'] ?? null,
                'position' => $data['position'] ?? 'sidebar',
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                'active' => $data['active'] ?? 1,
                'code' => $data['code'] ?? null
            ]);
            
            return (int) Database::getInstance()->lastInsertId();
        } catch (Exception $e) {
            error_log("Ad::create error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update existing ad
     */
    public static function update(int $id, array $data): bool {
        $sql = "UPDATE ads SET 
                name = :name,
                image = :image,
                link = :link,
                position = :position,
                start_date = :start_date,
                end_date = :end_date,
                active = :active,
                code = :code,
                updated_at = NOW()
                WHERE id = :id";
        
        return Database::getInstance()->prepare($sql)->execute([
            'id' => $id,
            'name' => $data['name'],
            'image' => $data['image'] ?? null,
            'link' => $data['link'] ?? null,
            'position' => $data['position'] ?? 'sidebar',
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'active' => $data['active'] ?? 1,
            'code' => $data['code'] ?? null
        ]);
    }
    
    /**
     * Delete ad
     */
    public static function delete(int $id): bool {
        return Database::getInstance()
            ->prepare("DELETE FROM ads WHERE id = :id")
            ->execute(['id' => $id]);
    }
    
    /**
     * Toggle ad active status
     */
    public static function toggleActive(int $id): bool {
        return Database::getInstance()
            ->prepare("UPDATE ads SET active = NOT active, updated_at = NOW() WHERE id = :id")
            ->execute(['id' => $id]);
    }
    
    /**
     * Record ad impression
     */
    public static function recordImpression(int $id): bool {
        return Database::getInstance()
            ->prepare("UPDATE ads SET impressions = impressions + 1 WHERE id = :id")
            ->execute(['id' => $id]);
    }
    
    /**
     * Record ad click
     */
    public static function recordClick(int $id): bool {
        return Database::getInstance()
            ->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = :id")
            ->execute(['id' => $id]);
    }
    
    /**
     * Get ad statistics
     */
    public static function getStats(): array {
        try {
            $total = (int) Database::fetchValue("SELECT COUNT(*) FROM ads");
            $active = (int) Database::fetchValue("SELECT COUNT(*) FROM ads WHERE active = 1");
            $totalClicks = (int) Database::fetchValue("SELECT COALESCE(SUM(clicks), 0) FROM ads");
            $totalImpressions = (int) Database::fetchValue("SELECT COALESCE(SUM(impressions), 0) FROM ads");
            
            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
                'clicks' => $totalClicks,
                'impressions' => $totalImpressions,
                'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0
            ];
        } catch (Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'clicks' => 0,
                'impressions' => 0,
                'ctr' => 0
            ];
        }
    }
    
    /**
     * Get positions list
     */
    public static function getPositions(): array {
        return [
            'sidebar' => 'Sidebar (300x250)',
            'header' => 'Header Banner (728x90)',
            'footer' => 'Footer Banner',
            'article-inline' => 'Între articole',
            'article-content' => 'În conținut articol'
        ];
    }
    
    /**
     * Initialize ads table (for schema creation)
     */
    public static function createTable(): string {
        if (Database::isMySQL()) {
            return "
                CREATE TABLE IF NOT EXISTS ads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    image VARCHAR(500),
                    link VARCHAR(500),
                    code TEXT,
                    position ENUM('sidebar','header','footer','article-inline','article-content') DEFAULT 'sidebar',
                    start_date DATE,
                    end_date DATE,
                    active TINYINT(1) DEFAULT 1,
                    clicks INT DEFAULT 0,
                    impressions INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME,
                    INDEX idx_position (position),
                    INDEX idx_active (active),
                    INDEX idx_dates (start_date, end_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
        } else {
            return "
                CREATE TABLE IF NOT EXISTS ads (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    image TEXT,
                    link TEXT,
                    code TEXT,
                    position TEXT DEFAULT 'sidebar',
                    start_date TEXT,
                    end_date TEXT,
                    active INTEGER DEFAULT 1,
                    clicks INTEGER DEFAULT 0,
                    impressions INTEGER DEFAULT 0,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT
                );
            ";
        }
    }
    
    /**
     * Run table migration
     */
    public static function migrate(): bool {
        try {
            // Ensure connection is established first (sets isMySQL flag)
            $db = Database::getInstance();
            $sql = self::createTable();
            $db->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("Ad table migration failed (PDO): " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Ad table migration failed: " . $e->getMessage());
            return false;
        }
    }
}
