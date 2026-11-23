<?php

/**
 * ReClaim - Common Functions
 * Session, authentication, CSRF, uploads and claims.
 */

// --------------------------------------------------
// SESSION INITIALIZATION
// --------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
    error_log('ReClaim session ID: ' . session_id());
error_log('ReClaim CSRF token: ' . ($_SESSION['csrf'] ?? 'NOT CREATED'));
}

// Database connection
require_once __DIR__ . '/db.php';

// --------------------------------------------------
// CATEGORIES
// --------------------------------------------------

const CATEGORIES = [
    'Electronics',
    'Books & Notes',
    'ID & Cards',
    'Clothing',
    'Bags',
    'Keys',
    'Wallets',
    'Accessories',
    'Sports',
    'Other'
];

// --------------------------------------------------
// GENERAL FUNCTIONS
// --------------------------------------------------

function h($s): string {
    return htmlspecialchars(
        (string)$s,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'][] = [$msg, $type];
}

// --------------------------------------------------
// USER AUTHENTICATION
// --------------------------------------------------

function current_user(): ?array {

    static $user = false;

    if ($user === false) {

        $user = null;

        if (!empty($_SESSION['uid'])) {

            $st = db()->prepare( 'SELECT id, name, email, usn, phone, role FROM users WHERE id = ?');

            $st->execute([$_SESSION['uid']]);

            $user = $st->fetch() ?: null;
        }
    }

    return $user;
}

function require_login(): array {

    $u = current_user();

    if (!$u) {

        flash('Please log in first.', 'error');

        redirect('login.php');
    }

    return $u;
}

function require_admin(): array {

    $u = require_login();

    if ($u['role'] !== 'admin') {

        http_response_code(403);

        exit('Access denied: administrators only.');
    }

    return $u;
}

// --------------------------------------------------
// CSRF PROTECTION
// --------------------------------------------------

function csrf_token(): string {

    if (empty($_SESSION['csrf'])) {

        $_SESSION['csrf'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string {

    return '<input type="hidden" name="csrf" value="' .
        h(csrf_token()) .
        '">';
}

function csrf_check(): void {

    $sessionToken = $_SESSION['csrf'] ?? '';

    $formToken = $_POST['csrf'] ?? '';

    if (
        !is_string($sessionToken) ||
        !is_string($formToken) ||
        $sessionToken === '' ||
        $formToken === '' ||
        !hash_equals($sessionToken, $formToken)
    ) {

        http_response_code(400);

        exit(
            'Invalid form token. Please refresh the page and try again.'
        );
    }
}

// --------------------------------------------------
// PHOTO UPLOAD
// Maximum size: 20 MB
// --------------------------------------------------

function handle_upload(string $field): ?string {

    // No photo uploaded
    if (
        !isset($_FILES[$field]) ||
        $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    $f = $_FILES[$field];

    // Check upload errors
    if ($f['error'] !== UPLOAD_ERR_OK) {

        if (
            $f['error'] === UPLOAD_ERR_INI_SIZE ||
            $f['error'] === UPLOAD_ERR_FORM_SIZE
        ) {

            throw new RuntimeException(
                'Photo must be 20 MB or smaller.'
            );
        }

        throw new RuntimeException(
            'Photo upload failed. Please try again.'
        );
    }

    // Validate file size
    if ($f['size'] > 20 * 1024 * 1024) {

        throw new RuntimeException(
            'Photo must be 20 MB or smaller.'
        );
    }

    // Validate temporary file
    if (
        !is_uploaded_file($f['tmp_name']) ||
        !is_readable($f['tmp_name'])
    ) {

        throw new RuntimeException(
            'Invalid uploaded photo.'
        );
    }

    // Validate MIME type
    $mime = (new finfo(FILEINFO_MIME_TYPE))
        ->file($f['tmp_name']);

    $types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    // Validate actual image content
    if (
        !isset($types[$mime]) ||
        @getimagesize($f['tmp_name']) === false
    ) {

        throw new RuntimeException(
            'Only JPG, PNG, WEBP or GIF images are allowed.'
        );
    }

    // Generate unique filename
    $name = bin2hex(random_bytes(12)) .
        '.' . $types[$mime];

    // Upload directory
    $uploadDir = __DIR__ . '/uploads/';

    // Create directory if missing
    if (!is_dir($uploadDir)) {

        if (
            !mkdir($uploadDir, 0755, true) &&
            !is_dir($uploadDir)
        ) {

            throw new RuntimeException(
                'Could not create the uploads directory.'
            );
        }
    }

    // Save uploaded photo
    if (
        !move_uploaded_file(
            $f['tmp_name'],
            $uploadDir . $name
        )
    ) {

        throw new RuntimeException(
            'Could not save the photo.'
        );
    }

    return $name;
}

// --------------------------------------------------
// DELETE PHOTO
// --------------------------------------------------

function delete_photo(?string $file): void {

    if (
        $file &&
        preg_match(
            '/^[a-f0-9]{24}\.(jpg|png|webp|gif)$/',
            $file
        )
    ) {

        @unlink(
            __DIR__ . '/uploads/' . $file
        );
    }
}

// --------------------------------------------------
// CLAIM APPROVAL / REJECTION
// --------------------------------------------------

function set_claim_status(
    int $claimId,
    string $status
): void {

    if (
        !in_array(
            $status,
            ['approved', 'rejected'],
            true
        )
    ) {
        return;
    }

    $pdo = db();

    $st = $pdo->prepare(
        'SELECT item_id FROM claims WHERE id = ?'
    );

    $st->execute([$claimId]);

    $itemId = $st->fetchColumn();

    if (!$itemId) {
        return;
    }

    $pdo->beginTransaction();

    try {

        // Update selected claim
        $pdo->prepare(
            'UPDATE claims SET status = ? WHERE id = ?'
        )->execute([
            $status,
            $claimId
        ]);

        if ($status === 'approved') {

            // Reject other pending claims
            $pdo->prepare(
                "UPDATE claims
                 SET status = 'rejected'
                 WHERE item_id = ?
                 AND id <> ?
                 AND status = 'pending'"
            )->execute([
                $itemId,
                $claimId
            ]);

            // Mark item as claimed
            $pdo->prepare(
                "UPDATE items
                 SET status = 'claimed'
                 WHERE id = ?"
            )->execute([$itemId]);
        }

        $pdo->commit();

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

// --------------------------------------------------
// DELETE ITEM
// --------------------------------------------------

function delete_item(int $id): void {

    $st = db()->prepare(
        'SELECT photo FROM items WHERE id = ?'
    );

    $st->execute([$id]);

    $photo = $st->fetchColumn();

    // Delete associated photo
    delete_photo($photo ?: null);

    // Delete database record
    db()->prepare(
        'DELETE FROM items WHERE id = ?'
    )->execute([$id]);
}