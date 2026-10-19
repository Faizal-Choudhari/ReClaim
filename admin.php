<?php
require __DIR__ . '/functions.php';
$me = require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $tab = $_POST['tab'] ?? 'users';
    if ($a === 'toggle_role' && $id !== (int)$me['id']) {
        $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'student', 'admin') WHERE id = ?")->execute([$id]); flash('Role updated.');
    } elseif ($a === 'delete_user' && $id !== (int)$me['id']) {
        $ph = $pdo->prepare('SELECT photo FROM items WHERE user_id = ?'); $ph->execute([$id]);
        foreach ($ph->fetchAll(PDO::FETCH_COLUMN) as $p) delete_photo($p);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); flash('User and their reports deleted.');
    } elseif ($a === 'delete_item') { delete_item($id); flash('Report deleted.');
    } elseif ($a === 'item_status' && in_array($_POST['status'] ?? '', ['open', 'claimed', 'closed'], true)) {
        $pdo->prepare('UPDATE items SET status = ? WHERE id = ?')->execute([$_POST['status'], $id]); flash('Status updated.');
    } elseif (in_array($a, ['approve', 'reject'], true)) { set_claim_status($id, $a === 'approve' ? 'approved' : 'rejected'); flash($a === 'approve' ? 'Claim approved.' : 'Claim rejected.'); }
    redirect('admin.php?tab=' . urlencode($tab));
}
$tab = in_array($_GET['tab'] ?? '', ['users', 'items', 'claims'], true) ? $_GET['tab'] : 'users';
$stats = [
    'Users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Open reports' => $pdo->query("SELECT COUNT(*) FROM items WHERE status='open'")->fetchColumn(),
    'Pending claims' => $pdo->query("SELECT COUNT(*) FROM claims WHERE status='pending'")->fetchColumn(),
];
$pageTitle = 'Admin'; require __DIR__ . '/header.php';
?>
<h1>Administrator dashboard</h1>
<div class="stats"><?php foreach ($stats as $k => $n): ?><div class="stat"><strong><?= (int)$n ?></strong><span><?= h($k) ?></span></div><?php endforeach; ?></div>
<div class="tabs">
  <?php foreach (['users' => 'Users', 'items' => 'Reports', 'claims' => 'Claims'] as $k => $l): ?>
    <a href="admin.php?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>
<div class="card"><div class="table-wrap">
<?php if ($tab === 'users'): $rows = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll(); ?>
  <table><tr><th>Name</th><th>USN</th><th>Contact</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= h($r['name']) ?></td><td><?= h($r['usn'] ?? 'N/A') ?></td><td><?= h($r['phone'] ?? 'N/A') ?></td><td><?= h($r['email']) ?></td><td><?= h($r['role']) ?></td><td><?= h(date('d M Y', strtotime($r['created_at']))) ?></td>
    <td><?php if ((int)$r['id'] !== (int)$me['id']): ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="tab" value="users"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-small btn-light" name="action" value="toggle_role" data-confirm="Change this user's role?"><?= $r['role'] === 'admin' ? 'Make student' : 'Make admin' ?></button>
        <button class="btn btn-small btn-danger" name="action" value="delete_user" data-confirm="Delete this user and ALL their reports and claims?">Delete</button></form>
    <?php else: ?><span class="muted">(you)</span><?php endif; ?></td></tr>
  <?php endforeach; ?></table>
<?php elseif ($tab === 'items'): $rows = $pdo->query('SELECT i.*, u.name AS reporter FROM items i JOIN users u ON u.id = i.user_id ORDER BY i.created_at DESC')->fetchAll(); ?>
  <table><tr><th>Title</th><th>Type</th><th>Reporter</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><a href="item.php?id=<?= (int)$r['id'] ?>"><?= h($r['title']) ?></a></td><td><?= h($r['item_type']) ?></td><td><?= h($r['reporter']) ?></td>
    <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="tab" value="items"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="item_status">
      <select name="status" onchange="this.form.submit()"><?php foreach (['open', 'claimed', 'closed'] as $s): ?><option value="<?= h($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select></form></td>
    <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="tab" value="items"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <button class="btn btn-small btn-danger" name="action" value="delete_item" data-confirm="Delete this report permanently?">Delete</button></form></td></tr>
  <?php endforeach; ?></table>
<?php else: $rows = $pdo->query('SELECT c.*, i.title, u.name FROM claims c JOIN items i ON i.id = c.item_id JOIN users u ON u.id = c.claimant_id ORDER BY c.created_at DESC')->fetchAll(); ?>
  <table><tr><th>Item</th><th>Claimant</th><th>Message</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><a href="item.php?id=<?= (int)$r['item_id'] ?>"><?= h($r['title']) ?></a></td><td><?= h($r['name']) ?></td><td><?= h(mb_strimwidth($r['message'], 0, 90, '…')) ?></td>
    <td><span class="badge badge-claim-<?= h($r['status']) ?>"><?= h($r['status']) ?></span></td>
    <td><?php if ($r['status'] === 'pending'): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="tab" value="claims"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <button class="btn btn-small" name="action" value="approve">Approve</button> <button class="btn btn-small btn-danger" name="action" value="reject">Reject</button></form><?php endif; ?></td></tr>
  <?php endforeach; ?></table>
<?php endif; ?>
<?php if (!$rows): ?><p class="muted">Nothing here yet.</p><?php endif; ?>
</div></div>
<?php require __DIR__ . '/footer.php'; ?>
