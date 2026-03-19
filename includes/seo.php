<?php
/**
 * SEO Manager for MatchDay.ro
 * Handles meta tags, structured data, sitemaps, and URL optimization
 */

class SEOManager {
    private $title = '';
    private $description = '';
    private $keywords = [];
    private $ogImage = '';
    private $canonicalUrl = '';
    private $publishedTime = '';
    private $modifiedTime = '';
    private $author = '';
    private $category = '';
    private $tags = [];
    private $articleType = 'article';
    private $structuredData = [];
    
    public function __construct() {
        $this->setDefaults();
    }
    
    private function setDefaults() {
        $this->title = SITE_NAME;
        $this->description = SITE_TAGLINE;
        $this->ogImage = $this->getBaseUrl() . 'assets/images/logo.png';
        $this->canonicalUrl = $this->getCurrentUrl();
                $this->siteName = 'MatchDay.ro';
        $this->author = 'David Nyikora';
        $this->publisher = 'MatchDay.ro';
    }
    
    public function setTitle($title, $appendSiteName = true) {
        if ($appendSiteName && !str_contains($title, SITE_NAME)) {
            $this->title = $title . ' - ' . SITE_NAME;
        } else {
            $this->title = $title;
        }
        return $this;
    }
    
    public function setDescription($description) {
        // Truncate to optimal length for search engines
        $this->description = mb_substr(trim(strip_tags($description)), 0, 155);
        return $this;
    }
    
    public function setKeywords($keywords) {
        if (is_array($keywords)) {
            $this->keywords = $keywords;
        } else {
            $this->keywords = array_map('trim', explode(',', $keywords));
        }
        return $this;
    }
    
    public function setOgImage($imagePath) {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $this->ogImage = $imagePath;
        } else {
            $this->ogImage = $this->getBaseUrl() . ltrim($imagePath, '/');
        }
        return $this;
    }
    
    public function setCanonicalUrl($url) {
        $this->canonicalUrl = $url;
        return $this;
    }
    
    public function setPublishedTime($datetime) {
        $this->publishedTime = date('c', strtotime($datetime));
        return $this;
    }
    
    public function setModifiedTime($datetime) {
        $this->modifiedTime = date('c', strtotime($datetime));
        return $this;
    }
    
    public function setAuthor($author) {
        $this->author = $author;
        return $this;
    }
    
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }
    
    public function setTags($tags) {
        if (is_array($tags)) {
            $this->tags = $tags;
        } else {
            $this->tags = array_map('trim', explode(',', $tags));
        }
        return $this;
    }
    
    public function setArticleType($type) {
        $this->articleType = $type; // article, blog, news, sport
        return $this;
    }
    
    public function generateMetaTags() {
        $output = PHP_EOL;
        
        // Basic meta tags
        $output .= '    <title>' . htmlspecialchars($this->title) . '</title>' . PHP_EOL;
        $output .= '    <meta name="description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        
        if (!empty($this->keywords)) {
            $output .= '    <meta name="keywords" content="' . htmlspecialchars(implode(', ', $this->keywords)) . '">' . PHP_EOL;
        }
        
        $output .= '    <meta name="author" content="' . htmlspecialchars($this->author) . '">' . PHP_EOL;
        $output .= '    <link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl) . '">' . PHP_EOL;
        
        // Open Graph tags
        $output .= '    <meta property="og:title" content="' . htmlspecialchars($this->title) . '">' . PHP_EOL;
        $output .= '    <meta property="og:description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        $output .= '    <meta property="og:image" content="' . htmlspecialchars($this->ogImage) . '">' . PHP_EOL;
        $output .= '    <meta property="og:url" content="' . htmlspecialchars($this->canonicalUrl) . '">' . PHP_EOL;
        $output .= '    <meta property="og:type" content="' . $this->articleType . '">' . PHP_EOL;
        $output .= '    <meta property="og:site_name" content="' . htmlspecialchars(SITE_NAME) . '">' . PHP_EOL;
        $output .= '    <meta property="og:locale" content="ro_RO">' . PHP_EOL;
        
        if ($this->publishedTime) {
            $output .= '    <meta property="article:published_time" content="' . $this->publishedTime . '">' . PHP_EOL;
        }
        
        if ($this->modifiedTime) {
            $output .= '    <meta property="article:modified_time" content="' . $this->modifiedTime . '">' . PHP_EOL;
        }
        
        if ($this->category) {
            $output .= '    <meta property="article:section" content="' . htmlspecialchars($this->category) . '">' . PHP_EOL;
        }
        
        foreach ($this->tags as $tag) {
            $output .= '    <meta property="article:tag" content="' . htmlspecialchars($tag) . '">' . PHP_EOL;
        }
        
        // Twitter Cards
        $output .= '    <meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
        $output .= '    <meta name="twitter:title" content="' . htmlspecialchars($this->title) . '">' . PHP_EOL;
        $output .= '    <meta name="twitter:description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        $output .= '    <meta name="twitter:image" content="' . htmlspecialchars($this->ogImage) . '">' . PHP_EOL;
        $output .= '    <meta name="twitter:site" content="@MatchDayRo">' . PHP_EOL;
        $output .= '    <meta name="twitter:creator" content="@DavidCocioaba">' . PHP_EOL;
        
        // Additional SEO tags
        $output .= '    <meta name="robots" content="index, follow, max-image-preview:large">' . PHP_EOL;
        $output .= '    <meta name="googlebot" content="index, follow">' . PHP_EOL;
        
        return $output;
    }
    
    public function generateStructuredData() {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];
        
        // Website schema
        $structuredData['@graph'][] = [
            '@type' => 'WebSite',
            '@id' => $this->getBaseUrl() . '#website',
            'url' => $this->getBaseUrl(),
            'name' => SITE_NAME,
            'description' => SITE_TAGLINE,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->getBaseUrl() . 'index.php?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        // Organization schema
        $structuredData['@graph'][] = [
            '@type' => 'Organization',
            '@id' => $this->getBaseUrl() . '#organization',
            'name' => SITE_NAME,
            'url' => $this->getBaseUrl(),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $this->getBaseUrl() . 'assets/images/logo.png'
            ],
            'sameAs' => [
                'https://facebook.com/matchdayro',
                'https://twitter.com/matchdayro',
                'https://instagram.com/matchdayro'
            ]
        ];
        
        // Article schema (if it's an article)
        if ($this->articleType === 'article' && !empty($this->publishedTime)) {
            $article = [
                '@type' => 'Article',
                '@id' => $this->canonicalUrl . '#article',
                'url' => $this->canonicalUrl,
                'headline' => $this->title,
                'description' => $this->description,
                'datePublished' => $this->publishedTime,
                'author' => [
                    '@type' => 'Person',
                    'name' => $this->author
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => SITE_NAME,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $this->getBaseUrl() . 'assets/images/logo.png'
                    ]
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $this->canonicalUrl
                ]
            ];
            
            if ($this->modifiedTime) {
                $article['dateModified'] = $this->modifiedTime;
            }
            
            if ($this->ogImage) {
                $article['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $this->ogImage
                ];
            }
            
            if (!empty($this->tags)) {
                $article['keywords'] = implode(', ', $this->tags);
            }
            
            if ($this->category) {
                $article['about'] = [
                    '@type' => 'Thing',
                    'name' => $this->category
                ];
            }
            
            $structuredData['@graph'][] = $article;
        }
        
        return '<script type="application/ld+json">' . PHP_EOL . 
               json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . 
               '</script>' . PHP_EOL;
    }
    
    public function generateBreadcrumbs($items) {
        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($items as $position => $item) {
            $breadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => isset($item['url']) ? $item['url'] : null
            ];
        }
        
        return '<script type="application/ld+json">' . PHP_EOL . 
               json_encode($breadcrumbs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . 
               '</script>' . PHP_EOL;
    }
    
    public static function generateFriendlyUrl($title, $date = null) {
        // Remove special characters and normalize
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Limit length
        $slug = substr($slug, 0, 60);
        $slug = rtrim($slug, '-');
        
        // Add date prefix if provided
        if ($date) {
            $datePrefix = date('Y-m-d', strtotime($date));
            $slug = $datePrefix . '-' . $slug;
        }
        
        return $slug;
    }
    
    public static function extractExcerpt($content, $length = 155) {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        if (strlen($content) <= $length) {
            return $content;
        }
        
        $excerpt = substr($content, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
    }
    
    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    public function render() {
        return $this->generateMetaTags() . $this->generateStructuredData();
    }
}
