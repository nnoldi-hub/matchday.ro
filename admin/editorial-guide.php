<?php
/**
 * Admin Editorial Guide
 * MatchDay.ro - Editorial Guidelines & Templates
 */

$pageTitle = 'Ghid Editorial - Admin';
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/admin-header.php');

// Template files
$templatesDir = __DIR__ . '/../data/templates';
$templates = [
    'GHID-EDITORIAL' => ['title' => 'Ghid Editorial Complet', 'icon' => 'fa-book', 'color' => 'primary'],
    'preview' => ['title' => 'Preview Meci', 'icon' => 'fa-eye', 'color' => 'info'],
    'analiza' => ['title' => 'Analiză Meci', 'icon' => 'fa-chart-pie', 'color' => 'success'],
    'transfer' => ['title' => 'Transfer News', 'icon' => 'fa-exchange-alt', 'color' => 'warning'],
    'interviu' => ['title' => 'Interviu', 'icon' => 'fa-microphone', 'color' => 'danger'],
    'opinie' => ['title' => 'Opinie / Editorial', 'icon' => 'fa-comment-dots', 'color' => 'secondary'],
    'breaking' => ['title' => 'Breaking News', 'icon' => 'fa-bolt', 'color' => 'danger'],
    'istoric' => ['title' => 'Retrospectivă / Istoric', 'icon' => 'fa-history', 'color' => 'dark'],
];

// Selected template
$selected = $_GET['template'] ?? 'GHID-EDITORIAL';
$selectedFile = $templatesDir . '/' . $selected . '.md';
$content = '';

if (file_exists($selectedFile)) {
    $content = file_get_contents($selectedFile);
}

// Parse Markdown (basic)
function parseMarkdown($text) {
    // Headers
    $text = preg_replace('/^### (.+)$/m', '<h5 class="mt-4 mb-2">$1</h5>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h4 class="mt-4 mb-3 text-primary">$1</h4>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h3 class="mt-4 mb-3">$1</h3>', $text);
    
    // Bold and italic
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    
    // Code blocks
    $text = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre class="bg-dark text-light p-3 rounded"><code>$2</code></pre>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code class="bg-light px-1 rounded">$1</code>', $text);
    
    // Blockquotes
    $text = preg_replace('/^> (.+)$/m', '<blockquote class="border-start border-4 border-primary ps-3 my-2 text-muted fst-italic">$1</blockquote>', $text);
    
    // Checkboxes
    $text = preg_replace('/- \[x\] (.+)$/m', '<div class="form-check"><input class="form-check-input" type="checkbox" checked disabled><label class="form-check-label text-success">$1</label></div>', $text);
    $text = preg_replace('/- \[ \] (.+)$/m', '<div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">$1</label></div>', $text);
    
    // Lists
    $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul class="mb-3">$0</ul>', $text);
    
    // Tables (basic)
    $text = preg_replace_callback('/(\|.+\|)\n(\|[-: |]+\|)\n((?:\|.+\|\n?)+)/', function($matches) {
        $header = $matches[1];
        $rows = $matches[3];
        
        $headerCells = array_filter(array_map('trim', explode('|', $header)));
        $headerHtml = '<thead class="table-dark"><tr>';
        foreach ($headerCells as $cell) {
            $headerHtml .= '<th>' . $cell . '</th>';
        }
        $headerHtml .= '</tr></thead>';
        
        $bodyHtml = '<tbody>';
        foreach (explode("\n", trim($rows)) as $row) {
            if (empty(trim($row))) continue;
            $cells = array_filter(array_map('trim', explode('|', $row)));
            $bodyHtml .= '<tr>';
            foreach ($cells as $cell) {
                $bodyHtml .= '<td>' . $cell . '</td>';
            }
            $bodyHtml .= '</tr>';
        }
        $bodyHtml .= '</tbody>';
        
        return '<div class="table-responsive mb-3"><table class="table table-bordered table-sm">' . $headerHtml . $bodyHtml . '</table></div>';
    }, $text);
    
    // Horizontal rule
    $text = preg_replace('/^---$/m', '<hr class="my-4">', $text);
    
    // Emojis (common ones)
    $emojis = [
        '✅' => '<span class="text-success">✅</span>',
        '❌' => '<span class="text-danger">❌</span>',
        '⚠️' => '<span class="text-warning">⚠️</span>',
        '📝' => '📝',
        '📊' => '📊',
        '⭐' => '<span class="text-warning">⭐</span>',
    ];
    $text = str_replace(array_keys($emojis), array_values($emojis), $text);
    
    // Paragraphs (for remaining text)
    $text = preg_replace('/\n\n+/', '</p><p>', $text);
    $text = '<p>' . $text . '</p>';
    $text = preg_replace('/<p>\s*<\/p>/', '', $text);
    $text = preg_replace('/<p>(<h[3-5]|<ul|<pre|<div|<hr|<blockquote|<table)/', '$1', $text);
    $text = preg_replace('/(<\/h[3-5]>|<\/ul>|<\/pre>|<\/div>|<hr>|<\/blockquote>|<\/table>)<\/p>/', '$1', $text);
    
    return $text;
}
?>

<!-- Page Header -->
<div class="admin-page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-book-open me-2"></i>Ghid Editorial</h1>
        <p class="text-muted mb-0">Șabloane și linii directoare pentru conținut</p>
    </div>
    <div>
        <a href="new-post.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Articol Nou
        </a>
    </div>
</div>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="admin-card sticky-top" style="top: 1rem;">
            <div class="admin-card-header">
                <h2><i class="fas fa-list me-2"></i>Șabloane</h2>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($templates as $key => $template): ?>
                    <a href="?template=<?= $key ?>" 
                       class="list-group-item list-group-item-action d-flex align-items-center <?= $selected === $key ? 'active' : '' ?>">
                        <i class="fas <?= $template['icon'] ?> me-2 text-<?= $template['color'] ?>"></i>
                        <span><?= $template['title'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="admin-card mt-3">
            <div class="admin-card-header">
                <h2><i class="fas fa-chart-bar me-2"></i>Sfaturi</h2>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2"><i class="fas fa-check text-success me-1"></i> Verifică SEO înainte de publish</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-1"></i> Adaugă imagini optimizate</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-1"></i> Include link-uri interne</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-1"></i> Preview în mobil</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="col-md-9">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas <?= $templates[$selected]['icon'] ?? 'fa-file' ?> me-2 text-<?= $templates[$selected]['color'] ?? 'primary' ?>"></i>
                    <?= $templates[$selected]['title'] ?? 'Document' ?>
                </h2>
                <?php if ($selected !== 'GHID-EDITORIAL'): ?>
                    <span class="badge bg-<?= $templates[$selected]['color'] ?? 'primary' ?>">
                        Șablon Articol
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body editorial-content">
                <?php if ($content): ?>
                    <?= parseMarkdown(htmlspecialchars_decode(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'))) ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Șablonul nu a fost găsit.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Copy Template Button -->
        <?php if ($selected !== 'GHID-EDITORIAL' && $content): ?>
            <div class="mt-3">
                <button class="btn btn-outline-primary" onclick="copyTemplate()">
                    <i class="fas fa-copy me-1"></i>Copiază Șablonul
                </button>
                <a href="new-post.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Crează Articol
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.editorial-content {
    font-size: 0.95rem;
    line-height: 1.7;
}
.editorial-content h3 {
    border-bottom: 2px solid #D32F2F;
    padding-bottom: 0.5rem;
}
.editorial-content h4 {
    border-left: 3px solid #D32F2F;
    padding-left: 0.75rem;
}
.editorial-content pre {
    font-size: 0.85rem;
    max-height: 400px;
    overflow: auto;
}
.editorial-content table {
    font-size: 0.9rem;
}
.editorial-content blockquote {
    font-size: 1rem;
}
.editorial-content .form-check {
    padding-left: 2rem;
}
</style>

<script>
function copyTemplate() {
    const template = <?= json_encode($content) ?>;
    navigator.clipboard.writeText(template).then(() => {
        alert('Șablonul a fost copiat în clipboard!');
    }).catch(err => {
        console.error('Eroare la copiere:', err);
    });
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
