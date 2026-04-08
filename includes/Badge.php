<?php
/**
 * Badge & Gamification System
 * MatchDay.ro - Rewards for user engagement
 */

require_once(__DIR__ . '/../config/database.php');

class Badge {
    
    // Available badges with requirements
    private static $badges = [
        'first_comment' => [
            'name' => 'Primul Comentariu',
            'description' => 'Ai lăsat primul tău comentariu',
            'icon' => 'fa-comment',
            'color' => '#3498db',
            'points' => 10
        ],
        'commenter_5' => [
            'name' => 'Comentator Activ',
            'description' => 'Ai lăsat 5 comentarii',
            'icon' => 'fa-comments',
            'color' => '#2ecc71',
            'points' => 25
        ],
        'commenter_25' => [
            'name' => 'Vocea Galeriei',
            'description' => 'Ai lăsat 25 de comentarii',
            'icon' => 'fa-bullhorn',
            'color' => '#9b59b6',
            'points' => 100
        ],
        'first_poll' => [
            'name' => 'Prima Votare',
            'description' => 'Ai votat în primul sondaj',
            'icon' => 'fa-check-to-slot',
            'color' => '#e74c3c',
            'points' => 5
        ],
        'voter_10' => [
            'name' => 'Participant Activ',
            'description' => 'Ai votat în 10 sondaje',
            'icon' => 'fa-square-poll-vertical',
            'color' => '#f39c12',
            'points' => 30
        ],
        'newsletter_sub' => [
            'name' => 'Fan Dedicat',
            'description' => 'Te-ai abonat la newsletter',
            'icon' => 'fa-envelope-open-text',
            'color' => '#1abc9c',
            'points' => 15
        ],
        'early_bird' => [
            'name' => 'Matinal',
            'description' => 'Ai vizitat site-ul înainte de 7:00',
            'icon' => 'fa-sun',
            'color' => '#f1c40f',
            'points' => 10
        ],
        'night_owl' => [
            'name' => 'Nocturn',
            'description' => 'Ai vizitat site-ul după miezul nopții',
            'icon' => 'fa-moon',
            'color' => '#34495e',
            'points' => 10
        ],
        'reader_10' => [
            'name' => 'Cititor Dedicat',
            'description' => 'Ai citit 10 articole',
            'icon' => 'fa-book-open',
            'color' => '#16a085',
            'points' => 20
        ],
        'reader_50' => [
            'name' => 'Enciclopedie',
            'description' => 'Ai citit 50 de articole',
            'icon' => 'fa-book',
            'color' => '#8e44ad',
            'points' => 75
        ],
        'share_first' => [
            'name' => 'Promotor',
            'description' => 'Ai distribuit primul articol',
            'icon' => 'fa-share-nodes',
            'color' => '#e67e22',
            'points' => 15
        ],
        'category_explorer' => [
            'name' => 'Explorer',
            'description' => 'Ai vizitat toate categoriile',
            'icon' => 'fa-compass',
            'color' => '#27ae60',
            'points' => 40
        ],
        'weekend_warrior' => [
            'name' => 'Războinic de Weekend',
            'description' => 'Activ în toate weekendurile lunii',
            'icon' => 'fa-calendar-week',
            'color' => '#c0392b',
            'points' => 50
        ],
        'loyal_reader' => [
            'name' => 'Cititor Fidel',
            'description' => '30 de zile consecutive de vizite',
            'icon' => 'fa-medal',
            'color' => '#d4af37',
            'points' => 200
        ]
    ];
    
    /**
     * Get all available badges
     */
    public static function getAllBadges(): array {
        return self::$badges;
    }
    
    /**
     * Get badges earned by a user (by IP hash)
     */
    public static function getUserBadges(string $identifier): array {
        $badges = Database::fetchAll(
            "SELECT badge_id, earned_at FROM user_badges WHERE user_identifier = :id ORDER BY earned_at DESC",
            ['id' => $identifier]
        );
        
        $result = [];
        foreach ($badges as $badge) {
            if (isset(self::$badges[$badge['badge_id']])) {
                $result[] = array_merge(
                    ['id' => $badge['badge_id']],
                    self::$badges[$badge['badge_id']],
                    ['earned_at' => $badge['earned_at']]
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get total points for a user
     */
    public static function getUserPoints(string $identifier): int {
        $badges = self::getUserBadges($identifier);
        return array_sum(array_column($badges, 'points'));
    }
    
    /**
     * Award a badge to a user
     */
    public static function award(string $identifier, string $badgeId): bool {
        if (!isset(self::$badges[$badgeId])) {
            return false;
        }
        
        // Check if already earned
        $existing = Database::fetchValue(
            "SELECT COUNT(*) FROM user_badges WHERE user_identifier = :id AND badge_id = :badge",
            ['id' => $identifier, 'badge' => $badgeId]
        );
        
        if ($existing > 0) {
            return false; // Already has this badge
        }
        
        // Award badge
        Database::execute(
            "INSERT INTO user_badges (user_identifier, badge_id, earned_at) VALUES (:id, :badge, CURRENT_TIMESTAMP)",
            ['id' => $identifier, 'badge' => $badgeId]
        );
        
        return true;
    }
    
    /**
     * Check and award badges based on activity
     */
    public static function checkAndAward(string $identifier, array $activity): array {
        $newBadges = [];
        
        // Comment badges
        if (isset($activity['comments'])) {
            $count = $activity['comments'];
            if ($count >= 1 && self::award($identifier, 'first_comment')) {
                $newBadges[] = self::$badges['first_comment'];
            }
            if ($count >= 5 && self::award($identifier, 'commenter_5')) {
                $newBadges[] = self::$badges['commenter_5'];
            }
            if ($count >= 25 && self::award($identifier, 'commenter_25')) {
                $newBadges[] = self::$badges['commenter_25'];
            }
        }
        
        // Poll badges
        if (isset($activity['polls'])) {
            $count = $activity['polls'];
            if ($count >= 1 && self::award($identifier, 'first_poll')) {
                $newBadges[] = self::$badges['first_poll'];
            }
            if ($count >= 10 && self::award($identifier, 'voter_10')) {
                $newBadges[] = self::$badges['voter_10'];
            }
        }
        
        // Reading badges
        if (isset($activity['articles_read'])) {
            $count = $activity['articles_read'];
            if ($count >= 10 && self::award($identifier, 'reader_10')) {
                $newBadges[] = self::$badges['reader_10'];
            }
            if ($count >= 50 && self::award($identifier, 'reader_50')) {
                $newBadges[] = self::$badges['reader_50'];
            }
        }
        
        // Time-based badges
        if (isset($activity['visit_hour'])) {
            $hour = (int)$activity['visit_hour'];
            if ($hour < 7 && $hour >= 5 && self::award($identifier, 'early_bird')) {
                $newBadges[] = self::$badges['early_bird'];
            }
            if ($hour >= 0 && $hour < 5 && self::award($identifier, 'night_owl')) {
                $newBadges[] = self::$badges['night_owl'];
            }
        }
        
        // Newsletter subscription
        if (isset($activity['newsletter']) && $activity['newsletter']) {
            if (self::award($identifier, 'newsletter_sub')) {
                $newBadges[] = self::$badges['newsletter_sub'];
            }
        }
        
        // Share badge
        if (isset($activity['shares']) && $activity['shares'] >= 1) {
            if (self::award($identifier, 'share_first')) {
                $newBadges[] = self::$badges['share_first'];
            }
        }
        
        return $newBadges;
    }
    
    /**
     * Get badge info
     */
    public static function getBadge(string $badgeId): ?array {
        return self::$badges[$badgeId] ?? null;
    }
    
    /**
     * Get leaderboard
     */
    public static function getLeaderboard(int $limit = 10): array {
        return Database::fetchAll(
            "SELECT user_identifier, COUNT(*) as badge_count, 
                    SUM(CASE 
                        WHEN badge_id = 'first_comment' THEN 10
                        WHEN badge_id = 'commenter_5' THEN 25
                        WHEN badge_id = 'commenter_25' THEN 100
                        WHEN badge_id = 'first_poll' THEN 5
                        WHEN badge_id = 'voter_10' THEN 30
                        WHEN badge_id = 'newsletter_sub' THEN 15
                        WHEN badge_id = 'reader_10' THEN 20
                        WHEN badge_id = 'reader_50' THEN 75
                        WHEN badge_id = 'share_first' THEN 15
                        WHEN badge_id = 'category_explorer' THEN 40
                        WHEN badge_id = 'loyal_reader' THEN 200
                        ELSE 10
                    END) as total_points
             FROM user_badges 
             GROUP BY user_identifier 
             ORDER BY total_points DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Get user rank
     */
    public static function getUserRank(string $identifier): int {
        $leaderboard = self::getLeaderboard(1000);
        $rank = 1;
        foreach ($leaderboard as $entry) {
            if ($entry['user_identifier'] === $identifier) {
                return $rank;
            }
            $rank++;
        }
        return 0; // Not ranked
    }
}
