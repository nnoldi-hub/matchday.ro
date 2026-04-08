<?php 
// SEO Configuration
$pageTitle = 'Disclaimer - MatchDay.ro';
$pageDescription = 'Disclaimer și limitare de responsabilitate pentru conținutul publicat pe MatchDay.ro';
$pageKeywords = ['disclaimer', 'termeni', 'responsabilitate', 'matchday'];
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Disclaimer']
];

$pageBodyClass = 'page-article';
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
    <h1 class="display-6 m-0">Disclaimer</h1>
  </div>
</section>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <article class="content-article">
        <p class="lead">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
        
        <h2 class="h4 mb-3">1. Informații generale</h2>
        <p>MatchDay.ro este un blog personal dedicat jurnalismului sportiv, cu focus pe fotbalul românesc și internațional. Site-ul este administrat de David Nyikora și oferă articole de opinie, analize, știri și comentarii despre lumea fotbalului.</p>
        
        <h2 class="h4 mb-3">2. Conținutul site-ului</h2>
        <p>Toate articolele, analizele și opiniile publicate pe MatchDay.ro reprezintă punctul de vedere personal al autorului și nu constituie:</p>
        <ul>
          <li>Poziția oficială a niciunui club, federație sau organizație sportivă</li>
          <li>Sfaturi de pariere sau investiții</li>
          <li>Informații verificate de surse oficiale (în toate cazurile)</li>
        </ul>
        
        <h2 class="h4 mb-3">3. Surse și acuratețe</h2>
        <p>Ne străduim să oferim informații corecte și actualizate, preluate din surse publice și de încredere. Cu toate acestea:</p>
        <ul>
          <li>Nu garantăm acuratețea 100% a tuturor informațiilor</li>
          <li>Sursele pot conține erori pe care nu le putem verifica întotdeauna</li>
          <li>Informațiile se pot schimba după publicare</li>
        </ul>
        <p>Dacă observi o greșeală, te rugăm să ne contactezi pentru corecturi.</p>
        
        <h2 class="h4 mb-3">4. Drepturi de autor</h2>
        <p>Conținutul original de pe MatchDay.ro (texte, analize, opinii) este protejat de drepturi de autor. Poți cita articolele noastre cu menționarea sursei și link către articolul original.</p>
        <p>Imaginile folosite sunt fie proprii, fie din surse publice cu licență liberă, fie cu credit către sursă.</p>
        
        <h2 class="h4 mb-3">5. Link-uri externe</h2>
        <p>Site-ul poate conține link-uri către site-uri externe. Nu suntem responsabili pentru conținutul sau practicile de confidențialitate ale acestor site-uri terțe.</p>
        
        <h2 class="h4 mb-3">6. Limitare de responsabilitate</h2>
        <p>MatchDay.ro și autorii săi nu sunt responsabili pentru:</p>
        <ul>
          <li>Decizii luate pe baza informațiilor de pe acest site</li>
          <li>Pariuri sau pierderi financiare</li>
          <li>Interpretări eronate ale conținutului</li>
          <li>Întreruperi sau erori tehnice ale site-ului</li>
        </ul>
        
        <h2 class="h4 mb-3">7. Modificări</h2>
        <p>Ne rezervăm dreptul de a modifica acest disclaimer în orice moment. Versiunea actualizată va fi publicată pe această pagină.</p>
        
        <div class="alert alert-secondary mt-4">
          <i class="fas fa-envelope me-2"></i>
          Pentru întrebări sau clarificări, ne poți contacta la <a href="mailto:contact@matchday.ro">contact@matchday.ro</a>
        </div>
        
        <div class="text-center mt-4">
          <a href="privacy.php" class="btn btn-outline-primary me-2">
            <i class="fas fa-shield-alt me-1"></i>Politica de confidențialitate
          </a>
          <a href="termeni.php" class="btn btn-outline-secondary">
            <i class="fas fa-file-contract me-1"></i>Termeni și condiții
          </a>
        </div>
      </article>
    </div>
  </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
