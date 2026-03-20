<?php 
require_once(__DIR__ . '/config/config.php');

// SEO Configuration for editorial calendar
$pageTitle = 'Calendar Editorial - MatchDay.ro';
$pageDescription = 'Calendarul editorial al MatchDay.ro cu programarea completa: cronici de meciuri, analize in avampremiera, interviuri, transferuri si reportaje speciale.';
$pageKeywords = ['calendar editorial', 'program publicare', 'cronici meciuri', 'analize fotbal', 'interviuri', 'transferuri', 'reportaje'];
$pageType = 'website';

// Breadcrumbs for editorial calendar
$breadcrumbs = [
    ['name' => 'Acasa', 'url' => './index.php'],
    ['name' => 'Calendar Editorial']
];

include(__DIR__ . '/includes/header.php'); 

// Citeste articolele din editorial-plan.json
function loadEditorialPlan() {
    $planFile = __DIR__ . '/data/editorial-plan.json';
    if (!file_exists($planFile)) {
        return [];
    }
    $content = file_get_contents($planFile);
    $articles = json_decode($content, true);
    return is_array($articles) ? $articles : [];
}

// Grupeaza articolele pe saptamani
function groupArticlesByWeek($articles) {
    if (empty($articles)) {
        return [];
    }
    
    // Sorteaza dupa data
    usort($articles, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    $weeks = [];
    $currentWeek = null;
    $weekNumber = 0;
    
    foreach ($articles as $article) {
        $date = new DateTime($article['date']);
        $weekOfYear = $date->format('W');
        $year = $date->format('Y');
        $weekKey = $year . '-' . $weekOfYear;
        
        if ($currentWeek !== $weekKey) {
            $currentWeek = $weekKey;
            $weekNumber++;
            $weeks[$weekKey] = [
                'week_number' => $weekNumber,
                'start_date' => clone $date,
                'days' => []
            ];
        }
        
        $dateStr = $article['date'];
        if (!isset($weeks[$weekKey]['days'][$dateStr])) {
            $weeks[$weekKey]['days'][$dateStr] = [
                'date' => $date,
                'day_name' => $article['day_name'] ?? getDayNameRo($date),
                'articles' => []
            ];
        }
        
        $weeks[$weekKey]['days'][$dateStr]['articles'][] = $article;
    }
    
    return array_values($weeks);
}

function getDayNameRo($date) {
    $dayNames = [
        'Monday' => 'Luni',
        'Tuesday' => 'Marti', 
        'Wednesday' => 'Miercuri',
        'Thursday' => 'Joi',
        'Friday' => 'Vineri',
        'Saturday' => 'Sambata',
        'Sunday' => 'Duminica'
    ];
    return $dayNames[$date->format('l')] ?? $date->format('l');
}

function getStatusBadge($status) {
    $badges = [
        'planned' => ['class' => 'bg-warning text-dark', 'text' => 'Planificat', 'icon' => 'fas fa-clock'],
        'in-progress' => ['class' => 'bg-info', 'text' => 'In lucru', 'icon' => 'fas fa-spinner fa-spin'],
        'published' => ['class' => 'bg-success', 'text' => 'Publicat', 'icon' => 'fas fa-check'],
        'draft' => ['class' => 'bg-secondary', 'text' => 'Ciorna', 'icon' => 'fas fa-edit']
    ];
    return $badges[$status] ?? $badges['planned'];
}

function getPriorityBadge($priority) {
    $badges = [
        'high' => ['class' => 'bg-danger', 'icon' => 'fas fa-arrow-up'],
        'normal' => ['class' => 'bg-primary', 'icon' => 'fas fa-minus'],
        'low' => ['class' => 'bg-secondary', 'icon' => 'fas fa-arrow-down']
    ];
    return $badges[$priority] ?? $badges['normal'];
}

function getContentTypeColor($type) {
    $colors = [
        'Clasament' => 'primary',
        'Top marcatori' => 'success',
        'Program UCL' => 'danger',
        'Rezultat meci' => 'info',
        'Meme' => 'warning',
        'Meme fotbal' => 'warning',
        'Analiza' => 'info',
        'Stiri' => 'dark'
    ];
    return $colors[$type] ?? 'secondary';
}

$articles = loadEditorialPlan();
$weeks = groupArticlesByWeek($articles);

// Statistici
$totalArticles = count($articles);
$plannedCount = count(array_filter($articles, fn($a) => ($a['status'] ?? '') === 'planned'));
$inProgressCount = count(array_filter($articles, fn($a) => ($a['status'] ?? '') === 'in-progress'));
$publishedCount = count(array_filter($articles, fn($a) => ($a['status'] ?? '') === 'published'));

// Data ultima actualizare
$lastUpdate = !empty($articles) ? max(array_column($articles, 'updated_at')) : date('Y-m-d H:i:s');
$lastUpdateFormatted = (new DateTime($lastUpdate))->format('d.m.Y');
?>

<main class="container my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php"><i class="fas fa-home"></i> Acasa</a></li>
                    <li class="breadcrumb-item active">Calendar Editorial</li>
                </ol>
            </nav>
            
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h2 fw-bold mb-1">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Calendar Editorial
                    </h1>
                    <p class="text-muted mb-0">
                        Programarea completa a continutului MatchDay.ro
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge bg-primary fs-6 px-3 py-2">
                        <i class="fas fa-clock me-1"></i>
                        Actualizat: <?= $lastUpdateFormatted ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h3 text-primary mb-1"><?= $totalArticles ?></div>
                    <small class="text-muted">Total articole</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h3 text-warning mb-1"><?= $plannedCount ?></div>
                    <small class="text-muted">Planificate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h3 text-info mb-1"><?= $inProgressCount ?></div>
                    <small class="text-muted">In lucru</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="h3 text-success mb-1"><?= $publishedCount ?></div>
                    <small class="text-muted">Publicate</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($weeks)): ?>
    <!-- No Articles Message -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-times text-muted fa-3x mb-3"></i>
                    <h4 class="text-muted">Niciun articol planificat</h4>
                    <p class="text-muted">Calendarul editorial va fi actualizat in curand.</p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Calendar Weeks -->
    <?php foreach ($weeks as $week): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-calendar-week me-2"></i>
                            Saptamana <?= $week['week_number'] ?>
                        </h3>
                        <small class="opacity-75">
                            <?= count($week['days']) ?> zile cu articole planificate
                        </small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($week['days'] as $dateStr => $day): ?>
                    <div class="border-bottom p-3">
                        <!-- Day Header -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <div class="fw-bold text-primary">
                                    <?= htmlspecialchars($day['day_name']) ?>
                                </div>
                                <small class="text-muted">
                                    <?= $day['date']->format('d.m.Y') ?>
                                </small>
                            </div>
                            <span class="badge bg-light text-dark">
                                <?= count($day['articles']) ?> articol<?= count($day['articles']) > 1 ? 'e' : '' ?>
                            </span>
                        </div>
                        
                        <!-- Articles List -->
                        <div class="row">
                            <?php foreach ($day['articles'] as $article): ?>
                            <div class="col-lg-6 mb-3">
                                <div class="card h-100 border <?= ($article['priority'] ?? '') === 'high' ? 'border-danger' : '' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-<?= getContentTypeColor($article['content_type'] ?? '') ?>">
                                                <?= htmlspecialchars($article['content_type'] ?? 'Articol') ?>
                                            </span>
                                            <?php $status = getStatusBadge($article['status'] ?? 'planned'); ?>
                                            <span class="badge <?= $status['class'] ?>">
                                                <i class="<?= $status['icon'] ?> me-1"></i>
                                                <?= $status['text'] ?>
                                            </span>
                                        </div>
                                        
                                        <h5 class="card-title h6 mb-2">
                                            <?= htmlspecialchars($article['title'] ?? 'Fara titlu') ?>
                                        </h5>
                                        
                                        <?php if (!empty($article['description'])): ?>
                                        <p class="card-text small text-muted mb-2">
                                            <?= htmlspecialchars($article['description']) ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <?php if (!empty($article['category'])): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-folder me-1"></i>
                                                <?= htmlspecialchars($article['category']) ?>
                                            </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($article['priority'])): ?>
                                            <?php $priority = getPriorityBadge($article['priority']); ?>
                                            <span class="badge <?= $priority['class'] ?>">
                                                <i class="<?= $priority['icon'] ?> me-1"></i>
                                                <?= ucfirst($article['priority']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($article['author'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($article['author']) ?>
                                        </small>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($article['notes'])): ?>
                                        <div class="mt-2 p-2 bg-light rounded">
                                            <small class="text-muted">
                                                <i class="fas fa-sticky-note me-1"></i>
                                                <?= htmlspecialchars($article['notes']) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>

    <!-- Legend -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="h6 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Legenda statusuri
                    </h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i> Planificat
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-spinner me-1"></i> In lucru
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i> Publicat
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-edit me-1"></i> Ciorna
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="h6 mb-0">
                        <i class="fas fa-flag me-2"></i>
                        Legenda prioritati
                    </h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-danger">
                            <i class="fas fa-arrow-up me-1"></i> Prioritate mare
                        </span>
                        <span class="badge bg-primary">
                            <i class="fas fa-minus me-1"></i> Prioritate normala
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-arrow-down me-1"></i> Prioritate mica
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Structured Data for Editorial Calendar -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Calendar Editorial MatchDay.ro",
  "description": "Programarea completa a continutului editorial pentru MatchDay.ro cu cronici, analize si interviuri.",
  "url": "https://matchday.ro/calendar-editorial.php",
  "mainEntity": {
    "@type": "Organization",
    "name": "MatchDay.ro",
    "description": "Jurnal de fotbal cu calendar editorial structurat pentru continut de calitate."
  }
}
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>
