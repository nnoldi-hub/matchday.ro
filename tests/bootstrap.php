<?php
/**
 * PHPUnit Bootstrap
 * MatchDay.ro - Test initialization
 */

// Set testing constants (only if not already defined)
if (!defined('TESTING')) {
    define('TESTING', true);
}
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);
}

// Define required constants if not set
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'MatchDay.ro Test');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost');
}

if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', false);
}

// USE_MYSQL will be defined by database.php

// Set include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load classes
require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Logger.php';

// Suppress output during tests
ob_start();
