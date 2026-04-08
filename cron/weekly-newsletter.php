<?php
/**
 * Weekly Newsletter Cron Job
 * MatchDay.ro - Sends weekly digest to subscribers
 * 
 * Run with: php cron/weekly-newsletter.php
 * Cron: 0 8 * * 1 (Every Monday at 8:00 AM)
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_MODE')) {
    http_response_code(403);
    exit('Access denied');
}

define('CRON_MODE', true);

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Newsletter.php');

echo "[" . date('Y-m-d H:i:s') . "] Starting weekly newsletter...\n";

try {
    // Get active subscribers
    $db = Database::getInstance();
    $stmt = $db->query("SELECT email, name, token FROM newsletter_subscribers WHERE status = 'active'");
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscribers)) {
        echo "No active subscribers found.\n";
        exit(0);
    }
    
    echo "Found " . count($subscribers) . " active subscribers.\n";
    
    // Get top articles from last 7 days
    $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $db->prepare("
        SELECT title, slug, excerpt, cover_image, views, published_at, category_slug
        FROM posts 
        WHERE status = 'published' AND published_at >= :week_ago
        ORDER BY views DESC
        LIMIT 5
    ");
    $stmt->execute(['week_ago' => $weekAgo]);
    $topArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total stats
    $stmt = $db->prepare("SELECT SUM(views) as total_views FROM posts WHERE published_at >= :week_ago");
    $stmt->execute(['week_ago' => $weekAgo]);
    $weekStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get newest articles
    $stmt = $db->prepare("
        SELECT title, slug, excerpt, cover_image, published_at, category_slug
        FROM posts 
        WHERE status = 'published' AND published_at >= :week_ago
        ORDER BY published_at DESC
        LIMIT 3
    ");
    $stmt->execute(['week_ago' => $weekAgo]);
    $newArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($topArticles) && empty($newArticles)) {
        echo "No articles from last week. Skipping newsletter.\n";
        exit(0);
    }
    
    // Load categories for display
    $categories = require(__DIR__ . '/../config/categories.php');
    
    // Build email content
    $sentCount = 0;
    $errorCount = 0;
    
    foreach ($subscribers as $subscriber) {
        $html = generateNewsletterHTML($subscriber, $topArticles, $newArticles, $weekStats, $categories);
        
        $subject = "📰 Săptămâna în sport | " . SITE_NAME . " - " . date('d.m.Y');
        
        $success = sendNewsletterEmail($subscriber['email'], $subject, $html);
        
        if ($success) {
            $sentCount++;
            echo "✓ Sent to: {$subscriber['email']}\n";
        } else {
            $errorCount++;
            echo "✗ Failed to send to: {$subscriber['email']}\n";
        }
        
        // Small delay to avoid rate limits
        usleep(100000); // 0.1 seconds
    }
    
    // Log results
    $stmt = $db->prepare("
        INSERT INTO newsletter_logs (sent_count, error_count, sent_at) 
        VALUES (:sent, :errors, NOW())
    ");
    $stmt->execute(['sent' => $sentCount, 'errors' => $errorCount]);
    
    echo "[" . date('Y-m-d H:i:s') . "] Newsletter complete. Sent: $sentCount, Errors: $errorCount\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Weekly newsletter error: " . $e->getMessage());
    exit(1);
}

/**
 * Generate newsletter HTML
 */
function generateNewsletterHTML($subscriber, $topArticles, $newArticles, $weekStats, $categories) {
    $baseUrl = defined('SITE_URL') ? SITE_URL : 'https://matchday.ro';
    $unsubscribeUrl = $baseUrl . '/newsletter.php?action=unsubscribe&token=' . $subscriber['token'];
    $name = $subscriber['name'] ?: 'Dragă Cititor';
    
    $html = '
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter MatchDay.ro</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a5f3c 0%, #0d4a2d 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #fff; margin: 0; font-size: 28px;">⚽ ' . SITE_NAME . '</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 14px;">Newsletter Săptămânal</p>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 30px 30px 20px;">
                            <h2 style="margin: 0 0 15px; color: #1a202c; font-size: 20px;">Salut, ' . htmlspecialchars($name) . '! 👋</h2>
                            <p style="color: #4a5568; line-height: 1.6; margin: 0;">
                                Iată ce s-a întâmplat săptămâna aceasta în lumea fotbalului. Am selectat cele mai importante știri pentru tine.
                            </p>
                        </td>
                    </tr>';
    
    // Week stats
    if ($weekStats && $weekStats['total_views']) {
        $html .= '
                    <tr>
                        <td style="padding: 0 30px 20px;">
                            <div style="background: #f7fafc; border-radius: 8px; padding: 15px; text-align: center;">
                                <span style="font-size: 24px; font-weight: bold; color: #1a5f3c;">' . number_format($weekStats['total_views']) . '</span>
                                <span style="color: #718096; display: block; font-size: 12px;">vizualizări săptămâna aceasta</span>
                            </div>
                        </td>
                    </tr>';
    }
    
    // Top Articles
    if (!empty($topArticles)) {
        $html .= '
                    <tr>
                        <td style="padding: 20px 30px;">
                            <h3 style="margin: 0 0 20px; color: #1a202c; font-size: 18px; border-bottom: 2px solid #1a5f3c; padding-bottom: 10px;">
                                🏆 Cele mai citite
                            </h3>';
        
        foreach ($topArticles as $article) {
            $articleUrl = $baseUrl . '/articol/' . $article['slug'];
            $catName = isset($categories[$article['category_slug']]) ? $categories[$article['category_slug']]['name'] : '';
            
            $html .= '
                            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                                <a href="' . $articleUrl . '" style="text-decoration: none;">
                                    <h4 style="margin: 0 0 8px; color: #1a202c; font-size: 16px; line-height: 1.4;">' . htmlspecialchars($article['title']) . '</h4>
                                </a>
                                <p style="color: #718096; font-size: 13px; margin: 0 0 8px; line-height: 1.5;">' . htmlspecialchars(mb_substr(strip_tags($article['excerpt']), 0, 120)) . '...</p>
                                <div style="font-size: 12px; color: #a0aec0;">
                                    ' . ($catName ? '<span style="background: #edf2f7; padding: 2px 8px; border-radius: 4px; margin-right: 10px;">' . $catName . '</span>' : '') . '
                                    <span>👁️ ' . number_format($article['views']) . ' citiri</span>
                                </div>
                            </div>';
        }
        
        $html .= '
                        </td>
                    </tr>';
    }
    
    // New Articles
    if (!empty($newArticles)) {
        $html .= '
                    <tr>
                        <td style="padding: 20px 30px;">
                            <h3 style="margin: 0 0 20px; color: #1a202c; font-size: 18px; border-bottom: 2px solid #FFD700; padding-bottom: 10px;">
                                ✨ Cele mai noi
                            </h3>';
        
        foreach ($newArticles as $article) {
            $articleUrl = $baseUrl . '/articol/' . $article['slug'];
            
            $html .= '
                            <div style="margin-bottom: 15px;">
                                <a href="' . $articleUrl . '" style="color: #1a5f3c; text-decoration: none; font-weight: 500;">
                                    → ' . htmlspecialchars($article['title']) . '
                                </a>
                                <span style="color: #a0aec0; font-size: 12px; margin-left: 10px;">' . date('d.m', strtotime($article['published_at'])) . '</span>
                            </div>';
        }
        
        $html .= '
                        </td>
                    </tr>';
    }
    
    // CTA
    $html .= '
                    <tr>
                        <td style="padding: 20px 30px 30px; text-align: center;">
                            <a href="' . $baseUrl . '" style="display: inline-block; background: linear-gradient(135deg, #1a5f3c 0%, #0d4a2d 100%); color: #fff; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                                Vezi toate articolele →
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #2d3748; padding: 25px 30px; text-align: center;">
                            <p style="color: #a0aec0; font-size: 12px; margin: 0 0 10px;">
                                Primești acest email pentru că te-ai abonat la newsletter-ul ' . SITE_NAME . '.
                            </p>
                            <a href="' . $unsubscribeUrl . '" style="color: #e53e3e; font-size: 12px; text-decoration: underline;">
                                Dezabonare
                            </a>
                            <p style="color: #718096; font-size: 11px; margin: 15px 0 0;">
                                © ' . date('Y') . ' ' . SITE_NAME . ' - Toate drepturile rezervate
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Send newsletter email
 */
function sendNewsletterEmail($to, $subject, $htmlBody) {
    // Try to load email configuration
    $emailConfigFile = __DIR__ . '/../config/email_secret.php';
    
    if (file_exists($emailConfigFile)) {
        $emailConfig = require($emailConfigFile);
        
        // Use SMTP if configured
        if (!empty($emailConfig['smtp_host'])) {
            return sendViaSMTP($to, $subject, $htmlBody, $emailConfig);
        }
    }
    
    // Fallback to mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <newsletter@matchday.ro>',
        'Reply-To: contact@matchday.ro',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}

/**
 * Send via SMTP (simplified)
 */
function sendViaSMTP($to, $subject, $body, $config) {
    // For production, use PHPMailer or similar library
    // This is a placeholder for SMTP implementation
    
    try {
        // Check if PHPMailer is available
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
            $mail->Port = $config['smtp_port'] ?? 587;
            
            $mail->setFrom($config['from_email'] ?? 'newsletter@matchday.ro', SITE_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        }
        
        // Fallback to mail()
        return sendNewsletterEmail($to, $subject, $body);
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
