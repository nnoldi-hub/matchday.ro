<?php
/**
 * Clasamente Hub Page
 * MatchDay.ro - Central hub for international league rankings
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Category.php');
require_once(__DIR__ . '/includes/Stats.php');
require_once(__DIR__ . '/includes/Ad.php');
require_once(__DIR__ . '/includes/AdWidget.php');
require_once(__DIR__ . '/includes/seo.php');

// Track page visit
Stats::trackView(null, 'clasamente');

// Load standings data
$standingsFile = __DIR__ . '/data/standings.json';
$standingsData = file_exists($standingsFile) ? json_decode(file_get_contents($standingsFile), true) : null;

// Get league parameter if viewing specific league
$leagueSlug = $_GET['liga'] ?? null;

// Get parent category "clasamente"
$clasamenteCategory = Category::getBySlug('clasamente');

// Get all child categories (leagues)
$leagues = Category::getChildren('clasamente');

// If viewing specific league
if ($leagueSlug) {
    $currentLeague = Category::getBySlug($leagueSlug);
    
    if (!$currentLeague || $currentLeague['parent_slug'] !== 'clasamente') {
        // Invalid league, redirect to main
        header('Location: /clasamente');
        exit;
    }
    
    // SEO for specific league
    $pageTitle = $currentLeague['name'] . ' - Clasament și Statistici | ' . SITE_NAME;
    $pageDescription = $currentLeague['description'] ?? 'Clasament actualizat ' . $currentLeague['name'] . ', statistici echipe, rezultate și analize.';
    $pageKeywords = ['clasament', 'fotbal', strtolower($currentLeague['name']), 'statistici', 'golaveraj', 'puncte'];
    
    $breadcrumbs = [
        ['name' => 'Acasă', 'url' => '/index.php'],
        ['name' => 'Clasamente', 'url' => '/clasamente'],
        ['name' => $currentLeague['name']]
    ];
    
    // Get posts for this specific league
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 12;
    $posts = Post::getPublished($page, $perPage, $leagueSlug, null);
    $total = Post::countPublished($leagueSlug, null);
    
} else {
    // Main Clasamente hub page
    $pageTitle = 'Clasamente - Ligi Internaționale | ' . SITE_NAME;
    $pageDescription = 'Clasamente actualizate din marile campionate europene: Premier League, La Liga, Serie A, Bundesliga și multe altele.';
    $pageKeywords = ['clasamente', 'fotbal', 'premier league', 'la liga', 'serie a', 'bundesliga', 'ligue 1', 'statistici'];
    
    $breadcrumbs = [
        ['name' => 'Acasă', 'url' => '/index.php'],
        ['name' => 'Clasamente']
    ];
    
    // Get recent posts from all league categories
    $leagueSlugs = array_column($leagues, 'slug');
    $posts = [];
    if (!empty($leagueSlugs)) {
        $posts = Post::getFromCategories($leagueSlugs, 1, 9);
    }
    $total = count($posts);
    $page = 1;
    $perPage = 9;
}

$pageType = 'website';
$pageBodyClass = 'page-clasamente';

include(__DIR__ . '/includes/header.php');
?>

<div class="container my-5">
    <?php if (!$leagueSlug): ?>
    <!-- Main Clasamente Hub -->
    <div class="clasamente-header text-center mb-5">
        <h1 class="display-4 fw-bold">
            <i class="fas fa-list-ol me-2" style="color: #0d6efd;"></i>
            Clasamente Internaționale
        </h1>
        <p class="lead text-muted">
            Urmărește clasamentele din cele mai importante campionate europene
        </p>
    </div>
    
    <!-- League Cards Grid -->
    <div class="row g-4 mb-5">
        <?php foreach ($leagues as $league): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 league-card shadow-sm border-0" style="border-top: 4px solid <?= htmlspecialchars($league['color']) ?> !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="league-icon me-3" style="background-color: <?= htmlspecialchars($league['color']) ?>;">
                            <i class="<?= htmlspecialchars($league['icon']) ?> fa-lg text-white"></i>
                        </div>
                        <h3 class="card-title mb-0 h5"><?= htmlspecialchars($league['name']) ?></h3>
                    </div>
                    <p class="card-text text-muted small">
                        <?= htmlspecialchars($league['description']) ?>
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="/clasamente/<?= urlencode($league['slug']) ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-chart-bar me-1"></i> Vezi clasament
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($leagues)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Categoriile de clasamente vor fi disponibile în curând. 
        <a href="/migrate-clasamente.php" class="alert-link">Rulează migrarea</a> pentru a le activa.
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- Specific League Page -->
    <div class="league-header mb-5">
        <div class="d-flex align-items-center mb-3">
            <div class="league-icon-large me-3" style="background-color: <?= htmlspecialchars($currentLeague['color']) ?>;">
                <i class="<?= htmlspecialchars($currentLeague['icon']) ?> fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="mb-1"><?= htmlspecialchars($currentLeague['name']) ?></h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($currentLeague['description']) ?></p>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Acasă</a></li>
                <li class="breadcrumb-item"><a href="/clasamente">Clasamente</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($currentLeague['name']) ?></li>
            </ol>
        </nav>
    </div>
    
    <!-- League Standings Widget -->
    <?php 
    $leagueStandings = null;
    if ($standingsData && isset($standingsData['leagues'][$leagueSlug])) {
        $leagueStandings = $standingsData['leagues'][$leagueSlug];
    }
    ?>
    
    <?php if ($leagueStandings): ?>
    <div class="card mb-4 standings-widget shadow">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: <?= htmlspecialchars($currentLeague['color']) ?>;">
            <span class="text-white fw-bold">
                <i class="fas fa-table me-2"></i>
                Clasament <?= htmlspecialchars($currentLeague['name']) ?> 2025/2026
            </span>
            <span class="badge bg-light text-dark">Etapa <?= $leagueStandings['matchday'] ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover standings-table mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Echipă</th>
                            <th class="text-center d-none d-md-table-cell">MJ</th>
                            <th class="text-center d-none d-lg-table-cell">V</th>
                            <th class="text-center d-none d-lg-table-cell">E</th>
                            <th class="text-center d-none d-lg-table-cell">Î</th>
                            <th class="text-center d-none d-md-table-cell">G</th>
                            <th class="text-center d-none d-sm-table-cell">DG</th>
                            <th class="text-center fw-bold">P</th>
                            <th class="text-center d-none d-md-table-cell">Formă</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $zones = $leagueStandings['zones'] ?? [];
                        foreach ($leagueStandings['standings'] as $team): 
                            $pos = $team['pos'];
                            $rowClass = '';
                            $zoneIndicator = '';
                            
                            // Determine zone colors
                            if (in_array($pos, $zones['champions_league'] ?? [])) {
                                $rowClass = 'zone-ucl';
                                $zoneIndicator = '<span class="zone-dot bg-primary" title="Champions League"></span>';
                            } elseif (in_array($pos, $zones['champions_league_qual'] ?? [])) {
                                $rowClass = 'zone-ucl-qual';
                                $zoneIndicator = '<span class="zone-dot bg-info" title="UCL Calificare"></span>';
                            } elseif (in_array($pos, $zones['europa_league'] ?? [])) {
                                $rowClass = 'zone-uel';
                                $zoneIndicator = '<span class="zone-dot bg-warning" title="Europa League"></span>';
                            } elseif (in_array($pos, $zones['conference_league'] ?? []) || in_array($pos, $zones['conference_league_qual'] ?? [])) {
                                $rowClass = 'zone-uecl';
                                $zoneIndicator = '<span class="zone-dot bg-success" title="Conference League"></span>';
                            } elseif (in_array($pos, $zones['relegation_playoff'] ?? [])) {
                                $rowClass = 'zone-relegation-playoff';
                                $zoneIndicator = '<span class="zone-dot bg-secondary" title="Baraj retrogradare"></span>';
                            } elseif (in_array($pos, $zones['relegation'] ?? [])) {
                                $rowClass = 'zone-relegation';
                                $zoneIndicator = '<span class="zone-dot bg-danger" title="Retrogradare"></span>';
                            }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="text-center position-cell">
                                <?= $zoneIndicator ?>
                                <span class="position-number"><?= $pos ?></span>
                            </td>
                            <td class="team-cell">
                                <span class="team-name"><?= htmlspecialchars($team['team']) ?></span>
                            </td>
                            <td class="text-center d-none d-md-table-cell"><?= $team['played'] ?></td>
                            <td class="text-center d-none d-lg-table-cell text-success"><?= $team['wins'] ?></td>
                            <td class="text-center d-none d-lg-table-cell text-warning"><?= $team['draws'] ?></td>
                            <td class="text-center d-none d-lg-table-cell text-danger"><?= $team['losses'] ?></td>
                            <td class="text-center d-none d-md-table-cell">
                                <small><?= $team['goals_for'] ?>:<?= $team['goals_against'] ?></small>
                            </td>
                            <td class="text-center d-none d-sm-table-cell <?= $team['gd'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $team['gd'] >= 0 ? '+' : '' ?><?= $team['gd'] ?>
                            </td>
                            <td class="text-center fw-bold points-cell"><?= $team['points'] ?></td>
                            <td class="text-center d-none d-md-table-cell form-cell">
                                <?php foreach ($team['form'] as $result): ?>
                                    <?php 
                                    $formClass = match($result) {
                                        'V' => 'form-win',
                                        'E' => 'form-draw', 
                                        'I' => 'form-loss',
                                        default => ''
                                    };
                                    ?>
                                    <span class="form-badge <?= $formClass ?>"><?= $result ?></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="d-flex flex-wrap gap-3 small">
                <span><span class="zone-dot bg-primary"></span> Champions League</span>
                <span><span class="zone-dot bg-warning"></span> Europa League</span>
                <span><span class="zone-dot bg-success"></span> Conference League</span>
                <span><span class="zone-dot bg-danger"></span> Retrogradare</span>
            </div>
            <div class="text-muted mt-2 small">
                <i class="fas fa-sync-alt me-1"></i> Actualizat: <?= date('d.m.Y', strtotime($standingsData['last_updated'])) ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-4 standings-widget">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-table me-2"></i>
            Clasament <?= htmlspecialchars($currentLeague['name']) ?>
        </div>
        <div class="card-body">
            <div class="standings-placeholder text-center py-4">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">
                    Clasamentul pentru această ligă nu este disponibil momentan.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-box text-center p-3 rounded bg-light">
                <div class="stat-value h3 mb-0" style="color: <?= htmlspecialchars($currentLeague['color']) ?>;">
                    <?= $total ?>
                </div>
                <small class="text-muted">Articole</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box text-center p-3 rounded bg-light">
                <div class="stat-value h3 mb-0">20</div>
                <small class="text-muted">Echipe</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box text-center p-3 rounded bg-light">
                <div class="stat-value h3 mb-0">38</div>
                <small class="text-muted">Etape</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box text-center p-3 rounded bg-light">
                <div class="stat-value h3 mb-0">2025-26</div>
                <small class="text-muted">Sezon</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Articles Section -->
    <div id="articole" class="articles-section">
        <h2 class="mb-4">
            <i class="fas fa-newspaper me-2 text-primary"></i>
            <?= $leagueSlug ? 'Articole ' . htmlspecialchars($currentLeague['name']) : 'Cele mai recente articole' ?>
        </h2>
        
        <?php if (!empty($posts)): ?>
        <div class="row g-4">
            <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card h-100 article-card shadow-sm">
                    <?php if (!empty($post['cover_image'])): ?>
                    <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         style="height: 180px; object-fit: cover;">
                    <?php else: ?>
                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 180px;">
                        <i class="fas fa-futbol fa-3x text-white-50"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?= SEOManager::getArticleUrl($post['slug']) ?>" class="text-decoration-none text-dark stretched-link">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h5>
                        <?php if (!empty($post['excerpt'])): ?>
                        <p class="card-text small text-muted">
                            <?= htmlspecialchars(substr($post['excerpt'], 0, 120)) ?>...
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top-0">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('d M Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                            <?php if (!empty($post['views'])): ?>
                            <span class="ms-2">
                                <i class="fas fa-eye me-1"></i><?= number_format($post['views']) ?>
                            </span>
                            <?php endif; ?>
                        </small>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($leagueSlug && $total > $perPage): ?>
        <!-- Pagination -->
        <nav class="mt-5" aria-label="Paginare articole">
            <?php
            $totalPages = ceil($total / $perPage);
            ?>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="/clasamente/<?= urlencode($leagueSlug) ?>?page=<?= $page - 1 ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/clasamente/<?= urlencode($leagueSlug) ?>?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="/clasamente/<?= urlencode($leagueSlug) ?>?page=<?= $page + 1 ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Nu există încă articole în această categorie. Revin cu conținut în curând!
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$leagueSlug): ?>
    <!-- Additional Info Section -->
    <div class="row mt-5">
        <div class="col-lg-8">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h3><i class="fas fa-info-circle me-2 text-info"></i>Despre secțiunea Clasamente</h3>
                    <p>
                        Aici găsești clasamentele actualizate din cele mai importante campionate europene. 
                        Fiecare ligă are propria pagină cu:
                    </p>
                    <ul>
                        <li><strong>Clasament actualizat</strong> - Poziții, puncte, golaveraj</li>
                        <li><strong>Formă echipe</strong> - Ultimele 5 meciuri</li>
                        <li><strong>Statistici</strong> - Top marcatori, asisturi</li>
                        <li><strong>Articole dedicate</strong> - Analize și știri din ligă</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
                <div class="card-body text-white text-center py-4">
                    <i class="fas fa-bell fa-2x mb-3"></i>
                    <h4>Vrei notificări?</h4>
                    <p class="small mb-3">
                        Abonează-te la newsletter pentru actualizări despre clasamente
                    </p>
                    <a href="/newsletter.php" class="btn btn-light btn-sm">
                        <i class="fas fa-envelope me-1"></i> Abonează-te
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.clasamente-header {
    padding: 2rem 0;
}

.league-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-top: 4px solid transparent;
}

.league-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.league-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.league-icon-large {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-box {
    transition: transform 0.2s;
}

.stat-box:hover {
    transform: scale(1.05);
}

.article-card {
    transition: transform 0.2s;
}

.article-card:hover {
    transform: translateY(-3px);
}

/* Standings Table Styles */
.standings-table {
    font-size: 0.9rem;
}

.standings-table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.standings-table td {
    vertical-align: middle;
    padding: 0.6rem 0.5rem;
}

.standings-table tbody tr {
    transition: background-color 0.15s;
}

.standings-table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
}

/* Position cell */
.position-cell {
    position: relative;
    font-weight: 700;
}

.position-number {
    display: inline-block;
    min-width: 24px;
}

/* Zone indicators */
.zone-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 4px;
}

/* Zone row colors */
.zone-ucl {
    border-left: 3px solid #0d6efd;
}

.zone-ucl-qual {
    border-left: 3px solid #0dcaf0;
}

.zone-uel {
    border-left: 3px solid #fd7e14;
}

.zone-uecl {
    border-left: 3px solid #198754;
}

.zone-relegation-playoff {
    border-left: 3px solid #6c757d;
    background-color: rgba(108, 117, 125, 0.05);
}

.zone-relegation {
    border-left: 3px solid #dc3545;
    background-color: rgba(220, 53, 69, 0.05);
}

/* Team cell */
.team-cell {
    font-weight: 500;
}

.team-name {
    white-space: nowrap;
}

/* Points cell */
.points-cell {
    font-size: 1rem;
    background-color: rgba(0, 0, 0, 0.02);
}

/* Form badges */
.form-cell {
    white-space: nowrap;
}

.form-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
    margin: 0 1px;
    color: white;
}

.form-win {
    background-color: #198754;
}

.form-draw {
    background-color: #ffc107;
    color: #212529;
}

.form-loss {
    background-color: #dc3545;
}

/* Card footer legend */
.card-footer .zone-dot {
    vertical-align: middle;
}

@media (max-width: 768px) {
    .clasamente-header h1 {
        font-size: 2rem;
    }
    
    .league-icon-large {
        width: 48px;
        height: 48px;
    }
    
    .standings-table {
        font-size: 0.85rem;
    }
    
    .standings-table td {
        padding: 0.5rem 0.3rem;
    }
    
    .form-badge {
        width: 18px;
        height: 18px;
        font-size: 0.65rem;
    }
}
</style>

<?php include(__DIR__ . '/includes/footer.php'); ?>
