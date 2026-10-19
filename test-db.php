<?php
require __DIR__ . '/db.php';
header('Content-Type: text/plain');
try {
    $pdo = db();
    echo "Connected OK to database: " . env_value('DB_NAME') . "\n";
    foreach (['users', 'items', 'claims'] as $t) {
        echo "Table $t: " . $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() . " rows\n";
    }
} catch (Throwable $e) {
    echo "Problem: " . $e->getMessage();
}
