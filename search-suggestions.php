<?php
/**
 * Search Suggestions API
 * MatchDay.ro - Enhanced autocomplete endpoint with images & categories
 */
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/seo.php');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $suggestions = Post::getSearchSuggestions($query, 6);
    
    // Load categories for names
    $categories = require(__DIR__ . '/config/categories.php');
    
    // Enrich suggestions with additional data
    $enrichedSuggestions = array_map(function($item) use ($categories) {
        return [
            'title' => $item['title'],
            'url' => SEOManager::getArticleUrl($item['slug']),
            'cover_image' => $item['cover_image'] ?? null,
            'category_slug' => $item['category_slug'] ?? null,
            'category_name' => isset($item['category_slug'], $categories[$item['category_slug']]) 
                ? $categories[$item['category_slug']]['name'] 
                : null,
            'published_at' => $item['published_at'] ?? null,
            'views' => $item['views'] ?? 0
        ];
    }, $suggestions);
    
    echo json_encode($enrichedSuggestions);
} catch (Exception $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    echo json_encode([]);
}
