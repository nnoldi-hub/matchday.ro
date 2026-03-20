<?php
/**
 * Admin - Lista Articole
 * MatchDay.ro
 */
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (Security::validateCSRFToken($token)) {
        $action = $_POST['action'] ?? '';
        $postId = (int)($_POST['post_id'] ?? 0);
        
        switch ($action) {
            case 'delete':
                if ($postId > 0) {
                    Post::delete($postId);
                    $_SESSION['flash_success'] = 'Articolul a fost șters.';
                }
                break;
            case 'publish':
                if ($postId > 0) {
                    Post::publish($postId);
                    $_SESSION['flash_success'] = 'Articolul a fost publicat.';
                }
                break;
            case 'unpublish':
                if ($postId > 0) {
                    Post::unpublish($postId);
                    $_SESSION['flash_success'] = 'Articolul a fost trecut în draft.';
                }
                break;
        }
        header('Location: posts.php');
        exit;
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$status = $_GET['status'] ?? null;
$search = trim($_GET['search'] ?? '');

// Get posts
if ($search !== '') {
    $posts = Post::search($search, $perPage * 2);
    $totalPosts = count($posts);
} else {
    $posts = Post::getAll($page, $perPage, $status);
    $totalPosts = Post::countAll($status);
}

$totalPages = ceil($totalPosts / $perPage);

$pageTitle = 'Articole';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-newspaper me-2"></i>Articole</h1>
    <a href="new-post.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Articol nou
    </a>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3><?= Post::countAll() ?></h3>
                <p>Total articole</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3><?= Post::countAll('published') ?></h3>
                <p>Publicate</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-edit"></i></div>
            <div class="stat-content">
                <h3><?= Post::countAll('draft') ?></h3>
                <p>Draft</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-archive"></i></div>
            <div class="stat-content">
                <h3><?= Post::countAll('archived') ?></h3>
                <p>Arhivate</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Caută</label>
            <input type="text" name="search" class="form-control" 
                   placeholder="Titlu, slug sau conținut..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Toate</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicate</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arhivate</option>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-search me-1"></i>Filtrează
            </button>
            <?php if ($search || $status): ?>
            <a href="posts.php" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Reset
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Articles Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Lista articole</h2>
        <span class="badge bg-primary"><?= $totalPosts ?> rezultate</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 45%">Titlu</th>
                    <th>Categorie</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="text-center">Views</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <?= $search ? 'Niciun articol găsit pentru "'.htmlspecialchars($search).'"' : 'Nu există articole.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <a href="new-post.php?edit=<?= $post['id'] ?>" class="text-decoration-none fw-medium">
                            <?= htmlspecialchars(mb_substr($post['title'], 0, 60)) ?><?= mb_strlen($post['title']) > 60 ? '...' : '' ?>
                        </a>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($post['slug']) ?></small>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($post['category_slug'] ?? '-') ?></span>
                    </td>
                    <td>
                        <?php
                        $statusClass = match($post['status']) {
                            'published' => 'success',
                            'draft' => 'warning',
                            'archived' => 'secondary',
                            default => 'light'
                        };
                        $statusText = match($post['status']) {
                            'published' => 'Publicat',
                            'draft' => 'Draft',
                            'archived' => 'Arhivat',
                            default => $post['status']
                        };
                        ?>
                        <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td class="text-muted small">
                        <?= date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark"><?= number_format($post['views'] ?? 0) ?></span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if ($post['status'] === 'published'): ?>
                            <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" 
                               class="btn btn-outline-success" target="_blank" title="Vezi pe site">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <?php endif; ?>
                            <a href="new-post.php?edit=<?= $post['id'] ?>" 
                               class="btn btn-outline-primary" title="Editează">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')"
                                    title="Șterge">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center pt-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmare ștergere</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ești sigur că vrei să ștergi articolul <strong id="deleteTitle"></strong>?</p>
                <p class="text-danger mb-0"><small><i class="fas fa-warning me-1"></i>Această acțiune nu poate fi anulată.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" id="deletePostId" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Șterge definitiv
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deletePostId').value = id;
    document.getElementById('deleteTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
