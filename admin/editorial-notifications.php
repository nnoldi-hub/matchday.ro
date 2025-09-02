<?php
// Sistemul de notificări editoriale pentru MatchDay.ro
class EditorialNotifications {
    
    private $dataDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    // Verifică și returnează notificările de deadline
    public function getDeadlineNotifications() {
        $notifications = [];
        $plan = $this->getEditorialPlan();
        
        if (!$plan) return $notifications;
        
        $today = new DateTime();
        $tomorrow = new DateTime('tomorrow');
        $in2Days = new DateTime('+2 days');
        
        foreach ($plan as $article) {
            $articleDate = new DateTime($article['date']);
            $status = $article['status'] ?? 'planned';
            
            // Skip articole deja publicate
            if ($status === 'published') continue;
            
            $daysUntil = $today->diff($articleDate)->days;
            $isPast = $articleDate < $today;
            
            if ($isPast && $status !== 'published') {
                $notifications[] = [
                    'type' => 'overdue',
                    'priority' => 'high',
                    'title' => 'Articol întârziat',
                    'message' => "Articolul pentru {$article['day_name']}, {$articleDate->format('d.m.Y')} a trecut de deadline",
                    'article' => $article,
                    'days' => $daysUntil,
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => 'danger'
                ];
            } elseif ($daysUntil === 0) { // Azi
                $notifications[] = [
                    'type' => 'today',
                    'priority' => 'high',
                    'title' => 'Publicare azi',
                    'message' => "Articolul pentru {$article['content_type']} trebuie publicat astăzi",
                    'article' => $article,
                    'days' => 0,
                    'icon' => 'fas fa-calendar-day',
                    'color' => 'warning'
                ];
            } elseif ($daysUntil === 1) { // Mâine
                $notifications[] = [
                    'type' => 'tomorrow',
                    'priority' => 'medium',
                    'title' => 'Publicare mâine',
                    'message' => "Articolul pentru {$article['content_type']} trebuie publicat mâine",
                    'article' => $article,
                    'days' => 1,
                    'icon' => 'fas fa-calendar-plus',
                    'color' => 'info'
                ];
            } elseif ($daysUntil === 2 && $status === 'planned') { // În 2 zile și încă nu e început
                $notifications[] = [
                    'type' => 'upcoming',
                    'priority' => 'low',
                    'title' => 'Start în curând',
                    'message' => "Ar trebui să începi lucrul la articolul pentru {$articleDate->format('d.m.Y')}",
                    'article' => $article,
                    'days' => 2,
                    'icon' => 'fas fa-clock',
                    'color' => 'primary'
                ];
            }
        }
        
        // Sortează notificările după prioritate
        usort($notifications, function($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorities[$b['priority']] - $priorities[$a['priority']];
        });
        
        return $notifications;
    }
    
    // Verifică progresul săptămânal
    public function getWeeklyProgress() {
        $plan = $this->getEditorialPlan();
        if (!$plan) return null;
        
        $today = new DateTime();
        $startOfWeek = clone $today;
        $startOfWeek->modify('monday this week');
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        
        $weekArticles = array_filter($plan, function($article) use ($startOfWeek, $endOfWeek) {
            $articleDate = new DateTime($article['date']);
            return $articleDate >= $startOfWeek && $articleDate <= $endOfWeek;
        });
        
        $total = count($weekArticles);
        $published = count(array_filter($weekArticles, function($a) { 
            return ($a['status'] ?? 'planned') === 'published'; 
        }));
        $inProgress = count(array_filter($weekArticles, function($a) { 
            return in_array($a['status'] ?? 'planned', ['in_progress', 'review']); 
        }));
        
        $completionRate = $total > 0 ? round(($published / $total) * 100) : 0;
        
        return [
            'week_range' => $startOfWeek->format('d.m') . ' - ' . $endOfWeek->format('d.m.Y'),
            'total' => $total,
            'published' => $published,
            'in_progress' => $inProgress,
            'planned' => $total - $published - $inProgress,
            'completion_rate' => $completionRate
        ];
    }
    
    // Sugestii pentru optimizarea planului
    public function getOptimizationSuggestions() {
        $suggestions = [];
        $plan = $this->getEditorialPlan();
        
        if (!$plan) return $suggestions;
        
        // Verifică articolele fără titlu
        $withoutTitle = array_filter($plan, function($article) {
            return empty($article['title']) && ($article['status'] ?? 'planned') !== 'published';
        });
        
        if (count($withoutTitle) > 0) {
            $suggestions[] = [
                'type' => 'missing_titles',
                'priority' => 'medium',
                'title' => 'Titluri lipsă',
                'message' => count($withoutTitle) . ' articole nu au titlu definit',
                'icon' => 'fas fa-heading',
                'color' => 'warning',
                'count' => count($withoutTitle)
            ];
        }
        
        // Verifică articolele fără categorie
        $withoutCategory = array_filter($plan, function($article) {
            return empty($article['category']) && ($article['status'] ?? 'planned') !== 'published';
        });
        
        if (count($withoutCategory) > 0) {
            $suggestions[] = [
                'type' => 'missing_categories',
                'priority' => 'low',
                'title' => 'Categorii lipsă',
                'message' => count($withoutCategory) . ' articole nu au categorie definită',
                'icon' => 'fas fa-folder',
                'color' => 'info',
                'count' => count($withoutCategory)
            ];
        }
        
        // Verifică distribuția tipurilor de conținut
        $contentTypes = [];
        foreach ($plan as $article) {
            $type = $article['content_type'] ?? 'Nedefinit';
            $contentTypes[$type] = ($contentTypes[$type] ?? 0) + 1;
        }
        
        // Caută dezechilibru în tipurile de conținut
        $avgPerType = count($plan) / count($contentTypes);
        foreach ($contentTypes as $type => $count) {
            if ($count < $avgPerType * 0.5) { // Mai puțin de jumătate din medie
                $suggestions[] = [
                    'type' => 'content_balance',
                    'priority' => 'low',
                    'title' => 'Dezechilibru conținut',
                    'message' => "Tipul '{$type}' pare subreprezentant ({$count} articole)",
                    'icon' => 'fas fa-balance-scale',
                    'color' => 'secondary'
                ];
            }
        }
        
        return $suggestions;
    }
    
    // Generate productivity report
    public function getProductivityReport($weeks = 4) {
        $plan = $this->getEditorialPlan();
        if (!$plan) return null;
        
        $today = new DateTime();
        $startDate = clone $today;
        $startDate->modify("-{$weeks} weeks");
        
        $articlesInPeriod = array_filter($plan, function($article) use ($startDate, $today) {
            $articleDate = new DateTime($article['date']);
            return $articleDate >= $startDate && $articleDate <= $today;
        });
        
        $totalPlanned = count($articlesInPeriod);
        $published = count(array_filter($articlesInPeriod, function($a) {
            return ($a['status'] ?? 'planned') === 'published';
        }));
        
        $onTime = 0;
        $late = 0;
        
        foreach ($articlesInPeriod as $article) {
            $articleDate = new DateTime($article['date']);
            $status = $article['status'] ?? 'planned';
            
            if ($status === 'published') {
                // Presupunem că a fost publicat la timp dacă e marcat ca publicat
                $onTime++;
            } elseif ($articleDate < $today) {
                $late++;
            }
        }
        
        $productivity = $totalPlanned > 0 ? round(($published / $totalPlanned) * 100) : 0;
        $punctuality = ($onTime + $late) > 0 ? round(($onTime / ($onTime + $late)) * 100) : 100;
        
        return [
            'period' => $weeks . ' săptămâni',
            'total_planned' => $totalPlanned,
            'published' => $published,
            'on_time' => $onTime,
            'late' => $late,
            'productivity_rate' => $productivity,
            'punctuality_rate' => $punctuality,
            'avg_per_week' => $weeks > 0 ? round($published / $weeks, 1) : 0
        ];
    }
    
    private function getEditorialPlan() {
        $planFile = $this->dataDir . '/editorial-plan.json';
        
        if (!file_exists($planFile)) {
            return null;
        }
        
        $content = file_get_contents($planFile);
        return json_decode($content, true) ?: null;
    }
}

// API endpoint pentru notificări
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    $notifications = new EditorialNotifications();
    
    switch ($_GET['api']) {
        case 'notifications':
            echo json_encode($notifications->getDeadlineNotifications());
            break;
            
        case 'weekly-progress':
            echo json_encode($notifications->getWeeklyProgress());
            break;
            
        case 'suggestions':
            echo json_encode($notifications->getOptimizationSuggestions());
            break;
            
        case 'productivity':
            $weeks = intval($_GET['weeks'] ?? 4);
            echo json_encode($notifications->getProductivityReport($weeks));
            break;
            
        default:
            echo json_encode(['error' => 'API endpoint invalid']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Notificări Editoriale - MatchDay.ro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-badge {
            position: relative;
            top: -2px;
            font-size: 0.7rem;
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .metric-card {
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 fw-bold">
                    <i class="fas fa-bell text-warning me-2"></i>
                    Dashboard Notificări Editoriale
                </h1>
                <p class="text-muted">Monitorizează deadline-urile și progresul pentru MatchDay.ro</p>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Notificări Active
                            <span class="badge bg-danger notification-badge" id="notification-count">0</span>
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshNotifications()">
                            <i class="fas fa-sync me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body" id="notifications-container">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Se încarcă notificările...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Weekly Progress -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm metric-card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar-week me-2"></i>
                            Progresul săptămânii
                        </h6>
                    </div>
                    <div class="card-body" id="weekly-progress">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm metric-card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Productivitate (4 săptămâni)
                        </h6>
                    </div>
                    <div class="card-body" id="productivity-report">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Optimization Suggestions -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Sugestii de optimizare
                        </h5>
                    </div>
                    <div class="card-body" id="suggestions-container">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Se încarcă sugestiile...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load all data on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshNotifications();
            loadWeeklyProgress();
            loadProductivityReport();
            loadSuggestions();
            
            // Auto-refresh every 5 minutes
            setInterval(refreshNotifications, 300000);
        });
        
        function refreshNotifications() {
            fetch('editorial-notifications.php?api=notifications')
                .then(response => response.json())
                .then(notifications => {
                    const container = document.getElementById('notifications-container');
                    const countBadge = document.getElementById('notification-count');
                    
                    countBadge.textContent = notifications.length;
                    
                    if (notifications.length === 0) {
                        container.innerHTML = `
                            <div class="text-center text-success py-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">Nu ai notificări urgente! Excellent!</p>
                            </div>
                        `;
                        return;
                    }
                    
                    const html = notifications.map(notification => `
                        <div class="alert alert-${notification.color} d-flex align-items-start">
                            <i class="${notification.icon} me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1">${notification.title}</h6>
                                <p class="mb-1">${notification.message}</p>
                                <small class="opacity-75">
                                    <strong>${notification.article.content_type}</strong> - ${notification.article.date}
                                </small>
                            </div>
                        </div>
                    `).join('');
                    
                    container.innerHTML = html;
                });
        }
        
        function loadWeeklyProgress() {
            fetch('editorial-notifications.php?api=weekly-progress')
                .then(response => response.json())
                .then(progress => {
                    const container = document.getElementById('weekly-progress');
                    
                    if (!progress) {
                        container.innerHTML = '<p class="text-muted mb-0">Nu sunt date disponibile</p>';
                        return;
                    }
                    
                    const completionRate = progress.completion_rate;
                    const progressColor = completionRate >= 80 ? 'success' : 
                                        completionRate >= 60 ? 'warning' : 'danger';
                    
                    container.innerHTML = `
                        <div class="text-center mb-3">
                            <div class="h2 text-${progressColor} mb-1">${completionRate}%</div>
                            <small class="text-muted">${progress.week_range}</small>
                        </div>
                        
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-${progressColor}" style="width: ${completionRate}%"></div>
                        </div>
                        
                        <div class="row text-center small">
                            <div class="col-4">
                                <div class="fw-bold text-success">${progress.published}</div>
                                <div class="text-muted">Publicate</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-warning">${progress.in_progress}</div>
                                <div class="text-muted">În progres</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-secondary">${progress.planned}</div>
                                <div class="text-muted">Planificate</div>
                            </div>
                        </div>
                    `;
                });
        }
        
        function loadProductivityReport() {
            fetch('editorial-notifications.php?api=productivity&weeks=4')
                .then(response => response.json())
                .then(report => {
                    const container = document.getElementById('productivity-report');
                    
                    if (!report) {
                        container.innerHTML = '<p class="text-muted mb-0">Nu sunt date disponibile</p>';
                        return;
                    }
                    
                    const productivityColor = report.productivity_rate >= 80 ? 'success' : 
                                            report.productivity_rate >= 60 ? 'warning' : 'danger';
                    
                    container.innerHTML = `
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="h4 text-${productivityColor} mb-1">${report.productivity_rate}%</div>
                                <small class="text-muted">Productivitate</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-info mb-1">${report.avg_per_week}</div>
                                <small class="text-muted">Articole/săpt</small>
                            </div>
                        </div>
                        
                        <div class="row text-center small">
                            <div class="col-6">
                                <div class="fw-bold text-primary">${report.published}/${report.total_planned}</div>
                                <div class="text-muted">Publicate</div>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold text-success">${report.on_time}</div>
                                <div class="text-muted">La timp</div>
                            </div>
                        </div>
                    `;
                });
        }
        
        function loadSuggestions() {
            fetch('editorial-notifications.php?api=suggestions')
                .then(response => response.json())
                .then(suggestions => {
                    const container = document.getElementById('suggestions-container');
                    
                    if (suggestions.length === 0) {
                        container.innerHTML = `
                            <div class="text-center text-success py-3">
                                <i class="fas fa-thumbs-up fa-2x mb-2"></i>
                                <p class="mb-0">Planul editorial este complet și bine structurat!</p>
                            </div>
                        `;
                        return;
                    }
                    
                    const html = suggestions.map(suggestion => `
                        <div class="alert alert-${suggestion.color} d-flex align-items-center">
                            <i class="${suggestion.icon} me-3"></i>
                            <div>
                                <strong>${suggestion.title}:</strong> ${suggestion.message}
                            </div>
                        </div>
                    `).join('');
                    
                    container.innerHTML = html;
                });
        }
    </script>
</body>
</html>
