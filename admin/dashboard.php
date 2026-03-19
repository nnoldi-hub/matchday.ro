<?php
// Admin dashboard
session_start();
require_once(__DIR__ . '/../config/config.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Get stats
$postsDir = __DIR__ . '/../posts';
$files = array_values(array_filter(scandir($postsDir), fn($f) => substr($f, -5) === '.html'));
$totalPosts = count($files);

$commentsDir = __DIR__ . '/../data/comments';
$totalComments = 0;
if (is_dir($commentsDir)) {
    $commentFiles = array_filter(scandir($commentsDir), fn($f) => substr($f, -5) === '.json' && !str_starts_with($f, 'recent_'));
    foreach ($commentFiles as $cf) {
        $comments = json_decode(file_get_contents($commentsDir . '/' . $cf), true) ?: [];
        $totalComments += count($comments);
    }
}

$pollsDir = __DIR__ . '/../data/polls';
$totalPolls = 0;
$activePolls = 0;
if (is_dir($pollsDir)) {
    $pollFiles = array_filter(scandir($pollsDir), fn($f) => substr($f, -5) === '.json');
    foreach ($pollFiles as $pf) {
        $poll = json_decode(file_get_contents($pollsDir . '/' . $pf), true);
        if ($poll) {
            $totalPolls++;
            if ($poll['active'] ?? false) {
                $activePolls++;
            }
        }
    }
}

$diskUsage = 0;
if (function_exists('disk_free_space')) {
    $diskUsage = disk_free_space(__DIR__ . '/../') / (1024 * 1024 * 1024); // GB
}

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
        <div class="card-body">
          <h3 class="h2 text-primary"><?php echo $totalPosts; ?></h3>
          <p class="text-muted mb-0">Articole</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <a href="comments.php" class="text-decoration-none">
          <div class="card-body">
            <h3 class="h2 text-success"><?php echo $totalComments; ?></h3>
            <p class="text-muted mb-0">Comentarii</p>
          </div>
        </a>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <a href="polls.php" class="text-decoration-none">
          <div class="card-body">
            <h3 class="h2 text-warning"><?php echo $activePolls; ?>/<?php echo $totalPolls; ?></h3>
            <p class="text-muted mb-0">Sondaje active</p>
          </div>
        </a>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3 class="h2 text-info"><?php echo number_format($diskUsage, 1); ?>GB</h3>
          <p class="text-muted mb-0">Spațiu liber</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Posts -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Articole recente</h5>
    </div>
    <div class="card-body">
      <?php if (empty($files)): ?>
        <p class="text-muted">Nu există articole încă.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Titlu</th>
                <th>Data</th>
                <th>Dimensiune</th>
                <th>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $recentFiles = array_slice($files, -10);
              foreach (array_reverse($recentFiles) as $file):
                $path = $postsDir . '/' . $file;
                $size = filesize($path);
                $html = file_get_contents($path);
                $title = pathinfo($file, PATHINFO_FILENAME);
                
                if (preg_match('/<!--\s*david-meta:(.*?)-->/', $html, $m)) {
                  $meta = json_decode(trim($m[1]), true);
                  if (isset($meta['title'])) $title = $meta['title'];
                }
              ?>
              <tr>
                <td>
                  <strong><?php echo Security::sanitizeInput($title); ?></strong>
                  <br><small class="text-muted"><?php echo $file; ?></small>
                </td>
                <td><?php echo date('d.m.Y H:i', filemtime($path)); ?></td>
                <td><?php echo number_format($size / 1024, 1); ?> KB</td>
                <td>
                  <a href="../posts/<?php echo urlencode($file); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                    Vezi
                  </a>
                  <button class="btn btn-sm btn-outline-danger" onclick="deletePost('<?php echo Security::sanitizeInput($file); ?>')">
                    Șterge
                  </button>
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
function deletePost(filename) {
  if (confirm('Sigur vrei să ștergi acest articol? Acțiunea nu poate fi anulată!')) {
    const formData = new FormData();
    formData.append('action', 'delete_post');
    formData.append('filename', filename);
    
    fetch('actions.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        // Reîncarcă pagina pentru a actualiza lista
        window.location.reload();
      } else {
        alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
      }
    })
    .catch(() => {
      alert('Eroare de conexiune. Încearcă din nou.');
    });
  }
}

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
