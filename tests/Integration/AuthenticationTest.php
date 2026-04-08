<?php
/**
 * AuthenticationTest - Integration tests for user authentication workflow
 * MatchDay.ro - Tests: login → session → permissions → logout
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../bootstrap.php');
require_once(__DIR__ . '/../../includes/User.php');

class AuthenticationTest extends TestCase
{
    private static $testUserId;
    private static $testUsername;
    
    public static function setUpBeforeClass(): void
    {
        // Clean up test users
        \Database::execute("DELETE FROM users WHERE username LIKE '%authtest%'");
        
        // Create a test user
        self::$testUsername = 'authtest_' . time();
        self::$testUserId = \User::create(
            self::$testUsername,
            self::$testUsername . '@test.com',
            'TestPassword123!',
            'editor'
        );
    }
    
    public static function tearDownAfterClass(): void
    {
        \Database::execute("DELETE FROM users WHERE username LIKE '%authtest%'");
    }
    
    // ======================= LOGIN WORKFLOW =======================
    
    #[Test]
    public function testSuccessfulLoginWorkflow(): void
    {
        // Attempt login with correct credentials
        $result = \User::authenticate(self::$testUsername, 'TestPassword123!');
        
        $this->assertIsArray($result);
        $this->assertEquals(self::$testUserId, $result['id']);
        $this->assertEquals(self::$testUsername, $result['username']);
        $this->assertEquals('editor', $result['role']);
    }
    
    #[Test]
    public function testLoginWithWrongPassword(): void
    {
        $result = \User::authenticate(self::$testUsername, 'WrongPassword123!');
        
        $this->assertNull($result);
    }
    
    #[Test]
    public function testLoginWithNonExistentUser(): void
    {
        $result = \User::authenticate('nonexistent_user_xyz', 'SomePassword');
        
        $this->assertNull($result);
    }
    
    #[Test]
    public function testLoginWithEmptyCredentials(): void
    {
        $result = \User::authenticate('', '');
        
        $this->assertNull($result);
    }
    
    // ======================= ROLE-BASED ACCESS =======================
    
    #[Test]
    public function testRolePermissions(): void
    {
        // Skip if hasPermission method doesn't exist
        if (!method_exists('\User', 'hasPermission')) {
            $this->markTestSkipped('User::hasPermission() not implemented');
        }
        
        // Admin can do everything
        $this->assertTrue(\User::hasPermission('manage_posts', 'admin'));
        $this->assertTrue(\User::hasPermission('manage_users', 'admin'));
        $this->assertTrue(\User::hasPermission('manage_settings', 'admin'));
        
        // Editor has limited permissions
        $this->assertTrue(\User::hasPermission('manage_posts', 'editor'));
        $this->assertFalse(\User::hasPermission('manage_users', 'editor'));
        $this->assertFalse(\User::hasPermission('manage_settings', 'editor'));
    }
    
    #[Test]
    public function testUserRoleAssignment(): void
    {
        $userId = \User::create(
            'authtest_role_' . time(),
            'role_' . time() . '@test.com',
            'Password123!',
            'editor'
        );
        
        $user = \User::getById($userId);
        $this->assertEquals('editor', $user['role']);
        
        // Update role
        $updated = \User::update($userId, ['role' => 'admin']);
        $this->assertTrue($updated);
        
        $user = \User::getById($userId);
        $this->assertEquals('admin', $user['role']);
    }
    
    // ======================= PASSWORD MANAGEMENT =======================
    
    #[Test]
    public function testPasswordChangeWorkflow(): void
    {
        // Create user with known password
        $userId = \User::create(
            'authtest_pwchange_' . time(),
            'pwchange_' . time() . '@test.com',
            'OldPassword123!',
            'editor'
        );
        
        // Change password
        $changed = \User::changePassword($userId, 'NewPassword456!');
        $this->assertTrue($changed);
        
        // Old password should not work
        $user = \User::getById($userId);
        $oldWorks = \User::authenticate($user['username'], 'OldPassword123!');
        $this->assertNull($oldWorks);
        
        // New password should work
        $newWorks = \User::authenticate($user['username'], 'NewPassword456!');
        $this->assertNotNull($newWorks);
    }
    
    #[Test]
    public function testPasswordIsHashed(): void
    {
        $userId = \User::create(
            'authtest_hash_' . time(),
            'hash_' . time() . '@test.com',
            'PlainTextPassword',
            'editor'
        );
        
        // Get stored password hash
        $user = \Database::fetch(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        // If no password_hash column, try just password
        if (!$user) {
            $this->markTestSkipped('Cannot access password hash column');
        }
        
        $passwordHash = $user['password_hash'] ?? $user['password'] ?? null;
        
        $this->assertNotEquals('PlainTextPassword', $passwordHash);
        $this->assertTrue(password_verify('PlainTextPassword', $passwordHash));
    }
    
    // ======================= USER MANAGEMENT =======================
    
    #[Test]
    public function testCreateAndDeleteUser(): void
    {
        $username = 'authtest_delete_' . time();
        
        $userId = \User::create(
            $username,
            $username . '@test.com',
            'ToDelete123!',
            'editor'
        );
        
        $this->assertGreaterThan(0, $userId);
        
        // User exists
        $user = \User::getById($userId);
        $this->assertNotNull($user);
        
        // Delete user
        $deleted = \User::delete($userId);
        $this->assertTrue($deleted);
        
        // User no longer exists
        $user = \User::getById($userId);
        $this->assertNull($user);
    }
    
    #[Test]
    public function testDuplicateUsernameRejected(): void
    {
        $username = 'authtest_dup_' . time();
        
        // Create first user
        $id1 = \User::create(
            $username,
            $username . '@test.com',
            'Password123!',
            'editor'
        );
        
        // Try to create duplicate - may throw exception or return null
        try {
            $id2 = \User::create(
                $username,
                $username . '2@test.com',
                'Password123!',
                'editor'
            );
            // If no exception, second create should have failed (return null)
            $this->assertNull($id2, 'Duplicate username should not be created');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Exception thrown for duplicate username');
        }
    }
    
    #[Test]
    public function testDuplicateEmailRejected(): void
    {
        $email = 'authtest_dup_' . time() . '@test.com';
        
        // Create first user
        $id1 = \User::create(
            'authtest_dup1_' . time(),
            $email,
            'Password123!',
            'editor'
        );
        
        // Try to create with same email - may throw exception or return null
        try {
            $id2 = \User::create(
                'authtest_dup2_' . time(),
                $email,
                'Password123!',
                'editor'
            );
            // If no exception, second create should have failed (return null)
            $this->assertNull($id2, 'Duplicate email should not be created');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Exception thrown for duplicate email');
        }
    }
    
    // ======================= SESSION SIMULATION =======================
    
    #[Test]
    public function testLoginCreatesSessionData(): void
    {
        $user = \User::authenticate(self::$testUsername, 'TestPassword123!');
        
        // Simulate setting session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        
        // Verify session data
        $this->assertEquals(self::$testUserId, $_SESSION['user_id']);
        $this->assertEquals('editor', $_SESSION['user_role']);
        
        // Cleanup
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['username']);
    }
    
    // ======================= USER LISTING =======================
    
    #[Test]
    public function testGetAllUsers(): void
    {
        $users = \User::getAll();
        
        $this->assertIsArray($users);
        $this->assertGreaterThan(0, count($users));
        
        // Each user should have expected fields
        foreach ($users as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('role', $user);
        }
    }
    
    #[Test]
    public function testGetUsersByRole(): void
    {
        // Create admin user
        \User::create(
            'authtest_admin_' . time(),
            'admin_' . time() . '@test.com',
            'Admin123!',
            'admin'
        );
        
        // Get admins
        if (method_exists('\User', 'getByRole')) {
            $admins = \User::getByRole('admin');
            
            foreach ($admins as $user) {
                $this->assertEquals('admin', $user['role']);
            }
        } else {
            // Filter manually
            $allUsers = \User::getAll();
            $admins = array_filter($allUsers, fn($u) => $u['role'] === 'admin');
            
            $this->assertGreaterThan(0, count($admins));
        }
    }
    
    // ======================= EDGE CASES =======================
    
    #[Test]
    public function testLoginWithSQLInjectionAttempt(): void
    {
        // Should not cause SQL injection
        $result = \User::authenticate("admin' OR '1'='1", "password' OR '1'='1");
        
        $this->assertNull($result);
    }
    
    #[Test]
    public function testLoginWithXSSAttempt(): void
    {
        $result = \User::authenticate('<script>alert("xss")</script>', 'password');
        
        $this->assertNull($result);
    }
    
    #[Test]
    public function testUserUpdateWithoutPasswordChange(): void
    {
        $userId = \User::create(
            'authtest_nopasschange_' . time(),
            'nopasschange_' . time() . '@test.com',
            'Original123!',
            'editor'
        );
        
        // Update email without changing password
        $updated = \User::update($userId, [
            'email' => 'updated_' . time() . '@test.com'
        ]);
        
        $this->assertTrue($updated);
        
        // Original password should still work
        $user = \User::getById($userId);
        $auth = \User::authenticate($user['username'], 'Original123!');
        $this->assertNotNull($auth);
    }
}
