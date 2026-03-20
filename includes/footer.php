<?php
// Ensure variables are defined for footer context
if (!isset($base)) {
    $base = (BASE_URL ?: '');
}
if (!isset($assetBase)) {
    $admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
    $assetBase = $base . ($admin ? '../' : '');
}

// Require Ad classes if not in admin
if (!$admin && !class_exists('AdWidget')) {
    require_once(__DIR__ . '/Ad.php');
    require_once(__DIR__ . '/AdWidget.php');
}
?>
    <?php 
    // Footer Ad Banner (before footer)
    if (!$admin && class_exists('AdWidget')):
        $footerAd = AdWidget::render('footer');
        if ($footerAd):
    ?>
    <div class="container-fluid bg-light py-3 border-top">
        <div class="container text-center">
            <?= $footerAd ?>
        </div>
    </div>
    <?php endif; endif; ?>
    
    <footer class="border-top py-4 mt-5">
      <div class="container">
        <div class="row">
          <!-- Newsletter Widget -->
          <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="newsletter-widget p-3 bg-dark rounded">
              <h6 class="text-white mb-2"><i class="fas fa-envelope me-2"></i>Newsletter</h6>
              <p class="text-muted small mb-2">Primește știrile pe email!</p>
              <form id="footer-newsletter-form" class="d-flex gap-2">
                <input type="email" class="form-control form-control-sm" placeholder="Email" required>
                <button type="submit" class="btn btn-accent btn-sm">
                  <i class="fas fa-paper-plane"></i>
                </button>
              </form>
              <div id="newsletter-message" class="small mt-2"></div>
            </div>
          </div>
          
          <!-- Logo & Tagline -->
          <div class="col-lg-4 text-center mb-4 mb-lg-0">
            <span class="brand-band"><img src="<?php echo $assetBase ?>assets/images/logo.png" width="20" height="20" alt="logo" class="me-1 align-text-bottom" /> <strong><?php echo SITE_NAME; ?></strong></span>
            <div class="mt-2 text-muted small"><?php echo SITE_TAGLINE; ?></div>
          </div>
          
          <!-- Contact Info -->
          <div class="col-lg-4 text-center text-lg-end small text-muted">
            <div><strong>David Nyikora</strong> - Jurnalist sportiv</div>
            <div class="mt-1">
              <a href="mailto:contact@matchday.ro" class="text-decoration-none me-2">
                <i class="fas fa-envelope me-1"></i>contact@matchday.ro
              </a>
              <a href="tel:+40740173581" class="text-decoration-none">
                <i class="fas fa-phone me-1"></i>0740 173 581
              </a>
            </div>
          </div>
        </div>
        
        <hr class="my-3">
        <div class="text-center small text-muted">
          &copy; <?php echo date('Y'); ?> MatchDay.ro — Toate drepturile rezervate.
        </div>
      </div>
    </footer>

    <!-- Bootstrap JS Local -->
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/comments.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/polls.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/social.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .then((registration) => {
                    console.log('ServiceWorker registered:', registration.scope);
                    
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New version available
                                console.log('New version available!');
                            }
                        });
                    });
                })
                .catch((error) => {
                    console.log('ServiceWorker registration failed:', error);
                });
        });
    }
    
    // Footer Newsletter Form
    document.getElementById('footer-newsletter-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const email = form.querySelector('input[type="email"]').value;
        const msgDiv = document.getElementById('newsletter-message');
        const btn = form.querySelector('button');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const formData = new FormData();
            formData.append('action', 'subscribe');
            formData.append('email', email);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            
            const response = await fetch('/newsletter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await response.json();
            msgDiv.className = 'small mt-2 text-' + (data.success ? 'success' : 'danger');
            msgDiv.textContent = data.message;
            
            if (data.success) {
                form.reset();
            }
        } catch (err) {
            msgDiv.className = 'small mt-2 text-danger';
            msgDiv.textContent = 'Eroare de conexiune.';
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
    </script>
  </body>
</html>
