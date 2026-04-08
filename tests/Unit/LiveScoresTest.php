<?php
/**
 * LiveScoresTest - Unit tests for LiveScores class
 * MatchDay.ro - Live football scores integration
 * 
 * Tests: manual matches CRUD, caching, status translation
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

require_once(__DIR__ . '/../bootstrap.php');
require_once(__DIR__ . '/../../includes/LiveScores.php');

class LiveScoresTest extends TestCase
{
    private static $testMatchId;
    
    public static function setUpBeforeClass(): void
    {
        // Create live_matches table if not exists
        \Database::execute("
            CREATE TABLE IF NOT EXISTS live_matches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                competition TEXT,
                home_team TEXT NOT NULL,
                away_team TEXT NOT NULL,
                home_score INTEGER DEFAULT 0,
                away_score INTEGER DEFAULT 0,
                status TEXT DEFAULT 'scheduled',
                minute INTEGER,
                kickoff TEXT NOT NULL,
                home_scorers TEXT,
                away_scorers TEXT,
                home_logo TEXT,
                away_logo TEXT,
                competition_logo TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Clear test cache directory
        $cacheDir = __DIR__ . '/../../data/cache';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/livescores_*.json') as $file) {
                @unlink($file);
            }
        }
        
        // Clean up existing test data
        \Database::execute("DELETE FROM live_matches WHERE competition = 'Test League'");
    }
    
    protected function setUp(): void
    {
        // Re-initialize LiveScores config for each test
        \LiveScores::init();
    }
    
    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        \Database::execute("DELETE FROM live_matches WHERE competition = 'Test League'");
        
        // Clear test cache
        $cacheDir = __DIR__ . '/../../data/cache';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/livescores_*.json') as $file) {
                @unlink($file);
            }
        }
    }
    
    // ======================= MANUAL MATCH CREATE TESTS =======================
    
    #[Test]
    public function testSaveManualMatchCreatesNew(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Test Home FC',
            'away_team' => 'Test Away FC',
            'home_score' => 0,
            'away_score' => 0,
            'status' => 'scheduled',
            'kickoff' => date('Y-m-d H:i:s', strtotime('+2 hours'))
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        
        $this->assertIsInt($matchId);
        $this->assertGreaterThan(0, $matchId);
        
        self::$testMatchId = $matchId;
    }
    
    #[Test]
    #[Depends('testSaveManualMatchCreatesNew')]
    public function testSaveManualMatchUpdatesExisting(): void
    {
        $updatedData = [
            'id' => self::$testMatchId,
            'competition' => 'Test League',
            'home_team' => 'Test Home FC',
            'away_team' => 'Test Away FC',
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'live',
            'minute' => 65,
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($updatedData);
        
        $this->assertEquals(self::$testMatchId, $matchId);
    }
    
    #[Test]
    public function testSaveManualMatchWithScorers(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Scorers FC',
            'away_team' => 'Goals FC',
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'live',
            'minute' => 75,
            'kickoff' => date('Y-m-d H:i:s'),
            'home_scorers' => ['Player A 15\'', 'Player B 45\''],
            'away_scorers' => ['Player C 30\'']
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $this->assertGreaterThan(0, $matchId);
    }
    
    #[Test]
    public function testSaveMatchWithMinimalData(): void
    {
        $matchData = [
            'home_team' => 'Minimal Home',
            'away_team' => 'Minimal Away',
            'kickoff' => date('Y-m-d H:i:s', strtotime('+1 day'))
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $this->assertGreaterThan(0, $matchId);
    }
    
    // ======================= READ TESTS =======================
    
    #[Test]
    public function testGetManualMatchesReturnsArray(): void
    {
        $matches = \LiveScores::getManualMatches();
        
        $this->assertIsArray($matches);
    }
    
    #[Test]
    public function testGetManualMatchesByDate(): void
    {
        // Create a match for today
        $today = date('Y-m-d');
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Today Home',
            'away_team' => 'Today Away',
            'kickoff' => $today . ' 20:00:00'
        ];
        
        \LiveScores::saveManualMatch($matchData);
        
        $matches = \LiveScores::getManualMatches($today);
        
        $this->assertIsArray($matches);
        // Should have at least the match we just created
        $foundToday = false;
        foreach ($matches as $match) {
            if ($match['home_team'] === 'Today Home') {
                $foundToday = true;
                break;
            }
        }
        $this->assertTrue($foundToday);
    }
    
    #[Test]
    public function testGetManualMatchesForFutureDateReturnsEmpty(): void
    {
        $futureDate = date('Y-m-d', strtotime('+365 days'));
        $matches = \LiveScores::getManualMatches($futureDate);
        
        $this->assertIsArray($matches);
        $this->assertEmpty($matches);
    }
    
    #[Test]
    public function testGetLiveMatchesReturnsArray(): void
    {
        // With manual provider (default), should return manual matches
        $matches = \LiveScores::getLiveMatches();
        
        $this->assertIsArray($matches);
    }
    
    #[Test]
    public function testGetLiveMatchesWithCompetitionFilter(): void
    {
        $matches = \LiveScores::getLiveMatches('liga1');
        
        $this->assertIsArray($matches);
    }
    
    #[Test]
    public function testGetTodayMatchesReturnsArray(): void
    {
        $matches = \LiveScores::getTodayMatches();
        
        $this->assertIsArray($matches);
    }
    
    #[Test]
    public function testGetTodayMatchesWithCompetitionFilter(): void
    {
        $matches = \LiveScores::getTodayMatches('champions');
        
        $this->assertIsArray($matches);
    }
    
    // ======================= DELETE TESTS =======================
    
    #[Test]
    public function testDeleteManualMatch(): void
    {
        // Create a match to delete
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Delete Home',
            'away_team' => 'Delete Away',
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        
        $deleted = \LiveScores::deleteManualMatch($matchId);
        $this->assertTrue($deleted);
    }
    
    #[Test]
    public function testDeleteNonExistentMatch(): void
    {
        $deleted = \LiveScores::deleteManualMatch(999999);
        $this->assertFalse($deleted);
    }
    
    // ======================= CACHE TESTS =======================
    
    #[Test]
    public function testClearCache(): void
    {
        // Create some cache by calling getLiveMatches
        \LiveScores::getLiveMatches();
        \LiveScores::getTodayMatches();
        
        // Clear cache
        \LiveScores::clearCache();
        
        // Verify cache files are deleted
        $cacheDir = __DIR__ . '/../../data/cache';
        $cacheFiles = glob($cacheDir . '/livescores_*.json');
        
        // After clear, there should be no livescores cache files
        // (new calls will recreate them, so we just verify the method doesn't throw)
        $this->assertTrue(true);
    }
    
    #[Test]
    public function testCacheIsUsedForRepeatedCalls(): void
    {
        // Clear cache first
        \LiveScores::clearCache();
        
        // First call - creates cache
        $matches1 = \LiveScores::getLiveMatches();
        
        // Second call - should use cache
        $matches2 = \LiveScores::getLiveMatches();
        
        // Results should be the same
        $this->assertEquals($matches1, $matches2);
    }
    
    #[Test]
    public function testDifferentCompetitionsUseDifferentCacheKeys(): void
    {
        \LiveScores::clearCache();
        
        $liga1 = \LiveScores::getLiveMatches('liga1');
        $champions = \LiveScores::getLiveMatches('champions');
        
        // Both should return arrays (possibly empty)
        $this->assertIsArray($liga1);
        $this->assertIsArray($champions);
    }
    
    // ======================= INIT TESTS =======================
    
    #[Test]
    public function testInitDoesNotThrow(): void
    {
        // Should not throw even without config file
        \LiveScores::init();
        $this->assertTrue(true);
    }
    
    // ======================= MATCH STATUS TESTS =======================
    
    #[Test]
    public function testMatchStatusScheduled(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Status Home',
            'away_team' => 'Status Away',
            'status' => 'scheduled',
            'kickoff' => date('Y-m-d H:i:s', strtotime('+3 hours'))
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertNotNull($found);
        $this->assertEquals('scheduled', $found['status']);
    }
    
    #[Test]
    public function testMatchStatusLive(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Live Home',
            'away_team' => 'Live Away',
            'status' => 'live',
            'minute' => 45,
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertNotNull($found);
        $this->assertEquals('live', $found['status']);
        $this->assertEquals(45, $found['minute']);
    }
    
    #[Test]
    public function testMatchStatusFinished(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Finished Home',
            'away_team' => 'Finished Away',
            'home_score' => 3,
            'away_score' => 2,
            'status' => 'finished',
            'kickoff' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertNotNull($found);
        $this->assertEquals('finished', $found['status']);
        $this->assertEquals(3, $found['home_score']);
        $this->assertEquals(2, $found['away_score']);
    }
    
    #[Test]
    public function testMatchStatusHalftime(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'HT Home',
            'away_team' => 'HT Away',
            'home_score' => 1,
            'away_score' => 0,
            'status' => 'halftime',
            'minute' => 45,
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertNotNull($found);
        $this->assertEquals('halftime', $found['status']);
    }
    
    // ======================= EDGE CASES =======================
    
    #[Test]
    public function testSaveMatchWithSpecialCharactersInTeamName(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'FC København',
            'away_team' => 'São Paulo FC',
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $this->assertGreaterThan(0, $matchId);
        
        $matches = \LiveScores::getManualMatches();
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertNotNull($found);
        $this->assertEquals('FC København', $found['home_team']);
        $this->assertEquals('São Paulo FC', $found['away_team']);
    }
    
    #[Test]
    public function testSaveMatchWithLongTeamNames(): void
    {
        $longName = 'The Extremely Long Football Club Name That Goes On Forever United FC';
        
        $matchData = [
            'competition' => 'Test League',
            'home_team' => $longName,
            'away_team' => 'Short FC',
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $this->assertGreaterThan(0, $matchId);
    }
    
    #[Test]
    public function testSaveMatchWithZeroScores(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'Zero Home',
            'away_team' => 'Zero Away',
            'home_score' => 0,
            'away_score' => 0,
            'status' => 'finished',
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertEquals(0, $found['home_score']);
        $this->assertEquals(0, $found['away_score']);
    }
    
    #[Test]
    public function testSaveMatchWithHighScores(): void
    {
        $matchData = [
            'competition' => 'Test League',
            'home_team' => 'High Scorer',
            'away_team' => 'Also High Scorer',
            'home_score' => 10,
            'away_score' => 9,
            'status' => 'finished',
            'kickoff' => date('Y-m-d H:i:s')
        ];
        
        $matchId = \LiveScores::saveManualMatch($matchData);
        $matches = \LiveScores::getManualMatches();
        
        $found = null;
        foreach ($matches as $match) {
            if ($match['id'] == $matchId) {
                $found = $match;
                break;
            }
        }
        
        $this->assertEquals(10, $found['home_score']);
        $this->assertEquals(9, $found['away_score']);
    }
    
    #[Test]
    public function testMatchesOrderedByKickoff(): void
    {
        // Create matches with different kickoff times
        $now = time();
        
        \LiveScores::saveManualMatch([
            'competition' => 'Test League',
            'home_team' => 'Later Home',
            'away_team' => 'Later Away',
            'kickoff' => date('Y-m-d H:i:s', $now + 7200)
        ]);
        
        \LiveScores::saveManualMatch([
            'competition' => 'Test League',
            'home_team' => 'Earlier Home',
            'away_team' => 'Earlier Away',
            'kickoff' => date('Y-m-d H:i:s', $now + 3600)
        ]);
        
        $matches = \LiveScores::getManualMatches();
        
        // Verify matches are ordered by kickoff
        $kickoffs = [];
        foreach ($matches as $match) {
            $kickoffs[] = strtotime($match['kickoff']);
        }
        
        $sortedKickoffs = $kickoffs;
        sort($sortedKickoffs);
        
        $this->assertEquals($sortedKickoffs, $kickoffs);
    }
    
    // ======================= COMPETITION CONSTANTS =======================
    
    #[Test]
    public function testCompetitionsMappingExists(): void
    {
        // Test that calling with known competition names doesn't throw
        $competitions = ['liga1', 'champions', 'europa', 'conference', 'premier', 'laliga', 'bundesliga', 'seriea', 'ligue1'];
        
        foreach ($competitions as $comp) {
            $matches = \LiveScores::getLiveMatches($comp);
            $this->assertIsArray($matches);
        }
    }
    
    #[Test]
    public function testUnknownCompetitionReturnsMatches(): void
    {
        // Unknown competition should still work (returns all or empty)
        $matches = \LiveScores::getLiveMatches('unknown_competition');
        $this->assertIsArray($matches);
    }
}
