<?php
/**
 * User Model - MatchDay.ro
 * Multi-user authentication with roles (admin/editor)
 */

require_once(__DIR__ . '/../config/database.php');

class User {
    
    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    
    /**
     * Get user by ID
     */
    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, role, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get user by username
     */
    public static function getByUsername(string $username): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get user by email
     */
    public static function getByEmail(string $email): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all users
     */
    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, username, email, role, last_login, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Create new user
     */
    public static function create(string $username, string $email, string $password, string $role = self::ROLE_EDITOR): ?int {
        $db = Database::getInstance();
        
        // Validate role
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_EDITOR])) {
            $role = self::ROLE_EDITOR;
        }
        
        // Hash password with Argon2id
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        try {
            if (Database::isMySQL()) {
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            } else {
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
            }
            $stmt->execute([$username, $email ?: null, $passwordHash, $role]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User create failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user
     */
    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        
        $fields = [];
        $values = [];
        
        if (isset($data['username'])) {
            $fields[] = 'username = ?';
            $values[] = $data['username'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        
        if (isset($data['role']) && in_array($data['role'], [self::ROLE_ADMIN, self::ROLE_EDITOR])) {
            $fields[] = 'role = ?';
            $values[] = $data['role'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        
        try {
            $stmt = $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("User update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user
     */
    public static function delete(int $id): bool {
        $db = Database::getInstance();
        
        // Prevent deleting last admin
        $user = self::getById($id);
        if ($user && $user['role'] === self::ROLE_ADMIN) {
            $adminCount = self::countByRole(self::ROLE_ADMIN);
            if ($adminCount <= 1) {
                return false; // Cannot delete last admin
            }
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Authenticate user
     */
    public static function authenticate(string $username, string $password): ?array {
        $user = self::getByUsername($username);
        
        if (!$user) {
            return null;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        
        // Update last login
        self::updateLastLogin($user['id']);
        
        // Return user without password hash
        unset($user['password_hash']);
        return $user;
    }
    
    /**
     * Update last login timestamp
     */
    public static function updateLastLogin(int $id): bool {
        $db = Database::getInstance();
        
        if (Database::isMySQL()) {
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        } else {
            $stmt = $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
        }
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Count users by role
     */
    public static function countByRole(string $role): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Count total users
     */
    public static function count(): int {
        $db = Database::getInstance();
        return (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
    
    /**
     * Check if username exists
     */
    public static function usernameExists(string $username, ?int $excludeId = null): bool {
        $db = Database::getInstance();
        
        if ($excludeId) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
        }
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if email exists
     */
    public static function emailExists(string $email, ?int $excludeId = null): bool {
        $db = Database::getInstance();
        
        if ($excludeId) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
        }
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Change password
     */
    public static function changePassword(int $id, string $newPassword): bool {
        return self::update($id, ['password' => $newPassword]);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin(?array $user): bool {
        return $user && isset($user['role']) && $user['role'] === self::ROLE_ADMIN;
    }
    
    /**
     * Get current logged in user from session
     */
    public static function getCurrentUser(): ?array {
        if (isset($_SESSION['user_id'])) {
            return self::getById($_SESSION['user_id']);
        }
        return null;
    }
    
    /**
     * Create default admin if no users exist
     */
    public static function createDefaultAdmin(): bool {
        if (self::count() > 0) {
            return false; // Users already exist
        }
        
        // Create default admin with configurable password
        $defaultPassword = defined('DEFAULT_ADMIN_PASSWORD') ? DEFAULT_ADMIN_PASSWORD : 'admin123';
        $id = self::create('admin', 'admin@matchday.ro', $defaultPassword, self::ROLE_ADMIN);
        
        return $id !== null;
    }
    
    /**
     * Get role display name
     */
    public static function getRoleDisplayName(string $role): string {
        return match($role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_EDITOR => 'Editor',
            default => 'Necunoscut'
        };
    }
    
    /**
     * Get available roles
     */
    public static function getAvailableRoles(): array {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_EDITOR => 'Editor'
        ];
    }
}
