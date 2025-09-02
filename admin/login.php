<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

$error = '';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

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

        $password = Validator::required($_POST['password'] ?? '', 'Parola');

        // Check password using secure hash
        if (Security::verifyPassword($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['david_logged'] = true;
            $_SESSION['login_time'] = time();
            // Clear rate limit on successful login
            Cache::delete("login_$clientIP");
            header('Location: new-post.php');
            exit;
        } else {
            throw new Exception('Parola este greșită.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include(__DIR__ . '/../includes/header.php');
?>
<div class="container admin-card">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3">Autentificare autor</h1>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo Security::sanitizeInput($error); ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
        <div class="mb-3">
          <label class="form-label">Parola</label>
          <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-brand" type="submit">Intră</button>
          <a class="btn btn-outline-secondary" href="../index.php">Înapoi la site</a>
        </div>
        </form>
    </div>
  </div>
</div>
<?php include(__DIR__ . '/../includes/footer.php'); ?>
