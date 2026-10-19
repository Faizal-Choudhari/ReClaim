<?php
require __DIR__ . '/functions.php';
if (current_user()) redirect('index.php');
$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $st = db()->prepare('SELECT id, password, role FROM users WHERE email = ?'); $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        redirect($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php');
    }
    $error = 'Incorrect email or password.';
}
$pageTitle = 'Login'; require __DIR__ . '/header.php';
?>
<div class="card narrow">
  <h1>Log in</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" value="<?= h($email) ?>" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p class="muted">New here? <a href="register.php">Create an account</a></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
