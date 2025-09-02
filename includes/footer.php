<?php
// Ensure variables are defined for footer context
if (!isset($base)) {
    $base = (BASE_URL ?: '');
}
if (!isset($assetBase)) {
    $admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
    $assetBase = $base . ($admin ? '../' : '');
}
?>
    <footer class="border-top py-4 mt-5">
      <div class="container text-center small text-muted">
        <div class="mb-2">
          <span class="brand-band"><img src="<?php echo $assetBase ?>assets/images/logo.png" width="20" height="20" alt="logo" class="me-1 align-text-bottom" /> <strong><?php echo SITE_NAME; ?></strong></span>
          <div class="mt-2"><?php echo SITE_TAGLINE; ?></div>
        </div>
        
        <!-- Contact Info -->
        <div class="mb-3">
          <div><strong>David Nyikora</strong> - Jurnalist sportiv</div>
          <div class="mt-1">
            <a href="mailto:contact@matchday.ro" class="text-decoration-none me-3">
              <i class="fas fa-envelope me-1"></i>contact@matchday.ro
            </a>
            <a href="tel:+40740173581" class="text-decoration-none">
              <i class="fas fa-phone me-1"></i>0740 173 581
            </a>
          </div>
        </div>
        
        <div>&copy; <?php echo date('Y'); ?> MatchDay.ro — Toate drepturile rezervate.</div>
      </div>
    </footer>

    <!-- Bootstrap JS Local -->
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/comments.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/polls.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/social.js"></script>
  </body>
</html>
