<?php
/**
 * MatchDay.ro - Script Diagnostic Tehnic
 * Verifică: categorii, posturi, meciuri, rute, relații DB
 */
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Category.php');
require_once(__DIR__ . '/../includes/Post.php');
require_once(__DIR__ . '/../includes/LiveScores.php');

// Admin check
if (empty($_SESSION['david_logged']) && !defined('CLI_MODE')) { 
    header('Location: login.php'); 
    exit; 
}

$diagnostics = [];
$errors = [];
$warnings = [];
$success = [];

// ============================================
// 1. DATABASE CONNECTION TEST
// ============================================
try {
    $db = Database::getInstance();
    $success[] = "✅ Conexiune la baza de date OK";
} catch (Exception $e) {
    $errors[] = "❌ Eroare conexiune DB: " . $e->getMessage();
}

// ============================================
// 2. CATEGORIES DIAGNOSTIC
// ============================================
echo "<h2>🔍 1. Verificare Categorii</h2>";

// Categories from config file
$configCategories = [];
$configFile = __DIR__ . '/../config/categories.php';
if (file_exists($configFile)) {
    $configCategories = require($configFile);
    $diagnostics['config_categories_count'] = count($configCategories);
} else {
    $errors[] = "❌ Fișierul config/categories.php nu există!";
}

// Categories from database
try {
    $dbCategories = Category::getAll();
    $diagnostics['db_categories_count'] = count($dbCategories);
    
    if (count($dbCategories) === 0) {
        $warnings[] = "⚠️ Baza de date nu conține nicio categorie! Categoriile din admin nu vor apărea în formularul de articol.";
    }
} catch (Exception $e) {
    $errors[] = "❌ Eroare la citirea categoriilor din DB: " . $e->getMessage();
    $dbCategories = [];
}

// Compare config vs DB
$configSlugs = array_keys($configCategories);
$dbSlugs = array_column($dbCategories, 'slug');

$missingInDb = array_diff($configSlugs, $dbSlugs);
$missingInConfig = array_diff($dbSlugs, $configSlugs);

if (!empty($missingInDb)) {
    $warnings[] = "⚠️ Categorii din config care LIPSESC din DB: " . implode(', ', $missingInDb);
    $warnings[] = "→ Rulează 'Sincronizează Categorii' de mai jos pentru a le importa.";
}

if (!empty($missingInConfig)) {
    $success[] = "✅ Categorii create în admin (doar în DB): " . implode(', ', $missingInConfig);
    $success[] = "→ Acestea vor apărea normal în formularul de articol (formularele citesc din DB).";
}

// ============================================
// 3. POSTS TABLE SCHEMA
// ============================================
echo "<h2>🔍 2. Verificare Schema Tabel Posts</h2>";

try {
    // Check if match-related columns exist
    $isMySQL = defined('USE_MYSQL') && USE_MYSQL;
    
    if ($isMySQL) {
        $columns = Database::fetchAll("SHOW COLUMNS FROM posts");
        $columnNames = array_column($columns, 'Field');
    } else {
        $tableInfo = Database::fetchAll("PRAGMA table_info(posts)");
        $columnNames = array_column($tableInfo, 'name');
    }
    
    $requiredColumns = [
        'is_match_result', 
        'home_team', 
        'away_team', 
        'home_score', 
        'away_score', 
        'match_competition'
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columnNames)) {
            $missingColumns[] = $col;
        }
    }
    
    if (!empty($missingColumns)) {
        $errors[] = "❌ Coloane lipsă în tabelul posts: " . implode(', ', $missingColumns);
        $diagnostics['posts_schema_complete'] = false;
    } else {
        $success[] = "✅ Schema tabelului posts este completă (include câmpuri pentru meciuri)";
        $diagnostics['posts_schema_complete'] = true;
    }
    
    $diagnostics['posts_columns'] = $columnNames;
    
} catch (Exception $e) {
    $errors[] = "❌ Eroare la verificarea schemei: " . $e->getMessage();
}

// ============================================
// 4. LIVE_MATCHES TABLE CHECK
// ============================================
echo "<h2>🔍 3. Verificare Tabel Live Matches</h2>";

try {
    if ($isMySQL) {
        $tables = Database::fetchAll("SHOW TABLES LIKE 'live_matches'");
        $tableExists = !empty($tables);
    } else {
        $result = Database::fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='live_matches'");
        $tableExists = !empty($result);
    }
    
    if ($tableExists) {
        $matchCount = Database::fetchValue("SELECT COUNT(*) FROM live_matches");
        $success[] = "✅ Tabelul live_matches există ($matchCount meciuri)";
        $diagnostics['live_matches_count'] = $matchCount;
    } else {
        $warnings[] = "⚠️ Tabelul live_matches nu există! Rulează migrate-phase5.php";
        $diagnostics['live_matches_exists'] = false;
    }
} catch (Exception $e) {
    $errors[] = "❌ Eroare live_matches: " . $e->getMessage();
}

// ============================================
// 5. POSTS WITH CATEGORIES
// ============================================
echo "<h2>🔍 4. Verificare Posturi cu Categorii</h2>";

try {
    // Posts without valid category
    $orphanedPosts = Database::fetchAll(
        "SELECT p.id, p.title, p.category_slug 
         FROM posts p 
         LEFT JOIN categories c ON p.category_slug = c.slug 
         WHERE c.id IS NULL AND p.category_slug IS NOT NULL AND p.category_slug != ''"
    );
    
    if (!empty($orphanedPosts)) {
        $warnings[] = "⚠️ " . count($orphanedPosts) . " articole au categorii care nu există în DB:";
        foreach ($orphanedPosts as $p) {
            $warnings[] = "   - ID {$p['id']}: \"{$p['title']}\" (categoria: {$p['category_slug']})";
        }
    } else {
        $success[] = "✅ Toate articolele au categorii valide";
    }
    
    // Count posts per category
    $categoryCounts = Database::fetchAll(
        "SELECT category_slug, COUNT(*) as cnt FROM posts WHERE category_slug IS NOT NULL GROUP BY category_slug ORDER BY cnt DESC"
    );
    $diagnostics['posts_per_category'] = $categoryCounts;
    
} catch (Exception $e) {
    $errors[] = "❌ Eroare verificare posturi: " . $e->getMessage();
}

// ============================================
// 6. FORM FIELD VALIDATION
// ============================================
echo "<h2>🔍 5. Verificare Câmpuri Formular</h2>";

// Check if forms use config or DB
$formFiles = [
    'new-post.php' => __DIR__ . '/new-post.php',
    'edit-post.php' => __DIR__ . '/edit-post.php',
    'save-post.php' => __DIR__ . '/save-post.php'
];

foreach ($formFiles as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        if (strpos($content, "require(__DIR__ . '/../config/categories.php')") !== false ||
            strpos($content, 'require(__DIR__ . "/../config/categories.php")') !== false) {
            $warnings[] = "⚠️ $name citește categorii din config/categories.php (fișier static), nu din DB!";
        }
        
        if (strpos($content, 'Category::getAll()') !== false) {
            $success[] = "✅ $name folosește Category::getAll() (DB)";
        }
    }
}

// ============================================
// 7. ROUTE CHECKS
// ============================================
echo "<h2>🔍 6. Verificare Rute</h2>";

$routes = [
    '/admin/new-post.php' => __DIR__ . '/new-post.php',
    '/admin/save-post.php' => __DIR__ . '/save-post.php',
    '/admin/edit-post.php' => __DIR__ . '/edit-post.php',
    '/admin/categories.php' => __DIR__ . '/categories.php',
    '/admin/livescores.php' => __DIR__ . '/livescores.php',
    '/admin/posts.php' => __DIR__ . '/posts.php'
];

foreach ($routes as $route => $file) {
    if (file_exists($file)) {
        $success[] = "✅ Ruta $route există";
    } else {
        $errors[] = "❌ Ruta $route LIPSEȘTE!";
    }
}

// ============================================
// DISPLAY RESULTS
// ============================================
$pageTitle = 'Diagnostic Tehnic';
require_once(__DIR__ . '/admin-header.php');
?>

<div class="admin-page-header">
    <h1><i class="fas fa-stethoscope me-2"></i>Diagnostic Tehnic</h1>
    <a href="diagnostic.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-sync me-1"></i>Reîncarcă
    </a>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h1 class="text-success"><?= count($success) ?></h1>
                <div>Verificări OK</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h1 class="text-warning"><?= count($warnings) ?></h1>
                <div>Avertismente</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h1 class="text-danger"><?= count($errors) ?></h1>
                <div>Erori</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="admin-card mb-4 border-danger">
    <div class="admin-card-header bg-danger text-white">
        <strong><i class="fas fa-times-circle me-2"></i>Erori (necesită corecție)</strong>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
<div class="admin-card mb-4 border-warning">
    <div class="admin-card-header bg-warning">
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Avertismente</strong>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <?php foreach ($warnings as $warn): ?>
            <li><?= $warn ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="admin-card mb-4 border-success">
    <div class="admin-card-header bg-success text-white">
        <strong><i class="fas fa-check-circle me-2"></i>Verificări OK</strong>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <?php foreach ($success as $s): ?>
            <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- Statistics -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <strong><i class="fas fa-chart-bar me-2"></i>Statistici</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Categorii</h5>
                <table class="table table-sm">
                    <tr>
                        <td>Categorii în config/categories.php:</td>
                        <td><strong><?= $diagnostics['config_categories_count'] ?? 0 ?></strong></td>
                    </tr>
                    <tr>
                        <td>Categorii în baza de date:</td>
                        <td><strong><?= $diagnostics['db_categories_count'] ?? 0 ?></strong></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Live Matches</h5>
                <table class="table table-sm">
                    <tr>
                        <td>Total meciuri:</td>
                        <td><strong><?= $diagnostics['live_matches_count'] ?? 'N/A' ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($diagnostics['posts_per_category'])): ?>
        <h5 class="mt-3">Posturi per Categorie</h5>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>Categorie</th><th>Nr. Articole</th></tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['posts_per_category'] as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['category_slug'] ?? 'Fără categorie') ?></td>
                    <td><?= $cat['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Fix Actions -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <strong><i class="fas fa-wrench me-2"></i>Acțiuni de Reparare</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <form method="post" action="diagnostic.php">
                    <input type="hidden" name="action" value="sync_categories">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sync me-2"></i>Sincronizează Categorii din Config în DB
                    </button>
                </form>
                <small class="text-muted">Importă categoriile din fișierul config în baza de date.</small>
            </div>
            <div class="col-md-4">
                <form method="post" action="diagnostic.php">
                    <input type="hidden" name="action" value="update_config">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-file-export me-2"></i>Exportă Categorii din DB în Config
                    </button>
                </form>
                <small class="text-muted">Actualizează config/categories.php cu categoriile din DB.</small>
            </div>
            <div class="col-md-4">
                <form method="post" action="diagnostic.php">
                    <input type="hidden" name="action" value="add_match_columns">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-database me-2"></i>Adaugă Coloane Meciuri în Posts
                    </button>
                </form>
                <small class="text-muted">Adaugă câmpuri pentru rezultate meciuri în tabelul posts.</small>
            </div>
        </div>
    </div>
</div>

<?php
// Handle fix actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (Security::validateCSRFToken($token)) {
        try {
            switch ($_POST['action']) {
                case 'sync_categories':
                    $count = Category::syncFromConfig();
                    echo "<div class='alert alert-success mt-3'>✅ $count categorii importate din config!</div>";
                    break;
                    
                case 'update_config':
                    // Export DB categories to config file
                    $dbCats = Category::getAll();
                    $export = "<?php\n// Categories configuration for MatchDay.ro\n// Auto-generated on " . date('Y-m-d H:i:s') . "\nreturn [\n";
                    
                    foreach ($dbCats as $cat) {
                        $export .= "    '" . addslashes($cat['slug']) . "' => [\n";
                        $export .= "        'name' => '" . addslashes($cat['name']) . "',\n";
                        $export .= "        'description' => '" . addslashes($cat['description'] ?? '') . "',\n";
                        $export .= "        'color' => '" . ($cat['color'] ?? '#007bff') . "',\n";
                        $export .= "        'icon' => '" . ($cat['icon'] ?? 'fas fa-folder') . "'\n";
                        $export .= "    ],\n";
                    }
                    
                    $export .= "];\n";
                    
                    file_put_contents(__DIR__ . '/../config/categories.php', $export);
                    echo "<div class='alert alert-success mt-3'>✅ Config actualizat cu " . count($dbCats) . " categorii din DB!</div>";
                    break;
                    
                case 'add_match_columns':
                    $isMySQL = defined('USE_MYSQL') && USE_MYSQL;
                    $added = [];
                    
                    $columnsToAdd = [
                        'is_match_result' => 'TINYINT DEFAULT 0',
                        'home_team' => 'VARCHAR(100)',
                        'away_team' => 'VARCHAR(100)',
                        'home_score' => 'INT DEFAULT NULL',
                        'away_score' => 'INT DEFAULT NULL',
                        'match_competition' => 'VARCHAR(200)'
                    ];
                    
                    foreach ($columnsToAdd as $col => $type) {
                        try {
                            if ($isMySQL) {
                                Database::execute("ALTER TABLE posts ADD COLUMN $col $type");
                            } else {
                                Database::execute("ALTER TABLE posts ADD COLUMN $col " . str_replace('TINYINT', 'INTEGER', $type));
                            }
                            $added[] = $col;
                        } catch (Exception $e) {
                            // Column might already exist
                        }
                    }
                    
                    if (!empty($added)) {
                        echo "<div class='alert alert-success mt-3'>✅ Coloane adăugate: " . implode(', ', $added) . "</div>";
                    } else {
                        echo "<div class='alert alert-info mt-3'>ℹ️ Toate coloanele existau deja.</div>";
                    }
                    break;
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger mt-3'>❌ Eroare: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
