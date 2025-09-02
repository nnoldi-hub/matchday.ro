<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pentru debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nu afișa erorile pe ecran, doar în log

function logDebug($message) {
    error_log("[polls-manager] " . $message);
}

logDebug("Script accessed by IP: " . $_SERVER['REMOTE_ADDR']);
logDebug("Request method: " . $_SERVER['REQUEST_METHOD']);
logDebug("POST data: " . json_encode($_POST));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    logDebug("Action requested: " . $action);
    
    switch ($action) {
        case 'create_poll':
            createPoll($_POST);
            break;
        case 'update_poll':
            updatePoll($_POST);
            break;
        case 'delete_poll':
            deletePoll($_POST);
            break;
        case 'toggle_poll':
            togglePoll($_POST);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută: ' . $action]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Doar POST requests sunt permise']);
}

function createPoll($data) {
    logDebug("Creating poll with data: " . json_encode($data));
    
    $id = $data['poll_id'] ?? $data['id'] ?? '';
    $question = $data['question'] ?? '';
    $description = $data['description'] ?? '';
    $options = [];
    
    // Procesează opțiunile - mai multe moduri de a veni datele
    if (isset($data['options']) && is_array($data['options'])) {
        $options = array_filter($data['options'], function($opt) {
            return !empty(trim($opt));
        });
    } else {
        // Caută în POST pentru options[]
        foreach ($data as $key => $value) {
            if (strpos($key, 'options') === 0 && !empty($value)) {
                $options[] = $value;
            }
        }
    }
    
    logDebug("Processed options: " . json_encode($options));
    
    if (empty($id) || empty($question) || count($options) < 2) {
        echo json_encode(['success' => false, 'error' => 'Date incomplete: ID, întrebare și minimum 2 opțiuni sunt obligatorii']);
        return;
    }
    
    // Creează directorul dacă nu există
    $pollsDir = __DIR__ . '/data/polls';
    if (!is_dir($pollsDir)) {
        if (!mkdir($pollsDir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Nu pot crea directorul pentru sondaje']);
            return;
        }
    }
    
    // Verifică dacă ID-ul există deja
    $filename = $pollsDir . '/' . $id . '.json';
    if (file_exists($filename)) {
        echo json_encode(['success' => false, 'error' => 'Un sondaj cu acest ID există deja']);
        return;
    }
    
    // Creează datele sondajului
    $pollData = [
        'id' => $id,
        'question' => $question,
        'description' => $description,
        'options' => array_map(function($opt, $index) {
            return [
                'id' => 'option_' . ($index + 1),
                'text' => trim($opt),
                'votes' => 0
            ];
        }, $options, array_keys($options)),
        'total_votes' => 0,
        'active' => isset($data['active']) ? (bool)$data['active'] : true,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => 'web_interface',
        'voted_ips' => []
    ];
    
    logDebug("Final poll data: " . json_encode($pollData));
    
    // Salvează fișierul
    $jsonData = json_encode($pollData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($filename, $jsonData)) {
        logDebug("Poll saved successfully to: " . $filename);
        
        // Șterge cache-ul
        $cacheFile = __DIR__ . '/data/polls_cache.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            logDebug("Cache cleared");
        }
        
        echo json_encode(['success' => true, 'message' => 'Sondaj creat cu succes!', 'poll_id' => $id]);
    } else {
        logDebug("Failed to save poll to: " . $filename);
        echo json_encode(['success' => false, 'error' => 'Eroare la salvarea sondajului']);
    }
}

function togglePoll($data) {
    $id = $data['poll_id'] ?? $data['id'] ?? '';
    $active = isset($data['active']) ? (bool)$data['active'] : null;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'ID sondaj lipsește']);
        return;
    }
    
    $filename = __DIR__ . '/data/polls/' . $id . '.json';
    if (!file_exists($filename)) {
        echo json_encode(['success' => false, 'error' => 'Sondajul nu există']);
        return;
    }
    
    $pollData = json_decode(file_get_contents($filename), true);
    
    if ($active !== null) {
        $pollData['active'] = $active;
    } else {
        $pollData['active'] = !$pollData['active'];
    }
    
    $pollData['updated_at'] = date('Y-m-d H:i:s');
    
    if (file_put_contents($filename, json_encode($pollData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // Șterge cache
        $cacheFile = __DIR__ . '/data/polls_cache.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        echo json_encode(['success' => true, 'message' => 'Status schimbat cu succes']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Eroare la salvare']);
    }
}

function deletePoll($data) {
    $id = $data['poll_id'] ?? $data['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'ID sondaj lipsește']);
        return;
    }
    
    $filename = __DIR__ . '/data/polls/' . $id . '.json';
    if (!file_exists($filename)) {
        echo json_encode(['success' => false, 'error' => 'Sondajul nu există']);
        return;
    }
    
    // Creează backup
    $backupDir = __DIR__ . '/data/backups/polls';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/' . $id . '_' . date('Y-m-d_H-i-s') . '.json';
    copy($filename, $backupFile);
    
    if (unlink($filename)) {
        // Șterge cache
        $cacheFile = __DIR__ . '/data/polls_cache.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        echo json_encode(['success' => true, 'message' => 'Sondaj șters cu succes']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Eroare la ștergere']);
    }
}

function updatePoll($data) {
    $id = $data['poll_id'] ?? $data['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'ID sondaj lipsește']);
        return;
    }
    
    $filename = __DIR__ . '/data/polls/' . $id . '.json';
    if (!file_exists($filename)) {
        echo json_encode(['success' => false, 'error' => 'Sondajul nu există']);
        return;
    }
    
    $pollData = json_decode(file_get_contents($filename), true);
    
    // Update datele
    if (isset($data['question'])) $pollData['question'] = $data['question'];
    if (isset($data['description'])) $pollData['description'] = $data['description'];
    if (isset($data['active'])) $pollData['active'] = (bool)$data['active'];
    
    // Update opțiuni dacă sunt furnizate
    if (isset($data['options']) && is_array($data['options'])) {
        $options = array_filter($data['options'], function($opt) {
            return !empty(trim($opt));
        });
        
        if (count($options) >= 2) {
            // Păstrează voturile existente pentru opțiunile cu același text
            $existingVotes = [];
            foreach ($pollData['options'] as $existingOption) {
                $existingVotes[$existingOption['text']] = $existingOption['votes'];
            }
            
            $pollData['options'] = array_map(function($opt, $index) use ($existingVotes) {
                return [
                    'id' => 'option_' . ($index + 1),
                    'text' => trim($opt),
                    'votes' => $existingVotes[trim($opt)] ?? 0
                ];
            }, $options, array_keys($options));
            
            // Recalculează totalul
            $pollData['total_votes'] = array_sum(array_column($pollData['options'], 'votes'));
        }
    }
    
    $pollData['updated_at'] = date('Y-m-d H:i:s');
    
    if (file_put_contents($filename, json_encode($pollData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // Șterge cache
        $cacheFile = __DIR__ . '/data/polls_cache.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        echo json_encode(['success' => true, 'message' => 'Sondaj actualizat cu succes']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Eroare la actualizare']);
    }
}
?>
