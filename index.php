<?php
require __DIR__ . '/functions.php';
$q = trim($_GET['q'] ?? ''); $cat = $_GET['category'] ?? ''; $loc = $_GET['location'] ?? ''; $type = $_GET['type'] ?? '';
$where = ["i.status = 'open'"]; $params = [];
if ($q !== '') { $where[] = '(i.title LIKE ? OR i.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if (in_array($cat, CATEGORIES, true)) { $where[] = 'i.category = ?'; $params[] = $cat; }
if ($loc !== '') { $where[] = 'i.location = ?'; $params[] = $loc; }
if (in_array($type, ['lost', 'found'], true)) { $where[] = 'i.item_type = ?'; $params[] = $type; }
$st = db()->prepare('SELECT i.*, u.name AS reporter FROM items i JOIN users u ON u.id = i.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY i.created_at DESC LIMIT 60');
$st->execute($params); $items = $st->fetchAll();
$locations = db()->query('SELECT DISTINCT location FROM items ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Browse Items'; require __DIR__ . '/header.php';
?>
<section class="hero">
  <h1>Lost something on campus?</h1>
  <p>Search reported items, or help a classmate by reporting what you found.</p>
  <p><a class="btn btn-light" href="report.php?type=lost">I lost something</a> <a class="btn btn-outline" href="report.php?type=found">I found something</a></p>
</section>

<form class="filters card" method="get">
  <input type="search" name="q" placeholder="Search keywords (e.g. black wallet)" value="<?= h($q) ?>">
  <select name="type">
    <option value="">Lost &amp; Found</option>
    <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>Lost only</option>
    <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found only</option>
  </select>
  <select name="category">
    <option value="">All categories</option>
    <?php foreach (CATEGORIES as $c): ?><option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
  </select>
  <select name="location">
    <option value="">All locations</option>
    <?php foreach ($locations as $l): ?><option value="<?= h($l) ?>" <?= $loc === $l ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Search</button>
</form>

<?php if (!$items): ?>
  <p class="card muted">No matching items. Try different keywords or filters.</p>
<?php else: ?>
<div class="grid">
<?php foreach ($items as $it): ?>
  <a class="item-card" href="item.php?id=<?= (int)$it['id'] ?>">
    <?php if ($it['photo']): ?><img src="uploads/<?= h($it['photo']) ?>" alt="<?= h($it['title']) ?>"><?php else: ?><div class="no-photo">No photo</div><?php endif; ?>
    <div class="item-body">
      <span class="badge badge-<?= h($it['item_type']) ?>"><?= h(ucfirst($it['item_type'])) ?></span>
      <h3><?= h($it['title']) ?></h3>
      <p class="muted"><?= h($it['category']) ?> · <?= h($it['location']) ?><br><?= h(date('d M Y', strtotime($it['item_date']))) ?></p>
    </div>
  </a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
