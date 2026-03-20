<?php
/**
 * Ad Click Tracker & Redirect
 * MatchDay.ro
 * 
 * Usage: /ad-click.php?id=123
 * Tracks click and redirects to ad destination
 */

require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Ad.php');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    // Invalid ID - redirect to homepage
    header('Location: /');
    exit;
}

// Get ad info
$ad = Ad::getById($id);

if (!$ad) {
    // Ad not found - redirect to homepage
    header('Location: /');
    exit;
}

// Record the click
Ad::recordClick($id);

// Redirect to destination
$destination = $ad['link'] ?: '/';

// Validate URL to prevent open redirect vulnerability
if (!filter_var($destination, FILTER_VALIDATE_URL)) {
    // If not a valid URL, assume it's a relative path
    $destination = '/' . ltrim($destination, '/');
}

// Send redirect
header('Location: ' . $destination, true, 302);
exit;
