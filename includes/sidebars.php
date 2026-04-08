<?php
/**
 * Sidebar Components for Homepage
 * MatchDay.ro
 */

require_once(__DIR__ . '/Post.php');
require_once(__DIR__ . '/Category.php');

/**
 * Left Sidebar - Recent articles by category
 */
function renderLeftSidebar() {
    $categories = require(__DIR__ . '/../config/categories.php');
    
    // Get recent articles from different categories
    $recentLiga1 = Post::getPublished(1, 3, 'liga-1');
    $recentUCL = Post::getPublished(1, 3, 'champions-league');
    ?>
    
    <!-- Widget: Liga 1 Recent -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header">
            <i class="fas fa-futbol me-2"></i>Liga 1
        </div>
        <div class="sidebar-widget-body">
            <?php if (!empty($recentLiga1)): ?>
                <?php foreach ($recentLiga1 as $post): ?>
                <a href="<?= SEOManager::getArticleUrl($post['slug']) ?>" class="sidebar-article-link">
                    <div class="sidebar-article">
                        <?php if (!empty($post['cover_image'])): ?>
                        <div class="sidebar-article-img" style="background-image: url('<?= htmlspecialchars($post['cover_image']) ?>')"></div>
                        <?php else: ?>
                        <div class="sidebar-article-img sidebar-article-img-placeholder">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <?php endif; ?>
                        <div class="sidebar-article-content">
                            <h4><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?><?= mb_strlen($post['title']) > 50 ? '...' : '' ?></h4>
                            <span class="sidebar-article-date">
                                <i class="far fa-clock me-1"></i><?= date('d.m', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted text-center py-2">Niciun articol încă</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Widget: Champions League Recent -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);">
            <i class="fas fa-trophy me-2"></i>Champions League
        </div>
        <div class="sidebar-widget-body">
            <?php if (!empty($recentUCL)): ?>
                <?php foreach ($recentUCL as $post): ?>
                <a href="<?= SEOManager::getArticleUrl($post['slug']) ?>" class="sidebar-article-link">
                    <div class="sidebar-article">
                        <?php if (!empty($post['cover_image'])): ?>
                        <div class="sidebar-article-img" style="background-image: url('<?= htmlspecialchars($post['cover_image']) ?>')"></div>
                        <?php else: ?>
                        <div class="sidebar-article-img sidebar-article-img-placeholder">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <?php endif; ?>
                        <div class="sidebar-article-content">
                            <h4><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?><?= mb_strlen($post['title']) > 50 ? '...' : '' ?></h4>
                            <span class="sidebar-article-date">
                                <i class="far fa-clock me-1"></i><?= date('d.m', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted text-center py-2">Niciun articol încă</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Widget: Quick Stats -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);">
            <i class="fas fa-chart-line me-2"></i>Statistici
        </div>
        <div class="sidebar-widget-body">
            <?php 
            $totalArticles = Post::countPublished();
            $categoryCounts = Post::getCountByCategory();
            ?>
            <div class="quick-stats">
                <div class="quick-stat">
                    <span class="quick-stat-value"><?= $totalArticles ?></span>
                    <span class="quick-stat-label">Articole</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-value"><?= count($categoryCounts) ?></span>
                    <span class="quick-stat-label">Categorii</span>
                </div>
            </div>
        </div>
    </div>
    
    <?php
}

/**
 * Right Sidebar - Popular articles, categories, social
 */
function renderRightSidebar() {
    $categories = require(__DIR__ . '/../config/categories.php');
    
    // Get articles for "Cele mai citite" - using latest as proxy
    $popularArticles = Post::getLatest(5, true);
    
    // Get recent transfers/news
    $recentTransfers = Post::getPublished(1, 3, 'transferuri');
    ?>
    
    <!-- Widget: Cele mai citite -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);">
            <i class="fas fa-fire me-2"></i>Cele mai citite
        </div>
        <div class="sidebar-widget-body">
            <?php if (!empty($popularArticles)): ?>
                <ol class="popular-list">
                <?php foreach ($popularArticles as $index => $post): ?>
                    <li>
                        <a href="<?= SEOManager::getArticleUrl($post['slug']) ?>" class="popular-item">
                            <span class="popular-rank"><?= $index + 1 ?></span>
                            <span class="popular-title"><?= htmlspecialchars(mb_substr($post['title'], 0, 55)) ?><?= mb_strlen($post['title']) > 55 ? '...' : '' ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="small text-muted text-center py-2">Niciun articol încă</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Widget: Transferuri -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #38a169 0%, #276749 100%);">
            <i class="fas fa-exchange-alt me-2"></i>Transferuri
        </div>
        <div class="sidebar-widget-body">
            <?php if (!empty($recentTransfers)): ?>
                <?php foreach ($recentTransfers as $post): ?>
                <a href="<?= SEOManager::getArticleUrl($post['slug']) ?>" class="sidebar-article-link">
                    <div class="sidebar-article">
                        <?php if (!empty($post['cover_image'])): ?>
                        <div class="sidebar-article-img" style="background-image: url('<?= htmlspecialchars($post['cover_image']) ?>')"></div>
                        <?php else: ?>
                        <div class="sidebar-article-img sidebar-article-img-placeholder">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <?php endif; ?>
                        <div class="sidebar-article-content">
                            <h4><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?><?= mb_strlen($post['title']) > 50 ? '...' : '' ?></h4>
                            <span class="sidebar-article-date">
                                <i class="far fa-clock me-1"></i><?= date('d.m', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted text-center py-2">Niciun transfer încă</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Widget: Categorii -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header">
            <i class="fas fa-th-large me-2"></i>Categorii
        </div>
        <div class="sidebar-widget-body p-0">
            <ul class="category-list">
                <?php 
                $categoryCounts = [];
                $counts = Post::getCountByCategory();
                foreach ($counts as $c) {
                    $categoryCounts[$c['category_slug']] = $c['count'];
                }
                foreach ($categories as $slug => $cat): 
                    $count = $categoryCounts[$slug] ?? 0;
                ?>
                <li>
                    <a href="<?= SEOManager::getCategoryUrl($slug) ?>" class="category-link">
                        <span class="category-icon" style="color: <?= $cat['color'] ?>">
                            <i class="<?= $cat['icon'] ?>"></i>
                        </span>
                        <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                        <span class="category-count"><?= $count ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Widget: Autor -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);">
            <i class="fas fa-pen-fancy me-2"></i>Autorul
        </div>
        <div class="sidebar-widget-body">
            <div class="author-spotlight">
                <div class="author-avatar">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="author-info">
                    <h5 class="author-name">David Nyikora</h5>
                    <p class="author-role">Jurnalist Sportiv</p>
                    <p class="author-bio">Pasionat de fotbal din copilărie. Scriu despre Liga 1, Champions League și tot ce mișcă în lumea fotbalului.</p>
                    <a href="despre.php" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-info-circle me-1"></i>Despre mine
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Widget: Taguri Populare -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);">
            <i class="fas fa-tags me-2"></i>Teme populare
        </div>
        <div class="sidebar-widget-body">
            <div class="popular-tags">
                <a href="search.php?q=champions+league" class="popular-tag">Champions League</a>
                <a href="search.php?q=liga+1" class="popular-tag">Liga 1</a>
                <a href="search.php?q=transferuri" class="popular-tag">Transferuri</a>
                <a href="search.php?q=real+madrid" class="popular-tag">Real Madrid</a>
                <a href="search.php?q=barcelona" class="popular-tag">Barcelona</a>
                <a href="search.php?q=fcsb" class="popular-tag">FCSB</a>
                <a href="search.php?q=cfr+cluj" class="popular-tag">CFR Cluj</a>
                <a href="search.php?q=rapid" class="popular-tag">Rapid</a>
            </div>
        </div>
    </div>
    
    <!-- Widget: Social -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="fas fa-share-alt me-2"></i>Urmărește-ne
        </div>
        <div class="sidebar-widget-body">
            <div class="social-buttons">
                <a href="#" class="social-btn social-btn-facebook" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-btn social-btn-twitter" title="Twitter/X">
                    <i class="fab fa-x-twitter"></i>
                </a>
                <a href="#" class="social-btn social-btn-instagram" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-btn social-btn-youtube" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="#" class="social-btn social-btn-tiktok" title="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>
        </div>
    </div>
    
    <?php
}

/**
 * Live Scores Widget - Real-time match scores
 * Can be embedded anywhere on the site
 */
function renderLiveScoresWidget($options = []) {
    $containerId = $options['containerId'] ?? 'live-scores-widget';
    $showFilter = $options['showFilter'] ?? true;
    $maxMatches = $options['maxMatches'] ?? 8;
    $competition = $options['competition'] ?? null;
    ?>
    
    <!-- Widget: Scoruri Live -->
    <div class="sidebar-widget live-scores-sidebar-widget">
        <div id="<?= htmlspecialchars($containerId) ?>"></div>
    </div>
    
    <script src="/assets/js/live-scores.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new LiveScoresWidget('<?= htmlspecialchars($containerId) ?>', {
            showCompetitionFilter: <?= $showFilter ? 'true' : 'false' ?>,
            maxMatches: <?= (int)$maxMatches ?>,
            <?php if ($competition): ?>
            competition: '<?= htmlspecialchars($competition) ?>',
            <?php endif; ?>
            autoRefresh: true,
            refreshInterval: 60000
        });
    });
    </script>
    
    <?php
}

/**
 * Contribute CTA Widget - Encourages external contributions
 */
function renderContributeCTA() {
    ?>
    
    <!-- Widget: Contribuie -->
    <div class="sidebar-widget">
        <div class="sidebar-widget-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <i class="fas fa-pen-nib me-2"></i>Scrie pentru noi
        </div>
        <div class="sidebar-widget-body text-center py-4">
            <p class="mb-3">Ai o opinie sau o analiză despre fotbal? Alătură-te echipei de contributori MatchDay.ro!</p>
            <a href="/contribute.php" class="btn btn-success">
                <i class="fas fa-paper-plane me-1"></i>Trimite Articol
            </a>
        </div>
    </div>
    
    <?php
}
