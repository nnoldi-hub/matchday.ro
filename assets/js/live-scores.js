/**
 * Live Scores Widget
 * MatchDay.ro - Real-time football scores
 */

class LiveScoresWidget {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        
        this.options = {
            refreshInterval: options.refreshInterval || 60000, // 1 minute
            showCompetitionFilter: options.showCompetitionFilter !== false,
            maxMatches: options.maxMatches || 10,
            competition: options.competition || null,
            autoRefresh: options.autoRefresh !== false
        };
        
        this.matches = [];
        this.timer = null;
        
        this.init();
    }
    
    init() {
        this.render();
        this.loadMatches();
        
        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }
        
        // Pause refresh when tab is not visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopAutoRefresh();
            } else {
                this.loadMatches();
                this.startAutoRefresh();
            }
        });
    }
    
    render() {
        this.container.innerHTML = `
            <div class="live-scores-widget">
                <div class="live-scores-header">
                    <h4>
                        <span class="live-indicator"></span>
                        Scoruri Live
                    </h4>
                    <button class="btn-refresh" title="Actualizează">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 4v6h-6M1 20v-6h6"/>
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                    </button>
                </div>
                ${this.options.showCompetitionFilter ? `
                <div class="live-scores-filter">
                    <select class="competition-filter">
                        <option value="">Toate competițiile</option>
                        <option value="liga1">Liga 1 România</option>
                        <option value="champions">Champions League</option>
                        <option value="europa">Europa League</option>
                        <option value="conference">Conference League</option>
                        <option value="premier">Premier League</option>
                        <option value="laliga">La Liga</option>
                        <option value="bundesliga">Bundesliga</option>
                        <option value="seriea">Serie A</option>
                    </select>
                </div>
                ` : ''}
                <div class="live-scores-content">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <span>Se încarcă...</span>
                    </div>
                </div>
                <div class="live-scores-footer">
                    <small class="last-update">Ultima actualizare: --:--</small>
                </div>
            </div>
        `;
        
        // Event listeners
        const refreshBtn = this.container.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadMatches());
        }
        
        const filterSelect = this.container.querySelector('.competition-filter');
        if (filterSelect) {
            filterSelect.value = this.options.competition || '';
            filterSelect.addEventListener('change', (e) => {
                this.options.competition = e.target.value || null;
                this.loadMatches();
            });
        }
    }
    
    async loadMatches() {
        const content = this.container.querySelector('.live-scores-content');
        const refreshBtn = this.container.querySelector('.btn-refresh');
        
        if (refreshBtn) {
            refreshBtn.classList.add('spinning');
        }
        
        try {
            const params = new URLSearchParams();
            if (this.options.competition) {
                params.append('competition', this.options.competition);
            }
            
            const response = await fetch(`/livescores_api.php?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                this.matches = data.matches || [];
                this.renderMatches();
            } else {
                this.renderError(data.error || 'Eroare la încărcarea scorurilor');
            }
        } catch (error) {
            console.error('LiveScores error:', error);
            this.renderError('Eroare de conexiune');
        }
        
        if (refreshBtn) {
            refreshBtn.classList.remove('spinning');
        }
        
        this.updateTimestamp();
    }
    
    renderMatches() {
        const content = this.container.querySelector('.live-scores-content');
        
        if (this.matches.length === 0) {
            content.innerHTML = `
                <div class="no-matches">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <p>Nu sunt meciuri live în acest moment</p>
                    <small>Verifică mai târziu pentru meciuri în desfășurare</small>
                </div>
            `;
            return;
        }
        
        // Group by competition
        const grouped = this.groupByCompetition(this.matches);
        
        let html = '';
        for (const [competition, matches] of Object.entries(grouped)) {
            html += `
                <div class="competition-group">
                    <div class="competition-header">
                        <span class="competition-name">${competition}</span>
                    </div>
                    <div class="matches-list">
                        ${matches.slice(0, this.options.maxMatches).map(m => this.renderMatch(m)).join('')}
                    </div>
                </div>
            `;
        }
        
        content.innerHTML = html;
    }
    
    renderMatch(match) {
        const statusClass = match.is_live ? 'live' : 
                           (match.status_code === 'FT' || match.status_code === 'finished' || match.status_code === 'FINISHED') ? 'finished' : 'scheduled';
        
        const minuteDisplay = match.is_live && match.minute ? `${match.minute}'` : '';
        
        return `
            <div class="match-item ${statusClass}">
                <div class="match-time">
                    ${match.is_live ? `
                        <span class="live-badge">LIVE</span>
                        ${minuteDisplay ? `<span class="minute">${minuteDisplay}</span>` : ''}
                    ` : `
                        <span class="status">${match.status}</span>
                        ${match.kickoff ? `<span class="kickoff">${this.formatTime(match.kickoff)}</span>` : ''}
                    `}
                </div>
                <div class="match-teams">
                    <div class="team home ${match.home_score > match.away_score ? 'winning' : ''}">
                        ${match.home_logo ? `<img src="${match.home_logo}" alt="" class="team-logo">` : ''}
                        <span class="team-name">${match.home_team}</span>
                    </div>
                    <div class="team away ${match.away_score > match.home_score ? 'winning' : ''}">
                        ${match.away_logo ? `<img src="${match.away_logo}" alt="" class="team-logo">` : ''}
                        <span class="team-name">${match.away_team}</span>
                    </div>
                </div>
                <div class="match-score">
                    <span class="score-home ${match.home_score > match.away_score ? 'winning' : ''}">${match.home_score ?? '-'}</span>
                    <span class="score-separator">:</span>
                    <span class="score-away ${match.away_score > match.home_score ? 'winning' : ''}">${match.away_score ?? '-'}</span>
                </div>
            </div>
        `;
    }
    
    renderError(message) {
        const content = this.container.querySelector('.live-scores-content');
        content.innerHTML = `
            <div class="error-message">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>${message}</p>
                <button class="btn-retry">Încearcă din nou</button>
            </div>
        `;
        
        const retryBtn = content.querySelector('.btn-retry');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => this.loadMatches());
        }
    }
    
    groupByCompetition(matches) {
        const grouped = {};
        
        for (const match of matches) {
            const comp = match.competition || 'Alte meciuri';
            if (!grouped[comp]) {
                grouped[comp] = [];
            }
            grouped[comp].push(match);
        }
        
        return grouped;
    }
    
    formatTime(datetime) {
        if (!datetime) return '';
        
        const date = new Date(datetime);
        return date.toLocaleTimeString('ro-RO', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    updateTimestamp() {
        const lastUpdate = this.container.querySelector('.last-update');
        if (lastUpdate) {
            const now = new Date();
            lastUpdate.textContent = `Ultima actualizare: ${now.toLocaleTimeString('ro-RO', {
                hour: '2-digit',
                minute: '2-digit'
            })}`;
        }
    }
    
    startAutoRefresh() {
        if (this.timer) return;
        
        this.timer = setInterval(() => {
            this.loadMatches();
        }, this.options.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    destroy() {
        this.stopAutoRefresh();
        this.container.innerHTML = '';
    }
}

// CSS Styles (will be injected)
const liveScoresStyles = `
<style>
.live-scores-widget {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.live-scores-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #2d5a27, #4a8f41);
    color: #fff;
}

.live-scores-header h4 {
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.live-indicator {
    width: 10px;
    height: 10px;
    background: #ff4444;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

.btn-refresh {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-refresh:hover {
    background: rgba(255,255,255,0.3);
}

.btn-refresh.spinning svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

.live-scores-filter {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-color, #eee);
}

.competition-filter {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 6px;
    font-size: 0.875rem;
    background: var(--input-bg, #fff);
    color: var(--text-color, #333);
}

.live-scores-content {
    max-height: 400px;
    overflow-y: auto;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    gap: 1rem;
    color: var(--text-muted, #666);
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--border-color, #eee);
    border-top-color: #4a8f41;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.no-matches {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    text-align: center;
    color: var(--text-muted, #666);
}

.no-matches svg {
    opacity: 0.3;
    margin-bottom: 1rem;
}

.no-matches p {
    margin: 0 0 0.5rem;
    font-weight: 500;
}

.no-matches small {
    opacity: 0.7;
}

.competition-group {
    border-bottom: 1px solid var(--border-color, #eee);
}

.competition-group:last-child {
    border-bottom: none;
}

.competition-header {
    padding: 0.5rem 1rem;
    background: var(--bg-secondary, #f8f9fa);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted, #666);
}

.match-item {
    display: grid;
    grid-template-columns: 70px 1fr 50px;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-color, #eee);
    align-items: center;
    transition: background 0.2s;
}

.match-item:last-child {
    border-bottom: none;
}

.match-item:hover {
    background: var(--bg-hover, #f8f9fa);
}

.match-item.live {
    background: rgba(255, 68, 68, 0.05);
}

.match-time {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
}

.live-badge {
    background: #ff4444;
    color: #fff;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.625rem;
    letter-spacing: 0.5px;
    animation: pulse 1.5s infinite;
}

.minute {
    font-weight: 600;
    color: #ff4444;
}

.status {
    color: var(--text-muted, #666);
    font-size: 0.7rem;
}

.kickoff {
    font-weight: 600;
    color: var(--text-color, #333);
}

.match-teams {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.team {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.team.winning .team-name {
    font-weight: 700;
}

.team-logo {
    width: 16px;
    height: 16px;
    object-fit: contain;
}

.team-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.match-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-weight: 600;
    font-size: 1rem;
}

.match-item.live .match-score {
    color: #ff4444;
}

.score-separator {
    font-size: 0.75rem;
    line-height: 0.5;
    opacity: 0.5;
}

.match-item.finished .match-score {
    opacity: 0.7;
}

.winning {
    font-weight: 700;
}

.live-scores-footer {
    padding: 0.75rem 1rem;
    background: var(--bg-secondary, #f8f9fa);
    text-align: center;
}

.last-update {
    color: var(--text-muted, #888);
    font-size: 0.75rem;
}

.error-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    text-align: center;
    color: var(--text-muted, #666);
}

.error-message svg {
    color: #ff4444;
    margin-bottom: 0.5rem;
}

.btn-retry {
    margin-top: 1rem;
    padding: 0.5rem 1.5rem;
    background: #4a8f41;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-retry:hover {
    background: #3a7f31;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .live-scores-widget {
        --card-bg: #1e1e1e;
        --text-color: #e0e0e0;
        --text-muted: #888;
        --border-color: #333;
        --bg-secondary: #252525;
        --bg-hover: #2a2a2a;
        --input-bg: #2a2a2a;
    }
}

/* Dark theme class */
.dark-theme .live-scores-widget,
[data-theme="dark"] .live-scores-widget {
    --card-bg: #1e1e1e;
    --text-color: #e0e0e0;
    --text-muted: #888;
    --border-color: #333;
    --bg-secondary: #252525;
    --bg-hover: #2a2a2a;
    --input-bg: #2a2a2a;
}
</style>
`;

// Auto-inject styles
if (!document.querySelector('#live-scores-styles')) {
    const styleEl = document.createElement('div');
    styleEl.id = 'live-scores-styles';
    styleEl.innerHTML = liveScoresStyles;
    document.head.appendChild(styleEl.querySelector('style'));
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LiveScoresWidget;
}
