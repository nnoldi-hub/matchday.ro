<?php 
require_once(__DIR__ . '/config/config.php');

// SEO Configuration for editorial calendar
$pageTitle = 'Calendar Editorial - MatchDay.ro';
$pageDescription = 'Calendarul editorial al MatchDay.ro cu programarea completă: cronici de meciuri, analize în avanpremieră, interviuri, transferuri și reportaje speciale.';
$pageKeywords = ['calendar editorial', 'program publicare', 'cronici meciuri', 'analize fotbal', 'interviuri', 'transferuri', 'reportaje'];
$pageType = 'website';

// Breadcrumbs for editorial calendar
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Calendar Editorial']
];

include(__DIR__ . '/includes/header.php'); 

// Generează calendarul pentru următoarele 4 săptămâni
function generateEditorialCalendar() {
    $calendar = [];
    $startDate = new DateTime('2025-09-01'); // Începe de luni
    
    // Meciuri importante programate (exemplu realist)
    $importantMatches = [
        '2025-09-03' => ['FCSB vs CFR Cluj', 'Liga 1', '20:30'],
        '2025-09-07' => ['Liverpool vs Manchester United', 'Premier League', '18:30'],
        '2025-09-10' => ['România vs Ucraina', 'Nations League', '21:45'],
        '2025-09-14' => ['Real Madrid vs Barcelona', 'La Liga', '22:00'],
        '2025-09-17' => ['Rapid vs Universitatea Craiova', 'Liga 1', '20:00'],
        '2025-09-21' => ['Manchester City vs Arsenal', 'Premier League', '17:30'],
        '2025-09-24' => ['România vs Lituania', 'Nations League', '21:45'],
        '2025-09-28' => ['Inter vs AC Milan', 'Serie A', '21:45']
    ];
    
    // Template-uri de conținut pentru fiecare zi a săptămânii
    $contentTemplates = [
        'Luni' => [
            'type' => 'Rezumatul săptămânii',
            'icon' => 'fas fa-calendar-week',
            'color' => 'primary',
            'description' => 'Analiză completă a weekendului fotbalistic'
        ],
        'Marți' => [
            'type' => 'Analize tactice',
            'icon' => 'fas fa-chess',
            'color' => 'info',
            'description' => 'Analize detaliate și statistici avansate'
        ],
        'Miercuri' => [
            'type' => 'Interviuri & Reportaje',
            'icon' => 'fas fa-microphone',
            'color' => 'success',
            'description' => 'Interviuri exclusive și reportaje speciale'
        ],
        'Joi' => [
            'type' => 'Știri & Transferuri',
            'icon' => 'fas fa-exchange-alt',
            'color' => 'warning',
            'description' => 'Ultimele știri și mișcări pe piața transferurilor'
        ],
        'Vineri' => [
            'type' => 'Avanpremiere weekend',
            'icon' => 'fas fa-binoculars',
            'color' => 'danger',
            'description' => 'Analize în avanpremieră pentru meciurile de weekend'
        ],
        'Sâmbătă' => [
            'type' => 'Live Updates & Cronici',
            'icon' => 'fas fa-broadcast-tower',
            'color' => 'success',
            'description' => 'Urmărire live și cronici de meciuri'
        ],
        'Duminică' => [
            'type' => 'Cronici & Reacții',
            'icon' => 'fas fa-newspaper',
            'color' => 'primary',
            'description' => 'Cronici detaliate și reacții post-meci'
        ]
    ];
    
    // Generează 4 săptămâni
    for ($week = 0; $week < 4; $week++) {
        $weekData = [
            'week_number' => $week + 1,
            'start_date' => clone $startDate,
            'days' => []
        ];
        
        for ($day = 0; $day < 7; $day++) {
            $currentDate = clone $startDate;
            $currentDate->add(new DateInterval('P' . ($week * 7 + $day) . 'D'));
            
            $dayName = $currentDate->format('l');
            $dayNameRo = [
                'Monday' => 'Luni',
                'Tuesday' => 'Marți', 
                'Wednesday' => 'Miercuri',
                'Thursday' => 'Joi',
                'Friday' => 'Vineri',
                'Saturday' => 'Sâmbătă',
                'Sunday' => 'Duminică'
            ][$dayName];
            
            $dateStr = $currentDate->format('Y-m-d');
            $template = $contentTemplates[$dayNameRo];
            
            $dayData = [
                'date' => $currentDate,
                'day_name' => $dayNameRo,
                'template' => $template,
                'matches' => isset($importantMatches[$dateStr]) ? $importantMatches[$dateStr] : null,
                'articles' => generateArticleIdeas($dayNameRo, $currentDate, $importantMatches[$dateStr] ?? null)
            ];
            
            $weekData['days'][] = $dayData;
        }
        
        $calendar[] = $weekData;
    }
    
    return $calendar;
}

function generateArticleIdeas($dayName, $date, $match = null) {
    $ideas = [];
    
    switch ($dayName) {
        case 'Luni':
            $ideas = [
                'Rezumatul weekendului în Liga 1',
                'Top 5 goluri ale etapei',
                'Clasamentele după etapa ' . $date->format('W'),
                'Jucătorul săptămânii în fotbalul românesc'
            ];
            break;
            
        case 'Marți':
            $ideas = [
                'Analiza tactică: Evoluția echipelor din Liga 1',
                'Statistici avansate: xG, pase decisive, interceptări',
                'Comparatie: Liga 1 vs alte campionate din regiune',
                'Focus pe tinerii jucători români'
            ];
            break;
            
        case 'Miercuri':
            $ideas = [
                'Interviu exclusiv cu un antrenor din Liga 1',
                'Reportaj: Ziua dintr-un club de fotbal',
                'Povestea unui transfer reușit',
                'Analiza infrastructurii fotbalului românesc'
            ];
            break;
            
        case 'Joi':
            $ideas = [
                'Transferuri: Cei mai urmăriți jucători',
                'Știri din lotul naționalei României',
                'Update-uri de la echipele europene',
                'Situația financiară a cluburilor din Liga 1'
            ];
            break;
            
        case 'Vineri':
            if ($match) {
                $ideas[] = "Avanpremieră: {$match[0]} - {$match[1]}";
                $ideas[] = "Predictii și cotele pentru {$match[0]}";
                $ideas[] = "Analiza loturilor: {$match[0]}";
            }
            $ideas = array_merge($ideas, [
                'Programul complet al weekendului',
                'Jucătorii de urmărit în etapa viitoare',
                'Predicții pentru meciurile din Liga 1'
            ]);
            break;
            
        case 'Sâmbătă':
            $ideas = [
                'Live text: Meciurile zilei',
                'Cronici rapide din Liga 1',
                'Update-uri în timp real',
                'Galerie foto: Momentele zilei'
            ];
            if ($match) {
                $ideas[] = "Cronică live: {$match[0]}";
            }
            break;
            
        case 'Duminică':
            $ideas = [
                'Cronici detaliate ale meciurilor',
                'Reacții post-meci: antrenori și jucători',
                'Analiza arbitrajelor din etapă',
                'Impactul rezultatelor asupra clasamentului'
            ];
            if ($match) {
                $ideas[] = "Analiza post-meci: {$match[0]}";
            }
            break;
    }
    
    return $ideas;
}

$calendar = generateEditorialCalendar();
?>

<main class="container my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php"><i class="fas fa-home"></i> Acasă</a></li>
                    <li class="breadcrumb-item active">Calendar Editorial</li>
                </ol>
            </nav>
            
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h2 fw-bold mb-1">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Calendar Editorial
                    </h1>
                    <p class="text-muted mb-0">
                        Programarea completă a conținutului MatchDay.ro pentru următoarele 4 săptămâni
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge bg-primary fs-6 px-3 py-2">
                        <i class="fas fa-clock me-1"></i>
                        Actualizat: <?= date('d.m.Y') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h6 mb-0"><i class="fas fa-info-circle me-2"></i>Legenda tipurilor de conținut</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $contentTypes = [
                            'Luni' => ['type' => 'Rezumatul săptămânii', 'icon' => 'fas fa-calendar-week', 'color' => 'primary'],
                            'Marți' => ['type' => 'Analize tactice', 'icon' => 'fas fa-chess', 'color' => 'info'],
                            'Miercuri' => ['type' => 'Interviuri & Reportaje', 'icon' => 'fas fa-microphone', 'color' => 'success'],
                            'Joi' => ['type' => 'Știri & Transferuri', 'icon' => 'fas fa-exchange-alt', 'color' => 'warning'],
                            'Vineri' => ['type' => 'Avanpremiere weekend', 'icon' => 'fas fa-binoculars', 'color' => 'danger'],
                            'Sâmbătă' => ['type' => 'Live Updates & Cronici', 'icon' => 'fas fa-broadcast-tower', 'color' => 'success'],
                            'Duminică' => ['type' => 'Cronici & Reacții', 'icon' => 'fas fa-newspaper', 'color' => 'primary']
                        ];
                        
                        foreach ($contentTypes as $day => $info):
                        ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?= $info['color'] ?> me-2">
                                    <i class="<?= $info['icon'] ?>"></i>
                                </span>
                                <small class="fw-medium"><?= $info['type'] ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Weeks -->
    <?php foreach ($calendar as $weekIndex => $week): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-calendar-week me-2"></i>
                            Săptămâna <?= $week['week_number'] ?>
                        </h3>
                        <small class="opacity-75">
                            <?= $week['start_date']->add(new DateInterval('P' . ($weekIndex * 7) . 'D'))->format('d.m') ?> - 
                            <?= $week['start_date']->add(new DateInterval('P6D'))->format('d.m.Y') ?>
                        </small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <?php foreach ($week['days'] as $dayIndex => $day): ?>
                        <div class="col-lg-12 border-bottom">
                            <div class="p-3 <?= $day['date']->format('Y-m-d') === date('Y-m-d') ? 'bg-light' : '' ?>">
                                <!-- Day Header -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="fw-bold text-<?= $day['template']['color'] ?>">
                                                <?= $day['day_name'] ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= $day['date']->format('d.m.Y') ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= $day['template']['color'] ?> me-2">
                                            <i class="<?= $day['template']['icon'] ?> me-1"></i>
                                            <?= $day['template']['type'] ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($day['matches']): ?>
                                    <div class="text-end">
                                        <div class="badge bg-danger">
                                            <i class="fas fa-futbol me-1"></i>
                                            <?= $day['matches'][2] ?>
                                        </div>
                                        <div class="small fw-medium text-primary mt-1">
                                            <?= $day['matches'][0] ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= $day['matches'][1] ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content Ideas -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="text-muted mb-2">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Idei de articole:
                                        </h6>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($day['articles'] as $idea): ?>
                                            <li class="mb-1">
                                                <i class="fas fa-chevron-right text-<?= $day['template']['color'] ?> me-2" style="font-size: 0.8rem;"></i>
                                                <span class="small"><?= $idea ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted mb-2">
                                            <i class="fas fa-tasks me-1"></i>
                                            Programare:
                                        </h6>
                                        <div class="small text-muted">
                                            <div class="mb-1">
                                                <i class="fas fa-clock text-primary me-1"></i>
                                                <strong>Publicare:</strong> 
                                                <?php
                                                $publishTimes = [
                                                    'Luni' => '09:00',
                                                    'Marți' => '10:00', 
                                                    'Miercuri' => '11:00',
                                                    'Joi' => '14:00',
                                                    'Vineri' => '16:00',
                                                    'Sâmbătă' => '12:00',
                                                    'Duminică' => '20:00'
                                                ];
                                                echo $publishTimes[$day['day_name']];
                                                ?>
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-user text-success me-1"></i>
                                                <strong>Responsabil:</strong> David C.
                                            </div>
                                            <div>
                                                <i class="fas fa-tag text-warning me-1"></i>
                                                <strong>Status:</strong> 
                                                <span class="badge bg-light text-dark">Programat</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted">
                                        <i class="fas fa-info me-1"></i>
                                        <?= $day['template']['description'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Statistics & Notes -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="h6 mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Statistici conținut
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="h4 text-primary mb-1">28</div>
                            <small class="text-muted">Articole programate</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-success mb-1">7</div>
                            <small class="text-muted">Tipuri de conținut</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="h4 text-warning mb-1">8</div>
                            <small class="text-muted">Meciuri importante</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-info mb-1">4</div>
                            <small class="text-muted">Săptămâni planificate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="h6 mb-0">
                        <i class="fas fa-sticky-note me-2"></i>
                        Note editoriale
                    </h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Programarea respectă consistența zilnică</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Conținutul e diversificat pe categorii</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Meciurile importante sunt acoperite</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            <small>Ajustări necesare în funcție de actualitate</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Structured Data for Editorial Calendar -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Calendar Editorial MatchDay.ro",
  "description": "Programarea completă a conținutului editorial pentru MatchDay.ro cu cronici, analize și interviuri.",
  "url": "https://matchday.ro/calendar-editorial.php",
  "mainEntity": {
    "@type": "Organization",
    "name": "MatchDay.ro",
    "description": "Jurnal de fotbal cu calendar editorial structurat pentru conținut de calitate."
  }
}
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>
