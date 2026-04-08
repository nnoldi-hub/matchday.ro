/**
 * Gamification & Badges System
 * MatchDay.ro - Track user achievements and display badges
 */

class MatchDayGamification {
    constructor() {
        this.userId = this.getUserIdentifier();
        this.badges = [];
        this.points = 0;
        this.init();
    }
    
    init() {
        this.loadUserStats();
        this.trackActivity();
        this.setupBadgeNotifications();
    }
    
    // Generate anonymous user identifier
    getUserIdentifier() {
        let id = localStorage.getItem('matchday_user_id');
        if (!id) {
            id = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('matchday_user_id', id);
        }
        return id;
    }
    
    // Load user badges and points
    async loadUserStats() {
        try {
            const response = await fetch(`/badges_api.php?action=stats&id=${encodeURIComponent(this.userId)}`);
            if (response.ok) {
                const data = await response.json();
                this.badges = data.badges || [];
                this.points = data.points || 0;
                this.updateBadgeDisplay();
            }
        } catch (error) {
            console.log('Could not load user stats:', error);
        }
    }
    
    // Track user activity for badges
    trackActivity() {
        // Track page view
        this.incrementLocalCounter('articles_read');
        
        // Track visit hour for time-based badges
        const hour = new Date().getHours();
        this.setLocalValue('last_visit_hour', hour);
        
        // Track categories visited
        if (window.location.pathname.includes('/category/')) {
            const category = window.location.pathname.split('/category/')[1]?.split('/')[0];
            if (category) {
                this.addToLocalSet('categories_visited', category);
            }
        }
        
        // Check for new badges periodically
        this.checkForBadges();
    }
    
    // Check for earned badges
    async checkForBadges() {
        const activity = {
            articles_read: this.getLocalCounter('articles_read'),
            comments: this.getLocalCounter('comments'),
            polls: this.getLocalCounter('polls'),
            shares: this.getLocalCounter('shares'),
            visit_hour: this.getLocalValue('last_visit_hour'),
            categories_visited: this.getLocalSet('categories_visited').length
        };
        
        try {
            const response = await fetch('/badges_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'check',
                    id: this.userId,
                    activity: activity
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.new_badges && data.new_badges.length > 0) {
                    data.new_badges.forEach(badge => {
                        this.showBadgeNotification(badge);
                    });
                    this.loadUserStats(); // Refresh stats
                }
            }
        } catch (error) {
            console.log('Could not check badges:', error);
        }
    }
    
    // Show badge notification
    showBadgeNotification(badge) {
        const notification = document.createElement('div');
        notification.className = 'badge-notification';
        notification.innerHTML = `
            <div class="badge-notification-content">
                <div class="badge-icon" style="color: ${badge.color}">
                    <i class="fas ${badge.icon}"></i>
                </div>
                <div class="badge-info">
                    <strong>🎉 Insignă nouă!</strong>
                    <span class="badge-name">${badge.name}</span>
                    <small class="badge-points">+${badge.points} puncte</small>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    // Update badge display in UI
    updateBadgeDisplay() {
        const container = document.getElementById('userBadges');
        if (!container) return;
        
        if (this.badges.length === 0) {
            container.innerHTML = '<p class="text-muted small">Nu ai câștigat încă nicio insignă.</p>';
            return;
        }
        
        container.innerHTML = this.badges.map(badge => `
            <div class="user-badge" title="${badge.description}">
                <i class="fas ${badge.icon}" style="color: ${badge.color}"></i>
            </div>
        `).join('');
        
        // Update points display
        const pointsEl = document.getElementById('userPoints');
        if (pointsEl) {
            pointsEl.textContent = this.points;
        }
    }
    
    // Setup notification system
    setupBadgeNotifications() {
        // Add notification container styles if not present
        if (!document.getElementById('badge-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'badge-notification-styles';
            style.textContent = `
                .badge-notification {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #1a5f3c 0%, #0d4a2d 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    z-index: 10000;
                    transform: translateX(120%);
                    transition: transform 0.3s ease;
                    max-width: 300px;
                }
                .badge-notification.show {
                    transform: translateX(0);
                }
                .badge-notification-content {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
                .badge-notification .badge-icon {
                    font-size: 2rem;
                    background: white;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .badge-notification .badge-info {
                    display: flex;
                    flex-direction: column;
                }
                .badge-notification .badge-name {
                    font-size: 1.1rem;
                    font-weight: 600;
                }
                .badge-notification .badge-points {
                    color: #F5E663;
                    font-weight: 500;
                }
                .user-badge {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 36px;
                    height: 36px;
                    background: #f8f9fa;
                    border-radius: 50%;
                    margin: 2px;
                    font-size: 1rem;
                    cursor: help;
                    transition: transform 0.2s;
                }
                .user-badge:hover {
                    transform: scale(1.2);
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Helper: Increment local counter
    incrementLocalCounter(key) {
        const current = parseInt(localStorage.getItem(`matchday_${key}`) || '0', 10);
        localStorage.setItem(`matchday_${key}`, current + 1);
        return current + 1;
    }
    
    // Helper: Get local counter
    getLocalCounter(key) {
        return parseInt(localStorage.getItem(`matchday_${key}`) || '0', 10);
    }
    
    // Helper: Set local value
    setLocalValue(key, value) {
        localStorage.setItem(`matchday_${key}`, value);
    }
    
    // Helper: Get local value
    getLocalValue(key) {
        return localStorage.getItem(`matchday_${key}`);
    }
    
    // Helper: Add to local set
    addToLocalSet(key, value) {
        const set = this.getLocalSet(key);
        if (!set.includes(value)) {
            set.push(value);
            localStorage.setItem(`matchday_${key}`, JSON.stringify(set));
        }
    }
    
    // Helper: Get local set
    getLocalSet(key) {
        try {
            return JSON.parse(localStorage.getItem(`matchday_${key}`) || '[]');
        } catch {
            return [];
        }
    }
    
    // Track comment submission
    trackComment() {
        this.incrementLocalCounter('comments');
        this.checkForBadges();
    }
    
    // Track poll vote
    trackPollVote() {
        this.incrementLocalCounter('polls');
        this.checkForBadges();
    }
    
    // Track share
    trackShare() {
        this.incrementLocalCounter('shares');
        this.checkForBadges();
    }
}

// Initialize gamification
document.addEventListener('DOMContentLoaded', () => {
    window.matchDayGamification = new MatchDayGamification();
});
