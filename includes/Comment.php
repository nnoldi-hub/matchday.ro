<?php
/**
 * Comment Model - Enhanced
 * MatchDay.ro - Comments with replies, reactions, moderation
 */

require_once(__DIR__ . '/../config/database.php');

class Comment {
    
    // Banned words for auto-moderation
    private static $bannedWords = [
        'viagra', 'casino', 'porn', 'xxx', 'cheap', 'free money', 
        'bitcoin', 'crypto scam', 'click here', 'buy now'
    ];
    
    // Flag to track if new columns exist
    private static $hasNewColumns = null;
    
    /**
     * Check if new columns (parent_id, likes) exist in database
     */
    private static function checkNewColumns(): bool {
        if (self::$hasNewColumns !== null) {
            return self::$hasNewColumns;
        }
        
        try {
            // Check if parent_id column exists using information_schema (MySQL) or pragma (SQLite)
            if (Database::isMySQL()) {
                $result = Database::fetchOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = 'comments' AND COLUMN_NAME = 'parent_id'"
                );
                self::$hasNewColumns = ($result !== null);
            } else {
                // SQLite - just try the query
                Database::fetchValue("SELECT COUNT(*) FROM comments WHERE parent_id IS NULL LIMIT 1");
                self::$hasNewColumns = true;
            }
        } catch (Exception $e) {
            self::$hasNewColumns = false;
        }
        
        return self::$hasNewColumns;
    }
    
    /**
     * Get approved comments for a post (with replies nested)
     */
    public static function getByPost(string $postSlug, int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        
        // Check if we have the new columns
        $hasNewCols = self::checkNewColumns();
        
        if ($hasNewCols) {
            // Get top-level comments only (no parent)
            $comments = Database::fetchAll(
                "SELECT id, author_name, content, created_at, likes, parent_id
                 FROM comments 
                 WHERE post_slug = :slug AND approved = 1 AND parent_id IS NULL
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset",
                ['slug' => $postSlug, 'limit' => $perPage, 'offset' => $offset]
            );
            
            // Get replies for each comment
            foreach ($comments as &$comment) {
                $comment['replies'] = self::getReplies($comment['id']);
            }
        } else {
            // Fallback: get all comments without nesting
            $comments = Database::fetchAll(
                "SELECT id, author_name, content, created_at, 0 as likes, NULL as parent_id
                 FROM comments 
                 WHERE post_slug = :slug AND approved = 1
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset",
                ['slug' => $postSlug, 'limit' => $perPage, 'offset' => $offset]
            );
            
            foreach ($comments as &$comment) {
                $comment['replies'] = [];
            }
        }
        
        return $comments;
    }
    
    /**
     * Get replies for a comment
     */
    public static function getReplies(int $parentId): array {
        if (!self::checkNewColumns()) {
            return [];
        }
        
        return Database::fetchAll(
            "SELECT id, author_name, content, created_at, likes
             FROM comments 
             WHERE parent_id = :parent_id AND approved = 1
             ORDER BY created_at ASC",
            ['parent_id' => $parentId]
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
     * Create new comment with auto-moderation
     */
    public static function create(array $data): int {
        $ipHash = hash('sha256', ($data['ip'] ?? '') . 'matchday_salt_2026');
        
        // Auto-moderation: check for spam
        $content = strtolower($data['content'] . ' ' . $data['author_name']);
        $autoApprove = true;
        
        foreach (self::$bannedWords as $word) {
            if (strpos($content, $word) !== false) {
                $autoApprove = false;
                break;
            }
        }
        
        // Check if this IP has approved comments before (trusted commenter)
        $trustedCommenter = self::isTrustedCommenter($ipHash);
        
        // Auto-approve if trusted or no spam detected
        $approved = ($trustedCommenter || $autoApprove) ? 1 : 0;
        
        // Allow manual override
        if (isset($data['approved'])) {
            $approved = $data['approved'];
        }
        
        $parentId = isset($data['parent_id']) && $data['parent_id'] > 0 ? (int)$data['parent_id'] : null;
        
        return Database::insert(
            "INSERT INTO comments (post_slug, author_name, author_email, content, ip_hash, approved, parent_id, likes, created_at) 
             VALUES (:slug, :name, :email, :content, :ip, :approved, :parent_id, 0, CURRENT_TIMESTAMP)",
            [
                'slug' => $data['post_slug'],
                'name' => Security::sanitizeInput($data['author_name']),
                'email' => $data['author_email'] ?? null,
                'content' => Security::sanitizeInput($data['content']),
                'ip' => $ipHash,
                'approved' => $approved,
                'parent_id' => $parentId
            ]
        );
    }
    
    /**
     * Check if commenter has previous approved comments
     */
    public static function isTrustedCommenter(string $ipHash): bool {
        $count = Database::fetchValue(
            "SELECT COUNT(*) FROM comments WHERE ip_hash = :ip AND approved = 1",
            ['ip' => $ipHash]
        );
        return $count >= 2; // At least 2 approved comments
    }
    
    /**
     * Like a comment
     */
    public static function like(int $id, string $ip): bool {
        $ipHash = hash('sha256', $ip . 'matchday_like_salt');
        
        // Check if already liked
        $existing = Database::fetchValue(
            "SELECT COUNT(*) FROM comment_likes WHERE comment_id = :id AND ip_hash = :ip",
            ['id' => $id, 'ip' => $ipHash]
        );
        
        if ($existing > 0) {
            return false; // Already liked
        }
        
        // Add like
        Database::execute(
            "INSERT INTO comment_likes (comment_id, ip_hash, created_at) VALUES (:id, :ip, CURRENT_TIMESTAMP)",
            ['id' => $id, 'ip' => $ipHash]
        );
        
        Database::execute(
            "UPDATE comments SET likes = likes + 1 WHERE id = :id",
            ['id' => $id]
        );
        
        return true;
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
     * Delete comment and its replies
     */
    public static function delete(int $id): bool {
        // Delete replies first
        Database::execute(
            "DELETE FROM comments WHERE parent_id = :id",
            ['id' => $id]
        );
        
        // Delete likes
        Database::execute(
            "DELETE FROM comment_likes WHERE comment_id = :id",
            ['id' => $id]
        );
        
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
