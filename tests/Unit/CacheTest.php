<?php
/**
 * CacheTest
 * Unit tests for Cache class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private static $testKeys = [];
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/config/cache.php';
    }
    
    protected function tearDown(): void
    {
        // Clean up test cache entries
        foreach (self::$testKeys as $key) {
            try {
                Cache::delete($key);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        self::$testKeys = [];
    }
    
    // ==========================================
    // Basic Get/Set Tests
    // ==========================================
    
    public function testSetAndGetValue(): void
    {
        $key = 'test_key_' . time();
        $value = 'test_value';
        
        self::$testKeys[] = $key;
        
        Cache::set($key, $value);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($value, $retrieved);
    }
    
    public function testGetNonExistentKey(): void
    {
        $result = Cache::get('nonexistent_key_' . time() . rand(10000, 99999));
        
        $this->assertNull($result);
    }
    
    public function testSetArrayValue(): void
    {
        $key = 'array_key_' . time();
        $value = ['name' => 'Test', 'count' => 123, 'nested' => ['a' => 1]];
        
        self::$testKeys[] = $key;
        
        Cache::set($key, $value);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($value, $retrieved);
        $this->assertIsArray($retrieved);
        $this->assertEquals('Test', $retrieved['name']);
        $this->assertEquals(123, $retrieved['count']);
    }
    
    public function testSetObjectValue(): void
    {
        $key = 'object_key_' . time();
        $value = new stdClass();
        $value->name = 'Test Object';
        $value->id = 42;
        
        self::$testKeys[] = $key;
        
        Cache::set($key, $value);
        $retrieved = Cache::get($key);
        
        $this->assertInstanceOf(stdClass::class, $retrieved);
        $this->assertEquals('Test Object', $retrieved->name);
        $this->assertEquals(42, $retrieved->id);
    }
    
    public function testSetIntegerValue(): void
    {
        $key = 'int_key_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 12345);
        $retrieved = Cache::get($key);
        
        $this->assertIsInt($retrieved);
        $this->assertEquals(12345, $retrieved);
    }
    
    public function testSetBooleanValue(): void
    {
        $key = 'bool_key_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, true);
        $retrieved = Cache::get($key);
        
        $this->assertTrue($retrieved);
    }
    
    public function testSetNullValue(): void
    {
        $key = 'null_key_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, null);
        $retrieved = Cache::get($key);
        
        // Note: null is a valid cached value
        $this->assertNull($retrieved);
    }
    
    // ==========================================
    // Expiration Tests
    // ==========================================
    
    public function testCacheExpiresAfterTTL(): void
    {
        $key = 'expire_key_' . time();
        
        self::$testKeys[] = $key;
        
        // Set with 1 second TTL
        Cache::set($key, 'short_lived', 1);
        
        // Should exist immediately
        $this->assertEquals('short_lived', Cache::get($key));
        
        // Wait for expiration
        sleep(2);
        
        // Should be null after expiration
        $this->assertNull(Cache::get($key));
    }
    
    public function testCacheWithCustomTTL(): void
    {
        $key = 'custom_ttl_' . time();
        
        self::$testKeys[] = $key;
        
        // Set with 10 second TTL
        Cache::set($key, 'longer_lived', 10);
        
        // Should still exist after 1 second
        sleep(1);
        $this->assertEquals('longer_lived', Cache::get($key));
    }
    
    public function testGetWithMaxAge(): void
    {
        $key = 'max_age_' . time();
        
        self::$testKeys[] = $key;
        
        // Set with long TTL
        Cache::set($key, 'test_value', 3600);
        
        // Get with short max age
        sleep(2);
        $result = Cache::get($key, 1); // Max age 1 second
        
        // Even though cached, if caller wants fresh data, maxAge isn't enforced in current implementation
        // This tests the current behavior
        $this->assertNotNull($result);
    }
    
    // ==========================================
    // Delete Tests
    // ==========================================
    
    public function testDeleteKey(): void
    {
        $key = 'delete_key_' . time();
        
        Cache::set($key, 'to_delete');
        
        // Verify it exists
        $this->assertEquals('to_delete', Cache::get($key));
        
        // Delete it
        Cache::delete($key);
        
        // Should be gone
        $this->assertNull(Cache::get($key));
    }
    
    public function testDeleteNonExistentKey(): void
    {
        // Should not throw error
        Cache::delete('nonexistent_' . time());
        
        $this->assertTrue(true); // No exception = pass
    }
    
    // ==========================================
    // Clear Tests
    // ==========================================
    
    public function testClearAllCache(): void
    {
        // Set multiple cache entries
        $keys = [];
        for ($i = 0; $i < 3; $i++) {
            $key = 'clear_test_' . time() . '_' . $i;
            $keys[] = $key;
            Cache::set($key, "value_$i");
        }
        
        // Verify they exist
        foreach ($keys as $key) {
            $this->assertNotNull(Cache::get($key));
        }
        
        // Clear all
        Cache::clear();
        
        // All should be gone
        foreach ($keys as $key) {
            $this->assertNull(Cache::get($key));
        }
    }
    
    // ==========================================
    // Key Handling Tests
    // ==========================================
    
    public function testKeyWithSpecialCharacters(): void
    {
        $key = 'special_!@#$%^&*()_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 'special_value');
        $retrieved = Cache::get($key);
        
        $this->assertEquals('special_value', $retrieved);
    }
    
    public function testKeyWithUnicode(): void
    {
        $key = 'unicode_ăîâșț_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 'unicode_value');
        $retrieved = Cache::get($key);
        
        $this->assertEquals('unicode_value', $retrieved);
    }
    
    public function testLongKey(): void
    {
        $key = str_repeat('a', 500) . '_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 'long_key_value');
        $retrieved = Cache::get($key);
        
        $this->assertEquals('long_key_value', $retrieved);
    }
    
    // ==========================================
    // Overwrite Tests
    // ==========================================
    
    public function testOverwriteExistingKey(): void
    {
        $key = 'overwrite_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 'original');
        $this->assertEquals('original', Cache::get($key));
        
        Cache::set($key, 'updated');
        $this->assertEquals('updated', Cache::get($key));
    }
    
    // ==========================================
    // Init Tests
    // ==========================================
    
    public function testInitCreatesCacheDirectory(): void
    {
        // Cache::init() is called by get/set, so just verify cache works
        $key = 'init_test_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 'init_value');
        
        // If no errors, directory was created/exists
        $this->assertEquals('init_value', Cache::get($key));
    }
    
    // ==========================================
    // Edge Cases
    // ==========================================
    
    public function testEmptyStringValue(): void
    {
        $key = 'empty_string_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, '');
        $retrieved = Cache::get($key);
        
        $this->assertEquals('', $retrieved);
        $this->assertIsString($retrieved);
    }
    
    public function testZeroValue(): void
    {
        $key = 'zero_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, 0);
        $retrieved = Cache::get($key);
        
        $this->assertEquals(0, $retrieved);
        $this->assertIsInt($retrieved);
    }
    
    public function testFalseValue(): void
    {
        $key = 'false_' . time();
        
        self::$testKeys[] = $key;
        
        Cache::set($key, false);
        $retrieved = Cache::get($key);
        
        $this->assertFalse($retrieved);
    }
    
    public function testLargeValue(): void
    {
        $key = 'large_' . time();
        
        self::$testKeys[] = $key;
        
        // Create a large array
        $value = array_fill(0, 1000, 'Lorem ipsum dolor sit amet');
        
        Cache::set($key, $value);
        $retrieved = Cache::get($key);
        
        $this->assertCount(1000, $retrieved);
        $this->assertEquals($value, $retrieved);
    }
}
