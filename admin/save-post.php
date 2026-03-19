<?php
require_once(__DIR__ . '/../config/config.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

try {
    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        throw new Exception('Token de securitate invalid.');
    }
    
    // Rate limiting for post creation
    $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::rateLimitCheck("post_$userIP", 3, 300)) {
        throw new Exception('Prea multe articole create. Încearcă din nou în 5 minute.');
    }
    
    // Input validation
    $title = Validator::required($_POST['title'] ?? '', 'Titlul');
    $title = Validator::maxLength($title, 200, 'Titlul');
    
    $date = Validator::required($_POST['date'] ?? '', 'Data');
    $date = Validator::date($date, 'Data');
    
    $tags = trim($_POST['tags'] ?? '');
    $tags = Validator::maxLength($tags, 500, 'Tagurile');
    
    $category = Validator::required($_POST['category'] ?? '', 'Categoria');
    // Validate category exists
    $categories = require(__DIR__ . '/../config/categories.php');
    if (!isset($categories[$category])) {
        throw new Exception('Categoria selectată nu este validă.');
    }
    
    $cover = Validator::url(trim($_POST['cover'] ?? ''), 'URL cover');
    
    $content = Validator::required($_POST['content'] ?? '', 'Conținutul');
    $content = Validator::maxLength($content, 50000, 'Conținutul');
    
    // Sanitize content (allow safe HTML)
    $allowedTags = '<p><br><strong><em><b><i><u><a><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><img><div><span>';
    $content = strip_tags($content, $allowedTags);
    
    // Handle file upload with enhanced security
    if ($cover === '' && isset($_FILES['cover_upload']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])) {
        $uploadFile = $_FILES['cover_upload'];
        
        // Validate file size
        if ($uploadFile['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('Imaginea este prea mare. Maximum ' . round(MAX_UPLOAD_SIZE/1024/1024, 1) . 'MB.');
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Tip de fișier neacceptat. Folosește JPG, PNG, WebP sau GIF.');
        }
        
        // Generate secure filename
        $ext = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowedExts)) {
            $ext = 'jpg';
        }
        
        $destName = date('Y-m-d') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = __DIR__ . '/../assets/uploads/' . $destName;
        
        // Ensure upload directory exists
        $uploadDir = dirname($destPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (move_uploaded_file($uploadFile['tmp_name'], $destPath)) {
            $cover = '../assets/uploads/' . $destName;
        } else {
            throw new Exception('Eroare la încărcarea imaginii.');
        }
    }
    
    // Process content and generate metadata
    $slug = slugify($title);
    $filename = $date . '-' . $slug . '.html';
    $path = __DIR__ . '/../posts/' . $filename;
    
    // Check for duplicate filename
    if (file_exists($path)) {
        $counter = 1;
        do {
            $newSlug = $slug . '-' . $counter;
            $filename = $date . '-' . $newSlug . '.html';
            $path = __DIR__ . '/../posts/' . $filename;
            $counter++;
        } while (file_exists($path) && $counter < 100);
        
        if (file_exists($path)) {
            throw new Exception('Nu se poate genera un filename unic pentru articol.');
        }
        $slug = $newSlug;
    }
    
    $plain = preg_replace('/\s+/', ' ', strip_tags($content));
    $excerpt = mb_substr($plain, 0, 180) . (mb_strlen($plain) > 180 ? '…' : '');
    
    $tagsArr = array_filter(array_map('trim', explode(',', $tags)));
    $tagsArr = array_slice($tagsArr, 0, 10); // Limit to 10 tags
    
    $meta = [
        'title' => $title,
        'date' => $date,
        'category' => $category,
        'cover' => $cover,
        'tags' => array_values($tagsArr),
        'excerpt' => $excerpt,
        'slug' => $slug,
        'created_at' => date('Y-m-d H:i:s'),
        'word_count' => str_word_count($plain)
    ];
    
    // Generate enhanced post HTML
    $postHtml = generatePostHTML($meta, $content);
    
    // Ensure posts directory exists
    $postsDir = dirname($path);
    if (!is_dir($postsDir)) {
        mkdir($postsDir, 0755, true);
    }
    
    // Save the post
    if (file_put_contents($path, $postHtml, LOCK_EX) === false) {
        throw new Exception('Nu s-a putut scrie fișierul articolului.');
    }
    
    // Clear relevant caches
    if (CACHE_ENABLED) {
        Cache::delete('posts_list');
        Cache::delete('recent_posts');
        Cache::clear(); // Clear all cache for simplicity
    }
    
    // Log the action (optional)
    error_log("Article created: $filename by IP: $userIP");
    
    header('Location: ../index.php?created=1');
    exit;
    
} catch (Exception $e) {
    // Log error
    error_log("Post creation error: " . $e->getMessage());
    
    // Redirect with error
    $errorMsg = urlencode($e->getMessage());
    header("Location: new-post.php?error=$errorMsg");
    exit;
}

function slugify($text) {
    $text = trim($text);
    
    // Romanian diacritics map
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'a', 'Â' => 'a', 'Î' => 'i', 'Ș' => 's', 'Ş' => 's', 'Ț' => 't', 'Ţ' => 't',
        'é' => 'e', 'è' => 'e', 'á' => 'a', 'à' => 'a', 'ó' => 'o', 'ò' => 'o',
        'ã' => 'a', 'õ' => 'o', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ß' => 'ss'
    ];
    
    $text = strtr($text, $map);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    
    return $text ?: 'articol';
}

function generatePostHTML($meta, $content) {
    $title = htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8');
    $excerpt = htmlspecialchars($meta['excerpt'], ENT_QUOTES, 'UTF-8');
    $cover = htmlspecialchars($meta['cover'], ENT_QUOTES, 'UTF-8');
    $slug = $meta['slug'];
    $date = $meta['date'];
    $tagsArr = $meta['tags'];
    
    // Enhanced meta tags for SEO
    $metaTags = '';
    if ($cover) {
        $metaTags .= '<meta property="og:image" content="' . $cover . '">' . "\n";
        $metaTags .= '<meta name="twitter:image" content="' . $cover . '">' . "\n";
    }
    
    $metaTags .= '<meta property="og:title" content="' . $title . '">' . "\n";
    $metaTags .= '<meta property="og:description" content="' . $excerpt . '">' . "\n";
    $metaTags .= '<meta property="og:type" content="article">' . "\n";
    $metaTags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $metaTags .= '<meta name="twitter:title" content="' . $title . '">' . "\n";
    $metaTags .= '<meta name="twitter:description" content="' . $excerpt . '">' . "\n";
    
    if (!empty($tagsArr)) {
        $metaTags .= '<meta name="keywords" content="' . htmlspecialchars(implode(', ', $tagsArr), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    return '<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $title . ' · ' . SITE_NAME . '</title>
    <meta name="description" content="' . $excerpt . '">
    ' . $metaTags . '
    <!-- david-meta:' . json_encode($meta, JSON_UNESCAPED_UNICODE) . '-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body{font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial,sans-serif;background:#F8FAFD;color:#0B132B}
        .top{background:#0E4D92;color:#fff}
        .container-narrow{max-width:920px}
        .tag{display:inline-block;background:#FFF9CF;color:#5d4a00;border:1px solid #FFE97A;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;margin-right:.25rem}
        .btn-accent{background:#F5E663;color:#1F1F1F;border-radius:.75rem}
        .btn-accent:hover{filter:brightness(.95);color:#1F1F1F}
        article.post-content img{max-width:100%;height:auto;border-radius:.75rem;margin:1rem 0}
        article.post-content blockquote{border-left:4px solid #0E4D92;padding-left:1rem;margin:1rem 0;color:#2b3a55;background:#f0f6ff;border-radius:.25rem;padding:1rem}
        .comments{background:#fff;border:1px solid #e9eefb;border-radius:1rem;padding:1rem}
        .reading-time{font-size:.9rem;color:#6b7b99}
        .share-buttons a{color:#6b7b99;text-decoration:none;margin-right:1rem}
        .share-buttons a:hover{color:#0E4D92}
    </style>
</head>
<body>
    <header class="top py-3">
        <div class="container container-narrow d-flex align-items-center justify-content-between">
            <a href="../index.php" class="text-decoration-none text-white fw-bold">' . SITE_NAME . '</a>
            <span class="small opacity-75">' . SITE_TAGLINE . '</span>
        </div>
    </header>
    
    <main class="container container-narrow my-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div class="small text-muted">' . date('d.m.Y', strtotime($date)) . '</div>
            <div class="reading-time">' . ceil($meta['word_count'] / 200) . ' min citire</div>
        </div>
        
        ' . (isset($meta['category']) ? '<div class="mb-2">' . getCategoryBadge($meta['category']) . '</div>' : '') . '
        
        <h1 class="h2 fw-bold">' . $title . '</h1>
        
        ' . ($cover ? '<img src="' . $cover . '" class="my-3 rounded w-100" alt="' . $title . '">' : '') . '
        
        <article class="post-content">' . $content . '</article>
        
        ' . (!empty($tagsArr) ? '<div class="mt-3">' . implode('', array_map(function($t) {
            return '<span class="tag">' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</span>';
        }, $tagsArr)) . '</div>' : '') . '
        
        <div class="share-buttons mt-4 py-3 border-top border-bottom">
            <div class="d-flex align-items-center">
                <span class="me-3">Distribuie:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) . '" target="_blank">Facebook</a>
                <a href="https://twitter.com/intent/tweet?text=' . urlencode($title) . '&url=' . urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) . '" target="_blank">Twitter</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) . '" target="_blank">LinkedIn</a>
            </div>
        </div>
        
        <section class="mt-4">
            <h2 class="h5">Comentarii</h2>
            <div class="comments" id="comments"></div>
            <form id="commentForm" class="mt-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input class="form-control" name="name" placeholder="Nume" required maxlength="50">
                    </div>
                    <div class="col-md-8">
                        <input class="form-control" name="message" placeholder="Mesaj" required maxlength="500">
                    </div>
                    <div class="col-12 d-none">
                        <input name="website" placeholder="Leave empty">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-accent" type="submit">Trimite comentariu</button>
                    </div>
                </div>
            </form>
        </section>
        
        <hr class="my-4">
        <div class="d-flex align-items-center justify-content-between">
            <a href="../index.php" class="btn btn-outline-primary">&larr; Înapoi la jurnal</a>
            <div class="small text-muted">© ' . date('Y') . ' • ' . SITE_NAME . '</div>
        </div>
    </main>
    
    <script>
        const slug = "' . $slug . '";
        
        async function loadComments() {
            try {
                const res = await fetch("../comments_api.php?slug=" + encodeURIComponent(slug));
                const data = await res.json();
                const wrap = document.getElementById("comments");
                
                if (!Array.isArray(data) || !data.length) {
                    wrap.innerHTML = \'<div class="text-muted small">Fii primul care comentează.</div>\';
                    return;
                }
                
                wrap.innerHTML = data.map(c => 
                    `<div class="mb-3 pb-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>${escapeHtml(c.name)}</strong>
                            <span class="text-muted small">${c.date}</span>
                        </div>
                        <div class="mt-1">${escapeHtml(c.message)}</div>
                    </div>`
                ).join("");
                
            } catch (e) {
                console.error("Error loading comments:", e);
            }
        }
        
        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str;
            return div.innerHTML;
        }
        
        document.getElementById("commentForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            // Honeypot check
            if (formData.get("website")) return;
            
            // Basic validation
            const name = formData.get("name").trim();
            const message = formData.get("message").trim();
            
            if (!name || !message) {
                alert("Completează toate câmpurile!");
                return;
            }
            
            if (name.length > 50 || message.length > 500) {
                alert("Textul este prea lung!");
                return;
            }
            
            formData.append("slug", slug);
            
            try {
                const submitBtn = e.target.querySelector(\'button[type="submit"]\');
                submitBtn.disabled = true;
                submitBtn.textContent = "Se trimite...";
                
                const res = await fetch("../comments_api.php", {
                    method: "POST",
                    body: formData
                });
                
                const result = await res.json();
                
                if (result.ok) {
                    e.target.reset();
                    loadComments();
                    alert("Comentariu trimis cu succes!");
                } else {
                    alert("Eroare la trimiterea comentariului: " + (result.error || "Necunoscut"));
                }
                
            } catch (error) {
                console.error("Error submitting comment:", error);
                alert("Eroare de conexiune. Încearcă din nou.");
            } finally {
                const submitBtn = e.target.querySelector(\'button[type="submit"]\');
                submitBtn.disabled = false;
                submitBtn.textContent = "Trimite comentariu";
            }
        });
        
        // Load comments on page load
        loadComments();
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
}

function getCategoryBadge($categoryKey) {
    static $categories = null;
    if ($categories === null) {
        $categories = require(__DIR__ . '/../config/categories.php');
    }
    
    if (!isset($categories[$categoryKey])) {
        return '';
    }
    
    $category = $categories[$categoryKey];
    return '<a href="../category.php?cat=' . urlencode($categoryKey) . '" class="badge text-decoration-none" style="background: ' . $category['color'] . '; color: white;"><i class="' . $category['icon'] . ' me-1"></i>' . htmlspecialchars($category['name']) . '</a>';
}
