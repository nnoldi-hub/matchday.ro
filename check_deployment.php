<?php
/**
 * Script de verificare post-deployment pentru MatchDay.ro
 * Rulează acest script după încărcarea pe server pentru a verifica că totul funcționează
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificare Deployment - MatchDay.ro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .check-ok { color: #28a745; }
        .check-error { color: #dc3545; }
        .check-warning { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <h1 class="mb-4">🔧 Verificare Deployment MatchDay.ro</h1>
        
        <?php
        $checks = [];
        
        // 1. Verificare PHP Version
        $phpVersion = PHP_VERSION;
        $checks[] = [
            'name' => 'Versiunea PHP',
            'status' => version_compare($phpVersion, '8.1.0', '>=') ? 'ok' : 'error',
            'message' => "PHP $phpVersion " . (version_compare($phpVersion, '8.1.0', '>=') ? '✅' : '❌ Necesită PHP 8.1+')
        ];
        
        // 2. Verificare config
        if (file_exists(__DIR__ . '/config/config.php')) {
            require_once(__DIR__ . '/config/config.php');
            $checks[] = [
                'name' => 'Fișier config.php',
                'status' => 'ok',
                'message' => '✅ Găsit și încărcat'
            ];
            
            // Verifică dacă email-ul este configurat
            $emailConfigured = CONTACT_TO_EMAIL !== 'test@example.com' && CONTACT_TO_EMAIL !== 'contact@matchday.ro';
            $checks[] = [
                'name' => 'Email de contact configurat',
                'status' => $emailConfigured ? 'ok' : 'warning',
                'message' => $emailConfigured ? '✅ Configurat: ' . CONTACT_TO_EMAIL : '⚠️ Folosește email-ul implicit'
            ];
        } else {
            $checks[] = [
                'name' => 'Fișier config.php',
                'status' => 'error',
                'message' => '❌ Lipsește sau nu poate fi citit'
            ];
        }
        
        // 3. Verificare directoare
        $dirs = [
            'data' => __DIR__ . '/data',
            'data/comments' => __DIR__ . '/data/comments',
            'data/contact_messages' => __DIR__ . '/data/contact_messages',
            'assets/uploads' => __DIR__ . '/assets/uploads'
        ];
        
        foreach ($dirs as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            
            if ($exists && $writable) {
                $status = 'ok';
                $message = '✅ Există și este scriptibil';
            } elseif ($exists) {
                $status = 'warning';
                $message = '⚠️ Există dar nu este scriptibil';
            } else {
                $status = 'error';
                $message = '❌ Nu există';
                // Încearcă să creeze directorul
                if (@mkdir($path, 0755, true)) {
                    $status = 'ok';
                    $message = '✅ Creat automat';
                }
            }
            
            $checks[] = [
                'name' => "Director $name",
                'status' => $status,
                'message' => $message
            ];
        }
        
        // 4. Verificare PHPMailer
        $phpmailerExists = file_exists(__DIR__ . '/vendor/autoload.php');
        if ($phpmailerExists) {
            require_once(__DIR__ . '/vendor/autoload.php');
            $phpmailerWorks = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
            $checks[] = [
                'name' => 'PHPMailer',
                'status' => $phpmailerWorks ? 'ok' : 'warning',
                'message' => $phpmailerWorks ? '✅ Instalat și funcțional' : '⚠️ Instalat dar nu funcționează'
            ];
        } else {
            $checks[] = [
                'name' => 'PHPMailer',
                'status' => 'warning',
                'message' => '⚠️ Nu este instalat - se va folosi mail() nativ'
            ];
        }
        
        // 5. Verificare funcții PHP necesare
        $functions = ['mail', 'json_encode', 'json_decode', 'file_get_contents', 'file_put_contents'];
        foreach ($functions as $func) {
            $exists = function_exists($func);
            $checks[] = [
                'name' => "Funcția $func()",
                'status' => $exists ? 'ok' : 'error',
                'message' => $exists ? '✅ Disponibilă' : '❌ Indisponibilă'
            ];
        }
        
        // 6. Verificare HTTPS (dacă este în producție)
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
        
        if (!$isLocalhost) {
            $checks[] = [
                'name' => 'HTTPS',
                'status' => $isHttps ? 'ok' : 'warning',
                'message' => $isHttps ? '✅ SSL activ' : '⚠️ Recomandăm activarea SSL'
            ];
        }
        
        // 7. Test scriere fișier
        $testFile = __DIR__ . '/data/test_write.txt';
        $canWrite = @file_put_contents($testFile, 'test') !== false;
        if ($canWrite) {
            @unlink($testFile); // Curățăm după test
        }
        
        $checks[] = [
            'name' => 'Test scriere fișiere',
            'status' => $canWrite ? 'ok' : 'error',
            'message' => $canWrite ? '✅ Poate scrie fișiere' : '❌ Nu poate scrie fișiere'
        ];
        ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Rezultate Verificare</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($checks as $check): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?= htmlspecialchars($check['name']) ?></div>
                                        <span class="check-<?= $check['status'] ?>"><?= $check['message'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Pași Următori</h3>
                    </div>
                    <div class="card-body">
                        <h5>Dacă totul e ✅:</h5>
                        <ol class="small">
                            <li>Șterge acest fișier (<code>check_deployment.php</code>)</li>
                            <li>Testează formularul de contact</li>
                            <li>Testează sistemul de comentarii</li>
                            <li>Verifică admin login</li>
                        </ol>
                        
                        <h5 class="mt-3">Pentru probleme ❌:</h5>
                        <ul class="small">
                            <li>Verifică permisiunile folder-elor (755)</li>
                            <li>Contactează hostingul pentru PHP/funcții</li>
                            <li>Instalează PHPMailer cu Composer</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Informații Server</h3>
                    </div>
                    <div class="card-body small">
                        <strong>OS:</strong> <?= PHP_OS ?><br>
                        <strong>PHP:</strong> <?= PHP_VERSION ?><br>
                        <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Necunoscut' ?><br>
                        <strong>Host:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'Necunoscut' ?><br>
                        <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Necunoscut' ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning mt-4">
            <strong>⚠️ IMPORTANT:</strong> După verificare, șterge acest fișier din motive de securitate!
            <br><code>rm check_deployment.php</code>
        </div>
    </div>
</body>
</html>
