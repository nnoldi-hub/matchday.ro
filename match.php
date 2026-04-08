<?php
/**
 * Match Details Page - MatchDay.ro
 * Public page for viewing complete match information
 */
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/LiveScores.php');
require_once(__DIR__ . '/includes/Stats.php');

// Get match ID
$matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$matchId) {
    header('Location: live.php');
    exit;
}

// Get match details
$match = LiveScores::getMatchById($matchId);

if (!$match) {
    header('HTTP/1.0 404 Not Found');
    include(__DIR__ . '/404.php');
    exit;
}

// Track page visit
Stats::trackView(null, 'match_' . $matchId);

// Determine match status
$isLive = in_array($match['status'] ?? '', ['live', '1H', '2H', 'HT', 'ET', 'P']);
$isScheduled = ($match['status'] ?? '') === 'scheduled';
$isFinished = ($match['status'] ?? '') === 'finished';

// Format date
$kickoffDate = isset($match['kickoff']) ? date('j F Y', strtotime($match['kickoff'])) : '';
$kickoffTime = isset($match['kickoff']) ? date('H:i', strtotime($match['kickoff'])) : '';

// SEO
$pageTitle = $match['home_team'] . ' vs ' . $match['away_team'] . ' | ' . SITE_NAME;
$pageDescription = ($match['competition'] ?? 'Meci') . ': ' . $match['home_team'] . ' vs ' . $match['away_team'] . '. Scoruri live, marcatori, cartonașe și detalii complete.';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => './index.php'],
    ['name' => 'Meciuri Live', 'url' => './live.php'],
    ['name' => $match['home_team'] . ' vs ' . $match['away_team'], 'url' => '#']
];

include(__DIR__ . '/includes/header.php');
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Match Header Card -->
            <div class="match-detail-card <?= $isLive ? 'is-live' : '' ?>">
                <!-- Competition & Status -->
                <div class="match-detail-header">
                    <span class="competition-name"><?= htmlspecialchars($match['competition'] ?? '') ?></span>
                    <?php if ($isLive): ?>
                    <span class="status-badge live"><i class="fas fa-circle"></i> LIVE <?= $match['minute'] ?? '' ?>'</span>
                    <?php elseif ($isFinished): ?>
                    <span class="status-badge finished">Final</span>
                    <?php else: ?>
                    <span class="status-badge scheduled"><?= $kickoffTime ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Teams & Score -->
                <div class="match-detail-teams">
                    <div class="team-block home">
                        <div class="team-name"><?= htmlspecialchars($match['home_team']) ?></div>
                        <?php if (!empty($match['home_scorers'])): ?>
                        <div class="team-scorers">
                            <?php foreach ($match['home_scorers'] as $scorer): ?>
                            <span class="scorer"><i class="fas fa-futbol"></i> <?= htmlspecialchars($scorer) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="score-block">
                        <?php if (!$isScheduled): ?>
                        <div class="score">
                            <span class="score-home <?= ($match['home_score'] ?? 0) > ($match['away_score'] ?? 0) ? 'winning' : '' ?>"><?= $match['home_score'] ?? 0 ?></span>
                            <span class="score-separator">-</span>
                            <span class="score-away <?= ($match['away_score'] ?? 0) > ($match['home_score'] ?? 0) ? 'winning' : '' ?>"><?= $match['away_score'] ?? 0 ?></span>
                        </div>
                        <?php else: ?>
                        <div class="vs-text">VS</div>
                        <?php endif; ?>
                        <div class="match-date"><?= $kickoffDate ?></div>
                    </div>
                    
                    <div class="team-block away">
                        <div class="team-name"><?= htmlspecialchars($match['away_team']) ?></div>
                        <?php if (!empty($match['away_scorers'])): ?>
                        <div class="team-scorers">
                            <?php foreach ($match['away_scorers'] as $scorer): ?>
                            <span class="scorer"><i class="fas fa-futbol"></i> <?= htmlspecialchars($scorer) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Venue -->
                <?php if (!empty($match['venue'])): ?>
                <div class="match-venue">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($match['venue']) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Cards Section -->
            <?php 
            $hasYellowCards = !empty($match['yellow_cards_home']) || !empty($match['yellow_cards_away']);
            $hasRedCards = !empty($match['red_cards_home']) || !empty($match['red_cards_away']);
            if ($hasYellowCards || $hasRedCards): 
            ?>
            <div class="match-section-card">
                <h5 class="section-title"><i class="fas fa-id-card me-2"></i>Cartonașe</h5>
                <div class="cards-grid">
                    <!-- Home Team -->
                    <div class="cards-team">
                        <div class="cards-team-name"><?= htmlspecialchars($match['home_team']) ?></div>
                        <?php if (!empty($match['yellow_cards_home'])): ?>
                        <div class="cards-list yellow">
                            <?php foreach ($match['yellow_cards_home'] as $card): ?>
                            <span class="card-item"><span class="card-icon yellow"></span> <?= htmlspecialchars($card) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($match['red_cards_home'])): ?>
                        <div class="cards-list red">
                            <?php foreach ($match['red_cards_home'] as $card): ?>
                            <span class="card-item"><span class="card-icon red"></span> <?= htmlspecialchars($card) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Away Team -->
                    <div class="cards-team">
                        <div class="cards-team-name"><?= htmlspecialchars($match['away_team']) ?></div>
                        <?php if (!empty($match['yellow_cards_away'])): ?>
                        <div class="cards-list yellow">
                            <?php foreach ($match['yellow_cards_away'] as $card): ?>
                            <span class="card-item"><span class="card-icon yellow"></span> <?= htmlspecialchars($card) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($match['red_cards_away'])): ?>
                        <div class="cards-list red">
                            <?php foreach ($match['red_cards_away'] as $card): ?>
                            <span class="card-item"><span class="card-icon red"></span> <?= htmlspecialchars($card) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Referee Section -->
            <?php if (!empty($match['referee']) || !empty($match['referee_team'])): ?>
            <div class="match-section-card">
                <h5 class="section-title"><i class="fas fa-whistle me-2"></i>Brigada de arbitri</h5>
                <div class="referee-list">
                    <?php if (!empty($match['referee'])): ?>
                    <div class="referee-item main">
                        <span class="referee-role">Arbitru principal</span>
                        <span class="referee-name"><?= htmlspecialchars($match['referee']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($match['referee_team'])): ?>
                    <?php foreach ($match['referee_team'] as $ref): ?>
                    <div class="referee-item">
                        <span class="referee-name"><?= htmlspecialchars($ref) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Related Article -->
            <?php if (!empty($match['article_slug'])): ?>
            <div class="match-section-card">
                <h5 class="section-title"><i class="fas fa-newspaper me-2"></i>Articol despre meci</h5>
                <a href="post.php?slug=<?= htmlspecialchars($match['article_slug']) ?>" class="related-article-link">
                    <i class="fas fa-arrow-right me-2"></i><?= htmlspecialchars($match['article_title'] ?? 'Citește articolul') ?>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Back Link -->
            <div class="text-center mt-4">
                <a href="live.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Înapoi la Meciuri Live
                </a>
            </div>
            
        </div>
    </div>
</div>

<style>
/* Match Detail Card */
.match-detail-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.match-detail-card.is-live {
    border: 3px solid #e53e3e;
    box-shadow: 0 4px 25px rgba(229, 62, 62, 0.2);
}

.match-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    color: #fff;
}

.competition-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.status-badge.live {
    background: #e53e3e;
    color: #fff;
    animation: pulse 1.5s ease-in-out infinite;
}

.status-badge.finished {
    background: #718096;
    color: #fff;
}

.status-badge.scheduled {
    background: #4299e1;
    color: #fff;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.match-detail-teams {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 2rem 1.5rem;
    gap: 1rem;
}

.team-block {
    flex: 1;
    text-align: center;
}

.team-block .team-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 0.75rem;
}

.team-scorers {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.team-scorers .scorer {
    font-size: 0.8rem;
    color: #4a5568;
}

.team-scorers .scorer i {
    color: #48bb78;
    margin-right: 0.3rem;
    font-size: 0.7rem;
}

.score-block {
    text-align: center;
    padding: 0 1rem;
}

.score {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.score span {
    font-size: 2.5rem;
    font-weight: 800;
}

.score-home, .score-away {
    background: #e2e8f0;
    padding: 0.3rem 1rem;
    border-radius: 8px;
    min-width: 60px;
}

.score-home.winning, .score-away.winning {
    background: #48bb78;
    color: #fff;
}

.score-separator {
    color: #a0aec0;
}

.vs-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: #a0aec0;
}

.match-date {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #718096;
}

.match-venue {
    text-align: center;
    padding: 1rem;
    background: #f7fafc;
    border-top: 1px solid #e2e8f0;
    color: #4a5568;
    font-size: 0.9rem;
}

.match-venue i {
    color: #e53e3e;
    margin-right: 0.5rem;
}

/* Section Cards */
.match-section-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.match-section-card .section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
}

/* Cards Grid */
.cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.cards-team-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #2d3748;
}

.cards-list {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.card-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #4a5568;
}

.card-icon {
    display: inline-block;
    width: 14px;
    height: 18px;
    border-radius: 2px;
}

.card-icon.yellow {
    background: #ecc94b;
}

.card-icon.red {
    background: #e53e3e;
}

/* Referee List */
.referee-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.referee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.referee-item:last-child {
    border-bottom: none;
}

.referee-item.main {
    background: #f7fafc;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.referee-role {
    font-size: 0.8rem;
    color: #718096;
}

.referee-name {
    font-weight: 600;
    color: #2d3748;
}

/* Related Article Link */
.related-article-link {
    display: block;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
}

.related-article-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Responsive */
@media (max-width: 576px) {
    .match-detail-teams {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .team-block.away {
        order: 3;
    }
    
    .score-block {
        order: 2;
    }
    
    .cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include(__DIR__ . '/includes/footer.php'); ?>
