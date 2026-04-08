<?php
session_start();
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/User.php');
require_once(__DIR__ . '/../includes/Logger.php');

$error = '';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Create default admin if no users exist (migration helper)
User::createDefaultAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF protection
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($token)) {
            throw new Exception('Token de securitate invalid.');
        }

        // Rate limiting
        if (!Security::rateLimitCheck("login_$clientIP", MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME)) {
            throw new Exception('Prea multe încercări. Încearcă din nou în 15 minute.');
        }

        $username = trim($_POST['username'] ?? '');
        $password = Validator::required($_POST['password'] ?? '', 'Parola');
        
        // Multi-user authentication via database
        $user = User::authenticate($username, $password);
        
        if ($user) {
            $_SESSION['david_logged'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['login_time'] = time();
            // Clear rate limit on successful login
            Cache::delete("login_$clientIP");
            
            // Audit log successful login
            Logger::audit('LOGIN_SUCCESS', $user['id'], [
                'username' => $user['username'],
                'ip' => $clientIP
            ]);
            
            header('Location: dashboard.php');
            exit;
        }
        
        // Fallback: check legacy single-password (for migration)
        if (empty($username) && defined('ADMIN_PASSWORD_HASH') && Security::verifyPassword($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['david_logged'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = 'Admin';
            $_SESSION['login_time'] = time();
            Cache::delete("login_$clientIP");
            header('Location: dashboard.php');
            exit;
        }
        
        throw new Exception('Utilizator sau parolă incorectă.');
    } catch (Exception $e) {
        // Audit log failed login
        Logger::audit('LOGIN_FAILED', 0, [
            'username' => $username ?? 'unknown',
            'ip' => $clientIP,
            'reason' => $e->getMessage()
        ]);
        
        $error = $e->getMessage();
    }
}

include(__DIR__ . '/../includes/header.php');
?>
<div class="container admin-card">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3"><i class="fas fa-user-shield me-2"></i>Autentificare</h1>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo Security::sanitizeInput($error); ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        <div class="mb-3">
          <label class="form-label">Utilizator</label>
          <input type="text" name="username" class="form-control" required autocomplete="username" placeholder="admin">
        </div>
        <div class="mb-3">
          <label class="form-label">Parola</label>
          <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-brand" type="submit"><i class="fas fa-sign-in-alt me-1"></i>Intră</button>
          <a class="btn btn-outline-secondary" href="../index.php">Înapoi la site</a>
        </div>
        </form>
    </div>
  </div>
</div>
<?php include(__DIR__ . '/../includes/footer.php'); ?>
