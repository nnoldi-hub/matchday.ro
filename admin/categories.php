<?php
/**
 * Admin Categories Management 
 * MatchDay.ro - CRUD Categories
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/Category.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

$message = '';
$messageType = 'info';
$editCategory = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }
        
        $action = $_POST['action'] ?? '';
        
        // Create category
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Numele categoriei este obligatoriu.');
            }
            
            // Check if slug exists
            $slug = Category::generateSlug($name);
            if (Category::getBySlug($slug)) {
                throw new Exception('O categorie cu acest nume există deja.');
            }
            
            Category::create([
                'name' => $name,
                'description' => $_POST['description'] ?? '',
                'color' => $_POST['color'] ?? '#007bff',
                'icon' => $_POST['icon'] ?? 'fas fa-folder',
                'sort_order' => $_POST['sort_order'] ?? 0
            ]);
            
            $message = 'Categoria a fost creată!';
            $messageType = 'success';
        }
        
        // Update category
        if ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID invalid.');
            
            Category::update($id, [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'color' => $_POST['color'] ?? '#007bff',
                'icon' => $_POST['icon'] ?? 'fas fa-folder',
                'sort_order' => $_POST['sort_order'] ?? 0
            ]);
            
            $message = 'Categoria a fost actualizată!';
            $messageType = 'success';
        }
        
        // Delete category
        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID invalid.');
            
            $category = Category::getById($id);
            if (!$category) throw new Exception('Categoria nu există.');
            
            $postCount = Category::countPosts($category['slug']);
            Category::delete($id);
            
            $msg = 'Categoria a fost ștearsă!';
            if ($postCount > 0) {
                $msg .= " ($postCount articole au rămas fără categorie)";
            }
            $message = $msg;
            $messageType = 'success';
        }
        
        // Sync from config
        if ($action === 'sync') {
            $count = Category::syncFromConfig();
            $message = $count > 0 ? "$count categorii importate din config!" : 'Toate categoriile sunt deja sincronizate.';
            $messageType = $count > 0 ? 'success' : 'info';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Edit mode
if (isset($_GET['edit'])) {
    $editCategory = Category::getById(intval($_GET['edit']));
}

// Get all categories
$categories = Category::getAll();
$icons = Category::getIconOptions();

$pageTitle = 'Categorii';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-folder me-2"></i>Categorii</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus me-1"></i>Categorie nouă
    </button>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Sync Button -->
<div class="mb-4">
    <form method="post" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
        <input type="hidden" name="action" value="sync">
        <button type="submit" class="btn btn-outline-info btn-sm">
            <i class="fas fa-sync me-1"></i>Sincronizează din config/categories.php
        </button>
    </form>
    <small class="text-muted ms-2">Importă categoriile din fișierul de configurare</small>
</div>

<!-- Categories Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Toate categoriile (<?= count($categories) ?>)</h2>
    </div>
    
    <?php if (empty($categories)): ?>
    <div class="text-center py-5">
        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
        <p class="text-muted mb-3">Nu există categorii. Începe prin a sincroniza din config sau creează una nouă.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Ord.</th>
                    <th>Categorie</th>
                    <th>Slug</th>
                    <th>Articole</th>
                    <th>Culoare</th>
                    <th style="width: 150px;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): 
                    $postCount = Category::countPosts($cat['slug']);
                ?>
                <tr>
                    <td>
                        <span class="badge bg-secondary"><?= $cat['sort_order'] ?></span>
                    </td>
                    <td>
                        <i class="<?= htmlspecialchars($cat['icon']) ?> me-2" style="color: <?= htmlspecialchars($cat['color']) ?>"></i>
                        <strong><?= htmlspecialchars($cat['name']) ?></strong>
                        <?php if ($cat['description']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($cat['description'], 0, 60, '...')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code><?= htmlspecialchars($cat['slug']) ?></code>
                    </td>
                    <td>
                        <?php if ($postCount > 0): ?>
                            <a href="posts.php?category=<?= urlencode($cat['slug']) ?>" class="text-decoration-none">
                                <?= $postCount ?> <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="d-inline-block rounded" style="width: 24px; height: 24px; background-color: <?= htmlspecialchars($cat['color']) ?>"></span>
                        <small class="ms-1"><?= htmlspecialchars($cat['color']) ?></small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Sigur ștergi categoria \'<?= htmlspecialchars(addslashes($cat['name'])) ?>\'?<?= $postCount > 0 ? "\\n\\n$postCount articole vor rămâne fără categorie." : '' ?>')">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Category Modal (Create/Edit) -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="categoryForm">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">
            <i class="fas fa-folder me-2"></i>Categorie nouă
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
          <input type="hidden" name="action" id="formAction" value="create">
          <input type="hidden" name="id" id="formId" value="">
          
          <div class="mb-3">
            <label class="form-label">Nume <span class="text-danger">*</span></label>
            <input type="text" name="name" id="catName" class="form-control" required maxlength="200">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Descriere</label>
            <textarea name="description" id="catDescription" class="form-control" rows="2" maxlength="500"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Culoare</label>
              <div class="input-group">
                <input type="color" name="color" id="catColor" class="form-control form-control-color" value="#007bff">
                <input type="text" class="form-control" id="catColorText" value="#007bff" maxlength="20">
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Ordine</label>
              <input type="number" name="sort_order" id="catOrder" class="form-control" value="0" min="0">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Icon</label>
            <select name="icon" id="catIcon" class="form-select">
              <?php foreach ($icons as $iconClass => $iconName): ?>
                <option value="<?= htmlspecialchars($iconClass) ?>"><?= htmlspecialchars($iconName) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="mt-2">
              <span id="iconPreview"><i class="fas fa-futbol fa-2x"></i></span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="fas fa-save me-1"></i>Salvează
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Color sync
document.getElementById('catColor')?.addEventListener('input', function() {
  document.getElementById('catColorText').value = this.value;
});
document.getElementById('catColorText')?.addEventListener('input', function() {
  if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
    document.getElementById('catColor').value = this.value;
  }
});

// Icon preview
document.getElementById('catIcon')?.addEventListener('change', function() {
  document.getElementById('iconPreview').innerHTML = `<i class="${this.value} fa-2x"></i>`;
});

// Edit category
function editCategory(cat) {
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează categoria';
  document.getElementById('formAction').value = 'update';
  document.getElementById('formId').value = cat.id;
  document.getElementById('catName').value = cat.name;
  document.getElementById('catDescription').value = cat.description || '';
  document.getElementById('catColor').value = cat.color || '#007bff';
  document.getElementById('catColorText').value = cat.color || '#007bff';
  document.getElementById('catOrder').value = cat.sort_order || 0;
  document.getElementById('catIcon').value = cat.icon || 'fas fa-folder';
  document.getElementById('iconPreview').innerHTML = `<i class="${cat.icon || 'fas fa-folder'} fa-2x"></i>`;
  
  new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

// Reset modal on close
document.getElementById('categoryModal')?.addEventListener('hidden.bs.modal', function() {
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-folder me-2"></i>Categorie nouă';
  document.getElementById('formAction').value = 'create';
  document.getElementById('formId').value = '';
  document.getElementById('categoryForm').reset();
  document.getElementById('catColor').value = '#007bff';
  document.getElementById('catColorText').value = '#007bff';
  document.getElementById('iconPreview').innerHTML = '<i class="fas fa-futbol fa-2x"></i>';
});
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
