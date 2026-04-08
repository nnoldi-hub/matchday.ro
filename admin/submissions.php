<?php
/**
 * Admin Submissions Management
 * MatchDay.ro - Review and manage article submissions
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/../includes/Submission.php');
require_once(__DIR__ . '/../includes/Category.php');
require_once(__DIR__ . '/../includes/Logger.php');

// Check admin access
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    header('Location: login.php');
    exit;
}

$user = User::getById($_SESSION['user_id']);
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $submissionId = (int)($_POST['submission_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'approve':
                Submission::updateStatus($submissionId, Submission::STATUS_APPROVED, $_SESSION['user_id']);
                
                // Notify contributor
                $submission = Submission::getById($submissionId);
                Submission::notifyContributor($submission, 'approved');
                
                Logger::audit('SUBMISSION_APPROVE', $_SESSION['user_id'], [
                    'submission_id' => $submissionId,
                    'title' => $submission['title'] ?? ''
                ]);
                
                $message = 'Articolul a fost aprobat!';
                $messageType = 'success';
                break;
                
            case 'reject':
                $feedback = $_POST['feedback'] ?? '';
                Submission::updateStatus($submissionId, Submission::STATUS_REJECTED, $_SESSION['user_id'], $feedback);
                
                // Notify contributor
                $submission = Submission::getById($submissionId);
                Submission::notifyContributor($submission, 'rejected');
                
                Logger::audit('SUBMISSION_REJECT', $_SESSION['user_id'], [
                    'submission_id' => $submissionId,
                    'feedback' => substr($feedback, 0, 100)
                ]);
                
                $message = 'Articolul a fost respins.';
                $messageType = 'warning';
                break;
                
            case 'publish':
                $authorId = (int)($_POST['author_id'] ?? $_SESSION['user_id']);
                $postId = Submission::publish($submissionId, $authorId);
                
                if ($postId) {
                    Logger::audit('SUBMISSION_PUBLISH', $_SESSION['user_id'], [
                        'submission_id' => $submissionId,
                        'post_id' => $postId
                    ]);
                    $message = 'Articolul a fost publicat cu succes!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Eroare la publicare');
                }
                break;
                
            case 'delete':
                Submission::delete($submissionId);
                Logger::audit('SUBMISSION_DELETE', $_SESSION['user_id'], [
                    'submission_id' => $submissionId
                ]);
                $message = 'Articolul a fost șters.';
                $messageType = 'info';
                break;
                
            case 'start_review':
                Submission::updateStatus($submissionId, Submission::STATUS_REVIEWING, $_SESSION['user_id']);
                $message = 'Ai început revizuirea articolului.';
                $messageType = 'info';
                break;
        }
    } catch (Exception $e) {
        $message = 'Eroare: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($searchQuery) $filters['search'] = $searchQuery;

$submissions = Submission::getAll($filters, $limit, $offset);
$totalSubmissions = Submission::count($filters);
$totalPages = ceil($totalSubmissions / $limit);

// Get counts by status
$pendingCount = Submission::count(['status' => 'pending']);
$reviewingCount = Submission::count(['status' => 'reviewing']);
$approvedCount = Submission::count(['status' => 'approved']);

$categories = Category::getAll();
$authors = User::getByRole('editor');

// View specific submission
$viewSubmission = null;
if (isset($_GET['view'])) {
    $viewSubmission = Submission::getById((int)$_GET['view']);
}

$pageTitle = 'Contribuții - Admin';
require_once('admin-header.php');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-inbox me-2"></i>Contribuții Externe</h1>
                <div>
                    <span class="badge bg-warning fs-6"><?= $pendingCount ?> în așteptare</span>
                    <span class="badge bg-info fs-6 ms-2"><?= $reviewingCount ?> în revizuire</span>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($viewSubmission): ?>
    <!-- Single Submission View -->
    <div class="row">
        <div class="col-12">
            <a href="submissions.php" class="btn btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-1"></i>Înapoi la listă
            </a>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><?= htmlspecialchars($viewSubmission['title']) ?></h4>
                        <small class="text-muted">
                            de <?= htmlspecialchars($viewSubmission['author_name']) ?> 
                            • <?= date('d.m.Y H:i', strtotime($viewSubmission['created_at'])) ?>
                        </small>
                    </div>
                    <span class="badge bg-<?= getStatusBadge($viewSubmission['status']) ?> fs-6">
                        <?= getStatusLabel($viewSubmission['status']) ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <!-- Author Info -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted d-block">Email</small>
                                <a href="mailto:<?= htmlspecialchars($viewSubmission['author_email']) ?>">
                                    <?= htmlspecialchars($viewSubmission['author_email']) ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted d-block">Categorie</small>
                                <?= htmlspecialchars($viewSubmission['category_name'] ?? 'Necategorizat') ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted d-block">Token</small>
                                <code class="small"><?= $viewSubmission['token'] ?></code>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($viewSubmission['author_bio']): ?>
                    <div class="bg-light p-3 rounded mb-4">
                        <small class="text-muted d-block">Bio autor</small>
                        <?= htmlspecialchars($viewSubmission['author_bio']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($viewSubmission['featured_image']): ?>
                    <div class="mb-4">
                        <img src="<?= htmlspecialchars($viewSubmission['featured_image']) ?>" 
                             alt="Featured" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($viewSubmission['excerpt']): ?>
                    <div class="mb-4">
                        <h6 class="text-muted">Rezumat</h6>
                        <p class="lead fst-italic"><?= htmlspecialchars($viewSubmission['excerpt']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h6 class="text-muted">Conținut</h6>
                        <div class="article-content border p-4 rounded bg-white">
                            <?= nl2br(htmlspecialchars($viewSubmission['content'])) ?>
                        </div>
                        <small class="text-muted">
                            <?= str_word_count($viewSubmission['content']) ?> cuvinte
                        </small>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <!-- Action Forms -->
                            <?php if (in_array($viewSubmission['status'], ['pending', 'reviewing'])): ?>
                            <div class="btn-group me-2">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="submission_id" value="<?= $viewSubmission['id'] ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-lg me-1"></i>Aprobă
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="bi bi-x-lg me-1"></i>Respinge
                                </button>
                            </div>
                            
                            <?php if ($viewSubmission['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="start_review">
                                <input type="hidden" name="submission_id" value="<?= $viewSubmission['id'] ?>">
                                <button type="submit" class="btn btn-outline-info">
                                    <i class="bi bi-eye me-1"></i>Începe Revizuirea
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($viewSubmission['status'] === 'approved'): ?>
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#publishModal">
                                <i class="bi bi-globe me-1"></i>Publică Acum
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <form method="POST" class="d-inline" onsubmit="return confirm('Sigur vrei să ștergi acest articol?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="submission_id" value="<?= $viewSubmission['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Șterge
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="submission_id" value="<?= $viewSubmission['id'] ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Respinge Articolul</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Feedback pentru autor (opțional)</label>
                            <textarea name="feedback" class="form-control" rows="4" 
                                      placeholder="Explică de ce articolul nu poate fi publicat..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                        <button type="submit" class="btn btn-warning">Respinge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Publish Modal -->
    <div class="modal fade" id="publishModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="publish">
                    <input type="hidden" name="submission_id" value="<?= $viewSubmission['id'] ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Publică Articolul</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Atribuie unui autor din echipă</label>
                            <select name="author_id" class="form-select">
                                <option value="<?= $_SESSION['user_id'] ?>">Eu (<?= $user['username'] ?>)</option>
                                <?php foreach ($authors as $author): ?>
                                <?php if ($author['id'] != $_SESSION['user_id']): ?>
                                <option value="<?= $author['id'] ?>"><?= htmlspecialchars($author['username']) ?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                Contribuitorul "<?= htmlspecialchars($viewSubmission['author_name']) ?>" va fi menționat în articol.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-globe me-1"></i>Publică
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Submissions List -->
    <div class="row">
        <div class="col-12">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Toate</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>În Așteptare</option>
                                <option value="reviewing" <?= $statusFilter === 'reviewing' ? 'selected' : '' ?>>În Revizuire</option>
                                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Aprobate</option>
                                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Respinse</option>
                                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Publicate</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Caută</label>
                            <input type="text" name="search" class="form-control" 
                                   value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Titlu sau autor...">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-filter me-1"></i>Filtrează
                            </button>
                            <a href="submissions.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Titlu</th>
                                <th>Autor</th>
                                <th>Categorie</th>
                                <th>Status</th>
                                <th>Trimis</th>
                                <th class="text-end">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Nu sunt contribuții.
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td>
                                    <a href="?view=<?= $sub['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars(mb_substr($sub['title'], 0, 60)) ?>
                                        <?= mb_strlen($sub['title']) > 60 ? '...' : '' ?>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($sub['author_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($sub['author_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($sub['category_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadge($sub['status']) ?>">
                                        <?= getStatusLabel($sub['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= date('d.m.Y', strtotime($sub['created_at'])) ?></small>
                                    <br>
                                    <small class="text-muted"><?= date('H:i', strtotime($sub['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="?view=<?= $sub['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> Vezi
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $statusFilter ?>&search=<?= urlencode($searchQuery) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
function getStatusBadge($status) {
    return match($status) {
        'pending' => 'warning',
        'reviewing' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'published' => 'primary',
        default => 'secondary'
    };
}

function getStatusLabel($status) {
    return match($status) {
        'pending' => 'În Așteptare',
        'reviewing' => 'În Revizuire',
        'approved' => 'Aprobat',
        'rejected' => 'Respins',
        'published' => 'Publicat',
        default => ucfirst($status)
    };
}

require_once('admin-footer.php');
?>
