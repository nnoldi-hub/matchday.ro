<?php
/**
 * Insert article into database
 * Run once to add the meme article
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/cache.php';

// Article data
$article = [
    'title' => 'Meme fotbal - cele mai amuzante momente ale saptamanii viral pe internet',
    'slug' => 'meme-fotbal-cele-mai-amuzante-momente',
    'excerpt' => 'Saptamana aceasta (16-20 martie 2026) a fost plina de faze comice din Champions League, Premier League si Liga 1. Cele mai amuzante meme-uri!',
    'content' => '<p class="lead"><strong>Saptamana aceasta (16-20 martie 2026)</strong> a fost plina de faze comice, in special datorita returului optimilor din UEFA Champions League si a unor momente neasteptate din ligile interne.</p>

<p>Iata o selectie a celor mai amuzante meme-uri si momente care au facut deliciul fanilor:</p>

<h2>🏆 Champions League: Manchester City vs. Real Madrid</h2>

<p>Duelul de pe 17 martie a generat cele mai multe reactii, dupa ce cele doua echipe s-au intalnit din nou in fazele eliminatorii.</p>

<ul>
<li><strong>"Deja Vu Infinite"</strong> - Internetul a fost inundat de meme-uri cu fanii care par sa traiasca in "Groundhog Day", avand impresia ca City si Real sunt blocate intr-un ciclu etern al confruntarilor directe.</li>
<li><strong>"Meme-urile devin realitate"</strong> - Pe Instagram, clipurile virale au glumit pe seama modului in care previziunile fanilor de acum cativa ani au devenit scenarii reale pe teren.</li>
</ul>

<h2>🎮 Neymar si Simularea Virtuala</h2>

<p>Un moment hilar care a circulat intens pe retelele sociale l-a avut in prim-plan pe Neymar in timpul unui stream de eFootball.</p>

<ul>
<li><strong>Cartonas rosu pentru "Dive"</strong> - In timpul unui meci virtual Argentina vs. Brazilia, Neymar a incercat sa obtina un penalty prin simulare, dar arbitrul din joc nu s-a lasat pacalit si i-a aratat direct rosu.</li>
</ul>

<p>Reactiile fanilor au fost savuroase: <em>"Nici macar in joc nu-si poate stapani instinctele!"</em></p>

<h2>🏴 Premier League &amp; Transferuri</h2>

<ul>
<li><strong>Casemiro si Loialitatea</strong> - O imagine cu Casemiro strangand emblema lui Manchester United dupa un gol a generat postari ironice de tipul "Inca un an, Casemiro...", facand aluzie la forma oscilanta a echipei.</li>
<li><strong>Probleme vechi in 2026</strong> - Meme-urile cu fanii United care intra in primavara lui 2026 cu "aceleasi probleme ca in sezoanele trecute" au devenit virale, subliniind frustrarea combinata cu umorul suporterilor.</li>
</ul>

<h2>🇷🇴 Fotbalul Romanesc (Liga 1)</h2>

<p>Desi este pauza pentru meciurile internationale, paginile de specialitate precum <strong>Liga 1 RO Memes</strong> au continuat sa taxeze declaratiile patronilor din SuperLiga, transformand orice interviu in material de "brain rot" pentru fani.</p>

<h2>📱 Tendinte pe Social Media</h2>

<ul>
<li><strong>"Great Meme Reset"</strong> - Pe TikTok si Twitter, saptamana aceasta a fost marcata de un val de postari care readuc in prim-plan stilul meme-urilor din anii 2010 (Trollface, Harambe), aplicate pe greselile de portar sau ratarile monumentale din martie 2026.</li>
<li><strong>March Madness</strong> - Desi termenul apartine baschetului, fanii fotbalului l-au adoptat pentru a descrie haosul rezultatelor surprinzatoare din aceasta perioada.</li>
</ul>

<hr>

<p><em>Care e meme-ul tau preferat din saptamana aceasta? Spune-ne in comentarii!</em></p>',
    'category_slug' => 'champions-league',
    'tags' => 'meme,viral,champions-league,funny',
    'cover_image' => '',
    'author' => 'David Nyikora',
    'status' => 'published',
    'published_at' => '2026-03-20 10:00:00'
];

try {
    $db = Database::getInstance();
    
    // Check if article already exists
    $existing = Database::fetchOne("SELECT id FROM posts WHERE slug = :slug", ['slug' => $article['slug']]);
    
    if ($existing) {
        echo "Articolul exista deja cu ID: " . $existing['id'] . "\n";
    } else {
        // Insert article
        $sql = "INSERT INTO posts (title, slug, excerpt, content, category_slug, tags, cover_image, author, status, published_at, created_at, updated_at) 
                VALUES (:title, :slug, :excerpt, :content, :category_slug, :tags, :cover_image, :author, :status, :published_at, NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $article['title'],
            'slug' => $article['slug'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'category_slug' => $article['category_slug'],
            'tags' => $article['tags'],
            'cover_image' => $article['cover_image'],
            'author' => $article['author'],
            'status' => $article['status'],
            'published_at' => $article['published_at']
        ]);
        
        $id = $db->lastInsertId();
        echo "Articol inserat cu succes! ID: $id\n";
    }
    
    // Clear cache
    Cache::clear();
    echo "Cache sters!\n";
    
} catch (Exception $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
}
