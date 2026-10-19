<?php
require __DIR__ . '/functions.php';
$me = require_login();
$type = ($_GET['type'] ?? $_POST['item_type'] ?? 'lost') === 'found' ? 'found' : 'lost';
$errors = []; $v = ['title' => '', 'description' => '', 'category' => '', 'location' => '', 'item_date' => date('Y-m-d')];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    if (mb_strlen($v['title']) < 3 || mb_strlen($v['title']) > 150) $errors[] = 'Title must be 3-150 characters.';
    if (mb_strlen($v['description']) < 10) $errors[] = 'Please describe the item in at least 10 characters.';
    if (!in_array($v['category'], CATEGORIES, true)) $errors[] = 'Choose a category.';
    if ($v['location'] === '' || mb_strlen($v['location']) > 150) $errors[] = 'Enter a location.';
    $d = DateTime::createFromFormat('Y-m-d', $v['item_date']);
    if (!$d || $d->format('Y-m-d') !== $v['item_date'] || $v['item_date'] > date('Y-m-d')) $errors[] = 'Enter a valid date (not in the future).';
    $photo = null;
    if (!$errors) { try { $photo = handle_upload('photo'); } catch (RuntimeException $e) { $errors[] = $e->getMessage(); } }
    if (!$errors) {
        db()->prepare('INSERT INTO items (user_id, title, description, category, location, item_type, item_date, photo) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$me['id'], $v['title'], $v['description'], $v['category'], $v['location'], $type, $v['item_date'], $photo]);
        flash('Your ' . $type . ' item report has been posted.');
        redirect('item.php?id=' . db()->lastInsertId());
    }
}
$pageTitle = 'Report ' . ucfirst($type) . ' Item'; require __DIR__ . '/header.php';
?>
<div class="card narrow wide">
  <h1>Report a <?= h($type) ?> item</h1>
  <p class="muted"><a href="report.php?type=<?= $type === 'lost' ? 'found' : 'lost' ?>">Switch to reporting a <?= $type === 'lost' ? 'found' : 'lost' ?> item</a></p>
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="item_type" value="<?= h($type) ?>">
    <label>Title<input name="title" value="<?= h($v['title']) ?>" placeholder="e.g. Black leather wallet" required></label>
    <label>Description<textarea name="description" rows="4" placeholder="Colour, brand, markings, contents…" required><?= h($v['description']) ?></textarea></label>
    <div class="row">
      <label>Category<select name="category" required><option value="">Select…</option>
        <?php foreach (CATEGORIES as $c): ?><option value="<?= h($c) ?>" <?= $v['category'] === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select></label>
      <label>Date <?= $type === 'lost' ? 'lost' : 'found' ?><input type="date" name="item_date" max="<?= date('Y-m-d') ?>" value="<?= h($v['item_date']) ?>" required></label>
    </div>
    <label>Location<input name="location" list="locs" value="<?= h($v['location']) ?>" placeholder="e.g. Library, Canteen" required>
      <datalist id="locs"><option>Library</option><option>Canteen</option><option>Main Building</option><option>Computer Lab</option><option>Sports Ground</option><option>Hostel</option><option>Bus Stop</option></datalist></label>
    <label>Photo (optional, JPG/PNG/WEBP/GIF, max 2 MB)<input type="file" name="photo" accept="image/*" id="photo"></label>
    <img id="preview" class="preview" alt="" hidden>
    <button class="btn" type="submit">Submit report</button>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
