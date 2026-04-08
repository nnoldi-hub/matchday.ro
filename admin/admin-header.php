<?php
/**
 * Admin Header with Sidebar Layout
 * MatchDay.ro - Phase 5
 */

// Ensure session and auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Get current user info
$currentUserRole = $_SESSION['user_role'] ?? 'admin';
$currentUserName = $_SESSION['user_name'] ?? 'Admin';
$isAdmin = $currentUserRole === 'admin';

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Menu items configuration
$menuItems = [
    ['page' => 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'admin_only' => false],
    ['page' => 'new-post', 'icon' => 'fa-plus-circle', 'label' => 'Articol nou', 'admin_only' => false],
    ['page' => 'posts', 'icon' => 'fa-newspaper', 'label' => 'Articole', 'admin_only' => false],
    ['page' => 'categories', 'icon' => 'fa-folder', 'label' => 'Categorii', 'admin_only' => false],
    ['page' => 'media', 'icon' => 'fa-images', 'label' => 'Media', 'admin_only' => false],
    ['page' => 'polls', 'icon' => 'fa-poll', 'label' => 'Sondaje', 'admin_only' => false],
    ['page' => 'homepage-results', 'icon' => 'fa-futbol', 'label' => 'Rezultate', 'admin_only' => false],
    ['page' => 'livescores', 'icon' => 'fa-stopwatch', 'label' => 'Scoruri Live', 'admin_only' => false],
    ['page' => 'comments', 'icon' => 'fa-comments', 'label' => 'Comentarii', 'admin_only' => false],
    ['page' => 'submissions', 'icon' => 'fa-user-edit', 'label' => 'Contribuții', 'admin_only' => false],
    ['divider' => true],
    ['page' => 'users', 'icon' => 'fa-users', 'label' => 'Utilizatori', 'admin_only' => true],
    ['page' => 'stats', 'icon' => 'fa-chart-line', 'label' => 'Statistici', 'admin_only' => true],
    ['page' => 'newsletter', 'icon' => 'fa-envelope', 'label' => 'Newsletter', 'admin_only' => true],
    ['page' => 'ads', 'icon' => 'fa-ad', 'label' => 'Reclame', 'admin_only' => true],
    ['divider' => true],
    ['page' => 'backup', 'icon' => 'fa-database', 'label' => 'Backup', 'admin_only' => true],
    ['page' => 'logs', 'icon' => 'fa-file-alt', 'label' => 'Logs', 'admin_only' => true],
    ['page' => 'audit-log', 'icon' => 'fa-clipboard-list', 'label' => 'Audit Log', 'admin_only' => true],
    ['page' => 'style-guide', 'icon' => 'fa-palette', 'label' => 'Style Guide', 'admin_only' => true],
    ['page' => 'settings', 'icon' => 'fa-cog', 'label' => 'Setări', 'admin_only' => true],
    ['page' => 'editorial-management', 'icon' => 'fa-calendar-alt', 'label' => 'Plan Editorial', 'admin_only' => false],
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $pageTitle ?? 'Admin' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link href="/assets/css/admin.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
</head>
<body class="admin-body">
    
    <!-- Mobile Header -->
    <header class="admin-mobile-header d-lg-none">
        <button class="sidebar-toggle" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="dashboard.php" class="brand">
            <img src="/assets/images/logo.png" alt="Logo" width="28" height="28">
            <span><?= SITE_NAME ?></span>
        </a>
        <a href="/index.php" class="view-site" target="_blank" title="Vezi site">
            <i class="fas fa-external-link-alt"></i>
        </a>
    </header>
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <img src="/assets/images/logo.png" alt="Logo" width="32" height="32">
                <span><?= SITE_NAME ?></span>
            </a>
            <button class="sidebar-close d-lg-none" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-info">
                <span class="user-name"><?= Security::sanitizeInput($currentUserName) ?></span>
                <span class="user-role badge bg-<?= $isAdmin ? 'danger' : 'secondary' ?>">
                    <?= $isAdmin ? 'Admin' : 'Editor' ?>
                </span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <?php foreach ($menuItems as $item): ?>
                <?php if (isset($item['divider'])): ?>
                    <div class="sidebar-divider"></div>
                <?php elseif (!$item['admin_only'] || $isAdmin): ?>
                    <a href="<?= $item['page'] ?>.php" 
                       class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="/index.php" target="_blank" class="sidebar-link">
                <i class="fas fa-globe"></i>
                <span>Vezi site</span>
            </a>
            <a href="logout.php" class="sidebar-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Delogare</span>
            </a>
        </div>
    </aside>
    
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-content">
