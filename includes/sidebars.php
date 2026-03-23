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
