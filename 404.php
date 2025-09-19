<?php 
require_once(__DIR__ . '/config/config.php');

// SEO Configuration for 404 page
$pageTitle = 'Pagină negăsită - MatchDay.ro';
$pageDescription = 'Această pagină nu a fost găsită pe MatchDay.ro. Întoarce-te la știrile de fotbal sau caută ce îți dorești.';
$pageKeywords = ['404', 'pagina nu exista', 'eroare', 'matchday'];
$pageType = 'website';

// Breadcrumbs for 404
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Pagină negăsită']
];

// Set proper HTTP status
http_response_code(404);

include(__DIR__ . '/includes/header.php'); 
?>

<main class="container container-narrow py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8 text-center">
      <div class="mb-4">
        <i class="fas fa-search fa-5x text-muted mb-3"></i>
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h2 class="h3 mb-3">Oops! Pagina nu a fost găsită</h2>
        <p class="lead text-muted mb-4">
          Se pare că pagina pe care o cauți nu există sau a fost mutată. 
          Nu-ți face griji, te ajutăm să găsești ce căutai!
        </p>
      </div>
      
      <!-- Search Box -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <form action="index.php" method="GET" class="row g-2">
            <div class="col-md-8">
              <input type="text" name="q" class="form-control" 
                     placeholder="Caută articole, echipe, jucători..." 
                     value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-2"></i>Caută
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Navigation Options -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <a href="index.php" class="btn btn-outline-primary btn-lg w-100">
            <i class="fas fa-home me-2"></i>Înapoi la știri
          </a>
        </div>
        <div class="col-md-6">
          <a href="contact.php" class="btn btn-outline-secondary btn-lg w-100">
            <i class="fas fa-envelope me-2"></i>Contactează-ne
          </a>
        </div>
      </div>
      
      <!-- Categories -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
          <h3 class="h5 mb-0"><i class="fas fa-folder me-2"></i>Explorează categoriile</h3>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <?php 
            $categories = [
                'opinii' => ['name' => 'Opinii', 'icon' => 'fas fa-comments', 'color' => 'warning'],
                'analize' => ['name' => 'Analize', 'icon' => 'fas fa-chart-line', 'color' => 'info'],
                'interviuri' => ['name' => 'Interviuri', 'icon' => 'fas fa-microphone', 'color' => 'success'],
                'reportaje' => ['name' => 'Reportaje', 'icon' => 'fas fa-camera', 'color' => 'danger'],
                'transfer' => ['name' => 'Transfer', 'icon' => 'fas fa-exchange-alt', 'color' => 'primary'],
                'nacional' => ['name' => 'Fotbal Național', 'icon' => 'fas fa-flag', 'color' => 'warning']
            ];
            
            foreach ($categories as $slug => $info): 
            ?>
            <div class="col-md-4 col-sm-6">
              <a href="/category.php?cat=<?= $slug ?>" 
                 class="btn btn-outline-<?= $info['color'] ?> w-100 d-flex align-items-center">
                <i class="<?= $info['icon'] ?> me-2"></i>
                <?= $info['name'] ?>
              </a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Popular Content -->
      <div class="mt-4 text-start">
        <h3 class="h5 mb-3">Poate te interesează:</h3>
        <ul class="list-unstyled">
          <li class="mb-2">
            <a href="despre.php" class="text-decoration-none">
              <i class="fas fa-info-circle text-primary me-2"></i>
              Află mai multe despre MatchDay.ro
            </a>
          </li>
          <li class="mb-2">
            <a href="index.php" class="text-decoration-none">
              <i class="fas fa-newspaper text-primary me-2"></i>
              Ultimele știri din fotbal
            </a>
          </li>
          <li class="mb-2">
            <a href="contact.php" class="text-decoration-none">
              <i class="fas fa-paper-plane text-primary me-2"></i>
              Trimite-ne o întrebare
            </a>
          </li>
        </ul>
      </div>
      
      <div class="mt-4 text-muted">
        <small>
          <strong>Cod eroare:</strong> HTTP 404 Not Found<br>
          <strong>Timp:</strong> <?= date('d.m.Y H:i') ?>
        </small>
      </div>
    </div>
  </div>
</main>

<!-- Structured Data for 404 Page -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Pagină negăsită - MatchDay.ro",
  "description": "Această pagină nu a fost găsită pe MatchDay.ro.",
  "url": "<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://matchday.ro/index.php?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  },
  "mainEntity": {
    "@type": "WebSite",
    "name": "MatchDay.ro",
    "url": "https://matchday.ro/",
    "potentialAction": {
      "@type": "SearchAction",
      "target": "https://matchday.ro/index.php?q={search_term}",
      "query-input": "required name=search_term"
    }
  }
}
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>
