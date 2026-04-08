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
    
    <footer class="site-footer">
      <!-- Main Footer -->
      <div class="footer-main py-5">
        <div class="container">
          <div class="row g-4">
            <!-- Brand & Description -->
            <div class="col-lg-4 col-md-6">
              <div class="footer-brand d-flex align-items-center gap-2 mb-3">
                <img src="<?php echo $assetBase ?>assets/images/logo.png" width="40" height="40" alt="logo" />
                <span class="h4 mb-0 text-white"><?php echo SITE_NAME; ?></span>
              </div>
              <p class="text-light opacity-75 small mb-3"><?php echo SITE_TAGLINE; ?></p>
              <p class="text-light opacity-50 small">Cea mai completă platformă de știri sportive din România. Acoperim fotbalul românesc și internațional cu pasiune și profesionalism.</p>
              
              <!-- Social Media -->
              <div class="footer-social mt-3">
                <a href="https://facebook.com/matchday.ro" target="_blank" class="social-link" title="Facebook">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://instagram.com/matchday.ro" target="_blank" class="social-link" title="Instagram">
                  <i class="fab fa-instagram"></i>
                </a>
                <a href="https://twitter.com/matchday_ro" target="_blank" class="social-link" title="Twitter/X">
                  <i class="fab fa-x-twitter"></i>
                </a>
                <a href="https://youtube.com/@matchdayro" target="_blank" class="social-link" title="YouTube">
                  <i class="fab fa-youtube"></i>
                </a>
                <a href="/rss.php" class="social-link" title="RSS Feed">
                  <i class="fas fa-rss"></i>
                </a>
              </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
              <h6 class="footer-title">Navigare</h6>
              <ul class="footer-links">
                <li><a href="/index.php"><i class="fas fa-home me-2"></i>Acasă</a></li>
                <li><a href="/category/champions-league"><i class="fas fa-trophy me-2"></i>Champions League</a></li>
                <li><a href="/category/meciuri"><i class="fas fa-futbol me-2"></i>Meciuri</a></li>
                <li><a href="/category/transferuri"><i class="fas fa-exchange-alt me-2"></i>Transferuri</a></li>
                <li><a href="/calendar-editorial.php"><i class="fas fa-calendar-alt me-2"></i>Program</a></li>
              </ul>
            </div>
            
            <!-- Categories -->
            <div class="col-lg-2 col-md-6">
              <h6 class="footer-title">Categorii</h6>
              <ul class="footer-links">
                <li><a href="/category/statistici"><i class="fas fa-chart-bar me-2"></i>Statistici</a></li>
                <li><a href="/category/opinii"><i class="fas fa-comment me-2"></i>Opinii</a></li>
                <li><a href="/category/interviuri"><i class="fas fa-microphone me-2"></i>Interviuri</a></li>
                <li><a href="/category/competitii"><i class="fas fa-medal me-2"></i>Competiții</a></li>
                <li><a href="/despre.php"><i class="fas fa-info-circle me-2"></i>Despre noi</a></li>
              </ul>
            </div>
            
            <!-- Newsletter & Contact -->
            <div class="col-lg-4 col-md-6">
              <h6 class="footer-title">Newsletter</h6>
              <p class="text-light opacity-75 small mb-3">Abonează-te pentru a primi ultimele știri direct pe email!</p>
              <form id="footer-newsletter-form" class="footer-newsletter">
                <div class="input-group">
                  <input type="email" class="form-control" placeholder="Adresa ta de email" required>
                  <button type="submit" class="btn btn-accent">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </div>
              </form>
              <div id="newsletter-message" class="small mt-2"></div>
              
              <!-- Contact Info -->
              <div class="footer-contact mt-4">
                <h6 class="footer-title">Contact</h6>
                <div class="contact-item">
                  <i class="fas fa-user me-2"></i>
                  <span>David Nyikora - Jurnalist sportiv</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-envelope me-2"></i>
                  <a href="mailto:contact@matchday.ro">contact@matchday.ro</a>
                </div>
                <div class="contact-item">
                  <i class="fas fa-phone me-2"></i>
                  <a href="tel:+40740173581">0740 173 581</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Footer Bottom -->
      <div class="footer-bottom py-3">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
              <span class="text-light opacity-75 small">
                &copy; <?php echo date('Y'); ?> MatchDay.ro — Toate drepturile rezervate.
              </span>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
              <a href="/contact.php" class="footer-bottom-link">Contact</a>
              <span class="text-light opacity-50 mx-2">|</span>
              <a href="/despre.php" class="footer-bottom-link">Despre</a>
              <span class="text-light opacity-50 mx-2">|</span>
              <a href="/termeni.php" class="footer-bottom-link">Termeni</a>
              <span class="text-light opacity-50 mx-2">|</span>
              <a href="/privacy.php" class="footer-bottom-link">Confidențialitate</a>
              <span class="text-light opacity-50 mx-2">|</span>
              <a href="/disclaimer.php" class="footer-bottom-link">Disclaimer</a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Bootstrap JS Local -->
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/comments.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/polls.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/social.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/gamification.js"></script>
    
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
