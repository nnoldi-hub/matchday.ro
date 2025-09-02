<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        $action = $_POST['action'] ?? '';
        $slug = Security::sanitizeInput($_POST['slug'] ?? '');
        $commentIndex = intval($_POST['comment_index'] ?? -1);
        
        if ($action === 'delete_comment' && $slug && $commentIndex >= 0) {
            $result = deleteComment($slug, $commentIndex);
            $message = $result ? 'Comentariu șters cu succes!' : 'Eroare la ștergerea comentariului.';
        } elseif ($action === 'approve_comment' && $slug && $commentIndex >= 0) {
            $result = approveComment($slug, $commentIndex);
            $message = $result ? 'Comentariu aprobat cu succes!' : 'Eroare la aprobarea comentariului.';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

// Get all comments
function getAllComments() {
    $commentsDir = __DIR__ . '/../data/comments';
    $allComments = [];
    
    if (!is_dir($commentsDir)) return $allComments;
    
    $files = array_filter(scandir($commentsDir), fn($f) => substr($f, -5) === '.json' && !str_starts_with($f, 'recent_'));
    
    foreach ($files as $file) {
        $slug = str_replace('.json', '', $file);
        $comments = json_decode(file_get_contents($commentsDir . '/' . $file), true) ?: [];
        
        foreach ($comments as $index => $comment) {
            $comment['slug'] = $slug;
            $comment['index'] = $index;
            $comment['post_title'] = getPostTitle($slug);
            $allComments[] = $comment;
        }
    }
    
    // Sort by timestamp descending (newest first)
    usort($allComments, fn($a, $b) => ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0));
    
    return $allComments;
}

function getPostTitle($slug) {
    $postsDir = __DIR__ . '/../posts';
    $files = scandir($postsDir);
    
    foreach ($files as $file) {
        if (strpos($file, $slug) !== false && substr($file, -5) === '.html') {
            $html = file_get_contents($postsDir . '/' . $file);
            if (preg_match('/<!--\s*david-meta:(.*?)-->/', $html, $m)) {
                $meta = json_decode(trim($m[1]), true);
                if (isset($meta['title'])) return $meta['title'];
            }
            break;
        }
    }
    
    return ucfirst(str_replace('-', ' ', $slug));
}

function deleteComment($slug, $commentIndex) {
    $file = __DIR__ . '/../data/comments/' . $slug . '.json';
    if (!file_exists($file)) return false;
    
    $comments = json_decode(file_get_contents($file), true) ?: [];
    if (!isset($comments[$commentIndex])) return false;
    
    array_splice($comments, $commentIndex, 1);
    return file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function approveComment($slug, $commentIndex) {
    $file = __DIR__ . '/../data/comments/' . $slug . '.json';
    if (!file_exists($file)) return false;
    
    $comments = json_decode(file_get_contents($file), true) ?: [];
    if (!isset($comments[$commentIndex])) return false;
    
    $comments[$commentIndex]['approved'] = true;
    $comments[$commentIndex]['approved_at'] = time();
    
    return file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

$comments = getAllComments();
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = count($comments);
$pages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$pagedComments = array_slice($comments, $offset, $perPage);

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Management Comentarii</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> Înapoi la Dashboard
    </a>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?php echo Security::sanitizeInput($message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <!-- Stats Summary -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h4 class="text-primary"><?php echo $total; ?></h4>
          <small class="text-muted">Total comentarii</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h4 class="text-success"><?php echo count(array_filter($comments, fn($c) => isset($c['approved']) && $c['approved'] === true)); ?></h4>
          <small class="text-muted">Aprobate</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h4 class="text-warning"><?php echo count(array_filter($comments, fn($c) => !isset($c['approved']) || $c['approved'] !== true)); ?></h4>
          <small class="text-muted">În așteptare</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h4 class="text-info"><?php echo $pages; ?></h4>
          <small class="text-muted">Pagini</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Comments List -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Toate comentariile</h5>
    </div>
    <div class="card-body">
      <?php if (empty($pagedComments)): ?>
        <div class="text-center py-4">
          <i class="fas fa-comments fa-3x text-muted mb-3"></i>
          <p class="text-muted">Nu există comentarii încă.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Articol</th>
                <th>Autor</th>
                <th>Comentariu</th>
                <th>Data</th>
                <th>Status</th>
                <th>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pagedComments as $comment): ?>
              <tr class="<?php echo isset($comment['approved']) ? '' : 'table-warning'; ?>">
                <td>
                  <strong><?php echo Security::sanitizeInput($comment['post_title']); ?></strong>
                  <br><small class="text-muted"><?php echo Security::sanitizeInput($comment['slug']); ?></small>
                </td>
                <td>
                  <strong><?php echo Security::sanitizeInput($comment['name']); ?></strong>
                  <br><small class="text-muted">IP: <?php echo Security::sanitizeInput(substr($comment['ip'] ?? '', 0, 8)); ?>...</small>
                </td>
                <td>
                  <div style="max-width: 300px;">
                    <?php 
                    $message = Security::sanitizeInput($comment['message']);
                    echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                    ?>
                  </div>
                </td>
                <td>
                  <?php echo Security::sanitizeInput($comment['date'] ?? 'N/A'); ?>
                  <?php if (isset($comment['timestamp'])): ?>
                    <br><small class="text-muted"><?php echo date('H:i', $comment['timestamp']); ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (isset($comment['approved']) && $comment['approved'] === true): ?>
                    <span class="badge bg-success">Aprobat</span>
                  <?php else: ?>
                    <span class="badge bg-warning">În așteptare</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary" 
                            onclick="viewComment('<?php echo Security::sanitizeInput($comment['message']); ?>', '<?php echo Security::sanitizeInput($comment['name']); ?>')">
                      <i class="fas fa-eye"></i>
                    </button>
                    
                    <?php if (!isset($comment['approved']) || $comment['approved'] !== true): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                      <input type="hidden" name="action" value="approve_comment">
                      <input type="hidden" name="slug" value="<?php echo Security::sanitizeInput($comment['slug']); ?>">
                      <input type="hidden" name="comment_index" value="<?php echo $comment['index']; ?>">
                      <button type="submit" class="btn btn-outline-success" 
                              onclick="return confirm('Aprobi acest comentariu?')">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                      <input type="hidden" name="action" value="delete_comment">
                      <input type="hidden" name="slug" value="<?php echo Security::sanitizeInput($comment['slug']); ?>">
                      <input type="hidden" name="comment_index" value="<?php echo $comment['index']; ?>">
                      <button type="submit" class="btn btn-outline-danger" 
                              onclick="return confirm('Sigur vrei să ștergi acest comentariu?')">
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
        
        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <nav aria-label="Paginare comentarii" class="mt-4">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page + 1; ?>">Următor</a>
            </li>
            <?php endif; ?>
          </ul>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal pentru vizualizare comentariu complet -->
<div class="modal fade" id="commentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comentariu de la <span id="modalAuthor"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalMessage" style="white-space: pre-wrap; word-wrap: break-word;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewComment(message, author) {
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('modalAuthor').textContent = author;
    new bootstrap.Modal(document.getElementById('commentModal')).show();
}
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
