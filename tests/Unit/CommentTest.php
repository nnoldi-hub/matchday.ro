<?php
/**
 * CommentTest
 * Unit tests for Comment class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    private static $testCommentIds = [];
    private static $testPostSlug = 'test-post-for-comments';
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/Comment.php';
        
        // Create a test post for comments
        require_once dirname(__DIR__, 2) . '/includes/Post.php';
        
        $existingPost = Post::getBySlug(self::$testPostSlug);
        if (!$existingPost) {
            Post::create([
                'title' => 'Test Post for Comments',
                'slug' => self::$testPostSlug,
                'content' => 'Test content',
                'status' => 'published'
            ]);
        }
    }
    
    protected function tearDown(): void
    {
        foreach (self::$testCommentIds as $id) {
            try {
                Comment::delete($id);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        self::$testCommentIds = [];
    }
    
    public static function tearDownAfterClass(): void
    {
        // Clean up test post
        $post = Post::getBySlug(self::$testPostSlug);
        if ($post) {
            Post::delete($post['id']);
        }
    }
    
    // ==========================================
    // Comment Creation Tests
    // ==========================================
    
    public function testCreateCommentReturnsId(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Test Author',
            'author_email' => 'test@example.com',
            'content' => 'This is a test comment',
            'ip' => '127.0.0.1'
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $this->assertIsInt($commentId);
        $this->assertGreaterThan(0, $commentId);
    }
    
    public function testCreateCommentSanitizesInput(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => '<script>alert("XSS")</script>Test',
            'content' => '<b>Bold</b> and <script>evil()</script>',
            'ip' => '127.0.0.1'
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $comment = Comment::getById($commentId);
        
        $this->assertStringNotContainsString('<script>', $comment['author_name']);
        $this->assertStringNotContainsString('<script>', $comment['content']);
    }
    
    public function testCreateReplyWithParentId(): void
    {
        // Create parent comment
        $parentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Parent Author',
            'content' => 'Parent comment',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $parentId;
        
        // Create reply
        $replyId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Reply Author',
            'content' => 'This is a reply',
            'ip' => '127.0.0.2',
            'parent_id' => $parentId,
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $replyId;
        
        $reply = Comment::getById($replyId);
        
        $this->assertEquals($parentId, $reply['parent_id']);
    }
    
    // ==========================================
    // Spam Detection Tests
    // ==========================================
    
    public function testSpamCommentNotAutoApproved(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Spammer',
            'content' => 'Buy viagra cheap now!',
            'ip' => '192.168.1.' . rand(100, 200)
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $comment = Comment::getById($commentId);
        
        $this->assertEquals(0, $comment['approved']);
    }
    
    public function testSpamInAuthorNameDetected(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Casino Winner',
            'content' => 'Great article!',
            'ip' => '192.168.2.' . rand(100, 200)
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $comment = Comment::getById($commentId);
        
        $this->assertEquals(0, $comment['approved']);
    }
    
    public function testCleanCommentAutoApproved(): void
    {
        // Note: Only if not a new IP (trusted commenter logic)
        // For a new IP with clean content, it may still be approved
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Legitimate User',
            'content' => 'Great article about football!',
            'ip' => '10.0.0.' . rand(1, 100)
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $comment = Comment::getById($commentId);
        
        // Clean content should be auto-approved
        $this->assertEquals(1, $comment['approved']);
    }
    
    // ==========================================
    // Retrieval Tests
    // ==========================================
    
    public function testGetByIdReturnsComment(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Test Author',
            'content' => 'Test content for getById',
            'ip' => '127.0.0.1'
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $comment = Comment::getById($commentId);
        
        $this->assertIsArray($comment);
        $this->assertEquals($commentId, $comment['id']);
        $this->assertEquals('Test Author', $comment['author_name']);
    }
    
    public function testGetByIdReturnsNullForNonExistent(): void
    {
        $comment = Comment::getById(999999);
        
        $this->assertNull($comment);
    }
    
    public function testGetByPostReturnsApprovedComments(): void
    {
        // Create approved comment
        $approvedId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Approved Author',
            'content' => 'This is approved',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $approvedId;
        
        // Create unapproved comment (spam)
        $unapprovedId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Spammer',
            'content' => 'Buy viagra now',
            'ip' => '192.168.100.1'
        ]);
        
        self::$testCommentIds[] = $unapprovedId;
        
        $comments = Comment::getByPost(self::$testPostSlug);
        
        $this->assertIsArray($comments);
        
        // Only approved comments should be returned
        foreach ($comments as $comment) {
            $this->assertEquals(1, $comment['approved'] ?? 1); // Approved
        }
    }
    
    // ==========================================
    // Approve/Reject Tests
    // ==========================================
    
    public function testApproveComment(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'To Approve',
            'content' => 'This comment needs approval',
            'ip' => '127.0.0.1',
            'approved' => 0
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $result = Comment::approve($commentId);
        
        $this->assertTrue($result);
        
        $comment = Comment::getById($commentId);
        $this->assertEquals(1, $comment['approved']);
    }
    
    public function testRejectComment(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'To Reject',
            'content' => 'This comment will be rejected',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $result = Comment::reject($commentId);
        
        $this->assertTrue($result);
        
        $comment = Comment::getById($commentId);
        $this->assertEquals(0, $comment['approved']);
    }
    
    // ==========================================
    // Like Tests
    // ==========================================
    
    public function testLikeComment(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Likeable Author',
            'content' => 'Like this comment',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $beforeLike = Comment::getById($commentId);
        $initialLikes = (int) $beforeLike['likes'];
        
        $result = Comment::like($commentId, '10.0.0.1');
        
        $this->assertTrue($result);
        
        $afterLike = Comment::getById($commentId);
        $this->assertEquals($initialLikes + 1, (int) $afterLike['likes']);
    }
    
    public function testCannotLikeTwiceFromSameIP(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Single Like Author',
            'content' => 'No double likes',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $ip = '10.0.0.' . rand(1, 255);
        
        // First like should succeed
        $first = Comment::like($commentId, $ip);
        $this->assertTrue($first);
        
        // Second like should fail
        $second = Comment::like($commentId, $ip);
        $this->assertFalse($second);
        
        // Likes should be 1, not 2
        $comment = Comment::getById($commentId);
        $this->assertEquals(1, (int) $comment['likes']);
    }
    
    // ==========================================
    // Delete Tests
    // ==========================================
    
    public function testDeleteComment(): void
    {
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'To Delete',
            'content' => 'This will be deleted',
            'ip' => '127.0.0.1'
        ]);
        
        $beforeDelete = Comment::getById($commentId);
        $this->assertNotNull($beforeDelete);
        
        $result = Comment::delete($commentId);
        $this->assertTrue($result);
        
        $afterDelete = Comment::getById($commentId);
        $this->assertNull($afterDelete);
    }
    
    public function testDeleteCommentAlsoDeletesReplies(): void
    {
        // Create parent
        $parentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Parent',
            'content' => 'Parent comment',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        // Create reply
        $replyId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Reply',
            'content' => 'Reply comment',
            'ip' => '127.0.0.2',
            'parent_id' => $parentId,
            'approved' => 1
        ]);
        
        // Delete parent
        Comment::delete($parentId);
        
        // Reply should also be deleted
        $reply = Comment::getById($replyId);
        $this->assertNull($reply);
    }
    
    // ==========================================
    // Count Tests
    // ==========================================
    
    public function testCountByPost(): void
    {
        $initialCount = Comment::countByPost(self::$testPostSlug);
        
        // Create approved comment
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Count Test',
            'content' => 'For counting',
            'ip' => '127.0.0.1',
            'approved' => 1
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $newCount = Comment::countByPost(self::$testPostSlug);
        
        $this->assertEquals($initialCount + 1, $newCount);
    }
    
    public function testCountByPostOnlyCountsApproved(): void
    {
        $initialCount = Comment::countByPost(self::$testPostSlug, true);
        
        // Create unapproved comment
        $commentId = Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Unapproved',
            'content' => 'Not approved',
            'ip' => '127.0.0.1',
            'approved' => 0
        ]);
        
        self::$testCommentIds[] = $commentId;
        
        $newCount = Comment::countByPost(self::$testPostSlug, true);
        
        // Count should not increase for unapproved
        $this->assertEquals($initialCount, $newCount);
    }
    
    public function testCountPending(): void
    {
        $count = Comment::countPending();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    public function testCountAll(): void
    {
        $count = Comment::countAll();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    // ==========================================
    // Bulk Operations Tests
    // ==========================================
    
    public function testBulkApprove(): void
    {
        $ids = [];
        
        for ($i = 0; $i < 3; $i++) {
            $id = Comment::create([
                'post_slug' => self::$testPostSlug,
                'author_name' => "Bulk Approve $i",
                'content' => 'To be bulk approved',
                'ip' => '127.0.0.1',
                'approved' => 0
            ]);
            $ids[] = $id;
            self::$testCommentIds[] = $id;
        }
        
        $result = Comment::bulkApprove($ids);
        
        $this->assertEquals(3, $result);
        
        foreach ($ids as $id) {
            $comment = Comment::getById($id);
            $this->assertEquals(1, $comment['approved']);
        }
    }
    
    public function testBulkDelete(): void
    {
        $ids = [];
        
        for ($i = 0; $i < 3; $i++) {
            $id = Comment::create([
                'post_slug' => self::$testPostSlug,
                'author_name' => "Bulk Delete $i",
                'content' => 'To be bulk deleted',
                'ip' => '127.0.0.1'
            ]);
            $ids[] = $id;
        }
        
        $result = Comment::bulkDelete($ids);
        
        $this->assertEquals(3, $result);
        
        foreach ($ids as $id) {
            $comment = Comment::getById($id);
            $this->assertNull($comment);
        }
    }
    
    public function testBulkApproveEmptyArray(): void
    {
        $result = Comment::bulkApprove([]);
        
        $this->assertEquals(0, $result);
    }
    
    public function testBulkDeleteEmptyArray(): void
    {
        $result = Comment::bulkDelete([]);
        
        $this->assertEquals(0, $result);
    }
    
    // ==========================================
    // Trusted Commenter Tests
    // ==========================================
    
    public function testIsTrustedCommenterWithNoHistory(): void
    {
        $ipHash = hash('sha256', '192.168.99.' . rand(1, 255) . 'matchday_salt_2026');
        
        $isTrusted = Comment::isTrustedCommenter($ipHash);
        
        $this->assertFalse($isTrusted);
    }
    
    // ==========================================
    // Get Recent Tests
    // ==========================================
    
    public function testGetRecentReturnsArray(): void
    {
        $recent = Comment::getRecent(5);
        
        $this->assertIsArray($recent);
        $this->assertLessThanOrEqual(5, count($recent));
    }
    
    // ==========================================
    // Get All for Admin Tests
    // ==========================================
    
    public function testGetAllReturnsArray(): void
    {
        $comments = Comment::getAll();
        
        $this->assertIsArray($comments);
    }
    
    public function testGetAllFiltersByApproved(): void
    {
        $pending = Comment::getAll(1, 50, 0);
        
        foreach ($pending as $comment) {
            $this->assertEquals(0, $comment['approved']);
        }
        
        $approved = Comment::getAll(1, 50, 1);
        
        foreach ($approved as $comment) {
            $this->assertEquals(1, $comment['approved']);
        }
    }
}
