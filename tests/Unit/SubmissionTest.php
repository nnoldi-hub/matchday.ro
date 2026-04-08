<?php
/**
 * SubmissionTest - Unit tests for Submission class
 * MatchDay.ro - External article contributions
 * 
 * Tests: create, workflow (pending→approved→published), validation, rate limiting
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

require_once(__DIR__ . '/../bootstrap.php');
require_once(__DIR__ . '/../../includes/Submission.php');

class SubmissionTest extends TestCase
{
    private static $testSubmissionId;
    private static $testToken;
    
    public static function setUpBeforeClass(): void
    {
        // Create submissions table if not exists
        \Database::execute("
            CREATE TABLE IF NOT EXISTS submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                excerpt TEXT,
                content TEXT NOT NULL,
                category_id INTEGER,
                author_name TEXT NOT NULL,
                author_email TEXT NOT NULL,
                author_bio TEXT,
                featured_image TEXT,
                status TEXT DEFAULT 'pending',
                token TEXT UNIQUE,
                reviewer_id INTEGER,
                reviewer_feedback TEXT,
                reviewed_at TEXT,
                ip_address TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Clean up any existing test data
        \Database::execute("DELETE FROM submissions WHERE author_email LIKE '%test%'");
    }
    
    protected function setUp(): void
    {
        // Clean up before each test
    }
    
    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        \Database::execute("DELETE FROM submissions WHERE author_email LIKE '%test%'");
    }
    
    // ======================= CREATE TESTS =======================
    
    #[Test]
    public function testCreateSubmissionWithValidData(): void
    {
        $data = [
            'title' => 'Test Article Title',
            'content' => 'This is the full content of the test article.',
            'author_name' => 'Test Author',
            'author_email' => 'test@example.com',
            'excerpt' => 'Short excerpt',
            'author_bio' => 'Test author biography'
        ];
        
        $submissionId = \Submission::create($data);
        
        $this->assertIsInt($submissionId);
        $this->assertGreaterThan(0, $submissionId);
        
        self::$testSubmissionId = $submissionId;
        
        // Verify it was created
        $submission = \Submission::getById($submissionId);
        $this->assertNotNull($submission);
        $this->assertEquals('Test Article Title', $submission['title']);
        $this->assertEquals('pending', $submission['status']);
        
        self::$testToken = $submission['token'];
    }
    
    #[Test]
    #[Depends('testCreateSubmissionWithValidData')]
    public function testCreatedSubmissionHasToken(): void
    {
        $this->assertNotNull(self::$testToken);
        $this->assertEquals(32, strlen(self::$testToken)); // bin2hex(random_bytes(16)) = 32 chars
    }
    
    #[Test]
    public function testCreateSubmissionWithoutTitleFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'title' is required");
        
        \Submission::create([
            'content' => 'Content without title',
            'author_name' => 'Test Author',
            'author_email' => 'test2@example.com'
        ]);
    }
    
    #[Test]
    public function testCreateSubmissionWithoutContentFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'content' is required");
        
        \Submission::create([
            'title' => 'Title without content',
            'author_name' => 'Test Author',
            'author_email' => 'test3@example.com'
        ]);
    }
    
    #[Test]
    public function testCreateSubmissionWithoutAuthorNameFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'author_name' is required");
        
        \Submission::create([
            'title' => 'Test Title',
            'content' => 'Test content',
            'author_email' => 'test4@example.com'
        ]);
    }
    
    #[Test]
    public function testCreateSubmissionWithoutEmailFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'author_email' is required");
        
        \Submission::create([
            'title' => 'Test Title',
            'content' => 'Test content',
            'author_name' => 'Test Author'
        ]);
    }
    
    #[Test]
    public function testCreateSubmissionWithInvalidEmailFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid email address");
        
        \Submission::create([
            'title' => 'Test Title',
            'content' => 'Test content',
            'author_name' => 'Test Author',
            'author_email' => 'not-an-email'
        ]);
    }
    
    #[Test]
    public function testCreateMinimalSubmission(): void
    {
        $data = [
            'title' => 'Minimal Submission',
            'content' => 'Minimal content',
            'author_name' => 'Minimal Author',
            'author_email' => 'minimal-test@example.com'
        ];
        
        $submissionId = \Submission::create($data);
        
        $this->assertGreaterThan(0, $submissionId);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('', $submission['excerpt']);
        $this->assertEquals('', $submission['author_bio']);
        $this->assertNull($submission['featured_image']);
    }
    
    // ======================= READ TESTS =======================
    
    #[Test]
    public function testGetById(): void
    {
        // Create a fresh submission for this test
        $submissionId = \Submission::create([
            'title' => 'GetById Test Title',
            'content' => 'Content for getById test',
            'author_name' => 'GetById Author',
            'author_email' => 'getbyid-test@example.com'
        ]);
        
        $submission = \Submission::getById($submissionId);
        
        $this->assertNotNull($submission);
        $this->assertEquals($submissionId, $submission['id']);
        $this->assertEquals('GetById Test Title', $submission['title']);
    }
    
    #[Test]
    public function testGetByIdReturnsNullForNonExistent(): void
    {
        $submission = \Submission::getById(999999);
        $this->assertNull($submission);
    }
    
    #[Test]
    public function testGetByToken(): void
    {
        // Create a fresh submission for this test
        $submissionId = \Submission::create([
            'title' => 'GetByToken Test Title',
            'content' => 'Content for getByToken test',
            'author_name' => 'Token Author',
            'author_email' => 'getbytoken-test@example.com'
        ]);
        
        $submission = \Submission::getById($submissionId);
        $token = $submission['token'];
        
        $foundByToken = \Submission::getByToken($token);
        
        $this->assertNotNull($foundByToken);
        $this->assertEquals($submissionId, $foundByToken['id']);
    }
    
    #[Test]
    public function testGetByTokenReturnsNullForInvalidToken(): void
    {
        $submission = \Submission::getByToken('invalid-token-12345');
        $this->assertNull($submission);
    }
    
    #[Test]
    public function testGetAll(): void
    {
        $submissions = \Submission::getAll();
        
        $this->assertIsArray($submissions);
        $this->assertGreaterThan(0, count($submissions));
    }
    
    #[Test]
    public function testGetAllWithStatusFilter(): void
    {
        $pending = \Submission::getAll(['status' => 'pending']);
        
        $this->assertIsArray($pending);
        foreach ($pending as $submission) {
            $this->assertEquals('pending', $submission['status']);
        }
    }
    
    #[Test]
    public function testGetAllWithSearchFilter(): void
    {
        // Create a submission with unique searchable text
        $uniqueTitle = 'SearchableUniqueTitle' . time();
        \Submission::create([
            'title' => $uniqueTitle,
            'content' => 'Content for search test',
            'author_name' => 'Search Author',
            'author_email' => 'search-filter-test@example.com'
        ]);
        
        $results = \Submission::getAll(['search' => $uniqueTitle]);
        
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }
    
    #[Test]
    public function testGetAllWithPagination(): void
    {
        $page1 = \Submission::getAll([], 1, 0);
        $page2 = \Submission::getAll([], 1, 1);
        
        $this->assertCount(1, $page1);
        
        // If there are at least 2 submissions, page 2 should have 1
        if (count($page2) > 0) {
            $this->assertNotEquals($page1[0]['id'], $page2[0]['id']);
        }
    }
    
    #[Test]
    public function testCount(): void
    {
        $count = \Submission::count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }
    
    #[Test]
    public function testCountWithFilter(): void
    {
        $pendingCount = \Submission::count(['status' => 'pending']);
        $totalCount = \Submission::count();
        
        $this->assertLessThanOrEqual($totalCount, $pendingCount);
    }
    
    // ======================= UPDATE TESTS =======================
    
    #[Test]
    public function testUpdateSubmission(): void
    {
        // Create a fresh submission for this test
        $submissionId = \Submission::create([
            'title' => 'Original Title',
            'content' => 'Original content',
            'author_name' => 'Update Author',
            'author_email' => 'update-test@example.com',
            'excerpt' => 'Original excerpt'
        ]);
        
        $updated = \Submission::update($submissionId, [
            'title' => 'Updated Article Title',
            'excerpt' => 'Updated excerpt'
        ]);
        
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('Updated Article Title', $submission['title']);
        $this->assertEquals('Updated excerpt', $submission['excerpt']);
    }
    
    #[Test]
    public function testUpdateWithEmptyDataReturnsFalse(): void
    {
        // Create a submission to test update on
        $submissionId = \Submission::create([
            'title' => 'Empty Update Test',
            'content' => 'Content',
            'author_name' => 'Author',
            'author_email' => 'empty-update-test@example.com'
        ]);
        
        $updated = \Submission::update($submissionId, []);
        $this->assertFalse($updated);
    }
    
    #[Test]
    public function testUpdateNonExistentSubmission(): void
    {
        $updated = \Submission::update(999999, ['title' => 'New Title']);
        $this->assertFalse($updated);
    }
    
    // ======================= STATUS WORKFLOW TESTS =======================
    
    #[Test]
    public function testStatusWorkflow(): void
    {
        // Create a new submission for workflow testing
        $submissionId = \Submission::create([
            'title' => 'Workflow Test Article',
            'content' => 'Content for workflow testing',
            'author_name' => 'Workflow Author',
            'author_email' => 'workflow-test@example.com'
        ]);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('pending', $submission['status']);
        
        // Step 1: Move to reviewing
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_REVIEWING, 1);
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('reviewing', $submission['status']);
        $this->assertEquals(1, $submission['reviewer_id']);
        $this->assertNotNull($submission['reviewed_at']);
        
        // Step 2: Approve
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_APPROVED, 1, 'Good article!');
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('approved', $submission['status']);
        $this->assertEquals('Good article!', $submission['reviewer_feedback']);
    }
    
    #[Test]
    public function testRejectWorkflow(): void
    {
        $submissionId = \Submission::create([
            'title' => 'Reject Test Article',
            'content' => 'Content for rejection testing',
            'author_name' => 'Reject Author',
            'author_email' => 'reject-test@example.com'
        ]);
        
        // Reject the submission
        $updated = \Submission::updateStatus($submissionId, \Submission::STATUS_REJECTED, 1, 'Does not meet guidelines');
        $this->assertTrue($updated);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('rejected', $submission['status']);
        $this->assertEquals('Does not meet guidelines', $submission['reviewer_feedback']);
    }
    
    #[Test]
    public function testStatusConstants(): void
    {
        $this->assertEquals('pending', \Submission::STATUS_PENDING);
        $this->assertEquals('reviewing', \Submission::STATUS_REVIEWING);
        $this->assertEquals('approved', \Submission::STATUS_APPROVED);
        $this->assertEquals('rejected', \Submission::STATUS_REJECTED);
        $this->assertEquals('published', \Submission::STATUS_PUBLISHED);
    }
    
    #[Test]
    public function testUpdateStatusForNonExistentSubmission(): void
    {
        $updated = \Submission::updateStatus(999999, \Submission::STATUS_APPROVED);
        $this->assertFalse($updated);
    }
    
    // ======================= DELETE TESTS =======================
    
    #[Test]
    public function testDeleteSubmission(): void
    {
        // Create a submission to delete
        $submissionId = \Submission::create([
            'title' => 'Delete Test Article',
            'content' => 'Content to be deleted',
            'author_name' => 'Delete Author',
            'author_email' => 'delete-test@example.com'
        ]);
        
        $deleted = \Submission::delete($submissionId);
        $this->assertTrue($deleted);
        
        $submission = \Submission::getById($submissionId);
        $this->assertNull($submission);
    }
    
    #[Test]
    public function testDeleteNonExistentSubmission(): void
    {
        $deleted = \Submission::delete(999999);
        $this->assertFalse($deleted);
    }
    
    // ======================= RATE LIMITING TESTS =======================
    
    #[Test]
    public function testCheckRateLimitAllowsFirstSubmission(): void
    {
        $allowed = \Submission::checkRateLimit('new-user@example.com', '192.168.1.100');
        $this->assertTrue($allowed);
    }
    
    #[Test]
    public function testCheckRateLimitBlocksAfterEmailLimit(): void
    {
        $email = 'rate-limit-test-email@example.com';
        $ip = '10.0.0.1';
        
        // Create 3 submissions from same email
        for ($i = 0; $i < 3; $i++) {
            \Submission::create([
                'title' => "Rate Limit Test $i",
                'content' => "Content $i",
                'author_name' => 'Rate Limiter',
                'author_email' => $email
            ]);
        }
        
        // 4th should be blocked
        $allowed = \Submission::checkRateLimit($email, $ip);
        $this->assertFalse($allowed);
    }
    
    // ======================= CONTRIBUTOR STATS TESTS =======================
    
    #[Test]
    public function testGetContributorStats(): void
    {
        $email = 'stats-test@example.com';
        
        // Create some test submissions
        \Submission::create([
            'title' => 'Stats Test 1',
            'content' => 'Content 1',
            'author_name' => 'Stats Author',
            'author_email' => $email
        ]);
        
        \Submission::create([
            'title' => 'Stats Test 2',
            'content' => 'Content 2',
            'author_name' => 'Stats Author',
            'author_email' => $email
        ]);
        
        $stats = \Submission::getContributorStats($email);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('published', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(0, $stats['published']);
    }
    
    #[Test]
    public function testGetContributorStatsForNonExistentEmail(): void
    {
        $stats = \Submission::getContributorStats('nonexistent@example.com');
        
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['published']);
        $this->assertEquals(0, $stats['pending']);
    }
    
    // ======================= EDGE CASES =======================
    
    #[Test]
    public function testCreateSubmissionWithSpecialCharacters(): void
    {
        $data = [
            'title' => 'Special Chars: <>&"\'',
            'content' => 'Content with émojis 🎉 and special chars: <script>alert("xss")</script>',
            'author_name' => 'Test <Author>',
            'author_email' => 'special-test@example.com'
        ];
        
        $submissionId = \Submission::create($data);
        $this->assertGreaterThan(0, $submissionId);
        
        $submission = \Submission::getById($submissionId);
        $this->assertEquals('Special Chars: <>&"\'', $submission['title']);
    }
    
    #[Test]
    public function testCreateSubmissionWithLongContent(): void
    {
        $longContent = str_repeat('Lorem ipsum dolor sit amet.', 1000); // ~27KB (no trailing space)
        
        $data = [
            'title' => 'Long Content Test',
            'content' => $longContent,
            'author_name' => 'Long Author',
            'author_email' => 'long-content-test@example.com'
        ];
        
        $submissionId = \Submission::create($data);
        $this->assertGreaterThan(0, $submissionId);
        
        $submission = \Submission::getById($submissionId);
        // Content should be preserved (may be trimmed)
        $this->assertGreaterThan(25000, strlen($submission['content']));
    }
    
    #[Test]
    public function testCreateSubmissionWithUnicode(): void
    {
        $data = [
            'title' => 'Test cu caractere românești: ăîșțâ',
            'content' => 'Conținut cu diacritice: Ștefan cel Mare și Sfânt',
            'author_name' => 'Autor Român',
            'author_email' => 'romanian-test@example.com'
        ];
        
        $submissionId = \Submission::create($data);
        $submission = \Submission::getById($submissionId);
        
        $this->assertStringContainsString('ăîșțâ', $submission['title']);
        $this->assertStringContainsString('Ștefan', $submission['content']);
    }
    
    #[Test]
    public function testGetAllEmptyResultForNoMatchingStatus(): void
    {
        $results = \Submission::getAll(['status' => 'nonexistent_status']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
