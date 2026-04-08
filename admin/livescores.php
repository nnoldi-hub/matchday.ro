<?php
/**
 * Live Scores Admin
 * MatchDay.ro - Manage live matches manually
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/../includes/LiveScores.php');
require_once(__DIR__ . '/../includes/Post.php');

// Check admin access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user = User::getById($_SESSION['user_id']);
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_match') {
        $matchData = [
            'id' => $_POST['match_id'] ?: null,
            'competition' => $_POST['competition'],
            'home_team' => $_POST['home_team'],
            'away_team' => $_POST['away_team'],
            'home_score' => (int)$_POST['home_score'],
            'away_score' => (int)$_POST['away_score'],
            'status' => $_POST['status'],
            'minute' => $_POST['minute'] ?: null,
            'kickoff' => $_POST['kickoff_date'] . ' ' . $_POST['kickoff_time'],
            'home_scorers' => array_filter(explode(',', $_POST['home_scorers'] ?? '')),
            'away_scorers' => array_filter(explode(',', $_POST['away_scorers'] ?? '')),
            'article_id' => !empty($_POST['article_id']) ? (int)$_POST['article_id'] : null,
            'venue' => trim($_POST['venue'] ?? ''),
            'referee' => trim($_POST['referee'] ?? ''),
            'referee_team' => array_filter(array_map('trim', explode(',', $_POST['referee_team'] ?? ''))),
            'yellow_cards_home' => array_filter(array_map('trim', explode(',', $_POST['yellow_cards_home'] ?? ''))),
            'yellow_cards_away' => array_filter(array_map('trim', explode(',', $_POST['yellow_cards_away'] ?? ''))),
            'red_cards_home' => array_filter(array_map('trim', explode(',', $_POST['red_cards_home'] ?? ''))),
            'red_cards_away' => array_filter(array_map('trim', explode(',', $_POST['red_cards_away'] ?? ''))),
            'substitutions_home' => array_filter(array_map('trim', explode(',', $_POST['substitutions_home'] ?? ''))),
            'substitutions_away' => array_filter(array_map('trim', explode(',', $_POST['substitutions_away'] ?? '')))
        ];
        
        $id = LiveScores::saveManualMatch($matchData);
        $message = $matchData['id'] ? 'Meciul a fost actualizat!' : 'Meciul a fost adăugat!';
        $messageType = 'success';
        
        // Clear cache
        LiveScores::clearCache();
    }
    
    if ($action === 'delete_match' && isset($_POST['match_id'])) {
        LiveScores::deleteManualMatch((int)$_POST['match_id']);
        $message = 'Meciul a fost șters!';
        $messageType = 'success';
        LiveScores::clearCache();
    }
    
    if ($action === 'update_score' && isset($_POST['match_id'])) {
        $matchData = [
            'id' => (int)$_POST['match_id'],
            'competition' => $_POST['competition'],
            'home_team' => $_POST['home_team'],
            'away_team' => $_POST['away_team'],
            'home_score' => (int)$_POST['home_score'],
            'away_score' => (int)$_POST['away_score'],
            'status' => $_POST['status'],
            'minute' => $_POST['minute'] ?: null,
            'kickoff' => $_POST['kickoff']
        ];
        
        LiveScores::saveManualMatch($matchData);
        $message = 'Scorul a fost actualizat!';
        $messageType = 'success';
        LiveScores::clearCache();
    }
}

// Get matches
$todayMatches = LiveScores::getManualMatches(date('Y-m-d'));
$upcomingMatches = Database::fetchAll(
    "SELECT * FROM live_matches WHERE DATE(kickoff) > :today ORDER BY kickoff ASC LIMIT 10",
    ['today' => date('Y-m-d')]
);
$recentMatches = Database::fetchAll(
    "SELECT * FROM live_matches WHERE DATE(kickoff) < :today ORDER BY kickoff DESC LIMIT 10",
    ['today' => date('Y-m-d')]
);

// Get recent articles for linking
$recentArticles = Database::fetchAll(
    "SELECT id, title, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 50"
);

// Edit mode
$editMatch = null;
if (isset($_GET['edit'])) {
    $editMatch = Database::fetch(
        "SELECT * FROM live_matches WHERE id = :id",
        ['id' => (int)$_GET['edit']]
    );
}

$pageTitle = 'Scoruri Live - Admin';
require_once('admin-header.php');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-broadcast me-2"></i>Scoruri Live</h1>
                <div class="d-flex gap-2">
                    <a href="match-comments.php" class="btn btn-outline-info">
                        <i class="bi bi-chat-dots me-1"></i>Comentarii Meciuri
                    </a>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                        <i class="bi bi-plus-lg me-1"></i>Adaugă Meci
                    </button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Today's Matches -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-day me-2"></i>Meciuri Azi 
                        <span class="badge bg-light text-success"><?= date('d.m.Y') ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($todayMatches)): ?>
                    <p class="text-muted text-center mb-0">Nu sunt meciuri programate pentru azi.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Competiție</th>
                                    <th>Echipa Acasă</th>
                                    <th class="text-center">Scor</th>
                                    <th>Echipa Deplasare</th>
                                    <th>Oră</th>
                                    <th>Status</th>
                                    <th>Minut</th>
                                    <th class="text-end">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayMatches as $match): ?>
                                <tr class="<?= $match['status'] === 'live' ? 'table-warning' : '' ?>">
                                    <td><?= htmlspecialchars($match['competition']) ?></td>
                                    <td><strong><?= htmlspecialchars($match['home_team']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-dark fs-6">
                                            <?= $match['home_score'] ?> - <?= $match['away_score'] ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($match['away_team']) ?></strong></td>
                                    <td><?= date('H:i', strtotime($match['kickoff'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($match['status']) ?>">
                                            <?= getStatusLabel($match['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $match['minute'] ? $match['minute'] . "'" : '-' ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="quickScore(<?= $match['id'] ?>, 'home')">
                                                <i class="bi bi-plus"></i> Gol Acasă
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="quickScore(<?= $match['id'] ?>, 'away')">
                                                <i class="bi bi-plus"></i> Gol Deplasare
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="editMatch(<?= htmlspecialchars(json_encode($match)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Status Panel -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Meciuri Următoare</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingMatches)): ?>
                    <p class="text-muted text-center mb-0">Nu sunt meciuri programate.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingMatches as $match): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted"><?= date('d.m H:i', strtotime($match['kickoff'])) ?></small>
                                <br>
                                <strong><?= htmlspecialchars($match['home_team']) ?></strong> vs 
                                <strong><?= htmlspecialchars($match['away_team']) ?></strong>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary btn-sm" 
                                        onclick="editMatch(<?= htmlspecialchars(json_encode($match)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="deleteMatch(<?= $match['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Meciuri Recente</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentMatches)): ?>
                    <p class="text-muted text-center mb-0">Nu sunt meciuri recente.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentMatches as $match): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted"><?= date('d.m.Y', strtotime($match['kickoff'])) ?></small>
                                <br>
                                <?= htmlspecialchars($match['home_team']) ?> 
                                <strong><?= $match['home_score'] ?> - <?= $match['away_score'] ?></strong> 
                                <?= htmlspecialchars($match['away_team']) ?>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteMatch(<?= $match['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Match Modal -->
<div class="modal fade" id="addMatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_match">
                <input type="hidden" name="match_id" id="matchId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="matchModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>Adaugă Meci
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Competiție</label>
                            <input type="text" class="form-control" name="competition" id="competition" 
                                   placeholder="ex: Liga 1, Champions League" required>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Echipa Acasă</label>
                            <input type="text" class="form-control" name="home_team" id="homeTeam" required>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <label class="form-label">Scor</label>
                            <div class="input-group">
                                <input type="number" class="form-control text-center" name="home_score" id="homeScore" value="0" min="0">
                                <span class="input-group-text">-</span>
                                <input type="number" class="form-control text-center" name="away_score" id="awayScore" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Echipa Deplasare</label>
                            <input type="text" class="form-control" name="away_team" id="awayTeam" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control" name="kickoff_date" id="kickoffDate" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Ora</label>
                            <input type="time" class="form-control" name="kickoff_time" id="kickoffTime" 
                                   value="20:00" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="scheduled">Programat</option>
                                <option value="live">LIVE</option>
                                <option value="halftime">Pauză</option>
                                <option value="finished">Final</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Minut (pentru LIVE)</label>
                            <input type="number" class="form-control" name="minute" id="minute" 
                                   min="0" max="120" placeholder="ex: 45">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Marcatori Acasă</label>
                            <input type="text" class="form-control" name="home_scorers" id="homeScorers" 
                                   placeholder="Nume 10', Nume2 45'">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Marcatori Deplasare</label>
                            <input type="text" class="form-control" name="away_scorers" id="awayScorers" 
                                   placeholder="Nume 30'">
                        </div>
                        
                        <!-- Stadion și Arbitraj -->
                        <div class="col-12 mt-3">
                            <hr>
                            <h6 class="text-muted"><i class="bi bi-geo-alt me-1"></i>Locație & Arbitraj</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Stadion</label>
                            <input type="text" class="form-control" name="venue" id="venue" 
                                   placeholder="ex: Arena Națională, București">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Arbitru Principal</label>
                            <input type="text" class="form-control" name="referee" id="referee" 
                                   placeholder="ex: István Kovács">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Echipa de Arbitraj</label>
                            <input type="text" class="form-control" name="referee_team" id="refereeTeam" 
                                   placeholder="ex: Asistent 1, Asistent 2, Arbitru VAR">
                            <div class="form-text">Separate prin virgulă</div>
                        </div>
                        
                        <!-- Cartonașe -->
                        <div class="col-12 mt-3">
                            <hr>
                            <h6 class="text-muted"><i class="bi bi-card-heading me-1"></i>Cartonașe</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><span class="badge bg-warning text-dark">🟨</span> Galbene Acasă</label>
                            <input type="text" class="form-control" name="yellow_cards_home" id="yellowCardsHome" 
                                   placeholder="ex: Popescu 23', Ionescu 67'">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><span class="badge bg-warning text-dark">🟨</span> Galbene Deplasare</label>
                            <input type="text" class="form-control" name="yellow_cards_away" id="yellowCardsAway" 
                                   placeholder="ex: Dumitrescu 45'">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><span class="badge bg-danger">🟥</span> Roșii Acasă</label>
                            <input type="text" class="form-control" name="red_cards_home" id="redCardsHome" 
                                   placeholder="ex: Georgescu 89'">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><span class="badge bg-danger">🟥</span> Roșii Deplasare</label>
                            <input type="text" class="form-control" name="red_cards_away" id="redCardsAway" 
                                   placeholder="ex: Popa 55'">
                        </div>
                        
                        <div class="col-12 mt-3">
                            <h6 class="text-muted"><i class="bi bi-arrow-left-right me-1"></i>Schimburi</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">🔄 Schimburi Acasă</label>
                            <textarea class="form-control" name="substitutions_home" id="substitutionsHome" rows="2"
                                      placeholder="ex: Iese Popescu, Intră Ionescu 60'"></textarea>
                            <div class="form-text">Format: Iese X, Intră Y minut' (câte unul pe linie sau separați cu virgulă)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">🔄 Schimburi Deplasare</label>
                            <textarea class="form-control" name="substitutions_away" id="substitutionsAway" rows="2"
                                      placeholder="ex: Iese Georgescu, Intră Nicolae 75'"></textarea>
                        </div>
                        
                        <div class="col-md-12 mt-3">
                            <label class="form-label"><i class="bi bi-newspaper me-1"></i>Articol asociat</label>
                            <select class="form-select" name="article_id" id="articleId">
                                <option value="">-- Fără articol --</option>
                                <?php foreach ($recentArticles as $article): ?>
                                <option value="<?= $article['id'] ?>">
                                    <?= htmlspecialchars(mb_substr($article['title'], 0, 60)) ?><?= mb_strlen($article['title']) > 60 ? '...' : '' ?>
                                    (<?= date('d.m.Y', strtotime($article['published_at'] ?? '')) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Leagă acest meci de un articol existent.</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_match">
    <input type="hidden" name="match_id" id="deleteMatchId">
</form>

<script>
function editMatch(matchData) {
    const match = typeof matchData === 'string' ? JSON.parse(matchData) : matchData;
    
    document.getElementById('matchModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editează Meci';
    document.getElementById('matchId').value = match.id;
    document.getElementById('competition').value = match.competition || '';
    document.getElementById('homeTeam').value = match.home_team;
    document.getElementById('awayTeam').value = match.away_team;
    document.getElementById('homeScore').value = match.home_score || 0;
    document.getElementById('awayScore').value = match.away_score || 0;
    document.getElementById('status').value = match.status || 'scheduled';
    document.getElementById('minute').value = match.minute || '';
    document.getElementById('articleId').value = match.article_id || '';
    
    // Stadion și arbitraj
    document.getElementById('venue').value = match.venue || '';
    document.getElementById('referee').value = match.referee || '';
    
    const refereeTeam = match.referee_team ? JSON.parse(match.referee_team) : [];
    document.getElementById('refereeTeam').value = refereeTeam.join(', ');
    
    // Cartonașe
    const yellowHome = match.yellow_cards_home ? JSON.parse(match.yellow_cards_home) : [];
    document.getElementById('yellowCardsHome').value = yellowHome.join(', ');
    
    const yellowAway = match.yellow_cards_away ? JSON.parse(match.yellow_cards_away) : [];
    document.getElementById('yellowCardsAway').value = yellowAway.join(', ');
    
    const redHome = match.red_cards_home ? JSON.parse(match.red_cards_home) : [];
    document.getElementById('redCardsHome').value = redHome.join(', ');
    
    const redAway = match.red_cards_away ? JSON.parse(match.red_cards_away) : [];
    document.getElementById('redCardsAway').value = redAway.join(', ');
    
    // Schimburi
    const subsHome = match.substitutions_home ? JSON.parse(match.substitutions_home) : [];
    document.getElementById('substitutionsHome').value = subsHome.join(', ');
    
    const subsAway = match.substitutions_away ? JSON.parse(match.substitutions_away) : [];
    document.getElementById('substitutionsAway').value = subsAway.join(', ');
    
    if (match.kickoff) {
        const dt = new Date(match.kickoff);
        document.getElementById('kickoffDate').value = dt.toISOString().split('T')[0];
        document.getElementById('kickoffTime').value = dt.toTimeString().slice(0, 5);
    }
    
    const scorers = match.home_scorers ? JSON.parse(match.home_scorers) : [];
    document.getElementById('homeScorers').value = scorers.join(', ');
    
    const awayScorers = match.away_scorers ? JSON.parse(match.away_scorers) : [];
    document.getElementById('awayScorers').value = awayScorers.join(', ');
    
    new bootstrap.Modal(document.getElementById('addMatchModal')).show();
}

function deleteMatch(id) {
    if (confirm('Sigur vrei să ștergi acest meci?')) {
        document.getElementById('deleteMatchId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function quickScore(matchId, team) {
    // Quick update score via AJAX
    fetch('/admin/livescores-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'quick_score', 
            match_id: matchId, 
            team: team 
        })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Eroare la actualizare');
        }
    });
}

// Reset modal on close
document.getElementById('addMatchModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('matchModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Adaugă Meci';
    document.getElementById('matchId').value = '';
    this.querySelector('form').reset();
});
</script>

<?php
function getStatusBadgeClass($status) {
    return match($status) {
        'live' => 'danger',
        'halftime' => 'warning',
        'finished' => 'secondary',
        default => 'info'
    };
}

function getStatusLabel($status) {
    return match($status) {
        'scheduled' => 'Programat',
        'live' => 'LIVE',
        'halftime' => 'Pauză',
        'finished' => 'Final',
        default => ucfirst($status)
    };
}

require_once('admin-footer.php');
?>
