<?php
/**
 * Admin Ads Management
 * MatchDay.ro - Advertising/Sponsorship System
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Ad.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Run migration to ensure table exists
try {
    Ad::migrate();
} catch (Exception $e) {
    error_log("Ad migration error: " . $e->getMessage());
}

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalid!';
    } else {
        try {
        switch ($action) {
            case 'create':
                $image = $_POST['image'] ?? '';
                
                // Handle file upload
                if (!empty($_FILES['image_upload']['name'])) {
                    $uploadDir = __DIR__ . '/../assets/uploads/ads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image_upload']['name']);
                    $uploadPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $uploadPath)) {
                        $image = '/assets/uploads/ads/' . $filename;
                    }
                }
                
                // Ensure table exists first
                Ad::migrate();
                
                $id = Ad::create([
                    'name' => $_POST['name'] ?? '',
                    'image' => $image,
                    'link' => $_POST['link'] ?? '',
                    'code' => $_POST['code'] ?? '',
                    'position' => $_POST['position'] ?? 'sidebar',
                    'start_date' => $_POST['start_date'] ?? null,
                    'end_date' => $_POST['end_date'] ?? null,
                    'active' => isset($_POST['active']) ? 1 : 0
                ]);
                
                $message = 'Reclama "' . htmlspecialchars($_POST['name']) . '" a fost creată cu succes!';
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $image = $_POST['image'] ?? '';
                
                // Handle file upload
                if (!empty($_FILES['image_upload']['name'])) {
                    $uploadDir = __DIR__ . '/../assets/uploads/ads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image_upload']['name']);
                    $uploadPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $uploadPath)) {
                        $image = '/assets/uploads/ads/' . $filename;
                    }
                }
                
                Ad::update($id, [
                    'name' => $_POST['name'] ?? '',
                    'image' => $image,
                    'link' => $_POST['link'] ?? '',
                    'code' => $_POST['code'] ?? '',
                    'position' => $_POST['position'] ?? 'sidebar',
                    'start_date' => $_POST['start_date'] ?? null,
                    'end_date' => $_POST['end_date'] ?? null,
                    'active' => isset($_POST['active']) ? 1 : 0
                ]);
                
                $message = 'Reclama a fost actualizată cu succes!';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                Ad::delete($id);
                $message = 'Reclama a fost ștearsă!';
                break;
                
            case 'toggle':
                $id = (int)$_POST['id'];
                Ad::toggleActive($id);
                $message = 'Status reclamă actualizat!';
                break;
        }
        } catch (Exception $e) {
            $error = 'Eroare: ' . $e->getMessage();
            error_log("Ads action error: " . $e->getMessage());
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get data
$ads = Ad::getAll();
$stats = Ad::getStats();
$positions = Ad::getPositions();

// Edit mode
$editAd = null;
if (isset($_GET['edit'])) {
    $editAd = Ad::getById((int)$_GET['edit']);
}

$pageTitle = 'Reclame & Sponsori';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-ad me-2"></i>Reclame & Sponsori</h1>
    <button type="button" class="btn btn-primary" id="btnNewAd">
        <i class="fas fa-plus me-1"></i>Reclamă nouă
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-ad"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total'] ?></h3>
                <p>Total reclame</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-play-circle"></i></div>
            <div class="stat-content">
                <h3><?= $stats['active'] ?></h3>
                <p>Active</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-content">
                <h3><?= number_format($stats['clicks']) ?></h3>
                <p>Total clickuri</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-percentage"></i></div>
            <div class="stat-content">
                <h3><?= $stats['ctr'] ?>%</h3>
                <p>CTR mediu</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ads List -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Toate reclamele</h2>
                <span class="badge bg-primary"><?= $stats['total'] ?> reclame</span>
            </div>
            
            <?php if (empty($ads)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ad fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">Nu există reclame încă.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adModal">
                        <i class="fas fa-plus me-1"></i>Creează prima reclamă
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 60px">Preview</th>
                                <th>Nume</th>
                                <th>Poziție</th>
                                <th>Perioadă</th>
                                <th>Performanță</th>
                                <th>Status</th>
                                <th class="text-center">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td>
                                    <?php if ($ad['image']): ?>
                                        <img src="<?= htmlspecialchars($ad['image']) ?>" 
                                             class="img-thumbnail" 
                                             style="width: 50px; height: 40px; object-fit: cover;">
                                    <?php elseif ($ad['code']): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-code"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ad['name']) ?></strong>
                                    <?php if ($ad['link']): ?>
                                        <br><small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                            <i class="fas fa-link me-1"></i><?= htmlspecialchars($ad['link']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $positions[$ad['position']] ?? $ad['position'] ?></span>
                                </td>
                                <td>
                                    <?php if ($ad['start_date'] || $ad['end_date']): ?>
                                        <small>
                                            <?= $ad['start_date'] ? date('d.m.Y', strtotime($ad['start_date'])) : 'Nedefinit' ?>
                                            <br>→ <?= $ad['end_date'] ? date('d.m.Y', strtotime($ad['end_date'])) : 'Permanent' ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Permanent</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span title="Afișări"><i class="fas fa-eye text-muted me-1"></i><?= number_format($ad['impressions']) ?></span>
                                    <br>
                                    <span title="Clickuri"><i class="fas fa-mouse-pointer text-primary me-1"></i><?= number_format($ad['clicks']) ?></span>
                                    <?php 
                                    $ctr = $ad['impressions'] > 0 ? round(($ad['clicks'] / $ad['impressions']) * 100, 2) : 0;
                                    ?>
                                    <small class="text-muted">(<?= $ctr ?>% CTR)</small>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $ad['active'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                                            <?php if ($ad['active']): ?>
                                                <i class="fas fa-check me-1"></i>Activ
                                            <?php else: ?>
                                                <i class="fas fa-pause me-1"></i>Inactiv
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?= $ad['id'] ?>" class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Ștergi această reclamă?')">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Șterge">
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
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar - Positions Info -->
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <strong><i class="fas fa-map-marker-alt me-2"></i>Poziții disponibile</strong>
            </div>
            <div class="p-4">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($positions as $key => $name): ?>
                    <li class="mb-2">
                        <span class="badge bg-secondary me-2"><?= $key ?></span>
                        <?= $name ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <strong><i class="fas fa-info-circle me-2"></i>Ghid utilizare</strong>
            </div>
            <div class="p-4">
                <p class="small mb-2"><strong>Imagine banner:</strong></p>
                <ul class="small text-muted mb-3">
                    <li>Sidebar: 300x250px (MPU)</li>
                    <li>Header: 728x90px (Leaderboard)</li>
                    <li>Footer: 970x90px sau 728x90px</li>
                </ul>
                
                <p class="small mb-2"><strong>Cod embed:</strong></p>
                <p class="small text-muted mb-3">
                    Poți adăuga cod HTML/JS pentru reclame externe (Google Ads, etc.)
                </p>
                
                <p class="small mb-2"><strong>Perioadă:</strong></p>
                <p class="small text-muted mb-0">
                    Lasă gol pentru afișare permanentă sau setează date start/end.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Ad Modal -->
<div class="modal fade" id="adModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="<?= $editAd ? 'update' : 'create' ?>">
                <?php if ($editAd): ?>
                    <input type="hidden" name="id" value="<?= $editAd['id'] ?>">
                <?php endif; ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ad me-2"></i>
                        <?= $editAd ? 'Editează reclama' : 'Reclamă nouă' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nume reclamă <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($editAd['name'] ?? '') ?>"
                                       placeholder="Ex: Banner Sponsor Principal">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Poziție</label>
                                <select name="position" class="form-select">
                                    <?php foreach ($positions as $key => $name): ?>
                                    <option value="<?= $key ?>" <?= ($editAd['position'] ?? 'sidebar') === $key ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Link destinație</label>
                        <input type="url" name="link" class="form-control"
                               value="<?= htmlspecialchars($editAd['link'] ?? '') ?>"
                               placeholder="https://sponsor.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL imagine</label>
                                <input type="url" name="image" class="form-control"
                                       value="<?= htmlspecialchars($editAd['image'] ?? '') ?>"
                                       placeholder="https://...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sau încarcă imagine</label>
                                <input type="file" name="image_upload" class="form-control" 
                                       accept=".jpg,.jpeg,.png,.gif,.webp">
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($editAd && $editAd['image']): ?>
                    <div class="mb-3">
                        <label class="form-label">Preview actual:</label><br>
                        <img src="<?= htmlspecialchars($editAd['image']) ?>" class="img-thumbnail" style="max-height: 100px;">
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Cod embed (HTML/JS) - opțional</label>
                        <textarea name="code" class="form-control" rows="4"
                                  placeholder="<script>...</script> sau cod banner extern"><?= htmlspecialchars($editAd['code'] ?? '') ?></textarea>
                        <div class="form-text">Pentru reclame Google Ads sau alte platforme externe</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data start</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= $editAd['start_date'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data end</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?= $editAd['end_date'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input type="checkbox" name="active" class="form-check-input" id="adActive"
                               <?= ($editAd['active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="adActive">Reclamă activă</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?= $editAd ? 'Salvează modificările' : 'Creează reclama' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editAd): ?>
<script>
// Auto-open modal in edit mode
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('adModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    // On close, redirect to clean URL
    modalEl.addEventListener('hidden.bs.modal', function () {
        window.location.href = 'ads.php';
    });
});
</script>
<?php endif; ?>

<script>
// Handle "Reclamă nouă" button
document.getElementById('btnNewAd').addEventListener('click', function() {
    <?php if ($editAd): ?>
    // We're in edit mode, redirect to clean URL then open modal
    window.location.href = 'ads.php#add';
    <?php else: ?>
    // Not in edit mode, just open modal
    var modal = new bootstrap.Modal(document.getElementById('adModal'));
    modal.show();
    <?php endif; ?>
});

// Check for #add hash on page load
if (window.location.hash === '#add') {
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('adModal'));
        modal.show();
        // Clear hash
        history.replaceState(null, null, 'ads.php');
    });
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
