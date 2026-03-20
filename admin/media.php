<?php
/**
 * Media Library
 * MatchDay.ro - Upload and manage images
 */
session_start();
require_once(__DIR__ . '/../config/config.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$uploadsDir = __DIR__ . '/../assets/uploads';
$uploadsUrl = '/assets/uploads';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

$message = '';
$messageType = 'info';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Eroare la upload: ' . $file['error']);
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Fișierul este prea mare. Maxim 5MB.');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipul fișierului nu este permis. Doar JPG, PNG, GIF, WebP.');
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $baseName = preg_replace('/[^a-z0-9\-_]/i', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $baseName = substr($baseName, 0, 50);
        $fileName = date('Y-m-d') . '-' . $baseName . '-' . substr(uniqid(), -6) . '.' . strtolower($ext);
        
        $targetPath = $uploadsDir . '/' . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Nu s-a putut salva fișierul.');
        }
        
        $message = 'Imagine încărcată cu succes!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        $fileName = basename($_POST['delete']);
        $filePath = $uploadsDir . '/' . $fileName;
        
        if (file_exists($filePath) && is_file($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
            if (unlink($filePath)) {
                $message = 'Imagine ștearsă!';
                $messageType = 'success';
            } else {
                throw new Exception('Nu s-a putut șterge fișierul.');
            }
        } else {
            throw new Exception('Fișierul nu există.');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all images
function getImages($dir) {
    $images = [];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!is_dir($dir)) return $images;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExt)) {
            $path = $dir . '/' . $file;
            $images[] = [
                'name' => $file,
                'size' => filesize($path),
                'modified' => filemtime($path),
                'url' => '/assets/uploads/' . $file
            ];
        }
    }
    
    // Sort by date, newest first
    usort($images, fn($a, $b) => $b['modified'] - $a['modified']);
    
    return $images;
}

$images = getImages($uploadsDir);
$totalSize = array_sum(array_column($images, 'size'));

include(__DIR__ . '/../includes/header.php');
?>

<div class="container admin-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">
      <i class="fas fa-images me-2"></i>Media Library
    </h1>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="fas fa-upload me-1"></i>Încarcă imagine
      </button>
      <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Dashboard
      </a>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
            <i class="fas fa-images fa-2x text-primary"></i>
          </div>
          <div>
            <h4 class="mb-0"><?= count($images) ?></h4>
            <small class="text-muted">Imagini</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3">
            <i class="fas fa-database fa-2x text-info"></i>
          </div>
          <div>
            <h4 class="mb-0"><?= number_format($totalSize / 1024 / 1024, 2) ?> MB</h4>
            <small class="text-muted">Spațiu folosit</small>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Images Grid -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Toate imaginile</h5>
    </div>
    <div class="card-body">
      <?php if (empty($images)): ?>
        <div class="text-center py-5">
          <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
          <p class="text-muted mb-3">Nu ai încărcat nicio imagine încă.</p>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i>Încarcă prima imagine
          </button>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($images as $img): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 image-card">
              <div class="position-relative">
                <img src="<?= htmlspecialchars($img['url']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($img['name']) ?>"
                     style="height: 150px; object-fit: cover; cursor: pointer;"
                     onclick="showPreview('<?= htmlspecialchars($img['url']) ?>', '<?= htmlspecialchars($img['name']) ?>')">
                <div class="position-absolute top-0 end-0 p-2">
                  <form method="post" class="d-inline" onsubmit="return confirm('Sigur ștergi această imagine?')">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="delete" value="<?= htmlspecialchars($img['name']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
              <div class="card-body p-2">
                <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($img['name']) ?>">
                  <?= htmlspecialchars($img['name']) ?>
                </small>
                <small class="text-muted">
                  <?= number_format($img['size'] / 1024, 1) ?> KB
                  · <?= date('d.m.Y', $img['modified']) ?>
                </small>
              </div>
              <div class="card-footer p-2 bg-light">
                <button class="btn btn-sm btn-outline-primary w-100" onclick="copyUrl('<?= htmlspecialchars($img['url']) ?>')">
                  <i class="fas fa-link me-1"></i>Copiază URL
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Încarcă imagine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
          
          <div class="mb-3">
            <label class="form-label">Selectează imagine</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
            <div class="form-text">JPG, PNG, GIF, WebP. Maxim 5MB.</div>
          </div>
          
          <div id="preview" class="text-center d-none">
            <img id="previewImg" src="" class="img-fluid rounded" style="max-height: 200px;">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload me-1"></i>Încarcă
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewTitle">Previzualizare</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-0">
        <img id="previewFullImg" src="" class="img-fluid">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" onclick="copyUrl(document.getElementById('previewFullImg').src)">
          <i class="fas fa-link me-1"></i>Copiază URL
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
      </div>
    </div>
  </div>
</div>

<style>
.image-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.image-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.image-card .btn-danger {
  opacity: 0;
  transition: opacity 0.2s;
}
.image-card:hover .btn-danger {
  opacity: 1;
}
</style>

<script>
// Preview before upload
document.querySelector('input[name="image"]')?.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImg').src = e.target.result;
      document.getElementById('preview').classList.remove('d-none');
    };
    reader.readAsDataURL(file);
  }
});

// Copy URL to clipboard
function copyUrl(url) {
  const fullUrl = window.location.origin + url;
  navigator.clipboard.writeText(fullUrl).then(() => {
    // Show toast or alert
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
      <div class="toast show bg-success text-white">
        <div class="toast-body">
          <i class="fas fa-check me-2"></i>URL copiat în clipboard!
        </div>
      </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  }).catch(() => {
    alert('URL: ' + fullUrl);
  });
}

// Show full preview
function showPreview(url, name) {
  document.getElementById('previewFullImg').src = url;
  document.getElementById('previewTitle').textContent = name;
  new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
