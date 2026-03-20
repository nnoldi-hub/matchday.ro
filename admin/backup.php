<?php
/**
 * Admin Backup Management
 * Create, download, and manage database backups
 */

session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/security.php');
require_once(__DIR__ . '/../includes/Backup.php');

// Check admin authentication (consistent with dashboard.php)
if (empty($_SESSION['david_logged'])) {
    header('Location: login.php');
    exit;
}

$backup = new BackupManager();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de securitate invalid.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_json':
                try {
                    $result = $backup->saveBackup('json');
                    $message = "Backup JSON creat: {$result['filename']} ({$result['size']} bytes)";
                } catch (Exception $e) {
                    $error = "Eroare la creare backup: " . $e->getMessage();
                }
                break;
                
            case 'create_sql':
                try {
                    $result = $backup->saveBackup('sql');
                    $message = "Backup SQL creat: {$result['filename']} ({$result['size']} bytes)";
                } catch (Exception $e) {
                    $error = "Eroare la creare backup: " . $e->getMessage();
                }
                break;
                
            case 'create_full':
                try {
                    $result = $backup->exportFullBackup();
                    $message = "Backup complet creat: {$result['filename']} ({$result['size_formatted']})";
                } catch (Exception $e) {
                    $error = "Eroare la creare backup complet: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $filename = $_POST['filename'] ?? '';
                if ($filename && $backup->deleteBackup($filename)) {
                    $message = "Backup șters: {$filename}";
                } else {
                    $error = "Nu s-a putut șterge backup-ul.";
                }
                break;
                
            case 'restore':
                $filename = $_POST['filename'] ?? '';
                if ($filename && pathinfo($filename, PATHINFO_EXTENSION) === 'json') {
                    try {
                        $result = $backup->restoreFromJSON($filename);
                        if ($result['success']) {
                            $message = "Baza de date restaurată din: {$filename}. Tabele: " . implode(', ', $result['restored']);
                        } else {
                            $error = "Erori la restaurare: " . implode(', ', $result['errors']);
                        }
                    } catch (Exception $e) {
                        $error = "Eroare la restaurare: " . $e->getMessage();
                    }
                } else {
                    $error = "Restaurarea este disponibilă doar pentru backup-uri JSON.";
                }
                break;
        }
    }
}

// Handle download
if (isset($_GET['download'])) {
    try {
        $backup->downloadBackup($_GET['download']);
    } catch (Exception $e) {
        $error = "Eroare la descărcare: " . $e->getMessage();
    }
}

// Get data
$backups = $backup->getBackups();
$stats = $backup->getStats();
$csrfToken = Security::generateCSRFToken();

$pageTitle = 'Backup';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-database me-2"></i>Backup & Restore</h1>
    <span class="text-muted">Gestionează backup-urile bazei de date</span>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-archive"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['count']; ?></h3>
                <p>Total Backup-uri</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-hdd"></i></div>
            <div class="stat-content">
                <h3><?php echo $stats['total_size_formatted']; ?></h3>
                <p>Spațiu Folosit</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3 class="h5"><?php echo $stats['last_backup']; ?></h3>
                <p>Ultimul Backup</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-layer-group"></i></div>
            <div class="stat-content">
                <h3>10</h3>
                <p>Păstrate</p>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup -->
<div class="admin-card mb-4">
    <div class="admin-card-header bg-primary text-white">
        <h2 class="text-white mb-0"><i class="fas fa-plus-circle me-2"></i>Creează Backup Nou</h2>
    </div>
    <div class="p-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-file-code fa-3x text-primary mb-3"></i>
                        <h5>Backup JSON</h5>
                        <p class="text-muted small">Export baza de date în format JSON. Ideal pentru restaurare și portabilitate.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create_json">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i>Creează JSON
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-database fa-3x text-success mb-3"></i>
                        <h5>Backup SQL</h5>
                        <p class="text-muted small">Export SQL standard. Compatible cu MySQL/SQLite. Include structura și datele.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create_sql">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download me-1"></i>Creează SQL
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-file-archive fa-3x text-warning mb-3"></i>
                        <h5>Backup Complet</h5>
                        <p class="text-muted small">Arhivă ZIP cu baza de date, articole, imagini și configurări.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create_full">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-download me-1"></i>Backup Complet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup List -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-list me-2"></i>Backup-uri Existente</h2>
    </div>
    
    <?php if (empty($backups)): ?>
    <div class="text-center py-5 text-muted">
        <i class="fas fa-archive fa-3x mb-3"></i>
        <p>Nu există backup-uri. Creează primul backup!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fișier</th>
                    <th>Tip</th>
                    <th>Mărime</th>
                    <th>Data Creării</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td>
                        <i class="fas fa-<?php echo $b['type'] === 'json' ? 'file-code text-primary' : ($b['type'] === 'sql' ? 'database text-success' : 'file-archive text-warning'); ?> me-2"></i>
                        <?php echo htmlspecialchars($b['filename']); ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $b['type'] === 'json' ? 'primary' : ($b['type'] === 'sql' ? 'success' : 'warning'); ?>">
                            <?php echo strtoupper($b['type']); ?>
                        </span>
                    </td>
                    <td><?php echo $b['size_formatted']; ?></td>
                    <td><?php echo $b['created_at_formatted']; ?></td>
                    <td class="text-end">
                        <a href="?download=<?php echo urlencode($b['filename']); ?>" class="btn btn-sm btn-outline-primary" title="Descarcă">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php if ($b['type'] === 'json'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('ATENȚIE: Aceasta va suprascrie datele existente din baza de date. Continuați?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($b['filename']); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Restaurează">
                                <i class="fas fa-undo"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Sigur vrei să ștergi acest backup?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($b['filename']); ?>">
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

<!-- Help Section -->
<div class="admin-card mt-4">
    <div class="admin-card-header">
        <h2><i class="fas fa-info-circle me-2"></i>Informații Backup</h2>
    </div>
    <div class="p-4">
        <div class="row">
            <div class="col-md-4">
                <h6><i class="fas fa-file-code text-primary me-1"></i>Backup JSON</h6>
                <ul class="small text-muted">
                    <li>Format ușor de citit</li>
                    <li>Portabil între sisteme</li>
                    <li>Suportă restaurare automată</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-database text-success me-1"></i>Backup SQL</h6>
                <ul class="small text-muted">
                    <li>Standard industrie</li>
                    <li>Import în phpMyAdmin</li>
                    <li>Include structura tabelelor</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-file-archive text-warning me-1"></i>Backup Complet</h6>
                <ul class="small text-muted">
                    <li>Include baza de date + fișiere</li>
                    <li>Articole și imagini</li>
                    <li>Ideal pentru migrare</li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="small text-muted mb-0">
            <i class="fas fa-lightbulb me-1"></i>
            <strong>Sfat:</strong> Creează backup-uri regulat înainte de orice modificare majoră. Sistemul păstrează automat ultimele 10 backup-uri.
        </p>
    </div>
</div>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
