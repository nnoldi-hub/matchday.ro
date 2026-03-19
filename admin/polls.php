<?php
require_once(__DIR__ . '/../config/config.php');
if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Get all polls
$pollsDir = __DIR__ . '/../data/polls';
$polls = [];

if (is_dir($pollsDir)) {
    $pollFiles = array_filter(scandir($pollsDir), fn($f) => substr($f, -5) === '.json');
    foreach ($pollFiles as $pf) {
        $pollData = json_decode(file_get_contents($pollsDir . '/' . $pf), true);
        if ($pollData) {
            $pollData['filename'] = $pf;
            $polls[] = $pollData;
        }
    }
}

// Sort polls by creation date (newest first)
usort($polls, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="fas fa-poll me-2 text-primary"></i>
                    Sondaje Interactive
                </h1>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPollModal">
                        <i class="fas fa-plus me-1"></i>Sondaj nou
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h3 class="h2"><?php echo count($polls); ?></h3>
                    <p class="mb-0 opacity-75">Total sondaje</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h3 class="h2"><?php echo count(array_filter($polls, fn($p) => $p['active'] ?? false)); ?></h3>
                    <p class="mb-0 opacity-75">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <h3 class="h2"><?php echo array_sum(array_column($polls, 'total_votes')); ?></h3>
                    <p class="mb-0 opacity-75">Total voturi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h3 class="h2"><?php echo count($polls) > 0 ? round(array_sum(array_column($polls, 'total_votes')) / count($polls)) : 0; ?></h3>
                    <p class="mb-0 opacity-75">Medie voturi/sondaj</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Polls List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Toate sondajele</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($polls)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nu există sondaje încă.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPollModal">
                        <i class="fas fa-plus me-1"></i>Creează primul sondaj
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Întrebare</th>
                                <th>Status</th>
                                <th>Voturi</th>
                                <th>Creat</th>
                                <th class="text-center">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($polls as $poll): ?>
                            <tr>
                                <td>
                                    <strong><?php echo Security::sanitizeInput($poll['question']); ?></strong>
                                    <?php if (!empty($poll['description'])): ?>
                                        <br><small class="text-muted"><?php echo Security::sanitizeInput($poll['description']); ?></small>
                                    <?php endif; ?>
                                    <br><code class="small"><?php echo $poll['id']; ?></code>
                                </td>
                                <td>
                                    <?php if ($poll['active'] ?? false): ?>
                                        <span class="badge bg-success">Activ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $poll['total_votes'] ?? 0; ?></strong> voturi
                                    <br><small class="text-muted"><?php echo count($poll['options'] ?? []); ?> opțiuni</small>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($poll['created_at'] ?? '1970-01-01')); ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewPollResults('<?php echo $poll['id']; ?>')">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editPoll('<?php echo $poll['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-<?php echo ($poll['active'] ?? false) ? 'warning' : 'success'; ?>" 
                                                onclick="togglePollStatus('<?php echo $poll['id']; ?>', <?php echo ($poll['active'] ?? false) ? 'false' : 'true'; ?>)">
                                            <i class="fas fa-<?php echo ($poll['active'] ?? false) ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deletePoll('<?php echo $poll['id']; ?>')">
                                            <i class="fas fa-trash"></i>
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

<!-- New Poll Modal -->
<div class="modal fade" id="newPollModal" tabindex="-1" aria-labelledby="newPollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="newPollModalLabel">
                    <i class="fas fa-plus me-2"></i>Sondaj nou
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newPollForm" onsubmit="createPoll(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="pollId" class="form-label">ID Sondaj *</label>
                            <input type="text" class="form-control" id="pollId" name="poll_id" 
                                   placeholder="ex: echipa-favorita" required
                                   pattern="[a-z0-9\-_]+" title="Doar litere mici, cifre, cratima și underscore">
                            <div class="form-text">Folosește doar litere mici, cifre, cratima (-) și underscore (_)</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="pollQuestion" class="form-label">Întrebarea *</label>
                            <input type="text" class="form-control" id="pollQuestion" name="question" 
                                   placeholder="Care este echipa ta favorită?" required maxlength="200">
                        </div>
                        
                        <div class="col-12">
                            <label for="pollDescription" class="form-label">Descriere (opțional)</label>
                            <textarea class="form-control" id="pollDescription" name="description" 
                                      rows="2" maxlength="500" placeholder="Detalii suplimentare despre sondaj..."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Opțiuni de răspuns *</label>
                            <div id="pollOptions">
                                <div class="poll-option mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text">1</span>
                                        <input type="text" class="form-control option-text" name="options[]" 
                                               placeholder="Prima opțiune" required maxlength="100">
                                    </div>
                                </div>
                                <div class="poll-option mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text">2</span>
                                        <input type="text" class="form-control option-text" name="options[]" 
                                               placeholder="A doua opțiune" required maxlength="100">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPollOption()">
                                <i class="fas fa-plus me-1"></i>Adaugă opțiune
                            </button>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pollActive" name="active" checked>
                                <label class="form-check-label" for="pollActive">
                                    Sondaj activ (vizibil pe site)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Creează sondaj
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Poll Results Modal -->
<div class="modal fade" id="pollResultsModal" tabindex="-1" aria-labelledby="pollResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="pollResultsModalLabel">
                    <i class="fas fa-chart-bar me-2"></i>Rezultate sondaj
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pollResultsContent">
                <!-- Results will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Poll Modal -->
<div class="modal fade" id="editPollModal" tabindex="-1" aria-labelledby="editPollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editPollModalLabel">
                    <i class="fas fa-edit me-2"></i>Editează sondaj
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPollForm" onsubmit="updatePoll(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="editPollId" class="form-label">ID Sondaj</label>
                            <input type="text" class="form-control" id="editPollId" name="poll_id" readonly>
                            <div class="form-text">ID-ul nu poate fi modificat după crearea sondajului</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="editPollQuestion" class="form-label">Întrebarea *</label>
                            <input type="text" class="form-control" id="editPollQuestion" name="question" 
                                   placeholder="Care este echipa ta favorită?" required maxlength="200">
                        </div>
                        
                        <div class="col-12">
                            <label for="editPollDescription" class="form-label">Descriere (opțional)</label>
                            <textarea class="form-control" id="editPollDescription" name="description" 
                                      rows="2" maxlength="500" placeholder="Detalii suplimentare despre sondaj..."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Opțiuni de răspuns *</label>
                            <div id="editPollOptions">
                                <!-- Options will be loaded here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditPollOption()">
                                <i class="fas fa-plus me-1"></i>Adaugă opțiune
                            </button>
                            <div class="form-text mt-2">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                <strong>Atenție:</strong> Modificarea opțiunilor poate afecta statisticile existente
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editPollActive" name="active">
                                <label class="form-check-label" for="editPollActive">
                                    Sondaj activ (vizibil pe site)
                                </label>
                            </div>
                        </div>

                        <!-- Statistics Display -->
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-chart-line me-1"></i>Statistici actuale
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted">Total voturi</small>
                                            <div class="fw-bold" id="editPollTotalVotes">0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Creat la</small>
                                            <div class="fw-bold" id="editPollCreatedAt">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Ultima modificare</small>
                                            <div class="fw-bold" id="editPollUpdatedAt">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvează modificările
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let optionCounter = 2;

function addPollOption() {
    if (optionCounter >= 10) {
        alert('Maxim 10 opțiuni per sondaj.');
        return;
    }
    
    optionCounter++;
    const optionsContainer = document.getElementById('pollOptions');
    const newOption = document.createElement('div');
    newOption.className = 'poll-option mb-2';
    newOption.innerHTML = `
        <div class="input-group">
            <span class="input-group-text">${optionCounter}</span>
            <input type="text" class="form-control option-text" name="options[]" 
                   placeholder="Opțiunea ${optionCounter}" required maxlength="100">
            <button type="button" class="btn btn-outline-danger" onclick="removePollOption(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    optionsContainer.appendChild(newOption);
}

function removePollOption(button) {
    if (document.querySelectorAll('.poll-option').length <= 2) {
        alert('Minim 2 opțiuni necesare.');
        return;
    }
    
    button.closest('.poll-option').remove();
    
    // Renumerotare opțiuni
    const options = document.querySelectorAll('.poll-option');
    options.forEach((option, index) => {
        const span = option.querySelector('.input-group-text');
        const input = option.querySelector('input');
        span.textContent = index + 1;
        if (!input.value) {
            input.placeholder = `Opțiunea ${index + 1}`;
        }
    });
    
    optionCounter = options.length;
}

async function createPoll(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate options
    const options = Array.from(form.querySelectorAll('input[name="options[]"]'))
        .map(input => input.value.trim())
        .filter(value => value.length > 0);
    
    if (options.length < 2) {
        alert('Minim 2 opțiuni necesare.');
        return;
    }
    
    // Prepare data
    const pollData = {
        action: 'create_poll',
        poll_id: formData.get('poll_id'),
        question: formData.get('question'),
        description: formData.get('description') || '',
        options: options,
        active: formData.has('active')
    };
    
    try {
        const response = await fetch('polls-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(pollData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Eroare: ' + result.error);
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

async function togglePollStatus(pollId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    const confirmMsg = newStatus ? 'activezi' : 'dezactivezi';
    
    if (!confirm(`Sigur vrei să ${confirmMsg} acest sondaj?`)) {
        return;
    }
    
    try {
        const response = await fetch('polls-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_poll',
                poll_id: pollId,
                active: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Eroare: ' + result.error);
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

async function deletePoll(pollId) {
    if (!confirm('Sigur vrei să ștergi acest sondaj? Acțiunea nu poate fi anulată!')) {
        return;
    }
    
    try {
        const response = await fetch('polls-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_poll',
                poll_id: pollId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Eroare: ' + result.error);
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

async function viewPollResults(pollId) {
    try {
        const response = await fetch(`../polls_api.php?poll=${pollId}`);
        const poll = await response.json();
        
        if (poll.error) {
            alert('Eroare: ' + poll.error);
            return;
        }
        
        const totalVotes = poll.total_votes || 0;
        let resultsHtml = `
            <div class="mb-4">
                <h5>${poll.question}</h5>
                ${poll.description ? `<p class="text-muted">${poll.description}</p>` : ''}
                <p><strong>Total voturi:</strong> ${totalVotes}</p>
            </div>
            <div class="poll-results">
        `;
        
        poll.options.forEach(option => {
            const votes = option.votes || 0;
            const percentage = totalVotes > 0 ? Math.round((votes / totalVotes) * 100) : 0;
            
            resultsHtml += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>${option.text}</span>
                        <span><strong>${percentage}%</strong> (${votes} voturi)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
        
        document.getElementById('pollResultsContent').innerHTML = resultsHtml;
        new bootstrap.Modal(document.getElementById('pollResultsModal')).show();
        
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

function editPoll(pollId) {
    // Load poll data
    loadPollForEdit(pollId);
}

async function loadPollForEdit(pollId) {
    try {
        const response = await fetch(`../polls_api.php?poll=${pollId}`);
        const poll = await response.json();
        
        if (poll.error) {
            alert('Eroare la încărcarea sondajului: ' + poll.error);
            return;
        }
        
        // Populate edit form
        document.getElementById('editPollId').value = poll.id;
        document.getElementById('editPollQuestion').value = poll.question || '';
        document.getElementById('editPollDescription').value = poll.description || '';
        document.getElementById('editPollActive').checked = poll.active || false;
        
        // Statistics
        document.getElementById('editPollTotalVotes').textContent = poll.total_votes || 0;
        document.getElementById('editPollCreatedAt').textContent = formatDate(poll.created_at);
        document.getElementById('editPollUpdatedAt').textContent = formatDate(poll.updated_at || poll.created_at);
        
        // Load options
        const optionsContainer = document.getElementById('editPollOptions');
        optionsContainer.innerHTML = '';
        
        poll.options.forEach((option, index) => {
            addEditPollOptionWithData(option.text, option.votes || 0, index + 1);
        });
        
        editOptionCounter = poll.options.length;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('editPollModal')).show();
        
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

let editOptionCounter = 0;

function addEditPollOption() {
    addEditPollOptionWithData('', 0, editOptionCounter + 1);
    editOptionCounter++;
}

function addEditPollOptionWithData(text = '', votes = 0, optionNumber = null) {
    if (editOptionCounter >= 10) {
        alert('Maxim 10 opțiuni per sondaj.');
        return;
    }
    
    if (!optionNumber) {
        editOptionCounter++;
        optionNumber = editOptionCounter;
    } else {
        editOptionCounter = Math.max(editOptionCounter, optionNumber);
    }
    
    const optionsContainer = document.getElementById('editPollOptions');
    const newOption = document.createElement('div');
    newOption.className = 'edit-poll-option mb-3';
    newOption.innerHTML = `
        <div class="input-group">
            <span class="input-group-text">${optionNumber}</span>
            <input type="text" class="form-control option-text" name="options[]" 
                   value="${text}" placeholder="Opțiunea ${optionNumber}" required maxlength="100">
            <button type="button" class="btn btn-outline-danger" onclick="removeEditPollOption(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        ${votes > 0 ? `<small class="text-muted mt-1 d-block"><i class="fas fa-chart-bar me-1"></i>${votes} voturi existente</small>` : ''}
    `;
    optionsContainer.appendChild(newOption);
}

function removeEditPollOption(button) {
    const options = document.querySelectorAll('.edit-poll-option');
    if (options.length <= 2) {
        alert('Minim 2 opțiuni necesare.');
        return;
    }
    
    if (!confirm('Sigur vrei să ștergi această opțiune? Voturile existente vor fi pierdute!')) {
        return;
    }
    
    button.closest('.edit-poll-option').remove();
    
    // Renumerotare opțiuni
    const remainingOptions = document.querySelectorAll('.edit-poll-option');
    remainingOptions.forEach((option, index) => {
        const span = option.querySelector('.input-group-text');
        const input = option.querySelector('input');
        span.textContent = index + 1;
        if (!input.value) {
            input.placeholder = `Opțiunea ${index + 1}`;
        }
    });
    
    editOptionCounter = remainingOptions.length;
}

async function updatePoll(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate options
    const options = Array.from(form.querySelectorAll('input[name="options[]"]'))
        .map(input => input.value.trim())
        .filter(value => value.length > 0);
    
    if (options.length < 2) {
        alert('Minim 2 opțiuni necesare.');
        return;
    }
    
    if (!confirm('Sigur vrei să salvezi modificările? Această acțiune poate afecta statisticile existente.')) {
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvez...';
    submitBtn.disabled = true;
    
    // Prepare data
    const pollData = {
        action: 'update_poll',
        poll_id: formData.get('poll_id'),
        question: formData.get('question'),
        description: formData.get('description') || '',
        options: options,
        active: formData.has('active')
    };
    
    try {
        const response = await fetch('polls-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(pollData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Eroare: ' + result.error);
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Generate slug from question
document.getElementById('pollQuestion').addEventListener('input', function() {
    const question = this.value;
    const slug = question.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-') // Replace spaces with hyphens
        .replace(/-+/g, '-') // Replace multiple hyphens with single
        .substring(0, 50); // Limit length
    
    document.getElementById('pollId').value = slug;
});
</script>

<!-- Fix pentru API endpoint -->
<script src="polls-api-fix.js"></script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
