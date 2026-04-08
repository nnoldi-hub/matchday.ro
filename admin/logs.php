<?php
/**
 * Admin Logs Viewer
 * MatchDay.ro - View and search application logs
 */

$pageTitle = 'Logs - Admin';
require_once(__DIR__ . '/../includes/Logger.php');
require_once(__DIR__ . '/admin-header.php');

// Only admins can view logs
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Get parameters
$channel = $_GET['channel'] ?? Logger::CHANNEL_ERROR;
$date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$lines = (int)($_GET['lines'] ?? 100);

// Available channels
$channels = [
    Logger::CHANNEL_ERROR => ['name' => 'Erori', 'icon' => 'fa-bug', 'color' => 'danger'],
    Logger::CHANNEL_SECURITY => ['name' => 'Securitate', 'icon' => 'fa-shield-alt', 'color' => 'warning'],
    Logger::CHANNEL_API => ['name' => 'API', 'icon' => 'fa-plug', 'color' => 'info'],
    Logger::CHANNEL_AUDIT => ['name' => 'Audit', 'icon' => 'fa-clipboard-list', 'color' => 'primary'],
    Logger::CHANNEL_PERFORMANCE => ['name' => 'Performanță', 'icon' => 'fa-tachometer-alt', 'color' => 'success'],
    Logger::CHANNEL_APP => ['name' => 'Aplicație', 'icon' => 'fa-cog', 'color' => 'secondary'],
];

// Get logs
if ($search) {
    $entries = Logger::search($search, $channel, $date, $date, $lines);
    $entries = array_column($entries, 'entry');
} else {
    $entries = Logger::getRecent($channel, $lines, $date);
}

// Get stats
$stats = Logger::getStats($date);

// Handle cleanup action
$cleanupMessage = '';
if (isset($_POST['cleanup']) && isset($_POST['csrf_token'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'])) {
        $days = (int)($_POST['keep_days'] ?? 30);
        $deleted = Logger::cleanup($days);
        $cleanupMessage = "Șterse $deleted fișiere log mai vechi de $days zile.";
        Logger::audit('LOGS_CLEANUP', $_SESSION['user_id'], ['deleted_files' => $deleted, 'keep_days' => $days]);
    }
}
?>

<!-- Page Header -->
<div class="admin-page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-file-alt me-2"></i>Logs</h1>
        <p class="text-muted mb-0">Vizualizare și căutare în loguri</p>
    </div>
    <div>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupModal">
            <i class="fas fa-trash-alt me-1"></i>Curățare
        </button>
    </div>
</div>

<?php if ($cleanupMessage): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($cleanupMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($channels as $ch => $info): ?>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="?channel=<?= $ch ?>&date=<?= $date ?>" class="text-decoration-none">
            <div class="card h-100 <?= $channel === $ch ? 'border-' . $info['color'] : '' ?>">
                <div class="card-body text-center py-3">
                    <i class="fas <?= $info['icon'] ?> text-<?= $info['color'] ?> mb-2" style="font-size: 1.5rem;"></i>
                    <div class="small text-muted"><?= $info['name'] ?></div>
                    <div class="fw-bold">
                        <?= $stats['channels'][$ch]['entries'] ?? 0 ?>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="channel" value="<?= htmlspecialchars($channel) ?>">
            
            <div class="col-md-3">
                <label class="form-label">Data</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Căutare</label>
                <input type="text" name="search" class="form-control" 
                       value="<?= htmlspecialchars($search) ?>" placeholder="Caută în loguri...">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Linii</label>
                <select name="lines" class="form-select">
                    <option value="50" <?= $lines === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $lines === 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $lines === 200 ? 'selected' : '' ?>>200</option>
                    <option value="500" <?= $lines === 500 ? 'selected' : '' ?>>500</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filtrează
                </button>
                <a href="?channel=<?= $channel ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Log Entries -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <h2>
            <i class="fas <?= $channels[$channel]['icon'] ?> text-<?= $channels[$channel]['color'] ?> me-2"></i>
            <?= $channels[$channel]['name'] ?> - <?= $date ?>
        </h2>
        <span class="badge bg-<?= $channels[$channel]['color'] ?>"><?= count($entries) ?> intrări</span>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p>Nu sunt loguri pentru această dată și canal.</p>
        </div>
        <?php else: ?>
        <div class="log-viewer" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-sm table-hover mb-0 font-monospace" style="font-size: 0.75rem;">
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                    <?php
                        // Determine row color based on log level
                        $rowClass = '';
                        if (strpos($entry, '.CRITICAL') !== false || strpos($entry, '.ERROR') !== false) {
                            $rowClass = 'table-danger';
                        } elseif (strpos($entry, '.WARNING') !== false) {
                            $rowClass = 'table-warning';
                        }
                        
                        // Highlight search term
                        $displayEntry = htmlspecialchars($entry);
                        if ($search) {
                            $displayEntry = preg_replace(
                                '/(' . preg_quote($search, '/') . ')/i',
                                '<mark>$1</mark>',
                                $displayEntry
                            );
                        }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="text-wrap" style="word-break: break-all;">
                            <?= $displayEntry ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="cleanup" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title">Curățare Loguri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Șterge fișierele log mai vechi de:</p>
                    <div class="mb-3">
                        <select name="keep_days" class="form-select">
                            <option value="7">7 zile</option>
                            <option value="14">14 zile</option>
                            <option value="30" selected>30 zile</option>
                            <option value="60">60 zile</option>
                            <option value="90">90 zile</option>
                        </select>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Această acțiune este ireversibilă!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>Șterge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.log-viewer {
    background: #1e1e1e;
}
.log-viewer table {
    color: #d4d4d4;
}
.log-viewer .table-danger {
    background: rgba(220, 53, 69, 0.2) !important;
    color: #f8d7da;
}
.log-viewer .table-warning {
    background: rgba(255, 193, 7, 0.15) !important;
    color: #fff3cd;
}
.log-viewer mark {
    background: #ffc107;
    color: #000;
    padding: 0 2px;
}
</style>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
