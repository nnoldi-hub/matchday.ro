<?php
/**
 * Settings Model - MatchDay.ro
 * Site configuration stored in database
 */

require_once(__DIR__ . '/../config/database.php');

class Settings {
    
    // Default values
    private static array $defaults = [
        'site_name' => 'MatchDay.ro',
        'site_description' => 'Știri și analize din lumea fotbalului',
        'site_keywords' => 'fotbal, sport, știri, Liga 1, Champions League',
        'contact_email' => 'contact@matchday.ro',
        'posts_per_page' => '10',
        'featured_results_count' => '5',
        'comments_enabled' => '1',
        'comments_moderation' => '1',
        'polls_enabled' => '1',
        'social_facebook' => '',
        'social_twitter' => '',
        'social_instagram' => '',
        'social_youtube' => '',
        'analytics_code' => '',
        'footer_text' => '© 2026 MatchDay.ro - Toate drepturile rezervate',
        'maintenance_mode' => '0',
        'maintenance_message' => 'Site-ul este în mentenanță. Revenim în curând!',
    ];
    
    /**
     * Get a setting value
     */
    public static function get(string $key, ?string $default = null): ?string {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        if ($result === false) {
            return $default ?? (self::$defaults[$key] ?? null);
        }
        
        return $result;
    }
    
    /**
     * Set a setting value
     */
    public static function set(string $key, string $value): bool {
        $db = Database::getInstance();
        
        // Check if setting exists
        $exists = self::get($key) !== null || array_key_exists($key, self::$defaults);
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare(
                "INSERT INTO settings (setting_key, setting_value, updated_at) 
                 VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()"
            );
            return $stmt->execute([$key, $value, $value]);
        } else {
            $stmt = $db->prepare(
                "INSERT INTO settings (setting_key, setting_value, updated_at) 
                 VALUES (?, ?, datetime('now')) 
                 ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?, updated_at = datetime('now')"
            );
            return $stmt->execute([$key, $value, $value]);
        }
    }
    
    /**
     * Get all settings
     */
    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Merge with defaults (database values take precedence)
        return array_merge(self::$defaults, $dbSettings);
    }
    
    /**
     * Save multiple settings at once
     */
    public static function saveMultiple(array $settings): bool {
        foreach ($settings as $key => $value) {
            if (!self::set($key, $value)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Delete a setting
     */
    public static function delete(string $key): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM settings WHERE setting_key = ?");
        return $stmt->execute([$key]);
    }
    
    /**
     * Initialize default settings
     */
    public static function initDefaults(): void {
        foreach (self::$defaults as $key => $value) {
            if (self::get($key) === null) {
                self::set($key, $value);
            }
        }
    }
    
    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool {
        return self::get('maintenance_mode') === '1';
    }
    
    /**
     * Check if comments are enabled
     */
    public static function commentsEnabled(): bool {
        return self::get('comments_enabled') === '1';
    }
    
    /**
     * Check if polls are enabled
     */
    public static function pollsEnabled(): bool {
        return self::get('polls_enabled') === '1';
    }
    
    /**
     * Get posts per page
     */
    public static function postsPerPage(): int {
        return (int) self::get('posts_per_page', '10');
    }
    
    /**
     * Get social links
     */
    public static function getSocialLinks(): array {
        return [
            'facebook' => self::get('social_facebook'),
            'twitter' => self::get('social_twitter'),
            'instagram' => self::get('social_instagram'),
            'youtube' => self::get('social_youtube'),
        ];
    }
}
