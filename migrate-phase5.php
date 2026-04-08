<?php
/**
 * Phase 5 Migration Script
 * MatchDay.ro - Live Scores & Submissions
 * 
 * Creates tables:
 * - live_matches: Manual live score entries
 * - submissions: External article contributions
 */

require_once(__DIR__ . '/config/database.php');

echo "<pre>\n";
echo "===========================================\n";
echo "MatchDay.ro - Migrare Faza 5\n";
echo "===========================================\n\n";

$errors = [];
$success = [];

// Detect database type
$isMySQL = defined('USE_MYSQL') && USE_MYSQL;

// Create live_matches table
echo "📊 Creare tabel live_matches...\n";
try {
    if ($isMySQL) {
        Database::execute("
            CREATE TABLE IF NOT EXISTS live_matches (
                id INT PRIMARY KEY AUTO_INCREMENT,
                competition VARCHAR(100) NOT NULL DEFAULT '',
                home_team VARCHAR(100) NOT NULL,
                away_team VARCHAR(100) NOT NULL,
                home_score INT DEFAULT 0,
                away_score INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'scheduled',
                minute INT DEFAULT NULL,
                kickoff DATETIME NOT NULL,
                home_scorers TEXT DEFAULT NULL,
                away_scorers TEXT DEFAULT NULL,
                venue VARCHAR(100) DEFAULT NULL,
                external_id VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        Database::execute("
            CREATE TABLE IF NOT EXISTS live_matches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                competition VARCHAR(100) NOT NULL DEFAULT '',
                home_team VARCHAR(100) NOT NULL,
                away_team VARCHAR(100) NOT NULL,
                home_score INTEGER DEFAULT 0,
                away_score INTEGER DEFAULT 0,
                status VARCHAR(20) DEFAULT 'scheduled',
                minute INTEGER DEFAULT NULL,
                kickoff DATETIME NOT NULL,
                home_scorers TEXT DEFAULT NULL,
                away_scorers TEXT DEFAULT NULL,
                venue VARCHAR(100) DEFAULT NULL,
                external_id VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    $success[] = "Tabel live_matches creat";
    echo "   ✓ Tabel creat cu succes\n";
} catch (Exception $e) {
    $errors[] = "live_matches: " . $e->getMessage();
    echo "   ✗ Eroare: " . $e->getMessage() . "\n";
}

// Create index for live_matches
echo "   Creare index kickoff...\n";
try {
    if ($isMySQL) {
        Database::execute("CREATE INDEX idx_live_matches_kickoff ON live_matches(kickoff)");
    } else {
        Database::execute("CREATE INDEX IF NOT EXISTS idx_live_matches_kickoff ON live_matches(kickoff)");
    }
    echo "   ✓ Index creat\n";
} catch (Exception $e) {
    echo "   ⚠ Index poate exista deja\n";
}

// Create submissions table
echo "\n📝 Creare tabel submissions...\n";
try {
    if ($isMySQL) {
        Database::execute("
            CREATE TABLE IF NOT EXISTS submissions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                excerpt TEXT DEFAULT NULL,
                content TEXT NOT NULL,
                category_id INT DEFAULT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(255) NOT NULL,
                author_bio TEXT DEFAULT NULL,
                featured_image VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                token VARCHAR(64) NOT NULL,
                reviewer_id INT DEFAULT NULL,
                reviewer_feedback TEXT DEFAULT NULL,
                reviewed_at DATETIME DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY (token),
                KEY idx_submissions_status (status),
                KEY idx_submissions_email (author_email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        Database::execute("
            CREATE TABLE IF NOT EXISTS submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                excerpt TEXT DEFAULT NULL,
                content TEXT NOT NULL,
                category_id INTEGER DEFAULT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(255) NOT NULL,
                author_bio TEXT DEFAULT NULL,
                featured_image VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                token VARCHAR(64) UNIQUE NOT NULL,
                reviewer_id INTEGER DEFAULT NULL,
                reviewer_feedback TEXT DEFAULT NULL,
                reviewed_at DATETIME DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (reviewer_id) REFERENCES users(id)
            )
        ");
    }
    $success[] = "Tabel submissions creat";
    echo "   ✓ Tabel creat cu succes\n";
} catch (Exception $e) {
    $errors[] = "submissions: " . $e->getMessage();
    echo "   ✗ Eroare: " . $e->getMessage() . "\n";
}

// Create indexes for submissions (only for SQLite, MySQL has them in table definition)
echo "   Creare indexuri...\n";
try {
    if (!$isMySQL) {
        Database::execute("CREATE INDEX IF NOT EXISTS idx_submissions_status ON submissions(status)");
        Database::execute("CREATE INDEX IF NOT EXISTS idx_submissions_token ON submissions(token)");
        Database::execute("CREATE INDEX IF NOT EXISTS idx_submissions_email ON submissions(author_email)");
    }
    echo "   ✓ Indexuri create\n";
} catch (Exception $e) {
    echo "   ⚠ Unele indexuri pot exista deja\n";
}

// Create submissions upload directory
echo "\n📁 Creare folder uploads/submissions...\n";
$uploadsDir = __DIR__ . '/assets/uploads/submissions';
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "   ✓ Folder creat: $uploadsDir\n";
        $success[] = "Folder submissions creat";
    } else {
        echo "   ✗ Nu s-a putut crea folderul\n";
        $errors[] = "Nu s-a putut crea folderul submissions";
    }
} else {
    echo "   ⚠ Folder există deja\n";
}

// Create livescores config template
echo "\n⚙️ Creare template configurare API scoruri...\n";
$configContent = <<<'PHP'
<?php
/**
 * Live Scores API Configuration
 * 
 * Options:
 * - provider: 'manual', 'api-football', 'football-data'
 * - api_key: Your API key (required for external providers)
 * 
 * API-Football: https://www.api-football.com/ (~$10-50/month)
 * Football-Data: https://www.football-data.org/ (Free tier available)
 */

return [
    'provider' => 'manual', // Change to 'api-football' or 'football-data' with valid API key
    'api_key' => '', // Your API key here
    
    // Cache settings
    'cache_minutes' => 1, // How long to cache live scores
    
    // Display settings
    'show_competitions' => [
        'liga1',      // Liga 1 Romania
        'champions',  // Champions League
        'europa',     // Europa League
    ]
];
PHP;

$configPath = __DIR__ . '/config/livescores_config.php.example';
if (file_put_contents($configPath, $configContent)) {
    echo "   ✓ Template creat: livescores_config.php.example\n";
    $success[] = "Template configurare API creat";
} else {
    echo "   ✗ Nu s-a putut crea template-ul\n";
}

// Summary
echo "\n===========================================\n";
echo "REZUMAT MIGRARE\n";
echo "===========================================\n";

if (count($success) > 0) {
    echo "\n✅ Succes (" . count($success) . "):\n";
    foreach ($success as $s) {
        echo "   • $s\n";
    }
}

if (count($errors) > 0) {
    echo "\n❌ Erori (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "   • $e\n";
    }
}

echo "\n===========================================\n";
if (count($errors) === 0) {
    echo "🎉 Migrarea Faza 5 s-a finalizat cu succes!\n";
} else {
    echo "⚠️ Migrarea s-a finalizat cu unele erori.\n";
}
echo "===========================================\n";

echo "\n📋 Pași următori:\n";
echo "   1. Verifică funcționalitatea la /contribute.php\n";
echo "   2. Accesează /admin/submissions.php pentru a gestiona contribuțiile\n";
echo "   3. Accesează /admin/livescores.php pentru scoruri live manuale\n";
echo "   4. Pentru API extern, configurează /config/livescores_config.php\n";

echo "\n</pre>";
