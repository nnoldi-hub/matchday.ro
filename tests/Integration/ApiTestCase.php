<?php
/**
 * ApiTestCase - Base class for integration tests
 * MatchDay.ro - Tests full workflow through multiple components
 * 
 * Note: These tests verify class integrations, not HTTP endpoints directly
 * (PHP's exit() in API files prevents direct inclusion testing)
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../bootstrap.php');

abstract class ApiTestCase extends TestCase
{
    /**
     * Helper to generate unique test identifiers
     */
    protected function uniqueId(string $prefix = 'test'): string
    {
        return $prefix . '_' . time() . '_' . mt_rand(1000, 9999);
    }
    
    /**
     * Helper to create a test post for workflows
     */
    protected function createTestPost(array $data = []): int
    {
        require_once(__DIR__ . '/../../includes/Post.php');
        
        $defaults = [
            'title' => $this->uniqueId('Post'),
            'content' => 'Test content for integration testing',
            'excerpt' => 'Test excerpt',
            'status' => 'published',
            'author_id' => 1
        ];
        
        return \Post::create(array_merge($defaults, $data));
    }
    
    /**
     * Helper to create a test poll
     */
    protected function createTestPoll(array $data = []): int
    {
        require_once(__DIR__ . '/../../includes/Poll.php');
        
        $defaults = [
            'title' => $this->uniqueId('Poll'),
            'slug' => $this->uniqueId('poll'),
            'options' => ['Option A', 'Option B', 'Option C'],
            'active' => true
        ];
        
        return \Poll::create(array_merge($defaults, $data));
    }
    
    /**
     * Helper to create a test user
     */
    protected function createTestUser(array $data = []): int
    {
        require_once(__DIR__ . '/../../includes/User.php');
        
        $defaults = [
            'username' => $this->uniqueId('user'),
            'email' => $this->uniqueId('user') . '@test.com',
            'password' => 'TestPassword123!',
            'role' => 'editor'
        ];
        
        return \User::create(array_merge($defaults, $data));
    }
    
    /**
     * Simulate IP address for rate limiting tests
     */
    protected function withIP(string $ip, callable $callback)
    {
        $originalIP = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = $ip;
        
        try {
            return $callback();
        } finally {
            if ($originalIP !== null) {
                $_SERVER['REMOTE_ADDR'] = $originalIP;
            } else {
                unset($_SERVER['REMOTE_ADDR']);
            }
        }
    }
}
