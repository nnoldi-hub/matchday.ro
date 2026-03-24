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
        header('Location: /clasamente.php');
        exit;
    }
    
    // SEO for specific league
    $pageTitle = $currentLeague['name'] . ' - Clasament și Statistici | ' . SITE_NAME;
    $pageDescription = $currentLeague['description'] ?? 'Clasament actualizat ' . $currentLeague['name'] . ', statistici echipe, rezultate și analize.';
    $pageKeywords = ['clasament', 'fotbal', strtolower($currentLeague['name']), 'statistici', 'golaveraj', 'puncte'];
    
    $breadcrumbs = [
        ['name' => 'Acasă', 'url' => '/index.php'],
        ['name' => 'Clasamente', 'url' => '/clasamente.php'],
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
                    <a href="/clasamente.php?liga=<?= urlencode($league['slug']) ?>" class="btn btn-outline-primary btn-sm w-100">
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
                <li class="breadcrumb-item"><a href="/clasamente.php">Clasamente</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($currentLeague['name']) ?></li>
            </ol>
        </nav>
    </div>
    
    <!-- League Standings Widget Placeholder -->
    <div class="card mb-4 standings-widget">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-table me-2"></i>
            Clasament <?= htmlspecialchars($currentLeague['name']) ?>
        </div>
        <div class="card-body">
            <div class="standings-placeholder text-center py-4">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">
                    Clasamentul va fi afișat aici.
                    <br>
                    <small>Poți integra date din API-uri externe pentru clasamente live.</small>
                </p>
                <a href="#articole" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-newspaper me-1"></i> Vezi articolele
                </a>
            </div>
        </div>
    </div>
    
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
                    <a class="page-link" href="?liga=<?= urlencode($leagueSlug) ?>&page=<?= $page - 1 ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?liga=<?= urlencode($leagueSlug) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?liga=<?= urlencode($leagueSlug) ?>&page=<?= $page + 1 ?>">
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

.standings-widget .card-header {
    border-radius: 0;
}

@media (max-width: 768px) {
    .clasamente-header h1 {
        font-size: 2rem;
    }
    
    .league-icon-large {
        width: 48px;
        height: 48px;
    }
}
</style>

<?php include(__DIR__ . '/includes/footer.php'); ?>
