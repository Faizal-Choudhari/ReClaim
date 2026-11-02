<?php
require __DIR__ . '/functions.php';
$me = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? (int)$id : 0;
$st = db()->prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
$st->execute([$id, $me['id']]);
$item = $st->fetch();
if (!$item) { http_response_code(404); exit('Item not found, or you do not have permission to edit it.'); }
$v = ['title'=>$item['title'], 'description'=>$item['description'], 'category'=>$item['category'], 'location'=>$item['location'], 'item_date'=>$item['item_date']];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $key => $_) $v[$key] = trim($_POST[$key] ?? '');
    if (mb_strlen($v['title']) < 3 || mb_strlen($v['title']) > 150) $errors[] = 'Title must be 3-150 characters.';
    if (mb_strlen($v['description']) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!in_array($v['category'], CATEGORIES, true)) $errors[] = 'Choose a valid category.';
    if ($v['location'] === '' || mb_strlen($v['location']) > 150) $errors[] = 'Enter a valid location.';
    $d = DateTime::createFromFormat('Y-m-d', $v['item_date']);
    if (!$d || $d->format('Y-m-d') !== $v['item_date'] || $v['item_date'] > date('Y-m-d')) $errors[] = 'Enter a valid date (not in the future).';
    $newPhoto = null;
    if (!$errors) { try { $newPhoto = handle_upload('photo'); } catch (RuntimeException $e) { $errors[] = $e->getMessage(); } }
    if (!$errors) {
        if ($newPhoto !== null) {
            db()->prepare('UPDATE items SET title=?, description=?, category=?, location=?, item_date=?, photo=? WHERE id=? AND user_id=?')->execute([$v['title'],$v['description'],$v['category'],$v['location'],$v['item_date'],$newPhoto,$id,$me['id']]);
            delete_photo($item['photo'] ?? null);
        } else {
            db()->prepare('UPDATE items SET title=?, description=?, category=?, location=?, item_date=? WHERE id=? AND user_id=?')->execute([$v['title'],$v['description'],$v['category'],$v['location'],$v['item_date'],$id,$me['id']]);
        }
        flash('Item details updated successfully.');
        redirect('item.php?id=' . $id);
    }
}
$pageTitle = 'Edit Item'; require __DIR__ . '/header.php';
?>
<div class="card narrow wide">
  <h1>Edit item details</h1>
  <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Title<input name="title" maxlength="150" value="<?= h($v['title']) ?>" required></label>
    <label>Description<textarea name="description" rows="4" required><?= h($v['description']) ?></textarea></label>
    <div class="row">
      <label>Category<select name="category" required><option value="">Select…</option><?php foreach (CATEGORIES as $c): ?><option value="<?= h($c) ?>" <?= $v['category'] === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select></label>
      <label>Date<input type="date" name="item_date" max="<?= date('Y-m-d') ?>" value="<?= h($v['item_date']) ?>" required></label>
    </div>
    <label>Location<input name="location" maxlength="150" value="<?= h($v['location']) ?>" required></label>
    <?php if (!empty($item['photo']) && is_file(__DIR__ . '/uploads/' . basename($item['photo']))): ?><p>Current photo:</p><img class="preview" src="uploads/<?= h(basename($item['photo'])) ?>" alt="Current item photo"><?php endif; ?>
    <label>Replace photo (optional, JPG/PNG/WEBP/GIF, max 2 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"></label>
    <button class="btn" type="submit">Save Changes</button>
    <a class="btn btn-back" href="item.php?id=<?= $id ?>">Cancel</a>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
