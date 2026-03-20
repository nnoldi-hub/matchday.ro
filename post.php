<?php
/**
 * Single Post Display Page
 * MatchDay.ro - Article Viewer
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Post.php');
require_once(__DIR__ . '/includes/Comment.php');
require_once(__DIR__ . '/includes/Stats.php');

// Get post slug
$slug = isset($_GET['slug']) ? Security::sanitizeInput($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /index.php');
    exit;
}

// Get post from database
$post = Post::getBySlug($slug);

if (!$post) {
    // Try to find by ID if slug is numeric
    if (is_numeric($slug)) {
        $post = Post::getById((int)$slug);
    }
    
    if (!$post) {
        http_response_code(404);
        include(__DIR__ . '/404.php');
        exit;
    }
}

// Only show published posts (or all if admin)
if ($post['status'] !== 'published' && empty($_SESSION['david_logged'])) {
    http_response_code(404);
    include(__DIR__ . '/404.php');
    exit;
}

// Track view with real visitor analytics
Stats::trackView($post['id'], 'post');
Post::incrementViews($post['id']);

// Load categories for styling
$categories = require(__DIR__ . '/config/categories.php');
$category = isset($post['category_slug']) && isset($categories[$post['category_slug']]) 
    ? $categories[$post['category_slug']] 
    : null;

// Parse tags
$tags = !empty($post['tags']) ? explode(',', $post['tags']) : [];

// Get similar/related posts (improved algorithm using tags + category)
$relatedPosts = Post::getSimilar($post['id'], $post['category_slug'] ?? '', $post['tags'] ?? '', 4);

// Get comments
$comments = Comment::getByPost($post['slug']);
$commentCount = Comment::countByPost($post['slug']);

// SEO Configuration
$pageTitle = $post['title'];
$pageDescription = !empty($post['excerpt']) ? $post['excerpt'] : mb_substr(strip_tags($post['content']), 0, 160);
$pageKeywords = $tags;
$pageImage = $post['cover_image'] ?? '/assets/images/logo.png';
$pageType = 'article';
$publishedDate = $post['published_at'];
$modifiedDate = $post['updated_at'] ?? $post['published_at'];
$articleAuthor = $post['author'] ?? 'Admin';
$articleCategory = $post['category_name'] ?? '';
$articleTags = $tags;

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Acasă', 'url' => '/index.php'],
];
if ($category) {
    $breadcrumbs[] = ['name' => $category['name'], 'url' => '/category.php?cat=' . $post['category_slug']];
}
$breadcrumbs[] = ['name' => $post['title'], 'url' => '/post.php?slug=' . $post['slug']];

include(__DIR__ . '/includes/header.php');
?>

<article class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <?php foreach ($breadcrumbs as $bc): ?>
            <li class="breadcrumb-item<?= $bc === end($breadcrumbs) ? ' active' : '' ?>">
                <?php if ($bc !== end($breadcrumbs)): ?>
                <a href="<?= htmlspecialchars($bc['url']) ?>"><?= htmlspecialchars($bc['name']) ?></a>
                <?php else: ?>
                <span><?= htmlspecialchars(mb_substr($bc['name'], 0, 50)) ?>...</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <!-- Article Header -->
            <header class="mb-4">
                <?php if ($category): ?>
                <a href="/category.php?cat=<?= htmlspecialchars($post['category_slug']) ?>" class="text-decoration-none">
                    <span class="badge mb-2" style="background: <?= $category['color'] ?>; color: white;">
                        <i class="<?= $category['icon'] ?> me-1"></i><?= $category['name'] ?>
                    </span>
                </a>
                <?php endif; ?>
                
                <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                
                <?php if (!empty($post['excerpt'])): ?>
                <p class="lead text-muted"><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>
                
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-3">
                    <span><i class="far fa-calendar me-1"></i><?= date('d.m.Y', strtotime($post['published_at'])) ?></span>
                    <span><i class="far fa-user me-1"></i><?= htmlspecialchars($post['author'] ?? 'Admin') ?></span>
                    <span><i class="far fa-eye me-1"></i><?= number_format($post['views']) ?> vizualizări</span>
                    <span><i class="far fa-comment me-1"></i><?= $commentCount ?> comentarii</span>
                </div>

                <!-- Admin Edit Link -->
                <?php if (!empty($_SESSION['david_logged'])): ?>
                <div class="mb-3">
                    <a href="/admin/new-post.php?edit=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i>Editează articolul
                    </a>
                </div>
                <?php endif; ?>
            </header>

            <!-- Cover Image -->
            <?php if (!empty($post['cover_image'])): ?>
            <figure class="mb-4">
                <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                     alt="<?= htmlspecialchars($post['title']) ?>"
                     class="img-fluid rounded-3 shadow-sm w-100"
                     style="max-height: 500px; object-fit: cover;">
            </figure>
            <?php endif; ?>

            <!-- Article Content -->
            <div class="article-content mb-4">
                <?= $post['content'] ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($tags)): ?>
            <div class="mb-4">
                <i class="fas fa-tags me-2 text-muted"></i>
                <?php foreach ($tags as $tag): ?>
                <a href="/index.php?q=<?= urlencode(trim($tag)) ?>" class="badge bg-light text-dark text-decoration-none me-1">
                    <?= htmlspecialchars(trim($tag)) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Share Buttons -->
            <div class="share-buttons mb-4 p-3 bg-light rounded-3">
                <span class="me-2 fw-bold"><i class="fas fa-share-alt me-1"></i>Distribuie:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/post.php?slug=' . $post['slug']) ?>" 
                   target="_blank" class="btn btn-sm btn-primary me-1" rel="noopener">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/post.php?slug=' . $post['slug']) ?>&text=<?= urlencode($post['title']) ?>" 
                   target="_blank" class="btn btn-sm btn-info text-white me-1" rel="noopener">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . SITE_URL . '/post.php?slug=' . $post['slug']) ?>" 
                   target="_blank" class="btn btn-sm btn-success me-1" rel="noopener">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>

            <hr>

            <!-- Comments Section -->
            <section class="comments-section mb-4" id="comments">
                <h3 class="h5 mb-4">
                    <i class="far fa-comments me-2"></i>Comentarii (<?= $commentCount ?>)
                </h3>

                <!-- Comment Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="commentForm" data-post-slug="<?= htmlspecialchars($post['slug']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                            <div class="mb-3">
                                <label for="commentName" class="form-label">Numele tău</label>
                                <input type="text" class="form-control" id="commentName" name="name" required maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label for="commentContent" class="form-label">Comentariul tău</label>
                                <textarea class="form-control" id="commentContent" name="content" rows="3" required maxlength="1000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Trimite comentariul
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Comments List -->
                <div id="commentsList">
                    <?php if (empty($comments)): ?>
                    <p class="text-muted">Nu există comentarii încă. Fii primul care comentează!</p>
                    <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <i class="far fa-user me-1"></i><?= htmlspecialchars($comment['author_name']) ?>
                                </h6>
                                <small class="text-muted">
                                    <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                </small>
                            </div>
                            <p class="card-text mb-0"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Sidebar -->
        <aside class="col-lg-4">
            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-newspaper me-2"></i>Articole similare
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($relatedPosts as $related): ?>
                    <li class="list-group-item">
                        <a href="/post.php?slug=<?= htmlspecialchars($related['slug']) ?>" class="text-decoration-none">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($related['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($related['cover_image']) ?>" 
                                     alt="<?= htmlspecialchars($related['title']) ?>"
                                     class="me-3 rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($related['title']) ?></h6>
                                    <small class="text-muted"><?= date('d.m.Y', strtotime($related['published_at'])) ?></small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-folder me-2"></i>Categorii
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($categories as $catSlug => $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="/category.php?cat=<?= htmlspecialchars($catSlug) ?>" class="text-decoration-none">
                            <i class="<?= $cat['icon'] ?> me-2" style="color: <?= $cat['color'] ?>"></i>
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</article>

<script src="/assets/js/comments.js"></script>

<?php include(__DIR__ . '/includes/footer.php'); ?>
