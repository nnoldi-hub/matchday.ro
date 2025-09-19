<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/seo.php');

$base = (BASE_URL ?: '');
$admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
// Pentru assets, folosește path relativ pentru a evita probleme cu HTTPS/HTTP
$assetBase = $admin ? '../' : '';

// Initialize SEO Manager
$seo = new SEOManager();

// Set default values if not already set by page
if (!isset($pageTitle)) $pageTitle = SITE_NAME;
if (!isset($pageDescription)) $pageDescription = SITE_TAGLINE;
if (!isset($pageKeywords)) $pageKeywords = ['fotbal', 'romania', 'sport', 'meciuri', 'transferuri'];
if (!isset($pageImage)) $pageImage = '/assets/images/logo.png';
if (!isset($pageType)) $pageType = 'website';

$seo->setTitle($pageTitle, $pageTitle !== SITE_NAME)
    ->setDescription($pageDescription)
    ->setKeywords($pageKeywords)
    ->setOgImage($pageImage)
    ->setArticleType($pageType);

// Add article-specific data if available
if (isset($publishedDate)) $seo->setPublishedTime($publishedDate);
if (isset($modifiedDate)) $seo->setModifiedTime($modifiedDate);
if (isset($articleAuthor)) $seo->setAuthor($articleAuthor);
if (isset($articleCategory)) $seo->setCategory($articleCategory);
if (isset($articleTags)) $seo->setTags($articleTags);
?>
<!doctype html>
<html lang="ro">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#71acbb">
    <meta name="msapplication-TileColor" content="#71acbb">
    
    <?php echo $seo->render(); ?>
    
    <!-- Bootstrap CSS Local -->
  <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
  <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
    
    <!-- RSS Feed -->
    <link rel="alternate" type="application/rss+xml" title="<?php echo SITE_NAME; ?> RSS" href="<?php echo $base ?>rss.php">
    
    <!-- Sitemap -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo $base ?>sitemap.php">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
  </head>
  <body class="bg-light"><?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
    <?php echo $seo->generateBreadcrumbs($breadcrumbs); ?>
  <?php endif; ?>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
      <div class="container">
  <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php">
          <img src="/assets/images/logo.png" width="36" height="36" alt="logo" />
          <span class="fw-bold"><?php echo SITE_NAME; ?></span>
          <span class="badge-accent ms-2 d-none d-lg-inline">Fiecare meci are o poveste. Noi o scriem. ⚽</span>        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
            <li class="nav-item"><a class="nav-link" href="/index.php">Jurnal</a></li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Categorii</a>
              <ul class="dropdown-menu">
                <?php
                $categories = require(__DIR__ . '/../config/categories.php');
                foreach ($categories as $key => $category):
                ?>
                <li>
                  <a class="dropdown-item d-flex align-items-center" href="<?php echo $admin ? '../category.php?cat=' . urlencode($key) : './category.php?cat=' . urlencode($key); ?>">
                    <i class="<?= $category['icon'] ?> me-2" style="color: <?= $category['color'] ?>"></i>
                    <?= htmlspecialchars($category['name']) ?>
                  </a>
                </li>
                <?php endforeach; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo $admin ? '../index.php' : './index.php'; ?>">Toate articolele</a></li>
              </ul>
            </li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? '../despre.php' : './despre.php'; ?>">Despre</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? '../contact.php' : './contact.php'; ?>">Contact</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? '../calendar-editorial.php' : './calendar-editorial.php'; ?>" title="Calendar Editorial"><i class="fas fa-calendar-alt me-1"></i>Program</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? '../rss.php' : './rss.php'; ?>" target="_blank" title="RSS Feed"><i class="fas fa-rss"></i></a></li>
            <?php if (isset($_SESSION['david_logged']) && $_SESSION['david_logged']): ?>
              <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? 'dashboard.php' : './admin/dashboard.php'; ?>">Dashboard</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="<?php echo $admin ? 'login.php' : './admin/login.php'; ?>">Scrie un articol</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
