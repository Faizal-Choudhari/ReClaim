<?php
require __DIR__ . '/functions.php';
$me = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? (int)$id : 0;

function load_item(int $id): ?array {
    $st = db()->prepare('SELECT i.*, u.name AS reporter, u.usn AS reporter_usn, u.phone AS reporter_phone FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
$item = load_item($id);
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/header.php';
    echo '<p class="card">Item not found.</p>';
    require __DIR__ . '/footer.php';
    exit;
}
$isOwner = $me && (int)$me['id'] === (int)$item['user_id'];
$isAdmin = $me && $me['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $me = require_login();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        // Deletion is restricted to the original reporter, not admins or other students.
        $own = db()->prepare('SELECT id, photo FROM items WHERE id = ? AND user_id = ?');
        $own->execute([$id, $me['id']]);
        $ownedItem = $own->fetch();
        if (!$ownedItem) {
            http_response_code(403);
            exit('You can delete only items you reported.');
        }
        db()->beginTransaction();
        try {
            // Remove dependent claims explicitly, then remove the report.
            db()->prepare('DELETE FROM claims WHERE item_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM items WHERE id = ? AND user_id = ?')->execute([$id, $me['id']]);
            db()->commit();
            delete_photo($ownedItem['photo'] ?? null);
            flash('Your item report was deleted.');
            redirect('dashboard.php');
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            throw $e;
        }
    } elseif ($action === 'claim') {
        $msg = trim($_POST['message'] ?? '');
        if ($isOwner) flash('You cannot claim your own report.', 'error');
        elseif ($item['status'] !== 'open') flash('This item is no longer open.', 'error');
        elseif (mb_strlen($msg) < 10) flash('Please write at least 10 characters to explain your claim.', 'error');
        else {
            $c = db()->prepare("SELECT COUNT(*) FROM claims WHERE item_id = ? AND claimant_id = ? AND status = 'pending'");
            $c->execute([$id, $me['id']]);
            if ($c->fetchColumn() > 0) flash('You already have a pending claim on this item.', 'error');
            else {
                db()->prepare('INSERT INTO claims (item_id, claimant_id, message) VALUES (?,?,?)')->execute([$id, $me['id'], $msg]);
                flash('Claim submitted. You can track it in your dashboard.');
            }
        }
    } elseif (in_array($action, ['approve', 'reject'], true) && ($isOwner || $isAdmin)) {
        $cid = (int)($_POST['claim_id'] ?? 0);
        $chk = db()->prepare('SELECT id FROM claims WHERE id = ? AND item_id = ?');
        $chk->execute([$cid, $id]);
        if ($chk->fetch()) {
            set_claim_status($cid, $action === 'approve' ? 'approved' : 'rejected');
            flash($action === 'approve' ? 'Claim approved.' : 'Claim rejected.');
        }
    }
    redirect('item.php?id=' . $id);
}

$claims = [];
if ($isOwner || $isAdmin) {
    $cs = db()->prepare('SELECT c.*, u.name, u.email, u.usn, u.phone FROM claims c JOIN users u ON u.id = c.claimant_id WHERE c.item_id = ? ORDER BY c.created_at DESC');
    $cs->execute([$id]);
    $claims = $cs->fetchAll();
}
$pageTitle = $item['title'];
require __DIR__ . '/header.php';
?>
<div class="detail card">
  <div class="detail-photo">
    <?php $photoFile = !empty($item['photo']) ? __DIR__ . '/uploads/' . basename($item['photo']) : ''; ?>
    <?php if ($photoFile && is_file($photoFile)): ?>
      <img src="uploads/<?= h(basename($item['photo'])) ?>" alt="<?= h($item['title']) ?>">
    <?php else: ?><div class="no-photo big">No photo</div><?php endif; ?>
  </div>
  <div>
    <span class="badge badge-<?= h($item['item_type']) ?>"><?= h(ucfirst($item['item_type'])) ?></span>
    <span class="badge badge-status-<?= h($item['status']) ?>"><?= h(ucfirst($item['status'])) ?></span>
    <h1><?= h($item['title']) ?></h1>
    <p><?= nl2br(h($item['description'])) ?></p>
    <ul class="meta">
      <li><strong>Category:</strong> <?= h($item['category']) ?></li>
      <li><strong>Location:</strong> <?= h($item['location']) ?></li>
      <li><strong>Date:</strong> <?= h(date('d M Y', strtotime($item['item_date']))) ?></li>
      <li><strong>Reported by:</strong> <?= h($item['reporter']) ?></li>
      <li><strong>Reporter USN:</strong> <?= h($item['reporter_usn'] ?? 'Not provided') ?></li>
      <li><strong>Contact number:</strong> <?= h($item['reporter_phone'] ?? 'Not provided') ?></li>
    </ul>
    <div class="item-actions">
      <?php if ($isOwner): ?>
        <a class="btn" href="edit-item.php?id=<?= (int)$item['id'] ?>">Edit Details</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this item permanently? This will also delete its claim requests.');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <button class="btn btn-danger" type="submit">Delete Item</button>
        </form>
      <?php endif; ?>
      <a class="btn btn-back" href="dashboard.php">&larr; Back to Dashboard</a>
    </div>
  </div>
</div>

<?php if ($item['status'] === 'open' && !$isOwner): ?>
<div class="card">
  <h2><?= $item['item_type'] === 'found' ? 'Is this yours? Submit a claim' : 'Found this item? Let the owner know' ?></h2>
  <?php if ($me): ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="claim">
    <label>Message<textarea name="message" rows="3" required placeholder="Describe identifying details only the owner would know (or where you found it)."></textarea></label>
    <button class="btn" type="submit">Submit claim</button>
  </form>
  <?php else: ?><p><a href="login.php">Log in</a> to submit a claim.</p><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($claims): ?>
<div class="card">
  <h2>Claims on this item</h2>
  <?php foreach ($claims as $c): ?>
    <div class="claim">
      <strong><?= h($c['name']) ?></strong> (<?= h($c['email']) ?> · USN: <?= h($c['usn'] ?? 'N/A') ?> · Contact: <?= h($c['phone'] ?? 'N/A') ?>) · <span class="badge badge-claim-<?= h($c['status']) ?>"><?= h(ucfirst($c['status'])) ?></span>
      <p><?= nl2br(h($c['message'])) ?></p>
      <?php if ($c['status'] === 'pending'): ?>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
        <button class="btn btn-small" name="action" value="approve">Approve</button>
        <button class="btn btn-small btn-danger" name="action" value="reject">Reject</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
