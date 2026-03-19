// Interactive Polls System for MatchDay.ro
class PollsSystem {
    constructor() {
        this.apiUrl = 'polls_api.php';
        this.init();
    }

    async init() {
        await this.loadActivePolls();
    }

    async loadActivePolls() {
        try {
            const response = await fetch(this.apiUrl);
            const polls = await response.json();
            
            if (Array.isArray(polls)) {
                polls.forEach(poll => this.renderPoll(poll));
            }
        } catch (error) {
            console.error('Error loading polls:', error);
        }
    }

    renderPoll(poll) {
        const container = document.querySelector(`[data-poll="${poll.id}"]`);
        if (!container) return;

        const hasVoted = this.hasUserVoted(poll.id);
        const totalVotes = poll.total_votes || 0;

        container.innerHTML = `
            <div class="poll-container border rounded-3 p-4 bg-white shadow-sm">
                <div class="poll-header mb-3">
                    <h4 class="poll-question h5 mb-2">
                        <i class="fas fa-poll me-2 text-primary"></i>
                        ${this.escapeHtml(poll.question)}
                    </h4>
                    ${poll.description ? `<p class="text-muted small mb-0">${this.escapeHtml(poll.description)}</p>` : ''}
                </div>
                
                <div class="poll-options">
                    ${hasVoted ? this.renderResults(poll) : this.renderVotingForm(poll)}
                </div>
                
                <div class="poll-footer mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-users me-1"></i>
                        ${totalVotes} ${totalVotes === 1 ? 'vot' : 'voturi'}
                    </small>
                    ${hasVoted ? '' : `
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Poți vota o singură dată
                        </small>
                    `}
                </div>
            </div>
        `;
    }

    renderVotingForm(poll) {
        return `
            <form class="poll-form" onsubmit="pollsSystem.submitVote(event, '${poll.id}')">
                ${poll.options.map(option => `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="option" value="${option.id}" id="option_${option.id}">
                        <label class="form-check-label" for="option_${option.id}">
                            ${this.escapeHtml(option.text)}
                        </label>
                    </div>
                `).join('')}
                
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-vote-yea me-1"></i>
                    Votează
                </button>
            </form>
        `;
    }

    renderResults(poll) {
        const totalVotes = poll.total_votes || 0;
        
        return `
            <div class="poll-results">
                ${poll.options.map(option => {
                    const votes = option.votes || 0;
                    const percentage = totalVotes > 0 ? Math.round((votes / totalVotes) * 100) : 0;
                    
                    return `
                        <div class="poll-option-result mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="option-text">${this.escapeHtml(option.text)}</span>
                                <span class="option-stats">
                                    <strong>${percentage}%</strong>
                                    <small class="text-muted">(${votes})</small>
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" 
                                     style="width: ${percentage}%" 
                                     role="progressbar" 
                                     aria-valuenow="${percentage}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
                
                <div class="text-center mt-3">
                    <span class="badge bg-success">
                        <i class="fas fa-check me-1"></i>
                        Ai votat în acest sondaj
                    </span>
                </div>
            </div>
        `;
    }

    async submitVote(event, pollId) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const selectedOption = formData.get('option');
        
        if (!selectedOption) {
            this.showNotification('Te rog să selectezi o opțiune', 'warning');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Se trimite...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    poll: pollId,
                    option: selectedOption
                })
            });
            
            const result = await response.json();
            
            if (response.ok) {
                this.markAsVoted(pollId);
                this.renderPoll(result.poll);
                this.showNotification(result.message || 'Vot înregistrat cu succes!', 'success');
            } else {
                this.showNotification(result.error || 'Eroare la înregistrarea votului', 'danger');
            }
        } catch (error) {
            console.error('Vote error:', error);
            this.showNotification('Eroare de conexiune. Încearcă din nou.', 'danger');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    hasUserVoted(pollId) {
        return localStorage.getItem(`voted_poll_${pollId}`) === 'true';
    }

    markAsVoted(pollId) {
        localStorage.setItem(`voted_poll_${pollId}`, 'true');
    }

    showNotification(message, type) {
        // Create notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Load more polls function
async function loadMorePolls() {
    try {
        const response = await fetch('polls_api.php');
        const polls = await response.json();
        
        if (Array.isArray(polls) && polls.length > 2) {
            const additionalPolls = polls.slice(2);
            const container = document.querySelector('.container .row .col-12 .row');
            
            // Remove the "load more" button
            const loadMoreBtn = document.querySelector('[onclick="loadMorePolls()"]');
            if (loadMoreBtn) loadMoreBtn.parentElement.remove();
            
            // Add additional polls
            additionalPolls.forEach(poll => {
                const pollDiv = document.createElement('div');
                pollDiv.className = 'col-md-6 mb-4';
                pollDiv.innerHTML = `<div data-poll="${poll.id}"></div>`;
                container.appendChild(pollDiv);
                
                // Initialize the poll
                if (pollsSystem) {
                    pollsSystem.renderPoll(poll);
                }
            });
        }
    } catch (error) {
        console.error('Error loading more polls:', error);
    }
}

// Initialize polls system
let pollsSystem;
document.addEventListener('DOMContentLoaded', function() {
    pollsSystem = new PollsSystem();
});
