<?php
/**
 * Ad Widget - Frontend Display Helper
 * MatchDay.ro
 * 
 * Usage:
 *   require_once 'includes/AdWidget.php';
 *   echo AdWidget::render('sidebar');  // Display sidebar ad
 *   echo AdWidget::render('header');   // Display header banner
 */

require_once(__DIR__ . '/Ad.php');

class AdWidget {
    
    /**
     * Render an ad for a specific position
     * 
     * @param string $position Ad position (sidebar, header, footer, article-inline, article-content)
     * @param array $options Additional options (class, wrapper, etc.)
     * @return string HTML output
     */
    public static function render(string $position = 'sidebar', array $options = []): string {
        $ad = Ad::getOneActive($position);
        
        if (!$ad) {
            return '';
        }
        
        // Record impression
        Ad::recordImpression($ad['id']);
        
        // Build ad HTML
        return self::buildHtml($ad, $options);
    }
    
    /**
     * Render multiple ads for a position
     */
    public static function renderMultiple(string $position = 'sidebar', int $limit = 3, array $options = []): string {
        $ads = Ad::getActive($position, $limit);
        
        if (empty($ads)) {
            return '';
        }
        
        $output = '';
        foreach ($ads as $ad) {
            Ad::recordImpression($ad['id']);
            $output .= self::buildHtml($ad, $options);
        }
        
        return $output;
    }
    
    /**
     * Build HTML for single ad
     */
    private static function buildHtml(array $ad, array $options = []): string {
        $wrapperClass = $options['class'] ?? 'ad-widget';
        $wrapperClass .= ' ad-' . $ad['position'];
        
        $html = '<div class="' . $wrapperClass . '" data-ad-id="' . $ad['id'] . '">';
        
        // If ad has embed code, use it directly
        if (!empty($ad['code'])) {
            $html .= $ad['code'];
        }
        // Otherwise, display image with link
        elseif (!empty($ad['image'])) {
            $clickUrl = '/ad-click.php?id=' . $ad['id'];
            $target = !empty($ad['link']) ? ' target="_blank" rel="noopener sponsored"' : '';
            
            if (!empty($ad['link'])) {
                $html .= '<a href="' . htmlspecialchars($clickUrl) . '"' . $target . '>';
            }
            
            $html .= '<img src="' . htmlspecialchars($ad['image']) . '" ';
            $html .= 'alt="' . htmlspecialchars($ad['name']) . '" ';
            $html .= 'class="ad-image img-fluid" loading="lazy">';
            
            if (!empty($ad['link'])) {
                $html .= '</a>';
            }
        }
        
        // Add subtle sponsor label
        if (!isset($options['hide_label']) || !$options['hide_label']) {
            $html .= '<small class="ad-label text-muted">Sponsorizat</small>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render sidebar widget with title
     */
    public static function sidebar(string $title = 'Sponsor'): string {
        $ad = Ad::getOneActive('sidebar');
        
        if (!$ad) {
            return '';
        }
        
        Ad::recordImpression($ad['id']);
        
        $html = '<div class="card mb-4 ad-sidebar-widget">';
        $html .= '<div class="card-header"><strong>' . htmlspecialchars($title) . '</strong></div>';
        $html .= '<div class="card-body text-center">';
        $html .= self::buildHtml($ad, ['hide_label' => true, 'class' => 'ad-widget-inner']);
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Render header banner
     */
    public static function header(): string {
        $ad = Ad::getOneActive('header');
        
        if (!$ad) {
            return '';
        }
        
        Ad::recordImpression($ad['id']);
        
        $html = '<div class="ad-header-banner text-center py-2 bg-light">';
        $html .= self::buildHtml($ad, ['hide_label' => true, 'class' => '']);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render inline ad between articles (for listing pages)
     * Returns ad HTML to insert every N items
     */
    public static function articleInline(): string {
        return self::render('article-inline', ['class' => 'ad-article-inline my-4']);
    }
    
    /**
     * Render ad inside article content
     */
    public static function articleContent(): string {
        return self::render('article-content', ['class' => 'ad-article-content my-4 text-center']);
    }
    
    /**
     * Get CSS styles for ad widgets (include in your stylesheet)
     */
    public static function getStyles(): string {
        return '
            <style>
            .ad-widget { position: relative; margin-bottom: 1rem; }
            .ad-widget .ad-image { max-width: 100%; height: auto; }
            .ad-widget .ad-label { 
                display: block; 
                font-size: 10px; 
                text-transform: uppercase; 
                letter-spacing: 0.5px;
                opacity: 0.7;
                margin-top: 4px;
            }
            .ad-sidebar { max-width: 300px; margin: 0 auto; }
            .ad-header-banner { border-bottom: 1px solid #eee; }
            .ad-header-banner .ad-image { max-height: 90px; }
            .ad-article-inline { 
                padding: 1rem; 
                background: #f8f9fa; 
                border-radius: 8px; 
                text-align: center;
            }
            .ad-article-content {
                padding: 1rem;
                background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
                border-radius: 8px;
            }
            </style>
        ';
    }
}
