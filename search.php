<?php
/**
 * Search Page
 * MatchDay.ro - Full-text search with advanced features
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Stats.php');

// Track search page visit
Stats::trackView(null, 'search');

$query = isset($_GET['q']) ? trim(Security::sanitizeInput($_GET['q'])) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$results = [];
$total = 0;
$searchTime = 0;

if ($query !== '') {
    $startTime = microtime(true);
    $searchData = Post::fullTextSearch($query, $page, $perPage);
    $results = $searchData['results'];
    $total = $searchData['total'];
    $searchTime = round((microtime(true) - $startTime) * 1000, 2);
}

$pages = max(1, ceil($total / $perPage));

// SEO Configuration
$pageTitle = $query ? "Căutare: $query" : 'Căutare articole';
$pageDescription = $query 
    ? "Rezultate căutare pentru \"$query\" pe MatchDay.ro - $total articole găsite"
    : 'Caută articole despre fotbal, transferuri și meciuri pe MatchDay.ro';
$pageKeywords = ['căutare', 'articole', 'fotbal', $query];
$pageType = 'website';

// Get trending posts for sidebar
$trendingPosts = Post::getTrending(5);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => '/index.php'],
    ['name' => 'Căutare', 'url' => '/search.php']
];

include(__DIR__ . '/includes/header.php');

$categories = require(__DIR__ . '/config/categories.php');
?>

<div class="container my-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="search.php" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="q" class="form-control form-control-lg" 
                                   placeholder="Caută articole..." 
                                   value="<?= Security::sanitizeInput($query) ?>"
                                   autocomplete="off"
                                   id="searchInput">
                        </div>
                        <button type="submit" class="btn btn-accent btn-lg px-4">
                            Caută
                        </button>
                    </form>
                    
                    <!-- Search Suggestions (populated by JS) -->
                    <div id="searchSuggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none;"></div>
                </div>
            </div>
            
            <?php if ($query !== ''): ?>
            <!-- Results Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h4 mb-0">
                        Rezultate pentru: <span class="text-primary">"<?= Security::sanitizeInput($query) ?>"</span>
                    </h1>
                    <small class="text-muted">
                        <?= number_format($total) ?> rezultat<?= $total !== 1 ? 'e' : '' ?> 
                        (<?= $searchTime ?> ms)
                    </small>
                </div>
                <?php if ($total > $perPage): ?>
                <small class="text-muted">
                    Pagina <?= $page ?> din <?= $pages ?>
                </small>
                <?php endif; ?>
            </div>
            
            <?php if (empty($results)): ?>
            <!-- No Results -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Nicio potrivire găsită</h5>
                    <p class="text-muted mb-4">
                        Nu am găsit articole pentru "<strong><?= Security::sanitizeInput($query) ?></strong>"
                    </p>
                    <div class="text-start mx-auto" style="max-width: 400px;">
                        <p class="small text-muted mb-2"><strong>Sugestii:</strong></p>
                        <ul class="small text-muted">
                            <li>Verifică ortografia cuvintelor</li>
                            <li>Folosește cuvinte cheie diferite</li>
                            <li>Încearcă termeni mai generali</li>
                            <li>Caută după numele unei echipe sau jucător</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Results List -->
            <div class="row g-4">
                <?php foreach ($results as $post): 
                    $cat = isset($categories[$post['category_slug']]) ? $categories[$post['category_slug']] : null;
                    $postDate = date('d M Y', strtotime($post['published_at']));
                ?>
                <div class="col-12">
                    <article class="card h-100 shadow-sm">
                        <div class="row g-0">
                            <?php if (!empty($post['cover_image'])): ?>
                            <div class="col-md-4">
                                <a href="post.php?slug=<?= urlencode($post['slug']) ?>">
                                    <img src="<?= Security::sanitizeInput($post['cover_image']) ?>" 
                                         class="img-fluid rounded-start h-100" 
                                         style="object-fit: cover; min-height: 180px;"
                                         alt="<?= Security::sanitizeInput($post['title']) ?>">
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="<?= !empty($post['cover_image']) ? 'col-md-8' : 'col-12' ?>">
                                <div class="card-body">
                                    <?php if ($cat): ?>
                                    <a href="category.php?cat=<?= urlencode($post['category_slug']) ?>" 
                                       class="badge text-decoration-none mb-2"
                                       style="background-color: <?= $cat['color'] ?>">
                                        <i class="<?= $cat['icon'] ?> me-1"></i><?= $cat['name'] ?>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <h2 class="card-title h5">
                                        <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="text-decoration-none text-dark">
                                            <?= Security::sanitizeInput($post['title']) ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="card-text text-muted">
                                        <?= Security::sanitizeInput(mb_substr(strip_tags($post['excerpt'] ?? $post['content']), 0, 150)) ?>...
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="far fa-calendar me-1"></i><?= $postDate ?>
                                            <span class="mx-2">|</span>
                                            <i class="far fa-eye me-1"></i><?= number_format($post['views'] ?? 0) ?>
                                        </small>
                                        <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-sm btn-outline-primary">
                                            Citește <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($query) ?>&page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($pages, $page + 2);
                    
                    if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($query) ?>&page=1">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $pages): ?>
                    <?php if ($endPage < $pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($query) ?>&page=<?= $pages ?>"><?= $pages ?></a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($query) ?>&page=<?= $page + 1 ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Empty State -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h2 class="h4">Caută pe MatchDay.ro</h2>
                    <p class="text-muted mb-4">
                        Găsește articole despre echipa ta favorită, transferuri, meciuri și multe altele.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <p class="small text-muted mb-2">Căutări populare:</p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <a href="?q=europa+league" class="btn btn-sm btn-outline-secondary">Europa League</a>
                                <a href="?q=champions+league" class="btn btn-sm btn-outline-secondary">Champions League</a>
                                <a href="?q=echipa+nationala" class="btn btn-sm btn-outline-secondary">Echipa Națională</a>
                                <a href="?q=transferuri" class="btn btn-sm btn-outline-secondary">Transferuri</a>
                                <a href="?q=liga+1" class="btn btn-sm btn-outline-secondary">Liga 1</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Trending -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-fire text-danger me-1"></i>
                    Trending acum
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($trendingPosts as $i => $post): ?>
                    <a href="post.php?slug=<?= urlencode($post['slug']) ?>" 
                       class="list-group-item list-group-item-action d-flex gap-3">
                        <span class="badge bg-danger rounded-pill"><?= $i + 1 ?></span>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= Security::sanitizeInput(mb_substr($post['title'], 0, 50)) ?>...</h6>
                            <small class="text-muted">
                                <i class="far fa-eye me-1"></i><?= number_format($post['trend_score'] ?? $post['views'] ?? 0) ?> vizualizări
                            </small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Categories -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-folder me-1"></i>
                    Categorii
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($categories as $slug => $cat): ?>
                    <a href="category.php?cat=<?= urlencode($slug) ?>" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <i class="<?= $cat['icon'] ?> me-2" style="color: <?= $cat['color'] ?>"></i>
                            <?= $cat['name'] ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Suggestions Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions');
    let debounceTimer;
    
    if (!searchInput || !suggestions) return;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`/search-suggestions.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.length > 0) {
                        suggestions.innerHTML = data.map(item => 
                            `<a href="/post.php?slug=${item.slug}" class="list-group-item list-group-item-action">
                                <i class="fas fa-newspaper me-2 text-muted"></i>${item.title}
                            </a>`
                        ).join('');
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                    }
                })
                .catch(() => suggestions.style.display = 'none');
        }, 300);
    });
    
    // Hide suggestions on blur
    searchInput.addEventListener('blur', function() {
        setTimeout(() => suggestions.style.display = 'none', 200);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && suggestions.innerHTML) {
            suggestions.style.display = 'block';
        }
    });
});
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>
