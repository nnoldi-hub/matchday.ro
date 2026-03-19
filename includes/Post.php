<?php
/**
 * Post Model
 * MatchDay.ro - Article Management
 */

require_once(__DIR__ . '/../config/database.php');

class Post {
    
    /**
     * Get all published posts with pagination
     */
    public static function getPublished(int $page = 1, int $perPage = 10, ?string $category = null, ?string $search = null): array {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT p.*, c.name as category_name, c.color as category_color 
                FROM posts p 
                LEFT JOIN categories c ON p.category_slug = c.slug 
                WHERE p.status = 'published'";
        
        if ($category) {
            $sql .= " AND p.category_slug = :category";
            $params['category'] = $category;
        }
        
        if ($search) {
            $sql .= " AND (p.title LIKE :search OR p.content LIKE :search2 OR p.excerpt LIKE :search3)";
            $params['search'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "%$search%";
        }
        
        $sql .= " ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = Database::getInstance()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Count published posts
     */
    public static function countPublished(?string $category = null, ?string $search = null): int {
        $params = [];
        $sql = "SELECT COUNT(*) FROM posts WHERE status = 'published'";
        
        if ($category) {
            $sql .= " AND category_slug = :category";
            $params['category'] = $category;
        }
        
        if ($search) {
            $sql .= " AND (title LIKE :search OR content LIKE :search2)";
            $params['search'] = "%$search%";
            $params['search2'] = "%$search%";
        }
        
        return (int) Database::fetchValue($sql, $params);
    }
    
    /**
     * Get single post by slug
     */
    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT p.*, c.name as category_name, c.color as category_color 
             FROM posts p 
             LEFT JOIN categories c ON p.category_slug = c.slug 
             WHERE p.slug = :slug",
            ['slug' => $slug]
        );
    }
    
    /**
     * Get single post by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT p.*, c.name as category_name, c.color as category_color 
             FROM posts p 
             LEFT JOIN categories c ON p.category_slug = c.slug 
             WHERE p.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Get all posts for admin
     */
    public static function getAll(int $page = 1, int $perPage = 20, ?string $status = null): array {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM posts p 
                LEFT JOIN categories c ON p.category_slug = c.slug";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE p.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Create new post
     */
    public static function create(array $data): int {
        $slug = $data['slug'] ?? self::generateSlug($data['title']);
        
        return Database::insert(
            "INSERT INTO posts (title, slug, content, excerpt, category_slug, cover_image, tags, status, author, published_at, created_at) 
             VALUES (:title, :slug, :content, :excerpt, :category, :cover, :tags, :status, :author, :published, CURRENT_TIMESTAMP)",
            [
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'] ?? '',
                'excerpt' => $data['excerpt'] ?? '',
                'category' => $data['category'] ?? null,
                'cover' => $data['cover_image'] ?? '',
                'tags' => is_array($data['tags'] ?? []) ? implode(',', $data['tags']) : ($data['tags'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'author' => $data['author'] ?? 'Admin',
                'published' => ($data['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null
            ]
        );
    }
    
    /**
     * Update existing post
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['title', 'content', 'excerpt', 'category_slug', 'cover_image', 'tags', 'status', 'author'];
        
        foreach ($allowedFields as $field) {
            $dataKey = str_replace('_slug', '', $field);
            if (isset($data[$dataKey]) || isset($data[$field])) {
                $value = $data[$field] ?? $data[$dataKey];
                if ($field === 'tags' && is_array($value)) {
                    $value = implode(',', $value);
                }
                $fields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        // Update published_at if publishing
        if (isset($data['status']) && $data['status'] === 'published') {
            $fields[] = "published_at = COALESCE(published_at, CURRENT_TIMESTAMP)";
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return Database::execute($sql, $params) > 0;
    }
    
    /**
     * Delete post
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM posts WHERE id = :id", ['id' => $id]) > 0;
    }
    
    /**
     * Increment view count
     */
    public static function incrementViews(int $id): void {
        Database::execute("UPDATE posts SET views = views + 1 WHERE id = :id", ['id' => $id]);
    }
    
    /**
     * Get related posts
     */
    public static function getRelated(string $category, string $excludeSlug, int $limit = 3): array {
        return Database::fetchAll(
            "SELECT id, title, slug, excerpt, cover_image, published_at 
             FROM posts 
             WHERE category_slug = :category 
               AND slug != :exclude 
               AND status = 'published' 
             ORDER BY published_at DESC 
             LIMIT :limit",
            ['category' => $category, 'exclude' => $excludeSlug, 'limit' => $limit]
        );
    }
    
    /**
     * Get latest posts
     */
    public static function getLatest(int $limit = 5): array {
        return Database::fetchAll(
            "SELECT id, title, slug, excerpt, cover_image, category_slug, published_at 
             FROM posts 
             WHERE status = 'published' 
             ORDER BY published_at DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Get posts count by category
     */
    public static function getCountByCategory(): array {
        return Database::fetchAll(
            "SELECT category_slug, COUNT(*) as count 
             FROM posts 
             WHERE status = 'published' 
             GROUP BY category_slug"
        );
    }
    
    /**
     * Generate URL-safe slug
     */
    public static function generateSlug(string $title): string {
        $slug = mb_strtolower($title, 'UTF-8');
        
        // Romanian character replacements
        $replacements = [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
            'Ă' => 'a', 'Â' => 'a', 'Î' => 'i', 'Ș' => 's', 'Ț' => 't'
        ];
        $slug = strtr($slug, $replacements);
        
        // Remove special characters
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while (self::slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    public static function slugExists(string $slug, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM posts WHERE slug = :slug";
        $params = ['slug' => $slug];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        return Database::fetchValue($sql, $params) > 0;
    }
    
    /**
     * Full-text search
     */
    public static function search(string $query, int $limit = 20): array {
        $searchTerm = "%$query%";
        return Database::fetchAll(
            "SELECT id, title, slug, excerpt, cover_image, category_slug, published_at, status, views,
                    CASE 
                        WHEN title LIKE :exact THEN 3
                        WHEN title LIKE :term THEN 2
                        ELSE 1
                    END as relevance
             FROM posts 
             WHERE (title LIKE :term2 OR content LIKE :term3 OR excerpt LIKE :term4)
             ORDER BY relevance DESC, published_at DESC
             LIMIT :limit",
            [
                'exact' => $query,
                'term' => $searchTerm,
                'term2' => $searchTerm,
                'term3' => $searchTerm,
                'term4' => $searchTerm,
                'limit' => $limit
            ]
        );
    }
    
    /**
     * Count all posts (for admin)
     */
    public static function countAll(?string $status = null): int {
        if ($status) {
            return (int) Database::fetchValue(
                "SELECT COUNT(*) FROM posts WHERE status = :status",
                ['status' => $status]
            );
        }
        return (int) Database::fetchValue("SELECT COUNT(*) FROM posts");
    }
    
    /**
     * Publish post
     */
    public static function publish(int $id): bool {
        return Database::execute(
            "UPDATE posts SET status = 'published', published_at = COALESCE(published_at, CURRENT_TIMESTAMP), updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Unpublish post (set to draft)
     */
    public static function unpublish(int $id): bool {
        return Database::execute(
            "UPDATE posts SET status = 'draft', updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Archive post
     */
    public static function archive(int $id): bool {
        return Database::execute(
            "UPDATE posts SET status = 'archived', updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
}
