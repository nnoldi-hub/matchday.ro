<?php
/**
 * Live Scores Service
 * MatchDay.ro - Integration with football data APIs
 * 
 * Supports: API-Football, Football-Data.org, or manual input
 */

require_once(__DIR__ . '/../config/database.php');

class LiveScores {
    
    // API configuration
    private static $apiKey = null;
    private static $apiProvider = 'manual'; // 'api-football', 'football-data', 'manual'
    private static $cacheMinutes = 1; // Cache live scores for 1 minute
    
    // Competition IDs (API-Football)
    private static $competitions = [
        'liga1' => 283,          // Liga 1 Romania
        'champions' => 2,         // Champions League
        'europa' => 3,            // Europa League
        'conference' => 848,      // Conference League
        'premier' => 39,          // Premier League
        'laliga' => 140,          // La Liga
        'bundesliga' => 78,       // Bundesliga
        'seriea' => 135,          // Serie A
        'ligue1' => 61            // Ligue 1
    ];
    
    /**
     * Initialize API configuration
     */
    public static function init() {
        $configFile = __DIR__ . '/../config/livescores_config.php';
        if (file_exists($configFile)) {
            $config = require($configFile);
            self::$apiKey = $config['api_key'] ?? null;
            self::$apiProvider = $config['provider'] ?? 'manual';
        }
    }
    
    /**
     * Get live matches
     */
    public static function getLiveMatches(?string $competition = null): array {
        self::init();
        
        // Check cache first
        $cacheKey = 'live_matches_' . ($competition ?? 'all');
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $matches = [];
        
        switch (self::$apiProvider) {
            case 'api-football':
                $matches = self::fetchFromApiFootball($competition);
                break;
            case 'football-data':
                $matches = self::fetchFromFootballData($competition);
                break;
            default:
                $matches = self::getManualMatches();
        }
        
        // Cache the result
        self::setCache($cacheKey, $matches, self::$cacheMinutes);
        
        return $matches;
    }
    
    /**
     * Get today's matches
     */
    public static function getTodayMatches(?string $competition = null): array {
        self::init();
        
        $cacheKey = 'today_matches_' . ($competition ?? 'all');
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $matches = [];
        
        switch (self::$apiProvider) {
            case 'api-football':
                $matches = self::fetchTodayFromApiFootball($competition);
                break;
            case 'football-data':
                $matches = self::fetchTodayFromFootballData($competition);
                break;
            default:
                $matches = self::getManualMatches(date('Y-m-d'));
        }
        
        self::setCache($cacheKey, $matches, 5); // Cache for 5 minutes
        
        return $matches;
    }
    
    /**
     * Fetch from API-Football (RapidAPI)
     */
    private static function fetchFromApiFootball(?string $competition): array {
        if (!self::$apiKey) return [];
        
        $leagueId = $competition ? (self::$competitions[$competition] ?? null) : null;
        
        $url = 'https://v3.football.api-sports.io/fixtures?live=all';
        if ($leagueId) {
            $url .= '&league=' . $leagueId;
        }
        
        $response = self::makeApiRequest($url, [
            'x-rapidapi-host: v3.football.api-sports.io',
            'x-rapidapi-key: ' . self::$apiKey
        ]);
        
        if (!$response || !isset($response['response'])) {
            return [];
        }
        
        return array_map(function($fixture) {
            return self::formatApiFootballMatch($fixture);
        }, $response['response']);
    }
    
    /**
     * Fetch today's matches from API-Football
     */
    private static function fetchTodayFromApiFootball(?string $competition): array {
        if (!self::$apiKey) return [];
        
        $leagueId = $competition ? (self::$competitions[$competition] ?? null) : null;
        $today = date('Y-m-d');
        
        $url = "https://v3.football.api-sports.io/fixtures?date={$today}";
        if ($leagueId) {
            $url .= '&league=' . $leagueId;
        }
        
        $response = self::makeApiRequest($url, [
            'x-rapidapi-host: v3.football.api-sports.io',
            'x-rapidapi-key: ' . self::$apiKey
        ]);
        
        if (!$response || !isset($response['response'])) {
            return [];
        }
        
        return array_map(function($fixture) {
            return self::formatApiFootballMatch($fixture);
        }, $response['response']);
    }
    
    /**
     * Format API-Football response
     */
    private static function formatApiFootballMatch($fixture): array {
        $status = $fixture['fixture']['status']['short'] ?? 'NS';
        $elapsed = $fixture['fixture']['status']['elapsed'] ?? null;
        
        return [
            'id' => $fixture['fixture']['id'],
            'competition' => $fixture['league']['name'] ?? '',
            'competition_logo' => $fixture['league']['logo'] ?? '',
            'home_team' => $fixture['teams']['home']['name'] ?? '',
            'home_logo' => $fixture['teams']['home']['logo'] ?? '',
            'away_team' => $fixture['teams']['away']['name'] ?? '',
            'away_logo' => $fixture['teams']['away']['logo'] ?? '',
            'home_score' => $fixture['goals']['home'] ?? 0,
            'away_score' => $fixture['goals']['away'] ?? 0,
            'status' => self::translateStatus($status),
            'status_code' => $status,
            'minute' => $elapsed,
            'kickoff' => $fixture['fixture']['date'] ?? null,
            'venue' => $fixture['fixture']['venue']['name'] ?? '',
            'is_live' => in_array($status, ['1H', '2H', 'HT', 'ET', 'P', 'LIVE'])
        ];
    }
    
    /**
     * Fetch from Football-Data.org
     */
    private static function fetchFromFootballData(?string $competition): array {
        if (!self::$apiKey) return [];
        
        $url = 'https://api.football-data.org/v4/matches?status=LIVE';
        
        $response = self::makeApiRequest($url, [
            'X-Auth-Token: ' . self::$apiKey
        ]);
        
        if (!$response || !isset($response['matches'])) {
            return [];
        }
        
        return array_map(function($match) {
            return self::formatFootballDataMatch($match);
        }, $response['matches']);
    }
    
    /**
     * Fetch today's matches from Football-Data.org
     */
    private static function fetchTodayFromFootballData(?string $competition): array {
        if (!self::$apiKey) return [];
        
        $today = date('Y-m-d');
        $url = "https://api.football-data.org/v4/matches?dateFrom={$today}&dateTo={$today}";
        
        $response = self::makeApiRequest($url, [
            'X-Auth-Token: ' . self::$apiKey
        ]);
        
        if (!$response || !isset($response['matches'])) {
            return [];
        }
        
        return array_map(function($match) {
            return self::formatFootballDataMatch($match);
        }, $response['matches']);
    }
    
    /**
     * Format Football-Data.org response
     */
    private static function formatFootballDataMatch($match): array {
        $status = $match['status'] ?? 'SCHEDULED';
        
        return [
            'id' => $match['id'],
            'competition' => $match['competition']['name'] ?? '',
            'competition_logo' => $match['competition']['emblem'] ?? '',
            'home_team' => $match['homeTeam']['name'] ?? '',
            'home_logo' => $match['homeTeam']['crest'] ?? '',
            'away_team' => $match['awayTeam']['name'] ?? '',
            'away_logo' => $match['awayTeam']['crest'] ?? '',
            'home_score' => $match['score']['fullTime']['home'] ?? 0,
            'away_score' => $match['score']['fullTime']['away'] ?? 0,
            'status' => self::translateStatus($status),
            'status_code' => $status,
            'minute' => null,
            'kickoff' => $match['utcDate'] ?? null,
            'venue' => $match['venue'] ?? '',
            'is_live' => $status === 'IN_PLAY' || $status === 'PAUSED'
        ];
    }
    
    /**
     * Get manually entered matches from database
     */
    public static function getManualMatches(?string $date = null): array {
        $sql = "SELECT * FROM live_matches WHERE 1=1";
        $params = [];
        
        if ($date) {
            $sql .= " AND DATE(kickoff) = :date";
            $params['date'] = $date;
        }
        
        $sql .= " ORDER BY kickoff ASC";
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Add/update manual match
     */
    public static function saveManualMatch(array $data): int {
        $existing = isset($data['id']) ? Database::fetchValue(
            "SELECT id FROM live_matches WHERE id = :id",
            ['id' => $data['id']]
        ) : null;
        
        if ($existing) {
            Database::execute(
                "UPDATE live_matches SET 
                    competition = :competition,
                    home_team = :home_team,
                    away_team = :away_team,
                    home_score = :home_score,
                    away_score = :away_score,
                    status = :status,
                    minute = :minute,
                    kickoff = :kickoff,
                    home_scorers = :home_scorers,
                    away_scorers = :away_scorers,
                    article_id = :article_id,
                    venue = :venue,
                    referee = :referee,
                    referee_team = :referee_team,
                    yellow_cards_home = :yellow_cards_home,
                    yellow_cards_away = :yellow_cards_away,
                    red_cards_home = :red_cards_home,
                    red_cards_away = :red_cards_away,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id",
                [
                    'id' => $data['id'],
                    'competition' => $data['competition'] ?? '',
                    'home_team' => $data['home_team'],
                    'away_team' => $data['away_team'],
                    'home_score' => $data['home_score'] ?? 0,
                    'away_score' => $data['away_score'] ?? 0,
                    'status' => $data['status'] ?? 'scheduled',
                    'minute' => $data['minute'] ?? null,
                    'kickoff' => $data['kickoff'],
                    'home_scorers' => json_encode($data['home_scorers'] ?? []),
                    'away_scorers' => json_encode($data['away_scorers'] ?? []),
                    'article_id' => !empty($data['article_id']) ? (int)$data['article_id'] : null,
                    'venue' => $data['venue'] ?? null,
                    'referee' => $data['referee'] ?? null,
                    'referee_team' => !empty($data['referee_team']) ? json_encode($data['referee_team']) : null,
                    'yellow_cards_home' => !empty($data['yellow_cards_home']) ? json_encode($data['yellow_cards_home']) : null,
                    'yellow_cards_away' => !empty($data['yellow_cards_away']) ? json_encode($data['yellow_cards_away']) : null,
                    'red_cards_home' => !empty($data['red_cards_home']) ? json_encode($data['red_cards_home']) : null,
                    'red_cards_away' => !empty($data['red_cards_away']) ? json_encode($data['red_cards_away']) : null
                ]
            );
            return $data['id'];
        } else {
            return Database::insert(
                "INSERT INTO live_matches 
                    (competition, home_team, away_team, home_score, away_score, status, minute, kickoff, home_scorers, away_scorers, article_id, venue, referee, referee_team, yellow_cards_home, yellow_cards_away, red_cards_home, red_cards_away, created_at) 
                VALUES 
                    (:competition, :home_team, :away_team, :home_score, :away_score, :status, :minute, :kickoff, :home_scorers, :away_scorers, :article_id, :venue, :referee, :referee_team, :yellow_cards_home, :yellow_cards_away, :red_cards_home, :red_cards_away, CURRENT_TIMESTAMP)",
                [
                    'competition' => $data['competition'] ?? '',
                    'home_team' => $data['home_team'],
                    'away_team' => $data['away_team'],
                    'home_score' => $data['home_score'] ?? 0,
                    'away_score' => $data['away_score'] ?? 0,
                    'status' => $data['status'] ?? 'scheduled',
                    'minute' => $data['minute'] ?? null,
                    'kickoff' => $data['kickoff'],
                    'home_scorers' => json_encode($data['home_scorers'] ?? []),
                    'away_scorers' => json_encode($data['away_scorers'] ?? []),
                    'article_id' => !empty($data['article_id']) ? (int)$data['article_id'] : null,
                    'venue' => $data['venue'] ?? null,
                    'referee' => $data['referee'] ?? null,
                    'referee_team' => !empty($data['referee_team']) ? json_encode($data['referee_team']) : null,
                    'yellow_cards_home' => !empty($data['yellow_cards_home']) ? json_encode($data['yellow_cards_home']) : null,
                    'yellow_cards_away' => !empty($data['yellow_cards_away']) ? json_encode($data['yellow_cards_away']) : null,
                    'red_cards_home' => !empty($data['red_cards_home']) ? json_encode($data['red_cards_home']) : null,
                    'red_cards_away' => !empty($data['red_cards_away']) ? json_encode($data['red_cards_away']) : null
                ]
            );
        }
    }
    
    /**
     * Delete manual match
     */
    public static function deleteManualMatch(int $id): bool {
        return Database::execute(
            "DELETE FROM live_matches WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Translate status codes
     */
    private static function translateStatus(string $code): string {
        $statuses = [
            // API-Football
            'NS' => 'Nepornit',
            '1H' => 'Repriza 1',
            'HT' => 'Pauză',
            '2H' => 'Repriza 2',
            'ET' => 'Prelungiri',
            'P' => 'Penalty',
            'FT' => 'Final',
            'AET' => 'Final (prelungiri)',
            'PEN' => 'Final (penalty)',
            'BT' => 'Pauză',
            'SUSP' => 'Suspendat',
            'INT' => 'Întrerupt',
            'PST' => 'Amânat',
            'CANC' => 'Anulat',
            'ABD' => 'Abandonat',
            'AWD' => 'Victorie la masă',
            'WO' => 'W.O.',
            'LIVE' => 'LIVE',
            // Football-Data
            'SCHEDULED' => 'Programat',
            'TIMED' => 'Programat',
            'IN_PLAY' => 'LIVE',
            'PAUSED' => 'Pauză',
            'FINISHED' => 'Final',
            'POSTPONED' => 'Amânat',
            'SUSPENDED' => 'Suspendat',
            'CANCELLED' => 'Anulat',
            // Manual
            'scheduled' => 'Programat',
            'live' => 'LIVE',
            'halftime' => 'Pauză',
            'finished' => 'Final'
        ];
        
        return $statuses[$code] ?? $code;
    }
    
    /**
     * Make API request
     */
    private static function makeApiRequest(string $url, array $headers): ?array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            error_log("LiveScores API error: HTTP $httpCode for $url");
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get from cache
     */
    private static function getCache(string $key): ?array {
        $cacheFile = __DIR__ . '/../data/cache/livescores_' . md5($key) . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
            @unlink($cacheFile);
            return null;
        }
        
        return $data['matches'];
    }
    
    /**
     * Set cache
     */
    private static function setCache(string $key, array $matches, int $minutes): void {
        $cacheDir = __DIR__ . '/../data/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/livescores_' . md5($key) . '.json';
        
        file_put_contents($cacheFile, json_encode([
            'expires' => time() + ($minutes * 60),
            'matches' => $matches
        ]));
    }
    
    /**
     * Clear cache
     */
    public static function clearCache(): void {
        $cacheDir = __DIR__ . '/../data/cache';
        $files = glob($cacheDir . '/livescores_*.json');
        
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
