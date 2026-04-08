<?php
/**
 * Admin Audit Log Viewer
 * MatchDay.ro - Detailed view of admin actions
 * 
 * Features:
 * - Filter by action type
 * - Filter by user
 * - Date range
 * - Export CSV
 */

$pageTitle = 'Audit Log - Admin';
require_once(__DIR__ . '/../includes/Logger.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/admin-header.php');

// Only admins can view audit logs
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Get parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$actionFilter = $_GET['action'] ?? '';
$userFilter = $_GET['user_id'] ?? '';
$limit = (int)($_GET['limit'] ?? 100);

// Action types for filter dropdown
$actionTypes = [
    'POST_CREATE' => ['label' => 'Articol creat', 'icon' => 'fa-plus', 'color' => 'success'],
    'POST_UPDATE' => ['label' => 'Articol editat', 'icon' => 'fa-edit', 'color' => 'info'],
    'POST_DELETE' => ['label' => 'Articol șters', 'icon' => 'fa-trash', 'color' => 'danger'],
    'POST_PUBLISH' => ['label' => 'Articol publicat', 'icon' => 'fa-check', 'color' => 'success'],
    'USER_CREATE' => ['label' => 'Utilizator creat', 'icon' => 'fa-user-plus', 'color' => 'success'],
    'USER_UPDATE' => ['label' => 'Utilizator editat', 'icon' => 'fa-user-edit', 'color' => 'info'],
    'USER_DELETE' => ['label' => 'Utilizator șters', 'icon' => 'fa-user-minus', 'color' => 'danger'],
    'COMMENT_DELETE' => ['label' => 'Comentariu șters', 'icon' => 'fa-comment-slash', 'color' => 'warning'],
    'COMMENT_APPROVE' => ['label' => 'Comentariu aprobat', 'icon' => 'fa-comment-check', 'color' => 'success'],
    'SUBMISSION_APPROVE' => ['label' => 'Contribuție aprobată', 'icon' => 'fa-check-circle', 'color' => 'success'],
    'SUBMISSION_REJECT' => ['label' => 'Contribuție respinsă', 'icon' => 'fa-times-circle', 'color' => 'danger'],
    'SUBMISSION_PUBLISH' => ['label' => 'Contribuție publicată', 'icon' => 'fa-paper-plane', 'color' => 'primary'],
    'SETTINGS_CHANGE' => ['label' => 'Setări modificate', 'icon' => 'fa-cog', 'color' => 'secondary'],
    'LOGIN_SUCCESS' => ['label' => 'Login reușit', 'icon' => 'fa-sign-in-alt', 'color' => 'success'],
    'LOGIN_FAILED' => ['label' => 'Login eșuat', 'icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
    'LOGS_CLEANUP' => ['label' => 'Curățare loguri', 'icon' => 'fa-broom', 'color' => 'secondary'],
];

// Get all users for filter dropdown
$users = User::getAll();

// Parse audit log entries
function parseAuditEntries(string $startDate, string $endDate, string $actionFilter = '', string $userFilter = '', int $limit = 100): array {
    $logsDir = __DIR__ . '/../data/logs';
    $entries = [];
    $currentDate = $startDate;
    
    while ($currentDate <= $endDate && count($entries) < $limit) {
        $filename = $logsDir . "/audit-{$currentDate}.log";
        
        if (file_exists($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $parsed = parseLogLine($line);
                
                if ($parsed) {
                    // Apply action filter
                    if ($actionFilter && $parsed['action'] !== $actionFilter) {
                        continue;
                    }
                    
                    // Apply user filter
                    if ($userFilter && $parsed['user_id'] != $userFilter) {
                        continue;
                    }
                    
                    $parsed['date'] = $currentDate;
                    $entries[] = $parsed;
                    
                    if (count($entries) >= $limit) {
                        break 2;
                    }
                }
            }
        }
        
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    
    // Sort by timestamp descending (most recent first)
    usort($entries, function($a, $b) {
        return strcmp($b['timestamp'], $a['timestamp']);
    });
    
    return $entries;
}

function parseLogLine(string $line): ?array {
    // Format: [2026-04-08 12:30:45] AUDIT.INFO: User #1: POST_CREATE {"action":"POST_CREATE",...} | IP: 127.0.0.1 | URI: /admin/save-post.php
    
    $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] AUDIT\.(\w+): User #(\d+): (\w+) ({.*?}) \| IP: ([\d\.\:a-fA-F]+|Unknown) \| URI: (.+)$/';
    
    if (preg_match($pattern, $line, $matches)) {
        $context = json_decode($matches[5], true) ?? [];
        
        return [
            'timestamp' => $matches[1],
            'level' => $matches[2],
            'user_id' => (int)$matches[3],
            'action' => $matches[4],
            'context' => $context,
            'ip' => $matches[6],
            'uri' => $matches[7],
            'raw' => $line
        ];
    }
    
    return null;
}

// Get parsed entries
$entries = parseAuditEntries($startDate, $endDate, $actionFilter, $userFilter, $limit);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit-log-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header row
    fputcsv($output, ['Data/Ora', 'Utilizator', 'Acțiune', 'Detalii', 'IP', 'URI']);
    
    // Build user lookup
    $userLookup = [];
    foreach ($users as $u) {
        $userLookup[$u['id']] = $u['username'];
    }
    
    // Data rows
    foreach ($entries as $entry) {
        $userName = $userLookup[$entry['user_id']] ?? "User #{$entry['user_id']}";
        $details = json_encode($entry['context'], JSON_UNESCAPED_UNICODE);
        
        fputcsv($output, [
            $entry['timestamp'],
            $userName,
            $entry['action'],
            $details,
            $entry['ip'],
            $entry['uri']
        ]);
    }
    
    fclose($output);
    exit;
}

// Build user lookup for display
$userLookup = [];
foreach ($users as $u) {
    $userLookup[$u['id']] = $u['username'];
}

// Count by action type
$actionCounts = [];
foreach ($entries as $entry) {
    $action = $entry['action'];
    $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
}
?>

<!-- Page Header -->
<div class="admin-page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-clipboard-list me-2"></i>Audit Log</h1>
        <p class="text-muted mb-0">Istoricul acțiunilor administrative</p>
    </div>
    <div>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-csv me-1"></i>Export CSV
        </a>
        <a href="logs.php?channel=audit" class="btn btn-outline-secondary btn-sm ms-2">
            <i class="fas fa-file-alt me-1"></i>Raw Logs
        </a>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small opacity-75">Total Acțiuni</div>
                        <div class="h3 mb-0"><?= count($entries) ?></div>
                    </div>
                    <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $topActions = array_slice($actionCounts, 0, 3, true);
    foreach ($topActions as $action => $count):
        $info = $actionTypes[$action] ?? ['label' => $action, 'icon' => 'fa-question', 'color' => 'secondary'];
    ?>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted"><?= $info['label'] ?></div>
                        <div class="h3 mb-0 text-<?= $info['color'] ?>"><?= $count ?></div>
                    </div>
                    <i class="fas <?= $info['icon'] ?> fa-2x text-<?= $info['color'] ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">De la</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Până la</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Tip Acțiune</label>
                <select name="action" class="form-select">
                    <option value="">Toate acțiunile</option>
                    <?php foreach ($actionTypes as $actionKey => $info): ?>
                    <option value="<?= $actionKey ?>" <?= $actionFilter === $actionKey ? 'selected' : '' ?>>
                        <?= $info['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Utilizator</label>
                <select name="user_id" class="form-select">
                    <option value="">Toți</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">Limită</label>
                <select name="limit" class="form-select">
                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200</option>
                    <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filtrează
                </button>
                <a href="audit-log.php" class="btn btn-outline-secondary">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Audit Entries Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-history me-2"></i>Acțiuni Recente</h2>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($entries)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard-check fa-3x mb-3"></i>
            <p>Nu s-au găsit acțiuni pentru criteriile selectate.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Data/Ora</th>
                        <th style="width: 120px;">Utilizator</th>
                        <th style="width: 180px;">Acțiune</th>
                        <th>Detalii</th>
                        <th style="width: 120px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): 
                        $actionInfo = $actionTypes[$entry['action']] ?? ['label' => $entry['action'], 'icon' => 'fa-question', 'color' => 'secondary'];
                        $userName = $userLookup[$entry['user_id']] ?? "User #{$entry['user_id']}";
                    ?>
                    <tr>
                        <td class="text-nowrap">
                            <small class="text-muted">
                                <?= date('d.m.Y', strtotime($entry['timestamp'])) ?><br>
                                <strong><?= date('H:i:s', strtotime($entry['timestamp'])) ?></strong>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($userName) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $actionInfo['color'] ?>">
                                <i class="fas <?= $actionInfo['icon'] ?> me-1"></i>
                                <?= $actionInfo['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $details = $entry['context'];
                            unset($details['action'], $details['user_id']); // Remove redundant fields
                            
                            if (!empty($details)): ?>
                            <div class="audit-details">
                                <?php foreach ($details as $key => $value): ?>
                                <span class="badge bg-light text-dark me-1 mb-1">
                                    <strong><?= htmlspecialchars($key) ?>:</strong>
                                    <?php if (is_array($value)): ?>
                                        <?= htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE)) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_strimwidth((string)$value, 0, 50, '...')) ?>
                                    <?php endif; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="small"><?= htmlspecialchars($entry['ip']) ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($entries) >= $limit): ?>
    <div class="card-footer text-center text-muted">
        <i class="fas fa-info-circle me-1"></i>
        Afișate primele <?= $limit ?> rezultate. Mărește limita sau ajustează filtrele pentru mai multe.
    </div>
    <?php endif; ?>
</div>

<style>
.audit-details {
    max-width: 400px;
    line-height: 1.8;
}
.audit-details .badge {
    font-weight: normal;
    font-size: 0.75rem;
}
</style>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
