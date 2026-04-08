<?php
/**
 * Admin Match Comments Management
 * MatchDay.ro - For managing comments on live matches
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Logger.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$message = '';
$messageType = 'info';
$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        $action = $_POST['action'] ?? '';
        
        // Single comment actions
        if (in_array($action, ['approve', 'reject', 'delete'])) {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                switch ($action) {
                    case 'approve':
                        $stmt = $db->prepare('UPDATE match_comments SET status = :status WHERE id = :id');
                        $stmt->execute([':status' => 'approved', ':id' => $id]);
                        Logger::audit('MATCH_COMMENT_APPROVE', $_SESSION['user_id'] ?? 0, ['comment_id' => $id]);
                        $message = 'Comentariu aprobat!';
                        $messageType = 'success';
                        break;
                    case 'reject':
                        $stmt = $db->prepare('UPDATE match_comments SET status = :status WHERE id = :id');
                        $stmt->execute([':status' => 'rejected', ':id' => $id]);
                        Logger::audit('MATCH_COMMENT_REJECT', $_SESSION['user_id'] ?? 0, ['comment_id' => $id]);
                        $message = 'Comentariu respins.';
                        $messageType = 'warning';
                        break;
                    case 'delete':
                        $stmt = $db->prepare('DELETE FROM match_comments WHERE id = :id');
                        $stmt->execute([':id' => $id]);
                        Logger::audit('MATCH_COMMENT_DELETE', $_SESSION['user_id'] ?? 0, ['comment_id' => $id]);
                        $message = 'Comentariu șters!';
                        $messageType = 'success';
                        break;
                }
            }
        }
        
        // Bulk actions
        if ($action === 'bulk' && !empty($_POST['selected'])) {
            $ids = array_map('intval', $_POST['selected']);
            $bulkAction = $_POST['bulk_action'] ?? '';
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            switch ($bulkAction) {
                case 'approve':
                    $stmt = $db->prepare("UPDATE match_comments SET status = 'approved' WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $count = $stmt->rowCount();
                    Logger::audit('MATCH_COMMENTS_BULK_APPROVE', $_SESSION['user_id'] ?? 0, ['count' => $count]);
                    $message = "$count comentarii aprobate!";
                    $messageType = 'success';
                    break;
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM match_comments WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $count = $stmt->rowCount();
                    Logger::audit('MATCH_COMMENTS_BULK_DELETE', $_SESSION['user_id'] ?? 0, ['count' => $count]);
                    $message = "$count comentarii șterse!";
                    $messageType = 'success';
                    break;
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Filters
$filter = $_GET['filter'] ?? 'pending';
$statusFilter = $filter === 'all' ? null : $filter;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Count total
$countSql = 'SELECT COUNT(*) FROM match_comments';
$countParams = [];
if ($statusFilter) {
    $countSql .= ' WHERE status = :status';
    $countParams[':status'] = $statusFilter;
}
$stmt = $db->prepare($countSql);
$stmt->execute($countParams);
$total = $stmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

// Get comments with match info
$sql = "SELECT mc.*, lm.home_team, lm.away_team, lm.kickoff 
        FROM match_comments mc 
        LEFT JOIN live_matches lm ON mc.match_id = lm.id";
if ($statusFilter) {
    $sql .= " WHERE mc.status = :status";
}
$sql .= " ORDER BY mc.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
if ($statusFilter) {
    $stmt->execute([':status' => $statusFilter]);
} else {
    $stmt->execute();
}
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];
$stmt = $db->query("SELECT status, COUNT(*) as cnt FROM match_comments GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['status']] = (int)$row['cnt'];
}

include(__DIR__ . '/admin-header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-comment-alt me-2"></i>Comentarii Meciuri</h2>
        <a href="livescores.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Înapoi la Meciuri
        </a>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?= $stats['pending'] ?></h3>
                    <p class="mb-0">În așteptare</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success"><?= $stats['approved'] ?></h3>
                    <p class="mb-0">Aprobate</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger"><?= $stats['rejected'] ?></h3>
                    <p class="mb-0">Respinse</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="?filter=pending">
                În așteptare 
                <?php if ($stats['pending'] > 0): ?>
                <span class="badge bg-warning text-dark"><?= $stats['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="?filter=approved">Aprobate</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'rejected' ? 'active' : '' ?>" href="?filter=rejected">Respinse</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">Toate</a>
        </li>
    </ul>
    
    <?php if (empty($comments)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Nu sunt comentarii în această categorie.
    </div>
    <?php else: ?>
    
    <form method="post" id="commentsForm">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
        <input type="hidden" name="action" value="bulk">
        
        <!-- Bulk Actions -->
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Selectează tot</label>
                </div>
                <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Acțiuni în masă</option>
                    <option value="approve">✓ Aprobă selectate</option>
                    <option value="delete">✗ Șterge selectate</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Ești sigur?')">Aplică</button>
            </div>
        </div>
        
        <!-- Comments Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="40"></th>
                        <th>Meci</th>
                        <th>Autor</th>
                        <th>Comentariu</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th width="200">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input comment-checkbox" 
                                   name="selected[]" value="<?= $comment['id'] ?>">
                        </td>
                        <td>
                            <?php if ($comment['home_team']): ?>
                            <a href="../match.php?id=<?= $comment['match_id'] ?>" target="_blank" class="text-decoration-none">
                                <strong><?= htmlspecialchars($comment['home_team']) ?></strong>
                                <span class="text-muted">vs</span>
                                <strong><?= htmlspecialchars($comment['away_team']) ?></strong>
                            </a>
                            <br><small class="text-muted"><?= date('d.m.Y H:i', strtotime($comment['kickoff'])) ?></small>
                            <?php else: ?>
                            <span class="text-muted">Meci șters</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($comment['author_name']) ?></strong>
                            <br><small class="text-muted" title="<?= htmlspecialchars($comment['ip_address']) ?>">
                                <?= htmlspecialchars($comment['ip_address']) ?>
                            </small>
                        </td>
                        <td style="max-width: 300px;">
                            <div class="text-truncate" title="<?= htmlspecialchars($comment['content']) ?>">
                                <?= htmlspecialchars(mb_substr($comment['content'], 0, 100)) ?>
                                <?= strlen($comment['content']) > 100 ? '...' : '' ?>
                            </div>
                        </td>
                        <td>
                            <small><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php
                            $statusClass = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ][$comment['status']] ?? 'secondary';
                            $statusText = [
                                'pending' => 'În așteptare',
                                'approved' => 'Aprobat',
                                'rejected' => 'Respins'
                            ][$comment['status']] ?? $comment['status'];
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($comment['status'] !== 'approved'): ?>
                                <button type="submit" name="action" value="approve" class="btn btn-success" 
                                        onclick="this.form.id.value='<?= $comment['id'] ?>'">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($comment['status'] !== 'rejected'): ?>
                                <button type="submit" name="action" value="reject" class="btn btn-warning"
                                        onclick="this.form.id.value='<?= $comment['id'] ?>'">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="delete" class="btn btn-danger"
                                        onclick="this.form.id.value='<?= $comment['id'] ?>'; return confirm('Ștergi acest comentariu?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <input type="hidden" name="id" value="">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    
    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.comment-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>

<?php include(__DIR__ . '/admin-footer.php'); ?>
