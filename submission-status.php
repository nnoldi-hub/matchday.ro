<?php
/**
 * Submission Status Page
 * MatchDay.ro - Check article submission status
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Submission.php');

$pageTitle = 'Status Articol - MatchDay.ro';
$submission = null;
$error = '';

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($token) {
    $submission = Submission::getByToken($token);
    if (!$submission) {
        $error = 'Articolul nu a fost găsit. Verifică codul de urmărire.';
    }
}

require_once(__DIR__ . '/includes/header.php');
?>

<div class="submission-status-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if (!$submission && !$error): ?>
                <!-- Search Form -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <h1 class="mb-4">
                            <i class="bi bi-search text-success"></i>
                        </h1>
                        <h2 class="mb-3">Verifică Statusul Articolului</h2>
                        <p class="text-muted mb-4">
                            Introdu codul de urmărire primit când ai trimis articolul.
                        </p>
                        
                        <form method="GET" action="" class="mx-auto" style="max-width: 400px;">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control form-control-lg" 
                                       name="token" placeholder="Cod de urmărire" required>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($error): ?>
                <!-- Error -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <h3 class="text-danger mb-3">Articol Negăsit</h3>
                        <p class="text-muted mb-4"><?= htmlspecialchars($error) ?></p>
                        <a href="/submission-status.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Încearcă Din Nou
                        </a>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Status Display -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white p-4">
                        <h4 class="mb-0">
                            <i class="bi bi-file-text text-success me-2"></i>
                            <?= htmlspecialchars($submission['title']) ?>
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Status Timeline -->
                        <div class="status-timeline mb-4">
                            <?php
                            $statuses = [
                                'pending' => ['label' => 'Trimis', 'icon' => 'send'],
                                'reviewing' => ['label' => 'În Revizuire', 'icon' => 'eye'],
                                'approved' => ['label' => 'Aprobat', 'icon' => 'check-circle'],
                                'published' => ['label' => 'Publicat', 'icon' => 'globe']
                            ];
                            
                            $currentIndex = array_search($submission['status'], array_keys($statuses));
                            $isRejected = $submission['status'] === 'rejected';
                            ?>
                            
                            <div class="d-flex justify-content-between position-relative">
                                <div class="progress-line"></div>
                                
                                <?php foreach ($statuses as $key => $status): 
                                    $index = array_search($key, array_keys($statuses));
                                    $isActive = $index <= $currentIndex && !$isRejected;
                                    $isCurrent = $key === $submission['status'];
                                ?>
                                <div class="status-step text-center <?= $isActive ? 'active' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-<?= $status['icon'] ?>"></i>
                                    </div>
                                    <small class="step-label"><?= $status['label'] ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($isRejected): ?>
                            <div class="alert alert-warning mt-4">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Articolul necesită revizuire</strong>
                                <?php if ($submission['reviewer_feedback']): ?>
                                <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($submission['reviewer_feedback'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Details -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <small class="text-muted d-block">Autor</small>
                                    <strong><?= htmlspecialchars($submission['author_name']) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <small class="text-muted d-block">Categorie</small>
                                    <strong><?= htmlspecialchars($submission['category_name'] ?? 'Necategorizat') ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <small class="text-muted d-block">Trimis la</small>
                                    <strong><?= date('d.m.Y H:i', strtotime($submission['created_at'])) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <small class="text-muted d-block">Ultima actualizare</small>
                                    <strong><?= date('d.m.Y H:i', strtotime($submission['updated_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Excerpt Preview -->
                        <?php if ($submission['excerpt']): ?>
                        <div class="mt-4">
                            <h6 class="text-muted">Rezumat</h6>
                            <p class="fst-italic"><?= htmlspecialchars($submission['excerpt']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <div class="card-footer bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/contribute.php" class="btn btn-outline-success">
                                <i class="bi bi-pencil me-1"></i>Scrie Alt Articol
                            </a>
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-1"></i>Acasă
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<style>
.submission-status-page {
    min-height: 60vh;
}

.status-timeline {
    padding: 2rem 0;
}

.status-timeline .progress-line {
    position: absolute;
    top: 24px;
    left: 10%;
    right: 10%;
    height: 3px;
    background: #dee2e6;
    z-index: 0;
}

.status-step {
    position: relative;
    z-index: 1;
    flex: 1;
}

.step-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    transition: all 0.3s;
}

.step-icon i {
    font-size: 1.25rem;
    color: #6c757d;
}

.status-step.active .step-icon {
    border-color: #28a745;
    background: #28a745;
}

.status-step.active .step-icon i {
    color: #fff;
}

.status-step.current .step-icon {
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
}

.step-label {
    color: #6c757d;
    font-weight: 500;
}

.status-step.active .step-label {
    color: #28a745;
}
</style>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
