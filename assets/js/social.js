// Social Media Integration for MatchDay.ro
class SocialMediaSystem {
    constructor() {
        this.init();
    }

    init() {
        this.createShareButtons();
        this.createSocialEmbeds();
        this.initLikeSystem();
    }

    createShareButtons() {
        const shareContainers = document.querySelectorAll('[data-social-share]');
        
        shareContainers.forEach(container => {
            const url = encodeURIComponent(container.dataset.url || window.location.href);
            const title = encodeURIComponent(container.dataset.title || document.title);
            const description = encodeURIComponent(container.dataset.description || '');
            
            container.innerHTML = `
                <div class="social-share-buttons">
                    <h6 class="share-title mb-3">
                        <i class="fas fa-share-alt me-2"></i>
                        Distribuie articolul
                    </h6>
                    
                    <div class="btn-group-vertical btn-group-sm d-grid gap-2" role="group">
                        <button type="button" class="btn btn-facebook" onclick="socialMedia.shareOnFacebook('${url}', '${title}')">
                            <i class="fab fa-facebook-f me-2"></i>
                            Facebook
                        </button>
                        
                        <button type="button" class="btn btn-twitter" onclick="socialMedia.shareOnTwitter('${url}', '${title}')">
                            <i class="fab fa-twitter me-2"></i>
                            Twitter
                        </button>
                        
                        <button type="button" class="btn btn-whatsapp" onclick="socialMedia.shareOnWhatsApp('${url}', '${title}')">
                            <i class="fab fa-whatsapp me-2"></i>
                            WhatsApp
                        </button>
                        
                        <button type="button" class="btn btn-telegram" onclick="socialMedia.shareOnTelegram('${url}', '${title}')">
                            <i class="fab fa-telegram-plane me-2"></i>
                            Telegram
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="socialMedia.copyToClipboard('${decodeURIComponent(url)}')">
                            <i class="fas fa-link me-2"></i>
                            Copiază link
                        </button>
                    </div>
                </div>
            `;
        });
    }

    shareOnFacebook(url, title) {
        const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
        this.openShareWindow(shareUrl, 'Facebook');
    }

    shareOnTwitter(url, title) {
        const shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}&via=MatchDayRo`;
        this.openShareWindow(shareUrl, 'Twitter');
    }

    shareOnWhatsApp(url, title) {
        const text = `${decodeURIComponent(title)} ${decodeURIComponent(url)}`;
        const shareUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
        this.openShareWindow(shareUrl, 'WhatsApp');
    }

    shareOnTelegram(url, title) {
        const shareUrl = `https://t.me/share/url?url=${url}&text=${title}`;
        this.openShareWindow(shareUrl, 'Telegram');
    }

    async copyToClipboard(url) {
        try {
            await navigator.clipboard.writeText(url);
            this.showNotification('Link copiat în clipboard!', 'success');
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('Link copiat în clipboard!', 'success');
        }
    }

    openShareWindow(url, platform) {
        const width = 600;
        const height = 400;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        
        window.open(
            url,
            `share_${platform}`,
            `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
        );
        
        // Track share events (you can integrate with analytics)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'share', {
                method: platform.toLowerCase(),
                content_type: 'article',
                content_id: window.location.pathname
            });
        }
    }

    createSocialEmbeds() {
        // Auto-embed YouTube videos
        this.embedYouTubeVideos();
        
        // Auto-embed Twitter tweets
        this.embedTweets();
        
        // Auto-embed Instagram posts
        this.embedInstagramPosts();
    }

    embedYouTubeVideos() {
        const youtubeLinks = document.querySelectorAll('a[href*="youtube.com/watch"], a[href*="youtu.be/"]');
        
        youtubeLinks.forEach(link => {
            const url = link.href;
            let videoId = '';
            
            if (url.includes('youtube.com/watch')) {
                const urlParams = new URLSearchParams(url.split('?')[1]);
                videoId = urlParams.get('v');
            } else if (url.includes('youtu.be/')) {
                videoId = url.split('/').pop().split('?')[0];
            }
            
            if (videoId) {
                const embed = document.createElement('div');
                embed.className = 'youtube-embed mb-3';
                embed.innerHTML = `
                    <div class="ratio ratio-16x9">
                        <iframe 
                            src="https://www.youtube.com/embed/${videoId}" 
                            title="YouTube video player" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                    <small class="text-muted">
                        <i class="fab fa-youtube me-1"></i>
                        Video YouTube: <a href="${url}" target="_blank">${link.textContent}</a>
                    </small>
                `;
                
                link.parentNode.insertBefore(embed, link);
                link.style.display = 'none';
            }
        });
    }

    embedTweets() {
        const tweetLinks = document.querySelectorAll('a[href*="twitter.com"][href*="/status/"], a[href*="x.com"][href*="/status/"]');
        
        tweetLinks.forEach(link => {
            const tweetId = link.href.split('/status/')[1].split('?')[0];
            
            if (tweetId) {
                const embed = document.createElement('div');
                embed.className = 'twitter-embed mb-3';
                embed.innerHTML = `
                    <blockquote class="twitter-tweet" data-theme="light">
                        <p>Se încarcă tweet-ul...</p>
                        <a href="${link.href}">Vezi pe Twitter</a>
                    </blockquote>
                `;
                
                link.parentNode.insertBefore(embed, link);
                link.style.display = 'none';
                
                // Load Twitter widgets script if not already loaded
                if (!document.querySelector('script[src*="platform.twitter.com"]')) {
                    const script = document.createElement('script');
                    script.src = 'https://platform.twitter.com/widgets.js';
                    script.async = true;
                    document.head.appendChild(script);
                }
            }
        });
    }

    embedInstagramPosts() {
        const instaLinks = document.querySelectorAll('a[href*="instagram.com/p/"], a[href*="instagram.com/reel/"]');
        
        instaLinks.forEach(link => {
            const postId = link.href.split('/p/')[1]?.split('/')[0] || link.href.split('/reel/')[1]?.split('/')[0];
            
            if (postId) {
                const embed = document.createElement('div');
                embed.className = 'instagram-embed mb-3';
                embed.innerHTML = `
                    <blockquote class="instagram-media" 
                                data-instgrm-permalink="${link.href}" 
                                data-instgrm-version="14">
                        <div style="padding: 16px;">
                            <div style="display: flex; flex-direction: row; align-items: center;">
                                <div style="background-color: #F4F4F4; border-radius: 50%; height: 40px; margin-right: 14px; width: 40px;"></div>
                                <div style="display: flex; flex-direction: column; flex-grow: 1;">
                                    <div style="background-color: #F4F4F4; border-radius: 4px; height: 14px; margin-bottom: 6px; width: 100px;"></div>
                                    <div style="background-color: #F4F4F4; border-radius: 4px; height: 14px; width: 60px;"></div>
                                </div>
                            </div>
                            <div style="padding: 19% 0;"></div>
                            <div style="display: block; height: 50px; margin: 0 auto 12px; width: 50px;">
                                <svg width="50px" height="50px" viewBox="0 0 60 60">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g transform="translate(-511.000000, -20.000000)" fill="#000000">
                                            <g>
                                                <path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <div style="padding-top: 8px;">
                        <div style="color:#3897f0; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:550; line-height:18px;">
                            Vezi pe Instagram
                        </div>
                    </div>
                    <div style="padding: 12.5% 0;"></div>
                </div>
                <p style="color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">
                    <a href="${link.href}" style="color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none;" target="_blank">
                        Vezi pe Instagram
                    </a>
                </p>
            </blockquote>
                `;
                
                link.parentNode.insertBefore(embed, link);
                link.style.display = 'none';
                
                // Load Instagram embed script if not already loaded
                if (!document.querySelector('script[src*="platform.instagram.com"]')) {
                    const script = document.createElement('script');
                    script.src = 'https://www.instagram.com/embed.js';
                    script.async = true;
                    document.head.appendChild(script);
                }
            }
        });
    }

    initLikeSystem() {
        const likeButtons = document.querySelectorAll('[data-like-article]');
        
        likeButtons.forEach(button => {
            const articleSlug = button.dataset.likeArticle;
            const currentLikes = this.getLikes(articleSlug);
            const hasLiked = this.hasUserLiked(articleSlug);
            
            this.updateLikeButton(button, currentLikes, hasLiked);
            
            button.addEventListener('click', () => {
                this.toggleLike(articleSlug, button);
            });
        });
    }

    toggleLike(articleSlug, button) {
        const hasLiked = this.hasUserLiked(articleSlug);
        let currentLikes = this.getLikes(articleSlug);
        
        if (hasLiked) {
            currentLikes = Math.max(0, currentLikes - 1);
            localStorage.removeItem(`liked_article_${articleSlug}`);
        } else {
            currentLikes += 1;
            localStorage.setItem(`liked_article_${articleSlug}`, 'true');
        }
        
        localStorage.setItem(`article_likes_${articleSlug}`, currentLikes.toString());
        this.updateLikeButton(button, currentLikes, !hasLiked);
        
        // Animate button
        button.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => {
            button.classList.remove('animate__animated', 'animate__pulse');
        }, 600);
    }

    updateLikeButton(button, likes, hasLiked) {
        const icon = hasLiked ? 'fas fa-heart' : 'far fa-heart';
        const colorClass = hasLiked ? 'text-danger' : 'text-muted';
        
        button.innerHTML = `
            <i class="${icon} me-1 ${colorClass}"></i>
            ${likes} ${likes === 1 ? 'Apreciere' : 'Aprecieri'}
        `;
        
        button.className = `btn btn-sm ${hasLiked ? 'btn-outline-danger' : 'btn-outline-secondary'}`;
    }

    getLikes(articleSlug) {
        return parseInt(localStorage.getItem(`article_likes_${articleSlug}`) || '0');
    }

    hasUserLiked(articleSlug) {
        return localStorage.getItem(`liked_article_${articleSlug}`) === 'true';
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
}

// Initialize social media system
let socialMedia;
document.addEventListener('DOMContentLoaded', function() {
    socialMedia = new SocialMediaSystem();
});
