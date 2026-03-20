<?php
/**
 * Polls Actions API - Refactored with Database
 * MatchDay.ro
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Poll.php');

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['david_logged'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acces interzis']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
$csrfToken = $input['csrf_token'] ?? '';
if (!Security::validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalid. Reîncarcă pagina.']);
    exit;
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'create_poll':
        createPoll($input);
        break;
        
    case 'update_poll':
        updatePoll($input);
        break;
        
    case 'toggle_poll':
        togglePollStatus($input);
        break;
        
    case 'delete_poll':
        deletePoll($input);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acțiune invalidă']);
        break;
}

function createPoll($data) {
    try {
        // Validate required fields
        $slug = Security::sanitizeInput($data['slug'] ?? '');
        $question = Security::sanitizeInput($data['question'] ?? '');
        $options = $data['options'] ?? [];
        
        if (empty($slug) || empty($question) || count($options) < 2) {
            throw new Exception('Date incomplete. Slug, întrebarea și minim 2 opțiuni sunt necesare.');
        }
        
        // Validate slug format
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            throw new Exception('Slug-ul poate conține doar litere mici, cifre și cratimă.');
        }
        
        // Check if slug already exists
        if (Poll::slugExists($slug)) {
            throw new Exception('Un sondaj cu acest slug există deja.');
        }
        
        // Validate and sanitize options
        $cleanOptions = [];
        foreach ($options as $optionText) {
            $optionText = trim(Security::sanitizeInput($optionText));
            if (strlen($optionText) > 0) {
                $cleanOptions[] = $optionText;
            }
        }
        
        if (count($cleanOptions) < 2) {
            throw new Exception('Minim 2 opțiuni valide sunt necesare.');
        }
        
        if (count($cleanOptions) > 10) {
            throw new Exception('Maxim 10 opțiuni sunt permise.');
        }
        
        // Create poll via model
        $pollId = Poll::create([
            'slug' => $slug,
            'question' => $question,
            'description' => Security::sanitizeInput($data['description'] ?? ''),
            'options' => $cleanOptions,
            'active' => $data['active'] ?? false ? 1 : 0
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sondaj creat cu succes!',
            'poll_id' => $pollId
        ]);
        
    } catch (Exception $e) {
        error_log("Create poll error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updatePoll($data) {
    try {
        $pollId = (int) ($data['poll_id'] ?? 0);
        
        if ($pollId <= 0) {
            throw new Exception('ID sondaj invalid.');
        }
        
        $poll = Poll::getById($pollId);
        if (!$poll) {
            throw new Exception('Sondajul nu a fost găsit.');
        }
        
        $question = Security::sanitizeInput($data['question'] ?? '');
        $options = $data['options'] ?? [];
        
        if (empty($question)) {
            throw new Exception('Întrebarea este obligatorie.');
        }
        
        // Validate options
        $cleanOptions = [];
        foreach ($options as $optionText) {
            $optionText = trim(Security::sanitizeInput($optionText));
            if (strlen($optionText) > 0) {
                $cleanOptions[] = $optionText;
            }
        }
        
        if (count($cleanOptions) < 2) {
            throw new Exception('Minim 2 opțiuni valide sunt necesare.');
        }
        
        // Update poll
        Poll::update($pollId, [
            'question' => $question,
            'description' => Security::sanitizeInput($data['description'] ?? ''),
            'active' => $data['active'] ?? false ? 1 : 0
        ]);
        
        // Update options (delete old and insert new)
        Poll::updateOptions($pollId, $cleanOptions);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sondaj actualizat cu succes!'
        ]);
        
    } catch (Exception $e) {
        error_log("Update poll error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function togglePollStatus($data) {
    try {
        $pollId = (int) ($data['poll_id'] ?? 0);
        
        if ($pollId <= 0) {
            throw new Exception('ID sondaj invalid.');
        }
        
        Poll::toggleStatus($pollId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Status sondaj modificat!'
        ]);
        
    } catch (Exception $e) {
        error_log("Toggle poll error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deletePoll($data) {
    try {
        $pollId = (int) ($data['poll_id'] ?? 0);
        
        if ($pollId <= 0) {
            throw new Exception('ID sondaj invalid.');
        }
        
        Poll::delete($pollId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sondaj șters cu succes!'
        ]);
        
    } catch (Exception $e) {
        error_log("Delete poll error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
