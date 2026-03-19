<?php
require_once(__DIR__ . '/../config/config.php');

// Check if user is logged in
if (empty($_SESSION['david_logged'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acces interzis']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$pollsDir = __DIR__ . '/../data/polls';
if (!is_dir($pollsDir)) {
    mkdir($pollsDir, 0755, true);
}

switch ($action) {
    case 'create_poll':
        createPoll($input, $pollsDir);
        break;
        
    case 'update_poll':
        updatePoll($input, $pollsDir);
        break;
        
    case 'toggle_poll':
        togglePollStatus($input, $pollsDir);
        break;
        
    case 'delete_poll':
        deletePoll($input, $pollsDir);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acțiune invalidă']);
        break;
}

function createPoll($data, $pollsDir) {
    try {
        // Debug logging
        error_log("CreatePoll called with data: " . json_encode($data));
        error_log("Polls directory: " . $pollsDir);
        error_log("Directory exists: " . (is_dir($pollsDir) ? 'YES' : 'NO'));
        error_log("Directory writable: " . (is_writable($pollsDir) ? 'YES' : 'NO'));
        
        // Validate required fields
        $pollId = Security::sanitizeInput($data['poll_id'] ?? '');
        $question = Security::sanitizeInput($data['question'] ?? '');
        $options = $data['options'] ?? [];
        
        if (empty($pollId) || empty($question) || count($options) < 2) {
            throw new Exception('Date incomplete. ID, întrebarea și minim 2 opțiuni sunt necesare.');
        }
        
        // Validate poll ID format
        if (!preg_match('/^[a-z0-9\-_]+$/', $pollId)) {
            throw new Exception('ID-ul sondajului poate conține doar litere mici, cifre, cratima și underscore.');
        }
        
        // Check if poll ID already exists
        $pollFile = $pollsDir . '/' . $pollId . '.json';
        if (file_exists($pollFile)) {
            throw new Exception('Un sondaj cu acest ID există deja.');
        }
        
        // Validate and sanitize options
        $cleanOptions = [];
        foreach ($options as $index => $optionText) {
            $optionText = trim(Security::sanitizeInput($optionText));
            if (strlen($optionText) > 0) {
                $cleanOptions[] = [
                    'id' => 'option_' . ($index + 1),
                    'text' => $optionText,
                    'votes' => 0
                ];
            }
        }
        
        if (count($cleanOptions) < 2) {
            throw new Exception('Minim 2 opțiuni valide sunt necesare.');
        }
        
        if (count($cleanOptions) > 10) {
            throw new Exception('Maxim 10 opțiuni sunt permise.');
        }
        
        // Create poll data
        $pollData = [
            'id' => $pollId,
            'question' => $question,
            'description' => Security::sanitizeInput($data['description'] ?? ''),
            'options' => $cleanOptions,
            'total_votes' => 0,
            'active' => $data['active'] ?? false,
            'created_at' => date('Y-m-d'),
            'created_by' => $_SESSION['david_logged'],
            'voted_ips' => []
        ];
        
        // Save poll
        error_log("Attempting to save poll to: " . $pollFile);
        error_log("Poll data: " . json_encode($pollData));
        
        $bytesWritten = file_put_contents($pollFile, json_encode($pollData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        if ($bytesWritten === false) {
            error_log("Failed to save poll file: " . $pollFile);
            throw new Exception('Eroare la salvarea sondajului.');
        }
        
        error_log("Poll saved successfully. Bytes written: " . $bytesWritten);
        error_log("File exists after save: " . (file_exists($pollFile) ? 'YES' : 'NO'));
        
        // Clear cache
        if (CACHE_ENABLED) {
            Cache::clear();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sondaj creat cu succes!',
            'poll_id' => $pollId
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updatePoll($data, $pollsDir) {
    try {
        // Validate required fields
        $pollId = Security::sanitizeInput($data['poll_id'] ?? '');
        $question = Security::sanitizeInput($data['question'] ?? '');
        $options = $data['options'] ?? [];
        
        if (empty($pollId) || empty($question) || count($options) < 2) {
            throw new Exception('Date incomplete. ID, întrebarea și minim 2 opțiuni sunt necesare.');
        }
        
        $pollFile = $pollsDir . '/' . $pollId . '.json';
        if (!file_exists($pollFile)) {
            throw new Exception('Sondajul nu a fost găsit.');
        }
        
        // Load existing poll data
        $existingPollData = json_decode(file_get_contents($pollFile), true);
        if (!$existingPollData) {
            throw new Exception('Eroare la citirea sondajului existent.');
        }
        
        // Validate and sanitize options
        $cleanOptions = [];
        foreach ($options as $index => $optionText) {
            $optionText = trim(Security::sanitizeInput($optionText));
            if (strlen($optionText) > 0) {
                // Try to preserve existing votes if option text matches
                $existingVotes = 0;
                foreach ($existingPollData['options'] as $existingOption) {
                    if ($existingOption['text'] === $optionText) {
                        $existingVotes = $existingOption['votes'];
                        break;
                    }
                }
                
                $cleanOptions[] = [
                    'id' => 'option_' . ($index + 1),
                    'text' => $optionText,
                    'votes' => $existingVotes
                ];
            }
        }
        
        if (count($cleanOptions) < 2) {
            throw new Exception('Minim 2 opțiuni valide sunt necesare.');
        }
        
        if (count($cleanOptions) > 10) {
            throw new Exception('Maxim 10 opțiuni sunt permise.');
        }
        
        // Calculate total votes
        $totalVotes = array_sum(array_column($cleanOptions, 'votes'));
        
        // Update poll data
        $updatedPollData = [
            'id' => $pollId,
            'question' => $question,
            'description' => Security::sanitizeInput($data['description'] ?? ''),
            'options' => $cleanOptions,
            'total_votes' => $totalVotes,
            'active' => $data['active'] ?? $existingPollData['active'],
            'created_at' => $existingPollData['created_at'],
            'created_by' => $existingPollData['created_by'],
            'voted_ips' => $existingPollData['voted_ips'] ?? [],
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['david_logged']
        ];
        
        // Create backup before update
        $backupDir = __DIR__ . '/../data/backups/polls';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/' . $pollId . '_backup_' . date('Y-m-d_H-i-s') . '.json';
        copy($pollFile, $backupFile);
        
        // Save updated poll
        if (file_put_contents($pollFile, json_encode($updatedPollData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new Exception('Eroare la actualizarea sondajului.');
        }
        
        // Clear cache
        if (CACHE_ENABLED) {
            Cache::clear();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sondajul a fost actualizat cu succes!',
            'poll_id' => $pollId
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function togglePollStatus($data, $pollsDir) {
    try {
        $pollId = Security::sanitizeInput($data['poll_id'] ?? '');
        $newStatus = $data['active'] ?? false;
        
        if (empty($pollId)) {
            throw new Exception('ID sondaj este necesar.');
        }
        
        $pollFile = $pollsDir . '/' . $pollId . '.json';
        if (!file_exists($pollFile)) {
            throw new Exception('Sondajul nu a fost găsit.');
        }
        
        $pollData = json_decode(file_get_contents($pollFile), true);
        if (!$pollData) {
            throw new Exception('Eroare la citirea sondajului.');
        }
        
        $pollData['active'] = $newStatus;
        $pollData['updated_at'] = date('Y-m-d H:i:s');
        
        if (file_put_contents($pollFile, json_encode($pollData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new Exception('Eroare la actualizarea sondajului.');
        }
        
        // Clear cache
        if (CACHE_ENABLED) {
            Cache::clear();
        }
        
        $statusText = $newStatus ? 'activat' : 'dezactivat';
        echo json_encode([
            'success' => true, 
            'message' => "Sondajul a fost {$statusText} cu succes!"
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deletePoll($data, $pollsDir) {
    try {
        $pollId = Security::sanitizeInput($data['poll_id'] ?? '');
        
        if (empty($pollId)) {
            throw new Exception('ID sondaj este necesar.');
        }
        
        $pollFile = $pollsDir . '/' . $pollId . '.json';
        if (!file_exists($pollFile)) {
            throw new Exception('Sondajul nu a fost găsit.');
        }
        
        // Create backup before deletion
        $backupDir = __DIR__ . '/../data/backups/polls';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/' . $pollId . '_deleted_' . date('Y-m-d_H-i-s') . '.json';
        copy($pollFile, $backupFile);
        
        // Delete poll
        if (!unlink($pollFile)) {
            throw new Exception('Eroare la ștergerea sondajului.');
        }
        
        // Clear cache
        if (CACHE_ENABLED) {
            Cache::clear();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sondajul a fost șters cu succes!'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
