// Global configuration pentru admin polls
const POLLS_API_URL = '../polls-manager.php';

// Override pentru toate fetch-urile către polls-actions.php
function pollsApiCall(data) {
    return fetch(POLLS_API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    });
}

// Actualizează funcția createPoll
async function createPollUpdated(formData) {
    // Colectează opțiunile
    const options = [];
    document.querySelectorAll('#pollOptions .poll-option-input').forEach(input => {
        if (input.value.trim()) {
            options.push(input.value.trim());
        }
    });
    
    if (options.length < 2) {
        alert('Adaugă cel puțin 2 opțiuni pentru sondaj.');
        return;
    }
    
    const pollData = {
        action: 'create_poll',
        poll_id: formData.get('poll_id'),
        question: formData.get('question'),
        description: formData.get('description') || '',
        options: options,
        active: formData.has('active') ? 1 : 0
    };
    
    try {
        const response = await pollsApiCall(pollData);
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Eroare: ' + (result.error || result.message));
        }
    } catch (error) {
        console.error('Eroare:', error);
        alert('Eroare de conexiune: ' + error.message);
    }
}

// Actualizează funcția togglePollStatus
async function togglePollStatusUpdated(pollId, newStatus) {
    if (!confirm('Ești sigur că vrei să schimbi statusul acestui sondaj?')) return;
    
    try {
        const response = await pollsApiCall({
            action: 'toggle_poll',
            poll_id: pollId,
            active: newStatus ? 1 : 0
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Eroare: ' + (result.error || result.message));
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

// Actualizează funcția deletePoll
async function deletePollUpdated(pollId) {
    if (!confirm('Ești sigur că vrei să ștergi acest sondaj? Această acțiune nu poate fi anulată.')) return;
    
    try {
        const response = await pollsApiCall({
            action: 'delete_poll',
            poll_id: pollId
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Eroare: ' + (result.error || result.message));
        }
    } catch (error) {
        alert('Eroare de conexiune: ' + error.message);
    }
}

// Actualizează funcția updatePoll
async function updatePollUpdated(pollId, formData) {
    const options = [];
    document.querySelectorAll('#editPollModal .poll-option-input').forEach(input => {
        if (input.value.trim()) {
            options.push(input.value.trim());
        }
    });
    
    if (options.length < 2) {
        alert('Adaugă cel puțin 2 opțiuni pentru sondaj.');
        return;
    }
    
    const pollData = {
        action: 'update_poll',
        poll_id: pollId,
        question: formData.get('question'),
        description: formData.get('description') || '',
        options: options,
        active: formData.has('active') ? 1 : 0
    };
    
    try {
        const response = await pollsApiCall(pollData);
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Eroare: ' + (result.error || result.message));
        }
    } catch (error) {
        console.error('Eroare:', error);
        alert('Eroare de conexiune: ' + error.message);
    }
}

// Override funcțiile existente
if (typeof window !== 'undefined') {
    window.createPoll = createPollUpdated;
    window.togglePollStatus = togglePollStatusUpdated;
    window.deletePoll = deletePollUpdated;
    window.updatePoll = updatePollUpdated;
}
