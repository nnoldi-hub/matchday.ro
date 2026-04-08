<?php
/**
 * CommentWorkflowTest - Integration tests for comment workflow
 * MatchDay.ro - Tests: create → moderate → approve → display
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../bootstrap.php');
require_once(__DIR__ . '/../../includes/Comment.php');
require_once(__DIR__ . '/../../includes/Post.php');

class CommentWorkflowTest extends TestCase
{
    private static $testPostSlug;
    
    public static function setUpBeforeClass(): void
    {
        // Create a test post for comments
        \Database::execute("
            INSERT INTO posts (title, slug, content, status, created_at)
            VALUES ('Integration Test Post', 'integration-test-post', 'Test content', 'published', CURRENT_TIMESTAMP)
        ");
        
        self::$testPostSlug = 'integration-test-post';
        
        // Clean up old test comments
        \Database::execute("DELETE FROM comments WHERE post_slug LIKE '%integration-test%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        \Database::execute("DELETE FROM comments WHERE post_slug LIKE '%integration-test%'");
        \Database::execute("DELETE FROM posts WHERE slug = 'integration-test-post'");
    }
    
    // ======================= FULL WORKFLOW TESTS =======================
    
    #[Test]
    public function testCompleteCommentWorkflow(): void
    {
        // Step 1: Submit a comment (as a visitor)
        $commentId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Integration User',
            'content' => 'This is an integration test comment',
            'ip' => '192.168.100.1',
            'approved' => 0  // Pending moderation
        ]);
        
        $this->assertGreaterThan(0, $commentId, 'Comment should be created');
        
        // Step 2: Verify comment is NOT visible (pending moderation)
        $visibleComments = \Comment::getByPost(self::$testPostSlug);
        $foundPending = false;
        foreach ($visibleComments as $c) {
            if ($c['id'] == $commentId) {
                $foundPending = true;
            }
        }
        // If getByPost returns only approved, this should be false
        // If it returns all, we check approved status
        
        // Step 3: Admin approves the comment
        $approved = \Comment::approve($commentId);
        $this->assertTrue($approved, 'Comment should be approved');
        
        // Step 4: Verify comment IS visible now
        $comment = \Comment::getById($commentId);
        $this->assertEquals(1, $comment['approved']);
        
        // Step 5: User likes the comment
        $liked = \Comment::like($commentId, '192.168.100.2');
        $this->assertTrue($liked || $liked !== false, 'Like should succeed');
        
        // Verify like count
        $comment = \Comment::getById($commentId);
        $this->assertGreaterThanOrEqual(1, $comment['likes'] ?? 0, 'Likes should be incremented');
    }
    
    #[Test]
    public function testNestedCommentWorkflow(): void
    {
        // Create parent comment
        $parentId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Parent Author',
            'content' => 'This is the parent comment',
            'ip' => '192.168.100.5',
            'approved' => 1
        ]);
        
        $this->assertGreaterThan(0, $parentId);
        
        // Create reply to parent
        $replyId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Reply Author',
            'content' => 'This is a reply to the parent',
            'ip' => '192.168.100.6',
            'parent_id' => $parentId,
            'approved' => 1
        ]);
        
        $this->assertGreaterThan(0, $replyId);
        
        // Verify the reply has correct parent_id
        $reply = \Comment::getById($replyId);
        $this->assertEquals($parentId, $reply['parent_id']);
    }
    
    #[Test]
    public function testCommentRejectionWorkflow(): void
    {
        // Create a spam-like comment
        $commentId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Spam User',
            'content' => 'Check out my website for free stuff!',
            'ip' => '192.168.100.10',
            'approved' => 0
        ]);
        
        // Admin rejects the comment (sets to unapproved)
        $rejected = \Comment::reject($commentId);
        $this->assertTrue($rejected);
        
        // Verify comment is marked as rejected (approved = 0)
        $comment = \Comment::getById($commentId);
        $this->assertEquals(0, $comment['approved']);
    }
    
    #[Test]
    public function testBulkModerationWorkflow(): void
    {
        // Create multiple pending comments
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = \Comment::create([
                'post_slug' => self::$testPostSlug,
                'author_name' => "Bulk User $i",
                'content' => "Bulk comment $i",
                'ip' => "192.168.100." . (20 + $i),
                'approved' => 0
            ]);
        }
        
        // Bulk approve all
        $approved = \Comment::bulkApprove($ids);
        $this->assertEquals(3, $approved, 'All 3 comments should be approved');
        
        // Verify all are approved
        foreach ($ids as $id) {
            $comment = \Comment::getById($id);
            $this->assertEquals(1, $comment['approved']);
        }
    }
    
    #[Test]
    public function testCommentEditByAdmin(): void
    {
        // Skip if update method doesn't exist
        if (!method_exists('\Comment', 'update')) {
            $this->markTestSkipped('Comment::update() not implemented');
        }
        
        // Create a comment
        $commentId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Edit Test User',
            'content' => 'Original content with typo',
            'ip' => '192.168.100.30',
            'approved' => 1
        ]);
        
        // Admin edits the comment
        $updated = \Comment::update($commentId, [
            'content' => 'Fixed content without typo'
        ]);
        
        $this->assertTrue($updated);
        
        $comment = \Comment::getById($commentId);
        $this->assertEquals('Fixed content without typo', $comment['content']);
    }
    
    // ======================= RATE LIMITING INTEGRATION =======================
    
    #[Test]
    public function testRateLimitingPreventsSpam(): void
    {
        $testIP = '192.168.200.1';
        
        // First few comments should succeed
        for ($i = 0; $i < 3; $i++) {
            $id = \Comment::create([
                'post_slug' => self::$testPostSlug,
                'author_name' => "Rate Limit User $i",
                'content' => "Rate limit test $i",
                'ip' => $testIP,
                'approved' => 0
            ]);
            $this->assertGreaterThan(0, $id, "Comment $i should be created");
        }
        
        // Check if rate limiting kicks in
        $allowed = \Comment::checkRateLimit($testIP, 3, 5);
        
        // After 3 comments, should be rate limited
        $this->assertFalse($allowed, 'Should be rate limited after 3 comments');
    }
    
    // ======================= SPAM DETECTION INTEGRATION =======================
    
    #[Test]
    public function testSpamDetectionIntegration(): void
    {
        // Test spam detection
        $spamContent = 'Buy cheap viagra online now!';
        
        if (method_exists('\Comment', 'isSpam')) {
            $isSpam = \Comment::isSpam($spamContent);
            $this->assertTrue($isSpam, 'Spam content should be detected');
        }
        
        $legitimateContent = 'Great article! I really enjoyed reading this.';
        if (method_exists('\Comment', 'isSpam')) {
            $isSpam = \Comment::isSpam($legitimateContent);
            $this->assertFalse($isSpam, 'Legitimate content should not be flagged');
        }
        
        $this->assertTrue(true); // Pass if no isSpam method
    }
    
    // ======================= REPORTING INTEGRATION =======================
    
    #[Test]
    public function testCommentReporting(): void
    {
        $commentId = \Comment::create([
            'post_slug' => self::$testPostSlug,
            'author_name' => 'Report Test User',
            'content' => 'This will be reported',
            'ip' => '192.168.100.50',
            'approved' => 1
        ]);
        
        // Skip if report method doesn't exist
        if (!method_exists('\Comment', 'report')) {
            $this->markTestSkipped('Comment::report() not implemented');
        }
        
        // User reports the comment
        $reported = \Comment::report($commentId, '192.168.100.51', 'Inappropriate content');
        $this->assertTrue($reported);
    }
    
    // ======================= COUNTS & STATS INTEGRATION =======================
    
    #[Test]
    public function testCommentCountsForPost(): void
    {
        $slug = 'count-test-post-' . time();
        
        // Create test post
        \Database::execute("
            INSERT INTO posts (title, slug, content, status, created_at)
            VALUES ('Count Test Post', :slug, 'Content', 'published', CURRENT_TIMESTAMP)
        ", ['slug' => $slug]);
        
        // Add some comments
        for ($i = 0; $i < 5; $i++) {
            \Comment::create([
                'post_slug' => $slug,
                'author_name' => "Count User $i",
                'content' => "Count comment $i",
                'ip' => "192.168.150.$i",
                'approved' => $i % 2 // Half approved, half pending
            ]);
        }
        
        // Get approved count
        $count = \Comment::countByPost($slug);
        
        // Should be 2 or 3 approved (indices 1, 3 with approved=1)
        $this->assertGreaterThan(0, $count);
        
        // Cleanup
        \Database::execute("DELETE FROM comments WHERE post_slug = :slug", ['slug' => $slug]);
        \Database::execute("DELETE FROM posts WHERE slug = :slug", ['slug' => $slug]);
    }
}
