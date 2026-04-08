<?php
/**
 * PostTest
 * Unit tests for Post class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private static $testPostIds = [];
    
    public static function setUpBeforeClass(): void
    {
        // Include Post class
        require_once dirname(__DIR__, 2) . '/includes/Post.php';
    }
    
    protected function tearDown(): void
    {
        // Cleanup test posts
        foreach (self::$testPostIds as $id) {
            try {
                Post::delete($id);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        self::$testPostIds = [];
    }
    
    // ==========================================
    // Slug Generation Tests
    // ==========================================
    
    public function testGenerateSlugBasic(): void
    {
        $slug = Post::generateSlug('Test Article Title');
        
        $this->assertNotEmpty($slug);
        $this->assertIsString($slug);
        $this->assertEquals('test-article-title', $slug);
    }
    
    public function testGenerateSlugWithRomanianCharacters(): void
    {
        $slug = Post::generateSlug('Articol cu diacritice: ăîâșț');
        
        $this->assertEquals('articol-cu-diacritice-aiast', $slug);
    }
    
    public function testGenerateSlugWithUppercaseRomanianChars(): void
    {
        $slug = Post::generateSlug('ȘTEFAN ȘI ĂPĂRAREA');
        
        $this->assertStringNotContainsString('ș', $slug);
        $this->assertStringNotContainsString('Ș', $slug);
        $this->assertStringNotContainsString('ă', $slug);
    }
    
    public function testGenerateSlugRemovesSpecialCharacters(): void
    {
        $slug = Post::generateSlug('Test! Article? With @#$% Special');
        
        $this->assertStringNotContainsString('!', $slug);
        $this->assertStringNotContainsString('?', $slug);
        $this->assertStringNotContainsString('@', $slug);
        $this->assertEquals('test-article-with-special', $slug);
    }
    
    public function testGenerateSlugNormalizesWhitespace(): void
    {
        $slug = Post::generateSlug('Multiple   Spaces    Here');
        
        $this->assertStringNotContainsString('--', $slug);
        $this->assertEquals('multiple-spaces-here', $slug);
    }
    
    public function testGenerateSlugTrimsHyphens(): void
    {
        $slug = Post::generateSlug('  Leading and trailing spaces  ');
        
        $this->assertNotEquals('-', substr($slug, 0, 1));
        $this->assertNotEquals('-', substr($slug, -1));
    }
    
    public function testGenerateSlugWithNumbers(): void
    {
        $slug = Post::generateSlug('Arsenal 2-1 Chelsea UCL 2026');
        
        $this->assertStringContainsString('2', $slug);
        $this->assertStringContainsString('2026', $slug);
    }
    
    public function testGenerateSlugEmptyTitle(): void
    {
        $slug = Post::generateSlug('');
        
        $this->assertEquals('', $slug);
    }
    
    // ==========================================
    // Post CRUD Tests
    // ==========================================
    
    public function testCreatePostReturnsId(): void
    {
        $postId = Post::create([
            'title' => 'Test Post ' . time(),
            'content' => 'Test content',
            'excerpt' => 'Test excerpt',
            'status' => 'draft',
            'author' => 'Test Author'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $this->assertIsInt($postId);
        $this->assertGreaterThan(0, $postId);
    }
    
    public function testCreatePostWithCustomSlug(): void
    {
        $customSlug = 'custom-test-slug-' . time();
        
        $postId = Post::create([
            'title' => 'Test Post',
            'slug' => $customSlug,
            'content' => 'Content',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getById($postId);
        $this->assertEquals($customSlug, $post['slug']);
    }
    
    public function testGetByIdReturnsPost(): void
    {
        $postId = Post::create([
            'title' => 'Get By ID Test ' . time(),
            'content' => 'Test content',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getById($postId);
        
        $this->assertIsArray($post);
        $this->assertEquals($postId, $post['id']);
        $this->assertStringContainsString('Get By ID Test', $post['title']);
    }
    
    public function testGetByIdReturnsNullForNonExistent(): void
    {
        $post = Post::getById(999999);
        
        $this->assertNull($post);
    }
    
    public function testGetBySlugReturnsPost(): void
    {
        $uniqueSlug = 'test-slug-' . time() . '-' . rand(1000, 9999);
        
        $postId = Post::create([
            'title' => 'Slug Test',
            'slug' => $uniqueSlug,
            'content' => 'Content',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getBySlug($uniqueSlug);
        
        $this->assertIsArray($post);
        $this->assertEquals($uniqueSlug, $post['slug']);
    }
    
    public function testUpdatePost(): void
    {
        $postId = Post::create([
            'title' => 'Original Title',
            'content' => 'Original content',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $result = Post::update($postId, [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ]);
        
        $this->assertTrue($result);
        
        $updated = Post::getById($postId);
        $this->assertEquals('Updated Title', $updated['title']);
        $this->assertEquals('Updated content', $updated['content']);
    }
    
    public function testUpdatePostSetsPublishedAt(): void
    {
        $postId = Post::create([
            'title' => 'Draft Post',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $draft = Post::getById($postId);
        $this->assertNull($draft['published_at']);
        
        Post::update($postId, ['status' => 'published']);
        
        $published = Post::getById($postId);
        $this->assertNotNull($published['published_at']);
    }
    
    public function testDeletePost(): void
    {
        $postId = Post::create([
            'title' => 'To Be Deleted',
            'status' => 'draft'
        ]);
        
        $beforeDelete = Post::getById($postId);
        $this->assertNotNull($beforeDelete);
        
        $result = Post::delete($postId);
        
        $this->assertTrue($result);
        
        $afterDelete = Post::getById($postId);
        $this->assertNull($afterDelete);
    }
    
    public function testDeleteNonExistentPost(): void
    {
        $result = Post::delete(999999);
        
        $this->assertFalse($result);
    }
    
    // ==========================================
    // Publish/Unpublish Tests
    // ==========================================
    
    public function testPublishPost(): void
    {
        $postId = Post::create([
            'title' => 'Draft to Publish',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $result = Post::publish($postId);
        
        $this->assertTrue($result);
        
        $post = Post::getById($postId);
        $this->assertEquals('published', $post['status']);
        $this->assertNotNull($post['published_at']);
    }
    
    // ==========================================
    // Slug Existence Tests
    // ==========================================
    
    public function testSlugExistsReturnsTrueForExistingSlug(): void
    {
        $uniqueSlug = 'exists-test-' . time();
        
        $postId = Post::create([
            'title' => 'Slug Exists Test',
            'slug' => $uniqueSlug,
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $exists = Post::slugExists($uniqueSlug);
        
        $this->assertTrue($exists);
    }
    
    public function testSlugExistsReturnsFalseForNonExistent(): void
    {
        $exists = Post::slugExists('nonexistent-slug-' . time() . rand(10000, 99999));
        
        $this->assertFalse($exists);
    }
    
    public function testSlugExistsExcludesId(): void
    {
        $uniqueSlug = 'exclude-test-' . time();
        
        $postId = Post::create([
            'title' => 'Exclude ID Test',
            'slug' => $uniqueSlug,
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        // Should return false when excluding this post's ID
        $exists = Post::slugExists($uniqueSlug, $postId);
        
        $this->assertFalse($exists);
    }
    
    // ==========================================
    // Match Result Posts Tests
    // ==========================================
    
    public function testCreateMatchResultPost(): void
    {
        $postId = Post::create([
            'title' => 'Arsenal 2-1 Chelsea',
            'content' => 'Match report',
            'status' => 'published',
            'is_match_result' => 1,
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'home_score' => 2,
            'away_score' => 1,
            'match_competition' => 'Premier League'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getById($postId);
        
        $this->assertEquals(1, $post['is_match_result']);
        $this->assertEquals('Arsenal', $post['home_team']);
        $this->assertEquals('Chelsea', $post['away_team']);
        $this->assertEquals(2, $post['home_score']);
        $this->assertEquals(1, $post['away_score']);
        $this->assertEquals('Premier League', $post['match_competition']);
    }
    
    // ==========================================
    // Tags Tests
    // ==========================================
    
    public function testCreatePostWithTagsArray(): void
    {
        $postId = Post::create([
            'title' => 'Tags Array Test',
            'tags' => ['fotbal', 'ucl', 'arsenal'],
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getById($postId);
        
        $this->assertEquals('fotbal,ucl,arsenal', $post['tags']);
    }
    
    public function testCreatePostWithTagsString(): void
    {
        $postId = Post::create([
            'title' => 'Tags String Test',
            'tags' => 'fotbal,ucl,arsenal',
            'status' => 'draft'
        ]);
        
        self::$testPostIds[] = $postId;
        
        $post = Post::getById($postId);
        
        $this->assertEquals('fotbal,ucl,arsenal', $post['tags']);
    }
    
    // ==========================================
    // Count Tests
    // ==========================================
    
    public function testCountAllReturnsInteger(): void
    {
        $count = Post::countAll();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    public function testCountAllWithStatus(): void
    {
        $draftCount = Post::countAll('draft');
        $publishedCount = Post::countAll('published');
        $totalCount = Post::countAll();
        
        $this->assertIsInt($draftCount);
        $this->assertIsInt($publishedCount);
        $this->assertLessThanOrEqual($totalCount, $draftCount + $publishedCount);
    }
    
    // ==========================================
    // Search Tests
    // ==========================================
    
    public function testSearchReturnsArray(): void
    {
        $results = Post::search('test');
        
        $this->assertIsArray($results);
    }
    
    public function testSearchWithNoMatchesReturnsEmptyArray(): void
    {
        $results = Post::search('xyznonexistenttermxyz' . time());
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    // ==========================================
    // Latest Posts Tests
    // ==========================================
    
    public function testGetLatestReturnsArray(): void
    {
        $latest = Post::getLatest(5);
        
        $this->assertIsArray($latest);
        $this->assertLessThanOrEqual(5, count($latest));
    }
    
    public function testGetLatestRespectsLimit(): void
    {
        // Create some test posts
        for ($i = 0; $i < 3; $i++) {
            $postId = Post::create([
                'title' => "Latest Test $i " . time(),
                'status' => 'draft'
            ]);
            self::$testPostIds[] = $postId;
        }
        
        $latest = Post::getLatest(2);
        
        $this->assertLessThanOrEqual(2, count($latest));
    }
}
