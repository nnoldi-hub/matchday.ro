<?php
/**
 * Sponsored Content Disclaimer Component
 * MatchDay.ro
 * 
 * Usage in article templates:
 * <?php if ($post['is_sponsored']): ?>
 *     <?php include(__DIR__ . '/sponsored-disclaimer.php'); ?>
 * <?php endif; ?>
 * 
 * Or with sponsor name:
 * <?php 
 * $sponsorName = 'Brand XYZ';
 * include(__DIR__ . '/sponsored-disclaimer.php'); 
 * ?>
 */

// Default sponsor name if not set
$sponsorName = $sponsorName ?? 'un partener';
$disclaimerStyle = $disclaimerStyle ?? 'standard'; // standard, minimal, footer
?>

<?php if ($disclaimerStyle === 'standard'): ?>
    <!-- Standard Sponsored Notice (Top of Article) -->
    <div class="sponsored-notice alert alert-warning border-start border-4 border-warning d-flex align-items-center mb-4" role="note">
        <i class="fas fa-ad fa-lg me-3 text-warning"></i>
        <div>
            <strong>Articol în parteneriat</strong><br>
            <small class="text-muted">
                Acest articol este realizat în colaborare cu <?= htmlspecialchars($sponsorName) ?>. 
                Opiniile exprimate aparțin redacției MatchDay.ro.
            </small>
        </div>
    </div>

<?php elseif ($disclaimerStyle === 'minimal'): ?>
    <!-- Minimal Badge (Inline) -->
    <span class="badge bg-warning text-dark">
        <i class="fas fa-ad me-1"></i>Sponsorizat
    </span>

<?php elseif ($disclaimerStyle === 'footer'): ?>
    <!-- Footer Disclaimer -->
    <div class="sponsored-footer border-top pt-3 mt-4">
        <p class="small text-muted fst-italic">
            <i class="fas fa-info-circle me-1"></i>
            <strong>Disclaimer:</strong> Acest articol conține conținut sponsorizat de <?= htmlspecialchars($sponsorName) ?>. 
            MatchDay.ro își păstrează independența editorială, iar opiniile exprimate aparțin exclusiv autorilor noștri. 
            Pentru mai multe informații despre politica noastră de publicitate, vizitați 
            <a href="/publicitate.php">pagina de publicitate</a>.
        </p>
    </div>

<?php elseif ($disclaimerStyle === 'card'): ?>
    <!-- Card Style (Sidebar) -->
    <div class="card bg-warning bg-opacity-10 border-warning mb-3">
        <div class="card-body p-3">
            <h6 class="card-title mb-2">
                <i class="fas fa-handshake text-warning me-1"></i>
                Conținut Parteneriat
            </h6>
            <p class="card-text small mb-0">
                Acest material este realizat cu sprijinul <?= htmlspecialchars($sponsorName) ?>.
            </p>
        </div>
    </div>

<?php endif; ?>

<style>
.sponsored-notice {
    background-color: rgba(255, 193, 7, 0.1);
}
.sponsored-footer {
    background-color: #f8f9fa;
    margin-left: -1rem;
    margin-right: -1rem;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>
