
<?php

require_once __DIR__ . '/db.php';

$email = 'admin@college.edu';

// Set your new admin password here
$newPassword = 'Admin@12345';

try {
    $pdo = db();

    $check = $pdo->prepare(
        'SELECT id FROM users WHERE email = ?'
    );
    $check->execute([$email]);

    $admin = $check->fetch();

    if (!$admin) {
        exit("Admin account not found: $email\n");
    }

    $hashedPassword = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    $update = $pdo->prepare(
        "UPDATE users SET password = ?, role = 'admin' WHERE email = ?"
    );

    $update->execute([$hashedPassword, $email]);

    echo "Admin password reset successfully!\n";
    echo "Email: $email\n";
    echo "New password: $newPassword\n";

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
