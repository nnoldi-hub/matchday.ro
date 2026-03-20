<?php
/**
 * Admin Settings Page
 * MatchDay.ro - Site configuration
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Settings.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Only admins can access settings
$currentUserRole = $_SESSION['user_role'] ?? 'admin';
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'general';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        $error = 'Token de securitate invalid.';
    } else {
        $tab = $_POST['tab'] ?? 'general';
        
        try {
            switch ($tab) {
                case 'general':
                    Settings::saveMultiple([
                        'site_name' => Security::sanitizeInput($_POST['site_name'] ?? ''),
                        'site_description' => Security::sanitizeInput($_POST['site_description'] ?? ''),
                        'site_keywords' => Security::sanitizeInput($_POST['site_keywords'] ?? ''),
                        'contact_email' => filter_var($_POST['contact_email'] ?? '', FILTER_SANITIZE_EMAIL),
                        'footer_text' => Security::sanitizeInput($_POST['footer_text'] ?? ''),
                    ]);
                    $message = 'Setări generale salvate!';
                    break;
                    
                case 'content':
                    Settings::saveMultiple([
                        'posts_per_page' => max(1, min(50, (int) ($_POST['posts_per_page'] ?? 10))),
                        'comments_enabled' => isset($_POST['comments_enabled']) ? '1' : '0',
                        'comments_moderation' => isset($_POST['comments_moderation']) ? '1' : '0',
                        'polls_enabled' => isset($_POST['polls_enabled']) ? '1' : '0',
                    ]);
                    $message = 'Setări conținut salvate!';
                    break;
                    
                case 'social':
                    Settings::saveMultiple([
                        'social_facebook' => filter_var($_POST['social_facebook'] ?? '', FILTER_SANITIZE_URL),
                        'social_twitter' => filter_var($_POST['social_twitter'] ?? '', FILTER_SANITIZE_URL),
                        'social_instagram' => filter_var($_POST['social_instagram'] ?? '', FILTER_SANITIZE_URL),
                        'social_youtube' => filter_var($_POST['social_youtube'] ?? '', FILTER_SANITIZE_URL),
                    ]);
                    $message = 'Linkuri sociale salvate!';
                    break;
                    
                case 'advanced':
                    Settings::saveMultiple([
                        'analytics_code' => $_POST['analytics_code'] ?? '',
                        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                        'maintenance_message' => Security::sanitizeInput($_POST['maintenance_message'] ?? ''),
                    ]);
                    $message = 'Setări avansate salvate!';
                    break;
            }
            
            $activeTab = $tab;
            
        } catch (Exception $e) {
            $error = 'Eroare la salvarea setărilor: ' . $e->getMessage();
        }
    }
}

// Get current settings
$settings = Settings::getAll();

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-cog me-2"></i>Setări Site</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Înapoi
        </a>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i><?= Security::sanitizeInput($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-1"></i><?= Security::sanitizeInput($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="?tab=general">
                <i class="fas fa-home me-1"></i>General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'content' ? 'active' : '' ?>" href="?tab=content">
                <i class="fas fa-newspaper me-1"></i>Conținut
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'social' ? 'active' : '' ?>" href="?tab=social">
                <i class="fas fa-share-alt me-1"></i>Social Media
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'advanced' ? 'active' : '' ?>" href="?tab=advanced">
                <i class="fas fa-tools me-1"></i>Avansat
            </a>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="card">
        <div class="card-body">
            
            <?php if ($activeTab === 'general'): ?>
            <!-- General Settings -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="tab" value="general">
                
                <div class="mb-3">
                    <label class="form-label">Numele site-ului</label>
                    <input type="text" name="site_name" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['site_name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descriere site (SEO)</label>
                    <textarea name="site_description" class="form-control" rows="2"><?= Security::sanitizeInput($settings['site_description']) ?></textarea>
                    <div class="form-text">Descrierea va apărea în rezultatele Google</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Cuvinte cheie (SEO)</label>
                    <input type="text" name="site_keywords" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['site_keywords']) ?>">
                    <div class="form-text">Separate prin virgulă</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email contact</label>
                    <input type="email" name="contact_email" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['contact_email']) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Text footer</label>
                    <input type="text" name="footer_text" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['footer_text']) ?>">
                </div>
                
                <button type="submit" class="btn btn-accent">
                    <i class="fas fa-save me-1"></i>Salvează
                </button>
            </form>
            <?php endif; ?>
            
            <?php if ($activeTab === 'content'): ?>
            <!-- Content Settings -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="tab" value="content">
                
                <div class="mb-3">
                    <label class="form-label">Articole pe pagină</label>
                    <input type="number" name="posts_per_page" class="form-control" style="max-width: 100px;"
                           value="<?= (int) $settings['posts_per_page'] ?>" min="1" max="50">
                </div>
                
                <div class="mb-4">
                    <label class="form-label d-block">Comentarii</label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="comments_enabled" id="commentsEnabled"
                               <?= $settings['comments_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="commentsEnabled">
                            Permite comentarii la articole
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="comments_moderation" id="commentsModeration"
                               <?= $settings['comments_moderation'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="commentsModeration">
                            Moderare comentarii (necesită aprobare)
                        </label>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label d-block">Sondaje</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="polls_enabled" id="pollsEnabled"
                               <?= $settings['polls_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pollsEnabled">
                            Afișează sondaje pe site
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-accent">
                    <i class="fas fa-save me-1"></i>Salvează
                </button>
            </form>
            <?php endif; ?>
            
            <?php if ($activeTab === 'social'): ?>
            <!-- Social Media Settings -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="tab" value="social">
                
                <div class="mb-3">
                    <label class="form-label"><i class="fab fa-facebook text-primary me-2"></i>Facebook</label>
                    <input type="url" name="social_facebook" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['social_facebook']) ?>"
                           placeholder="https://facebook.com/pagina-ta">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fab fa-twitter text-info me-2"></i>Twitter / X</label>
                    <input type="url" name="social_twitter" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['social_twitter']) ?>"
                           placeholder="https://twitter.com/contul-tau">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fab fa-instagram text-danger me-2"></i>Instagram</label>
                    <input type="url" name="social_instagram" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['social_instagram']) ?>"
                           placeholder="https://instagram.com/contul-tau">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fab fa-youtube text-danger me-2"></i>YouTube</label>
                    <input type="url" name="social_youtube" class="form-control" 
                           value="<?= Security::sanitizeInput($settings['social_youtube']) ?>"
                           placeholder="https://youtube.com/canalul-tau">
                </div>
                
                <button type="submit" class="btn btn-accent">
                    <i class="fas fa-save me-1"></i>Salvează
                </button>
            </form>
            <?php endif; ?>
            
            <?php if ($activeTab === 'advanced'): ?>
            <!-- Advanced Settings -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="tab" value="advanced">
                
                <div class="mb-4">
                    <label class="form-label">Cod Google Analytics</label>
                    <textarea name="analytics_code" class="form-control font-monospace" rows="4" 
                              placeholder="<!-- Global site tag (gtag.js) -->"><?= htmlspecialchars($settings['analytics_code']) ?></textarea>
                    <div class="form-text">Pune codul complet de tracking aici</div>
                </div>
                
                <div class="card bg-warning bg-opacity-10 border-warning mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>Mod Mentenanță
                        </h6>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode"
                                   <?= $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="maintenanceMode">
                                <strong>Activează modul mentenanță</strong>
                            </label>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Mesaj afișat vizitatorilor</label>
                            <textarea name="maintenance_message" class="form-control" rows="2"><?= Security::sanitizeInput($settings['maintenance_message']) ?></textarea>
                        </div>
                        <div class="form-text text-warning">
                            <i class="fas fa-info-circle me-1"></i>
                            Când este activ, doar administratorii pot accesa site-ul.
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-accent">
                    <i class="fas fa-save me-1"></i>Salvează
                </button>
            </form>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- System Info -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i>Informații sistem
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted">PHP Version</small>
                    <div><?= phpversion() ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Bază de date</small>
                    <div><?= Database::isMySQL() ? 'MySQL' : 'SQLite' ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Server</small>
                    <div><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
