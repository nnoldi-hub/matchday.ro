<?php
// Categories configuration for MatchDay.ro
return [
    'champions-league' => [
        'name' => 'Champions League',
        'description' => 'Știri și analize din Liga Campionilor',
        'color' => '#1a237e',
        'icon' => 'fas fa-trophy'
    ],
    'meciuri' => [
        'name' => 'Meciuri',
        'description' => 'Analize și rapoarte de la meciurile importante',
        'color' => '#28a745',
        'icon' => 'fas fa-futbol'
    ],
    'transferuri' => [
        'name' => 'Transferuri',
        'description' => 'Știri și analize despre transferurile din fotbal',
        'color' => '#007bff',
        'icon' => 'fas fa-exchange-alt'
    ],
    'opinii' => [
        'name' => 'Opinii',
        'description' => 'Analize personale și puncte de vedere',
        'color' => '#6f42c1',
        'icon' => 'fas fa-comment-alt'
    ],
    'interviuri' => [
        'name' => 'Interviuri',
        'description' => 'Interviuri cu jucători, antrenori și oficiali',
        'color' => '#fd7e14',
        'icon' => 'fas fa-microphone'
    ],
    'statistici' => [
        'name' => 'Statistici',
        'description' => 'Date și analize statistice din fotbal',
        'color' => '#20c997',
        'icon' => 'fas fa-chart-bar'
    ],
    'competitii' => [
        'name' => 'Competiții',
        'description' => 'Urmărirea ligilor și cupelor importante',
        'color' => '#dc3545',
        'icon' => 'fas fa-trophy'
    ],
    // === CLASAMENTE - Hub central pentru ligi internaționale ===
    'clasamente' => [
        'name' => 'Clasamente',
        'description' => 'Clasamente actualizate din marile campionate europene',
        'color' => '#0d6efd',
        'icon' => 'fas fa-list-ol',
        'parent' => null,
        'is_hub' => true
    ],
    'premier-league' => [
        'name' => 'Premier League',
        'description' => 'Clasament și statistici din Premier League - Anglia',
        'color' => '#3d195b',
        'icon' => 'fas fa-crown',
        'parent' => 'clasamente'
    ],
    'la-liga' => [
        'name' => 'La Liga',
        'description' => 'Clasament și statistici din La Liga - Spania',
        'color' => '#ee8707',
        'icon' => 'fas fa-sun',
        'parent' => 'clasamente'
    ],
    'serie-a' => [
        'name' => 'Serie A',
        'description' => 'Clasament și statistici din Serie A - Italia',
        'color' => '#024494',
        'icon' => 'fas fa-shield-alt',
        'parent' => 'clasamente'
    ],
    'bundesliga' => [
        'name' => 'Bundesliga',
        'description' => 'Clasament și statistici din Bundesliga - Germania',
        'color' => '#d20515',
        'icon' => 'fas fa-futbol',
        'parent' => 'clasamente'
    ],
    'ligue-1' => [
        'name' => 'Ligue 1',
        'description' => 'Clasament și statistici din Ligue 1 - Franța',
        'color' => '#091c3e',
        'icon' => 'fas fa-flag',
        'parent' => 'clasamente'
    ],
    'primeira-liga' => [
        'name' => 'Primeira Liga',
        'description' => 'Clasament și statistici din Primeira Liga - Portugalia',
        'color' => '#006847',
        'icon' => 'fas fa-anchor',
        'parent' => 'clasamente'
    ],
    'nb1-ungaria' => [
        'name' => 'NB I Ungaria',
        'description' => 'Clasament și statistici din NB I - Ungaria',
        'color' => '#436f4d',
        'icon' => 'fas fa-landmark',
        'parent' => 'clasamente'
    ]
];
?>
