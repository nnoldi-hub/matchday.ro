/**
 * Advanced Filtering System
 * MatchDay.ro - Filters, autocomplete, and search enhancements
 */

class MatchDayFilters {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.suggestionsBox = document.getElementById('searchSuggestions');
        this.filterForm = document.getElementById('filterForm');
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        this.setupAutocomplete();
        this.setupFilters();
        this.setupKeyboardNav();
        this.loadSavedFilters();
    }
    
    // ===== AUTOCOMPLETE =====
    setupAutocomplete() {
        if (!this.searchInput || !this.suggestionsBox) return;
        
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                this.hideSuggestions();
                return;
            }
            
            this.debounceTimer = setTimeout(() => {
                this.fetchSuggestions(query);
            }, 200);
        });
        
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= 2) {
                this.fetchSuggestions(this.searchInput.value);
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.suggestionsBox.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    async fetchSuggestions(query) {
        try {
            const response = await fetch(`/search-suggestions.php?q=${encodeURIComponent(query)}`);
            const suggestions = await response.json();
            this.renderSuggestions(suggestions, query);
        } catch (error) {
            console.error('Error fetching suggestions:', error);
        }
    }
    
    renderSuggestions(suggestions, query) {
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map((item, index) => {
            const title = this.highlightMatch(item.title, query);
            const category = item.category_name || '';
            const date = item.published_at ? new Date(item.published_at).toLocaleDateString('ro-RO') : '';
            
            return `
                <a href="${item.url}" class="list-group-item list-group-item-action suggestion-item" data-index="${index}">
                    <div class="d-flex align-items-center">
                        ${item.cover_image ? `<img src="${item.cover_image}" class="suggestion-thumb me-3" alt="">` : '<div class="suggestion-thumb-placeholder me-3"><i class="fas fa-newspaper"></i></div>'}
                        <div class="flex-grow-1">
                            <div class="suggestion-title">${title}</div>
                            <small class="text-muted">
                                ${category ? `<span class="badge bg-secondary me-2">${category}</span>` : ''}
                                ${date}
                            </small>
                        </div>
                    </div>
                </a>
            `;
        }).join('');
        
        this.suggestionsBox.innerHTML = html;
        this.suggestionsBox.style.display = 'block';
        this.selectedIndex = -1;
    }
    
    highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    hideSuggestions() {
        if (this.suggestionsBox) {
            this.suggestionsBox.style.display = 'none';
        }
    }
    
    // ===== KEYBOARD NAVIGATION =====
    setupKeyboardNav() {
        if (!this.searchInput) return;
        
        this.selectedIndex = -1;
        
        this.searchInput.addEventListener('keydown', (e) => {
            const items = this.suggestionsBox?.querySelectorAll('.suggestion-item');
            if (!items || items.length === 0) return;
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                    this.updateSelection(items);
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    this.updateSelection(items);
                    break;
                    
                case 'Enter':
                    if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                        e.preventDefault();
                        window.location.href = items[this.selectedIndex].href;
                    }
                    break;
                    
                case 'Escape':
                    this.hideSuggestions();
                    break;
            }
        });
    }
    
    updateSelection(items) {
        items.forEach((item, index) => {
            item.classList.toggle('active', index === this.selectedIndex);
        });
        
        if (this.selectedIndex >= 0) {
            items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }
    
    // ===== ADVANCED FILTERS =====
    setupFilters() {
        // Filter toggle button
        const filterToggle = document.getElementById('filterToggle');
        const filterPanel = document.getElementById('filterPanel');
        
        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', () => {
                filterPanel.classList.toggle('show');
                filterToggle.querySelector('i').classList.toggle('fa-chevron-down');
                filterToggle.querySelector('i').classList.toggle('fa-chevron-up');
            });
        }
        
        // Date range presets
        document.querySelectorAll('[data-date-preset]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.setDatePreset(btn.dataset.datePreset);
            });
        });
        
        // Clear filters
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }
        
        // Apply filters on change
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                this.applyFilters();
            });
        });
    }
    
    setDatePreset(preset) {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        
        if (!dateFrom || !dateTo) return;
        
        const today = new Date();
        let fromDate = new Date();
        
        switch (preset) {
            case 'today':
                fromDate = today;
                break;
            case 'week':
                fromDate.setDate(today.getDate() - 7);
                break;
            case 'month':
                fromDate.setMonth(today.getMonth() - 1);
                break;
            case 'year':
                fromDate.setFullYear(today.getFullYear() - 1);
                break;
        }
        
        dateFrom.value = fromDate.toISOString().split('T')[0];
        dateTo.value = today.toISOString().split('T')[0];
        
        // Update active state
        document.querySelectorAll('[data-date-preset]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.datePreset === preset);
        });
        
        this.applyFilters();
    }
    
    applyFilters() {
        const params = new URLSearchParams(window.location.search);
        
        // Get current search query
        const query = document.getElementById('searchInput')?.value || params.get('q') || '';
        
        // Gather filter values
        const category = document.getElementById('filterCategory')?.value || '';
        const dateFrom = document.getElementById('dateFrom')?.value || '';
        const dateTo = document.getElementById('dateTo')?.value || '';
        const sortBy = document.getElementById('sortBy')?.value || '';
        
        // Build new URL
        const newParams = new URLSearchParams();
        if (query) newParams.set('q', query);
        if (category) newParams.set('category', category);
        if (dateFrom) newParams.set('from', dateFrom);
        if (dateTo) newParams.set('to', dateTo);
        if (sortBy) newParams.set('sort', sortBy);
        
        // Save filters to localStorage
        this.saveFilters({ category, dateFrom, dateTo, sortBy });
        
        // Navigate with filters
        window.location.href = `${window.location.pathname}?${newParams.toString()}`;
    }
    
    clearFilters() {
        const query = document.getElementById('searchInput')?.value || '';
        localStorage.removeItem('matchday_filters');
        
        if (query) {
            window.location.href = `${window.location.pathname}?q=${encodeURIComponent(query)}`;
        } else {
            window.location.href = window.location.pathname;
        }
    }
    
    saveFilters(filters) {
        localStorage.setItem('matchday_filters', JSON.stringify(filters));
    }
    
    loadSavedFilters() {
        const saved = localStorage.getItem('matchday_filters');
        if (!saved) return;
        
        try {
            const filters = JSON.parse(saved);
            const params = new URLSearchParams(window.location.search);
            
            // Only apply saved filters if no filters in URL
            if (!params.has('category') && !params.has('from') && !params.has('to') && !params.has('sort')) {
                // Populate filter inputs with saved values
                if (filters.category && document.getElementById('filterCategory')) {
                    document.getElementById('filterCategory').value = filters.category;
                }
                if (filters.sortBy && document.getElementById('sortBy')) {
                    document.getElementById('sortBy').value = filters.sortBy;
                }
            }
        } catch (e) {
            console.error('Error loading saved filters:', e);
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.matchDayFilters = new MatchDayFilters();
});
