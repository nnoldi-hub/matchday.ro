// Advanced Comments System for MatchDay.ro
class CommentsSystem {
    constructor(slug, containerSelector = '#comments') {
        this.slug = slug;
        this.container = document.querySelector(containerSelector);
        this.apiUrl = '../comments_api.php';
        this.comments = [];
        this.init();
    }

    async init() {
        this.setupForm();
        await this.loadComments();
    }

    setupForm() {
        const form = document.getElementById('commentForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitComment(e);
        });

        // Real-time character counter
        const textarea = form.querySelector('textarea[name="message"]');
        if (textarea) {
            const counter = document.createElement('small');
            counter.className = 'text-muted float-end';
            counter.textContent = '0/500';
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', () => {
                const len = textarea.value.length;
                counter.textContent = `${len}/500`;
                counter.className = len > 450 ? 'text-warning float-end' : 
                                  len > 500 ? 'text-danger float-end' : 'text-muted float-end';
            });
        }
    }

    async loadComments() {
        try {
            const response = await fetch(`${this.apiUrl}?slug=${encodeURIComponent(this.slug)}`);
            const data = await response.json();
            
            this.comments = Array.isArray(data) ? data : [];
            this.renderComments();
        } catch (error) {
            console.error('Error loading comments:', error);
            this.showError('Eroare la încărcarea comentariilor');
        }
    }

    renderComments() {
        if (!this.container) return;

        if (this.comments.length === 0) {
            this.container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="far fa-comments fa-2x mb-2"></i>
                    <p>Fii primul care comentează!</p>
                </div>
            `;
            return;
        }

        const commentsHtml = this.comments.map(comment => this.renderComment(comment)).join('');
        this.container.innerHTML = `
            <div class="comments-header d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-comments me-2"></i>${this.comments.length} Comentarii</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="this.scrollToForm()">
                    <i class="fas fa-plus me-1"></i>Adaugă comentariu
                </button>
            </div>
            <div class="comments-list">
                ${commentsHtml}
            </div>
        `;
    }

    renderComment(comment) {
        const timeAgo = this.timeAgo(comment.date);
        return `
            <div class="comment-item border-bottom py-3">
                <div class="d-flex">
                    <div class="comment-avatar me-3">
                        <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            ${comment.name.charAt(0).toUpperCase()}
                        </div>
                    </div>
                    <div class="comment-content flex-grow-1">
                        <div class="comment-header d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="comment-author">${this.escapeHtml(comment.name)}</strong>
                                <small class="text-muted ms-2">
                                    <i class="far fa-clock me-1"></i>${timeAgo}
                                </small>
                            </div>
                            <div class="comment-actions">
                                <button class="btn btn-sm btn-link text-muted p-0 me-2" onclick="commentsSystem.likeComment('${comment.id || Date.now()}')">
                                    <i class="far fa-thumbs-up"></i> ${comment.likes || 0}
                                </button>
                                <button class="btn btn-sm btn-link text-muted p-0" onclick="commentsSystem.replyToComment('${comment.id || Date.now()}')">
                                    <i class="fas fa-reply"></i> Răspunde
                                </button>
                            </div>
                        </div>
                        <div class="comment-text mt-2">
                            ${this.formatCommentText(comment.message)}
                        </div>
                        ${comment.replies ? this.renderReplies(comment.replies) : ''}
                    </div>
                </div>
            </div>
        `;
    }

    renderReplies(replies) {
        if (!replies || replies.length === 0) return '';
        
        const repliesHtml = replies.map(reply => `
            <div class="comment-reply ms-4 mt-2 pt-2 border-top border-light">
                <div class="d-flex">
                    <div class="reply-avatar me-2">
                        <div class="avatar-placeholder bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.8rem;">
                            ${reply.name.charAt(0).toUpperCase()}
                        </div>
                    </div>
                    <div class="reply-content">
                        <strong class="reply-author">${this.escapeHtml(reply.name)}</strong>
                        <small class="text-muted ms-2">${this.timeAgo(reply.date)}</small>
                        <div class="reply-text mt-1">${this.formatCommentText(reply.message)}</div>
                    </div>
                </div>
            </div>
        `).join('');

        return `<div class="comment-replies mt-2">${repliesHtml}</div>`;
    }

    async submitComment(event) {
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Honeypot check
        if (formData.get('website')) return;
        
        formData.append('slug', this.slug);

        // Disable submit button
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Se trimite...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                form.reset();
                this.showSuccess(result.message || 'Comentariu trimis cu succes!');
                
                // Reset character counter
                const counter = form.querySelector('small.float-end');
                if (counter) counter.textContent = '0/500';
                
                // Reload comments after a short delay
                setTimeout(() => this.loadComments(), 1000);
            } else {
                this.showError(result.error || 'Eroare la trimiterea comentariului');
            }
        } catch (error) {
            console.error('Submit error:', error);
            this.showError('Eroare de conexiune. Încearcă din nou.');
        } finally {
            // Re-enable submit button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    formatCommentText(text) {
        // Convert URLs to links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        text = text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
        
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        return this.escapeHtml(text);
    }

    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'acum';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}z`;
        
        return date.toLocaleDateString('ro-RO');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showNotification(message, type) {
        // Remove existing notifications
        const existing = document.querySelectorAll('.comment-notification');
        existing.forEach(el => el.remove());

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} comment-notification alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert before comments container
        this.container.parentNode.insertBefore(notification, this.container);

        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    }

    scrollToForm() {
        const form = document.getElementById('commentForm');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.querySelector('input[name="name"]')?.focus();
        }
    }

    likeComment(commentId) {
        // TODO: Implement comment liking system
        console.log('Like comment:', commentId);
    }

    replyToComment(commentId) {
        // TODO: Implement reply system
        console.log('Reply to comment:', commentId);
    }
}

// Global variable for external access
let commentsSystem;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // This will be set by the article page
    if (typeof window.articleSlug !== 'undefined') {
        commentsSystem = new CommentsSystem(window.articleSlug);
    }
});
