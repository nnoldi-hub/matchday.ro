<?php 
// SEO Configuration
$pageTitle = 'Termeni și Condiții - MatchDay.ro';
$pageDescription = 'Termenii și condițiile de utilizare a site-ului MatchDay.ro';
$pageKeywords = ['termeni', 'conditii', 'utilizare', 'reguli', 'matchday'];
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Termeni și condiții']
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
    <h1 class="display-6 m-0">Termeni și Condiții</h1>
  </div>
</section>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <article class="content-article">
        <p class="lead">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
        
        <div class="alert alert-warning mb-4">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Prin utilizarea site-ului MatchDay.ro, accepți acești termeni și condiții.
        </div>
        
        <h2 class="h4 mb-3">1. Acceptarea termenilor</h2>
        <p>Accesarea și utilizarea site-ului MatchDay.ro implică acceptarea în totalitate a acestor termeni și condiții. Dacă nu ești de acord cu oricare dintre termeni, te rugăm să nu utilizezi site-ul.</p>
        
        <h2 class="h4 mb-3">2. Descrierea serviciului</h2>
        <p>MatchDay.ro este un blog de jurnalism sportiv care oferă:</p>
        <ul>
          <li>Articole și analize despre fotbal</li>
          <li>Știri și informații sportive</li>
          <li>Sondaje interactive</li>
          <li>Secțiune de comentarii</li>
          <li>Newsletter (opțional)</li>
        </ul>
        
        <h2 class="h4 mb-3">3. Utilizarea conținutului</h2>
        <p>Conținutul de pe MatchDay.ro poate fi utilizat în următoarele condiții:</p>
        <ul>
          <li><strong>Citire și distribuire:</strong> Poți citi și distribui articolele pe rețelele sociale</li>
          <li><strong>Citare:</strong> Poți cita fragmente cu menționarea sursei și link</li>
          <li><strong>Interzis:</strong> Reproducerea integrală fără permisiune, modificarea conținutului, utilizarea comercială</li>
        </ul>
        
        <h2 class="h4 mb-3">4. Comentarii și contribuții</h2>
        <p>Când postezi un comentariu, te angajezi să:</p>
        <ul>
          <li>Respecți ceilalți utilizatori și autori</li>
          <li>Nu folosești limbaj ofensator, discriminatoriu sau de ură</li>
          <li>Nu postezi spam, link-uri irelevante sau conținut ilegal</li>
          <li>Nu te dai drept altcineva (impersonare)</li>
        </ul>
        <p>Ne rezervăm dreptul de a șterge orice comentariu care încalcă aceste reguli, fără notificare prealabilă.</p>
        
        <h2 class="h4 mb-3">5. Sondaje</h2>
        <ul>
          <li>Voturile în sondaje sunt anonime</li>
          <li>Poți vota o singură dată per sondaj (verificat prin cookie)</li>
          <li>Rezultatele sondajelor sunt orientative și nu au valoare științifică</li>
        </ul>
        
        <h2 class="h4 mb-3">6. Conturi și acces</h2>
        <p>Pentru moment, MatchDay.ro nu oferă conturi pentru utilizatori obișnuiți. Accesul la zona de administrare este restricționat doar pentru persoanele autorizate.</p>
        
        <h2 class="h4 mb-3">7. Limitare de responsabilitate</h2>
        <p>MatchDay.ro și autorii săi:</p>
        <ul>
          <li>Nu garantează disponibilitatea continuă a site-ului</li>
          <li>Nu sunt responsabili pentru erori tehnice sau întreruperi</li>
          <li>Nu răspund pentru decizii luate pe baza conținutului site-ului</li>
          <li>Nu garantează acuratețea informațiilor terțe</li>
        </ul>
        
        <h2 class="h4 mb-3">8. Proprietate intelectuală</h2>
        <ul>
          <li>Logo-ul și denumirea MatchDay.ro sunt proprietatea noastră</li>
          <li>Codul sursă și designul sunt protejate de drepturi de autor</li>
          <li>Nu poți utiliza marca noastră fără permisiune scrisă</li>
        </ul>
        
        <h2 class="h4 mb-3">9. Link-uri externe</h2>
        <p>Site-ul poate conține link-uri către site-uri terțe. Nu controlăm și nu suntem responsabili pentru conținutul sau politicile acestor site-uri.</p>
        
        <h2 class="h4 mb-3">10. Modificări ale termenilor</h2>
        <p>Ne rezervăm dreptul de a modifica acești termeni în orice moment. Modificările intră în vigoare la publicarea pe această pagină. Utilizarea continuă a site-ului după modificări reprezintă acceptarea noilor termeni.</p>
        
        <h2 class="h4 mb-3">11. Legea aplicabilă</h2>
        <p>Acești termeni sunt guvernați de legislația României. Orice dispute vor fi soluționate de instanțele competente din România.</p>
        
        <h2 class="h4 mb-3">12. Contact</h2>
        <p>Pentru întrebări despre acești termeni, contactează-ne:</p>
        <ul>
          <li>Email: <a href="mailto:contact@matchday.ro">contact@matchday.ro</a></li>
          <li>Formular: <a href="contact.php">Pagina de contact</a></li>
        </ul>
        
        <div class="text-center mt-4">
          <a href="disclaimer.php" class="btn btn-outline-primary me-2">
            <i class="fas fa-exclamation-triangle me-1"></i>Disclaimer
          </a>
          <a href="privacy.php" class="btn btn-outline-secondary">
            <i class="fas fa-shield-alt me-1"></i>Politica de confidențialitate
          </a>
        </div>
      </article>
    </div>
  </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
