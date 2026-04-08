<?php
/**
 * SecurityTest
 * Unit tests for Security class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    private string $rateLimitsFile;
    
    protected function setUp(): void
    {
        // Start session for CSRF tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        
        // Use temp file for rate limits
        $this->rateLimitsFile = dirname(__DIR__, 2) . '/data/rate_limits_test.json';
    }
    
    protected function tearDown(): void
    {
        $_SESSION = [];
        
        // Cleanup test rate limits file
        if (file_exists($this->rateLimitsFile)) {
            unlink($this->rateLimitsFile);
        }
    }
    
    // ==========================================
    // CSRF Token Tests
    // ==========================================
    
    public function testGenerateCSRFTokenCreatesNewToken(): void
    {
        $token = Security::generateCSRFToken();
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }
    
    public function testGenerateCSRFTokenReturnsSameTokenOnMultipleCalls(): void
    {
        $token1 = Security::generateCSRFToken();
        $token2 = Security::generateCSRFToken();
        
        $this->assertEquals($token1, $token2);
    }
    
    public function testValidateCSRFTokenWithValidToken(): void
    {
        $token = Security::generateCSRFToken();
        
        $this->assertTrue(Security::validateCSRFToken($token));
    }
    
    public function testValidateCSRFTokenWithInvalidToken(): void
    {
        Security::generateCSRFToken();
        
        $this->assertFalse(Security::validateCSRFToken('invalid_token'));
    }
    
    public function testValidateCSRFTokenWithEmptyToken(): void
    {
        Security::generateCSRFToken();
        
        $this->assertFalse(Security::validateCSRFToken(''));
    }
    
    public function testValidateCSRFTokenWithNoSessionToken(): void
    {
        // Session is empty
        $this->assertFalse(Security::validateCSRFToken('any_token'));
    }
    
    // ==========================================
    // Input Sanitization Tests
    // ==========================================
    
    public function testSanitizeInputRemovesHTML(): void
    {
        $input = '<script>alert("XSS")</script>';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('</script>', $sanitized);
    }
    
    public function testSanitizeInputEscapesQuotes(): void
    {
        $input = 'Test "quotes" and \'apostrophes\'';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringContainsString('&quot;', $sanitized);
        $this->assertStringContainsString('&#039;', $sanitized);
    }
    
    public function testSanitizeInputTrimsWhitespace(): void
    {
        $input = '  trimmed text  ';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertEquals('trimmed text', $sanitized);
    }
    
    public function testSanitizeInputPreservesNormalText(): void
    {
        $input = 'Normal text without special chars';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertEquals($input, $sanitized);
    }
    
    public function testSanitizeInputHandlesEmptyString(): void
    {
        $sanitized = Security::sanitizeInput('');
        
        $this->assertEquals('', $sanitized);
    }
    
    public function testSanitizeInputHandlesUnicode(): void
    {
        $input = 'Diacritice: ăîâșț ĂÎÂȘȚ';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertEquals($input, $sanitized);
    }
    
    public function testSanitizeInputPreventsXSSInAttributes(): void
    {
        $input = 'onclick="javascript:alert(1)"';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringContainsString('&quot;', $sanitized);
    }
    
    // ==========================================
    // Email Validation Tests
    // ==========================================
    
    public function testValidateEmailWithValidEmail(): void
    {
        $this->assertTrue(Security::validateEmail('test@example.com'));
        $this->assertTrue(Security::validateEmail('user.name@domain.co.uk'));
        $this->assertTrue(Security::validateEmail('user+tag@example.org'));
    }
    
    public function testValidateEmailWithInvalidEmail(): void
    {
        $this->assertFalse(Security::validateEmail('invalid'));
        $this->assertFalse(Security::validateEmail('missing@domain'));
        $this->assertFalse(Security::validateEmail('@nodomain.com'));
        $this->assertFalse(Security::validateEmail('spaces in@email.com'));
    }
    
    public function testValidateEmailWithEmptyString(): void
    {
        $this->assertFalse(Security::validateEmail(''));
    }
    
    // ==========================================
    // Password Hashing Tests
    // ==========================================
    
    public function testHashPasswordCreatesValidHash(): void
    {
        $password = 'SecurePassword123!';
        $hash = Security::hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$argon2id$', $hash);
    }
    
    public function testHashPasswordCreatesDifferentHashesForSamePassword(): void
    {
        $password = 'SamePassword';
        $hash1 = Security::hashPassword($password);
        $hash2 = Security::hashPassword($password);
        
        // Hashes should be different due to random salt
        $this->assertNotEquals($hash1, $hash2);
    }
    
    public function testVerifyPasswordWithCorrectPassword(): void
    {
        $password = 'MySecretPassword!';
        $hash = Security::hashPassword($password);
        
        $this->assertTrue(Security::verifyPassword($password, $hash));
    }
    
    public function testVerifyPasswordWithIncorrectPassword(): void
    {
        $password = 'MySecretPassword!';
        $hash = Security::hashPassword($password);
        
        $this->assertFalse(Security::verifyPassword('WrongPassword', $hash));
    }
    
    public function testVerifyPasswordWithEmptyPassword(): void
    {
        $hash = Security::hashPassword('ValidPassword');
        
        $this->assertFalse(Security::verifyPassword('', $hash));
    }
    
    // ==========================================
    // Rate Limiting Tests
    // ==========================================
    
    public function testRateLimitCheckAllowsFirstRequest(): void
    {
        $result = Security::rateLimitCheck('test_key_' . time(), 5, 300);
        
        $this->assertTrue($result);
    }
    
    public function testRateLimitCheckAllowsUpToLimit(): void
    {
        $key = 'limit_test_' . time();
        
        // First 5 requests should succeed
        for ($i = 0; $i < 5; $i++) {
            $result = Security::rateLimitCheck($key, 5, 300);
            $this->assertTrue($result, "Request {$i} should be allowed");
        }
    }
    
    public function testRateLimitCheckBlocksAfterLimit(): void
    {
        $key = 'block_test_' . time();
        
        // Make 5 requests (limit)
        for ($i = 0; $i < 5; $i++) {
            Security::rateLimitCheck($key, 5, 300);
        }
        
        // 6th request should be blocked
        $result = Security::rateLimitCheck($key, 5, 300);
        $this->assertFalse($result);
    }
    
    // ==========================================
    // Edge Cases and Security Tests
    // ==========================================
    
    public function testSanitizeInputWithSQLInjectionAttempt(): void
    {
        $input = "'; DROP TABLE users; --";
        $sanitized = Security::sanitizeInput($input);
        
        // Should escape the special characters
        $this->assertStringContainsString('&#039;', $sanitized);
    }
    
    public function testSanitizeInputWithNestedTags(): void
    {
        $input = '<div><script>evil()</script></div>';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringNotContainsString('<', $sanitized);
    }
    
    public function testSanitizeInputWithEncodedEntities(): void
    {
        // Already encoded entities should be double-encoded
        $input = '&lt;script&gt;';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertEquals('&amp;lt;script&amp;gt;', $sanitized);
    }
    
    public function testCSRFTokenIsStoredInSession(): void
    {
        $token = Security::generateCSRFToken();
        
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }
}
