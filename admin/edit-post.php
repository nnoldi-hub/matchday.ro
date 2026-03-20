<?php
/**
 * Admin - Editare Articol
 * MatchDay.ro
 */
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Post.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$postId = (int)($_GET['id'] ?? 0);
if ($postId === 0) {
    header('Location: posts.php');
    exit;
}

$post = Post::getById($postId);
if (!$post) {
    $_SESSION['flash_error'] = 'Articolul nu a fost găsit.';
    header('Location: posts.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        // Validation
        $title = Validator::required($_POST['title'] ?? '', 'Titlul');
        $title = Validator::maxLength($title, 200, 'Titlul');
        
        $content = Validator::required($_POST['content'] ?? '', 'Conținutul');
        $content = Validator::maxLength($content, 100000, 'Conținutul');
        
        $category = $_POST['category'] ?? '';
        $tags = trim($_POST['tags'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $cover = trim($_POST['cover'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        
        // Handle file upload
        if ($cover === '' && isset($_FILES['cover_upload']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])) {
            $uploadFile = $_FILES['cover_upload'];
            
            if ($uploadFile['size'] > MAX_UPLOAD_SIZE) {
                throw new Exception('Imaginea este prea mare. Maximum ' . round(MAX_UPLOAD_SIZE/1024/1024, 1) . 'MB.');
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $uploadFile['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                throw new Exception('Tip de fișier neacceptat.');
            }
            
            $ext = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));
            $destName = date('Y-m-d') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destPath = __DIR__ . '/../assets/uploads/' . $destName;
            
            if (move_uploaded_file($uploadFile['tmp_name'], $destPath)) {
                $cover = '/assets/uploads/' . $destName;
            }
        }
        
        // Update post
        $updated = Post::update($postId, [
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'category_slug' => $category,
            'cover_image' => $cover ?: $post['cover_image'],
            'tags' => $tags,
            'status' => $status
        ]);
        
        if ($updated) {
            $success = 'Articolul a fost actualizat cu succes!';
            $post = Post::getById($postId); // Refresh data
        } else {
            throw new Exception('Eroare la actualizarea articolului.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get categories
$categories = require(__DIR__ . '/../config/categories.php');

$pageTitle = 'Editare Articol';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-edit me-2"></i>Editare articol</h1>
    <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="fas fa-eye me-1"></i> Vizualizează
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
    
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label">Titlu *</label>
                        <input type="text" name="title" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($post['title']) ?>" required maxlength="200">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($post['slug']) ?>" 
                               disabled readonly>
                        <div class="form-text">Slug-ul nu poate fi modificat.</div>
                    </div>
                    
                    <div class="mb-3">
                            <label class="form-label">Excerpt (rezumat)</label>
                            <textarea name="excerpt" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Conținut *</label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('bold')">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('italic')">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('link')">
                                    <i class="fas fa-link"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('image')">
                                    <i class="fas fa-image"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('h2')">H2</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('h3')">H3</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('quote')">
                                    <i class="fas fa-quote-left"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('ul')">
                                    <i class="fas fa-list-ul"></i>
                                </button>
                            </div>
                            <textarea name="content" id="content" class="form-control" rows="20" required><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Publish Box -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <strong>Publicare</strong>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Publicat</option>
                                <option value="archived" <?= $post['status'] === 'archived' ? 'selected' : '' ?>>Arhivat</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                Creat: <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?><br>
                                <?php if ($post['updated_at']): ?>
                                Modificat: <?= date('d.m.Y H:i', strtotime($post['updated_at'])) ?><br>
                                <?php endif; ?>
                                <?php if ($post['published_at']): ?>
                                Publicat: <?= date('d.m.Y H:i', strtotime($post['published_at'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Salvează modificările
                        </button>
                    </div>
                </div>
                
                <!-- Category -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <strong>Categorie</strong>
                    </div>
                    <div class="p-4">
                        <select name="category" class="form-select">
                            <option value="">Fără categorie</option>
                            <?php foreach ($categories as $key => $cat): ?>
                            <option value="<?= htmlspecialchars($key) ?>" 
                                    <?= $post['category_slug'] === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Tags -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <strong>Taguri</strong>
                    </div>
                    <div class="p-4">
                        <input type="text" name="tags" class="form-control" 
                               value="<?= htmlspecialchars($post['tags'] ?? '') ?>"
                               placeholder="tag1, tag2, tag3">
                        <div class="form-text">Separate prin virgulă</div>
                    </div>
                </div>
                
                <!-- Cover Image -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <strong>Imagine cover</strong>
                    </div>
                    <div class="p-4">
                        <?php if ($post['cover_image']): ?>
                        <div class="mb-3">
                            <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                                 class="img-fluid rounded" alt="Cover actual">
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">URL imagine</label>
                            <input type="url" name="cover" class="form-control" 
                                   value="<?= htmlspecialchars($post['cover_image'] ?? '') ?>"
                                   placeholder="https://...">
                        </div>
                        
                        <div>
                            <label class="form-label">Sau încarcă</label>
                            <input type="file" name="cover_upload" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.webp">
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <strong>Statistici</strong>
                    </div>
                    <div class="p-4">
                        <div class="d-flex justify-content-between">
                            <span>Vizualizări:</span>
                            <strong><?= number_format($post['views'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addFormatting(type) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selected = text.substring(start, end);
    
    let before = '', after = '';
    
    switch(type) {
        case 'bold':
            before = '<strong>'; after = '</strong>';
            break;
        case 'italic':
            before = '<em>'; after = '</em>';
            break;
        case 'link':
            const url = prompt('URL:', 'https://');
            if (url) {
                before = '<a href="' + url + '">'; 
                after = '</a>';
            }
            break;
        case 'image':
            const imgUrl = prompt('URL imagine:', 'https://');
            if (imgUrl) {
                before = '<img src="' + imgUrl + '" alt="'; 
                after = '" class="img-fluid">';
            }
            break;
        case 'h2':
            before = '<h2>'; after = '</h2>';
            break;
        case 'h3':
            before = '<h3>'; after = '</h3>';
            break;
        case 'quote':
            before = '<blockquote class="blockquote">'; after = '</blockquote>';
            break;
        case 'ul':
            before = '<ul>\n<li>'; after = '</li>\n</ul>';
            break;
    }
    
    if (before) {
        textarea.value = text.substring(0, start) + before + selected + after + text.substring(end);
        textarea.focus();
        textarea.selectionStart = start + before.length;
        textarea.selectionEnd = start + before.length + selected.length;
    }
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
