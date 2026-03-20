<?php
/**
 * Admin Newsletter Management
 * Manage subscribers and send newsletters
 */

session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/security.php');
require_once(__DIR__ . '/../includes/Newsletter.php');
require_once(__DIR__ . '/../includes/Post.php');

// Check admin authentication (consistent with dashboard.php)
if (empty($_SESSION['david_logged'])) {
    header('Location: login.php');
    exit;
}

// Ensure tables exist
Newsletter::createTables();

$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'subscribers';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de securitate invalid.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id && Newsletter::delete($id)) {
                    $message = 'Abonat șters cu succes.';
                } else {
                    $error = 'Eroare la ștergere.';
                }
                break;
                
            case 'send_post':
                $postId = (int)($_POST['post_id'] ?? 0);
                if ($postId) {
                    $post = Post::getById($postId);
                    if ($post) {
                        $result = Newsletter::sendForPost($post);
                        if ($result['success']) {
                            $message = $result['message'];
                        } else {
                            $error = $result['message'];
                        }
                    } else {
                        $error = 'Articolul nu a fost găsit.';
                    }
                }
                break;
                
            case 'send_custom':
                $subject = $_POST['subject'] ?? '';
                $content = $_POST['content'] ?? '';
                
                if ($subject && $content) {
                    // Wrap content in template
                    $htmlContent = '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <h1 style="color: #e94560;">⚽ MatchDay.ro</h1>
                        </div>
                        <div style="background: #f8f9fa; border-radius: 10px; padding: 30px;">
                            ' . nl2br(htmlspecialchars($content)) . '
                        </div>
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                            <p style="color: #999; font-size: 12px;">
                                <a href="{{unsubscribe_url}}" style="color: #e94560;">Dezabonare</a>
                            </p>
                        </div>
                    </div>';
                    
                    $result = Newsletter::send($subject, $htmlContent);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Completează subiectul și conținutul.';
                }
                break;
                
            case 'export':
                $csv = Newsletter::exportCSV();
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
                echo $csv;
                exit;
        }
    }
}

// Get data
$stats = Newsletter::getStats();
$filter = $_GET['status'] ?? '';
$subscribers = Newsletter::getAll($filter ?: null);
$recentPosts = Post::getLatest(10);
$csrfToken = Security::generateCSRFToken();

$pageTitle = 'Newsletter - ' . SITE_NAME;
require_once(__DIR__ . '/../includes/header.php');
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Newsletter</li>
                </ol>
            </nav>
            <h1 class="mb-0"><i class="fas fa-envelope me-2"></i>Newsletter</h1>
            <p class="text-muted">Gestionează abonații și trimite newsletter-uri</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Abonați</h6>
                            <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Activi</h6>
                            <h2 class="mb-0"><?php echo $stats['active']; ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">În așteptare</h6>
                            <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Dezabonați</h6>
                            <h2 class="mb-0"><?php echo $stats['unsubscribed']; ?></h2>
                        </div>
                        <i class="fas fa-user-minus fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'subscribers' ? 'active' : ''; ?>" href="?tab=subscribers">
                <i class="fas fa-users me-1"></i>Abonați
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'send' ? 'active' : ''; ?>" href="?tab=send">
                <i class="fas fa-paper-plane me-1"></i>Trimite Newsletter
            </a>
        </li>
    </ul>

    <?php if ($tab === 'subscribers'): ?>
    <!-- Subscribers List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista Abonați</h5>
            <div class="d-flex gap-2">
                <div class="btn-group">
                    <a href="?tab=subscribers" class="btn btn-sm <?php echo !$filter ? 'btn-primary' : 'btn-outline-primary'; ?>">Toți</a>
                    <a href="?tab=subscribers&status=active" class="btn btn-sm <?php echo $filter === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">Activi</a>
                    <a href="?tab=subscribers&status=pending" class="btn btn-sm <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">În așteptare</a>
                </div>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="export">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i>Export CSV
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($subscribers)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nu există abonați.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Email</th>
                            <th>Nume</th>
                            <th>Status</th>
                            <th>Data abonării</th>
                            <th>Confirmat</th>
                            <th class="text-end">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td>
                                <i class="fas fa-envelope text-muted me-1"></i>
                                <?php echo htmlspecialchars($sub['email']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($sub['name'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $statusClass = [
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'unsubscribed' => 'secondary'
                                ][$sub['status']] ?? 'secondary';
                                $statusText = [
                                    'active' => 'Activ',
                                    'pending' => 'În așteptare',
                                    'unsubscribed' => 'Dezabonat'
                                ][$sub['status']] ?? $sub['status'];
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($sub['created_at'])); ?></td>
                            <td>
                                <?php if ($sub['confirmed_at']): ?>
                                <span class="text-success"><i class="fas fa-check"></i> <?php echo date('d.m.Y', strtotime($sub['confirmed_at'])); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" class="d-inline" onsubmit="return confirm('Sigur ștergi acest abonat?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Șterge">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tab === 'send'): ?>
    <!-- Send Newsletter -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-newspaper me-2"></i>Trimite Articol</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Trimite un articol publicat către toți abonații activi.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="send_post">
                        
                        <div class="mb-3">
                            <label class="form-label">Selectează articol</label>
                            <select name="post_id" class="form-select" required>
                                <option value="">-- Alege un articol --</option>
                                <?php foreach ($recentPosts as $post): ?>
                                <option value="<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?> 
                                    (<?php echo date('d.m.Y', strtotime($post['created_at'])); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i>
                            Va fi trimis către <strong><?php echo $stats['active']; ?></strong> abonați activi.
                        </div>
                        
                        <button type="submit" class="btn btn-primary" 
                                onclick="return confirm('Sigur trimiți newsletter-ul către <?php echo $stats['active']; ?> abonați?');">
                            <i class="fas fa-paper-plane me-1"></i>Trimite Newsletter
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Newsletter Personalizat</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Compune și trimite un mesaj personalizat.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="send_custom">
                        
                        <div class="mb-3">
                            <label class="form-label">Subiect</label>
                            <input type="text" name="subject" class="form-control" 
                                   placeholder="Ex: Știri importante de la MatchDay.ro" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Conținut</label>
                            <textarea name="content" class="form-control" rows="6" 
                                      placeholder="Scrie mesajul aici..." required></textarea>
                            <small class="text-muted">Textul va fi formatat automat în template-ul newsletter-ului.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Sigur trimiți newsletter-ul către <?php echo $stats['active']; ?> abonați?');">
                            <i class="fas fa-paper-plane me-1"></i>Trimite
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Widget Code -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-code me-2"></i>Widget Abonare</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Adaugă acest cod în site pentru a afișa formularul de abonare:</p>
            <pre class="bg-dark text-light p-3 rounded"><code>&lt;!-- Newsletter Widget --&gt;
&lt;div id="newsletter-widget"&gt;&lt;/div&gt;
&lt;script src="/assets/js/newsletter-widget.js"&gt;&lt;/script&gt;</code></pre>
            <p class="small text-muted mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Formularul este deja integrat în footer-ul site-ului.
            </p>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>
