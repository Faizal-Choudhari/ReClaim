<?php require_once __DIR__ . '/functions.php'; $me = current_user(); $pageTitle = $pageTitle ?? 'Home'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> | College Lost &amp; Found</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php">🔎 Lost &amp; Found</a>
    <button class="nav-toggle" aria-label="Menu" type="button">☰</button>
    <nav id="menu">
      <a href="index.php">Browse</a>
      <?php if ($me): ?>
        <a href="report.php?type=lost">Report Lost</a>
        <a href="report.php?type=found">Report Found</a>
        <a href="dashboard.php">My Dashboard</a>
        <?php if ($me['role'] === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="logout.php" class="btn btn-small btn-light">Logout (<?= h(explode(' ', $me['name'])[0]) ?>)</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php" class="btn btn-small btn-light">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach ($_SESSION['flash'] ?? [] as [$m, $t]): ?>
  <div class="alert alert-<?= h($t) ?>"><?= h($m) ?></div>
<?php endforeach; unset($_SESSION['flash']); ?>
