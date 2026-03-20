<?php
/**
 * Admin Dashboard
 * MatchDay.ro - Phase 5 (Sidebar Layout)
 */

$pageTitle = 'Dashboard';

require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Poll.php');
require_once(__DIR__ . '/../includes/Comment.php');
require_once(__DIR__ . '/../includes/User.php');

// Include admin header (handles session, auth, sidebar)
require_once(__DIR__ . '/admin-header.php');

// Get stats from database
$totalPosts = Post::countAll();
$publishedPosts = Post::countAll('published');
$draftPosts = Post::countAll('draft');
$totalComments = Comment::countAll();
$pendingComments = Comment::countPending();
$totalPolls = Database::fetchValue("SELECT COUNT(*) FROM polls");
$activePolls = Database::fetchValue("SELECT COUNT(*) FROM polls WHERE active = 1");
$totalViews = Database::fetchValue("SELECT COALESCE(SUM(views), 0) FROM posts");

// Get recent posts from database
$recentPosts = Post::getLatest(8);
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1>Dashboard</h1>
    <p>Bine ai venit înapoi, <?= Security::sanitizeInput($currentUserName) ?>!</p>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="posts.php" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $totalPosts ?></h3>
                    <p>Articole (<?= $publishedPosts ?> publicate)</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="comments.php" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $totalComments ?></h3>
                    <p>Comentarii<?= $pendingComments > 0 ? " ({$pendingComments} în așteptare)" : '' ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="polls.php" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-poll"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $activePolls ?>/<?= $totalPolls ?></h3>
                    <p>Sondaje active</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="stats.php" class="text-decoration-none">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($totalViews) ?></h3>
                    <p>Vizualizări totale</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h2><i class="fas fa-bolt me-2"></i>Acțiuni rapide</h2>
    </div>
    <div class="quick-actions">
        <a href="new-post.php" class="quick-action-btn">
            <i class="fas fa-plus-circle"></i> Articol nou
        </a>
        <a href="polls.php?action=new" class="quick-action-btn">
            <i class="fas fa-poll"></i> Sondaj nou
        </a>
        <a href="media.php" class="quick-action-btn">
            <i class="fas fa-upload"></i> Upload media
        </a>
        <a href="comments.php?filter=pending" class="quick-action-btn">
            <i class="fas fa-check-circle"></i> Moderare comentarii
        </a>
        <a href="../index.php" class="quick-action-btn" target="_blank">
            <i class="fas fa-globe"></i> Vezi site
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Posts -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fas fa-clock me-2"></i>Articole recente</h2>
                <a href="posts.php" class="btn btn-sm btn-outline-primary">Vezi toate</a>
            </div>
            <?php if (empty($recentPosts)): ?>
                <p class="text-muted">Nu există articole încă.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Titlu</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td>
                                    <a href="new-post.php?edit=<?= $post['id'] ?>" class="text-decoration-none fw-medium">
                                        <?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?><?= mb_strlen($post['title']) > 50 ? '...' : '' ?>
                                    </a>
                                    <br><small class="text-muted"><?= htmlspecialchars($post['category_slug'] ?? '-') ?></small>
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
                                <td class="text-muted small">
                                    <?= date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                                </td>
                                <td>
                                    <a href="new-post.php?edit=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editează">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($post['status'] === 'published'): ?>
                                    <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-sm btn-outline-success" target="_blank" title="Vezi">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- System Info & Tools -->
    <div class="col-lg-4">
        <!-- Tools -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h2><i class="fas fa-tools me-2"></i>Instrumente</h2>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-info btn-sm" onclick="clearCache()">
                    <i class="fas fa-broom me-1"></i>Golește cache
                </button>
                <?php if ($isAdmin): ?>
                <a href="backup.php" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-download me-1"></i>Backup & Export
                </a>
                <?php endif; ?>
                <a href="../rss.php" class="btn btn-outline-success btn-sm" target="_blank">
                    <i class="fas fa-rss me-1"></i>Vezi RSS
                </a>
                <a href="../sitemap.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="fas fa-sitemap me-1"></i>Vezi Sitemap
                </a>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fas fa-server me-2"></i>Sistem</h2>
            </div>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted">PHP</td>
                    <td class="text-end"><code><?= PHP_VERSION ?></code></td>
                </tr>
                <tr>
                    <td class="text-muted">Memorie</td>
                    <td class="text-end"><code><?= ini_get('memory_limit') ?></code></td>
                </tr>
                <tr>
                    <td class="text-muted">Upload max</td>
                    <td class="text-end"><code><?= ini_get('upload_max_filesize') ?></code></td>
                </tr>
                <tr>
                    <td class="text-muted">Bază de date</td>
                    <td class="text-end"><code>MySQL</code></td>
                </tr>
            </table>
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
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
