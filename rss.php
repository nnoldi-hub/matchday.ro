<?php
// RSS Feed generator
require_once(__DIR__ . '/config/config.php');

header('Content-Type: application/rss+xml; charset=utf-8');

$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$postsDir = __DIR__ . '/posts';

// Get recent posts
$files = array_values(array_filter(scandir($postsDir), fn($f) => substr($f, -5) === '.html'));
$items = [];

foreach (array_slice($files, 0, 20) as $file) {
    $path = $postsDir . '/' . $file;
    $html = file_get_contents($path);
    
    $meta = [
        'title' => pathinfo($file, PATHINFO_FILENAME),
        'date' => date('Y-m-d', filemtime($path)),
        'excerpt' => '',
        'cover' => '',
        'tags' => []
    ];
    
    if (preg_match('/<!--\s*david-meta:(.*?)-->/', $html, $m)) {
        $j = json_decode(trim($m[1]), true);
        if (is_array($j)) $meta = array_merge($meta, $j);
    }
    
    $meta['file'] = 'posts/' . $file;
    $meta['url'] = $baseUrl . '/posts/' . rawurlencode($file);
    $items[] = $meta;
}

// Sort by date
usort($items, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo htmlspecialchars(SITE_NAME, ENT_XML1); ?></title>
    <link><?php echo htmlspecialchars($baseUrl, ENT_XML1); ?></link>
    <description><?php echo htmlspecialchars(SITE_TAGLINE, ENT_XML1); ?></description>
    <language>ro</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
    <atom:link href="<?php echo htmlspecialchars($baseUrl . '/rss.php', ENT_XML1); ?>" rel="self" type="application/rss+xml" />
    
    <?php foreach ($items as $item): ?>
    <item>
      <title><?php echo htmlspecialchars($item['title'], ENT_XML1); ?></title>
      <link><?php echo htmlspecialchars($item['url'], ENT_XML1); ?></link>
      <description><?php echo htmlspecialchars($item['excerpt'], ENT_XML1); ?></description>
      <pubDate><?php echo date(DATE_RSS, strtotime($item['date'])); ?></pubDate>
      <guid><?php echo htmlspecialchars($item['url'], ENT_XML1); ?></guid>
      <?php if (!empty($item['tags'])): ?>
        <?php foreach ($item['tags'] as $tag): ?>
        <category><?php echo htmlspecialchars($tag, ENT_XML1); ?></category>
        <?php endforeach; ?>
      <?php endif; ?>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
