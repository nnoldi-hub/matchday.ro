<?php
/**
 * Newsletter Management for MatchDay.ro
 * Handles subscriber management and email dispatch
 */

require_once(__DIR__ . '/../config/database.php');

class Newsletter {
    private static $pdo = null;
    
    private static function getPDO() {
        if (self::$pdo === null) {
            self::$pdo = Database::getInstance();
        }
        return self::$pdo;
    }
    
    /**
     * Subscribe an email to newsletter
     */
    public static function subscribe(string $email, string $name = ''): array {
        $email = strtolower(trim($email));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresa de email nu este validă.'];
        }
        
        $pdo = self::getPDO();
        
        // Check if already subscribed
        $stmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                return ['success' => false, 'message' => 'Acest email este deja abonat.'];
            } else {
                // Reactivate subscription
                $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$existing['id']]);
                return ['success' => true, 'message' => 'Abonamentul a fost reactivat!'];
            }
        }
        
        // Generate confirmation token
        $token = bin2hex(random_bytes(32));
        
        // Insert new subscriber
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_subscribers (email, name, token, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$email, $name, $token]);
        
        // Send confirmation email
        self::sendConfirmationEmail($email, $name, $token);
        
        return [
            'success' => true, 
            'message' => 'Te-ai abonat cu succes! Verifică-ți email-ul pentru confirmare.'
        ];
    }
    
    /**
     * Confirm subscription via token
     */
    public static function confirm(string $token): array {
        $pdo = self::getPDO();
        
        $stmt = $pdo->prepare("SELECT id, email FROM newsletter_subscribers WHERE token = ? AND status = 'pending'");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subscriber) {
            return ['success' => false, 'message' => 'Link invalid sau abonament deja confirmat.'];
        }
        
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', confirmed_at = NOW() WHERE id = ?");
        $stmt->execute([$subscriber['id']]);
        
        return ['success' => true, 'message' => 'Abonamentul a fost confirmat! Vei primi ultimele știri pe email.'];
    }
    
    /**
     * Unsubscribe via token
     */
    public static function unsubscribe(string $token): array {
        $pdo = self::getPDO();
        
        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE token = ?");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subscriber) {
            return ['success' => false, 'message' => 'Link invalid.'];
        }
        
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$subscriber['id']]);
        
        return ['success' => true, 'message' => 'Te-ai dezabonat cu succes.'];
    }
    
    /**
     * Get all subscribers
     */
    public static function getAll(string $status = null, int $page = 1, int $perPage = 50): array {
        $pdo = self::getPDO();
        $offset = ($page - 1) * $perPage;
        
        $where = '';
        $params = [];
        
        if ($status) {
            $where = 'WHERE status = ?';
            $params[] = $status;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subscriber counts
     */
    public static function getStats(): array {
        $pdo = self::getPDO();
        
        $stats = [
            'total' => 0,
            'active' => 0,
            'pending' => 0,
            'unsubscribed' => 0
        ];
        
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM newsletter_subscribers GROUP BY status");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Delete subscriber
     */
    public static function delete(int $id): bool {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Send newsletter to all active subscribers
     */
    public static function send(string $subject, string $content, int $postId = null): array {
        $pdo = self::getPDO();
        
        // Get active subscribers
        $stmt = $pdo->query("SELECT id, email, name, token FROM newsletter_subscribers WHERE status = 'active'");
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscribers)) {
            return ['success' => false, 'message' => 'Nu există abonați activi.', 'sent' => 0];
        }
        
        $sent = 0;
        $failed = 0;
        
        foreach ($subscribers as $subscriber) {
            $personalizedContent = self::personalizeContent($content, $subscriber);
            
            if (self::sendEmail($subscriber['email'], $subject, $personalizedContent)) {
                $sent++;
            } else {
                $failed++;
            }
        }
        
        // Log the send
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_logs (subject, post_id, sent_count, failed_count, sent_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$subject, $postId, $sent, $failed]);
        
        return [
            'success' => true,
            'message' => "Newsletter trimis: {$sent} succes, {$failed} eșuate.",
            'sent' => $sent,
            'failed' => $failed
        ];
    }
    
    /**
     * Send newsletter for a specific post
     */
    public static function sendForPost(array $post): array {
        $subject = "📰 {$post['title']} - MatchDay.ro";
        
        $postUrl = BASE_URL . '/post.php?slug=' . $post['slug'];
        $imageUrl = !empty($post['featured_image']) ? BASE_URL . $post['featured_image'] : '';
        
        $content = self::getPostTemplate($post, $postUrl, $imageUrl);
        
        return self::send($subject, $content, $post['id']);
    }
    
    /**
     * Get email template for post
     */
    private static function getPostTemplate(array $post, string $postUrl, string $imageUrl): string {
        $excerpt = strip_tags($post['excerpt'] ?? substr($post['content'], 0, 200) . '...');
        
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #e94560; margin: 0;">⚽ MatchDay.ro</h1>
                <p style="color: #666; margin: 5px 0;">Știri sportive din România</p>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 10px; overflow: hidden;">
                ' . ($imageUrl ? '<img src="' . $imageUrl . '" style="width: 100%; height: 200px; object-fit: cover;" alt="">' : '') . '
                
                <div style="padding: 20px;">
                    <h2 style="color: #1a1a2e; margin: 0 0 15px 0;">' . htmlspecialchars($post['title']) . '</h2>
                    <p style="color: #666; line-height: 1.6;">' . htmlspecialchars($excerpt) . '</p>
                    
                    <a href="' . $postUrl . '" style="display: inline-block; background: #e94560; color: white; text-decoration: none; padding: 12px 25px; border-radius: 5px; margin-top: 15px;">
                        Citește articolul →
                    </a>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 12px;">
                    Primești acest email pentru că te-ai abonat la newsletter-ul MatchDay.ro<br>
                    <a href="{{unsubscribe_url}}" style="color: #e94560;">Dezabonare</a>
                </p>
            </div>
        </div>';
    }
    
    /**
     * Personalize content for subscriber
     */
    private static function personalizeContent(string $content, array $subscriber): string {
        $unsubscribeUrl = BASE_URL . '/newsletter.php?action=unsubscribe&token=' . $subscriber['token'];
        
        $content = str_replace('{{name}}', $subscriber['name'] ?: 'Abonat', $content);
        $content = str_replace('{{email}}', $subscriber['email'], $content);
        $content = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $content);
        
        return $content;
    }
    
    /**
     * Send confirmation email
     */
    private static function sendConfirmationEmail(string $email, string $name, string $token): bool {
        $confirmUrl = BASE_URL . '/newsletter.php?action=confirm&token=' . $token;
        
        $subject = 'Confirmă abonarea la MatchDay.ro';
        
        $content = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #e94560;">⚽ MatchDay.ro</h1>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 10px; padding: 30px; text-align: center;">
                <h2 style="color: #1a1a2e;">Bine ai venit, ' . htmlspecialchars($name ?: 'fan') . '!</h2>
                <p style="color: #666; line-height: 1.6;">
                    Mulțumim că te-ai abonat la newsletter-ul nostru.<br>
                    Te rugăm să confirmi abonarea făcând click pe butonul de mai jos.
                </p>
                
                <a href="' . $confirmUrl . '" style="display: inline-block; background: #e94560; color: white; text-decoration: none; padding: 15px 30px; border-radius: 5px; margin-top: 15px; font-weight: bold;">
                    Confirmă abonarea
                </a>
                
                <p style="color: #999; font-size: 12px; margin-top: 20px;">
                    Dacă nu ai solicitat acest email, te rugăm să-l ignori.
                </p>
            </div>
        </div>';
        
        return self::sendEmail($email, $subject, $content);
    }
    
    /**
     * Send email via SMTP or PHP mail()
     */
    private static function sendEmail(string $to, string $subject, string $htmlContent): bool {
        // Try SMTP first if configured
        if (defined('SMTP_ENABLED') && SMTP_ENABLED && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '') {
            return self::sendViaSMTP($to, $subject, $htmlContent);
        }
        
        // Fallback to PHP mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MatchDay.ro') . ' <' . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'newsletter@matchday.ro') . '>',
            'Reply-To: ' . (defined('SMTP_REPLY_TO') ? SMTP_REPLY_TO : 'contact@matchday.ro'),
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return @mail($to, $subject, $htmlContent, implode("\r\n", $headers));
    }
    
    /**
     * Send email using SMTP with authentication
     */
    private static function sendViaSMTP(string $to, string $subject, string $htmlContent): bool {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $secure = SMTP_SECURE ?? 'ssl';
        $username = SMTP_USERNAME;
        $password = SMTP_PASSWORD;
        $fromEmail = SMTP_FROM_EMAIL;
        $fromName = SMTP_FROM_NAME ?? 'MatchDay.ro';
        $replyTo = SMTP_REPLY_TO ?? $fromEmail;
        
        try {
            // Connect with SSL/TLS
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $prefix = ($secure === 'ssl') ? 'ssl://' : '';
            $socket = @stream_socket_client(
                $prefix . $host . ':' . $port,
                $errno, $errstr, 30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return false;
            }
            
            // Read greeting
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return false;
            }
            
            // EHLO
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = self::readSMTPResponse($socket);
            
            // AUTH LOGIN
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 515);
            
            // Username (base64)
            fwrite($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket, 515);
            
            // Password (base64)
            fwrite($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket, 515);
            
            if (substr($response, 0, 3) !== '235') {
                error_log("SMTP Auth failed: $response");
                fclose($socket);
                return false;
            }
            
            // MAIL FROM
            fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
            $response = fgets($socket, 515);
            
            // RCPT TO
            fwrite($socket, "RCPT TO:<$to>\r\n");
            $response = fgets($socket, 515);
            
            // DATA
            fwrite($socket, "DATA\r\n");
            $response = fgets($socket, 515);
            
            // Headers and body
            $boundary = md5(time());
            $message = "From: $fromName <$fromEmail>\r\n";
            $message .= "To: $to\r\n";
            $message .= "Reply-To: $replyTo\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "\r\n";
            $message .= chunk_split(base64_encode($htmlContent));
            $message .= "\r\n.\r\n";
            
            fwrite($socket, $message);
            $response = fgets($socket, 515);
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return substr($response, 0, 3) === '250';
            
        } catch (Exception $e) {
            error_log("SMTP Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read multi-line SMTP response
     */
    private static function readSMTPResponse($socket): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }
    
    /**
     * Export subscribers to CSV
     */
    public static function exportCSV(): string {
        $subscribers = self::getAll('active', 1, 10000);
        
        $csv = "Email,Nume,Data abonarii\n";
        
        foreach ($subscribers as $sub) {
            $csv .= '"' . $sub['email'] . '","' . ($sub['name'] ?? '') . '","' . $sub['created_at'] . "\"\n";
        }
        
        return $csv;
    }
    
    /**
     * Create newsletter tables if not exist
     */
    public static function createTables(): void {
        $pdo = self::getPDO();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    name VARCHAR(100),
                    token VARCHAR(64) NOT NULL,
                    status ENUM('pending', 'active', 'unsubscribed') DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    confirmed_at DATETIME,
                    updated_at DATETIME,
                    INDEX idx_status (status),
                    INDEX idx_token (token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS newsletter_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    subject VARCHAR(255),
                    post_id INT,
                    sent_count INT DEFAULT 0,
                    failed_count INT DEFAULT 0,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            // SQLite
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT NOT NULL UNIQUE,
                    name TEXT,
                    token TEXT NOT NULL,
                    status TEXT DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    confirmed_at DATETIME,
                    updated_at DATETIME
                )
            ");
            
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS newsletter_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    subject TEXT,
                    post_id INTEGER,
                    sent_count INTEGER DEFAULT 0,
                    failed_count INTEGER DEFAULT 0,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    }
}
