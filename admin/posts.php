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

include(__DIR__ . '/../includes/header.php');
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Articole</h1>
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

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Caută articole..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Toate statusurile</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicate</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arhivate</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filtrează</button>
                </div>
                <?php if ($search || $status): ?>
                <div class="col-md-2">
                    <a href="posts.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h4 class="mb-0"><?= Post::countAll() ?></h4>
                    <small class="text-muted">Total articole</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-success"><?= Post::countAll('published') ?></h4>
                    <small class="text-muted">Publicate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-warning"><?= Post::countAll('draft') ?></h4>
                    <small class="text-muted">Draft</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-secondary bg-opacity-10">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-secondary"><?= Post::countAll('archived') ?></h4>
                    <small class="text-muted">Arhivate</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles Table -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50%">Titlu</th>
                        <th>Categorie</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Views</th>
                        <th class="text-end">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <?= $search ? 'Niciun articol găsit.' : 'Nu există articole.' ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <a href="edit-post.php?id=<?= $post['id'] ?>" class="text-decoration-none fw-medium">
                                <?= htmlspecialchars($post['title']) ?>
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
                        <td>
                            <small><?= date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])) ?></small>
                        </td>
                        <td>
                            <small><?= number_format($post['views'] ?? 0) ?></small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" 
                                   class="btn btn-outline-secondary" target="_blank" title="Vezi">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit-post.php?id=<?= $post['id'] ?>" 
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
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>">Anterior</a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>">Următor</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmare ștergere</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ești sigur că vrei să ștergi articolul <strong id="deleteTitle"></strong>?</p>
                <p class="text-danger mb-0"><small>Această acțiune nu poate fi anulată.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" id="deletePostId" value="">
                    <button type="submit" class="btn btn-danger">Șterge</button>
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

<?php include(__DIR__ . '/../includes/footer.php'); ?>
