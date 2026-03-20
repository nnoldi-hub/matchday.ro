<?php
/**
 * Newsletter Public Endpoint
 * Handles subscribe, confirm, unsubscribe actions
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/security.php');
require_once(__DIR__ . '/includes/Newsletter.php');

// Ensure tables exist
Newsletter::createTables();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$message = '';
$success = false;

switch ($action) {
    case 'subscribe':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Rate limiting
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateKey = 'newsletter_' . md5($ip);
            
            // Simple rate limit check (max 3 per hour)
            session_start();
            $attempts = $_SESSION[$rateKey] ?? ['count' => 0, 'time' => time()];
            
            if (time() - $attempts['time'] > 3600) {
                $attempts = ['count' => 0, 'time' => time()];
            }
            
            if ($attempts['count'] >= 3) {
                $message = 'Prea multe încercări. Încearcă din nou mai târziu.';
                break;
            }
            
            $attempts['count']++;
            $_SESSION[$rateKey] = $attempts;
            
            // Validate CSRF
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $message = 'Token de securitate invalid.';
                break;
            }
            
            $email = $_POST['email'] ?? '';
            $name = $_POST['name'] ?? '';
            
            $result = Newsletter::subscribe($email, $name);
            $success = $result['success'];
            $message = $result['message'];
            
            // If AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
        }
        break;
        
    case 'confirm':
        $token = $_GET['token'] ?? '';
        if ($token) {
            $result = Newsletter::confirm($token);
            $success = $result['success'];
            $message = $result['message'];
        } else {
            $message = 'Token lipsă.';
        }
        break;
        
    case 'unsubscribe':
        $token = $_GET['token'] ?? '';
        if ($token) {
            $result = Newsletter::unsubscribe($token);
            $success = $result['success'];
            $message = $result['message'];
        } else {
            $message = 'Token lipsă.';
        }
        break;
        
    default:
        // Show subscribe form
        break;
}

$pageTitle = 'Newsletter - ' . SITE_NAME;
require_once(__DIR__ . '/includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> text-center">
                <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?> fa-2x mb-3"></i>
                <h4><?php echo $success ? 'Succes!' : 'Eroare'; ?></h4>
                <p class="mb-0"><?php echo htmlspecialchars($message); ?></p>
                <?php if ($success): ?>
                <a href="/" class="btn btn-primary mt-3">
                    <i class="fas fa-home me-1"></i>Înapoi la site
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$action || ($action === 'subscribe' && !$success)): ?>
            <div class="card shadow">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <div class="display-4 mb-3">📰</div>
                        <h2>Abonează-te la Newsletter</h2>
                        <p class="text-muted">Primește cele mai noi știri sportive direct în inbox!</p>
                    </div>
                    
                    <form method="POST" action="/newsletter.php" id="newsletter-form">
                        <input type="hidden" name="action" value="subscribe">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nume (opțional)</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   placeholder="Numele tău">
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   placeholder="email@exemplu.com" required>
                        </div>
                        
                        <button type="submit" class="btn btn-accent btn-lg w-100">
                            <i class="fas fa-paper-plane me-2"></i>Abonează-mă
                        </button>
                        
                        <p class="text-muted small text-center mt-3 mb-0">
                            <i class="fas fa-lock me-1"></i>
                            Nu trimitem spam. Te poți dezabona oricând.
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <h5>Ce vei primi?</h5>
                <div class="row mt-3">
                    <div class="col-4">
                        <div class="p-3">
                            <i class="fas fa-futbol fa-2x text-primary mb-2"></i>
                            <p class="small mb-0">Știri Liga 1</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3">
                            <i class="fas fa-flag fa-2x text-warning mb-2"></i>
                            <p class="small mb-0">Echipa Națională</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3">
                            <i class="fas fa-exchange-alt fa-2x text-success mb-2"></i>
                            <p class="small mb-0">Transferuri</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
