<?php
/**
 * Submission Model
 * MatchDay.ro - External article contributions
 */

require_once(__DIR__ . '/../config/database.php');

class Submission {
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';
    
    /**
     * Create a new submission
     */
    public static function create(array $data): int {
        // Validate required fields
        $required = ['title', 'content', 'author_name', 'author_email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '$field' is required");
            }
        }
        
        // Validate email
        if (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address");
        }
        
        // Generate unique token for tracking
        $token = bin2hex(random_bytes(16));
        
        return Database::insert(
            "INSERT INTO submissions 
                (title, excerpt, content, category_id, author_name, author_email, author_bio, 
                 featured_image, status, token, created_at, updated_at) 
            VALUES 
                (:title, :excerpt, :content, :category_id, :author_name, :author_email, :author_bio, 
                 :featured_image, :status, :token, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [
                'title' => trim($data['title']),
                'excerpt' => trim($data['excerpt'] ?? ''),
                'content' => trim($data['content']),
                'category_id' => $data['category_id'] ?? null,
                'author_name' => trim($data['author_name']),
                'author_email' => trim($data['author_email']),
                'author_bio' => trim($data['author_bio'] ?? ''),
                'featured_image' => $data['featured_image'] ?? null,
                'status' => self::STATUS_PENDING,
                'token' => $token
            ]
        );
    }
    
    /**
     * Get submission by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetch(
            "SELECT s.*, c.name as category_name 
             FROM submissions s 
             LEFT JOIN categories c ON s.category_id = c.id 
             WHERE s.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Get submission by token
     */
    public static function getByToken(string $token): ?array {
        return Database::fetch(
            "SELECT s.*, c.name as category_name 
             FROM submissions s 
             LEFT JOIN categories c ON s.category_id = c.id 
             WHERE s.token = :token",
            ['token' => $token]
        );
    }
    
    /**
     * Get all submissions with filters
     */
    public static function getAll(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT s.*, c.name as category_name 
                FROM submissions s 
                LEFT JOIN categories c ON s.category_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND s.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.title LIKE :search OR s.author_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Count submissions
     */
    public static function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM submissions WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        
        return (int)Database::fetchValue($sql, $params);
    }
    
    /**
     * Update submission status
     */
    public static function updateStatus(int $id, string $status, ?int $reviewerId = null, ?string $feedback = null): bool {
        $sql = "UPDATE submissions SET 
                    status = :status, 
                    reviewer_id = :reviewer_id,
                    updated_at = CURRENT_TIMESTAMP";
        $params = [
            'id' => $id,
            'status' => $status,
            'reviewer_id' => $reviewerId
        ];
        
        if ($feedback !== null) {
            $sql .= ", reviewer_feedback = :feedback";
            $params['feedback'] = $feedback;
        }
        
        if ($status === self::STATUS_REVIEWING || $status === self::STATUS_APPROVED || $status === self::STATUS_REJECTED) {
            $sql .= ", reviewed_at = CURRENT_TIMESTAMP";
        }
        
        $sql .= " WHERE id = :id";
        
        return Database::execute($sql, $params) > 0;
    }
    
    /**
     * Update submission
     */
    public static function update(int $id, array $data): bool {
        $allowedFields = ['title', 'excerpt', 'content', 'category_id', 'featured_image'];
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE submissions SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        return Database::execute($sql, $params) > 0;
    }
    
    /**
     * Delete submission
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM submissions WHERE id = :id", ['id' => $id]) > 0;
    }
    
    /**
     * Convert submission to post
     */
    public static function publish(int $submissionId, int $authorId): ?int {
        $submission = self::getById($submissionId);
        if (!$submission) {
            return null;
        }
        
        require_once(__DIR__ . '/Post.php');
        
        // Create post data
        $postData = [
            'title' => $submission['title'],
            'excerpt' => $submission['excerpt'],
            'content' => $submission['content'],
            'category_id' => $submission['category_id'],
            'author_id' => $authorId,
            'featured_image' => $submission['featured_image'],
            'status' => 'published',
            'meta' => json_encode([
                'contributor_name' => $submission['author_name'],
                'contributor_email' => $submission['author_email'],
                'submission_id' => $submissionId
            ])
        ];
        
        // Create the post
        $postId = Post::create($postData);
        
        if ($postId) {
            // Update submission status
            self::updateStatus($submissionId, self::STATUS_PUBLISHED);
            
            // Send notification to contributor
            self::notifyContributor($submission, 'published');
        }
        
        return $postId;
    }
    
    /**
     * Send email notification to contributor
     */
    public static function notifyContributor(array $submission, string $action): void {
        $to = $submission['author_email'];
        $authorName = $submission['author_name'];
        $title = $submission['title'];
        
        switch ($action) {
            case 'received':
                $subject = "Am primit articolul tău - MatchDay.ro";
                $message = "Salut {$authorName},\n\n";
                $message .= "Mulțumim că ai trimis articolul '{$title}' pentru publicare pe MatchDay.ro!\n\n";
                $message .= "Echipa noastră editorială va analiza conținutul și te vom contacta în curând.\n\n";
                $message .= "Poți urmări statusul articolului folosind acest link:\n";
                $message .= "https://matchday.ro/submission-status.php?token=" . $submission['token'] . "\n\n";
                $message .= "Echipa MatchDay.ro";
                break;
                
            case 'approved':
                $subject = "Articolul tău a fost aprobat! - MatchDay.ro";
                $message = "Salut {$authorName},\n\n";
                $message .= "Vești bune! Articolul '{$title}' a fost aprobat de echipa editorială.\n\n";
                $message .= "Va fi publicat în curând pe site.\n\n";
                $message .= "Echipa MatchDay.ro";
                break;
                
            case 'rejected':
                $subject = "Feedback pe articolul tău - MatchDay.ro";
                $message = "Salut {$authorName},\n\n";
                $message .= "Am analizat articolul '{$title}' și, din păcate, nu îl putem publica în forma actuală.\n\n";
                if (!empty($submission['reviewer_feedback'])) {
                    $message .= "Feedback de la editor:\n{$submission['reviewer_feedback']}\n\n";
                }
                $message .= "Te încurajăm să încerci din nou cu un alt articol sau să îmbunătățești conținutul!\n\n";
                $message .= "Echipa MatchDay.ro";
                break;
                
            case 'published':
                $subject = "Articolul tău este LIVE! - MatchDay.ro";
                $message = "Salut {$authorName},\n\n";
                $message .= "Articolul '{$title}' a fost publicat pe MatchDay.ro!\n\n";
                $message .= "Mulțumim pentru contribuție și te așteptăm cu alte articole.\n\n";
                $message .= "Echipa MatchDay.ro";
                break;
                
            default:
                return;
        }
        
        $headers = [
            'From: MatchDay.ro <noreply@matchday.ro>',
            'Reply-To: redactie@matchday.ro',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        @mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Get contributor stats
     */
    public static function getContributorStats(string $email): array {
        return [
            'total' => (int)Database::fetchValue(
                "SELECT COUNT(*) FROM submissions WHERE author_email = :email",
                ['email' => $email]
            ),
            'published' => (int)Database::fetchValue(
                "SELECT COUNT(*) FROM submissions WHERE author_email = :email AND status = 'published'",
                ['email' => $email]
            ),
            'pending' => (int)Database::fetchValue(
                "SELECT COUNT(*) FROM submissions WHERE author_email = :email AND status IN ('pending', 'reviewing')",
                ['email' => $email]
            )
        ];
    }
    
    /**
     * Check for spam/duplicate submissions
     */
    public static function checkRateLimit(string $email, string $ip): bool {
        // Max 3 submissions per day per email
        $emailCount = (int)Database::fetchValue(
            "SELECT COUNT(*) FROM submissions 
             WHERE author_email = :email 
             AND created_at > datetime('now', '-1 day')",
            ['email' => $email]
        );
        
        if ($emailCount >= 3) {
            return false;
        }
        
        // Max 5 submissions per day per IP
        $ipCount = (int)Database::fetchValue(
            "SELECT COUNT(*) FROM submissions 
             WHERE ip_address = :ip 
             AND created_at > datetime('now', '-1 day')",
            ['ip' => $ip]
        );
        
        if ($ipCount >= 5) {
            return false;
        }
        
        return true;
    }
}
