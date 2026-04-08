<?php 
// SEO Configuration
$pageTitle = 'Politica de Confidențialitate - MatchDay.ro';
$pageDescription = 'Politica de confidențialitate și protecția datelor personale pe MatchDay.ro';
$pageKeywords = ['privacy', 'confidentialitate', 'date personale', 'gdpr', 'matchday'];
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Politica de confidențialitate']
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
    <h1 class="display-6 m-0">Politica de Confidențialitate</h1>
  </div>
</section>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <article class="content-article">
        <p class="lead">Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
        
        <div class="alert alert-info mb-4">
          <i class="fas fa-info-circle me-2"></i>
          Respectăm confidențialitatea datelor tale. Această politică explică ce date colectăm și cum le folosim.
        </div>
        
        <h2 class="h4 mb-3">1. Ce date colectăm</h2>
        <p>MatchDay.ro colectează următoarele tipuri de date:</p>
        
        <h5>Date furnizate voluntar:</h5>
        <ul>
          <li><strong>Formularul de contact:</strong> Nume, email, mesaj</li>
          <li><strong>Comentarii:</strong> Nume (sau pseudonim), email (opțional), conținutul comentariului</li>
          <li><strong>Newsletter:</strong> Adresa de email (dacă te abonezi)</li>
          <li><strong>Sondaje:</strong> Voturile sunt anonime, păstrăm doar totalurile</li>
        </ul>
        
        <h5>Date colectate automat:</h5>
        <ul>
          <li>Adresa IP (anonimizată pentru statistici)</li>
          <li>Tipul de browser și dispozitiv</li>
          <li>Paginile vizitate și durata vizitei</li>
          <li>Sursa de referință (de unde ai venit pe site)</li>
        </ul>
        
        <h2 class="h4 mb-3">2. Cum folosim datele</h2>
        <p>Datele colectate sunt utilizate pentru:</p>
        <ul>
          <li>Răspunsuri la mesajele trimise prin formularul de contact</li>
          <li>Moderarea și afișarea comentariilor</li>
          <li>Trimiterea newsletter-ului (dacă ești abonat)</li>
          <li>Înțelegerea traficului și îmbunătățirea site-ului</li>
          <li>Protecția împotriva spam-ului și abuzurilor</li>
        </ul>
        
        <h2 class="h4 mb-3">3. Cookie-uri</h2>
        <p>Folosim cookie-uri pentru:</p>
        <ul>
          <li><strong>Cookie-uri esențiale:</strong> Sesiune, preferințe, securitate</li>
          <li><strong>Cookie-uri de analiză:</strong> Statistici anonime de vizitare</li>
          <li><strong>Cookie-uri funcționale:</strong> Memorarea voturilor la sondaje</li>
        </ul>
        <p>Poți dezactiva cookie-urile din setările browser-ului, dar unele funcții ale site-ului pot fi afectate.</p>
        
        <h2 class="h4 mb-3">4. Partajarea datelor</h2>
        <p><strong>Nu vindem și nu partajăm</strong> datele tale personale cu terți, cu excepția:</p>
        <ul>
          <li>Furnizorilor de hosting (necesari pentru funcționarea site-ului)</li>
          <li>Autorităților competente, dacă legea o impune</li>
        </ul>
        
        <h2 class="h4 mb-3">5. Securitatea datelor</h2>
        <p>Implementăm măsuri de securitate pentru protecția datelor:</p>
        <ul>
          <li>Conexiune securizată HTTPS</li>
          <li>Protecție CSRF pentru formulare</li>
          <li>Parolele sunt criptate (pentru conturi admin)</li>
          <li>Backup-uri regulate</li>
        </ul>
        
        <h2 class="h4 mb-3">6. Drepturile tale (GDPR)</h2>
        <p>Conform GDPR, ai următoarele drepturi:</p>
        <ul>
          <li><strong>Dreptul de acces:</strong> Poți solicita o copie a datelor tale</li>
          <li><strong>Dreptul la rectificare:</strong> Poți cere corectarea datelor incorecte</li>
          <li><strong>Dreptul la ștergere:</strong> Poți cere ștergerea datelor tale</li>
          <li><strong>Dreptul de opoziție:</strong> Te poți opune prelucrării datelor</li>
          <li><strong>Dreptul la portabilitate:</strong> Poți cere transferul datelor</li>
        </ul>
        <p>Pentru exercitarea acestor drepturi, contactează-ne la <a href="mailto:contact@matchday.ro">contact@matchday.ro</a></p>
        
        <h2 class="h4 mb-3">7. Păstrarea datelor</h2>
        <ul>
          <li>Mesajele de contact: 1 an</li>
          <li>Comentarii: Până la ștergerea la cerere</li>
          <li>Email-uri newsletter: Până la dezabonare</li>
          <li>Statistici: Anonimizate și păstrate pe termen lung</li>
        </ul>
        
        <h2 class="h4 mb-3">8. Modificări</h2>
        <p>Putem actualiza această politică periodic. Te încurajăm să verifici această pagină pentru ultimele modificări.</p>
        
        <div class="alert alert-secondary mt-4">
          <i class="fas fa-envelope me-2"></i>
          Pentru întrebări despre confidențialitate, scrie-ne la <a href="mailto:contact@matchday.ro">contact@matchday.ro</a>
        </div>
        
        <div class="text-center mt-4">
          <a href="disclaimer.php" class="btn btn-outline-primary me-2">
            <i class="fas fa-exclamation-triangle me-1"></i>Disclaimer
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
