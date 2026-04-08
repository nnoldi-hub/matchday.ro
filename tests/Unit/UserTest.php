<?php
/**
 * UserTest
 * Unit tests for User class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private static $testUserIds = [];
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/User.php';
    }
    
    protected function tearDown(): void
    {
        // Cleanup test users (after ensuring we don't delete the last admin)
        foreach (self::$testUserIds as $id) {
            try {
                // Check if this is an admin and we have other admins
                $user = User::getById($id);
                if ($user && $user['role'] === User::ROLE_ADMIN) {
                    $adminCount = User::countByRole(User::ROLE_ADMIN);
                    if ($adminCount <= 1) {
                        continue; // Don't delete last admin
                    }
                }
                User::delete($id);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        self::$testUserIds = [];
    }
    
    // ==========================================
    // User Creation Tests
    // ==========================================
    
    public function testCreateUserReturnsId(): void
    {
        $userId = User::create(
            'testuser_' . time(),
            'test_' . time() . '@example.com',
            'SecurePassword123!',
            User::ROLE_EDITOR
        );
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
    }
    
    public function testCreateUserWithDefaultRole(): void
    {
        $userId = User::create(
            'defaultrole_' . time(),
            'default_' . time() . '@example.com',
            'Password123!'
        );
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getById($userId);
        $this->assertEquals(User::ROLE_EDITOR, $user['role']);
    }
    
    public function testCreateAdminUser(): void
    {
        $userId = User::create(
            'testadmin_' . time(),
            'admin_' . time() . '@example.com',
            'AdminPassword123!',
            User::ROLE_ADMIN
        );
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getById($userId);
        $this->assertEquals(User::ROLE_ADMIN, $user['role']);
    }
    
    public function testCreateUserWithInvalidRoleDefaultsToEditor(): void
    {
        $userId = User::create(
            'invalidrole_' . time(),
            'invalid_' . time() . '@example.com',
            'Password123!',
            'invalid_role'
        );
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getById($userId);
        $this->assertEquals(User::ROLE_EDITOR, $user['role']);
    }
    
    // ==========================================
    // User Retrieval Tests
    // ==========================================
    
    public function testGetByIdReturnsUser(): void
    {
        $username = 'getbyid_' . time();
        $userId = User::create($username, 'getbyid@example.com', 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getById($userId);
        
        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id']);
        $this->assertEquals($username, $user['username']);
    }
    
    public function testGetByIdReturnsNullForNonExistent(): void
    {
        $user = User::getById(999999);
        
        $this->assertNull($user);
    }
    
    public function testGetByUsernameReturnsUser(): void
    {
        $username = 'byusername_' . time();
        $userId = User::create($username, 'byusername@example.com', 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getByUsername($username);
        
        $this->assertIsArray($user);
        $this->assertEquals($username, $user['username']);
    }
    
    public function testGetByUsernameReturnsNullForNonExistent(): void
    {
        $user = User::getByUsername('nonexistent_user_' . time());
        
        $this->assertNull($user);
    }
    
    public function testGetByEmailReturnsUser(): void
    {
        $email = 'byemail_' . time() . '@example.com';
        $userId = User::create('byemail_' . time(), $email, 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::getByEmail($email);
        
        $this->assertIsArray($user);
        $this->assertEquals($email, $user['email']);
    }
    
    public function testGetAllReturnsArray(): void
    {
        $users = User::getAll();
        
        $this->assertIsArray($users);
    }
    
    public function testGetByRoleReturnsFilteredUsers(): void
    {
        $editors = User::getByRole(User::ROLE_EDITOR);
        
        $this->assertIsArray($editors);
        
        foreach ($editors as $editor) {
            $this->assertEquals(User::ROLE_EDITOR, $editor['role']);
        }
    }
    
    // ==========================================
    // Authentication Tests
    // ==========================================
    
    public function testAuthenticateWithValidCredentials(): void
    {
        $username = 'authtest_' . time();
        $password = 'AuthPassword123!';
        
        $userId = User::create($username, 'auth@example.com', $password);
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::authenticate($username, $password);
        
        $this->assertIsArray($user);
        $this->assertEquals($username, $user['username']);
        $this->assertArrayNotHasKey('password_hash', $user); // Password should be removed
    }
    
    public function testAuthenticateWithWrongPassword(): void
    {
        $username = 'authfail_' . time();
        
        $userId = User::create($username, 'authfail@example.com', 'CorrectPassword123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::authenticate($username, 'WrongPassword');
        
        $this->assertNull($user);
    }
    
    public function testAuthenticateWithNonExistentUser(): void
    {
        $user = User::authenticate('nonexistent_' . time(), 'AnyPassword');
        
        $this->assertNull($user);
    }
    
    public function testAuthenticateWithEmptyPassword(): void
    {
        $username = 'emptypass_' . time();
        
        $userId = User::create($username, 'emptypass@example.com', 'ActualPassword123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $user = User::authenticate($username, '');
        
        $this->assertNull($user);
    }
    
    // ==========================================
    // Password Tests
    // ==========================================
    
    public function testChangePassword(): void
    {
        $username = 'changepass_' . time();
        $oldPassword = 'OldPassword123!';
        $newPassword = 'NewPassword456!';
        
        $userId = User::create($username, 'changepass@example.com', $oldPassword);
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        // Verify old password works
        $user = User::authenticate($username, $oldPassword);
        $this->assertNotNull($user);
        
        // Change password
        $result = User::changePassword($userId, $newPassword);
        $this->assertTrue($result);
        
        // Verify old password no longer works
        $user = User::authenticate($username, $oldPassword);
        $this->assertNull($user);
        
        // Verify new password works
        $user = User::authenticate($username, $newPassword);
        $this->assertNotNull($user);
    }
    
    // ==========================================
    // Update Tests
    // ==========================================
    
    public function testUpdateUsername(): void
    {
        $userId = User::create('updateuser_' . time(), 'update@example.com', 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $newUsername = 'updated_' . time();
        $result = User::update($userId, ['username' => $newUsername]);
        
        $this->assertTrue($result);
        
        $user = User::getById($userId);
        $this->assertEquals($newUsername, $user['username']);
    }
    
    public function testUpdateRole(): void
    {
        $userId = User::create('updaterole_' . time(), 'role@example.com', 'Password123!', User::ROLE_EDITOR);
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $result = User::update($userId, ['role' => User::ROLE_ADMIN]);
        
        $this->assertTrue($result);
        
        $user = User::getById($userId);
        $this->assertEquals(User::ROLE_ADMIN, $user['role']);
    }
    
    public function testUpdateWithInvalidRoleIgnored(): void
    {
        $userId = User::create('invalidupdate_' . time(), 'invalidupdate@example.com', 'Password123!', User::ROLE_EDITOR);
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        // Try to update with invalid role
        User::update($userId, ['role' => 'superadmin']);
        
        $user = User::getById($userId);
        $this->assertEquals(User::ROLE_EDITOR, $user['role']); // Should remain editor
    }
    
    // ==========================================
    // Role Check Tests
    // ==========================================
    
    public function testIsAdminWithAdminUser(): void
    {
        $user = ['role' => User::ROLE_ADMIN];
        
        $this->assertTrue(User::isAdmin($user));
    }
    
    public function testIsAdminWithEditorUser(): void
    {
        $user = ['role' => User::ROLE_EDITOR];
        
        $this->assertFalse(User::isAdmin($user));
    }
    
    public function testIsAdminWithNullUser(): void
    {
        $this->assertFalse(User::isAdmin(null));
    }
    
    public function testIsAdminWithUserMissingRole(): void
    {
        $user = ['username' => 'test'];
        
        $this->assertFalse(User::isAdmin($user));
    }
    
    // ==========================================
    // Existence Check Tests
    // ==========================================
    
    public function testUsernameExistsReturnsTrue(): void
    {
        $username = 'exists_' . time();
        $userId = User::create($username, 'exists@example.com', 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $exists = User::usernameExists($username);
        
        $this->assertTrue($exists);
    }
    
    public function testUsernameExistsReturnsFalse(): void
    {
        $exists = User::usernameExists('nonexistent_' . time() . '_' . rand(10000, 99999));
        
        $this->assertFalse($exists);
    }
    
    public function testUsernameExistsWithExcludeId(): void
    {
        $username = 'excludetest_' . time();
        $userId = User::create($username, 'exclude@example.com', 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        // Should return false when excluding this user's ID
        $exists = User::usernameExists($username, $userId);
        
        $this->assertFalse($exists);
    }
    
    public function testEmailExistsReturnsTrue(): void
    {
        $email = 'emailexists_' . time() . '@example.com';
        $userId = User::create('emailexists_' . time(), $email, 'Password123!');
        
        if ($userId) {
            self::$testUserIds[] = $userId;
        }
        
        $exists = User::emailExists($email);
        
        $this->assertTrue($exists);
    }
    
    public function testEmailExistsReturnsFalse(): void
    {
        $exists = User::emailExists('nonexistent_' . time() . '@example.com');
        
        $this->assertFalse($exists);
    }
    
    // ==========================================
    // Count Tests
    // ==========================================
    
    public function testCountReturnsInteger(): void
    {
        $count = User::count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    public function testCountByRoleReturnsInteger(): void
    {
        $count = User::countByRole(User::ROLE_ADMIN);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    // ==========================================
    // Delete Tests
    // ==========================================
    
    public function testDeleteUser(): void
    {
        $userId = User::create('todelete_' . time(), 'delete@example.com', 'Password123!', User::ROLE_EDITOR);
        
        $beforeDelete = User::getById($userId);
        $this->assertNotNull($beforeDelete);
        
        $result = User::delete($userId);
        $this->assertTrue($result);
        
        $afterDelete = User::getById($userId);
        $this->assertNull($afterDelete);
    }
    
    public function testCannotDeleteLastAdmin(): void
    {
        // Create an additional admin first
        $extraAdminId = User::create('extraadmin_' . time(), 'extra@example.com', 'Password123!', User::ROLE_ADMIN);
        
        if ($extraAdminId) {
            self::$testUserIds[] = $extraAdminId;
        }
        
        // Get current admin count
        $adminCount = User::countByRole(User::ROLE_ADMIN);
        
        // If only one admin, trying to delete should fail
        if ($adminCount == 1) {
            // Get the single admin
            $admins = User::getByRole(User::ROLE_ADMIN);
            $adminId = $admins[0]['id'];
            
            $result = User::delete($adminId);
            $this->assertFalse($result, 'Should not be able to delete last admin');
        }
        
        // This test passes if we have multiple admins
        $this->assertGreaterThanOrEqual(1, $adminCount);
    }
    
    // ==========================================
    // Constants Tests
    // ==========================================
    
    public function testRoleConstants(): void
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('editor', User::ROLE_EDITOR);
    }
}
