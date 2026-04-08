<?php
/**
 * Author Profile Page
 * MatchDay.ro - Show author bio, stats, and articles
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/User.php');
require_once(__DIR__ . '/includes/Stats.php');
require_once(__DIR__ . '/includes/seo.php');

// Track author page visit
Stats::trackView(null, 'author');

// Get author ID or username from URL
$authorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$authorUsername = isset($_GET['name']) ? Security::sanitizeInput($_GET['name']) : '';

// Fetch author
$author = null;
if ($authorId > 0) {
    $author = User::getById($authorId);
} elseif ($authorUsername) {
    $author = User::getByUsername($authorUsername);
}

// Extended author profiles (for now, manually defined)
$authorProfiles = [
    'david' => [
        'display_name' => 'David Nyikora',
        'bio' => 'Jurnalist sportiv pasionat cu peste 10 ani de experiență în acoperirea evenimentelor fotbalistice din România și Europa. Specializat în analize tactice, interviuri cu personalități din lumea fotbalului și reportaje de la fața locului.',
        'avatar' => '/assets/images/authors/david-nyikora.jpg',
        'role_title' => 'Editor-șef & Fondator',
        'location' => 'București, România',
        'joined' => '2020-01-01',
        'social' => [
            'twitter' => 'https://twitter.com/davidnyikora',
            'instagram' => 'https://instagram.com/davidnyikora',
            'linkedin' => 'https://linkedin.com/in/davidnyikora',
            'facebook' => 'https://facebook.com/davidnyikora'
        ],
        'expertise' => ['Champions League', 'Liga 1', 'Echipa Națională', 'Analize Tactice'],
        'achievements' => [
            'Peste 500 de articole publicate',
            'Interviu exclusiv cu selecționerul României',
            'Acreditare UEFA pentru finale europene'
        ]
    ],
    'admin' => [
        'display_name' => 'Echipa MatchDay',
        'bio' => 'Conținut realizat de echipa redacțională MatchDay.ro. Acoperim cele mai importante știri din fotbalul românesc și internațional.',
        'avatar' => '/assets/images/logo.png',
        'role_title' => 'Redacția',
        'location' => 'România',
        'joined' => '2020-01-01',
        'social' => [],
        'expertise' => ['Breaking News', 'Transferuri', 'Rezultate'],
        'achievements' => []
    ]
];

// Try to find author profile
$authorProfile = null;
$authorKey = null;

if ($author) {
    $authorKey = strtolower($author['username']);
    $authorProfile = $authorProfiles[$authorKey] ?? null;
    
    if (!$authorProfile) {
        // Create a basic profile from database info
        $authorProfile = [
            'display_name' => $author['username'],
            'bio' => 'Autor pe MatchDay.ro',
            'avatar' => '/assets/images/default-avatar.png',
            'role_title' => $author['role'] === 'admin' ? 'Administrator' : 'Editor',
            'location' => 'România',
            'joined' => $author['created_at'],
            'social' => [],
            'expertise' => [],
            'achievements' => []
        ];
    }
}

// Handle 404 if no author found
if (!$author && !$authorKey) {
    // Try to show default profile if no specific author requested
    $authorKey = 'david';
    $authorProfile = $authorProfiles['david'];
    $author = ['id' => 1, 'username' => 'david'];
}

if (!$authorProfile) {
    http_response_code(404);
    $pageTitle = '404 - Autor negăsit';
    $pageDescription = 'Autorul căutat nu a fost găsit.';
    $breadcrumbs = [
        ['name' => 'Acasă', 'url' => '/index.php'],
        ['name' => '404']
    ];
    $pageBodyClass = 'page-article';
    include(__DIR__ . '/includes/header.php');
    echo '<div class="container my-5 text-center"><h1>Autorul nu a fost găsit</h1><p>Ne pare rău, profilul căutat nu există.</p><a href="/index.php" class="btn btn-primary">Înapoi la pagina principală</a></div>';
    include(__DIR__ . '/includes/footer.php');
    exit;
}

// Get author's articles
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

// Fetch posts by author (we'll filter by author in the query if possible)
$db = Database::getInstance();
try {
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $posts = $stmt->fetchAll();
    
    $totalStmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $totalPosts = (int)$totalStmt->fetchColumn();
} catch (Exception $e) {
    $posts = [];
    $totalPosts = 0;
}

$totalPages = max(1, ceil($totalPosts / $perPage));

// Calculate stats
$totalViews = 0;
foreach ($posts as $post) {
    $totalViews += $post['views'] ?? 0;
}

// SEO Configuration
$pageTitle = $authorProfile['display_name'] . ' | Autor pe ' . SITE_NAME;
$pageDescription = mb_substr(strip_tags($authorProfile['bio']), 0, 155);
$pageKeywords = ['autor', 'jurnalist', 'fotbal', $authorProfile['display_name']];
$pageType = 'profile';

$breadcrumbs = [
    ['name' => 'Acasă', 'url' => '/index.php'],
    ['name' => 'Autori', 'url' => '/author.php'],
    ['name' => $authorProfile['display_name']]
];

$pageBodyClass = 'page-article';
include(__DIR__ . '/includes/header.php');

$categories = require(__DIR__ . '/config/categories.php');
?>

<div class="container my-4">
    <!-- Author Profile Header -->
    <div class="author-profile-header mb-5">
        <div class="row align-items-center">
            <div class="col-lg-3 col-md-4 text-center mb-4 mb-md-0">
                <div class="author-avatar-large mx-auto">
                    <?php if (!empty($authorProfile['avatar']) && file_exists(__DIR__ . $authorProfile['avatar'])): ?>
                        <img src="<?= $authorProfile['avatar'] ?>" alt="<?= $authorProfile['display_name'] ?>" class="rounded-circle">
                    <?php else: ?>
                        <div class="avatar-placeholder rounded-circle">
                            <i class="fas fa-user-tie fa-4x"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-9 col-md-8">
                <h1 class="h2 mb-2"><?= htmlspecialchars($authorProfile['display_name']) ?></h1>
                <p class="text-accent mb-3">
                    <i class="fas fa-briefcase me-2"></i><?= htmlspecialchars($authorProfile['role_title']) ?>
                </p>
                <p class="lead text-muted mb-4"><?= htmlspecialchars($authorProfile['bio']) ?></p>
                
                <div class="d-flex flex-wrap gap-4 mb-4">
                    <div class="author-stat">
                        <span class="stat-number"><?= number_format($totalPosts) ?></span>
                        <span class="stat-label">Articole</span>
                    </div>
                    <div class="author-stat">
                        <span class="stat-number"><?= number_format($totalViews) ?></span>
                        <span class="stat-label">Vizualizări</span>
                    </div>
                    <div class="author-stat">
                        <span class="stat-number"><?= date('Y') - date('Y', strtotime($authorProfile['joined'])) ?>+</span>
                        <span class="stat-label">Ani experiență</span>
                    </div>
                </div>
                
                <?php if (!empty($authorProfile['social'])): ?>
                <div class="author-social">
                    <?php foreach ($authorProfile['social'] as $platform => $url): ?>
                        <a href="<?= $url ?>" target="_blank" rel="noopener" class="social-btn social-<?= $platform ?>" title="<?= ucfirst($platform) ?>">
                            <i class="fab fa-<?= $platform ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Expertise Tags -->
            <?php if (!empty($authorProfile['expertise'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-star me-2 text-warning"></i>Specializări</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($authorProfile['expertise'] as $tag): ?>
                            <span class="badge bg-accent"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Author's Articles -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">
                    <i class="fas fa-newspaper me-2"></i>Articole publicate
                </h2>
                <span class="text-muted"><?= number_format($totalPosts) ?> articole</span>
            </div>
            
            <?php if (empty($posts)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Nicio publicație încă.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($posts as $post): 
                    $cat = isset($categories[$post['category_slug']]) ? $categories[$post['category_slug']] : null;
                    $postUrl = '/post/' . $post['slug'];
                ?>
                <div class="col-md-6">
                    <article class="card h-100 news-card shadow-sm">
                        <?php if (!empty($post['cover_image'])): ?>
                        <a href="<?= $postUrl ?>">
                            <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 loading="lazy">
                        </a>
                        <?php else: ?>
                        <a href="<?= $postUrl ?>" class="card-img-top news-card-img-placeholder">
                            <i class="fas fa-newspaper"></i>
                        </a>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <?php if ($cat): ?>
                            <a href="/category/<?= $post['category_slug'] ?>" 
                               class="badge text-decoration-none mb-2"
                               style="background-color: <?= $cat['color'] ?>">
                                <?= $cat['name'] ?>
                            </a>
                            <?php endif; ?>
                            
                            <h3 class="card-title h6 news-card-title">
                                <a href="<?= $postUrl ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="far fa-calendar me-1"></i><?= date('d M Y', strtotime($post['published_at'])) ?></span>
                                <span><i class="far fa-eye me-1"></i><?= number_format($post['views'] ?? 0) ?></span>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $author['id'] ?>&page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?id=<?= $author['id'] ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $author['id'] ?>&page=<?= $page + 1 ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Achievements -->
            <?php if (!empty($authorProfile['achievements'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Realizări</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($authorProfile['achievements'] as $achievement): ?>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <?= htmlspecialchars($achievement) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Contact Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Ai o întrebare sau vrei să propui un subiect?</p>
                    <a href="/contact.php" class="btn btn-accent btn-sm w-100">
                        <i class="fas fa-paper-plane me-2"></i>Trimite un mesaj
                    </a>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-map-marker-alt text-muted me-3"></i>
                        <span><?= htmlspecialchars($authorProfile['location']) ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-check text-muted me-3"></i>
                        <span>Membru din <?= date('F Y', strtotime($authorProfile['joined'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
