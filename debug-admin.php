<?php
/**
 * Debug Admin Pages - Afișează erorile detaliate
 * ȘTERGE ACEST FIȘIER DUPĂ REZOLVARE!
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>\n";
echo "=== DEBUG ADMIN PAGES ===\n\n";

$page = $_GET['page'] ?? 'newsletter';

// Test 1: Session
echo "[1] Session test...\n";
try {
    session_start();
    echo "    ✓ Session started\n";
    echo "    david_logged: " . (isset($_SESSION['david_logged']) ? 'YES' : 'NO') . "\n";
    echo "    Session data: " . print_r($_SESSION, true) . "\n";
} catch (Throwable $e) {
    echo "    ✗ Session ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Config
echo "\n[2] Loading config...\n";
try {
    require_once(__DIR__ . '/config/config.php');
    echo "    ✓ Config loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Config ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 3: Database
echo "\n[3] Loading database...\n";
try {
    require_once(__DIR__ . '/config/database.php');
    $db = Database::getInstance();
    echo "    ✓ Database OK\n";
} catch (Throwable $e) {
    echo "    ✗ Database ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

// Test 4: Security
echo "\n[4] Loading security...\n";
try {
    require_once(__DIR__ . '/config/security.php');
    echo "    ✓ Security loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Security ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

if ($page === 'newsletter') {
    // Test 5: Newsletter class
    echo "\n[5] Loading Newsletter.php...\n";
    try {
        require_once(__DIR__ . '/includes/Newsletter.php');
        echo "    ✓ Newsletter.php loaded OK\n";
    } catch (Throwable $e) {
        echo "    ✗ Newsletter ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }

    // Test 6: Create tables
    echo "\n[6] Testing Newsletter::createTables()...\n";
    try {
        Newsletter::createTables();
        echo "    ✓ Tables created/verified OK\n";
    } catch (Throwable $e) {
        echo "    ✗ createTables ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }

    // Test 7: Get subscribers
    echo "\n[7] Testing Newsletter::getSubscribers()...\n";
    try {
        $subs = Newsletter::getSubscribers();
        echo "    ✓ Subscribers: " . count($subs['subscribers']) . " found\n";
    } catch (Throwable $e) {
        echo "    ✗ getSubscribers ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }
} else {
    // Test 5: Backup class
    echo "\n[5] Loading Backup.php...\n";
    try {
        require_once(__DIR__ . '/includes/Backup.php');
        echo "    ✓ Backup.php loaded OK\n";
    } catch (Throwable $e) {
        echo "    ✗ Backup ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }

    // Test 6: Create BackupManager
    echo "\n[6] Testing new BackupManager()...\n";
    try {
        $backup = new BackupManager();
        echo "    ✓ BackupManager created OK\n";
    } catch (Throwable $e) {
        echo "    ✗ BackupManager ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }

    // Test 7: List backups
    echo "\n[7] Testing listBackups()...\n";
    try {
        $backups = $backup->listBackups();
        echo "    ✓ Backups: " . count($backups) . " found\n";
    } catch (Throwable $e) {
        echo "    ✗ listBackups ERROR: " . $e->getMessage() . "\n";
        echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
    }
}

// Test 8: Post model (needed by newsletter)
echo "\n[8] Loading Post.php...\n";
try {
    require_once(__DIR__ . '/includes/Post.php');
    echo "    ✓ Post.php loaded OK\n";
} catch (Throwable $e) {
    echo "    ✗ Post ERROR: " . $e->getMessage() . "\n";
    echo "    Line: " . $e->getLine() . " in " . $e->getFile() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "\nUsage:\n";
echo "  ?page=newsletter - debug newsletter\n";
echo "  ?page=backup - debug backup\n";
echo "</pre>";
