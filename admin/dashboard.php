<?php
/**
 * Admin Dashboard
 * MatchDay.ro
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Poll.php');
require_once(__DIR__ . '/../includes/Comment.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Get stats from database
$totalPosts = Post::countAll();
$publishedPosts = Post::countAll('published');
$draftPosts = Post::countAll('draft');
$totalComments = Comment::countAll();
$pendingComments = Comment::countPending();
$totalPolls = Database::fetchValue("SELECT COUNT(*) FROM polls");
$activePolls = Database::fetchValue("SELECT COUNT(*) FROM polls WHERE active = 1");

$diskUsage = 0;
if (function_exists('disk_free_space')) {
    $diskUsage = disk_free_space(__DIR__ . '/../') / (1024 * 1024 * 1024);
}

// Get recent posts from database
$recentPosts = Post::getLatest(10);

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Dashboard Admin</h1>
        <div class="d-flex gap-2">
          <a href="new-post.php" class="btn btn-accent">
            <i class="fas fa-plus me-1"></i>Articol nou
          </a>
          <a href="posts.php" class="btn btn-outline-primary">
            <i class="fas fa-newspaper me-1"></i>Articole
          </a>
          <a href="polls.php" class="btn btn-primary">
            <i class="fas fa-poll me-1"></i>Sondaje
          </a>
          <a href="editorial-management.php" class="btn btn-info">
            <i class="fas fa-calendar-check me-1"></i>Plan Editorial
          </a>
          <a href="logout.php" class="btn btn-outline-secondary">Delogare</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center">
        <a href="posts.php" class="text-decoration-none">
          <div class="card-body">
            <h3 class="h2 text-primary"><?= $totalPosts ?></h3>
            <p class="text-muted mb-0">Articole (<?= $publishedPosts ?> publicate)</p>
          </div>
        </a>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <a href="comments.php" class="text-decoration-none">
          <div class="card-body">
            <h3 class="h2 text-success"><?= $totalComments ?></h3>
            <p class="text-muted mb-0">Comentarii<?= $pendingComments > 0 ? " ($pendingComments pending)" : '' ?></p>
          </div>
        </a>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <a href="polls.php" class="text-decoration-none">
          <div class="card-body">
            <h3 class="h2 text-warning"><?= $activePolls ?>/<?= $totalPolls ?></h3>
            <p class="text-muted mb-0">Sondaje active</p>
          </div>
        </a>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3 class="h2 text-info"><?= number_format($diskUsage, 1) ?>GB</h3>
          <p class="text-muted mb-0">Spațiu liber</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Posts -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Articole recente</h5>
      <a href="posts.php" class="btn btn-sm btn-outline-primary">Vezi toate</a>
    </div>
    <div class="card-body">
      <?php if (empty($recentPosts)): ?>
        <p class="text-muted">Nu există articole încă.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Titlu</th>
                <th>Categorie</th>
                <th>Status</th>
                <th>Data</th>
                <th>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentPosts as $post): ?>
              <tr>
                <td>
                  <a href="edit-post.php?id=<?= $post['id'] ?>" class="text-decoration-none fw-medium">
                    <?= htmlspecialchars($post['title']) ?>
                  </a>
                </td>
                <td>
                  <span class="badge bg-secondary"><?= htmlspecialchars($post['category_slug'] ?? '-') ?></span>
                </td>
                <td>
                  <?php
                  $statusClass = match($post['status'] ?? 'draft') {
                      'published' => 'success',
                      'draft' => 'warning',
                      default => 'secondary'
                  };
                  ?>
                  <span class="badge bg-<?= $statusClass ?>"><?= $post['status'] ?? 'draft' ?></span>
                </td>
                <td><?= date('d.m.Y H:i', strtotime($post['published_at'] ?? $post['created_at'])) ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="edit-post.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary">
                      <i class="fas fa-edit"></i>
                    </a>
                    <?php if ($post['status'] === 'published'): ?>
                    <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-outline-success" target="_blank">
                      <i class="fas fa-eye"></i>
                    </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tools -->
  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Instrumente</h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <button class="btn btn-outline-info" onclick="clearCache()">Golește cache-ul</button>
            <button class="btn btn-outline-warning" onclick="exportData()">Exportă date</button>
            <a href="../rss.php" class="btn btn-outline-success" target="_blank">Vezi RSS</a>
            <a href="../sitemap.php" class="btn btn-outline-secondary" target="_blank">Vezi Sitemap</a>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Sistem</h6>
        </div>
        <div class="card-body">
          <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
          <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Necunoscut'; ?></p>
          <p><strong>Memorie:</strong> <?php echo ini_get('memory_limit'); ?></p>
          <p><strong>Upload max:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function clearCache() {
  if (confirm('Sigur vrei să golești cache-ul?')) {
    const formData = new FormData();
    formData.append('action', 'clear_cache');
    
    fetch('actions.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
      } else {
        alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
      }
    })
    .catch(() => {
      alert('Eroare de conexiune. Încearcă din nou.');
    });
  }
}

function exportData() {
  alert('Funcția de export va fi implementată în versiunea următoare.');
}
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
