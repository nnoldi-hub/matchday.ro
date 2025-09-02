<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

// Check if user is logged in
if (!isset($_SESSION['david_logged']) || !$_SESSION['david_logged']) {
    header('Location: login.php');
    exit;
}

// Funcție pentru citirea planului editorial
function getEditorialPlan() {
    $planFile = __DIR__ . '/../data/editorial-plan.json';
    
    if (!file_exists($planFile)) {
        // Creează planul inițial dacă nu există
        $initialPlan = generateInitialPlan();
        file_put_contents($planFile, json_encode($initialPlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $initialPlan;
    }
    
    $content = file_get_contents($planFile);
    return json_decode($content, true) ?: [];
}

// Funcție pentru salvarea planului editorial
function saveEditorialPlan($plan) {
    $planFile = __DIR__ . '/../data/editorial-plan.json';
    return file_put_contents($planFile, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Generează planul inițial
function generateInitialPlan() {
    $plan = [];
    $startDate = new DateTime('2025-09-01');
    
    for ($i = 0; $i < 28; $i++) { // 4 săptămâni
        $currentDate = clone $startDate;
        $currentDate->add(new DateInterval('P' . $i . 'D'));
        
        $dayName = $currentDate->format('l');
        $dayNameRo = [
            'Monday' => 'Luni',
            'Tuesday' => 'Marți', 
            'Wednesday' => 'Miercuri',
            'Thursday' => 'Joi',
            'Friday' => 'Vineri',
            'Saturday' => 'Sâmbătă',
            'Sunday' => 'Duminică'
        ][$dayName];
        
        $contentTypes = [
            'Luni' => 'Rezumatul săptămânii',
            'Marți' => 'Analize tactice',
            'Miercuri' => 'Interviuri & Reportaje',
            'Joi' => 'Știri & Transferuri',
            'Vineri' => 'Avanpremiere weekend',
            'Sâmbătă' => 'Live Updates & Cronici',
            'Duminică' => 'Cronici & Reacții'
        ];
        
        $plan[] = [
            'id' => 'article_' . $currentDate->format('Y_m_d'),
            'date' => $currentDate->format('Y-m-d'),
            'day_name' => $dayNameRo,
            'content_type' => $contentTypes[$dayNameRo],
            'title' => '',
            'description' => '',
            'status' => 'planned', // planned, in_progress, review, published
            'priority' => 'normal', // high, normal, low
            'author' => 'David Cocioabă',
            'category' => '',
            'tags' => [],
            'notes' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return $plan;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $plan = getEditorialPlan();
    
    switch ($action) {
        case 'update_article':
            $articleId = $_POST['article_id'] ?? '';
            $field = $_POST['field'] ?? '';
            $value = $_POST['value'] ?? '';
            
            foreach ($plan as &$article) {
                if ($article['id'] === $articleId) {
                    if ($field === 'tags') {
                        $article[$field] = array_filter(explode(',', $value));
                    } else {
                        $article[$field] = $value;
                    }
                    $article['updated_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            
            if (saveEditorialPlan($plan)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Nu s-a putut salva planul']);
            }
            exit;
            
        case 'get_article':
            $articleId = $_POST['article_id'] ?? '';
            
            foreach ($plan as $article) {
                if ($article['id'] === $articleId) {
                    echo json_encode(['success' => true, 'article' => $article]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'error' => 'Articolul nu a fost găsit']);
            exit;
            
        case 'save_article':
            $articleId = $_POST['article_id'] ?? '';
            $updates = json_decode($_POST['data'] ?? '{}', true);
            
            foreach ($plan as &$article) {
                if ($article['id'] === $articleId) {
                    foreach ($updates as $field => $value) {
                        if ($field === 'tags') {
                            $article[$field] = is_array($value) ? $value : array_filter(explode(',', $value));
                        } else {
                            $article[$field] = $value;
                        }
                    }
                    $article['updated_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            
            if (saveEditorialPlan($plan)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Nu s-a putut salva articolul']);
            }
            exit;
            
        case 'get_stats':
            $stats = [
                'total' => count($plan),
                'planned' => 0,
                'in_progress' => 0,
                'review' => 0,
                'published' => 0
            ];
            
            foreach ($plan as $article) {
                $status = $article['status'] ?? 'planned';
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
            
            echo json_encode($stats);
            exit;
    }
}

$editorialPlan = getEditorialPlan();
$admin = true;
$pageTitle = 'Management Editorial - Admin MatchDay.ro';

include(__DIR__ . '/../includes/header.php');
?>

<style>
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.priority-high { border-left: 4px solid #dc3545; }
.priority-normal { border-left: 4px solid #6c757d; }
.priority-low { border-left: 4px solid #28a745; }
.editable { cursor: pointer; border-bottom: 1px dashed #dee2e6; }
.editable:hover { background-color: #f8f9fa; }
</style>

<main class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Management Editorial
                    </h1>
                    <p class="text-muted mb-0">Urmărește și gestionează planul editorial pentru MatchDay.ro</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshStats()">
                        <i class="fas fa-sync me-1"></i>Refresh
                    </button>
                    <button class="btn btn-primary" onclick="exportPlan()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4" id="statsCards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h2 text-primary mb-1" id="stat-total">-</div>
                    <small class="text-muted">Total articole</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h2 text-warning mb-1" id="stat-planned">-</div>
                    <small class="text-muted">Planificate</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h2 text-info mb-1" id="stat-in_progress">-</div>
                    <small class="text-muted">În progres</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h2 text-success mb-1" id="stat-published">-</div>
                    <small class="text-muted">Publicate</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Editorial Plan Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="h6 mb-0">
                        <i class="fas fa-list me-2"></i>
                        Planul Editorial - Următoarele 4 săptămâni
                    </h4>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="filterStatus" onchange="filterTable()">
                            <option value="">Toate statusurile</option>
                            <option value="planned">Planificat</option>
                            <option value="in_progress">În progres</option>
                            <option value="review">Review</option>
                            <option value="published">Publicat</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="editorialTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">Data</th>
                                    <th style="width: 80px;">Zi</th>
                                    <th style="width: 150px;">Tip conținut</th>
                                    <th>Titlu articol</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 80px;">Prioritate</th>
                                    <th style="width: 120px;">Categorie</th>
                                    <th style="width: 100px;">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($editorialPlan as $article): ?>
                                <tr class="article-row priority-<?= $article['priority'] ?>" data-article-id="<?= $article['id'] ?>">
                                    <td class="small text-muted">
                                        <?= date('d.m', strtotime($article['date'])) ?>
                                    </td>
                                    <td>
                                        <small class="fw-medium"><?= $article['day_name'] ?></small>
                                    </td>
                                    <td>
                                        <small class="text-primary"><?= $article['content_type'] ?></small>
                                    </td>
                                    <td>
                                        <div class="editable" data-field="title" data-article="<?= $article['id'] ?>">
                                            <?= !empty($article['title']) ? htmlspecialchars($article['title']) : '<em class="text-muted">Click pentru a adăuga titlu...</em>' ?>
                                        </div>
                                        <?php if (!empty($article['description'])): ?>
                                        <small class="text-muted d-block mt-1 editable" data-field="description" data-article="<?= $article['id'] ?>">
                                            <?= htmlspecialchars($article['description']) ?>
                                        </small>
                                        <?php else: ?>
                                        <small class="text-muted d-block mt-1 editable" data-field="description" data-article="<?= $article['id'] ?>">
                                            <em>Click pentru descriere...</em>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" data-field="status" data-article="<?= $article['id'] ?>">
                                            <option value="planned" <?= $article['status'] === 'planned' ? 'selected' : '' ?>>Planificat</option>
                                            <option value="in_progress" <?= $article['status'] === 'in_progress' ? 'selected' : '' ?>>În progres</option>
                                            <option value="review" <?= $article['status'] === 'review' ? 'selected' : '' ?>>Review</option>
                                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Publicat</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm priority-select" data-field="priority" data-article="<?= $article['id'] ?>">
                                            <option value="low" <?= $article['priority'] === 'low' ? 'selected' : '' ?>>Scăzută</option>
                                            <option value="normal" <?= $article['priority'] === 'normal' ? 'selected' : '' ?>>Normală</option>
                                            <option value="high" <?= $article['priority'] === 'high' ? 'selected' : '' ?>>Înaltă</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="editable small" data-field="category" data-article="<?= $article['id'] ?>">
                                            <?= !empty($article['category']) ? htmlspecialchars($article['category']) : '<em class="text-muted">-</em>' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editArticle('<?= $article['id'] ?>')" title="Editează">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" onclick="viewDetails('<?= $article['id'] ?>')" title="Detalii">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editează articol
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editArticleId">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-heading me-1"></i>Titlu articol
                                </label>
                                <input type="text" class="form-control" id="editTitle" 
                                       placeholder="Introduce titlul articolului...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-flag me-1"></i>Status
                                </label>
                                <select class="form-select" id="editStatus">
                                    <option value="planned">Planificat</option>
                                    <option value="in_progress">În progres</option>
                                    <option value="review">Review</option>
                                    <option value="published">Publicat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-align-left me-1"></i>Descriere
                        </label>
                        <textarea class="form-control" id="editDescription" rows="4" 
                                  placeholder="Descrie pe scurt conținutul articolului..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-folder me-1"></i>Categorie
                                </label>
                                <select class="form-select" id="editCategory">
                                    <option value="">Selectează categoria</option>
                                    <option value="opinii">Opinii</option>
                                    <option value="analize">Analize</option>
                                    <option value="interviuri">Interviuri</option>
                                    <option value="reportaje">Reportaje</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="nacional">Fotbal Național</option>
                                    <option value="international">Fotbal Internațional</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-exclamation-circle me-1"></i>Prioritate
                                </label>
                                <select class="form-select" id="editPriority">
                                    <option value="low">Scăzută</option>
                                    <option value="normal">Normală</option>
                                    <option value="high">Înaltă</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1"></i>Autor
                                </label>
                                <input type="text" class="form-control" id="editAuthor" 
                                       value="David Cocioabă" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-tags me-1"></i>Tags
                            <small class="text-muted">(separate prin virgulă)</small>
                        </label>
                        <input type="text" class="form-control" id="editTags" 
                               placeholder="ex: analiza, transferuri, liga 1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sticky-note me-1"></i>Note personale
                        </label>
                        <textarea class="form-control" id="editNotes" rows="3" 
                                  placeholder="Adaugă note pentru tine sau echipă..."></textarea>
                    </div>
                    
                    <!-- Info display -->
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Data publicării: <span id="editDateDisplay"></span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Tip conținut: <span id="editContentType"></span>
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Anulează
                </button>
                <button type="button" class="btn btn-primary" onclick="saveArticle()">
                    <i class="fas fa-save me-1"></i>Salvează modificările
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Detalii articol
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h4 id="detailTitle" class="text-primary"></h4>
                        <p id="detailDescription" class="text-muted"></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="detailStatus" class="badge"></span>
                        <br><small id="detailPriority" class="text-muted"></small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="fw-medium"><i class="fas fa-calendar me-1"></i>Data:</td>
                                <td id="detailDate"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-calendar-week me-1"></i>Ziua:</td>
                                <td id="detailDay"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-file-alt me-1"></i>Tip conținut:</td>
                                <td id="detailContentTypeValue"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-folder me-1"></i>Categorie:</td>
                                <td id="detailCategoryValue"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="fw-medium"><i class="fas fa-user me-1"></i>Autor:</td>
                                <td id="detailAuthorValue"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-clock me-1"></i>Creat:</td>
                                <td id="detailCreated"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-edit me-1"></i>Actualizat:</td>
                                <td id="detailUpdated"></td>
                            </tr>
                            <tr>
                                <td class="fw-medium"><i class="fas fa-tags me-1"></i>Tags:</td>
                                <td id="detailTagsValue"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3" id="detailNotesSection" style="display: none;">
                    <h6 class="fw-medium"><i class="fas fa-sticky-note me-1"></i>Note:</h6>
                    <div class="p-3 bg-light rounded">
                        <p id="detailNotesValue" class="mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Închide
                </button>
                <button type="button" class="btn btn-primary" onclick="openEditFromDetails()">
                    <i class="fas fa-edit me-1"></i>Editează
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load stats on page load
document.addEventListener('DOMContentLoaded', function() {
    refreshStats();
});

// Refresh statistics
function refreshStats() {
    fetch('editorial-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_stats'
    })
    .then(response => response.json())
    .then(stats => {
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-planned').textContent = stats.planned;
        document.getElementById('stat-in_progress').textContent = stats.in_progress;
        document.getElementById('stat-published').textContent = stats.published;
    });
}

// Handle editable fields
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('editable')) {
        const field = e.target.dataset.field;
        const articleId = e.target.dataset.article;
        const currentValue = e.target.textContent.trim();
        
        if (currentValue.includes('Click pentru') || currentValue === '-') {
            e.target.textContent = '';
        }
        
        e.target.contentEditable = true;
        e.target.focus();
        
        e.target.addEventListener('blur', function() {
            const newValue = this.textContent.trim();
            this.contentEditable = false;
            
            updateField(articleId, field, newValue);
        });
        
        e.target.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    }
});

// Handle select changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('status-select') || e.target.classList.contains('priority-select')) {
        const field = e.target.dataset.field;
        const articleId = e.target.dataset.article;
        const value = e.target.value;
        
        updateField(articleId, field, value);
        
        if (field === 'priority') {
            const row = e.target.closest('.article-row');
            row.className = row.className.replace(/priority-\w+/, 'priority-' + value);
        }
    }
});

// Update field via AJAX
function updateField(articleId, field, value) {
    fetch('editorial-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_article&article_id=${articleId}&field=${field}&value=${encodeURIComponent(value)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            refreshStats();
        } else {
            alert('Eroare la salvare: ' + result.error);
        }
    });
}

// Filter table by status
function filterTable() {
    const filterValue = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('.article-row');
    
    rows.forEach(row => {
        if (!filterValue) {
            row.style.display = '';
        } else {
            const select = row.querySelector('.status-select');
            const currentStatus = select.value;
            row.style.display = currentStatus === filterValue ? '' : 'none';
        }
    });
}

// Edit article - Load data and show modal
function editArticle(articleId) {
    fetch('editorial-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_article&article_id=${articleId}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const article = result.article;
            
            // Populate edit form
            document.getElementById('editArticleId').value = article.id;
            document.getElementById('editTitle').value = article.title || '';
            document.getElementById('editDescription').value = article.description || '';
            document.getElementById('editCategory').value = article.category || '';
            document.getElementById('editStatus').value = article.status || 'planned';
            document.getElementById('editPriority').value = article.priority || 'normal';
            document.getElementById('editAuthor').value = article.author || 'David Cocioabă';
            document.getElementById('editTags').value = Array.isArray(article.tags) ? article.tags.join(', ') : '';
            document.getElementById('editNotes').value = article.notes || '';
            
            // Update display info
            document.getElementById('editDateDisplay').textContent = formatDate(article.date);
            document.getElementById('editContentType').textContent = article.content_type || '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        } else {
            alert('Eroare la încărcarea articolului: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la încărcarea datelor');
    });
}

// View article details
function viewDetails(articleId) {
    fetch('editorial-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_article&article_id=${articleId}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const article = result.article;
            
            // Populate details modal
            document.getElementById('detailTitle').textContent = article.title || 'Fără titlu';
            document.getElementById('detailDescription').textContent = article.description || 'Fără descriere';
            
            // Status badge
            const statusBadge = document.getElementById('detailStatus');
            const statusColors = {
                'planned': 'bg-warning',
                'in_progress': 'bg-info', 
                'review': 'bg-primary',
                'published': 'bg-success'
            };
            const statusTexts = {
                'planned': 'Planificat',
                'in_progress': 'În progres',
                'review': 'Review',
                'published': 'Publicat'
            };
            statusBadge.className = `badge ${statusColors[article.status] || 'bg-secondary'}`;
            statusBadge.textContent = statusTexts[article.status] || article.status;
            
            // Priority
            const priorityTexts = {
                'low': 'Prioritate scăzută',
                'normal': 'Prioritate normală',
                'high': 'Prioritate înaltă'
            };
            document.getElementById('detailPriority').textContent = priorityTexts[article.priority] || 'Normal';
            
            // Details table
            document.getElementById('detailDate').textContent = formatDate(article.date);
            document.getElementById('detailDay').textContent = article.day_name || '';
            document.getElementById('detailContentTypeValue').textContent = article.content_type || '';
            document.getElementById('detailCategoryValue').textContent = article.category || 'Necategorizat';
            document.getElementById('detailAuthorValue').textContent = article.author || 'David Cocioabă';
            document.getElementById('detailCreated').textContent = formatDateTime(article.created_at);
            document.getElementById('detailUpdated').textContent = formatDateTime(article.updated_at);
            
            // Tags
            const tagsText = Array.isArray(article.tags) && article.tags.length > 0 
                ? article.tags.join(', ') 
                : 'Fără tags';
            document.getElementById('detailTagsValue').textContent = tagsText;
            
            // Notes section
            const notesSection = document.getElementById('detailNotesSection');
            const notesValue = document.getElementById('detailNotesValue');
            if (article.notes && article.notes.trim()) {
                notesValue.textContent = article.notes;
                notesSection.style.display = 'block';
            } else {
                notesSection.style.display = 'none';
            }
            
            // Store article ID for potential edit
            document.getElementById('detailsModal').setAttribute('data-article-id', article.id);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        } else {
            alert('Eroare la încărcarea detaliilor: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la încărcarea datelor');
    });
}

// Save article changes
function saveArticle() {
    const articleId = document.getElementById('editArticleId').value;
    const saveBtn = document.querySelector('#editModal .btn-primary');
    
    // Show loading state
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvez...';
    saveBtn.disabled = true;
    
    const formData = {
        title: document.getElementById('editTitle').value.trim(),
        description: document.getElementById('editDescription').value.trim(),
        category: document.getElementById('editCategory').value,
        status: document.getElementById('editStatus').value,
        priority: document.getElementById('editPriority').value,
        author: document.getElementById('editAuthor').value.trim(),
        tags: document.getElementById('editTags').value.trim(),
        notes: document.getElementById('editNotes').value.trim()
    };
    
    fetch('editorial-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=save_article&article_id=${articleId}&data=${encodeURIComponent(JSON.stringify(formData))}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
            
            // Refresh the page to show updated data
            window.location.reload();
        } else {
            alert('Eroare la salvare: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la salvarea datelor');
    })
    .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Open edit modal from details modal
function openEditFromDetails() {
    const articleId = document.getElementById('detailsModal').getAttribute('data-article-id');
    
    // Close details modal
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
    detailsModal.hide();
    
    // Open edit modal
    setTimeout(() => {
        editArticle(articleId);
    }, 300); // Small delay to allow modal to close
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric'
    });
}

// Helper function to format date and time
function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('ro-RO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Export plan (placeholder)
function exportPlan() {
    // TODO: Generate CSV or PDF export
    alert('Funcționalitatea de export va fi implementată în curând.');
}
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
