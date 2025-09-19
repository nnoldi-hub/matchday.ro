<?php 
require_once(__DIR__ . '/config/config.php');

// SEO Configuration for homepage
$pageTitle = SITE_NAME . ' - Jurnalul meciurilor și transferurilor';
$pageDescription = 'Citește ultimele știri din fotbalul românesc, analize ale meciurilor și transferurilor pe MatchDay.ro. Fiecare meci are o poveste - noi o scriem!';
$pageKeywords = ['fotbal', 'romania', 'sport', 'meciuri', 'transferuri', 'echipa nationala', 'liga 1', 'champions league', 'europa league'];
$pageType = 'website';

// Breadcrumbs for homepage
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php']
];

include(__DIR__ . '/includes/header.php'); 

// Cache key for posts
$cacheKey = 'posts_list_' . ($_GET['q'] ?? '') . '_' . ($_GET['page'] ?? 1);
$cachedResult = CACHE_ENABLED ? Cache::get($cacheKey, CACHE_TTL) : null;

if ($cachedResult === null) {
    $perPage = POSTS_PER_PAGE;
    $q = isset($_GET['q']) ? Security::sanitizeInput(trim($_GET['q'])) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    
    $postsDir = __DIR__ . '/posts';
    if (!is_dir($postsDir)) {
        mkdir($postsDir, 0755, true);
    }
    
    $files = array_values(array_filter(scandir($postsDir), function($f) {
        return substr($f, -5) === '.html' && $f !== 'index.html';
    }));
    
    $items = [];
    foreach ($files as $file) {
        try {
            $path = $postsDir . '/' . $file;
            if (!is_readable($path)) continue;
            
            $html = file_get_contents($path);
            if ($html === false) continue;
            
            // Default metadata
            $meta = [
                'title' => pathinfo($file, PATHINFO_FILENAME),
                'date' => date('Y-m-d', filemtime($path)),
                'excerpt' => '',
                'cover' => '',
                'tags' => [],
                'slug' => pathinfo($file, PATHINFO_FILENAME)
            ];
            
            // Extract embedded metadata
            if (preg_match('/<!--\s*david-meta:(.*?)-->/', $html, $matches)) {
                $jsonMeta = json_decode(trim($matches[1]), true);
                if (is_array($jsonMeta)) {
                    $meta = array_merge($meta, $jsonMeta);
                }
            }
            
            $meta['file'] = 'posts/' . $file;
            $meta['filesize'] = filesize($path);
            $items[] = $meta;
            
        } catch (Exception $e) {
            error_log("Error processing post $file: " . $e->getMessage());
            continue;
        }
    }
    
    // Search functionality
    if ($q !== '') {
        $items = array_values(array_filter($items, function($item) use ($q) {
            $searchText = mb_strtolower(implode(' ', [
                $item['title'] ?? '',
                $item['excerpt'] ?? '',
                implode(' ', $item['tags'] ?? [])
            ]));
            return mb_strpos($searchText, mb_strtolower($q)) !== false;
        }));
    }
    
    // Sort by date (newest first)
    usort($items, function($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']);
    });
    
    $total = count($items);
    $pages = max(1, ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;
    $itemsPage = array_slice($items, $offset, $perPage);
    
    $result = [
        'items' => $itemsPage,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'search_query' => $q
    ];
    
    // Cache the result
    if (CACHE_ENABLED) {
        Cache::set($cacheKey, $result, CACHE_TTL);
    }
} else {
    $result = $cachedResult;
    $itemsPage = $result['items'];
    $total = $result['total'];
    $pages = $result['pages'];
    $page = $result['current_page'];
    $q = $result['search_query'];
}

// Success message
$successMsg = '';
if (isset($_GET['created'])) {
    $successMsg = 'Articol creat cu succes!';
}
?>

<section class="hero">
  <div class="container">
    <div class="brand-badge mb-3">
      <img src="assets/images/logo.png" width="28" height="28" alt="<?php echo SITE_NAME; ?> logo">
      <div>
        <strong><?php echo SITE_NAME; ?></strong>
        <div class="small-muted"><?php echo SITE_TAGLINE; ?></div>
      </div>
    </div>
    
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h1 class="display-6 m-0">Jurnalul meciurilor și transferurilor</h1>
      <a class="btn btn-accent" href="admin/login.php" aria-label="Scrie un articol nou">
        <i class="fas fa-plus me-1"></i> Scrie un articol
      </a>
    </div>
    
    <?php if ($successMsg): ?>
      <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <?php echo Security::sanitizeInput($successMsg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <form method="get" class="mt-3" role="search">
      <div class="searchbar d-flex align-items-center gap-2">
        <input type="search" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" 
               class="form-control border-0" placeholder="Caută titluri, taguri, transferuri..." 
               maxlength="100" aria-label="Caută articole">
        <button class="btn btn-brand" type="submit">
          <i class="fas fa-search me-1"></i> Caută
        </button>
        <?php if ($q): ?>
      <a href="/index.php" class="btn btn-outline-secondary" title="Șterge căutarea">
            <i class="fas fa-times"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
    
    <?php if ($q): ?>
      <div class="mt-2">
        <small class="text-muted">
          <?php echo $total; ?> rezultate pentru "<?php echo Security::sanitizeInput($q); ?>"
        </small>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Hero Carousel cu ultimele 3 articole -->
<div class="container mb-4">
  <?php if (!empty($itemsPage) && count($itemsPage) >= 3): ?>
  <div id="heroCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    
    <div class="carousel-inner rounded-4 shadow">
      <?php 
      $heroItems = array_slice($itemsPage, 0, 3);
      foreach ($heroItems as $index => $item): 
        $categories = require(__DIR__ . '/config/categories.php');
        $category = isset($item['category']) && isset($categories[$item['category']]) ? $categories[$item['category']] : null;
      ?>
      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
        <div class="hero-slide position-relative" style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <?php if (!empty($item['cover'])): ?>
          <img src="<?= htmlspecialchars($item['cover']) ?>" 
               class="position-absolute w-100 h-100" 
               style="object-fit: cover; opacity: 0.3;"
               alt="<?= htmlspecialchars($item['title']) ?>">
          <?php endif; ?>
          
          <div class="position-absolute w-100 h-100 d-flex align-items-end" 
               style="background: linear-gradient(transparent, rgba(0,0,0,0.7));">
            <div class="container">
              <div class="row">
                <div class="col-md-8">
                  <div class="text-white p-4">
                    <?php if ($category): ?>
                    <span class="badge mb-2" style="background: <?= $category['color'] ?>; color: white;">
                      <i class="<?= $category['icon'] ?> me-1"></i><?= $category['name'] ?>
                    </span>
                    <?php endif; ?>
                    
                    <h2 class="h3 mb-2 fw-bold">
                      <a href="/<?= htmlspecialchars($item['file']) ?>" class="text-white text-decoration-none">
                        <?= htmlspecialchars($item['title']) ?>
                      </a>
                    </h2>
                    
                    <?php if (!empty($item['excerpt'])): ?>
                    <p class="mb-3 opacity-75">
                      <?= htmlspecialchars(mb_substr($item['excerpt'], 0, 120)) ?>...
                    </p>
                    <?php endif; ?>
                    
                    <div class="d-flex align-items-center gap-3 small">
                      <span><i class="far fa-calendar me-1"></i><?= date('d.m.Y', strtotime($item['date'])) ?></span>
                      <?php if (isset($item['word_count']) && $item['word_count'] > 0): ?>
                      <span><i class="far fa-clock me-1"></i><?= ceil($item['word_count'] / 200) ?> min</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
      <span class="visually-hidden">Anterior</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
      <span class="visually-hidden">Următor</span>
    </button>
  </div>
  <?php endif; ?>
</div>

<!-- Categories Section -->
<div class="container mb-4">
  <div class="row">
    <div class="col-12">
      <h2 class="h5 mb-3 d-flex align-items-center">
        <i class="fas fa-th-large me-2"></i>Categorii
      </h2>
      <div class="row g-3">
        <?php 
        $categories = require(__DIR__ . '/config/categories.php');
        foreach ($categories as $key => $category): 
        ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="category.php?cat=<?= urlencode($key) ?>" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="transition: transform 0.2s ease">
              <div class="card-body text-center p-3" onmouseover="this.parentElement.style.transform='translateY(-2px)'" onmouseout="this.parentElement.style.transform='translateY(0)'">
                <div class="d-flex align-items-center justify-content-center mb-2" 
                     style="width: 40px; height: 40px; background: <?= $category['color'] ?>15; border-radius: 12px; color: <?= $category['color'] ?>; margin: 0 auto;">
                  <i class="<?= $category['icon'] ?>"></i>
                </div>
                <h6 class="mb-1 small fw-bold"><?= htmlspecialchars($category['name']) ?></h6>
                <div class="small text-muted">
                  <?php
                  // Count posts in this category
                  $categoryCount = 0;
                  if (isset($items)) {
                    foreach ($items as $item) {
                      if (($item['category'] ?? '') === $key) $categoryCount++;
                    }
                  }
                  echo $categoryCount . ' articole';
                  ?>
                </div>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Interactive Polls Section -->
<div class="container mb-4">
  <?php
  // Load active polls
  $pollsDir = __DIR__ . '/data/polls';
  $activePolls = [];
  
  if (is_dir($pollsDir)) {
    $pollFiles = glob($pollsDir . '/*.json');
    foreach ($pollFiles as $pollFile) {
      $pollData = json_decode(file_get_contents($pollFile), true);
      if ($pollData && isset($pollData['active']) && $pollData['active']) {
        $activePolls[] = $pollData;
      }
    }
  }
  ?>
  
  <?php if (!empty($activePolls)): ?>
  <div class="row">
    <div class="col-12">
      <h2 class="h5 mb-3 d-flex align-items-center">
        <i class="fas fa-poll me-2 text-primary"></i>Sondaje interactive
      </h2>
      <div class="row g-4">
        <?php 
        // Display up to 2 active polls
        $displayPolls = array_slice($activePolls, 0, 2);
        $colClass = count($displayPolls) === 1 ? 'col-md-8 col-lg-6 mx-auto' : 'col-md-6';
        ?>
        <?php foreach ($displayPolls as $poll): ?>
        <div class="<?= $colClass ?>">
          <div data-poll="<?= htmlspecialchars($poll['id']) ?>"></div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (count($activePolls) > 2): ?>
      <div class="text-center mt-3">
        <a href="#" class="btn btn-outline-primary btn-sm" onclick="loadMorePolls(); return false;">
          <i class="fas fa-plus me-1"></i>Vezi mai multe sondaje (<?= count($activePolls) - 2 ?>)
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="container my-4">
  <?php 
  // Pentru afișarea în listă, excludem primele 3 articole dacă sunt în carousel
  $displayItems = (!empty($itemsPage) && count($itemsPage) >= 3) ? array_slice($itemsPage, 3) : $itemsPage;
  ?>
  
  <?php if (empty($displayItems) && empty($itemsPage)): ?>
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info text-center">
          <h4>Nu am găsit articole</h4>
          <?php if ($q): ?>
            <p>Nu există articole pentru căutarea ta. <a href="/index.php">Vezi toate articolele</a></p>
          <?php else: ?>
            <p>Nu există încă articole publicate. <a href="admin/login.php">Scrie primul articol!</a></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php if (!empty($displayItems)): ?>
    <div class="row mb-4">
      <div class="col-12">
        <h2 class="h5 d-flex align-items-center">
          <i class="fas fa-newspaper me-2"></i>
          <?= (!empty($itemsPage) && count($itemsPage) >= 3) ? 'Mai multe articole' : 'Toate articolele' ?>
        </h2>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="row g-4">
      <?php foreach ($displayItems as $index => $item): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <article class="card card-article h-100" itemscope itemtype="https://schema.org/Article">
            <a href="/<?php echo Security::sanitizeInput($item['file']); ?>" 
               class="text-decoration-none text-reset" 
               aria-label="Citește: <?php echo Security::sanitizeInput($item['title']); ?>">
               
              <?php if (!empty($item['cover'])): ?>
                <img src="<?php echo Security::sanitizeInput($item['cover']); ?>" 
                     class="cover" 
                     alt="Imagine pentru <?php echo Security::sanitizeInput($item['title']); ?>"
                     loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>"
                     itemprop="image">
              <?php endif; ?>
              
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="meta small" itemprop="datePublished" content="<?php echo $item['date']; ?>">
                    <?php echo date('d.m.Y', strtotime($item['date'])); ?>
                    <?php if (isset($item['word_count']) && $item['word_count'] > 0): ?>
                      <span class="text-muted ms-2"><?php echo ceil($item['word_count'] / 200); ?> min</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($item['category'])): 
                    $categories = require(__DIR__ . '/config/categories.php');
                    if (isset($categories[$item['category']])):
                      $cat = $categories[$item['category']];
                  ?>
                  <span class="badge" style="background: <?= $cat['color'] ?>; color: white; font-size: 0.7rem;">
                    <i class="<?= $cat['icon'] ?> me-1"></i><?= $cat['name'] ?>
                  </span>
                  <?php endif; endif; ?>
                </div>
                
                <h2 class="h5" itemprop="headline">
                  <?php echo Security::sanitizeInput($item['title']); ?>
                </h2>
                
                <?php if (!empty($item['excerpt'])): ?>
                  <p class="mb-2 small text-muted" itemprop="description">
                    <?php echo Security::sanitizeInput($item['excerpt']); ?>
                  </p>
                <?php endif; ?>
                
                <?php if (!empty($item['tags'])): ?>
                  <div class="mb-2" itemprop="keywords">
                    <?php foreach (array_slice($item['tags'], 0, 3) as $tag): ?>
                      <span class="tag"><?php echo Security::sanitizeInput($tag); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($item['tags']) > 3): ?>
                      <span class="tag text-muted">+<?php echo count($item['tags']) - 3; ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                
                <div class="small text-primary mt-auto">
                  Citește <i class="fas fa-arrow-right ms-1"></i>
                </div>
              </div>
            </a>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <nav aria-label="Paginare articole" class="mt-5">
      <ul class="pagination justify-content-center">
        <!-- Previous page -->
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <?php if ($page > 1): ?>
            <a class="page-link" href="?<?php echo http_build_query(['q' => $q, 'page' => $page - 1]); ?>" 
               aria-label="Pagina anterioară">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php else: ?>
            <span class="page-link" aria-label="Pagina anterioară">
              <i class="fas fa-chevron-left"></i>
            </span>
          <?php endif; ?>
        </li>

        <!-- Page numbers -->
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($pages, $page + 2);
        
        if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(['q' => $q, 'page' => 1]); ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <?php if ($i == $page): ?>
              <span class="page-link" aria-current="page"><?php echo $i; ?></span>
            <?php else: ?>
              <a class="page-link" href="?<?php echo http_build_query(['q' => $q, 'page' => $i]); ?>">
                <?php echo $i; ?>
              </a>
            <?php endif; ?>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $pages): ?>
          <?php if ($endPage < $pages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(['q' => $q, 'page' => $pages]); ?>">
              <?php echo $pages; ?>
            </a>
          </li>
        <?php endif; ?>

        <!-- Next page -->
        <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
          <?php if ($page < $pages): ?>
            <a class="page-link" href="?<?php echo http_build_query(['q' => $q, 'page' => $page + 1]); ?>" 
               aria-label="Pagina următoare">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php else: ?>
            <span class="page-link" aria-label="Pagina următoare">
              <i class="fas fa-chevron-right"></i>
            </span>
          <?php endif; ?>
        </li>
      </ul>
    </nav>

    <div class="text-center text-muted small mt-2">
      Pagina <?php echo $page; ?> din <?php echo $pages; ?> 
      (<?php echo $total; ?> articole total)
    </div>
  <?php endif; ?>
</div>
<?php include(__DIR__ . '/includes/footer.php'); ?>
