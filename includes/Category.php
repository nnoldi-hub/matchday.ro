<?php
/**
 * Category Model
 * MatchDay.ro - Category Management with Parent/Child Support
 */

require_once(__DIR__ . '/../config/database.php');

class Category {
    
    /**
     * Get all categories
     */
    public static function getAll(): array {
        return Database::fetchAll(
            "SELECT * FROM categories ORDER BY sort_order ASC, name ASC"
        );
    }
    
    /**
     * Get all top-level categories (no parent)
     */
    public static function getTopLevel(): array {
        return Database::fetchAll(
            "SELECT * FROM categories WHERE parent_slug IS NULL OR parent_slug = '' ORDER BY sort_order ASC, name ASC"
        );
    }
    
    /**
     * Get children of a parent category
     */
    public static function getChildren(string $parentSlug): array {
        return Database::fetchAll(
            "SELECT * FROM categories WHERE parent_slug = :parent ORDER BY sort_order ASC, name ASC",
            ['parent' => $parentSlug]
        );
    }
    
    /**
     * Get category by slug
     */
    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT * FROM categories WHERE slug = :slug",
            ['slug' => $slug]
        );
    }
    
    /**
     * Get category by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT * FROM categories WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Create new category
     */
    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO categories (slug, name, description, color, icon, sort_order, parent_slug) 
             VALUES (:slug, :name, :description, :color, :icon, :sort_order, :parent_slug)",
            [
                'slug' => self::generateSlug($data['name']),
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'color' => $data['color'] ?? '#007bff',
                'icon' => $data['icon'] ?? 'fas fa-folder',
                'sort_order' => intval($data['sort_order'] ?? 0),
                'parent_slug' => $data['parent_slug'] ?? null
            ]
        );
    }
    
    /**
     * Update category
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params['name'] = trim($data['name']);
        }
        
        if (isset($data['slug'])) {
            $fields[] = "slug = :slug";
            $params['slug'] = self::generateSlug($data['slug']);
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params['description'] = trim($data['description']);
        }
        
        if (isset($data['color'])) {
            $fields[] = "color = :color";
            $params['color'] = $data['color'];
        }
        
        if (isset($data['icon'])) {
            $fields[] = "icon = :icon";
            $params['icon'] = $data['icon'];
        }
        
        if (isset($data['sort_order'])) {
            $fields[] = "sort_order = :sort_order";
            $params['sort_order'] = intval($data['sort_order']);
        }
        
        if (array_key_exists('parent_slug', $data)) {
            $fields[] = "parent_slug = :parent_slug";
            $params['parent_slug'] = $data['parent_slug'];
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
        return Database::execute($sql, $params) > 0;
    }
    
    /**
     * Delete category
     */
    public static function delete(int $id): bool {
        // Get slug first to update posts
        $category = self::getById($id);
        if (!$category) return false;
        
        // Set posts category to null
        Database::execute(
            "UPDATE posts SET category_slug = NULL WHERE category_slug = :slug",
            ['slug' => $category['slug']]
        );
        
        return Database::execute(
            "DELETE FROM categories WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Count posts in category
     */
    public static function countPosts(string $slug): int {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM posts WHERE category_slug = :slug",
            ['slug' => $slug]
        );
    }
    
    /**
     * Generate slug from name
     */
    public static function generateSlug(string $text): string {
        // Convert to lowercase
        $slug = mb_strtolower($text, 'UTF-8');
        
        // Romanian characters
        $slug = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'],
            $slug
        );
        
        // Replace non-alphanumeric with dash
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Get available icons
     */
    public static function getIconOptions(): array {
        return [
            'fas fa-futbol' => 'Minge',
            'fas fa-trophy' => 'Trofeu',
            'fas fa-medal' => 'Medalie',
            'fas fa-flag' => 'Steag',
            'fas fa-users' => 'Echipă',
            'fas fa-star' => 'Stea',
            'fas fa-chart-bar' => 'Statistici',
            'fas fa-newspaper' => 'Știri',
            'fas fa-comment-alt' => 'Opinie',
            'fas fa-microphone' => 'Interviu',
            'fas fa-exchange-alt' => 'Transfer',
            'fas fa-calendar' => 'Calendar',
            'fas fa-globe' => 'Internațional',
            'fas fa-home' => 'Liga 1',
            'fas fa-fire' => 'Hot',
            'fas fa-bolt' => 'Breaking',
            'fas fa-list-ol' => 'Clasament',
            'fas fa-crown' => 'Coroanǎ',
            'fas fa-sun' => 'Soare',
            'fas fa-shield-alt' => 'Scut',
            'fas fa-anchor' => 'Ancoră',
            'fas fa-landmark' => 'Monument'
        ];
    }
    
    /**
     * Sync from config file (initial migration)
     */
    public static function syncFromConfig(): int {
        $configFile = __DIR__ . '/../config/categories.php';
        if (!file_exists($configFile)) return 0;
        
        $categories = include $configFile;
        $count = 0;
        $order = 0;
        
        foreach ($categories as $slug => $data) {
            $existing = self::getBySlug($slug);
            
            if (!$existing) {
                try {
                    Database::insert(
                        "INSERT INTO categories (slug, name, description, color, icon, sort_order, parent_slug) 
                         VALUES (:slug, :name, :description, :color, :icon, :sort_order, :parent_slug)",
                        [
                            'slug' => $slug,
                            'name' => $data['name'],
                            'description' => $data['description'] ?? '',
                            'color' => $data['color'] ?? '#007bff',
                            'icon' => $data['icon'] ?? 'fas fa-folder',
                            'sort_order' => $order,
                            'parent_slug' => $data['parent'] ?? null
                        ]
                    );
                    $count++;
                } catch (Exception $e) {
                    // Skip duplicates
                }
            }
            $order++;
        }
        
        return $count;
    }
    
    /**
     * Get categories with their children (hierarchical)
     */
    public static function getHierarchical(): array {
        $all = self::getAll();
        $result = [];
        $children = [];
        
        // Group children by parent
        foreach ($all as $cat) {
            if (!empty($cat['parent_slug'])) {
                if (!isset($children[$cat['parent_slug']])) {
                    $children[$cat['parent_slug']] = [];
                }
                $children[$cat['parent_slug']][] = $cat;
            }
        }
        
        // Build hierarchical structure
        foreach ($all as $cat) {
            if (empty($cat['parent_slug'])) {
                $cat['children'] = $children[$cat['slug']] ?? [];
                $result[] = $cat;
            }
        }
        
        return $result;
    }
}
