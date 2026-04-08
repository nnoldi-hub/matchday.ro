<?php
/**
 * Health Check Endpoint
 * MatchDay.ro - System health monitoring
 * 
 * Returns JSON with system status for monitoring tools
 * Example: UptimeRobot, Better Uptime, etc.
 */

// No session needed, no auth required for basic health check
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/cache.php');
require_once(__DIR__ . '/includes/Logger.php');

// Start timing
$startTime = microtime(true);

// Initialize response
$response = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'version' => '2.0.0',
    'checks' => []
];

$hasErrors = false;
$hasDegraded = false;

// ====== Check 1: Database ======
try {
    $dbStart = microtime(true);
    $db = Database::getInstance();
    
    // Simple query to verify connection
    if (Database::isMySQL()) {
        $result = $db->query("SELECT 1")->fetch();
    } else {
        $result = $db->query("SELECT 1")->fetch();
    }
    
    $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
    
    $response['checks']['database'] = [
        'status' => 'ok',
        'latency_ms' => $dbLatency,
        'type' => Database::isMySQL() ? 'mysql' : 'sqlite'
    ];
    
    // Warning if latency is high
    if ($dbLatency > 500) {
        $response['checks']['database']['status'] = 'degraded';
        $hasDegraded = true;
    }
    
} catch (Exception $e) {
    $response['checks']['database'] = [
        'status' => 'error',
        'error' => 'Connection failed'
    ];
    $hasErrors = true;
    Logger::critical('Health check: Database connection failed', ['error' => $e->getMessage()]);
}

// ====== Check 2: Cache ======
try {
    $cacheStart = microtime(true);
    
    // Test write
    $testKey = 'health_check_' . time();
    $testValue = 'test_' . random_int(1000, 9999);
    Cache::set($testKey, $testValue, 60);
    
    // Test read
    $readValue = Cache::get($testKey);
    
    // Test delete
    Cache::delete($testKey);
    
    $cacheLatency = round((microtime(true) - $cacheStart) * 1000, 2);
    
    if ($readValue === $testValue) {
        $response['checks']['cache'] = [
            'status' => 'ok',
            'latency_ms' => $cacheLatency,
            'enabled' => defined('CACHE_ENABLED') && CACHE_ENABLED
        ];
    } else {
        $response['checks']['cache'] = [
            'status' => 'degraded',
            'error' => 'Read/write mismatch'
        ];
        $hasDegraded = true;
    }
    
} catch (Exception $e) {
    $response['checks']['cache'] = [
        'status' => 'error',
        'error' => 'Cache test failed'
    ];
    $hasErrors = true;
}

// ====== Check 3: Disk Space ======
try {
    $uploadsDir = __DIR__ . '/assets/uploads';
    $dataDir = __DIR__ . '/data';
    
    $freeBytes = disk_free_space(__DIR__);
    $totalBytes = disk_total_space(__DIR__);
    
    $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
    $totalGB = round($totalBytes / 1024 / 1024 / 1024, 2);
    $usedPercent = round((1 - $freeBytes / $totalBytes) * 100, 1);
    
    $diskStatus = 'ok';
    if ($freeGB < 1) {
        $diskStatus = 'error';
        $hasErrors = true;
    } elseif ($freeGB < 5 || $usedPercent > 90) {
        $diskStatus = 'degraded';
        $hasDegraded = true;
    }
    
    $response['checks']['disk_space'] = [
        'status' => $diskStatus,
        'free_gb' => $freeGB,
        'total_gb' => $totalGB,
        'used_percent' => $usedPercent
    ];
    
} catch (Exception $e) {
    $response['checks']['disk_space'] = [
        'status' => 'unknown',
        'error' => 'Could not check disk space'
    ];
}

// ====== Check 4: Uploads Directory ======
try {
    $uploadsDir = __DIR__ . '/assets/uploads';
    $isWritable = is_writable($uploadsDir);
    
    $response['checks']['uploads'] = [
        'status' => $isWritable ? 'ok' : 'error',
        'writable' => $isWritable,
        'path' => 'assets/uploads'
    ];
    
    if (!$isWritable) {
        $hasErrors = true;
    }
    
} catch (Exception $e) {
    $response['checks']['uploads'] = [
        'status' => 'unknown'
    ];
}

// ====== Check 5: Logs Directory ======
try {
    $logsDir = __DIR__ . '/data/logs';
    $logsWritable = is_dir($logsDir) && is_writable($logsDir);
    
    $response['checks']['logs'] = [
        'status' => $logsWritable ? 'ok' : 'degraded',
        'writable' => $logsWritable
    ];
    
    if (!$logsWritable) {
        $hasDegraded = true;
    }
    
} catch (Exception $e) {
    $response['checks']['logs'] = [
        'status' => 'unknown'
    ];
}

// ====== Check 6: External API (Live Scores) ======
// Only check if configured
$livescoresConfig = __DIR__ . '/config/livescores_config.php';
if (file_exists($livescoresConfig)) {
    try {
        $config = include $livescoresConfig;
        $provider = $config['provider'] ?? 'manual';
        
        if ($provider !== 'manual' && !empty($config['api_key'])) {
            // Check if we have cached data (don't make actual API call in health check)
            $cachedData = Cache::get('live_matches');
            
            $response['checks']['external_api'] = [
                'status' => $cachedData !== null ? 'ok' : 'unknown',
                'provider' => $provider,
                'cached_data' => $cachedData !== null
            ];
        } else {
            $response['checks']['external_api'] = [
                'status' => 'ok',
                'provider' => 'manual',
                'note' => 'No external API configured'
            ];
        }
    } catch (Exception $e) {
        $response['checks']['external_api'] = [
            'status' => 'unknown'
        ];
    }
} else {
    $response['checks']['external_api'] = [
        'status' => 'ok',
        'provider' => 'manual'
    ];
}

// ====== Check 7: PHP Configuration ======
$response['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $response['checks']['php']['status'] = 'degraded';
    $hasDegraded = true;
}

// ====== Overall Status ======
$totalTime = round((microtime(true) - $startTime) * 1000, 2);

if ($hasErrors) {
    $response['status'] = 'unhealthy';
    http_response_code(503); // Service Unavailable
} elseif ($hasDegraded) {
    $response['status'] = 'degraded';
    http_response_code(200); // Still OK but degraded
} else {
    $response['status'] = 'healthy';
    http_response_code(200);
}

$response['response_time_ms'] = $totalTime;

// Output JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Log health check if there are issues
if ($hasErrors || $hasDegraded) {
    Logger::warning('Health check: ' . $response['status'], [
        'checks' => array_map(fn($c) => $c['status'] ?? 'unknown', $response['checks'])
    ]);
}
