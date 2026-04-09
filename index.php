<?php 
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Poll.php');
require_once(__DIR__ . '/includes/Stats.php');
require_once(__DIR__ . '/includes/Ad.php');
require_once(__DIR__ . '/includes/AdWidget.php');
require_once(__DIR__ . '/includes/sidebars.php');

// Track homepage visit
Stats::trackView(null, 'homepage');

// SEO Configuration for homepage
$pageTitle = 'Jurnalul meciurilor și transferurilor | Știri fotbal România';
$pageDescription = 'Jurnalul meciurilor și transferurilor din fotbalul românesc. Analize, știri, comentarii despre Liga 1, Champions League și echipa națională. Fiecare meci are o poveste - noi o scriem!';
$pageKeywords = ['jurnalul meciurilor', 'transferuri fotbal', 'fotbal romania', 'liga 1', 'champions league', 'echipa nationala', 'stiri fotbal', 'analize meciuri'];
$pageType = 'website';

// Breadcrumbs for homepage
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php']
];

include(__DIR__ . '/includes/header.php'); 

// Cache key for posts

$cacheKey = 'posts_list_' . ($_GET['q'] ?? '') . '_' . ($_GET['page'] ?? 1);
$ignoreCache = isset($_GET['nocache']) && $_GET['nocache'] == '1';
$cachedResult = (CACHE_ENABLED && !$ignoreCache) ? Cache::get($cacheKey, CACHE_TTL) : null;

if ($cachedResult === null) {
  $perPage = POSTS_PER_PAGE;
  $q = isset($_GET['q']) ? Security::sanitizeInput(trim($_GET['q'])) : '';
  $page = max(1, intval($_GET['page'] ?? 1));
  
  // Get posts from database
  $searchTerm = $q !== '' ? $q : null;
  $itemsPage = Post::getPublished($page, $perPage, null, $searchTerm);
  $total = Post::countPublished(null, $searchTerm);
  $pages = max(1, ceil($total / $perPage));
  
  // Format items for template compatibility
  foreach ($itemsPage as &$item) {
    $item['date'] = $item['published_at'] ?? $item['created_at'];
    $item['file'] = SEOManager::getArticleUrl($item['slug']);
    $item['tags'] = !empty($item['tags']) ? explode(',', $item['tags']) : [];
  }
  unset($item);
  
  // Get category counts from database
  $categoryCounts = [];
  $catCounts = Post::getCountByCategory();
  foreach ($catCounts as $cc) {
    $categoryCounts[$cc['category_slug']] = $cc['count'];
  }
  
  $result = [
    'items' => $itemsPage,
    'total' => $total,
    'pages' => $pages,
    'current_page' => $page,
    'search_query' => $q,
    'category_counts' => $categoryCounts,
  ];
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
  $categoryCounts = $result['category_counts'] ?? [];
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

<!-- ========================================
     LAYOUT CU 3 COLOANE
     ======================================== -->
<?php if (!$q): ?>
<div class="three-column-layout">
  <!-- SIDEBAR STÂNGA -->
  <aside class="sidebar-column sidebar-left d-none d-xl-block">
    <div class="sidebar-sticky">
      <?php renderLeftSidebar(); ?>
    </div>
  </aside>
  
  <!-- CONȚINUT PRINCIPAL -->
  <main class="main-column">
    <div class="main-column-inner">
<?php endif; ?>

<!-- Hero Carousel cu ultimele 3 articole (ascuns la căutare) -->
<div class="container mb-4">
  <?php if (!$q && !empty($itemsPage) && count($itemsPage) >= 3): ?>
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
        // Use category data from DB (via JOIN)
        $category = !empty($item['category_name']) ? [
          'name' => $item['category_name'],
          'color' => $item['category_color'] ?? '#6c757d',
          'icon' => $item['category_icon'] ?? 'fas fa-newspaper'
        ] : null;
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
                        <?= htmlspecialchars($item['title']) ?>
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
        <div style="position:absolute; bottom:32px; right:32px; z-index:999; pointer-events:auto;">
          <a href="<?= htmlspecialchars($item['file']) ?>" class="btn btn-accent btn-lg">Citește articolul</a>
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

<!-- ========================================
     SECȚIUNEA: Noutăți & Rezultate
     ======================================== -->
<?php if (!$q): ?>
<section class="news-results-section py-4">
  <div class="container">
    <div class="section-header d-flex align-items-center justify-content-between mb-4">
      <h2 class="h4 mb-0 d-flex align-items-center">
        <i class="fas fa-bolt text-warning me-2"></i>Noutăți & Rezultate
      </h2>
      <a href="?q=" class="btn btn-sm btn-outline-primary">
        Vezi toate articolele <i class="fas fa-arrow-right ms-1"></i>
      </a>
    </div>
    
    <div class="row g-4">
      <!-- Coloana principală: Ultimele articole -->
      <div class="col-lg-8">
        <div class="row g-3">
          <?php 
          // Fetch latest 6 published articles
          $latestNews = Post::getLatest(6, true);
          foreach ($latestNews as $newsIndex => $newsItem): 
            // Use category data from DB (via JOIN)
            $newsCat = !empty($newsItem['category_name']) ? [
              'name' => $newsItem['category_name'],
              'color' => $newsItem['category_color'] ?? '#6c757d',
              'icon' => $newsItem['category_icon'] ?? 'fas fa-newspaper'
            ] : null;
            $newsUrl = SEOManager::getArticleUrl($newsItem['slug']);
            $newsDate = $newsItem['published_at'] ?? $newsItem['created_at'];
          ?>
          <div class="col-sm-6">
            <article class="news-card h-100">
              <a href="<?= htmlspecialchars($newsUrl) ?>" class="text-decoration-none text-reset d-flex h-100">
                <?php if (!empty($newsItem['cover_image'])): ?>
                <div class="news-card-img" style="background-image: url('<?= htmlspecialchars($newsItem['cover_image']) ?>')"></div>
                <?php else: ?>
                <div class="news-card-img news-card-img-placeholder">
                  <i class="fas fa-futbol"></i>
                </div>
                <?php endif; ?>
                <div class="news-card-body">
                  <?php if ($newsCat): ?>
                  <span class="news-cat-badge" style="background: <?= $newsCat['color'] ?>">
                    <?= $newsCat['name'] ?>
                  </span>
                  <?php endif; ?>
                  <h3 class="news-card-title"><?= htmlspecialchars($newsItem['title']) ?></h3>
                  <div class="news-card-meta">
                    <i class="far fa-clock me-1"></i><?= date('d.m.Y', strtotime($newsDate)) ?>
                  </div>
                </div>
              </a>
            </article>
          </div>
          <?php endforeach; ?>
        </div>
        
        <!-- Sondaje Interactive -->
        <?php
        // Load active polls from database
        $activePolls = Poll::getActive(5);
        ?>
        <?php if (!empty($activePolls)): ?>
        <div class="polls-inline mt-4">
          <h3 class="h6 mb-3 d-flex align-items-center">
            <i class="fas fa-poll me-2 text-primary"></i>Sondaje interactive
          </h3>
          <div class="row g-2">
            <?php 
            $displayPolls = array_slice($activePolls, 0, 2);
            foreach ($displayPolls as $poll): 
            ?>
            <div class="col-md-6">
              <div data-poll="<?= htmlspecialchars($poll['id']) ?>" class="poll-compact"></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Coloana laterală: Rezultate + Clasament -->
      <div class="col-lg-4">
        <!-- Card Meciuri Live -->
        <?php
        require_once(__DIR__ . '/includes/LiveScores.php');
        require_once(__DIR__ . '/includes/Settings.php');
        
        // Preia meciurile de azi
        $todayMatches = LiveScores::getTodayMatches();
        $liveMatches = array_filter($todayMatches, fn($m) => in_array($m['status'] ?? '', ['live', '1H', '2H', 'HT', 'ET', 'P']));
        $scheduledMatches = array_filter($todayMatches, fn($m) => ($m['status'] ?? '') === 'scheduled');
        ?>
        
        <?php if (!empty($todayMatches)): ?>
        <div class="live-matches-card mb-3" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(229,62,62,0.15);border:2px solid #e53e3e;">
          <div class="live-matches-header" style="background:linear-gradient(135deg,#e53e3e 0%,#c53030 100%);color:#fff;padding:0.75rem 1rem;font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:0.5rem;">
            <?php if (!empty($liveMatches)): ?>
            <span class="live-badge pulse" style="display:inline-flex;align-items:center;gap:0.3rem;background:#fff;color:#e53e3e;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.7rem;font-weight:700;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> LIVE</span>
            <?php endif; ?>
            <i class="fas fa-broadcast-tower me-2"></i>Meciuri Azi
          </div>
          <div class="live-matches-body" style="padding:0;max-height:300px;overflow-y:auto;">
            <?php foreach ($todayMatches as $match): 
              $isLive = in_array($match['status'] ?? '', ['live', '1H', '2H', 'HT', 'ET', 'P']);
              $isScheduled = ($match['status'] ?? '') === 'scheduled';
              $kickoffTime = isset($match['kickoff']) ? date('H:i', strtotime($match['kickoff'])) : '';
              $liveStyle = $isLive ? 'background:linear-gradient(90deg,#fef2f2 0%,#fff 100%);border-left:3px solid #e53e3e;' : '';
            ?>
            <a href="match.php?id=<?= $match['id'] ?>" class="live-match-link" style="display:block;text-decoration:none;color:inherit;transition:background 0.2s;<?= $liveStyle ?>padding:0.6rem 1rem;border-bottom:1px solid #f0f0f0;">
              <div class="live-match-teams" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                <div class="live-team home" style="display:flex;align-items:center;gap:0.4rem;flex:1;">
                  <span class="team-name" style="font-weight:600;font-size:0.8rem;color:#1a202c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;"><?= htmlspecialchars($match['home_team']) ?></span>
                  <?php if (!$isScheduled): 
                    $homeWin = ($match['home_score'] ?? 0) > ($match['away_score'] ?? 0);
                    $scoreStyle = $homeWin ? 'background:#48bb78;color:#fff;' : 'background:#e2e8f0;';
                  ?>
                  <span class="team-score" style="<?= $scoreStyle ?>padding:0.15rem 0.4rem;border-radius:4px;font-weight:700;font-size:0.85rem;min-width:24px;text-align:center;"><?= $match['home_score'] ?? 0 ?></span>
                  <?php endif; ?>
                </div>
                <div class="live-match-status" style="flex-shrink:0;text-align:center;min-width:45px;">
                  <?php if ($isLive): ?>
                    <span class="match-minute" style="background:#e53e3e;color:#fff;padding:0.1rem 0.4rem;border-radius:4px;font-size:0.7rem;font-weight:700;"><?= $match['minute'] ?? '' ?>'</span>
                  <?php elseif ($isScheduled): ?>
                    <span class="match-time" style="color:#718096;font-size:0.75rem;font-weight:600;"><?= $kickoffTime ?></span>
                  <?php else: ?>
                    <span class="match-final" style="color:#a0aec0;font-size:0.7rem;font-weight:500;">Final</span>
                  <?php endif; ?>
                </div>
                <div class="live-team away" style="display:flex;align-items:center;gap:0.4rem;flex:1;flex-direction:row-reverse;text-align:right;">
                  <?php if (!$isScheduled): 
                    $awayWin = ($match['away_score'] ?? 0) > ($match['home_score'] ?? 0);
                    $scoreStyleAway = $awayWin ? 'background:#48bb78;color:#fff;' : 'background:#e2e8f0;';
                  ?>
                  <span class="team-score" style="<?= $scoreStyleAway ?>padding:0.15rem 0.4rem;border-radius:4px;font-weight:700;font-size:0.85rem;min-width:24px;text-align:center;"><?= $match['away_score'] ?? 0 ?></span>
                  <?php endif; ?>
                  <span class="team-name" style="font-weight:600;font-size:0.8rem;color:#1a202c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;"><?= htmlspecialchars($match['away_team']) ?></span>
                </div>
              </div>
              <div class="live-match-competition" style="text-align:center;font-size:0.7rem;color:#a0aec0;margin-top:0.25rem;">
                <?= htmlspecialchars($match['competition'] ?? '') ?>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <a href="live.php" class="live-matches-footer" style="display:block;text-align:center;padding:0.6rem;background:#f7fafc;color:#e53e3e;text-decoration:none;font-size:0.8rem;font-weight:600;">
            <i class="fas fa-external-link-alt me-1"></i> Vezi toate meciurile
          </a>
        </div>
        <?php endif; ?>
        
        <!-- Card Rezultate Importante -->
        <?php
        // Preia setarea pentru nr. de rezultate (default 5)
        $resultsCount = (int) Settings::get('featured_results_count', '5');
        
        // Preia rezultatele featured din tabelul dedicat
        $featuredResults = Database::fetchAll(
            "SELECT fr.*, p.slug as post_slug 
             FROM featured_results fr 
             LEFT JOIN posts p ON fr.post_id = p.id 
             WHERE fr.active = 1 
             ORDER BY fr.sort_order ASC 
             LIMIT " . $resultsCount
        );
        ?>
        
        <div class="results-card results-card-live mb-3">
          <div class="results-card-header">
            <span class="live-badge pulse"><i class="fas fa-circle"></i> LIVE</span>
            <i class="fas fa-futbol me-2"></i>Rezultate importante
          </div>
          <div class="results-card-body">
            <?php if (empty($featuredResults)): ?>
              <div class="no-results p-3 text-center text-muted">
                <i class="fas fa-calendar-times mb-2"></i><br>
                Nu există rezultate recente
              </div>
            <?php else: ?>
              <?php foreach ($featuredResults as $match): ?>
              <?php 
                $homeScorers = !empty($match['home_scorers']) ? json_decode($match['home_scorers'], true) : [];
                $awayScorers = !empty($match['away_scorers']) ? json_decode($match['away_scorers'], true) : [];
              ?>
              <?php if ($match['post_slug']): ?>
              <a href="post.php?slug=<?= htmlspecialchars($match['post_slug']) ?>" class="match-result-link">
              <?php else: ?>
              <div class="match-result-link">
              <?php endif; ?>
                <div class="match-result">
                  <div class="match-teams">
                    <div class="team home">
                      <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                      <span class="team-score <?= $match['home_score'] > $match['away_score'] ? 'win' : '' ?>"><?= $match['home_score'] ?></span>
                    </div>
                    <span class="match-vs">-</span>
                    <div class="team away">
                      <span class="team-score <?= $match['away_score'] > $match['home_score'] ? 'win' : '' ?>"><?= $match['away_score'] ?></span>
                      <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                    </div>
                  </div>
                  <?php if (!empty($homeScorers) || !empty($awayScorers)): ?>
                  <div class="match-scorers">
                    <?php if (!empty($homeScorers)): ?>
                    <div class="scorers-home">
                      <?php foreach ($homeScorers as $scorer): ?>
                        <span class="scorer"><?= htmlspecialchars($scorer['name']) ?><?= !empty($scorer['minute']) ? " <small>({$scorer['minute']}')</small>" : '' ?></span>
                      <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($awayScorers)): ?>
                    <div class="scorers-away">
                      <?php foreach ($awayScorers as $scorer): ?>
                        <span class="scorer"><?= htmlspecialchars($scorer['name']) ?><?= !empty($scorer['minute']) ? " <small>({$scorer['minute']}')</small>" : '' ?></span>
                      <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                  <div class="match-info">
                    <span class="match-league"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                  </div>
                </div>
              <?php if ($match['post_slug']): ?>
              </a>
              <?php else: ?>
              </div>
              <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
                <!-- Card Program UCL -->
        <div class="ucl-program-card mb-3">
          <div class="ucl-program-header">
            <i class="fas fa-calendar-alt me-2"></i>Program UCL – Săptămâna viitoare
          </div>
          <div class="ucl-program-body">
            <ul class="ucl-matches-list">
              <li>
                <span class="ucl-teams"><strong>Sporting CP</strong> vs <strong>Arsenal</strong></span>
                <span class="ucl-date">7 apr, 22:00</span>
              </li>
              <li>
                <span class="ucl-teams"><strong>Real Madrid</strong> vs <strong>Bayern</strong></span>
                <span class="ucl-date">7 apr, 22:00</span>
              </li>
              <li>
                <span class="ucl-teams"><strong>PSG</strong> vs <strong>Liverpool</strong></span>
                <span class="ucl-date">8 apr, 22:00</span>
              </li>
              <li>
                <span class="ucl-teams"><strong>Barcelona</strong> vs <strong>Atletico</strong></span>
                <span class="ucl-date">8 apr, 22:00</span>
              </li>
            </ul>
            <a href="post.php?slug=program-champions-league-meciurile-urmatoare-sferturi-2026" class="ucl-full-link">
              Vezi calendarul complet <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
                <!-- Card Clasament Liga 1 -->
        <div class="standings-card">
          <div class="standings-card-header">
            <i class="fas fa-trophy me-2"></i>Clasament Liga 1
          </div>
          <div class="standings-card-body">
            <div class="standings-subtitle">Play-off</div>
            <table class="standings-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Echipă</th>
                  <th>MJ</th>
                  <th>Pct</th>
                </tr>
              </thead>
              <tbody>
                <tr class="top-team">
                  <td class="pos">1</td>
                  <td class="team-name">"U" Cluj</td>
                  <td>2</td>
                  <td class="points">33</td>
                </tr>
                <tr class="top-team">
                  <td class="pos">2</td>
                  <td class="team-name">U Craiova</td>
                  <td>2</td>
                  <td class="points">33</td>
                </tr>
                <tr>
                  <td class="pos">3</td>
                  <td class="team-name">Rapid București</td>
                  <td>2</td>
                  <td class="points">31</td>
                </tr>
                <tr>
                  <td class="pos">4</td>
                  <td class="team-name">CFR Cluj</td>
                  <td>2</td>
                  <td class="points">30</td>
                </tr>
                <tr>
                  <td class="pos">5</td>
                  <td class="team-name">Argeș Pitești</td>
                  <td>2</td>
                  <td class="points">28</td>
                </tr>
                <tr>
                  <td class="pos">6</td>
                  <td class="team-name">Dinamo București</td>
                  <td>2</td>
                  <td class="points">26</td>
                </tr>
              </tbody>
            </table>
            <a href="post.php?slug=clasament-liga-1-superliga-2025-2026" class="standings-full-link">
              Vezi clasamentul complet <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
        
        <!-- Card Top Marcatori UCL -->
        <div class="top-scorers-card mb-3">
          <div class="top-scorers-header">
            <i class="fas fa-star me-2"></i>Top marcatori UCL
          </div>
          <div class="top-scorers-body">
            <table class="top-scorers-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Jucător</th>
                  <th>Goluri</th>
                </tr>
              </thead>
              <tbody>
                <tr class="gold">
                  <td class="pos">1</td>
                  <td class="player">Kylian Mbappé <span class="team-badge">Real Madrid</span></td>
                  <td class="goals">13</td>
                </tr>
                <tr class="silver">
                  <td class="pos">2</td>
                  <td class="player">Anthony Gordon <span class="team-badge">Newcastle</span></td>
                  <td class="goals">10</td>
                </tr>
                <tr class="bronze">
                  <td class="pos">3</td>
                  <td class="player">Harry Kane <span class="team-badge">Bayern</span></td>
                  <td class="goals">10</td>
                </tr>
                <tr>
                  <td class="pos">4</td>
                  <td class="player">Erling Haaland <span class="team-badge">Man City</span></td>
                  <td class="goals">8</td>
                </tr>
                <tr>
                  <td class="pos">5</td>
                  <td class="player">Julián Álvarez <span class="team-badge">Atletico</span></td>
                  <td class="goals">8</td>
                </tr>
              </tbody>
            </table>
            <a href="post.php?slug=clasament-marcatori-ucl-2026" class="top-scorers-link">
              Vezi topul complet <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
        
        <!-- Card Program Liga 1 -->
        <div class="liga1-program-card mb-3">
          <div class="liga1-program-header">
            <i class="fas fa-calendar-alt me-2"></i>Program Liga 1 – Etapa următoare
          </div>
          <div class="liga1-program-body">
            <ul class="liga1-matches-list">
              <li>
                <span class="liga1-teams"><strong>Dinamo</strong> vs <strong>Rapid</strong></span>
                <span class="liga1-date">Vin, 28 mar, 20:30</span>
              </li>
              <li>
                <span class="liga1-teams"><strong>U Cluj</strong> vs <strong>CFR Cluj</strong></span>
                <span class="liga1-date">Sâm, 29 mar, 21:00</span>
              </li>
              <li>
                <span class="liga1-teams"><strong>U Craiova</strong> vs <strong>Argeș</strong></span>
                <span class="liga1-date">Dum, 30 mar, 20:30</span>
              </li>
            </ul>
            <a href="post.php?slug=program-liga-1-etapa-urmatoare" class="liga1-full-link">
              Vezi programul complet <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="container my-4">
  <?php 
  // Când e căutare activă, afișăm toate rezultatele (fără carousel)
  // Altfel excludem primele 3 articole care sunt în carousel
  if ($q) {
    $displayItems = $itemsPage; // Toate rezultatele căutării
  } else {
    $displayItems = (!empty($itemsPage) && count($itemsPage) >= 3) ? array_slice($itemsPage, 3) : $itemsPage;
  }
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
          <i class="fas fa-<?= $q ? 'search' : 'newspaper' ?> me-2"></i>
          <?php if ($q): ?>
            Rezultate pentru "<?= htmlspecialchars($q) ?>"
          <?php else: ?>
            <?= (!empty($itemsPage) && count($itemsPage) >= 3) ? 'Mai multe articole' : 'Toate articolele' ?>
          <?php endif; ?>
        </h2>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
      <!-- Main Content -->
      <div class="col-lg-8">
        <div class="row g-4">
      <?php 
      $adInlineShown = false;
      foreach ($displayItems as $index => $item): 
        // Show inline ad after every 2 articles
        if ($index > 0 && $index % 2 === 0 && !$adInlineShown):
          $inlineAd = AdWidget::articleInline();
          if ($inlineAd):
            $adInlineShown = true;
      ?>
        <div class="col-12">
          <?= $inlineAd ?>
        </div>
      <?php endif; endif; ?>
        <div class="col-12 col-md-6">
          <article class="card card-article h-100" itemscope itemtype="https://schema.org/Article">
            <a href="<?php echo Security::sanitizeInput($item['file']); ?>" 
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
                  <?php if (!empty($item['category_slug'])): 
                    // Use category data from DB (already in $item via JOIN)
                    $cat = !empty($item['category_name']) ? [
                      'name' => $item['category_name'],
                      'color' => $item['category_color'] ?? '#6c757d',
                      'icon' => $item['category_icon'] ?? 'fas fa-newspaper'
                    ] : null;
                    if ($cat):
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
      </div><!-- /.col-lg-8 -->
      
      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="sticky-top" style="top: 90px;">
          <?= AdWidget::sidebar('Sponsor') ?>
          
          <!-- Newsletter Widget -->
          <div class="card mb-4">
            <div class="card-header"><strong><i class="fas fa-envelope me-2"></i>Newsletter</strong></div>
            <div class="card-body">
              <p class="small text-muted mb-3">Abonează-te pentru ultimele știri!</p>
              <form>
                <div class="mb-2">
                  <input type="email" class="form-control form-control-sm" placeholder="Email...">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Abonează-te</button>
              </form>
            </div>
          </div>
          
          <!-- Social Widget -->
          <div class="card mb-4">
            <div class="card-header"><strong><i class="fas fa-share-alt me-2"></i>Urmărește-ne</strong></div>
            <div class="card-body">
              <div class="d-flex gap-2 justify-content-center">
                <a href="#" class="btn btn-outline-primary btn-sm"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="btn btn-outline-info btn-sm"><i class="fab fa-twitter"></i></a>
                <a href="#" class="btn btn-outline-danger btn-sm"><i class="fab fa-instagram"></i></a>
                <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-tiktok"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.col-lg-4 sidebar -->
    </div><!-- /.row -->
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

<?php if (!$q): ?>
    </div><!-- /.main-column-inner -->
  </main><!-- /.main-column -->
  
  <!-- SIDEBAR DREAPTA -->
  <aside class="sidebar-column sidebar-right d-none d-xl-block">
    <div class="sidebar-sticky">
      <?php renderRightSidebar(); ?>
    </div>
  </aside>
</div><!-- /.three-column-layout -->
<?php endif; ?>

<?php include(__DIR__ . '/includes/footer.php'); ?>
