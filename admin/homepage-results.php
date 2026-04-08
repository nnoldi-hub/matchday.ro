<?php
/**
 * Admin - Rezultate Homepage
 * MatchDay.ro - Manage featured match results for homepage
 */
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$success = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token invalid');
        }
        
        switch ($action) {
            case 'add':
                $homeTeam = trim($_POST['home_team'] ?? '');
                $awayTeam = trim($_POST['away_team'] ?? '');
                $homeScore = (int)($_POST['home_score'] ?? 0);
                $awayScore = (int)($_POST['away_score'] ?? 0);
                $competition = trim($_POST['competition'] ?? '');
                $postId = !empty($_POST['post_id']) ? (int)$_POST['post_id'] : null;
                
                // Process goal scorers
                $homeScorers = [];
                $awayScorers = [];
                
                if (!empty($_POST['home_scorer_name'])) {
                    foreach ($_POST['home_scorer_name'] as $i => $name) {
                        if (!empty(trim($name))) {
                            $homeScorers[] = [
                                'name' => trim($name),
                                'minute' => (int)($_POST['home_scorer_minute'][$i] ?? 0)
                            ];
                        }
                    }
                }
                
                if (!empty($_POST['away_scorer_name'])) {
                    foreach ($_POST['away_scorer_name'] as $i => $name) {
                        if (!empty(trim($name))) {
                            $awayScorers[] = [
                                'name' => trim($name),
                                'minute' => (int)($_POST['away_scorer_minute'][$i] ?? 0)
                            ];
                        }
                    }
                }
                
                if (empty($homeTeam) || empty($awayTeam)) {
                    throw new Exception('Echipele sunt obligatorii');
                }
                
                // Check if we already have 3 results
                $count = Database::fetchValue("SELECT COUNT(*) FROM featured_results WHERE active = 1");
                if ($count >= 3) {
                    throw new Exception('Poți avea maxim 3 rezultate pe homepage. Dezactivează unul pentru a adăuga altul.');
                }
                
                $sortOrder = Database::fetchValue("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM featured_results");
                
                Database::insert(
                    "INSERT INTO featured_results (home_team, away_team, home_score, away_score, competition, post_id, sort_order, active, home_scorers, away_scorers) 
                     VALUES (:home_team, :away_team, :home_score, :away_score, :competition, :post_id, :sort_order, 1, :home_scorers, :away_scorers)",
                    [
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'home_score' => $homeScore,
                        'away_score' => $awayScore,
                        'competition' => $competition,
                        'post_id' => $postId,
                        'sort_order' => $sortOrder,
                        'home_scorers' => !empty($homeScorers) ? json_encode($homeScorers) : null,
                        'away_scorers' => !empty($awayScorers) ? json_encode($awayScorers) : null
                    ]
                );
                $success = 'Rezultat adăugat cu succes!';
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $homeTeam = trim($_POST['home_team'] ?? '');
                $awayTeam = trim($_POST['away_team'] ?? '');
                $homeScore = (int)($_POST['home_score'] ?? 0);
                $awayScore = (int)($_POST['away_score'] ?? 0);
                $competition = trim($_POST['competition'] ?? '');
                $postId = !empty($_POST['post_id']) ? (int)$_POST['post_id'] : null;
                
                // Process goal scorers
                $homeScorers = [];
                $awayScorers = [];
                
                if (!empty($_POST['home_scorer_name'])) {
                    foreach ($_POST['home_scorer_name'] as $i => $name) {
                        if (!empty(trim($name))) {
                            $homeScorers[] = [
                                'name' => trim($name),
                                'minute' => (int)($_POST['home_scorer_minute'][$i] ?? 0)
                            ];
                        }
                    }
                }
                
                if (!empty($_POST['away_scorer_name'])) {
                    foreach ($_POST['away_scorer_name'] as $i => $name) {
                        if (!empty(trim($name))) {
                            $awayScorers[] = [
                                'name' => trim($name),
                                'minute' => (int)($_POST['away_scorer_minute'][$i] ?? 0)
                            ];
                        }
                    }
                }
                
                Database::execute(
                    "UPDATE featured_results SET home_team = :home_team, away_team = :away_team, 
                     home_score = :home_score, away_score = :away_score, competition = :competition, 
                     post_id = :post_id, home_scorers = :home_scorers, away_scorers = :away_scorers, 
                     updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                    [
                        'id' => $id,
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'home_score' => $homeScore,
                        'away_score' => $awayScore,
                        'competition' => $competition,
                        'post_id' => $postId,
                        'home_scorers' => !empty($homeScorers) ? json_encode($homeScorers) : null,
                        'away_scorers' => !empty($awayScorers) ? json_encode($awayScorers) : null
                    ]
                );
                $success = 'Rezultat actualizat!';
                break;
                
            case 'toggle':
                $id = (int)$_POST['id'];
                $current = Database::fetchValue("SELECT active FROM featured_results WHERE id = :id", ['id' => $id]);
                $newStatus = $current ? 0 : 1;
                
                // Check if activating and we already have 3
                if ($newStatus === 1) {
                    $count = Database::fetchValue("SELECT COUNT(*) FROM featured_results WHERE active = 1");
                    if ($count >= 3) {
                        throw new Exception('Poți avea maxim 3 rezultate active.');
                    }
                }
                
                Database::execute("UPDATE featured_results SET active = :active WHERE id = :id", 
                    ['id' => $id, 'active' => $newStatus]);
                $success = $newStatus ? 'Rezultat activat!' : 'Rezultat dezactivat!';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                Database::execute("DELETE FROM featured_results WHERE id = :id", ['id' => $id]);
                $success = 'Rezultat șters!';
                break;
                
            case 'reorder':
                $order = json_decode($_POST['order'] ?? '[]', true);
                foreach ($order as $index => $id) {
                    Database::execute("UPDATE featured_results SET sort_order = :sort WHERE id = :id",
                        ['id' => (int)$id, 'sort' => $index]);
                }
                $success = 'Ordine actualizată!';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all featured results
$results = Database::fetchAll(
    "SELECT fr.*, p.title as post_title, p.slug as post_slug 
     FROM featured_results fr 
     LEFT JOIN posts p ON fr.post_id = p.id 
     ORDER BY fr.sort_order ASC"
);

// Get all published posts for linking
$posts = Post::getPublished(1, 100);

$pageTitle = 'Rezultate Homepage';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-futbol me-2"></i>Rezultate Homepage</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResultModal">
        <i class="fas fa-plus me-1"></i>Adaugă rezultat
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Info Card -->
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Maxim 3 rezultate active</strong> pot fi afișate pe homepage în secțiunea "Rezultate importante".
    Trage pentru a reordona.
</div>

<!-- Results List -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-list me-2"></i>Rezultate configurate</strong>
        <span class="badge bg-primary"><?= count(array_filter($results, fn($r) => $r['active'])) ?>/3 active</span>
    </div>
    
    <?php if (empty($results)): ?>
    <div class="p-5 text-center text-muted">
        <i class="fas fa-inbox fa-3x mb-3"></i>
        <p>Nu există rezultate configurate.</p>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResultModal">
            <i class="fas fa-plus me-1"></i>Adaugă primul rezultat
        </button>
    </div>
    <?php else: ?>
    <div class="list-group list-group-flush" id="resultsList">
        <?php foreach ($results as $result): ?>
        <div class="list-group-item d-flex align-items-center gap-3 <?= !$result['active'] ? 'opacity-50' : '' ?>" 
             data-id="<?= $result['id'] ?>">
            
            <!-- Drag Handle -->
            <div class="drag-handle text-muted" style="cursor: grab;">
                <i class="fas fa-grip-vertical"></i>
            </div>
            
            <!-- Match Info -->
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <strong><?= htmlspecialchars($result['home_team']) ?></strong>
                    <span class="badge bg-<?= $result['home_score'] > $result['away_score'] ? 'success' : 'secondary' ?>">
                        <?= $result['home_score'] ?>
                    </span>
                    <span class="text-muted">-</span>
                    <span class="badge bg-<?= $result['away_score'] > $result['home_score'] ? 'success' : 'secondary' ?>">
                        <?= $result['away_score'] ?>
                    </span>
                    <strong><?= htmlspecialchars($result['away_team']) ?></strong>
                    
                    <?php if (!$result['active']): ?>
                    <span class="badge bg-warning text-dark ms-2">Inactiv</span>
                    <?php endif; ?>
                </div>
                <?php 
                $homeScorers = !empty($result['home_scorers']) ? json_decode($result['home_scorers'], true) : [];
                $awayScorers = !empty($result['away_scorers']) ? json_decode($result['away_scorers'], true) : [];
                ?>
                <?php if (!empty($homeScorers) || !empty($awayScorers)): ?>
                <div class="small text-success mb-1">
                    <i class="fas fa-futbol me-1"></i>
                    <?php if (!empty($homeScorers)): ?>
                        <?php foreach ($homeScorers as $i => $s): ?>
                            <?= htmlspecialchars($s['name']) ?><?= !empty($s['minute']) ? " ({$s['minute']}')" : '' ?><?= $i < count($homeScorers) - 1 ? ', ' : '' ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($homeScorers) && !empty($awayScorers)): ?> | <?php endif; ?>
                    <?php if (!empty($awayScorers)): ?>
                        <?php foreach ($awayScorers as $i => $s): ?>
                            <?= htmlspecialchars($s['name']) ?><?= !empty($s['minute']) ? " ({$s['minute']}')" : '' ?><?= $i < count($awayScorers) - 1 ? ', ' : '' ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="small text-muted">
                    <?php if ($result['competition']): ?>
                    <i class="fas fa-trophy me-1"></i><?= htmlspecialchars($result['competition']) ?>
                    <?php endif; ?>
                    <?php if ($result['post_title']): ?>
                    <span class="ms-2">
                        <i class="fas fa-link me-1"></i>
                        <a href="../post.php?slug=<?= urlencode($result['post_slug']) ?>" target="_blank">
                            <?= htmlspecialchars(mb_substr($result['post_title'], 0, 40)) ?>...
                        </a>
                    </span>
                    <?php else: ?>
                    <span class="ms-2 text-warning"><i class="fas fa-unlink me-1"></i>Fără articol legat</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="editResult(<?= htmlspecialchars(json_encode($result)) ?>)">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $result['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-<?= $result['active'] ? 'warning' : 'success' ?>"
                            title="<?= $result['active'] ? 'Dezactivează' : 'Activează' ?>">
                        <i class="fas fa-<?= $result['active'] ? 'eye-slash' : 'eye' ?>"></i>
                    </button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Sigur vrei să ștergi acest rezultat?')">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $result['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Result Modal -->
<div class="modal fade" id="addResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Adaugă rezultat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <label class="form-label">Echipa gazdă *</label>
                            <input type="text" name="home_team" class="form-control" required 
                                   placeholder="Ex: FCSB">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Scor</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" name="home_score" class="form-control text-center fw-bold score-input" 
                                       min="0" max="99" value="0">
                                <span class="fw-bold">-</span>
                                <input type="number" name="away_score" class="form-control text-center fw-bold score-input" 
                                       min="0" max="99" value="0">
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label">Echipa oaspete *</label>
                            <input type="text" name="away_team" class="form-control" required
                                   placeholder="Ex: CFR Cluj">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Competiție</label>
                            <input type="text" name="competition" class="form-control" 
                                   placeholder="Ex: Liga 1 • Etapa 3 Play-off">
                        </div>
                        
                        <!-- Home Team Scorers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fas fa-futbol me-1"></i>Marcatori gazdă</label>
                            <div id="add_home_scorers_list">
                                <!-- Scorers will be added here dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addScorer('add_home_scorers_list', 'home')">
                                <i class="fas fa-plus me-1"></i>Adaugă marcator
                            </button>
                        </div>
                        
                        <!-- Away Team Scorers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fas fa-futbol me-1"></i>Marcatori oaspete</label>
                            <div id="add_away_scorers_list">
                                <!-- Scorers will be added here dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addScorer('add_away_scorers_list', 'away')">
                                <i class="fas fa-plus me-1"></i>Adaugă marcator
                            </button>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Legă cu un articol (opțional)</label>
                            <select name="post_id" class="form-select">
                                <option value="">-- Fără articol --</option>
                                <?php foreach ($posts as $post): ?>
                                <option value="<?= $post['id'] ?>">
                                    <?= htmlspecialchars(mb_substr($post['title'], 0, 60)) ?>
                                    (<?= date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Adaugă
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Result Modal -->
<div class="modal fade" id="editResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="editForm">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editează rezultat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <label class="form-label">Echipa gazdă *</label>
                            <input type="text" name="home_team" id="edit_home_team" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Scor</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" name="home_score" id="edit_home_score" 
                                       class="form-control text-center fw-bold score-input" min="0" max="99">
                                <span class="fw-bold">-</span>
                                <input type="number" name="away_score" id="edit_away_score" 
                                       class="form-control text-center fw-bold score-input" min="0" max="99">
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label">Echipa oaspete *</label>
                            <input type="text" name="away_team" id="edit_away_team" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Competiție</label>
                            <input type="text" name="competition" id="edit_competition" class="form-control">
                        </div>
                        
                        <!-- Home Team Scorers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fas fa-futbol me-1"></i>Marcatori gazdă</label>
                            <div id="edit_home_scorers_list">
                                <!-- Scorers will be loaded dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addScorer('edit_home_scorers_list', 'home')">
                                <i class="fas fa-plus me-1"></i>Adaugă marcator
                            </button>
                        </div>
                        
                        <!-- Away Team Scorers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label"><i class="fas fa-futbol me-1"></i>Marcatori oaspete</label>
                            <div id="edit_away_scorers_list">
                                <!-- Scorers will be loaded dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addScorer('edit_away_scorers_list', 'away')">
                                <i class="fas fa-plus me-1"></i>Adaugă marcator
                            </button>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Legă cu un articol</label>
                            <select name="post_id" id="edit_post_id" class="form-select">
                                <option value="">-- Fără articol --</option>
                                <?php foreach ($posts as $post): ?>
                                <option value="<?= $post['id'] ?>">
                                    <?= htmlspecialchars(mb_substr($post['title'], 0, 60)) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Score input styling */
.score-input {
    width: 60px !important;
    height: 40px !important;
    font-size: 1.2rem !important;
    color: #000 !important;
    background-color: #fff !important;
    padding: 5px !important;
    -moz-appearance: textfield;
}
.score-input::-webkit-outer-spin-button,
.score-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
/* Scorer row styling */
.scorer-row {
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
// Add scorer input row
function addScorer(containerId, team) {
    const container = document.getElementById(containerId);
    const row = document.createElement('div');
    row.className = 'scorer-row d-flex align-items-center gap-2 mb-2';
    row.innerHTML = `
        <input type="text" name="${team}_scorer_name[]" class="form-control form-control-sm" 
               placeholder="Nume jucător" style="flex: 1;">
        <input type="number" name="${team}_scorer_minute[]" class="form-control form-control-sm text-center" 
               placeholder="Min" min="1" max="120" style="width: 70px;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.scorer-row').remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(row);
}

// Load scorers into container
function loadScorers(containerId, team, scorers) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    if (scorers && Array.isArray(scorers)) {
        scorers.forEach(scorer => {
            const row = document.createElement('div');
            row.className = 'scorer-row d-flex align-items-center gap-2 mb-2';
            row.innerHTML = `
                <input type="text" name="${team}_scorer_name[]" class="form-control form-control-sm" 
                       placeholder="Nume jucător" value="${escapeHtml(scorer.name || '')}" style="flex: 1;">
                <input type="number" name="${team}_scorer_minute[]" class="form-control form-control-sm text-center" 
                       placeholder="Min" min="1" max="120" value="${scorer.minute || ''}" style="width: 70px;">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.scorer-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(row);
        });
    }
}

// Escape HTML function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function editResult(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_home_team').value = data.home_team;
    document.getElementById('edit_away_team').value = data.away_team;
    document.getElementById('edit_home_score').value = data.home_score;
    document.getElementById('edit_away_score').value = data.away_score;
    document.getElementById('edit_competition').value = data.competition || '';
    document.getElementById('edit_post_id').value = data.post_id || '';
    
    // Load scorers
    const homeScorers = data.home_scorers ? (typeof data.home_scorers === 'string' ? JSON.parse(data.home_scorers) : data.home_scorers) : [];
    const awayScorers = data.away_scorers ? (typeof data.away_scorers === 'string' ? JSON.parse(data.away_scorers) : data.away_scorers) : [];
    
    loadScorers('edit_home_scorers_list', 'home', homeScorers);
    loadScorers('edit_away_scorers_list', 'away', awayScorers);
    
    new bootstrap.Modal(document.getElementById('editResultModal')).show();
}

// Clear add modal when opened
document.getElementById('addResultModal').addEventListener('show.bs.modal', function() {
    document.getElementById('add_home_scorers_list').innerHTML = '';
    document.getElementById('add_away_scorers_list').innerHTML = '';
});

// Simple drag & drop reordering
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('resultsList');
    if (!list) return;
    
    let draggedItem = null;
    
    list.querySelectorAll('.list-group-item').forEach(item => {
        item.setAttribute('draggable', 'true');
        
        item.addEventListener('dragstart', function() {
            draggedItem = this;
            this.classList.add('dragging');
        });
        
        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            saveOrder();
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (this !== draggedItem) {
                const rect = this.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;
                if (e.clientY < midY) {
                    this.parentNode.insertBefore(draggedItem, this);
                } else {
                    this.parentNode.insertBefore(draggedItem, this.nextSibling);
                }
            }
        });
    });
    
    function saveOrder() {
        const items = list.querySelectorAll('.list-group-item');
        const order = Array.from(items).map(item => item.dataset.id);
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(order)) + 
                  '&csrf_token=<?= Security::generateCSRFToken() ?>'
        });
    }
});
</script>

<style>
.dragging { opacity: 0.5; background: #f0f0f0; }
.drag-handle:hover { color: var(--brand) !important; }
</style>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
