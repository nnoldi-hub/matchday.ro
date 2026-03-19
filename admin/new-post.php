<?php
require_once(__DIR__ . '/../config/config.php');
if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

include(__DIR__ . '/../includes/header.php');
?>
<div class="container admin-card">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 m-0">Articol nou</h1>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-info btn-sm" onclick="previewPost()">Preview</button>
          <a class="btn btn-outline-secondary btn-sm" href="logout.php">Delogare</a>
        </div>
      </div>
      
      <form action="save-post.php" method="post" enctype="multipart/form-data" id="postForm">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Titlul articolului *</label>
            <input type="text" name="title" class="form-control" 
                   placeholder="Ex. Cronica meciului România - Italia" 
                   required maxlength="200" id="title">
            <div class="form-text">Slug: <span id="slug-preview"></span></div>
          </div>
          
          <div class="col-12 col-md-6">
            <label class="form-label">Data publicării *</label>
            <input type="date" name="date" class="form-control" 
                   value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          
          <div class="col-12 col-md-6">
            <label class="form-label">Categorie *</label>
            <select name="category" class="form-select" required>
              <option value="">Selectează categoria</option>
              <?php
              $categories = require(__DIR__ . '/../config/categories.php');
              foreach ($categories as $key => $category):
              ?>
              <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($category['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-12">
            <label class="form-label">Taguri</label>
            <input type="text" name="tags" class="form-control" 
                   placeholder="meci, transferuri, opinie" maxlength="500">
            <div class="form-text">Separate prin virgulă, maxim 10 taguri</div>
          </div>
          
          <div class="col-12 col-md-6">
            <label class="form-label">Cover (URL imagine)</label>
            <input type="url" name="cover" class="form-control" 
                   placeholder="https://..." maxlength="500">
            <div class="form-text">Are prioritate peste upload</div>
          </div>
          
          <div class="col-12 col-md-6">
            <label class="form-label">Upload imagine cover</label>
            <input type="file" name="cover_upload" class="form-control" 
                   accept=".jpg,.jpeg,.png,.webp,.gif" id="coverUpload">
            <div class="form-text">Max 5MB. JPG, PNG, WebP, GIF</div>
          </div>
          
          <div class="col-12">
            <label class="form-label">Conținut *</label>
            <div class="mb-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('bold')">Bold</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('italic')">Italic</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('link')">Link</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('quote')">Citat</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFormatting('image')">Imagine</button>
            </div>
            <textarea name="content" class="form-control" rows="16" 
                      placeholder="<p>Salut! Astăzi discutăm despre...</p>" 
                      required id="content"></textarea>
            <div class="form-text">
              HTML simplu permis. Primele 180 caractere vor fi excerptul.
              <br><strong>Caractere:</strong> <span id="char-count">0</span>
            </div>
          </div>
        </div>
        
        <div class="d-flex gap-2 mt-4">
          <button class="btn btn-accent" type="submit">Publică articolul</button>
          <button type="button" class="btn btn-outline-success" onclick="saveDraft()">Salvează draft</button>
          <button type="button" class="btn btn-outline-info" onclick="loadDraft()">Încarcă draft</button>
          <a href="../index.php" class="btn btn-outline-secondary">Anulează</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Preview articol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewContent">
      </div>
    </div>
  </div>
</div>

<script>
// Character counter
document.getElementById('content').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});

// Slug preview
document.getElementById('title').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('slug-preview').textContent = slug || 'articol';
});

// File size validation
document.getElementById('coverUpload').addEventListener('change', function() {
    if (this.files[0] && this.files[0].size > <?php echo MAX_UPLOAD_SIZE; ?>) {
        alert('Imaginea este prea mare! Maximum <?php echo round(MAX_UPLOAD_SIZE/1024/1024, 1); ?>MB.');
        this.value = '';
    }
});

// Formatting helpers
function addFormatting(type) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    let replacement = '';
    switch(type) {
        case 'bold':
            replacement = `<strong>${selectedText || 'text bold'}</strong>`;
            break;
        case 'italic':
            replacement = `<em>${selectedText || 'text italic'}</em>`;
            break;
        case 'link':
            const url = prompt('URL:');
            if (url) replacement = `<a href="${url}">${selectedText || 'text link'}</a>`;
            break;
        case 'quote':
            replacement = `<blockquote>${selectedText || 'Citatul aici'}</blockquote>`;
            break;
        case 'image':
            const imgUrl = prompt('URL imagine:');
            if (imgUrl) replacement = `<img src="${imgUrl}" alt="${selectedText || 'Descriere'}" class="img-fluid">`;
            break;
    }
    
    if (replacement) {
        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + replacement.length, start + replacement.length);
    }
}

// Draft functionality
function saveDraft() {
    const formData = new FormData(document.getElementById('postForm'));
    const draft = {
        title: formData.get('title'),
        date: formData.get('date'),
        tags: formData.get('tags'),
        cover: formData.get('cover'),
        content: formData.get('content'),
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('article_draft', JSON.stringify(draft));
    alert('Draft salvat!');
}

function loadDraft() {
    const draft = localStorage.getItem('article_draft');
    if (draft) {
        const data = JSON.parse(draft);
        document.querySelector('[name="title"]').value = data.title || '';
        document.querySelector('[name="date"]').value = data.date || '';
        document.querySelector('[name="tags"]').value = data.tags || '';
        document.querySelector('[name="cover"]').value = data.cover || '';
        document.querySelector('[name="content"]').value = data.content || '';
        alert(`Draft încărcat din ${new Date(data.timestamp).toLocaleString('ro-RO')}`);
    } else {
        alert('Nu există draft salvat!');
    }
}

// Preview functionality
function previewPost() {
    const title = document.querySelector('[name="title"]').value;
    const content = document.querySelector('[name="content"]').value;
    const tags = document.querySelector('[name="tags"]').value;
    const cover = document.querySelector('[name="cover"]').value;
    
    if (!title || !content) {
        alert('Completează titlul și conținutul pentru preview!');
        return;
    }
    
    const previewHtml = `
        <article class="container-narrow mx-auto" style="max-width: 800px;">
            <h1 class="h2 fw-bold mb-3">${title}</h1>
            ${cover ? `<img src="${cover}" class="img-fluid rounded mb-3" alt="cover">` : ''}
            <div class="post-content">${content}</div>
            ${tags ? `<div class="mt-3">${tags.split(',').map(t => `<span class="tag">${t.trim()}</span>`).join('')}</div>` : ''}
        </article>
    `;
    
    document.getElementById('previewContent').innerHTML = previewHtml;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>
<?php include(__DIR__ . '/../includes/footer.php'); ?>
