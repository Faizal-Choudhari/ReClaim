<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

const CATEGORIES = ['Electronics', 'Books & Notes', 'ID & Cards', 'Clothing', 'Bags', 'Keys', 'Wallets', 'Accessories', 'Sports', 'Other'];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header("Location: $url"); exit; }

function flash(string $msg, string $type = 'success'): void { $_SESSION['flash'][] = [$msg, $type]; }

function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $st = db()->prepare('SELECT id, name, email, usn, phone, role FROM users WHERE id = ?');
            $st->execute([$_SESSION['uid']]);
            $user = $st->fetch() ?: null;
        }
    }
    return $user;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('Please log in first.', 'error'); redirect('login.php'); }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Access denied: administrators only.'); }
    return $u;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400); exit('Invalid form token. Go back, refresh the page and try again.');
    }
}

/** Validate and save an uploaded photo. Returns file name or null if none uploaded. Throws RuntimeException on error. */
function handle_upload(string $field): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Photo upload failed. Try a smaller image.');
    if ($f['size'] > 2 * 1024 * 1024) throw new RuntimeException('Photo must be 2 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($types[$mime]) || @getimagesize($f['tmp_name']) === false) throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $name)) throw new RuntimeException('Could not save the photo.');
    return $name;
}
function delete_photo(?string $file): void {
    if ($file && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp|gif)$/', $file)) @unlink(__DIR__ . '/uploads/' . $file);
}

/** Approve or reject a claim. Approving marks the item as claimed and rejects other pending claims. */
function set_claim_status(int $claimId, string $status): void {
    if (!in_array($status, ['approved', 'rejected'], true)) return;
    $pdo = db();
    $st = $pdo->prepare('SELECT item_id FROM claims WHERE id = ?');
    $st->execute([$claimId]);
    $itemId = $st->fetchColumn();
    if (!$itemId) return;
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE claims SET status = ? WHERE id = ?')->execute([$status, $claimId]);
    if ($status === 'approved') {
        $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE item_id = ? AND id <> ? AND status = 'pending'")->execute([$itemId, $claimId]);
        $pdo->prepare("UPDATE items SET status = 'claimed' WHERE id = ?")->execute([$itemId]);
    }
    $pdo->commit();
}

function delete_item(int $id): void {
    $st = db()->prepare('SELECT photo FROM items WHERE id = ?');
    $st->execute([$id]);
    delete_photo($st->fetchColumn() ?: null);
    db()->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
}
