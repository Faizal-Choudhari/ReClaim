<?php
require __DIR__ . '/functions.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $iid = (int)($_POST['item_id'] ?? 0);
    $own = db()->prepare('SELECT id FROM items WHERE id = ? AND user_id = ?'); $own->execute([$iid, $me['id']]);
    if ($own->fetch()) {
        if (($_POST['action'] ?? '') === 'close') { db()->prepare("UPDATE items SET status = 'closed' WHERE id = ?")->execute([$iid]); flash('Report closed.'); }
        if (($_POST['action'] ?? '') === 'delete') { delete_item($iid); flash('Report deleted.'); }
    }
    redirect('dashboard.php');
}
$items = db()->prepare('SELECT i.*, (SELECT COUNT(*) FROM claims c WHERE c.item_id = i.id AND c.status = "pending") AS pending FROM items i WHERE user_id = ? ORDER BY created_at DESC');
$items->execute([$me['id']]); $items = $items->fetchAll();
$claims = db()->prepare('SELECT c.*, i.title, i.item_type FROM claims c JOIN items i ON i.id = c.item_id WHERE c.claimant_id = ? ORDER BY c.created_at DESC');
$claims->execute([$me['id']]); $claims = $claims->fetchAll();
$pageTitle = 'My Dashboard'; require __DIR__ . '/header.php';
?>
<h1>Hello, <?= h($me['name']) ?></h1>
<div class="card profile-card">
  <h2>Student details</h2>
  <div class="row">
    <p><strong>USN:</strong> <?= h($me['usn'] ?? 'Not provided') ?></p>
    <p><strong>Contact number:</strong> <?= h($me['phone'] ?? 'Not provided') ?></p>
    <p><strong>Email:</strong> <?= h($me['email']) ?></p>
  </div>
</div>
<div class="card">
  <h2>My reports</h2>
  <?php if (!$items): ?><p class="muted">You haven't reported anything yet. <a href="report.php?type=lost">Report a lost item</a> or <a href="report.php?type=found">report a found item</a>.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <tr><th>Item</th><th>Type</th><th>Status</th><th>Pending claims</th><th>Actions</th></tr>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><a href="item.php?id=<?= (int)$it['id'] ?>"><?= h($it['title']) ?></a></td>
      <td><span class="badge badge-<?= h($it['item_type']) ?>"><?= h($it['item_type']) ?></span></td>
      <td><?= h(ucfirst($it['status'])) ?></td>
      <td><?= (int)$it['pending'] ?></td>
      <td>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
          <?php if ($it['status'] === 'open'): ?><button class="btn btn-small btn-light" name="action" value="close" data-confirm="Close this report?">Close</button><?php endif; ?>
          <button class="btn btn-small btn-danger" name="action" value="delete" data-confirm="Delete this report permanently?">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<div class="card">
  <h2>My claims</h2>
  <?php if (!$claims): ?><p class="muted">You have not submitted any claims.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <tr><th>Item</th><th>My message</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($claims as $c): ?>
    <tr>
      <td><a href="item.php?id=<?= (int)$c['item_id'] ?>"><?= h($c['title']) ?></a></td>
      <td><?= h(mb_strimwidth($c['message'], 0, 80, '…')) ?></td>
      <td><span class="badge badge-claim-<?= h($c['status']) ?>"><?= h(ucfirst($c['status'])) ?></span></td>
      <td><?= h(date('d M Y', strtotime($c['created_at']))) ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
