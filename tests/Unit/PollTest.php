<?php
/**
 * PollTest
 * Unit tests for Poll class
 * 
 * @package MatchDay
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class PollTest extends TestCase
{
    private static $testPollIds = [];
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/Poll.php';
    }
    
    protected function tearDown(): void
    {
        foreach (self::$testPollIds as $id) {
            try {
                Poll::delete($id);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        self::$testPollIds = [];
    }
    
    // ==========================================
    // Poll Creation Tests
    // ==========================================
    
    public function testCreatePollReturnsId(): void
    {
        $pollId = Poll::create([
            'question' => 'Test Poll ' . time(),
            'options' => ['Option A', 'Option B', 'Option C']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $this->assertIsInt($pollId);
        $this->assertGreaterThan(0, $pollId);
    }
    
    public function testCreatePollWithDescription(): void
    {
        $pollId = Poll::create([
            'question' => 'Poll with Description ' . time(),
            'description' => 'This is a test description',
            'options' => ['Yes', 'No']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertEquals('This is a test description', $poll['description']);
    }
    
    public function testCreatePollGeneratesSlug(): void
    {
        $question = 'Cine va câștiga UCL ' . time();
        
        $pollId = Poll::create([
            'question' => $question,
            'options' => ['Real Madrid', 'Bayern', 'PSG']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertNotEmpty($poll['slug']);
        $this->assertStringNotContainsString(' ', $poll['slug']);
    }
    
    public function testCreatePollWithCustomSlug(): void
    {
        $customSlug = 'custom-poll-' . time();
        
        $pollId = Poll::create([
            'question' => 'Custom Slug Poll',
            'slug' => $customSlug,
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertEquals($customSlug, $poll['slug']);
    }
    
    public function testCreatePollWithOptions(): void
    {
        $options = ['Barcelona', 'Real Madrid', 'Atletico', 'Sevilla'];
        
        $pollId = Poll::create([
            'question' => 'Best La Liga Team ' . time(),
            'options' => $options
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertCount(4, $poll['options']);
        
        $optionTexts = array_column($poll['options'], 'option_text');
        foreach ($options as $opt) {
            $this->assertContains($opt, $optionTexts);
        }
    }
    
    public function testCreatePollActiveByDefault(): void
    {
        $pollId = Poll::create([
            'question' => 'Active Poll ' . time(),
            'options' => ['Yes', 'No']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertEquals(1, $poll['active']);
    }
    
    public function testCreateInactivePoll(): void
    {
        $pollId = Poll::create([
            'question' => 'Inactive Poll ' . time(),
            'options' => ['A', 'B'],
            'active' => 0
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $this->assertEquals(0, $poll['active']);
    }
    
    // ==========================================
    // Poll Retrieval Tests
    // ==========================================
    
    public function testGetByIdReturnsPoll(): void
    {
        $pollId = Poll::create([
            'question' => 'Get By ID Test ' . time(),
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        
        $this->assertIsArray($poll);
        $this->assertEquals($pollId, $poll['id']);
        $this->assertArrayHasKey('options', $poll);
        $this->assertArrayHasKey('total_votes', $poll);
    }
    
    public function testGetByIdReturnsNullForNonExistent(): void
    {
        $poll = Poll::getById(999999);
        
        $this->assertNull($poll);
    }
    
    public function testGetBySlugReturnsPoll(): void
    {
        $slug = 'test-slug-' . time();
        
        $pollId = Poll::create([
            'question' => 'Slug Test',
            'slug' => $slug,
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getBySlug($slug);
        
        $this->assertIsArray($poll);
        $this->assertEquals($slug, $poll['slug']);
    }
    
    public function testGetBySlugReturnsNullForNonExistent(): void
    {
        $poll = Poll::getBySlug('nonexistent-slug-' . time());
        
        $this->assertNull($poll);
    }
    
    public function testGetAllReturnsArray(): void
    {
        $polls = Poll::getAll();
        
        $this->assertIsArray($polls);
    }
    
    public function testGetAllActiveOnly(): void
    {
        // Create active poll
        $activeId = Poll::create([
            'question' => 'Active Poll ' . time(),
            'options' => ['A', 'B'],
            'active' => 1
        ]);
        self::$testPollIds[] = $activeId;
        
        // Create inactive poll
        $inactiveId = Poll::create([
            'question' => 'Inactive Poll ' . time(),
            'options' => ['A', 'B'],
            'active' => 0
        ]);
        self::$testPollIds[] = $inactiveId;
        
        $activePolls = Poll::getAll(true);
        
        foreach ($activePolls as $poll) {
            $this->assertEquals(1, $poll['active']);
        }
    }
    
    public function testGetActiveReturnsOnlyActivePolls(): void
    {
        $polls = Poll::getActive(10);
        
        foreach ($polls as $poll) {
            $this->assertEquals(1, $poll['active']);
        }
    }
    
    // ==========================================
    // Voting Tests
    // ==========================================
    
    public function testVoteSuccessful(): void
    {
        $pollId = Poll::create([
            'question' => 'Vote Test ' . time(),
            'options' => ['Option A', 'Option B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $optionId = $poll['options'][0]['id'];
        
        $result = Poll::vote($pollId, $optionId, '10.0.0.' . rand(1, 255));
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('poll', $result);
    }
    
    public function testVoteIncrementsCount(): void
    {
        $pollId = Poll::create([
            'question' => 'Vote Count Test ' . time(),
            'options' => ['Choice 1', 'Choice 2']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $optionId = $poll['options'][0]['id'];
        $initialVotes = (int) $poll['options'][0]['votes'];
        
        Poll::vote($pollId, $optionId, '10.1.0.' . rand(1, 255));
        
        $updatedPoll = Poll::getById($pollId);
        $newVotes = (int) $updatedPoll['options'][0]['votes'];
        
        $this->assertEquals($initialVotes + 1, $newVotes);
    }
    
    public function testCannotVoteTwiceFromSameIP(): void
    {
        $pollId = Poll::create([
            'question' => 'No Double Vote ' . time(),
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $optionId = $poll['options'][0]['id'];
        $ip = '192.168.' . rand(1, 255) . '.' . rand(1, 255);
        
        // First vote
        $first = Poll::vote($pollId, $optionId, $ip);
        $this->assertTrue($first['success']);
        
        // Second vote should fail
        $second = Poll::vote($pollId, $optionId, $ip);
        $this->assertFalse($second['success']);
        $this->assertArrayHasKey('error', $second);
    }
    
    public function testVoteWithInvalidOption(): void
    {
        $pollId = Poll::create([
            'question' => 'Invalid Option ' . time(),
            'options' => ['Valid A', 'Valid B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $result = Poll::vote($pollId, 999999, '10.2.0.1');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testHasVotedReturnsTrueAfterVoting(): void
    {
        $pollId = Poll::create([
            'question' => 'Has Voted Test ' . time(),
            'options' => ['Yes', 'No']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        $optionId = $poll['options'][0]['id'];
        $ip = '10.3.' . rand(1, 255) . '.' . rand(1, 255);
        
        // Before voting
        $this->assertFalse(Poll::hasVoted($pollId, $ip));
        
        // Vote
        Poll::vote($pollId, $optionId, $ip);
        
        // After voting
        $this->assertTrue(Poll::hasVoted($pollId, $ip));
    }
    
    // ==========================================
    // Statistics Tests
    // ==========================================
    
    public function testGetStatsReturnsCorrectFormat(): void
    {
        $pollId = Poll::create([
            'question' => 'Stats Test ' . time(),
            'options' => ['A', 'B', 'C']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $stats = Poll::getStats($pollId);
        
        $this->assertArrayHasKey('total_votes', $stats);
        $this->assertArrayHasKey('options', $stats);
        $this->assertCount(3, $stats['options']);
        
        foreach ($stats['options'] as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('text', $option);
            $this->assertArrayHasKey('votes', $option);
            $this->assertArrayHasKey('percentage', $option);
        }
    }
    
    public function testGetStatsCalculatesPercentages(): void
    {
        $pollId = Poll::create([
            'question' => 'Percentage Test ' . time(),
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        
        // Vote 3 times for A, 1 time for B
        for ($i = 0; $i < 3; $i++) {
            Poll::vote($pollId, $poll['options'][0]['id'], '10.4.' . $i . '.1');
        }
        Poll::vote($pollId, $poll['options'][1]['id'], '10.4.100.1');
        
        $stats = Poll::getStats($pollId);
        
        $this->assertEquals(4, $stats['total_votes']);
        
        // A should have 75%, B should have 25%
        $optionA = $stats['options'][0];
        $optionB = $stats['options'][1];
        
        $this->assertEquals(75, $optionA['percentage']);
        $this->assertEquals(25, $optionB['percentage']);
    }
    
    public function testGetStatsWithNoVotes(): void
    {
        $pollId = Poll::create([
            'question' => 'No Votes Test ' . time(),
            'options' => ['X', 'Y']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $stats = Poll::getStats($pollId);
        
        $this->assertEquals(0, $stats['total_votes']);
        
        foreach ($stats['options'] as $option) {
            $this->assertEquals(0, $option['votes']);
            $this->assertEquals(0, $option['percentage']);
        }
    }
    
    // ==========================================
    // Update Tests
    // ==========================================
    
    public function testUpdateQuestion(): void
    {
        $pollId = Poll::create([
            'question' => 'Original Question',
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $result = Poll::update($pollId, ['question' => 'Updated Question']);
        
        $this->assertTrue($result);
        
        $poll = Poll::getById($pollId);
        $this->assertEquals('Updated Question', $poll['question']);
    }
    
    public function testUpdateDescription(): void
    {
        $pollId = Poll::create([
            'question' => 'Desc Update Test ' . time(),
            'description' => 'Old desc',
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        Poll::update($pollId, ['description' => 'New description']);
        
        $poll = Poll::getById($pollId);
        $this->assertEquals('New description', $poll['description']);
    }
    
    // ==========================================
    // Toggle Status Tests
    // ==========================================
    
    public function testToggleStatusDeactivates(): void
    {
        $pollId = Poll::create([
            'question' => 'Toggle Test ' . time(),
            'options' => ['A', 'B'],
            'active' => 1
        ]);
        
        self::$testPollIds[] = $pollId;
        
        Poll::toggleStatus($pollId);
        
        $poll = Poll::getById($pollId);
        $this->assertEquals(0, $poll['active']);
    }
    
    public function testToggleStatusActivates(): void
    {
        $pollId = Poll::create([
            'question' => 'Toggle Activate ' . time(),
            'options' => ['A', 'B'],
            'active' => 0
        ]);
        
        self::$testPollIds[] = $pollId;
        
        Poll::toggleStatus($pollId);
        
        $poll = Poll::getById($pollId);
        $this->assertEquals(1, $poll['active']);
    }
    
    // ==========================================
    // Delete Tests
    // ==========================================
    
    public function testDeletePoll(): void
    {
        $pollId = Poll::create([
            'question' => 'To Delete ' . time(),
            'options' => ['A', 'B']
        ]);
        
        $beforeDelete = Poll::getById($pollId);
        $this->assertNotNull($beforeDelete);
        
        $result = Poll::delete($pollId);
        $this->assertTrue($result);
        
        $afterDelete = Poll::getById($pollId);
        $this->assertNull($afterDelete);
    }
    
    public function testDeleteNonExistentPoll(): void
    {
        $result = Poll::delete(999999);
        
        $this->assertFalse($result);
    }
    
    // ==========================================
    // Slug Tests
    // ==========================================
    
    public function testGenerateSlugFromText(): void
    {
        $slug = Poll::generateSlug('Cine va câștiga Liga 1?');
        
        $this->assertNotEmpty($slug);
        $this->assertStringNotContainsString(' ', $slug);
        $this->assertStringNotContainsString('?', $slug);
    }
    
    public function testGenerateSlugRomanianChars(): void
    {
        $slug = Poll::generateSlug('Sondaj cu diacritice ăîâșț');
        
        $this->assertStringNotContainsString('ă', $slug);
        $this->assertStringNotContainsString('î', $slug);
        $this->assertStringNotContainsString('ș', $slug);
    }
    
    public function testSlugExistsReturnsTrue(): void
    {
        $uniqueSlug = 'exists-test-' . time();
        
        $pollId = Poll::create([
            'question' => 'Slug Exists Test',
            'slug' => $uniqueSlug,
            'options' => ['A', 'B']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $exists = Poll::slugExists($uniqueSlug);
        
        $this->assertTrue($exists);
    }
    
    public function testSlugExistsReturnsFalse(): void
    {
        $exists = Poll::slugExists('nonexistent-' . time() . rand(10000, 99999));
        
        $this->assertFalse($exists);
    }
    
    // ==========================================
    // Total Votes Tests
    // ==========================================
    
    public function testTotalVotesCalculatedCorrectly(): void
    {
        $pollId = Poll::create([
            'question' => 'Total Votes Test ' . time(),
            'options' => ['A', 'B', 'C']
        ]);
        
        self::$testPollIds[] = $pollId;
        
        $poll = Poll::getById($pollId);
        
        // Initial total should be 0
        $this->assertEquals(0, $poll['total_votes']);
        
        // Add some votes
        Poll::vote($pollId, $poll['options'][0]['id'], '10.5.0.1');
        Poll::vote($pollId, $poll['options'][0]['id'], '10.5.0.2');
        Poll::vote($pollId, $poll['options'][1]['id'], '10.5.0.3');
        
        $updatedPoll = Poll::getById($pollId);
        $this->assertEquals(3, $updatedPoll['total_votes']);
    }
}
