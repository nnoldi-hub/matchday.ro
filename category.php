<?php
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Category.php');
require_once(__DIR__ . '/includes/Stats.php');
require_once(__DIR__ . '/includes/Ad.php');
require_once(__DIR__ . '/includes/AdWidget.php');
require_once(__DIR__ . '/includes/seo.php');

// Track category page visit
Stats::trackView(null, 'category');

// Get category from URL
$categorySlug = $_GET['cat'] ?? '';

// Try to get category from database first
$currentCategory = Category::getBySlug($categorySlug);

// If not in database, try config file (backwards compatibility)
if (!$currentCategory) {
    $configCategories = require(__DIR__ . '/config/categories.php');
    if (isset($configCategories[$categorySlug])) {
        $currentCategory = $configCategories[$categorySlug];
        $currentCategory['slug'] = $categorySlug;
    }
}

// Validate category
if (empty($categorySlug) || !$currentCategory) {
    http_response_code(404);
    
    // SEO for 404
    $pageTitle = '404 - Categoria nu a fost găsită';
    $pageDescription = 'Categoria căutată nu există. Explorează celelalte categorii de pe MatchDay.ro.';
  $breadcrumbs = [
    ['name' => 'Acasă', 'url' => '/index.php'],
    ['name' => '404 - Categoria nu a fost găsită']
  ];
    
    $pageBodyClass = 'page-article';
    include(__DIR__ . '/includes/header.php');
  echo '<div class="container my-5"><h1>Categoria nu a fost găsită</h1><p>Categoria "' . htmlspecialchars($categorySlug) . '" nu există.</p><a href="/index.php">Înapoi la jurnal</a></div>';
    include(__DIR__ . '/includes/footer.php');
    exit;
}

// SEO Configuration for category page
$pageTitle = $currentCategory['name'] . ' | Articole și știri fotbal';
$pageDescription = 'Citește toate articolele din categoria ' . $currentCategory['name'] . ' pe ' . SITE_NAME . '. ' . ($currentCategory['description'] ?? 'Știri și analize din fotbalul românesc.');

// Generate keywords based on category
$categoryKeywords = ['fotbal', 'romania', 'sport'];
switch($categorySlug) {
    case 'meciuri':
        $categoryKeywords = array_merge($categoryKeywords, ['meciuri', 'rezultate', 'scoruri', 'analiza meci', 'cronica']);
        break;
    case 'transferuri':
        $categoryKeywords = array_merge($categoryKeywords, ['transferuri', 'mutari', 'jucatori', 'echipe', 'piata transferurilor']);
        break;
    case 'opinii':
        $categoryKeywords = array_merge($categoryKeywords, ['opinii', 'comentarii', 'analize', 'pareri', 'editorialul']);
        break;
    case 'interviuri':
        $categoryKeywords = array_merge($categoryKeywords, ['interviuri', 'convorbiri', 'jucatori', 'antrenori', 'declaratii']);
        break;
    case 'statistici':
        $categoryKeywords = array_merge($categoryKeywords, ['statistici', 'cifre', 'performante', 'clasamente', 'recorduri']);
        break;
    case 'competitii':
        $categoryKeywords = array_merge($categoryKeywords, ['competitii', 'turnee', 'cupe', 'liga', 'campionate']);
        break;
}

$pageKeywords = $categoryKeywords;
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
  ['name' => 'Acasă', 'url' => '/index.php'],
  ['name' => 'Categorii', 'url' => '/index.php#categorii'],
  ['name' => $currentCategory['name']]
];

// Get posts from this category using database
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$posts = Post::getPublished($page, $perPage, $categorySlug, null);
$total = Post::countPublished($categorySlug, null);

// Format posts for template
foreach ($posts as &$post) {
    $post['date'] = $post['published_at'] ?? $post['created_at'];
    $post['file'] = SEOManager::getArticleUrl($post['slug']);
    $post['tags'] = !empty($post['tags']) ? explode(',', $post['tags']) : [];
    $post['cover'] = $post['cover_image'] ?? '';
}
unset($post);

$pageBodyClass = 'page-article';
include(__DIR__ . '/includes/header.php');
?>

<div class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="d-flex align-items-center justify-content-center" 
               style="width: 64px; height: 64px; background: <?= $currentCategory['color'] ?>15; border-radius: 16px; color: <?= $currentCategory['color'] ?>">
            <i class="<?= $currentCategory['icon'] ?> fa-2x"></i>
          </div>
          <div>
            <h1 class="h2 mb-1"><?= htmlspecialchars($currentCategory['name']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($currentCategory['description']) ?></p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted">
          <span><i class="fas fa-newspaper me-1"></i><?= $total ?> articole</span>
          <a href="/index.php" class="text-decoration-none">← Înapoi la toate articolele</a>
        </div>
      </div>
      <div class="col-lg-4 text-lg-end">
        <div class="brand-badge">
          <img src="assets/images/logo.png" width="24" height="24" alt="logo" />
          <span class="fw-600"><?= SITE_NAME ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container my-4">
  <?php if (empty($posts)): ?>
    <div class="text-center py-5">
      <i class="<?= $currentCategory['icon'] ?> fa-3x mb-3 opacity-50"></i>
      <h3 class="h5">Nu sunt articole în această categorie încă</h3>
      <p class="text-muted">Primul articol va apărea aici în curând!</p>
  <a href="/index.php" class="btn btn-brand">Vezi toate articolele</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $post): ?>
      <div class="col-md-6 col-lg-4">
        <article class="card card-article h-100 border-0 shadow-sm">
          <?php if (!empty($post['cover'])): ?>
          <img src="<?= htmlspecialchars($post['cover']) ?>" class="cover" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <div class="mb-2">
              <span class="badge" style="background: <?= $currentCategory['color'] ?>; color: white;">
                <i class="<?= $currentCategory['icon'] ?> me-1"></i><?= $currentCategory['name'] ?>
              </span>
            </div>
            <h2 class="h6 mb-2">
              <a href="<?= htmlspecialchars($post['file']) ?>" class="text-decoration-none stretched-link">
                <?= htmlspecialchars($post['title']) ?>
              </a>
            </h2>
            <?php if (!empty($post['excerpt'])): ?>
            <p class="text-muted small mb-2"><?= htmlspecialchars($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="mt-auto">
              <div class="meta small">
                <?php if (!empty($post['date'])): ?>
                <i class="far fa-calendar me-1"></i><?= date('d.m.Y', strtotime($post['date'])) ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($post['tags'])): ?>
              <div class="mt-2">
                <?php foreach (array_slice($post['tags'], 0, 3) as $tag): ?>
                <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total > $perPage): ?>
    <nav class="mt-5">
      <ul class="pagination justify-content-center">
        <?php
        $totalPages = ceil($total / $perPage);
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?cat=<?= urlencode($categorySlug) ?>&page=<?= $page - 1 ?>">Anterior</a>
          </li>
        <?php endif;
        
        for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?cat=<?= urlencode($categorySlug) ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor;
        
        if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="?cat=<?= urlencode($categorySlug) ?>&page=<?= $page + 1 ?>">Următor</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
