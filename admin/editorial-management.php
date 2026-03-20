<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

// Check if user is logged in
if (!isset($_SESSION['david_logged']) || !$_SESSION['david_logged']) {
    header('Location: login.php');
    exit;
}

// Functie pentru citirea planului editorial
function getEditorialPlan() {
    $planFile = __DIR__ . '/../data/editorial-plan.json';
    
    if (!file_exists($planFile)) {
        // Creeaza planul initial daca nu exista
        $initialPlan = generateInitialPlan();
        file_put_contents($planFile, json_encode($initialPlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $initialPlan;
    }
    
    $content = file_get_contents($planFile);
    return json_decode($content, true) ?: [];
}

// Functie pentru salvarea planului editorial
function saveEditorialPlan($plan) {
    $planFile = __DIR__ . '/../data/editorial-plan.json';
    return file_put_contents($planFile, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Genereaza planul initial
function generateInitialPlan() {
    $plan = [];
    $startDate = new DateTime('2025-09-01');
    
    for ($i = 0; $i < 28; $i++) { // 4 saptamani
        $currentDate = clone $startDate;
        $currentDate->add(new DateInterval('P' . $i . 'D'));
        
        $dayName = $currentDate->format('l');
        $dayNameRo = [
            'Monday' => 'Luni',
            'Tuesday' => 'Marti', 
            'Wednesday' => 'Miercuri',
            'Thursday' => 'Joi',
            'Friday' => 'Vineri',
            'Saturday' => 'Sambata',
            'Sunday' => 'Duminica'
        ][$dayName];
        
        $contentTypes = [
            'Luni' => 'Rezumatul saptamanii',
            'Marti' => 'Analize tactice',
            'Miercuri' => 'Interviuri & Reportaje',
            'Joi' => 'Stiri & Transferuri',
            'Vineri' => 'Avanpremiere weekend',
            'Sambata' => 'Live Updates & Cronici',
            'Duminica' => 'Cronici & Reactii'
        ];
        
        $plan[] = [
            'id' => 'article_' . $currentDate->format('Y_m_d'),
            'date' => $currentDate->format('Y-m-d'),
            'day_name' => $dayNameRo,
            'content_type' => $contentTypes[$dayNameRo],
            'title' => '',
            'description' => '',
            'status' => 'planned',
            'priority' => 'normal',
            'author' => 'David Nyikora',
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
            
            echo json_encode(['success' => false, 'error' => 'Articolul nu a fost gasit']);
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
$pageTitle = 'Management Editorial';

require_once(__DIR__ . '/admin-header.php');
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

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-calendar-check me-2"></i>Management Editorial</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="refreshStats()">
            <i class="fas fa-sync me-1"></i>Refresh
        </button>
        <button class="btn btn-primary btn-sm" onclick="exportPlan()">
            <i class="fas fa-download me-1"></i>Export
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4" id="statsCards">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3 id="stat-total">-</h3>
                <p>Total articole</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3 id="stat-planned">-</h3>
                <p>Planificate</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-spinner"></i></div>
            <div class="stat-content">
                <h3 id="stat-in_progress">-</h3>
                <p>In progres</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check"></i></div>
            <div class="stat-content">
                <h3 id="stat-published">-</h3>
                <p>Publicate</p>
            </div>
        </div>
    </div>
</div>

<!-- Editorial Plan Table -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center flex-wrap">
        <h2><i class="fas fa-list me-2"></i>Planul Editorial - Urmatoarele 4 saptamani</h2>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <select class="form-select form-select-sm" id="filterStatus" onchange="filterTable()">
                <option value="">Toate statusurile</option>
                <option value="planned">Planificat</option>
                <option value="in_progress">In progres</option>
                <option value="review">Review</option>
                <option value="published">Publicat</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table" id="editorialTable">
            <thead>
                <tr>
                    <th style="width: 100px;">Data</th>
                    <th style="width: 80px;">Zi</th>
                    <th style="width: 150px;">Tip continut</th>
                    <th>Titlu articol</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 80px;">Prioritate</th>
                    <th style="width: 120px;">Categorie</th>
                    <th style="width: 100px;">Actiuni</th>
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
                            <?= !empty($article['title']) ? htmlspecialchars($article['title']) : '<em class="text-muted">Click pentru a adauga titlu...</em>' ?>
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
                            <option value="planned" <?= $article['status'] === 'planned' ? 'selected' : '' ?>>Plar</option>
                            <option value="in_progress" <?= $article['status'] === 'in_progress' ? 'selected' : '' ?>>Inalt</option>
                            <option value="review" <?= $article['status'] === 'review' ? 'selected' : '' ?>>Review</option>
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Publicat</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm priority-select" data-field="priority" data-article="<?= $article['id'] ?>">
                            <option value="low" <?= $article['priority'] === 'low' ? 'selected' : '' ?>>Scazuta</option>
                            <option value="normal" <?= $article['priority'] === 'normal' ? 'selected' : '' ?>>Nor</option>
                            <option value="high" <?= $article['priority'] === 'high' ? 'selected' : '' ?>>Inalt</option>
                        </select>
                    </td>
                    <td>
                        <div class="editable small" data-field="category" data-article="<?= $article['id'] ?>">
                            <?= !empty($article['category']) ? htmlspecialchars($article['category']) : '<em class="text-muted">-</em>' ?>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-sm" onclick="editArticle('<?= $article['id'] ?>')" title="Editeaza">
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editeaza articol
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
                                    <option value="in_progress">In progres</option>
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
                                  placeholder="Descrie pe scurt continutul articolului..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-folder me-1"></i>Categorie
                                </label>
                                <select class="form-select" id="editCategory">
                                    <option value="">Selecteaza categoria</option>
                                    <option value="statistici">Statistici</option>
                                    <option value="champions-league">Champions League</option>
                                    <option value="meciuri">Meciuri</option>
                                    <option value="transferuri">Transferuri</option>
                                    <option value="opinii">Opinii</option>
                                    <option value="interviuri">Interviuri</option>
                                    <option value="competitii">Competitii</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-exclamation-circle me-1"></i>Prioritate
                                </label>
                                <select class="form-select" id="editPriority">
                                    <option value="low">Scazuta</option>
                                    <option value="normal">Normala</option>
                                    <option value="high">Inalta</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1"></i>Autor
                                </label>
                                <input type="text" class="form-control" id="editAuthor" 
                                       value="David Nyikora" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-tags me-1"></i>Tags
                            <small class="text-muted">(separate prin virgula)</small>
                        </label>
                        <input type="text" class="form-control" id="editTags" 
                               placeholder="ex: analiza, transferuri, liga 1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sticky-note me-1"></i>Note personale
                        </label>
                        <textarea class="form-control" id="editNotes" rows="3" 
                                  placeholder="Adauga note pentru tine sau echipa..."></textarea>
                    </div>
                    
                    <!-- Info display -->
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Data publicarii: <span id="editDateDisplay"></span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Tip continut: <span id="editContentType"></span>
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Anuleaza
                </button>
                <button type="button" class="btn btn-primary" onclick="saveArticle()">
                    <i class="fas fa-save me-1"></i>Salveaza modificarile
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
                                <td class="fw-medium"><i class="fas fa-file-alt me-1"></i>Tip continut:</td>
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
                    <i class="fas fa-times me-1"></i>Inchide
                </button>
                <button type="button" class="btn btn-primary" onclick="openEditFromDetails()">
                    <i class="fas fa-edit me-1"></i>Editeaza
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
            document.getElementById('editAuthor').value = article.author || 'David Nyikora';
            document.getElementById('editTags').value = Array.isArray(article.tags) ? article.tags.join(', ') : '';
            document.getElementById('editNotes').value = article.notes || '';
            
            // Update display info
            document.getElementById('editDateDisplay').textContent = formatDate(article.date);
            document.getElementById('editContentType').textContent = article.content_type || '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        } else {
            alert('Eroare la incarcarea articolului: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la incarcarea datelor');
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
            document.getElementById('detailTitle').textContent = article.title || 'Fara titlu';
            document.getElementById('detailDescription').textContent = article.description || 'Fara descriere';
            
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
                'in_progress': 'In progres',
                'review': 'Review',
                'published': 'Publicat'
            };
            statusBadge.className = `badge ${statusColors[article.status] || 'bg-secondary'}`;
            statusBadge.textContent = statusTexts[article.status] || article.status;
            
            // Priority
            const priorityTexts = {
                'low': 'Prioritate scazuta',
                'normal': 'Prioritate normala',
                'high': 'Prioritate inalta'
            };
            document.getElementById('detailPriority').textContent = priorityTexts[article.priority] || 'Normal';
            
            // Details table
            document.getElementById('detailDate').textContent = formatDate(article.date);
            document.getElementById('detailDay').textContent = article.day_name || '';
            document.getElementById('detailContentTypeValue').textContent = article.content_type || '';
            document.getElementById('detailCategoryValue').textContent = article.category || 'Necategorizat';
            document.getElementById('detailAuthorValue').textContent = article.author || 'David Nyikora';
            document.getElementById('detailCreated').textContent = formatDateTime(article.created_at);
            document.getElementById('detailUpdated').textContent = formatDateTime(article.updated_at);
            
            // Tags
            const tagsText = Array.isArray(article.tags) && article.tags.length > 0 
                ? article.tags.join(', ') 
                : 'Fara tags';
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
            alert('Eroare la incarcarea detaliilor: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la incarcarea datelor');
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
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        
        if (result.success) {
            // Close modal and refresh
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            location.reload();
        } else {
            alert('Eroare la salvare: ' + result.error);
        }
    })
    .catch(error => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        console.error('Error:', error);
        alert('Eroare la salvarea datelor');
    });
}

// Open edit from details modal
function openEditFromDetails() {
    const articleId = document.getElementById('detailsModal').getAttribute('data-article-id');
    bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
    setTimeout(() => editArticle(articleId), 300);
}

// Export plan
function exportPlan() {
    window.location.href = '../data/editorial-plan.json';
}

// Format date helper
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Format datetime helper
function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('ro-RO', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>

