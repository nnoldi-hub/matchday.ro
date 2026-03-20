<?php
/**
 * Poll Model
 * MatchDay.ro - Interactive Polls Management
 */

require_once(__DIR__ . '/../config/database.php');

class Poll {
    
    /**
     * Get all polls with options and vote counts
     */
    public static function getAll(bool $activeOnly = false): array {
        $sql = "SELECT * FROM polls";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $polls = Database::fetchAll($sql);
        
        // Add options to each poll
        foreach ($polls as &$poll) {
            $poll['options'] = self::getOptions($poll['id']);
            $poll['total_votes'] = array_sum(array_column($poll['options'], 'votes'));
        }
        
        return $polls;
    }
    
    /**
     * Get single poll by slug
     */
    public static function getBySlug(string $slug): ?array {
        $poll = Database::fetchOne(
            "SELECT * FROM polls WHERE slug = :slug",
            ['slug' => $slug]
        );
        
        if ($poll) {
            $poll['options'] = self::getOptions($poll['id']);
            $poll['total_votes'] = array_sum(array_column($poll['options'], 'votes'));
        }
        
        return $poll;
    }
    
    /**
     * Get single poll by ID
     */
    public static function getById(int $id): ?array {
        $poll = Database::fetchOne(
            "SELECT * FROM polls WHERE id = :id",
            ['id' => $id]
        );
        
        if ($poll) {
            $poll['options'] = self::getOptions($poll['id']);
            $poll['total_votes'] = array_sum(array_column($poll['options'], 'votes'));
        }
        
        return $poll;
    }
    
    /**
     * Get active polls
     */
    public static function getActive(int $limit = 5): array {
        $polls = Database::fetchAll(
            "SELECT * FROM polls WHERE active = 1 ORDER BY created_at DESC LIMIT :limit",
            ['limit' => $limit]
        );
        
        foreach ($polls as &$poll) {
            $poll['options'] = self::getOptions($poll['id']);
            $poll['total_votes'] = array_sum(array_column($poll['options'], 'votes'));
        }
        
        return $polls;
    }
    
    /**
     * Get poll options
     */
    public static function getOptions(int $pollId): array {
        return Database::fetchAll(
            "SELECT * FROM poll_options WHERE poll_id = :poll_id ORDER BY sort_order, id",
            ['poll_id' => $pollId]
        );
    }
    
    /**
     * Create new poll
     */
    public static function create(array $data): int {
        Database::beginTransaction();
        
        try {
            $slug = $data['slug'] ?? self::generateSlug($data['question']);
            
            $pollId = Database::insert(
                "INSERT INTO polls (slug, question, description, active, created_at) 
                 VALUES (:slug, :question, :description, :active, CURRENT_TIMESTAMP)",
                [
                    'slug' => $slug,
                    'question' => $data['question'],
                    'description' => $data['description'] ?? '',
                    'active' => $data['active'] ?? 1
                ]
            );
            
            // Insert options
            if (!empty($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $index => $optionText) {
                    if (trim($optionText)) {
                        Database::insert(
                            "INSERT INTO poll_options (poll_id, option_text, sort_order) VALUES (:poll_id, :text, :order)",
                            [
                                'poll_id' => $pollId,
                                'text' => trim($optionText),
                                'order' => $index
                            ]
                        );
                    }
                }
            }
            
            Database::commit();
            return $pollId;
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
    
    /**
     * Update poll
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['question'])) {
            $fields[] = "question = :question";
            $params['question'] = $data['question'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params['description'] = $data['description'];
        }
        
        if (isset($data['active'])) {
            $fields[] = "active = :active";
            $params['active'] = $data['active'] ? 1 : 0;
        }
        
        if (empty($fields)) return false;
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE polls SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return Database::execute($sql, $params) > 0;
    }
    
    /**
     * Delete poll
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM polls WHERE id = :id", ['id' => $id]) > 0;
    }
    
    /**
     * Toggle poll status
     */
    public static function toggleStatus(int $id): bool {
        return Database::execute(
            "UPDATE polls SET active = NOT active, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
    
    /**
     * Vote on poll
     */
    public static function vote(int $pollId, int $optionId, string $ipAddress): array {
        $ipHash = hash('sha256', $ipAddress . 'matchday_salt_2026');
        
        // Check if already voted
        $hasVoted = Database::fetchValue(
            "SELECT COUNT(*) FROM poll_votes WHERE poll_id = :poll_id AND ip_hash = :ip_hash",
            ['poll_id' => $pollId, 'ip_hash' => $ipHash]
        );
        
        if ($hasVoted > 0) {
            return ['success' => false, 'error' => 'Ai votat deja la acest sondaj.'];
        }
        
        // Verify option belongs to poll
        $option = Database::fetchOne(
            "SELECT id FROM poll_options WHERE id = :option_id AND poll_id = :poll_id",
            ['option_id' => $optionId, 'poll_id' => $pollId]
        );
        
        if (!$option) {
            return ['success' => false, 'error' => 'Opțiune invalidă.'];
        }
        
        Database::beginTransaction();
        
        try {
            // Record vote
            Database::insert(
                "INSERT INTO poll_votes (poll_id, option_id, ip_hash) VALUES (:poll_id, :option_id, :ip_hash)",
                ['poll_id' => $pollId, 'option_id' => $optionId, 'ip_hash' => $ipHash]
            );
            
            // Increment vote count
            Database::execute(
                "UPDATE poll_options SET votes = votes + 1 WHERE id = :option_id",
                ['option_id' => $optionId]
            );
            
            Database::commit();
            
            // Return updated poll data
            $poll = self::getById($pollId);
            return ['success' => true, 'poll' => $poll];
            
        } catch (Exception $e) {
            Database::rollback();
            return ['success' => false, 'error' => 'Eroare la înregistrarea votului.'];
        }
    }
    
    /**
     * Check if IP has voted
     */
    public static function hasVoted(int $pollId, string $ipAddress): bool {
        $ipHash = hash('sha256', $ipAddress . 'matchday_salt_2026');
        return Database::fetchValue(
            "SELECT COUNT(*) FROM poll_votes WHERE poll_id = :poll_id AND ip_hash = :ip_hash",
            ['poll_id' => $pollId, 'ip_hash' => $ipHash]
        ) > 0;
    }
    
    /**
     * Get vote statistics
     */
    public static function getStats(int $pollId): array {
        $options = self::getOptions($pollId);
        $totalVotes = array_sum(array_column($options, 'votes'));
        
        $stats = [];
        foreach ($options as $option) {
            $percentage = $totalVotes > 0 ? round(($option['votes'] / $totalVotes) * 100, 1) : 0;
            $stats[] = [
                'id' => $option['id'],
                'text' => $option['option_text'],
                'votes' => $option['votes'],
                'percentage' => $percentage
            ];
        }
        
        return [
            'total_votes' => $totalVotes,
            'options' => $stats
        ];
    }
    
    /**
     * Generate URL-safe slug
     */
    public static function generateSlug(string $text): string {
        $slug = mb_strtolower($text, 'UTF-8');
        
        $replacements = [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
            'Ă' => 'a', 'Â' => 'a', 'Î' => 'i', 'Ș' => 's', 'Ț' => 't'
        ];
        $slug = strtr($slug, $replacements);
        
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Limit length
        if (strlen($slug) > 50) {
            $slug = substr($slug, 0, 50);
            $slug = rtrim($slug, '-');
        }
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while (self::slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    public static function slugExists(string $slug): bool {
        return Database::fetchValue(
            "SELECT COUNT(*) FROM polls WHERE slug = :slug",
            ['slug' => $slug]
        ) > 0;
    }
    
    /**
     * Update poll options (replaces existing options)
     * Note: This will reset vote counts!
     */
    public static function updateOptions(int $pollId, array $options): bool {
        Database::beginTransaction();
        
        try {
            // Delete existing options and votes
            Database::execute("DELETE FROM poll_votes WHERE poll_id = :poll_id", ['poll_id' => $pollId]);
            Database::execute("DELETE FROM poll_options WHERE poll_id = :poll_id", ['poll_id' => $pollId]);
            
            // Insert new options
            foreach ($options as $index => $optionText) {
                if (trim($optionText)) {
                    Database::insert(
                        "INSERT INTO poll_options (poll_id, option_text, sort_order) VALUES (:poll_id, :text, :order)",
                        [
                            'poll_id' => $pollId,
                            'text' => trim($optionText),
                            'order' => $index
                        ]
                    );
                }
            }
            
            Database::commit();
            return true;
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
    
    /**
     * Add a single option to existing poll
     */
    public static function addOption(int $pollId, string $optionText): int {
        $maxOrder = Database::fetchValue(
            "SELECT MAX(sort_order) FROM poll_options WHERE poll_id = :poll_id",
            ['poll_id' => $pollId]
        ) ?? -1;
        
        return Database::insert(
            "INSERT INTO poll_options (poll_id, option_text, sort_order) VALUES (:poll_id, :text, :order)",
            [
                'poll_id' => $pollId,
                'text' => trim($optionText),
                'order' => $maxOrder + 1
            ]
        );
    }
    
    /**
     * Remove option from poll
     */
    public static function removeOption(int $optionId): bool {
        // Delete votes for this option first
        Database::execute("DELETE FROM poll_votes WHERE option_id = :option_id", ['option_id' => $optionId]);
        return Database::execute("DELETE FROM poll_options WHERE id = :id", ['id' => $optionId]) > 0;
    }
}
