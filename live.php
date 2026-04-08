<?php
/**
 * Live Scores Page - MatchDay.ro
 * Public page for viewing live matches and today's schedule
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/LiveScores.php');
require_once(__DIR__ . '/includes/Settings.php');
require_once(__DIR__ . '/includes/Stats.php');

// Track page visit
Stats::trackView(null, 'livescores');

// SEO
$pageTitle = 'Meciuri Live - Scoruri în timp real | ' . SITE_NAME;
$pageDescription = 'Urmărește scorurile live și programul meciurilor de fotbal. Liga 1, Champions League, Europa League și alte competiții.';
$pageKeywords = ['scoruri live', 'meciuri live', 'fotbal live', 'rezultate fotbal', 'liga 1 live'];

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Meciuri Live', 'url' => './live.php']
];

include(__DIR__ . '/includes/header.php');

// Get matches
$todayMatches = LiveScores::getTodayMatches();
$liveMatches = array_filter($todayMatches, fn($m) => in_array($m['status'] ?? '', ['live', '1H', '2H', 'HT', 'ET', 'P']));
$scheduledMatches = array_filter($todayMatches, fn($m) => ($m['status'] ?? '') === 'scheduled');
$finishedMatches = array_filter($todayMatches, fn($m) => ($m['status'] ?? '') === 'finished');

// Get upcoming matches (next 7 days)
$upcomingMatches = LiveScores::getManualMatches();
$futureMatches = array_filter($upcomingMatches, fn($m) => 
    isset($m['kickoff']) && 
    strtotime($m['kickoff']) > strtotime('today 23:59:59') &&
    strtotime($m['kickoff']) <= strtotime('+7 days')
);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-4">
                <h1 class="h3 mb-0">
                    <?php if (!empty($liveMatches)): ?>
                    <span class="live-badge pulse me-2"><i class="fas fa-circle"></i> LIVE</span>
                    <?php endif; ?>
                    <i class="fas fa-broadcast-tower me-2"></i>Meciuri Live
                </h1>
            </div>
            
            <?php if (!empty($liveMatches)): ?>
            <!-- Meciuri în desfășurare -->
            <div class="live-section mb-4">
                <h5 class="section-title text-danger">
                    <i class="fas fa-play-circle me-2"></i>În desfășurare
                </h5>
                <div class="matches-grid">
                    <?php foreach ($liveMatches as $match): ?>
                    <a href="match.php?id=<?= $match['id'] ?>" class="match-card live">
                        <div class="match-card-header">
                            <span class="competition"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                            <span class="match-minute-badge"><?= $match['minute'] ?? '' ?>'</span>
                        </div>
                        <div class="match-card-body">
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                                <span class="team-score <?= ($match['home_score'] ?? 0) > ($match['away_score'] ?? 0) ? 'winning' : '' ?>">
                                    <?= $match['home_score'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                                <span class="team-score <?= ($match['away_score'] ?? 0) > ($match['home_score'] ?? 0) ? 'winning' : '' ?>">
                                    <?= $match['away_score'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($match['venue'])): ?>
                        <div class="match-card-footer">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($match['venue']) ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($scheduledMatches)): ?>
            <!-- Meciuri programate azi -->
            <div class="live-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-clock me-2"></i>Programate azi
                </h5>
                <div class="matches-grid">
                    <?php foreach ($scheduledMatches as $match): 
                        $kickoff = isset($match['kickoff']) ? date('H:i', strtotime($match['kickoff'])) : '';
                    ?>
                    <a href="match.php?id=<?= $match['id'] ?>" class="match-card scheduled">
                        <div class="match-card-header">
                            <span class="competition"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                            <span class="match-time-badge"><?= $kickoff ?></span>
                        </div>
                        <div class="match-card-body">
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                                <span class="vs-text">vs</span>
                            </div>
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($match['venue'])): ?>
                        <div class="match-card-footer">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($match['venue']) ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($finishedMatches)): ?>
            <!-- Meciuri terminate azi -->
            <div class="live-section mb-4">
                <h5 class="section-title text-muted">
                    <i class="fas fa-flag-checkered me-2"></i>Terminate azi
                </h5>
                <div class="matches-grid">
                    <?php foreach ($finishedMatches as $match): ?>
                    <a href="match.php?id=<?= $match['id'] ?>" class="match-card finished">
                        <div class="match-card-header">
                            <span class="competition"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                            <span class="match-final-badge">Final</span>
                        </div>
                        <div class="match-card-body">
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                                <span class="team-score <?= ($match['home_score'] ?? 0) > ($match['away_score'] ?? 0) ? 'winning' : '' ?>">
                                    <?= $match['home_score'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="team-row">
                                <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                                <span class="team-score <?= ($match['away_score'] ?? 0) > ($match['home_score'] ?? 0) ? 'winning' : '' ?>">
                                    <?= $match['away_score'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (empty($todayMatches)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Nu există meciuri programate pentru azi.
            </div>
            <?php endif; ?>
            
            <?php if (!empty($futureMatches)): ?>
            <!-- Meciuri următoarele 7 zile -->
            <div class="live-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-calendar-alt me-2"></i>Următoarele 7 zile
                </h5>
                <div class="matches-list">
                    <?php 
                    // Group by date
                    $byDate = [];
                    foreach ($futureMatches as $m) {
                        $date = date('Y-m-d', strtotime($m['kickoff']));
                        $byDate[$date][] = $m;
                    }
                    ksort($byDate);
                    
                    foreach ($byDate as $date => $matches): 
                        $dateFormatted = strftime('%A, %d %B', strtotime($date));
                    ?>
                    <div class="date-group">
                        <div class="date-header"><?= ucfirst($dateFormatted) ?></div>
                        <?php foreach ($matches as $match): 
                            $kickoff = date('H:i', strtotime($match['kickoff']));
                        ?>
                        <div class="match-row">
                            <span class="match-time"><?= $kickoff ?></span>
                            <span class="match-teams-inline">
                                <?= htmlspecialchars($match['home_team']) ?> 
                                <small class="text-muted">vs</small> 
                                <?= htmlspecialchars($match['away_team']) ?>
                            </span>
                            <span class="match-comp"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <?php include(__DIR__ . '/includes/sidebars.php'); ?>
        </div>
    </div>
</div>

<style>
.live-section { margin-bottom: 2rem; }
.section-title {
    font-size: 1rem;
    font-weight: 600;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1rem;
}
.section-title.text-danger { border-color: #e53e3e; }

.matches-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

a.match-card {
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
}

a.match-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.match-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.match-card.live {
    border: 2px solid #e53e3e;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.15);
}

.match-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: #f7fafc;
    font-size: 0.75rem;
}

.match-card-header .competition {
    color: #718096;
    font-weight: 500;
}

.match-minute-badge {
    background: #e53e3e;
    color: #fff;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 700;
    animation: live-pulse 1.5s ease-in-out infinite;
}

.match-time-badge {
    background: #4299e1;
    color: #fff;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
}

.match-final-badge {
    background: #a0aec0;
    color: #fff;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.match-card-body {
    padding: 0.75rem;
}

.team-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
}

.team-row .team-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.team-row .team-score {
    background: #e2e8f0;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-weight: 700;
    min-width: 30px;
    text-align: center;
}

.team-row .team-score.winning {
    background: #48bb78;
    color: #fff;
}

.team-row .vs-text {
    color: #a0aec0;
    font-size: 0.8rem;
}

.match-card-footer {
    padding: 0.5rem 0.75rem;
    background: #f7fafc;
    font-size: 0.75rem;
    color: #718096;
    border-top: 1px solid #e2e8f0;
}

/* Matches list style */
.matches-list .date-group {
    margin-bottom: 1rem;
}

.matches-list .date-header {
    background: #2d3748;
    color: #fff;
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    border-radius: 6px 6px 0 0;
}

.matches-list .match-row {
    display: flex;
    align-items: center;
    padding: 0.6rem 1rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-top: none;
}

.matches-list .match-row:last-child {
    border-radius: 0 0 6px 6px;
}

.matches-list .match-time {
    background: #edf2f7;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
    margin-right: 1rem;
    min-width: 50px;
    text-align: center;
}

.matches-list .match-teams-inline {
    flex: 1;
    font-size: 0.9rem;
}

.matches-list .match-comp {
    color: #718096;
    font-size: 0.75rem;
}
</style>

<?php include(__DIR__ . '/includes/footer.php'); ?>
