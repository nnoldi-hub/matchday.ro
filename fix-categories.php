<?php
/**
 * Script pentru verificare și corectare categorii articole
 * Folosire: 
 *   1. Acces direct pentru a vedea situația
 *   2. Acces ?action=auto-fix pentru auto-detectare și corectare
 *   3. Acces ?action=bulk-fix&to=champions-league pentru a schimba toate
 */

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/security.php';

// Verificare admin
session_start();
if (empty($_SESSION['david_logged'])) { 
    die('Trebuie să fii logat ca admin. <a href="/admin/login.php">Login</a>'); 
}

$action = $_GET['action'] ?? 'check';

// Funcție pentru auto-detectare categorie pe baza titlului
function detectCategory($title) {
    $title = mb_strtolower($title);
    
    // Champions League
    if (preg_match('/champions|ucl|liga campionilor|sfert|optimi|semifinal|final[aă]|grupe ucl/i', $title)) {
        return 'champions-league';
    }
    
    // Europa League / Conference
    if (preg_match('/europa league|conference|uel/i', $title)) {
        return 'competitii';
    }
    
    // Meciuri / Rezultate
    if (preg_match('/\d+-\d+|meci|rezultat|scor|gol|victori|înfrâng|egal/i', $title)) {
        return 'meciuri';
    }
    
    // Transferuri
    if (preg_match('/transfer|semn[ae]|contract|cump[aă]r|vinde|ofert[aă]|negocier/i', $title)) {
        return 'transferuri';
    }
    
    // Breaking / Știri urgente - merge în "competitii" sau "meciuri" 
    if (preg_match('/murit|deces|urgen[tț]|breaking|șoc|incredibil|oficial/i', $title)) {
        return 'competitii';
    }
    
    // Opinii
    if (preg_match('/opinie|p[aă]rere|de ce|ar trebui|cred c[aă]/i', $title)) {
        return 'opinii';
    }
    
    // Statistici / Clasamente
    if (preg_match('/clasament|marcator|statistic|top\s*\d+|record/i', $title)) {
        return 'statistici';
    }
    
    // Interviuri
    if (preg_match('/interviu|declar|a spus|a zis/i', $title)) {
        return 'interviuri';
    }
    
    // Ligi specifice
    if (preg_match('/premier league|anglia|chelsea|arsenal|liverpool|city|united/i', $title)) {
        return 'premier-league';
    }
    if (preg_match('/la liga|spania|real madrid|barcelona|atletico/i', $title)) {
        return 'la-liga';
    }
    if (preg_match('/serie a|italia|juventus|milan|inter|napoli|roma/i', $title)) {
        return 'serie-a';
    }
    if (preg_match('/bundesliga|germania|bayern|dortmund/i', $title)) {
        return 'bundesliga';
    }
    if (preg_match('/ligue 1|fran[tț]a|psg|paris|marseille|monaco/i', $title)) {
        return 'ligue-1';
    }
    
    // Default
    return 'meciuri';
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix Categories</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
tr:hover { background: #f9f9f9; }
.btn { padding: 8px 16px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
.alert-success { background: #d4edda; color: #155724; }
.alert-warning { background: #fff3cd; color: #856404; }
.alert-info { background: #cce5ff; color: #004085; }
.badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.badge-wrong { background: #dc3545; color: white; }
.badge-ok { background: #28a745; color: white; }
</style></head><body>";

echo "<h1>🔧 Corectare Categorii Articole</h1>";

// Lista categorii valide
$categories = Database::fetchAll("SELECT slug, name FROM categories ORDER BY name");
$catMap = [];
foreach ($categories as $cat) {
    $catMap[$cat['slug']] = $cat['name'];
}

// AUTO-FIX: Detectare automată și previzualizare
if ($action === 'auto-fix') {
    $posts = Database::fetchAll(
        "SELECT id, title, category_slug FROM posts WHERE category_slug = 'nb1-ungaria' OR category_slug IS NULL OR category_slug = ''"
    );
    
    if (empty($posts)) {
        echo "<div class='alert alert-success'>✅ Nu există articole de corectat!</div>";
    } else {
        echo "<h2>Previzualizare Auto-Detectare ({$_GET['action']})</h2>";
        echo "<div class='alert alert-info'>Se vor corecta " . count($posts) . " articole</div>";
        
        // Dacă e confirmat, aplică
        if (isset($_GET['confirm']) && $_GET['confirm'] === '1') {
            $fixed = 0;
            foreach ($posts as $post) {
                $newCat = detectCategory($post['title']);
                Database::execute(
                    "UPDATE posts SET category_slug = ? WHERE id = ?",
                    [$newCat, $post['id']]
                );
                $fixed++;
            }
            echo "<div class='alert alert-success'>✅ $fixed articole au fost corectate automat!</div>";
            echo "<p><a href='fix-categories.php' class='btn btn-primary'>Vezi Rezultat</a></p>";
        } else {
            // Previzualizare
            echo "<table><tr><th>ID</th><th>Titlu</th><th>Categorie Actuală</th><th>→</th><th>Categorie Detectată</th></tr>";
            foreach ($posts as $post) {
                $newCat = detectCategory($post['title']);
                $oldCatName = $catMap[$post['category_slug']] ?? $post['category_slug'];
                $newCatName = $catMap[$newCat] ?? $newCat;
                echo "<tr>";
                echo "<td>{$post['id']}</td>";
                echo "<td>" . htmlspecialchars(mb_substr($post['title'], 0, 50)) . "...</td>";
                echo "<td><span class='badge badge-wrong'>{$oldCatName}</span></td>";
                echo "<td>→</td>";
                echo "<td><span class='badge badge-ok'>{$newCatName}</span></td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><a href='fix-categories.php?action=auto-fix&confirm=1' class='btn btn-success' onclick=\"return confirm('Ești sigur? Această acțiune va modifica " . count($posts) . " articole.')\">✅ APLICĂ CORECTĂRILE</a></p>";
        }
    }
}

// BULK-FIX: Schimbă toate dintr-o categorie în alta
elseif ($action === 'bulk-fix') {
    $from = $_GET['from'] ?? 'nb1-ungaria';
    $to = $_GET['to'] ?? '';
    
    if (isset($_GET['confirm']) && $_GET['confirm'] === '1' && !empty($to)) {
        $affected = Database::execute(
            "UPDATE posts SET category_slug = ? WHERE category_slug = ?",
            [$to, $from]
        );
        echo "<div class='alert alert-success'>✅ $affected articole actualizate de la '$from' la '$to'</div>";
        echo "<p><a href='fix-categories.php' class='btn btn-primary'>Vezi Rezultat</a></p>";
    } else {
        echo "<h2>Schimbare în Masă</h2>";
        echo "<form method='GET'>";
        echo "<input type='hidden' name='action' value='bulk-fix'>";
        echo "<p>De la categoria: <select name='from'>";
        foreach ($catMap as $slug => $name) {
            $sel = ($slug === $from) ? 'selected' : '';
            echo "<option value='$slug' $sel>$name ($slug)</option>";
        }
        echo "</select></p>";
        echo "<p>La categoria: <select name='to'>";
        echo "<option value=''>-- Selectează --</option>";
        foreach ($catMap as $slug => $name) {
            echo "<option value='$slug'>$name ($slug)</option>";
        }
        echo "</select></p>";
        echo "<input type='hidden' name='confirm' value='1'>";
        echo "<button type='submit' class='btn btn-danger'>Schimbă Toate</button>";
        echo "</form>";
    }
}

// CHECK: Afișare status curent
else {
    // Toate articolele cu categoria lor
    $posts = Database::fetchAll(
        "SELECT p.id, p.title, p.category_slug, c.name as category_name 
         FROM posts p 
         LEFT JOIN categories c ON p.category_slug = c.slug 
         ORDER BY p.id DESC"
    );
    
    // Statistici pe categorii
    echo "<h2>📊 Distribuție pe categorii</h2>";
    $stats = Database::fetchAll(
        "SELECT category_slug, COUNT(*) as cnt 
         FROM posts 
         GROUP BY category_slug 
         ORDER BY cnt DESC"
    );
    echo "<table style='width: auto;'><tr><th>Categorie</th><th>Nr. Articole</th></tr>";
    foreach ($stats as $s) {
        $catName = $catMap[$s['category_slug']] ?? $s['category_slug'];
        $badge = ($s['category_slug'] === 'nb1-ungaria') ? 'badge-wrong' : 'badge-ok';
        echo "<tr><td><span class='badge $badge'>$catName</span></td><td>{$s['cnt']}</td></tr>";
    }
    echo "</table>";
    
    // Acțiuni rapide
    echo "<h2>🛠️ Acțiuni</h2>";
    echo "<p>";
    echo "<a href='fix-categories.php?action=auto-fix' class='btn btn-success'>🤖 Auto-Detectare & Corectare</a> ";
    echo "<a href='fix-categories.php?action=bulk-fix' class='btn btn-primary'>📦 Schimbare în Masă</a>";
    echo "</p>";
    
    // Lista articole
    echo "<h2>📝 Toate Articolele (" . count($posts) . ")</h2>";
    echo "<table><tr><th>ID</th><th>Titlu</th><th>Categorie</th><th>Auto-Detectată</th></tr>";
    foreach ($posts as $post) {
        $detected = detectCategory($post['title']);
        $detectedName = $catMap[$detected] ?? $detected;
        $isWrong = ($post['category_slug'] !== $detected);
        $badge = $isWrong ? 'badge-wrong' : 'badge-ok';
        
        echo "<tr>";
        echo "<td>{$post['id']}</td>";
        echo "<td>" . htmlspecialchars(mb_substr($post['title'], 0, 60)) . "</td>";
        echo "<td><span class='badge $badge'>" . ($post['category_name'] ?? $post['category_slug']) . "</span></td>";
        echo "<td>" . ($isWrong ? "→ <strong>$detectedName</strong>" : "✓") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</body></html>";
