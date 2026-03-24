<?php
/**
 * Database Configuration & Connection
 * MatchDay.ro - MySQL (Hostico) / SQLite (local) Database Layer
 */

// =====================================================
// HOSTICO MYSQL CONFIGURATION
// =====================================================
define('MYSQL_HOST', 'localhost');
define('MYSQL_DB', 'opnwyzqa_matchday');
define('MYSQL_USER', 'opnwyzqa_david');
define('MYSQL_PASS', 'PetreIonel205!'); // <-- PUNE PAROLA AICI

// SQLite fallback path (local development)
define('SQLITE_PATH', __DIR__ . '/../data/matchday.db');

// Auto-detect: use MySQL on Hostico, SQLite locally
define('USE_MYSQL', strpos($_SERVER['HTTP_HOST'] ?? '', 'matchday.ro') !== false);

/**
 * Database Singleton Class
 * Provides PDO connection to MySQL or SQLite
 */
class Database {
    private static ?PDO $instance = null;
    private static bool $isMySQL = false;
    
    /**
     * Check if using MySQL
     */
    public static function isMySQL(): bool {
        return self::$isMySQL;
    }
    
    /**
     * Get database connection (singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                if (USE_MYSQL && MYSQL_PASS !== '') {
                    // MySQL connection (Hostico)
                    self::$instance = new PDO(
                        'mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_DB . ';charset=utf8mb4',
                        MYSQL_USER,
                        MYSQL_PASS,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                        ]
                    );
                    self::$isMySQL = true;
                } else {
                    // SQLite connection (local)
                    self::$instance = new PDO(
                        'sqlite:' . SQLITE_PATH,
                        null,
                        null,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );
                    
                    // SQLite specific settings
                    self::$instance->exec('PRAGMA foreign_keys = ON');
                    self::$instance->exec('PRAGMA journal_mode = WAL');
                    self::$instance->exec('PRAGMA synchronous = NORMAL');
                    self::$isMySQL = false;
                }
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Nu s-a putut conecta la baza de date.");
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize database schema
     */
    public static function initSchema(): bool {
        $db = self::getInstance();
        
        if (self::$isMySQL) {
            return self::initMySQLSchema($db);
        } else {
            return self::initSQLiteSchema($db);
        }
    }
    
    /**
     * MySQL Schema (Hostico)
     */
    private static function initMySQLSchema(PDO $db): bool {
        $schema = "
            -- Categorii
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(100) UNIQUE NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT,
                color VARCHAR(20) DEFAULT '#007bff',
                icon VARCHAR(50) DEFAULT 'fas fa-folder',
                sort_order INT DEFAULT 0,
                parent_slug VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parent (parent_slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Articole
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(500) NOT NULL,
                slug VARCHAR(200) UNIQUE NOT NULL,
                content LONGTEXT,
                excerpt TEXT,
                category_slug VARCHAR(100),
                cover_image VARCHAR(500),
                tags TEXT,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                views INT DEFAULT 0,
                author VARCHAR(100) DEFAULT 'Admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
                published_at DATETIME,
                INDEX idx_status (status),
                INDEX idx_category (category_slug),
                INDEX idx_published (published_at),
                FOREIGN KEY (category_slug) REFERENCES categories(slug) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Sondaje
            CREATE TABLE IF NOT EXISTS polls (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(200) UNIQUE NOT NULL,
                question VARCHAR(500) NOT NULL,
                description TEXT,
                active TINYINT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Opțiuni sondaje
            CREATE TABLE IF NOT EXISTS poll_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                poll_id INT NOT NULL,
                option_text VARCHAR(500) NOT NULL,
                votes INT DEFAULT 0,
                sort_order INT DEFAULT 0,
                FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Voturi sondaje
            CREATE TABLE IF NOT EXISTS poll_votes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                poll_id INT NOT NULL,
                option_id INT NOT NULL,
                ip_hash VARCHAR(64) NOT NULL,
                voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_poll_ip (poll_id, ip_hash),
                FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Comentarii
            CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_slug VARCHAR(200) NOT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(200),
                content TEXT NOT NULL,
                ip_hash VARCHAR(64),
                approved TINYINT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post (post_slug),
                INDEX idx_approved (approved)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Utilizatori admin
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(200) UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'editor') DEFAULT 'editor',
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Statistici
            CREATE TABLE IF NOT EXISTS stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT,
                date DATE NOT NULL,
                views INT DEFAULT 0,
                unique_visitors INT DEFAULT 0,
                UNIQUE KEY unique_post_date (post_id, date),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Setări site
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            -- Rate limiting
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_hash VARCHAR(64) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                attempts INT DEFAULT 1,
                first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_ip_action (ip_hash, action_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            // MySQL needs separate statements
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $db->exec($stmt);
                }
            }
            
            // Auto-sync categories from config
            self::syncCategoriesFromConfig($db);
            
            return true;
        } catch (PDOException $e) {
            error_log("MySQL Schema initialization failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync categories from config file to database
     */
    private static function syncCategoriesFromConfig(PDO $db): void {
        $configFile = __DIR__ . '/categories.php';
        if (!file_exists($configFile)) return;
        
        $categories = include $configFile;
        $order = 0;
        
        foreach ($categories as $slug => $data) {
            try {
                // Check if exists
                $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmt->execute([$slug]);
                
                if (!$stmt->fetch()) {
                    // Insert new category
                    $insert = $db->prepare("
                        INSERT INTO categories (slug, name, description, color, icon, sort_order) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insert->execute([
                        $slug,
                        $data['name'],
                        $data['description'] ?? '',
                        $data['color'] ?? '#007bff',
                        $data['icon'] ?? 'fas fa-folder',
                        $order
                    ]);
                }
            } catch (PDOException $e) {
                // Skip errors, category might already exist
            }
            $order++;
        }
    }
    
    /**
     * SQLite Schema (Local development)
     */
    private static function initSQLiteSchema(PDO $db): bool {
        $schema = "
            -- Categorii
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                color TEXT DEFAULT '#007bff',
                icon TEXT DEFAULT 'fas fa-folder',
                sort_order INTEGER DEFAULT 0,
                parent_slug TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_slug);
            
            -- Articole
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                content TEXT,
                excerpt TEXT,
                category_slug TEXT,
                cover_image TEXT,
                tags TEXT,
                status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'published', 'archived')),
                views INTEGER DEFAULT 0,
                author TEXT DEFAULT 'Admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                published_at DATETIME,
                FOREIGN KEY (category_slug) REFERENCES categories(slug)
            );
            
            CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
            CREATE INDEX IF NOT EXISTS idx_posts_category ON posts(category_slug);
            CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at DESC);
            
            -- Sondaje
            CREATE TABLE IF NOT EXISTS polls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                question TEXT NOT NULL,
                description TEXT,
                active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME
            );
            
            -- Opțiuni sondaje
            CREATE TABLE IF NOT EXISTS poll_options (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poll_id INTEGER NOT NULL,
                option_text TEXT NOT NULL,
                votes INTEGER DEFAULT 0,
                sort_order INTEGER DEFAULT 0,
                FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
            );
            
            -- Voturi sondaje
            CREATE TABLE IF NOT EXISTS poll_votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poll_id INTEGER NOT NULL,
                option_id INTEGER NOT NULL,
                ip_hash TEXT NOT NULL,
                voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
            );
            
            CREATE INDEX IF NOT EXISTS idx_poll_votes_ip ON poll_votes(poll_id, ip_hash);
            
            -- Comentarii
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_slug TEXT NOT NULL,
                author_name TEXT NOT NULL,
                author_email TEXT,
                content TEXT NOT NULL,
                ip_hash TEXT,
                approved INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_slug) REFERENCES posts(slug) ON DELETE CASCADE
            );
            
            CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_slug);
            CREATE INDEX IF NOT EXISTS idx_comments_approved ON comments(approved);
            
            -- Utilizatori admin
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'editor' CHECK(role IN ('admin', 'editor')),
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            -- Statistici
            CREATE TABLE IF NOT EXISTS stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER,
                date DATE NOT NULL,
                views INTEGER DEFAULT 0,
                unique_visitors INTEGER DEFAULT 0,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                UNIQUE(post_id, date)
            );
            
            -- Setări site
            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            -- Rate limiting
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_hash TEXT NOT NULL,
                action_type TEXT NOT NULL,
                attempts INTEGER DEFAULT 1,
                first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(ip_hash, action_type)
            );
        ";
        
        try {
            $db->exec($schema);
            
            // Auto-sync categories from config
            self::syncCategoriesFromConfig($db);
            
            return true;
        } catch (PDOException $e) {
            error_log("SQLite Schema initialization failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Query helper - returns all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Query helper - returns single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Query helper - returns single value
     */
    public static function fetchValue(string $sql, array $params = []) {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Execute query and return affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert and return last insert ID
     */
    public static function insert(string $sql, array $params = []): int {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getInstance()->rollBack();
    }
}
