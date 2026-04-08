<?php
/**
 * Contribute Page
 * MatchDay.ro - Submit your article
 */

require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/Category.php');
require_once(__DIR__ . '/includes/Submission.php');

$pageTitle = 'Scrie un Articol - MatchDay.ro';
$pageDescription = 'Contribuie la MatchDay.ro! Trimite-ți articolul despre fotbal și fă parte din comunitatea noastră de jurnaliști sportivi.';

$categories = Category::getAll();
$message = '';
$messageType = '';
$submitted = false;
$submissionToken = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }
        
        // Rate limit check
        $ip = $_SERVER['REMOTE_ADDR'];
        $email = filter_var($_POST['author_email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        if (!Submission::checkRateLimit($email, $ip)) {
            throw new Exception('Ai atins limita de submisii pentru azi. Te rugăm să încerci mâine.');
        }
        
        // Handle featured image upload
        $featuredImage = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/uploads/submissions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES['featured_image']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Tip de imagine invalid. Acceptăm doar JPG, PNG sau WebP.');
            }
            
            if ($_FILES['featured_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Imaginea este prea mare. Maxim 5MB.');
            }
            
            $extension = match($fileType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            
            $filename = 'submission_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $destination)) {
                $featuredImage = '/assets/uploads/submissions/' . $filename;
            }
        }
        
        // Create submission
        $submissionData = [
            'title' => $_POST['title'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'content' => $_POST['content'] ?? '',
            'category_id' => $_POST['category_id'] ?: null,
            'author_name' => $_POST['author_name'] ?? '',
            'author_email' => $email,
            'author_bio' => $_POST['author_bio'] ?? '',
            'featured_image' => $featuredImage,
            'ip_address' => $ip
        ];
        
        $submissionId = Submission::create($submissionData);
        
        if ($submissionId) {
            // Get submission with token
            $submission = Submission::getById($submissionId);
            $submissionToken = $submission['token'];
            
            // Send confirmation email
            Submission::notifyContributor($submission, 'received');
            
            $submitted = true;
            $message = 'Mulțumim! Articolul tău a fost trimis cu succes.';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once(__DIR__ . '/includes/header.php');
?>

<div class="contribute-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <?php if ($submitted): ?>
                <!-- Success Message -->
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <h2 class="text-success mb-3">Articolul a fost trimis!</h2>
                        <p class="lead mb-4">
                            Mulțumim pentru contribuție! Echipa noastră editorială va analiza articolul tău 
                            și te va contacta în maxim 48 de ore.
                        </p>
                        <div class="bg-light p-4 rounded mb-4">
                            <p class="mb-2"><strong>Cod de urmărire:</strong></p>
                            <code class="fs-5"><?= htmlspecialchars($submissionToken) ?></code>
                            <p class="small text-muted mt-2 mb-0">
                                Păstrează acest cod pentru a verifica statusul articolului.
                            </p>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-1"></i>Acasă
                            </a>
                            <a href="/contribute.php" class="btn btn-success">
                                <i class="bi bi-pencil me-1"></i>Scrie Alt Articol
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-pen text-success me-2"></i>Scrie un Articol
                    </h1>
                    <p class="lead text-muted">
                        Fă parte din echipa MatchDay.ro! Trimite articolul tău și contribuie la jurnalismul sportiv.
                    </p>
                </div>
                
                <!-- Guidelines -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-lightbulb text-warning me-2"></i>Ghid pentru articole
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Minim 500 cuvinte</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Conținut original</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Informații verificate</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Ton profesionist</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Titlu atractiv</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Imagine de calitate</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Form -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-lg-5">
                        <form method="POST" action="" enctype="multipart/form-data" id="contributeForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <!-- Author Info -->
                            <div class="mb-4 pb-4 border-bottom">
                                <h5 class="mb-3"><i class="bi bi-person me-2"></i>Despre Tine</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="author_name" class="form-label">Nume complet *</label>
                                        <input type="text" class="form-control" id="author_name" name="author_name" 
                                               value="<?= htmlspecialchars($_POST['author_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="author_email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="author_email" name="author_email" 
                                               value="<?= htmlspecialchars($_POST['author_email'] ?? '') ?>" required>
                                        <small class="text-muted">Nu va fi afișat public</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="author_bio" class="form-label">Bio scurt (opțional)</label>
                                        <textarea class="form-control" id="author_bio" name="author_bio" rows="2" 
                                                  placeholder="Câteva cuvinte despre tine..."><?= htmlspecialchars($_POST['author_bio'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Article Details -->
                            <div class="mb-4 pb-4 border-bottom">
                                <h5 class="mb-3"><i class="bi bi-file-text me-2"></i>Articolul Tău</h5>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="title" class="form-label">Titlu *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                               placeholder="Un titlu captivant pentru articol" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="category_id" class="form-label">Categorie</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Selectează categoria</option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="excerpt" class="form-label">Rezumat (2-3 propoziții)</label>
                                        <textarea class="form-control" id="excerpt" name="excerpt" rows="2" 
                                                  placeholder="Un scurt rezumat care va apărea în listări..."><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="content" class="form-label">Conținut articol *</label>
                                        <textarea class="form-control" id="content" name="content" rows="15" 
                                                  placeholder="Scrie articolul tău aici... Minim 500 cuvinte." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted">
                                                Poți folosi formatare simplă: **bold**, *italic*, [link](url)
                                            </small>
                                            <small class="text-muted word-count">0 cuvinte</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Featured Image -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-image me-2"></i>Imagine Reprezentativă</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="featured_image" class="form-label">Încarcă o imagine (opțional)</label>
                                        <input type="file" class="form-control" id="featured_image" name="featured_image" 
                                               accept="image/jpeg,image/png,image/webp">
                                        <small class="text-muted">JPG, PNG sau WebP. Maxim 5MB. Dimensiuni recomandate: 1200x630px</small>
                                    </div>
                                    <div class="col-12">
                                        <div id="imagePreview" class="image-preview" style="display: none;">
                                            <img src="" alt="Preview" class="img-fluid rounded">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms -->
                            <div class="form-check mb-4">
                                <input type="checkbox" class="form-check-input" id="accept_terms" required>
                                <label class="form-check-label" for="accept_terms">
                                    Accept <a href="/termeni.php" target="_blank">termenii și condițiile</a> și 
                                    acord MatchDay.ro dreptul de a publica și edita conținutul trimis.
                                </label>
                            </div>
                            
                            <!-- Submit -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                    <i class="bi bi-save me-1"></i>Salvează Ciornă
                                </button>
                                <button type="submit" class="btn btn-success btn-lg px-5">
                                    <i class="bi bi-send me-1"></i>Trimite Articolul
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<style>
.contribute-page {
    background: linear-gradient(180deg, #f8f9fa 0%, #fff 100%);
    min-height: 80vh;
}

.image-preview {
    max-width: 400px;
    margin-top: 1rem;
}

.image-preview img {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#content {
    font-family: 'Georgia', serif;
    line-height: 1.6;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Word count
    const content = document.getElementById('content');
    const wordCount = document.querySelector('.word-count');
    
    if (content && wordCount) {
        content.addEventListener('input', function() {
            const words = this.value.trim().split(/\s+/).filter(w => w.length > 0);
            wordCount.textContent = words.length + ' cuvinte';
            
            if (words.length < 500) {
                wordCount.classList.add('text-danger');
                wordCount.classList.remove('text-success');
            } else {
                wordCount.classList.remove('text-danger');
                wordCount.classList.add('text-success');
            }
        });
        
        // Trigger initial count
        content.dispatchEvent(new Event('input'));
    }
    
    // Image preview
    const imageInput = document.getElementById('featured_image');
    const preview = document.getElementById('imagePreview');
    
    if (imageInput && preview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.style.display = 'block';
                    preview.querySelector('img').src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    }
    
    // Load draft
    const draft = localStorage.getItem('matchday_article_draft');
    if (draft) {
        const data = JSON.parse(draft);
        if (confirm('Ai o ciornă salvată. Vrei să o încarci?')) {
            document.getElementById('title').value = data.title || '';
            document.getElementById('excerpt').value = data.excerpt || '';
            document.getElementById('content').value = data.content || '';
            document.getElementById('category_id').value = data.category_id || '';
            
            content.dispatchEvent(new Event('input'));
        }
    }
});

function saveDraft() {
    const draft = {
        title: document.getElementById('title').value,
        excerpt: document.getElementById('excerpt').value,
        content: document.getElementById('content').value,
        category_id: document.getElementById('category_id').value,
        saved_at: new Date().toISOString()
    };
    
    localStorage.setItem('matchday_article_draft', JSON.stringify(draft));
    
    alert('Ciornă salvată local! Poți continua mai târziu.');
}
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>
