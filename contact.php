<?php 
// SEO Configuration for Contact page
$pageTitle = 'Contact - Scrie-ne pe MatchDay.ro';
$pageDescription = 'Intră în legătură cu echipa MatchDay.ro. Trimite-ne sugestii, întrebări sau propuneri pentru articole despre fotbalul românesc.';
$pageKeywords = ['contact matchday', 'contact david nyikora', 'scrie-ne', 'sugestii', 'colaborare', 'feedback'];
$pageType = 'website';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Contact']
];

include(__DIR__ . '/includes/header.php'); 

// Mesaje de feedback
$successMsg = '';
$errorMsg = '';

if (isset($_GET['sent'])) {
    if ($_GET['sent'] == '1') {
        $successMsg = 'Mesaj trimis cu succes! ✅ Pentru testare locală, mesajul a fost salvat în fișier.';
    } else {
        $errorMsg = 'A apărut o problemă la trimiterea mesajului. Te rog încearcă din nou.';
    }
}

if (isset($_GET['error'])) {
    $errorMsg = Security::sanitizeInput($_GET['error']);
}
?>

<section class="hero"><div class="container">
  <div class="brand-badge mb-3"><img src="assets/images/logo.png" width="28" height="28" alt="logo">
  <div><strong><?php echo SITE_NAME; ?></strong><div class="small-muted"><?php echo SITE_TAGLINE; ?></div></div></div>
  <h1 class="display-6 m-0">Contact</h1>
  
  <?php if ($successMsg): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
      <?php echo $successMsg; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
      <?php echo $errorMsg; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
</div></section>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Informații de contact -->
      <div class="card mb-4 shadow-sm">
        <div class="card-body">
          <h3 class="h5 mb-3"><i class="fas fa-address-card me-2 text-primary"></i>Informații de contact</h3>
          <div class="row">
            <div class="col-md-6">
              <p><strong><i class="fas fa-user me-2"></i>Jurnalist:</strong><br>David Nyikora</p>
            </div>
            <div class="col-md-6">
              <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong><br>
                <a href="mailto:contact@matchday.ro">contact@matchday.ro</a>
              </p>
            </div>
            <div class="col-md-6">
              <p><strong><i class="fas fa-phone me-2"></i>Telefon:</strong><br>
                <a href="tel:+40740173581">0740 173 581</a>
              </p>
            </div>
            <div class="col-md-6">
              <p><strong><i class="fas fa-globe me-2"></i>Website:</strong><br>
                <a href="https://matchday.ro" target="_blank">www.matchday.ro</a>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Formular de contact -->
      <div class="card shadow-sm">
        <div class="card-header">
          <h3 class="h5 mb-0"><i class="fas fa-paper-plane me-2"></i>Trimite-ne un mesaj</h3>
        </div>
        <div class="card-body">

<div class="container my-4"><div class="row justify-content-center"><div class="col-lg-8">
  <form method="post" action="send_contact.php" class="card p-3 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
    <div class="mb-3"><label class="form-label">Nume</label><input type="text" name="name" class="form-control" required maxlength="50"></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Mesaj</label><textarea name="message" rows="5" class="form-control" required maxlength="1000"></textarea></div>
    <div class="d-none"><input name="website" placeholder="Leave empty" tabindex="-1" autocomplete="off"></div>
    <button class="btn btn-brand" type="submit">Trimite</button>
    <div class="small text-muted mt-2">
      <i class="fas fa-shield-alt me-1"></i>Formularul este securizat cu protecție CSRF și anti-spam.
    </div>
  </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include(__DIR__ . '/includes/footer.php'); ?>
