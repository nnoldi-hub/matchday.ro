<?php
/**
 * Comment Model
 * MatchDay.ro - Comments Management
 */

require_once(__DIR__ . '/../config/database.php');

class Comment {
    
    /**
     * Get approved comments for a post
     */
    public static function getByPost(string $postSlug, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        
        return Database::fetchAll(
            "SELECT id, author_name, content, created_at 
             FROM comments 
             WHERE post_slug = :slug AND approved = 1 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['slug' => $postSlug, 'limit' => $perPage, 'offset' => $offset]
        );
    }
    
    /**
     * Count comments for a post
     */
    public static function countByPost(string $postSlug, bool $approvedOnly = true): int {
        $sql = "SELECT COUNT(*) FROM comments WHERE post_slug = :slug";
        if ($approvedOnly) {
            $sql .= " AND approved = 1";
        }
        return (int) Database::fetchValue($sql, ['slug' => $postSlug]);
    }
    
    /**
     * Get all comments for admin
     */
    public static function getAll(int $page = 1, int $perPage = 50, ?int $approved = null): array {
        $offset = ($page - 1) * $perPage;
        $params = ['limit' => $perPage, 'offset' => $offset];
        
        $sql = "SELECT c.*, p.title as post_title 
                FROM comments c 
                LEFT JOIN posts p ON c.post_slug = p.slug";
        
        if ($approved !== null) {
            $sql .= " WHERE c.approved = :approved";
            $params['approved'] = $approved;
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $type);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Count all comments
     */
    public static function countAll(?int $approved = null): int {
        $sql = "SELECT COUNT(*) FROM comments";
        $params = [];
        
        if ($approved !== null) {
            $sql .= " WHERE approved = :approved";
            $params['approved'] = $approved;
        }
        
        return (int) Database::fetchValue($sql, $params);
    }
    
    /**
     * Get pending comments count
     */
    public static function countPending(): int {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM comments WHERE approved = 0");
    }
    
    /**
     * Get single comment
     */
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT c.*, p.title as post_title 
             FROM comments c 
             LEFT JOIN posts p ON c.post_slug = p.slug 
             WHERE c.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Create new comment
     */
    public static function create(array $data): int {
        $ipHash = hash('sha256', ($data['ip'] ?? '') . 'matchday_salt_2026');
        
        return Database::insert(
            "INSERT INTO comments (post_slug, author_name, author_email, content, ip_hash, approved, created_at) 
             VALUES (:slug, :name, :email, :content, :ip, :approved, CURRENT_TIMESTAMP)",
            [
                'slug' => $data['post_slug'],
                'name' => Security::sanitizeInput($data['author_name']),
                'email' => $data['author_email'] ?? null,
                'content' => Security::sanitizeInput($data['content']),
                'ip' => $ipHash,
                'approved' => $data['approved'] ?? 0
            ]
        );
    }
    
    /**
     * Approve comment
     */
    public static function approve(int $id): bool {
        return Database::execute(
            "UPDATE comments SET approved = 1 WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Reject/unapprove comment
     */
    public static function reject(int $id): bool {
        return Database::execute(
            "UPDATE comments SET approved = 0 WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Delete comment
     */
    public static function delete(int $id): bool {
        return Database::execute(
            "DELETE FROM comments WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Bulk approve comments
     */
    public static function bulkApprove(array $ids): int {
        if (empty($ids)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare(
            "UPDATE comments SET approved = 1 WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        
        return $stmt->rowCount();
    }
    
    /**
     * Bulk delete comments
     */
    public static function bulkDelete(array $ids): int {
        if (empty($ids)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare(
            "DELETE FROM comments WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        
        return $stmt->rowCount();
    }
    
    /**
     * Get recent comments
     */
    public static function getRecent(int $limit = 5): array {
        return Database::fetchAll(
            "SELECT c.id, c.author_name, c.content, c.created_at, c.post_slug, p.title as post_title
             FROM comments c
             LEFT JOIN posts p ON c.post_slug = p.slug
             WHERE c.approved = 1
             ORDER BY c.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Check rate limit for IP
     */
    public static function checkRateLimit(string $ip, int $limit = 5, int $windowMinutes = 5): bool {
        $ipHash = hash('sha256', $ip . 'matchday_salt_2026');
        $windowStart = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
        
        $count = Database::fetchValue(
            "SELECT COUNT(*) FROM comments 
             WHERE ip_hash = :ip AND created_at > :window",
            ['ip' => $ipHash, 'window' => $windowStart]
        );
        
        return $count < $limit;
    }
    
    /**
     * Search comments
     */
    public static function search(string $query, int $limit = 50): array {
        $term = "%$query%";
        return Database::fetchAll(
            "SELECT c.*, p.title as post_title
             FROM comments c
             LEFT JOIN posts p ON c.post_slug = p.slug
             WHERE c.content LIKE :term OR c.author_name LIKE :term2
             ORDER BY c.created_at DESC
             LIMIT :limit",
            ['term' => $term, 'term2' => $term, 'limit' => $limit]
        );
    }
}
