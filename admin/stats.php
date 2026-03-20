<?php
/**
 * Admin Statistics Page
 * MatchDay.ro - Visitor Analytics Dashboard
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Stats.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Get stats data
$summary = Stats::getSummary();
$dailyStats = Stats::getViewsLastDays(30);
$topPosts = Stats::getMostViewedPosts(10, 30);
$hourlyStats = Stats::getTodayHourlyStats();
$topReferers = Stats::getTopReferers(10, 7);

// Prepare chart data
$chartLabels = [];
$chartViews = [];
$chartUnique = [];

foreach ($dailyStats as $day) {
    $chartLabels[] = date('d M', strtotime($day['date']));
    $chartViews[] = (int) $day['views'];
    $chartUnique[] = (int) $day['unique_visitors'];
}

// Prepare hourly chart
$hourlyLabels = [];
$hourlyValues = [];
for ($h = 0; $h < 24; $h++) {
    $hourlyLabels[] = sprintf('%02d:00', $h);
    $hourlyValues[] = $hourlyStats[$h] ?? 0;
}

$pageTitle = 'Statistici';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-chart-line me-2"></i>Statistici Vizitatori</h1>
    <span class="text-muted">Ultima actualizare: <?= date('d.m.Y H:i') ?></span>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-eye"></i></div>
            <div class="stat-content">
                <h3><?= number_format($summary['today_views']) ?></h3>
                <p>Vizualizări azi</p>
                <small class="text-muted"><i class="fas fa-users me-1"></i><?= number_format($summary['today_unique']) ?> unici</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-content">
                <h3><?= number_format($summary['yesterday_views']) ?></h3>
                <p>Vizualizări ieri</p>
                <?php 
                $change = $summary['today_views'] - $summary['yesterday_views'];
                $changeClass = $change >= 0 ? 'text-success' : 'text-danger';
                $changeIcon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                ?>
                <small class="<?= $changeClass ?>"><i class="fas <?= $changeIcon ?> me-1"></i><?= abs($change) ?> vs azi</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-content">
                <h3><?= number_format($summary['this_week_views']) ?></h3>
                <p>Săptămâna asta</p>
                <?php 
                $weekChange = $summary['this_week_views'] - $summary['last_week_views'];
                $weekClass = $weekChange >= 0 ? 'text-success' : 'text-danger';
                $weekIcon = $weekChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                ?>
                <small class="<?= $weekClass ?>"><i class="fas <?= $weekIcon ?> me-1"></i><?= abs($weekChange) ?> vs săpt. trec.</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-chart-bar"></i></div>
            <div class="stat-content">
                <h3><?= number_format($summary['total_views']) ?></h3>
                <p>Total vizualizări</p>
                <small class="text-muted"><i class="fas fa-users me-1"></i><?= number_format($summary['total_unique']) ?> unici</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Daily Views Chart -->
    <div class="col-lg-8">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h2><i class="fas fa-chart-area me-2"></i>Vizualizări ultimele 30 zile</h2>
            </div>
            <div class="p-3">
                <canvas id="dailyChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Hourly Distribution -->
    <div class="col-lg-4">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h2><i class="fas fa-clock me-2"></i>Distribuție orară</h2>
            </div>
            <div class="p-3">
                <canvas id="hourlyChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-4">
    <!-- Top Posts -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fas fa-fire me-2"></i>Articole populare (30 zile)</h2>
            </div>
            <?php if (empty($topPosts)): ?>
            <p class="text-muted p-3 mb-0">Nu există date încă.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Articol</th>
                            <th class="text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPosts as $i => $post): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td>
                                <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" target="_blank" class="text-decoration-none">
                                    <?= Security::sanitizeInput(mb_substr($post['title'], 0, 45)) ?><?= mb_strlen($post['title']) > 45 ? '...' : '' ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-primary"><?= number_format($post['total_views']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Traffic Sources -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fas fa-globe me-2"></i>Surse de trafic (7 zile)</h2>
            </div>
            <?php if (empty($topReferers)): ?>
            <p class="text-muted p-3 mb-0">Nu există date încă.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Sursă</th>
                            <th class="text-end">Vizite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalVisits = array_sum(array_column($topReferers, 'visits'));
                        foreach ($topReferers as $ref): 
                            $percent = $totalVisits > 0 ? round(($ref['visits'] / $totalVisits) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <?php if ($ref['source'] === 'Direct'): ?>
                                    <i class="fas fa-link text-muted me-1"></i>
                                <?php elseif (strpos($ref['source'], 'google') !== false): ?>
                                    <i class="fab fa-google text-danger me-1"></i>
                                <?php elseif (strpos($ref['source'], 'facebook') !== false): ?>
                                    <i class="fab fa-facebook text-primary me-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-external-link-alt text-secondary me-1"></i>
                                <?php endif; ?>
                                <?= Security::sanitizeInput(mb_substr($ref['source'], 0, 25)) ?>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-secondary"><?= number_format($ref['visits']) ?></span>
                                <small class="text-muted">(<?= $percent ?>%)</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Info Footer -->
<div class="admin-card mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap p-3">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Statisticile sunt actualizate în timp real. Vizitatorii unici sunt identificați prin IP hash zilnic (GDPR compliant).
            Datele mai vechi de 90 de zile sunt șterse automat.
        </small>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Views Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Vizualizări',
                data: <?= json_encode($chartViews) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3
            },
            {
                label: 'Vizitatori unici',
                data: <?= json_encode($chartUnique) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Hourly Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($hourlyLabels) ?>,
        datasets: [{
            label: 'Vizite',
            data: <?= json_encode($hourlyValues) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.7)',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                ticks: {
                    maxTicksLimit: 12
                }
            }
        }
    }
});
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
