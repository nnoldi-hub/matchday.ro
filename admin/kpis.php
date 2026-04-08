<?php
/**
 * Admin KPIs Dashboard
 * MatchDay.ro - Key Performance Indicators
 * 
 * Metrics:
 * - Content (articles, views)
 * - Engagement (comments, polls, newsletter)
 * - Technical (errors, cache, performance)
 */

$pageTitle = 'KPIs Dashboard - Admin';
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/Comment.php');
require_once(__DIR__ . '/../includes/Poll.php');
require_once(__DIR__ . '/../includes/Logger.php');
require_once(__DIR__ . '/../includes/Stats.php');
require_once(__DIR__ . '/admin-header.php');

// Only admins can view KPIs
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Date range
$period = $_GET['period'] ?? '30';
$startDate = date('Y-m-d', strtotime("-{$period} days"));
$endDate = date('Y-m-d');

// ==========================================
// Gather KPI Data
// ==========================================

// Content KPIs
$row = Database::fetchOne(
    "SELECT COUNT(*) as count FROM posts WHERE status = 'published' AND created_at >= :start",
    ['start' => $startDate]
);
$articlesThisPeriod = $row['count'] ?? 0;

$row = Database::fetchOne(
    "SELECT COUNT(*) as count FROM posts WHERE status = 'published' AND created_at >= :prev AND created_at < :start",
    ['prev' => date('Y-m-d', strtotime("-" . ($period * 2) . " days")), 'start' => $startDate]
);
$articlesPreviousPeriod = $row['count'] ?? 0;

$row = Database::fetchOne(
    "SELECT SUM(views) as total FROM posts WHERE status = 'published'"
);
$totalViews = $row['total'] ?? 0;

$row = Database::fetchOne(
    "SELECT AVG(views) as avg FROM posts WHERE status = 'published' AND created_at >= :start",
    ['start' => $startDate]
);
$avgViewsPerPost = $row['avg'] ?? 0;

// Engagement KPIs
$row = Database::fetchOne(
    "SELECT COUNT(*) as count FROM comments WHERE created_at >= :start",
    ['start' => $startDate]
);
$commentsThisPeriod = $row['count'] ?? 0;

$commentsPerArticle = 0;
if ($articlesThisPeriod > 0) {
    $commentsPerArticle = round($commentsThisPeriod / $articlesThisPeriod, 1);
}

$pollVotesToday = 0;
$pollsDir = __DIR__ . '/../data/polls';
if (is_dir($pollsDir)) {
    foreach (glob($pollsDir . '/*.json') as $pollFile) {
        $poll = json_decode(file_get_contents($pollFile), true);
        if (isset($poll['votes'])) {
            foreach ($poll['votes'] as $vote) {
                if (isset($vote['timestamp']) && date('Y-m-d', strtotime($vote['timestamp'])) === date('Y-m-d')) {
                    $pollVotesToday++;
                }
            }
        }
    }
}

$row = Database::fetchOne(
    "SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'"
);
$newsletterSubscribers = $row['count'] ?? 0;

$row = Database::fetchOne(
    "SELECT COUNT(*) as count FROM newsletter_subscribers WHERE created_at >= :start",
    ['start' => $startDate]
);
$newSubscribersThisPeriod = $row['count'] ?? 0;

// Technical KPIs
$logsDir = __DIR__ . '/../data/logs';
$errorCount = 0;
$apiLatency = [];

// Count errors in last period
$currentDate = $startDate;
while ($currentDate <= $endDate) {
    $errorFile = $logsDir . "/error-{$currentDate}.log";
    if (file_exists($errorFile)) {
        $errorCount += substr_count(file_get_contents($errorFile), "\n");
    }
    
    // Parse API logs for latency
    $apiFile = $logsDir . "/api-{$currentDate}.log";
    if (file_exists($apiFile)) {
        $lines = file($apiFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match('/\((\d+(?:\.\d+)?)ms\)/', $line, $matches)) {
                $apiLatency[] = (float)$matches[1];
            }
        }
    }
    
    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
}

$avgApiLatency = count($apiLatency) > 0 ? round(array_sum($apiLatency) / count($apiLatency), 1) : 0;

// Cache stats
$cacheDir = __DIR__ . '/../data/cache';
$cacheFiles = is_dir($cacheDir) ? count(glob($cacheDir . '/*.cache')) : 0;

// Health check
$healthFile = __DIR__ . '/../health.php';
$healthStatus = 'unknown';
if (file_exists($healthFile)) {
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include($healthFile);
    $healthOutput = ob_get_clean();
    if (strpos($healthOutput, '"status":"healthy"') !== false) {
        $healthStatus = 'healthy';
    } elseif (strpos($healthOutput, '"status":"degraded"') !== false) {
        $healthStatus = 'degraded';
    } else {
        $healthStatus = 'unhealthy';
    }
}

// ==========================================
// KPI Targets
// ==========================================
$targets = [
    'articles_per_week' => 5,
    'avg_time_on_page' => 120, // seconds
    'comments_per_article' => 5,
    'poll_votes_per_day' => 50,
    'newsletter_signup_rate' => 2, // percent
    'api_latency' => 200, // ms
    'error_rate' => 0.1, // percent
    'cache_hit_rate' => 80, // percent
];

// Calculate weekly article rate
$weeksInPeriod = max(1, $period / 7);
$articlesPerWeek = round($articlesThisPeriod / $weeksInPeriod, 1);

// ==========================================
// Helper Functions
// ==========================================
function kpiStatus($actual, $target, $higherIsBetter = true) {
    if ($higherIsBetter) {
        if ($actual >= $target) return 'success';
        if ($actual >= $target * 0.7) return 'warning';
        return 'danger';
    } else {
        if ($actual <= $target) return 'success';
        if ($actual <= $target * 1.5) return 'warning';
        return 'danger';
    }
}

function kpiPercent($actual, $target, $higherIsBetter = true) {
    if ($target == 0) return 0;
    $percent = ($actual / $target) * 100;
    return min(100, max(0, $higherIsBetter ? $percent : (200 - $percent)));
}

function formatChange($current, $previous) {
    if ($previous == 0) return '<span class="text-muted">N/A</span>';
    $change = (($current - $previous) / $previous) * 100;
    $class = $change >= 0 ? 'text-success' : 'text-danger';
    $icon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
    return sprintf('<span class="%s"><i class="fas %s me-1"></i>%s%%</span>', $class, $icon, number_format(abs($change), 1));
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kpis-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Categorie', 'KPI', 'Valoare', 'Target', 'Status']);
    
    fputcsv($output, ['Conținut', 'Articole/săptămână', $articlesPerWeek, $targets['articles_per_week'], $articlesPerWeek >= $targets['articles_per_week'] ? 'OK' : 'Sub target']);
    fputcsv($output, ['Conținut', 'Vizualizări medii/articol', round($avgViewsPerPost), '-', '-']);
    fputcsv($output, ['Engagement', 'Comentarii/articol', $commentsPerArticle, $targets['comments_per_article'], $commentsPerArticle >= $targets['comments_per_article'] ? 'OK' : 'Sub target']);
    fputcsv($output, ['Engagement', 'Voturi sondaje/zi', $pollVotesToday, $targets['poll_votes_per_day'], $pollVotesToday >= $targets['poll_votes_per_day'] ? 'OK' : 'Sub target']);
    fputcsv($output, ['Engagement', 'Abonați newsletter', $newsletterSubscribers, '-', '-']);
    fputcsv($output, ['Tehnic', 'Erori în perioada', $errorCount, '<10', $errorCount < 10 ? 'OK' : 'Verifică']);
    fputcsv($output, ['Tehnic', 'API latency (avg)', $avgApiLatency . 'ms', $targets['api_latency'] . 'ms', $avgApiLatency <= $targets['api_latency'] ? 'OK' : 'Lent']);
    fputcsv($output, ['Tehnic', 'Fișiere în cache', $cacheFiles, '-', '-']);
    
    fclose($output);
    exit;
}
?>

<!-- Page Header -->
<div class="admin-page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-bullseye me-2"></i>KPIs Dashboard</h1>
        <p class="text-muted mb-0">Indicatori cheie de performanță - Ultimele <?= $period ?> zile</p>
    </div>
    <div>
        <div class="btn-group me-2">
            <a href="?period=7" class="btn btn-outline-secondary btn-sm <?= $period == '7' ? 'active' : '' ?>">7 zile</a>
            <a href="?period=30" class="btn btn-outline-secondary btn-sm <?= $period == '30' ? 'active' : '' ?>">30 zile</a>
            <a href="?period=90" class="btn btn-outline-secondary btn-sm <?= $period == '90' ? 'active' : '' ?>">90 zile</a>
        </div>
        <a href="?period=<?= $period ?>&export=csv" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-csv me-1"></i>Export
        </a>
    </div>
</div>

<!-- System Health -->
<div class="alert alert-<?= $healthStatus === 'healthy' ? 'success' : ($healthStatus === 'degraded' ? 'warning' : 'danger') ?> mb-4">
    <i class="fas fa-<?= $healthStatus === 'healthy' ? 'check-circle' : ($healthStatus === 'degraded' ? 'exclamation-triangle' : 'times-circle') ?> me-2"></i>
    <strong>Status Sistem:</strong> 
    <?= $healthStatus === 'healthy' ? 'Toate serviciile funcționează normal' : ($healthStatus === 'degraded' ? 'Unele servicii au probleme' : 'Verifică health check') ?>
    <a href="../health.php" target="_blank" class="ms-2">Vezi detalii</a>
</div>

<!-- Content KPIs -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h2><i class="fas fa-newspaper text-primary me-2"></i>Conținut</h2>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <!-- Articles per Week -->
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Articole / Săptămână</span>
                        <span class="badge bg-<?= kpiStatus($articlesPerWeek, $targets['articles_per_week']) ?>">
                            <?= $articlesPerWeek >= $targets['articles_per_week'] ? 'On Target' : 'Sub Target' ?>
                        </span>
                    </div>
                    <div class="kpi-value"><?= $articlesPerWeek ?></div>
                    <div class="kpi-target">Target: <?= $targets['articles_per_week'] ?></div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-<?= kpiStatus($articlesPerWeek, $targets['articles_per_week']) ?>" 
                             style="width: <?= kpiPercent($articlesPerWeek, $targets['articles_per_week']) ?>%"></div>
                    </div>
                    <div class="kpi-change mt-2">
                        vs perioadă anterioară: <?= formatChange($articlesThisPeriod, $articlesPreviousPeriod) ?>
                    </div>
                </div>
            </div>
            
            <!-- Total Articles -->
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Articole în Perioada</span>
                    </div>
                    <div class="kpi-value"><?= number_format($articlesThisPeriod) ?></div>
                    <div class="kpi-sub">Din total: <?= number_format(Post::getCount()) ?> articole publicate</div>
                </div>
            </div>
            
            <!-- Avg Views -->
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Vizualizări Medii / Articol</span>
                    </div>
                    <div class="kpi-value"><?= number_format(round($avgViewsPerPost)) ?></div>
                    <div class="kpi-sub">Total views: <?= number_format($totalViews) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Engagement KPIs -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h2><i class="fas fa-comments text-success me-2"></i>Engagement</h2>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <!-- Comments per Article -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Comentarii / Articol</span>
                        <span class="badge bg-<?= kpiStatus($commentsPerArticle, $targets['comments_per_article']) ?>">
                            <?= $commentsPerArticle >= $targets['comments_per_article'] ? 'OK' : 'Sub' ?>
                        </span>
                    </div>
                    <div class="kpi-value"><?= $commentsPerArticle ?></div>
                    <div class="kpi-target">Target: <?= $targets['comments_per_article'] ?></div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-<?= kpiStatus($commentsPerArticle, $targets['comments_per_article']) ?>" 
                             style="width: <?= kpiPercent($commentsPerArticle, $targets['comments_per_article']) ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Total Comments -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Total Comentarii</span>
                    </div>
                    <div class="kpi-value"><?= number_format($commentsThisPeriod) ?></div>
                    <div class="kpi-sub">În ultimele <?= $period ?> zile</div>
                </div>
            </div>
            
            <!-- Poll Votes Today -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Voturi Sondaje Azi</span>
                        <span class="badge bg-<?= kpiStatus($pollVotesToday, $targets['poll_votes_per_day']) ?>">
                            <?= $pollVotesToday >= $targets['poll_votes_per_day'] ? 'OK' : 'Sub' ?>
                        </span>
                    </div>
                    <div class="kpi-value"><?= number_format($pollVotesToday) ?></div>
                    <div class="kpi-target">Target: <?= $targets['poll_votes_per_day'] ?>/zi</div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-<?= kpiStatus($pollVotesToday, $targets['poll_votes_per_day']) ?>" 
                             style="width: <?= kpiPercent($pollVotesToday, $targets['poll_votes_per_day']) ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Abonați Newsletter</span>
                    </div>
                    <div class="kpi-value"><?= number_format($newsletterSubscribers) ?></div>
                    <div class="kpi-sub text-success">
                        <i class="fas fa-arrow-up me-1"></i>+<?= $newSubscribersThisPeriod ?> noi
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Technical KPIs -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h2><i class="fas fa-server text-info me-2"></i>Tehnic</h2>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <!-- Errors -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Erori în Perioada</span>
                        <span class="badge bg-<?= $errorCount < 10 ? 'success' : ($errorCount < 50 ? 'warning' : 'danger') ?>">
                            <?= $errorCount < 10 ? 'OK' : 'Verifică' ?>
                        </span>
                    </div>
                    <div class="kpi-value <?= $errorCount > 50 ? 'text-danger' : '' ?>"><?= number_format($errorCount) ?></div>
                    <div class="kpi-sub">
                        <a href="logs.php?channel=error">Vezi loguri</a>
                    </div>
                </div>
            </div>
            
            <!-- API Latency -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">API Latency (avg)</span>
                        <span class="badge bg-<?= kpiStatus($avgApiLatency, $targets['api_latency'], false) ?>">
                            <?= $avgApiLatency <= $targets['api_latency'] ? 'OK' : 'Lent' ?>
                        </span>
                    </div>
                    <div class="kpi-value"><?= $avgApiLatency ?><small>ms</small></div>
                    <div class="kpi-target">Target: < <?= $targets['api_latency'] ?>ms</div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-<?= kpiStatus($avgApiLatency, $targets['api_latency'], false) ?>" 
                             style="width: <?= min(100, ($targets['api_latency'] / max(1, $avgApiLatency)) * 100) ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Cache -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Fișiere în Cache</span>
                    </div>
                    <div class="kpi-value"><?= number_format($cacheFiles) ?></div>
                    <div class="kpi-sub">Cache activ: <?= CACHE_ENABLED ? 'Da' : 'Nu' ?></div>
                </div>
            </div>
            
            <!-- Health Status -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Health Status</span>
                    </div>
                    <div class="kpi-value">
                        <?php if ($healthStatus === 'healthy'): ?>
                            <i class="fas fa-check-circle text-success"></i>
                        <?php elseif ($healthStatus === 'degraded'): ?>
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-danger"></i>
                        <?php endif; ?>
                    </div>
                    <div class="kpi-sub"><?= ucfirst($healthStatus) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-bolt text-warning me-2"></i>Acțiuni Rapide</h2>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="new-post.php" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-2"></i>Articol Nou
                </a>
            </div>
            <div class="col-md-3">
                <a href="polls.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-poll me-2"></i>Sondaje
                </a>
            </div>
            <div class="col-md-3">
                <a href="logs.php?channel=error" class="btn btn-outline-danger w-100">
                    <i class="fas fa-bug me-2"></i>Erori
                </a>
            </div>
            <div class="col-md-3">
                <a href="stats.php" class="btn btn-outline-info w-100">
                    <i class="fas fa-chart-line me-2"></i>Statistici Detaliate
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.kpi-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.75rem;
    padding: 1.25rem;
    height: 100%;
    transition: box-shadow 0.2s ease;
}
.kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}
.kpi-title {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}
.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.2;
}
.kpi-value small {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
}
.kpi-target {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}
.kpi-sub {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.5rem;
}
.kpi-change {
    font-size: 0.8rem;
}
</style>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
