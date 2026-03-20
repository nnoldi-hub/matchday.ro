<?php
/**
 * Search Suggestions API
 * MatchDay.ro - Autocomplete endpoint
 */
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $suggestions = Post::getSearchSuggestions($query, 5);
    echo json_encode($suggestions);
} catch (Exception $e) {
    echo json_encode([]);
}
