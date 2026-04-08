<?php
/**
 * Admin Users Management
 * MatchDay.ro - Multi-user system with roles
 */
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/../includes/Logger.php');

if (empty($_SESSION['david_logged'])) { 
    header('Location: login.php'); 
    exit; 
}

// Only admins can manage users
$currentUserRole = $_SESSION['user_role'] ?? 'admin';
if ($currentUserRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        $error = 'Token de securitate invalid.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'editor';
                
                if (empty($username) || empty($password)) {
                    $error = 'Utilizatorul și parola sunt obligatorii.';
                } elseif (strlen($username) < 3) {
                    $error = 'Utilizatorul trebuie să aibă minim 3 caractere.';
                } elseif (strlen($password) < 6) {
                    $error = 'Parola trebuie să aibă minim 6 caractere.';
                } elseif (User::usernameExists($username)) {
                    $error = 'Acest nume de utilizator există deja.';
                } elseif (!empty($email) && User::emailExists($email)) {
                    $error = 'Acest email este deja folosit.';
                } else {
                    $id = User::create($username, $email, $password, $role);
                    if ($id) {
                        Logger::audit('USER_CREATE', $_SESSION['user_id'] ?? 0, [
                            'new_user_id' => $id,
                            'username' => $username,
                            'role' => $role
                        ]);
                        $message = 'Utilizator creat cu succes!';
                    } else {
                        $error = 'Eroare la crearea utilizatorului.';
                    }
                }
                break;
                
            case 'update':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'editor';
                
                if ($userId <= 0) {
                    $error = 'ID utilizator invalid.';
                } elseif (empty($username)) {
                    $error = 'Numele de utilizator este obligatoriu.';
                } elseif (User::usernameExists($username, $userId)) {
                    $error = 'Acest nume de utilizator există deja.';
                } elseif (!empty($email) && User::emailExists($email, $userId)) {
                    $error = 'Acest email este deja folosit.';
                } else {
                    $data = [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role
                    ];
                    if (!empty($password)) {
                        $data['password'] = $password;
                    }
                    
                    if (User::update($userId, $data)) {
                        Logger::audit('USER_UPDATE', $_SESSION['user_id'] ?? 0, [
                            'target_user_id' => $userId,
                            'username' => $username,
                            'role' => $role
                        ]);
                        $message = 'Utilizator actualizat!';
                    } else {
                        $error = 'Eroare la actualizarea utilizatorului.';
                    }
                }
                break;
                
            case 'delete':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $currentUserId = $_SESSION['user_id'] ?? 0;
                
                if ($userId <= 0) {
                    $error = 'ID utilizator invalid.';
                } elseif ($userId === $currentUserId) {
                    $error = 'Nu te poți șterge pe tine însuți.';
                } else {
                    if (User::delete($userId)) {
                        Logger::audit('USER_DELETE', $_SESSION['user_id'] ?? 0, [
                            'deleted_user_id' => $userId
                        ]);
                        $message = 'Utilizator șters!';
                    } else {
                        $error = 'Nu se poate șterge. Posibil ultimul admin.';
                    }
                }
                break;
        }
    }
}

// Get all users
$users = User::getAll();
$roles = User::getAvailableRoles();

$pageTitle = 'Utilizatori';
require_once(__DIR__ . '/admin-header.php');
?>

<!-- Page Header -->
<div class="admin-page-header">
    <h1><i class="fas fa-users me-2"></i>Gestionare Utilizatori</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openCreateModal()">
        <i class="fas fa-user-plus me-1"></i>Utilizator nou
    </button>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i><?= Security::sanitizeInput($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-1"></i><?= Security::sanitizeInput($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= count($users) ?></h3>
                <p>Total utilizatori</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-crown"></i></div>
            <div class="stat-content">
                <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></h3>
                <p>Administratori</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="fas fa-edit"></i></div>
            <div class="stat-content">
                <h3><?= count(array_filter($users, fn($u) => $u['role'] === 'editor')) ?></h3>
                <p>Editori</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3><?= count(array_filter($users, fn($u) => $u['last_login'] && strtotime($u['last_login']) > strtotime('-7 days'))) ?></h3>
                <p>Activi (7 zile)</p>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Lista utilizatori</h2>
        <span class="badge bg-primary"><?= count($users) ?> utilizatori</span>
    </div>
    
    <?php if (empty($users)): ?>
    <div class="text-center py-5">
        <i class="fas fa-users fa-3x text-muted mb-3"></i>
        <p class="text-muted">Nu există utilizatori.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilizator</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Ultima autentificare</th>
                    <th>Creat la</th>
                    <th class="text-center">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-muted">#<?= $user['id'] ?></td>
                    <td>
                        <strong><?= Security::sanitizeInput($user['username']) ?></strong>
                        <?php if (($user['id'] ?? 0) === ($_SESSION['user_id'] ?? 0)): ?>
                        <span class="badge bg-info ms-1">Tu</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= Security::sanitizeInput($user['email'] ?? '-') ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge bg-danger"><i class="fas fa-crown me-1"></i>Admin</span>
                        <?php else: ?>
                        <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>Editor</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small">
                        <?php if ($user['last_login']): ?>
                        <?= date('d.m.Y H:i', strtotime($user['last_login'])) ?>
                        <?php else: ?>
                        <span class="text-muted">Niciodată</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($user), ENT_QUOTES) ?>)"
                                    title="Editează">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (($user['id'] ?? 0) !== ($_SESSION['user_id'] ?? 0)): ?>
                            <button class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?= $user['id'] ?>, '<?= Security::sanitizeInput($user['username']) ?>')"
                                    title="Șterge">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Role Info -->
<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h2><span class="badge bg-danger me-2"><i class="fas fa-crown"></i></span>Administrator</h2>
            </div>
            <ul class="list-unstyled mb-0 p-3">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acces complet la toate funcțiile</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Poate gestiona utilizatori</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Poate modifica setările site-ului</li>
                <li><i class="fas fa-check text-success me-2"></i>Poate șterge orice conținut</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h2><span class="badge bg-secondary me-2"><i class="fas fa-edit"></i></span>Editor</h2>
            </div>
            <ul class="list-unstyled mb-0 p-3">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Poate crea și edita articole</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Poate gestiona comentariile</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Poate crea și edita sondaje</li>
                <li><i class="fas fa-times text-danger me-2"></i>Nu poate gestiona utilizatori sau setări</li>
            </ul>
        </div>
    </div>
</div>

<!-- User Modal (Create/Edit) -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="userForm">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-user-plus me-2"></i>Utilizator nou
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nume utilizator *</label>
                        <input type="text" name="username" id="inputUsername" class="form-control" required minlength="3">
                        <div class="form-text">Minim 3 caractere, fără spații</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="inputEmail" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="passwordLabel">Parolă *</label>
                        <input type="password" name="password" id="inputPassword" class="form-control" minlength="6">
                        <div class="form-text" id="passwordHelp">Minim 6 caractere</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="role" id="inputRole" class="form-select">
                            <?php foreach ($roles as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-accent" id="submitBtn">
                        <i class="fas fa-save me-1"></i>Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId" value="">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmare</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Sigur ștergi utilizatorul <strong id="deleteUsername"></strong>?</p>
                    <p class="small text-muted mb-0">Această acțiune este ireversibilă.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash me-1"></i>Șterge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Utilizator nou';
    document.getElementById('inputUsername').value = '';
    document.getElementById('inputEmail').value = '';
    document.getElementById('inputPassword').value = '';
    document.getElementById('inputPassword').required = true;
    document.getElementById('passwordLabel').innerHTML = 'Parolă *';
    document.getElementById('passwordHelp').innerHTML = 'Minim 6 caractere';
    document.getElementById('inputRole').value = 'editor';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Creează';
}

function openEditModal(user) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('userId').value = user.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editează utilizator';
    document.getElementById('inputUsername').value = user.username;
    document.getElementById('inputEmail').value = user.email || '';
    document.getElementById('inputPassword').value = '';
    document.getElementById('inputPassword').required = false;
    document.getElementById('passwordLabel').innerHTML = 'Parolă nouă (opțional)';
    document.getElementById('passwordHelp').innerHTML = 'Lasă gol pentru a păstra parola actuală';
    document.getElementById('inputRole').value = user.role;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Salvează';
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function confirmDelete(id, username) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once(__DIR__ . '/admin-footer.php'); ?>
