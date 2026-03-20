<?php
/**
 * Admin Comments Management
 * MatchDay.ro - Database Version
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Comment.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$message = '';
$messageType = 'info';

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
                        Comment::approve($id);
                        $message = 'Comentariu aprobat!';
                        $messageType = 'success';
                        break;
                    case 'reject':
                        Comment::reject($id);
                        $message = 'Comentariu respins.';
                        $messageType = 'warning';
                        break;
                    case 'delete':
                        Comment::delete($id);
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
            
            switch ($bulkAction) {
                case 'approve':
                    $count = Comment::bulkApprove($ids);
                    $message = "$count comentarii aprobate!";
                    $messageType = 'success';
                    break;
                case 'delete':
                    $count = Comment::bulkDelete($ids);
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
$filter = $_GET['filter'] ?? 'all';
$approvedFilter = null;
if ($filter === 'pending') $approvedFilter = 0;
if ($filter === 'approved') $approvedFilter = 1;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = Comment::countAll($approvedFilter);
$pages = max(1, ceil($total / $perPage));
$page = min($page, $pages);

// Get comments
$comments = Comment::getAll($page, $perPage, $approvedFilter);

// Stats
$totalAll = Comment::countAll();
$totalApproved = Comment::countAll(1);
$totalPending = Comment::countPending();

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">
      <i class="fas fa-comments me-2"></i>Comentarii
    </h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i>Dashboard
    </a>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <a href="?filter=all" class="text-decoration-none">
        <div class="card text-center <?= $filter === 'all' ? 'border-primary' : '' ?>">
          <div class="card-body py-3">
            <h4 class="mb-0 text-primary"><?= $totalAll ?></h4>
            <small class="text-muted">Total</small>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="?filter=approved" class="text-decoration-none">
        <div class="card text-center <?= $filter === 'approved' ? 'border-success' : '' ?>">
          <div class="card-body py-3">
            <h4 class="mb-0 text-success"><?= $totalApproved ?></h4>
            <small class="text-muted">Aprobate</small>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="?filter=pending" class="text-decoration-none">
        <div class="card text-center <?= $filter === 'pending' ? 'border-warning' : '' ?>">
          <div class="card-body py-3">
            <h4 class="mb-0 text-warning"><?= $totalPending ?></h4>
            <small class="text-muted">În așteptare</small>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Comments Table -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <?php
        echo match($filter) {
            'pending' => 'Comentarii în așteptare',
            'approved' => 'Comentarii aprobate',
            default => 'Toate comentariile'
        };
        ?>
      </h5>
      <?php if ($totalPending > 0): ?>
        <span class="badge bg-warning"><?= $totalPending ?> noi</span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if (empty($comments)): ?>
        <div class="text-center py-5">
          <i class="fas fa-comments fa-3x text-muted mb-3"></i>
          <p class="text-muted mb-0">Nu există comentarii<?= $filter !== 'all' ? ' în această categorie' : '' ?>.</p>
        </div>
      <?php else: ?>
        <form method="post" id="bulkForm">
          <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
          <input type="hidden" name="action" value="bulk">
          
          <!-- Bulk Actions Bar -->
          <div class="mb-3 d-flex gap-2 align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="selectAll">
              <label class="form-check-label" for="selectAll">Selectează tot</label>
            </div>
            <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
              <option value="">-- Acțiune --</option>
              <option value="approve">Aprobă selectate</option>
              <option value="delete">Șterge selectate</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulk()">
              <i class="fas fa-check me-1"></i>Aplică
            </button>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width: 40px;"></th>
                  <th>Autor</th>
                  <th>Comentariu</th>
                  <th>Articol</th>
                  <th>Data</th>
                  <th>Status</th>
                  <th style="width: 150px;">Acțiuni</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($comments as $comment): ?>
                <tr class="<?= $comment['approved'] ? '' : 'table-warning' ?>">
                  <td>
                    <input type="checkbox" name="selected[]" value="<?= $comment['id'] ?>" class="form-check-input comment-check">
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($comment['author_name']) ?></strong>
                    <?php if (!empty($comment['author_email'])): ?>
                      <br><small class="text-muted"><?= htmlspecialchars($comment['author_email']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="comment-preview" style="max-width: 300px;">
                      <?php 
                      $content = htmlspecialchars($comment['content']);
                      echo mb_strlen($content) > 100 ? mb_substr($content, 0, 100) . '...' : $content;
                      ?>
                    </div>
                    <?php if (mb_strlen($comment['content']) > 100): ?>
                      <button type="button" class="btn btn-link btn-sm p-0" 
                              onclick="viewComment(<?= $comment['id'] ?>, '<?= htmlspecialchars(addslashes($comment['author_name'])) ?>')">
                        Vezi tot
                      </button>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($comment['post_title']): ?>
                      <a href="edit-post.php?slug=<?= urlencode($comment['post_slug']) ?>" class="text-decoration-none">
                        <?= htmlspecialchars(mb_strimwidth($comment['post_title'], 0, 30, '...')) ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted"><?= htmlspecialchars($comment['post_slug']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <small><?= date('d.m.Y', strtotime($comment['created_at'])) ?></small>
                    <br><small class="text-muted"><?= date('H:i', strtotime($comment['created_at'])) ?></small>
                  </td>
                  <td>
                    <?php if ($comment['approved']): ?>
                      <span class="badge bg-success">Aprobat</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark">În așteptare</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <?php if (!$comment['approved']): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                        <button type="submit" class="btn btn-success" title="Aprobă">
                          <i class="fas fa-check"></i>
                        </button>
                      </form>
                      <?php else: ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                        <button type="submit" class="btn btn-outline-warning" title="Respinge">
                          <i class="fas fa-times"></i>
                        </button>
                      </form>
                      <?php endif; ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Sigur ștergi acest comentariu?')">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Șterge">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
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
          <ul class="pagination justify-content-center mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $page - 1 ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($pages, $page + 2);
            
            if ($start > 1): ?>
              <li class="page-item"><a class="page-link" href="?filter=<?= $filter ?>&page=1">1</a></li>
              <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif;
            
            for ($i = $start; $i <= $end; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor;
            
            if ($end < $pages): ?>
              <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
              <li class="page-item"><a class="page-link" href="?filter=<?= $filter ?>&page=<?= $pages ?>"><?= $pages ?></a></li>
            <?php endif; ?>
            
            <?php if ($page < $pages): ?>
              <li class="page-item">
                <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $page + 1 ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal View Comment -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comentariu de la <span id="modalAuthor"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalContent" style="white-space: pre-wrap;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
      </div>
    </div>
  </div>
</div>

<script>
// Store comments content for modal
const commentsData = <?= json_encode(array_map(fn($c) => ['id' => $c['id'], 'content' => $c['content']], $comments)) ?>;

// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
  document.querySelectorAll('.comment-check').forEach(cb => cb.checked = this.checked);
});

// Update "select all" when individual checkboxes change
document.querySelectorAll('.comment-check').forEach(cb => {
  cb.addEventListener('change', function() {
    const all = document.querySelectorAll('.comment-check');
    const checked = document.querySelectorAll('.comment-check:checked');
    document.getElementById('selectAll').checked = all.length === checked.length;
  });
});

// Confirm bulk action
function confirmBulk() {
  const checked = document.querySelectorAll('.comment-check:checked');
  const action = document.querySelector('[name="bulk_action"]').value;
  
  if (checked.length === 0) {
    alert('Selectează cel puțin un comentariu.');
    return false;
  }
  
  if (!action) {
    alert('Alege o acțiune.');
    return false;
  }
  
  const actionText = action === 'delete' ? 'ștergi' : 'aprobi';
  return confirm(`Sigur vrei să ${actionText} ${checked.length} comentarii?`);
}

// View full comment
function viewComment(id, author) {
  const comment = commentsData.find(c => c.id === id);
  if (comment) {
    document.getElementById('modalAuthor').textContent = author;
    document.getElementById('modalContent').textContent = comment.content;
    new bootstrap.Modal(document.getElementById('viewModal')).show();
  }
}
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
