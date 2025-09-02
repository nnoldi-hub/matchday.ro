<?php 
// SEO Configuration for About page
$pageTitle = 'Despre MatchDay.ro - Jurnalul fotbalului românesc';
$pageDescription = 'Află mai multe despre MatchDay.ro, jurnalul online dedicat fotbalului românesc. David Nyikora scrie despre meciuri, transferuri și pasiunea pentru fotbal.';
$pageKeywords = ['despre matchday', 'david nyikora', 'fotbal romanesc', 'jurnal sport', 'blogger fotbal', 'pasiune fotbal'];
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Despre']
];

$articleAuthor = 'David Nyikora';

include(__DIR__ . '/includes/header.php'); 
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
    <h1 class="display-6 m-0">Despre MatchDay.ro</h1>
  </div>
</section>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <article class="content-article">
        <div class="lead mb-4">
          <p>Bun venit pe <strong>MatchDay.ro</strong> - locul unde fiecare meci are o poveste, iar noi o scriem cu pasiune și dedicare!</p>
        </div>
        
        <h2 class="h4 mb-3">Cine sunt eu?</h2>
        <p>Salut! Sunt <strong>David Nyikora</strong> și iubesc fotbalul din tot sufletul. De ani de zile urmăresc cu atenție fotbalul românesc și internațional, iar această pasiune m-a determinat să creez MatchDay.ro - un spațiu digital unde să împărtășesc cu voi emoțiile, analizele și gândurile mele despre acest sport minunat.</p>
        
        <h2 class="h4 mb-3">Ce găsești pe MatchDay.ro?</h2>
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <i class="fas fa-futbol fa-2x text-primary mb-3"></i>
                <h5>Analiza meciurilor</h5>
                <p class="small mb-0">Cronici detaliate ale celor mai importante meciuri, cu analize tactice și observații personale.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <i class="fas fa-exchange-alt fa-2x text-success mb-3"></i>
                <h5>Noutăți transferuri</h5>
                <p class="small mb-0">Ultimele informații din piața transferurilor, mutări surprinzătoare și analize de impact.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <i class="fas fa-comments fa-2x text-warning mb-3"></i>
                <h5>Opinii și comentarii</h5>
                <p class="small mb-0">Puncte de vedere personale asupra evenimentelor din fotbal, fără filtre sau compromisuri.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <i class="fas fa-chart-bar fa-2x text-info mb-3"></i>
                <h5>Statistici și analize</h5>
                <p class="small mb-0">Date și cifre care spun povestea din spatele rezultatelor, performanțe și recorduri.</p>
              </div>
            </div>
          </div>
        </div>
        
        <h2 class="h4 mb-3">Misiunea noastră</h2>
        <p>MatchDay.ro nu este doar un blog despre fotbal - este o comunitate pentru toți cei care trăiesc cu intensitate acest sport. Obiectivul meu este să ofer:</p>
        
        <ul class="list-unstyled">
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Conținut autentic</strong> - Doar păreri personale, fără influențe externe</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Analize obiective</strong> - Pe baza faptelor și observațiilor concrete</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Informații la timp</strong> - Actualizări rapide și relevante</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i><strong>Interactivitate</strong> - Dialog deschis cu cititorii prin comentarii și sondaje</li>
        </ul>
        
        <div class="alert alert-primary mt-4">
          <h5 class="alert-heading">
            <i class="fas fa-heart me-2"></i>Mulțumesc că ești aici!
          </h5>
          <p class="mb-0">Fiecare cititor este important pentru dezvoltarea acestei comunități. Dacă îți place ce citești, te invit să lași un comentariu, să împărtășești articolele cu prietenii și să îmi spui ce teme te-ar interesa în viitor!</p>
        </div>
        
        <div class="text-center mt-5">
          <a href="index.php" class="btn btn-primary btn-lg">
            <i class="fas fa-newspaper me-2"></i>
            Citește articolele
          </a>
          <a href="contact.php" class="btn btn-outline-secondary btn-lg ms-3">
            <i class="fas fa-envelope me-2"></i>
            Contactează-mă
          </a>
        </div>
      </article>
    </div>
  </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
