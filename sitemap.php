<?php
header("Content-Type: application/xml; charset=utf-8");
require_once 'config/config.php';

// Generează URL-ul complet pentru site
function generateSiteUrl($path = '') {
    $protocol = 'https';
    $domain = 'matchday.ro';
    return $protocol . '://' . $domain . '/' . ltrim($path, '/');
}

// Generează data în format ISO pentru sitemap
function formatDateForSitemap($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('c') : date('c');
}

// Scanează directorul posts pentru articole
function getArticles() {
    $articles = [];
    $postsDir = __DIR__ . '/posts/';
    
    if (is_dir($postsDir)) {
        $files = glob($postsDir . '*.html');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $content = file_get_contents($file);
            
            // Extrage meta-datele din comentariul david-meta
            if (preg_match('/<!-- david-meta:\s*({.*?})\s*-->/', $content, $matches)) {
                $meta = json_decode($matches[1], true);
                if ($meta) {
                    $articles[] = [
                        'url' => generateSiteUrl('posts/' . $filename),
                        'title' => $meta['title'] ?? 'Articol MatchDay.ro',
                        'date' => $meta['date'] ?? date('Y-m-d'),
                        'category' => $meta['category'] ?? 'general',
                        'tags' => $meta['tags'] ?? [],
                        'lastmod' => date('c', filemtime($file)),
                        'changefreq' => 'monthly',
                        'priority' => '0.7'
                    ];
                }
            }
        }
    }
    
    // Sortează articolele după dată (cel mai recent primul)
    usort($articles, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $articles;
}

// Categorii disponibile
$categories = [
    'opinii' => ['name' => 'Opinii', 'priority' => '0.8'],
    'analize' => ['name' => 'Analize', 'priority' => '0.8'],
    'interviuri' => ['name' => 'Interviuri', 'priority' => '0.7'],
    'reportaje' => ['name' => 'Reportaje', 'priority' => '0.7'],
    'transfer' => ['name' => 'Transfer', 'priority' => '0.9'],
    'nacional' => ['name' => 'Fotbal Național', 'priority' => '0.8'],
    'international' => ['name' => 'Fotbal Internațional', 'priority' => '0.7']
];

// Obține toate articolele
$articles = getArticles();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Homepage -->
    <url>
        <loc><?= generateSiteUrl() ?></loc>
        <lastmod><?= date('c') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
        <mobile:mobile/>
    </url>

    <!-- Pagina Despre -->
    <url>
        <loc><?= generateSiteUrl('despre.php') ?></loc>
        <lastmod><?= date('c', filemtime(__DIR__ . '/despre.php')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
        <mobile:mobile/>
    </url>

    <!-- Pagina Contact -->
    <url>
        <loc><?= generateSiteUrl('contact.php') ?></loc>
        <lastmod><?= date('c', filemtime(__DIR__ . '/contact.php')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
        <mobile:mobile/>
    </url>

    <!-- Calendar Editorial -->
    <url>
        <loc><?= generateSiteUrl('calendar-editorial.php') ?></loc>
        <lastmod><?= date('c', filemtime(__DIR__ . '/calendar-editorial.php')) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <mobile:mobile/>
    </url>

    <!-- Categorii -->
    <?php foreach ($categories as $slug => $info): ?>
    <url>
        <loc><?= generateSiteUrl('category.php?cat=' . urlencode($slug)) ?></loc>
        <lastmod><?= date('c') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority><?= $info['priority'] ?></priority>
        <mobile:mobile/>
    </url>
    <?php endforeach; ?>

    <!-- Articole -->
    <?php foreach ($articles as $article): ?>
    <url>
        <loc><?= htmlspecialchars($article['url']) ?></loc>
        <lastmod><?= $article['lastmod'] ?></lastmod>
        <changefreq><?= $article['changefreq'] ?></changefreq>
        <priority><?= $article['priority'] ?></priority>
        <mobile:mobile/>
        
        <!-- News Sitemap pentru articole recente (ultimele 2 zile) -->
        <?php if (strtotime($article['date']) > strtotime('-2 days')): ?>
        <news:news>
            <news:publication>
                <news:name>MatchDay.ro</news:name>
                <news:language>ro</news:language>
            </news:publication>
            <news:publication_date><?= formatDateForSitemap($article['date']) ?></news:publication_date>
            <news:title><?= htmlspecialchars($article['title']) ?></news:title>
            <news:keywords><?= htmlspecialchars(implode(', ', $article['tags'])) ?></news:keywords>
        </news:news>
        <?php endif; ?>
        
        <!-- Adaugă imaginea logo pentru fiecare articol -->
        <image:image>
            <image:loc><?= generateSiteUrl('assets/images/logo.png') ?></image:loc>
            <image:title><?= htmlspecialchars($article['title']) ?></image:title>
            <image:caption>MatchDay.ro - <?= htmlspecialchars($article['title']) ?></image:caption>
        </image:image>
    </url>
    <?php endforeach; ?>

</urlset>
