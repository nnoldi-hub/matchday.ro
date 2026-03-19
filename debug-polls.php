<?php
// Debug script pentru probleme cu sondajele pe Hostico
require_once(__DIR__ . '/config/config.php');

// Verifică și creează directorul polls dacă nu există
$pollsDir = __DIR__ . '/data/polls';
$dataDir = __DIR__ . '/data';

echo "=== DEBUGGING POLLS SYSTEM ===\n\n";

echo "1. VERIFICARE DIRECTOARE:\n";
echo "Data dir exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . "\n";
echo "Polls dir exists: " . (is_dir($pollsDir) ? 'YES' : 'NO') . "\n";
echo "Data dir writable: " . (is_writable($dataDir) ? 'YES' : 'NO') . "\n";
echo "Polls dir writable: " . (is_writable($pollsDir) ? 'YES' : 'NO') . "\n";

// Încearcă să creeze directorul dacă nu există
if (!is_dir($dataDir)) {
    $created = mkdir($dataDir, 0755, true);
    echo "Created data dir: " . ($created ? 'YES' : 'NO') . "\n";
}

if (!is_dir($pollsDir)) {
    $created = mkdir($pollsDir, 0755, true);
    echo "Created polls dir: " . ($created ? 'YES' : 'NO') . "\n";
}

echo "\n2. PERMISIUNI DIRECTOARE:\n";
if (is_dir($dataDir)) {
    echo "Data dir permissions: " . substr(sprintf('%o', fileperms($dataDir)), -4) . "\n";
}
if (is_dir($pollsDir)) {
    echo "Polls dir permissions: " . substr(sprintf('%o', fileperms($pollsDir)), -4) . "\n";
}

echo "\n3. LISTA SONDAJE EXISTENTE:\n";
if (is_dir($pollsDir)) {
    $files = scandir($pollsDir);
    $jsonFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'json';
    });
    
    if (empty($jsonFiles)) {
        echo "Nu există sondaje în director.\n";
    } else {
        foreach ($jsonFiles as $file) {
            echo "- " . $file . "\n";
            $content = file_get_contents($pollsDir . '/' . $file);
            $poll = json_decode($content, true);
            if ($poll) {
                echo "  ID: " . $poll['id'] . "\n";
                echo "  Active: " . ($poll['active'] ? 'YES' : 'NO') . "\n";
                echo "  Created: " . $poll['created_at'] . "\n";
            }
        }
    }
} else {
    echo "Directorul polls nu există!\n";
}

echo "\n4. TEST CREARE SONDAJ:\n";
$testPollId = 'test-debug-' . date('Y-m-d-H-i-s');
$testPoll = [
    'id' => $testPollId,
    'question' => 'Test debug sondaj',
    'description' => 'Aceasta este o întrebare de test',
    'options' => [
        ['id' => 'option_1', 'text' => 'Opțiunea 1', 'votes' => 0],
        ['id' => 'option_2', 'text' => 'Opțiunea 2', 'votes' => 0]
    ],
    'total_votes' => 0,
    'active' => true,
    'created_at' => date('Y-m-d'),
    'created_by' => 'debug_script',
    'voted_ips' => []
];

$testFile = $pollsDir . '/' . $testPollId . '.json';
$written = file_put_contents($testFile, json_encode($testPoll, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

echo "Test poll creation: " . ($written !== false ? 'SUCCESS' : 'FAILED') . "\n";
if ($written !== false) {
    echo "Bytes written: " . $written . "\n";
    echo "File exists after creation: " . (file_exists($testFile) ? 'YES' : 'NO') . "\n";
}

echo "\n5. TEST API POLLS:\n";
// Simulează cererea către polls_api.php
$_GET = ['action' => 'get_active_polls'];
ob_start();
include(__DIR__ . '/polls_api.php');
$apiResponse = ob_get_clean();

echo "API Response:\n";
echo $apiResponse . "\n";

echo "\n6. INFORMAȚII SERVER:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Current Working Directory: " . getcwd() . "\n";
echo "Script Directory: " . __DIR__ . "\n";

echo "\n7. ERROR LOG:\n";
if (function_exists('error_get_last')) {
    $lastError = error_get_last();
    if ($lastError) {
        echo "Last Error: " . $lastError['message'] . "\n";
        echo "File: " . $lastError['file'] . "\n";
        echo "Line: " . $lastError['line'] . "\n";
    } else {
        echo "No recent errors.\n";
    }
}

echo "\n=== END DEBUG ===\n";
?>
