<?php
require __DIR__ . '/functions.php';
if (current_user()) redirect('index.php');
$errors = []; $name = $email = $usn = $phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $usn = strtoupper(trim($_POST['usn'] ?? '')); $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Enter your name (2-100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Z0-9-]{4,30}$/', $usn)) $errors[] = 'Enter a valid USN (4-30 letters, numbers or hyphens).';
    if (!preg_match('/^[0-9+() -]{7,20}$/', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 7) $errors[] = 'Enter a valid contact number (7-20 characters).';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $st = db()->prepare('SELECT id FROM users WHERE email = ? OR usn = ?'); $st->execute([$email, $usn]);
        if ($st->fetch()) {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?'); $check->execute([$email]);
            $errors[] = $check->fetch() ? 'That email is already registered.' : 'That USN is already registered.';
        }
    }
    if (!$errors) {
        db()->prepare("INSERT INTO users (name, email, usn, phone, password, role) VALUES (?, ?, ?, ?, ?, 'student')")
            ->execute([$name, $email, $usn, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
        flash('Account created! Please log in.');
        redirect('login.php');
    }
}
$pageTitle = 'Register'; require __DIR__ . '/header.php';
?>
<div class="card narrow">
  <h1>Create account</h1>
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>Full name<input name="name" value="<?= h($name) ?>" autocomplete="name" required></label>
    <label>USN (University Seat Number)<input name="usn" value="<?= h($usn) ?>" maxlength="30" placeholder="e.g. 1BM24CS001" required></label>
    <label>Contact number<input type="tel" name="phone" value="<?= h($phone) ?>" maxlength="20" autocomplete="tel" placeholder="e.g. 9876543210" required></label>
    <label>Email<input type="email" name="email" value="<?= h($email) ?>" autocomplete="email" required></label>
    <label>Password (min 8 characters)<input type="password" name="password" required></label>
    <label>Confirm password<input type="password" name="password2" required></label>
    <button class="btn" type="submit">Register</button>
  </form>
  <p class="muted">Already registered? <a href="login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
