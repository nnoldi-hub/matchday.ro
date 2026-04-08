<?php
/**
 * SubmissionWorkflowTest - Integration tests for article submission workflow
 * MatchDay.ro - Tests: submit → review → approve/reject → publish
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../bootstrap.php');
require_once(__DIR__ . '/../../includes/Submission.php');

class SubmissionWorkflowTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Clean up test submissions
        \Database::execute("DELETE FROM submissions WHERE author_email LIKE '%integration-test%'");
    }
    
    public static function tearDownAfterClass(): void
    {
        \Database::execute("DELETE FROM submissions WHERE author_email LIKE '%integration-test%'");
    }
    
    // ======================= FULL SUBMISSION TO PUBLISH WORKFLOW =======================
    
    #[Test]
    public function testCompleteSubmissionToPublishWorkflow(): void
    {
        // Step 1: External contributor submits an article
        $submissionId = \Submission::create([
            'title' => 'Arsenal și triumful în Champions League',
            'content' => 'Articol complet despre victoria spectaculoasă a lui Arsenal în finala Champions League 2026.',
            'excerpt' => 'Arsenal câștigă primul titlu UCL din istorie',
            'author_name' => 'Ion Popescu',
            'author_email' => 'ion.integration-test@example.com',
            'author_bio' => 'Jurnalist sportiv cu 10 ani experiență'
        ]);
        
        $this->assertGreaterThan(0, $submissionId);
        
        // Step 2: Verify submission is pending
        $submission = \Submission::getById($submissionId);
        $this->assertEquals(\Submission::STATUS_PENDING, $submission['status']);
        $this->assertNotNull($submission['token']); // For tracking
        
        // Step 3: Contributor can track status via token
        $tracked = \Submission::getByToken($submission['token']);
        $this->assertEquals($submissionId, $tracked['id']);
        
        // Step 4: Editor picks up for review
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_REVIEWING, 1);
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals(\Submission::STATUS_REVIEWING, $submission['status']);
        $this->assertNotNull($submission['reviewed_at']);
        
        // Step 5: Editor approves with feedback
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_APPROVED, 1, 'Articol excelent! Se publică.');
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals(\Submission::STATUS_APPROVED, $submission['status']);
        $this->assertEquals('Articol excelent! Se publică.', $submission['reviewer_feedback']);
        
        // Step 6: Publish (convert to post) - this would normally create a Post
        // Testing only the status change since Post::create integration is complex
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_PUBLISHED);
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals(\Submission::STATUS_PUBLISHED, $submission['status']);
    }
    
    #[Test]
    public function testSubmissionRejectionWorkflow(): void
    {
        // Submit low-quality article
        $submissionId = \Submission::create([
            'title' => 'Meci fotbal',
            'content' => 'Scurt și fără substanță.',
            'author_name' => 'Test User',
            'author_email' => 'reject.integration-test@example.com'
        ]);
        
        // Editor reviews and rejects
        $updated = \Submission::updateStatus(
            $submissionId, 
            \Submission::STATUS_REJECTED, 
            1, 
            'Articolul nu respectă standardele de calitate. Te rugăm să adaugi mai mult conținut și detalii.'
        );
        
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals(\Submission::STATUS_REJECTED, $submission['status']);
        $this->assertStringContainsString('standardele de calitate', $submission['reviewer_feedback']);
    }
    
    // ======================= CONTRIBUTOR TRACKING =======================
    
    #[Test]
    public function testContributorCanTrackMultipleSubmissions(): void
    {
        $email = 'multi.integration-test@example.com';
        
        // Create multiple submissions
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = \Submission::create([
                'title' => "Articol test $i",
                'content' => "Conținut test $i",
                'author_name' => 'Multi User',
                'author_email' => $email
            ]);
        }
        
        // Get contributor stats
        $stats = \Submission::getContributorStats($email);
        
        $this->assertGreaterThanOrEqual(3, $stats['total']);
        $this->assertGreaterThanOrEqual(3, $stats['pending']);
    }
    
    #[Test]
    public function testSubmissionEditBeforeReview(): void
    {
        $submissionId = \Submission::create([
            'title' => 'Articol cu greșeli',
            'content' => 'Conținut inițial cu greșeli',
            'author_name' => 'Edit User',
            'author_email' => 'edit.integration-test@example.com'
        ]);
        
        // Author realizes mistake and wants to edit (if allowed while pending)
        $updated = \Submission::update($submissionId, [
            'title' => 'Articol corectat',
            'content' => 'Conținut corectat și îmbunătățit cu mai multe detalii.'
        ]);
        
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('Articol corectat', $submission['title']);
    }
    
    // ======================= RATE LIMITING INTEGRATION =======================
    
    #[Test]
    public function testRateLimitingForSubmissions(): void
    {
        $email = 'ratelimit.integration-test@example.com';
        $ip = '192.168.50.1';
        
        // Submit 3 articles (limit is 3 per day per email)
        for ($i = 0; $i < 3; $i++) {
            \Submission::create([
                'title' => "Rate Limit Test $i",
                'content' => "Content $i",
                'author_name' => 'Rate User',
                'author_email' => $email
            ]);
        }
        
        // 4th should be blocked
        $allowed = \Submission::checkRateLimit($email, $ip);
        $this->assertFalse($allowed, 'Should be rate limited after 3 submissions');
    }
    
    // ======================= LISTING & FILTERING =======================
    
    #[Test]
    public function testAdminCanFilterSubmissionsByStatus(): void
    {
        // Get pending submissions
        $pending = \Submission::getAll(['status' => 'pending'], 100);
        
        foreach ($pending as $sub) {
            $this->assertEquals('pending', $sub['status']);
        }
        
        // Get count
        $pendingCount = \Submission::count(['status' => 'pending']);
        $this->assertGreaterThanOrEqual(0, $pendingCount);
    }
    
    #[Test]
    public function testAdminCanSearchSubmissions(): void
    {
        // Create submission with unique searchable content
        $uniqueKeyword = 'UniqueSearchable' . time();
        
        \Submission::create([
            'title' => $uniqueKeyword,
            'content' => 'Searchable content',
            'author_name' => 'Search Author',
            'author_email' => 'search.integration-test@example.com'
        ]);
        
        // Search by title
        $results = \Submission::getAll(['search' => $uniqueKeyword]);
        
        $this->assertGreaterThan(0, count($results));
        $this->assertStringContainsString($uniqueKeyword, $results[0]['title']);
    }
    
    // ======================= DELETION =======================
    
    #[Test]
    public function testAdminCanDeleteSubmission(): void
    {
        $submissionId = \Submission::create([
            'title' => 'To Be Deleted',
            'content' => 'This will be deleted',
            'author_name' => 'Delete User',
            'author_email' => 'delete.integration-test@example.com'
        ]);
        
        $deleted = \Submission::delete($submissionId);
        $this->assertTrue($deleted);
        
        $submission = \Submission::getById($submissionId);
        $this->assertNull($submission);
    }
    
    // ======================= VALIDATION WORKFLOW =======================
    
    #[Test]
    public function testSubmissionValidation(): void
    {
        // Missing required fields
        $this->expectException(\InvalidArgumentException::class);
        
        \Submission::create([
            'title' => '', // Empty title
            'content' => 'Some content',
            'author_name' => 'Name',
            'author_email' => 'valid@email.com'
        ]);
    }
    
    #[Test]
    public function testInvalidEmailRejection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');
        
        \Submission::create([
            'title' => 'Valid Title',
            'content' => 'Valid content',
            'author_name' => 'Name',
            'author_email' => 'not-an-email'
        ]);
    }
}
